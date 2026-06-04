<?php

namespace Pterodactyl\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Services\Backups\DownloadLinkService;
use Pterodactyl\Services\Backups\UniversalBackupService;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;

class UploadBackupToGDriveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $backupId, protected int $universalBackupId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(
        DownloadLinkService $downloadLinkService,
        UniversalBackupService $universalBackupService,
        DaemonBackupRepository $daemonBackupRepository
    ): void {
        $backup = Backup::find($this->backupId);
        if (!$backup) {
            DB::table('universal_backups')->where('id', $this->universalBackupId)->update(['status' => 'failed']);
            return;
        }

        // Fetch first root admin user to sign backup download JWT token
        $user = User::where('root_admin', true)->first();
        if (!$user) {
            Log::error('No admin user found to sign backup download JWT token.');
            DB::table('universal_backups')->where('id', $this->universalBackupId)->update(['status' => 'failed']);
            return;
        }

        try {
            // 1. Get the download URL from Wings
            $downloadUrl = $downloadLinkService->handle($backup, $user);

            // 2. Get Google Drive Access Token
            $token = $universalBackupService->getAccessToken();
            if (empty($token)) {
                throw new \Exception('Failed to retrieve Google Drive access token.');
            }

            $folderId = config('pterodactyl.backups.gdrive_folder_id');
            $universalBackup = DB::table('universal_backups')->where('id', $this->universalBackupId)->first();
            $filename = $universalBackup->filename;

            // 3. Initiate Resumable Upload Session on Google Drive
            $metadata = ['name' => $filename];
            if (!empty($folderId)) {
                $metadata['parents'] = [$folderId];
            }

            $sessionResponse = Http::withToken($token)
                ->withHeaders([
                    'X-Upload-Content-Type' => 'application/x-gzip',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable', $metadata);

            if (!$sessionResponse->successful()) {
                throw new \Exception('Failed to initiate resumable upload session: ' . $sessionResponse->body());
            }

            $uploadUrl = $sessionResponse->header('Location');

            // 4. Open read stream from Wings (ignore SSL verification for daemon connectivity)
            $readStream = fopen($downloadUrl, 'r', false, stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]));

            if (!$readStream) {
                throw new \Exception('Failed to open connection stream to Wings: ' . $downloadUrl);
            }

            // 5. Stream PUT request to Google Drive
            $uploadResponse = Http::send('PUT', $uploadUrl, [
                'body' => $readStream,
            ]);

            @fclose($readStream);

            if (!$uploadResponse->successful()) {
                throw new \Exception('Google Drive stream upload failed: ' . $uploadResponse->body());
            }

            $fileId = $uploadResponse->json('id');
            if (empty($fileId)) {
                throw new \Exception('Google Drive upload response did not contain file ID.');
            }

            // 6. Delete backup on Wings node to free space
            try {
                $daemonBackupRepository->setServer($backup->server)->delete($backup);
            } catch (\Exception $de) {
                Log::warning('Failed to delete temporary backup on Wings: ' . $de->getMessage());
            }

            // 7. Delete native Backup model record on panel
            $backup->delete();

            // 8. Mark universal backup as completed
            DB::table('universal_backups')
                ->where('id', $this->universalBackupId)
                ->update([
                    'status' => 'completed',
                    'file_id' => $fileId,
                    'file_size' => $uploadResponse->json('size') ?? $backup->bytes,
                    'updated_at' => now(),
                ]);

            // Delete previous completed backups for the same server
            try {
                $oldServerBackups = DB::table('universal_backups')
                    ->where('server_id', $backup->server_id)
                    ->where('status', 'completed')
                    ->where('id', '!=', $this->universalBackupId)
                    ->get();

                foreach ($oldServerBackups as $old) {
                    if ($old->file_id) {
                        $universalBackupService->deleteFile($old->file_id);
                    }
                    DB::table('universal_backups')->where('id', $old->id)->delete();
                }
            } catch (\Exception $ex) {
                Log::warning('Failed to purge old server backups: ' . $ex->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('UploadBackupToGDriveJob failed: ' . $e->getMessage());
            DB::table('universal_backups')
                ->where('id', $this->universalBackupId)
                ->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);
        }
    }
}
