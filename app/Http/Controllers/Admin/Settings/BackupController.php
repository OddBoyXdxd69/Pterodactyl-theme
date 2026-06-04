<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Backups\UniversalBackupService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class BackupController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private UniversalBackupService $backupService
    ) {
    }

    /**
     * Display the backups administration index page.
     */
    public function index(): View
    {
        $nodes = Node::all();
        $servers = Server::all();
        $backups = DB::table('universal_backups')
            ->orderBy('created_at', 'desc')
            ->get();

        // Enforce readable formatting for file sizes
        foreach ($backups as $backup) {
            if ($backup->file_size) {
                $bytes = $backup->file_size;
                $label = array('B', 'KB', 'MB', 'GB', 'TB');
                for ($i = 0; $bytes >= 1024 && $i < count($label) - 1; $i++) {
                    $bytes /= 1024;
                }
                $backup->readable_size = round($bytes, 2) . ' ' . $label[$i];
            } else {
                $backup->readable_size = 'N/A';
            }

            if ($backup->server_id) {
                $server = Server::find($backup->server_id);
                $backup->server_name = $server ? $server->name : 'Deleted Server';
            } else {
                $backup->server_name = 'N/A';
            }
        }

        return view('admin.settings.backups', [
            'nodes' => $nodes,
            'servers' => $servers,
            'backups' => $backups,
        ]);
    }

    /**
     * Update Universal Backup credentials.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'pterodactyl:backups:enabled' => 'required|in:0,1',
            'pterodactyl:backups:gdrive_auth_method' => 'required|in:oauth2,service_account',
            'pterodactyl:backups:gdrive_client_id' => 'nullable|string',
            'pterodactyl:backups:gdrive_client_secret' => 'nullable|string',
            'pterodactyl:backups:gdrive_refresh_token' => 'nullable|string',
            'pterodactyl:backups:gdrive_service_account' => 'nullable|string',
            'pterodactyl:backups:gdrive_folder_id' => 'nullable|string',
            'pterodactyl:backups:schedule_enabled' => 'required|in:0,1',
            'pterodactyl:backups:schedule_interval' => 'required|integer|min:1',
            'pterodactyl:backups:schedule_type' => 'required|in:database,all_servers,both',
            'pterodactyl:backups:ignore_limits' => 'required|in:0,1',
        ]);

        $this->settings->set('settings::pterodactyl:backups:enabled', $request->input('pterodactyl:backups:enabled'));
        $this->settings->set('settings::pterodactyl:backups:gdrive_auth_method', $request->input('pterodactyl:backups:gdrive_auth_method'));
        $this->settings->set('settings::pterodactyl:backups:gdrive_client_id', $request->input('pterodactyl:backups:gdrive_client_id') ?? '');
        $this->settings->set('settings::pterodactyl:backups:gdrive_client_secret', $request->input('pterodactyl:backups:gdrive_client_secret') ?? '');
        $this->settings->set('settings::pterodactyl:backups:gdrive_refresh_token', $request->input('pterodactyl:backups:gdrive_refresh_token') ?? '');
        $this->settings->set('settings::pterodactyl:backups:gdrive_service_account', $request->input('pterodactyl:backups:gdrive_service_account') ?? '');
        $this->settings->set('settings::pterodactyl:backups:gdrive_folder_id', $request->input('pterodactyl:backups:gdrive_folder_id') ?? '');
        $this->settings->set('settings::pterodactyl:backups:schedule_enabled', $request->input('pterodactyl:backups:schedule_enabled'));
        $this->settings->set('settings::pterodactyl:backups:schedule_interval', $request->input('pterodactyl:backups:schedule_interval'));
        $this->settings->set('settings::pterodactyl:backups:schedule_type', $request->input('pterodactyl:backups:schedule_type'));
        $this->settings->set('settings::pterodactyl:backups:ignore_limits', $request->input('pterodactyl:backups:ignore_limits'));

        $this->alert->success('Universal Backups configurations have been successfully updated.')->flash();
        return redirect()->route('admin.settings.backups');
    }

    /**
     * Trigger database or server/node backups.
     */
    public function trigger(Request $request): RedirectResponse
    {
        if (!$this->settings->get('settings::pterodactyl:backups:enabled', false)) {
            $this->alert->danger('Universal Backups are currently disabled. Please enable them first.')->flash();
            return redirect()->route('admin.settings.backups');
        }

        $backupDatabase = $request->has('backup_database');
        $nodeId = $request->input('backup_node_id');
        $serverIds = $request->input('backup_servers', []);

        $triggeredCount = 0;

        if ($backupDatabase) {
            $success = $this->backupService->backupDatabase();
            if ($success) {
                $triggeredCount++;
            } else {
                $this->alert->danger('Database backup failed. Please check credentials or error logs.')->flash();
            }
        }

        $serversToBackup = [];
        if ($nodeId) {
            $serversToBackup = Server::where('node_id', $nodeId)->get();
        } elseif (!empty($serverIds)) {
            $serversToBackup = Server::whereIn('id', $serverIds)->get();
        }

        if (count($serversToBackup) > 0) {
            $initiateBackupService = app(\Pterodactyl\Services\Backups\InitiateBackupService::class);
            $ignoreLimits = $this->settings->get('settings::pterodactyl:backups:ignore_limits', '0') === '1';
            foreach ($serversToBackup as $server) {
                try {
                    if ($ignoreLimits) {
                        $server->backup_limit = 99999;
                    }
                    // Create native backup on Wings (asynchronous)
                    $backup = $initiateBackupService->handle($server, 'GDrive Backup ' . date('Y-m-d H:i:s'), true);

                    DB::table('universal_backups')->insert([
                        'node_id' => $server->node_id,
                        'server_id' => $server->id,
                        'backup_type' => 'server',
                        'status' => 'backing_up',
                        'file_id' => $backup->uuid, // store native backup uuid temporarily
                        'filename' => $server->name . '_backup_' . date('Y-m-d_H-i-s') . '.tar.gz',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $triggeredCount++;
                } catch (\Exception $e) {
                    $this->alert->danger('Failed to initiate backup for server ' . $server->name . ': ' . $e->getMessage())->flash();
                }
            }
        }

        if ($triggeredCount > 0) {
            $this->alert->success("Successfully triggered {$triggeredCount} backup task(s) on the nodes.")->flash();
        } else {
            $this->alert->warning('No backups were selected or executed.')->flash();
        }

        return redirect()->route('admin.settings.backups');
    }

    /**
     * Restore database or trigger server restore via Wings.
     */
    public function restore(Request $request): RedirectResponse
    {
        $backupId = $request->input('backup_id');
        $backup = DB::table('universal_backups')->where('id', $backupId)->first();

        if (!$backup) {
            $this->alert->danger('Backup record not found.')->flash();
            return redirect()->route('admin.settings.backups');
        }

        if ($backup->backup_type === 'database') {
            $success = $this->backupService->restoreDatabase($backup->file_id);
            if ($success) {
                $this->alert->success('Database backup has been successfully restored.')->flash();
            } else {
                $this->alert->danger('Database restore failed. Check logs for details.')->flash();
            }
        } else {
            // Restore server backup onto Node VPS
            $server = Server::find($backup->server_id);
            if (!$server) {
                $this->alert->danger('Target server not found.')->flash();
                return redirect()->route('admin.settings.backups');
            }

            try {
                // Create a temporary native Backup record in database to satisfy Wings API request
                $tempBackup = new \Pterodactyl\Models\Backup();
                $tempBackup->server_id = $server->id;
                $tempBackup->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
                $tempBackup->name = 'Temp Restore ' . $backup->id;
                $tempBackup->disk = 'local';
                $tempBackup->is_successful = true;
                $tempBackup->save();

                // Get download URL from Google Drive API with token query param
                $accessToken = $this->backupService->getAccessToken();
                $gdriveDownloadUrl = sprintf(
                    'https://www.googleapis.com/drive/v3/files/%s?alt=media&access_token=%s',
                    $backup->file_id,
                    $accessToken
                );

                // Update universal backup record status to restoring
                DB::table('universal_backups')
                    ->where('id', $backupId)
                    ->update(['status' => 'restoring', 'updated_at' => now()]);

                // Call Wings API directly to start restoration
                $daemonBackupRepository = app(\Pterodactyl\Repositories\Wings\DaemonBackupRepository::class);
                $daemonBackupRepository->setServer($server)->restore($tempBackup, $gdriveDownloadUrl, true);

                $this->alert->success('Server restore task has been initiated on the node VPS.')->flash();
            } catch (\Exception $e) {
                if (isset($tempBackup) && $tempBackup->exists) {
                    $tempBackup->delete();
                }
                DB::table('universal_backups')
                    ->where('id', $backupId)
                    ->update(['status' => 'failed', 'updated_at' => now()]);

                $this->alert->danger('Failed to initiate restore on Wings: ' . $e->getMessage())->flash();
            }
        }

        return redirect()->route('admin.settings.backups');
    }

    /**
     * Delete a backup from list and Google Drive.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $backupId = $request->input('backup_id');
        $backup = DB::table('universal_backups')->where('id', $backupId)->first();

        if ($backup) {
            if ($backup->file_id) {
                $this->backupService->deleteFile($backup->file_id);
            }
            DB::table('universal_backups')->where('id', $backupId)->delete();
            $this->alert->success('Backup record has been successfully deleted from database and Google Drive.')->flash();
        } else {
            $this->alert->danger('Backup record not found.')->flash();
        }

        return redirect()->route('admin.settings.backups');
    }
}
