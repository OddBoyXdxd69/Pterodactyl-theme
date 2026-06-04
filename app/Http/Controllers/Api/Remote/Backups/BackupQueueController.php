<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Backups\UniversalBackupService;

class BackupQueueController extends Controller
{
    public function __construct(private UniversalBackupService $backupService)
    {
    }

    /**
     * List all pending backups and restores for the requesting node.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Node $node */
        $node = $request->attributes->get('node');

        $backups = DB::table('universal_backups')
            ->where('node_id', $node->id)
            ->whereIn('status', ['pending', 'restore_pending'])
            ->get();

        $accessToken = $this->backupService->getAccessToken();
        $gdriveFolderId = config('pterodactyl.backups.gdrive_folder_id');

        $data = [];
        foreach ($backups as $backup) {
            $server = Server::find($backup->server_id);
            if (!$server) {
                DB::table('universal_backups')->where('id', $backup->id)->update(['status' => 'failed']);
                continue;
            }

            $action = $backup->status === 'restore_pending' ? 'restore' : 'backup';
            $nextStatus = $action === 'restore' ? 'restoring' : 'backing_up';

            // Update status
            DB::table('universal_backups')->where('id', $backup->id)->update(['status' => $nextStatus]);

            $data[] = [
                'backup_id' => $backup->id,
                'action' => $action,
                'server_uuid' => $server->uuid,
                'gdrive_folder_id' => $gdriveFolderId,
                'file_id' => $backup->file_id,
                'filename' => $backup->filename,
                'access_token' => $accessToken,
            ];
        }

        return response()->json($data);
    }

    /**
     * Callback from Wings indicating backup/restore status.
     */
    public function callback(Request $request): JsonResponse
    {
        /** @var Node $node */
        $node = $request->attributes->get('node');

        $request->validate([
            'backup_id' => 'required|integer',
            'status' => 'required|in:success,failed',
            'file_id' => 'nullable|string',
            'filename' => 'nullable|string',
            'file_size' => 'nullable|integer',
        ]);

        $backupId = $request->input('backup_id');
        $status = $request->input('status');

        $backup = DB::table('universal_backups')
            ->where('id', $backupId)
            ->first();

        if (!$backup || $backup->node_id !== $node->id) {
            return response()->json(['error' => 'Backup record not found or unauthorized.'], 404);
        }

        if ($status === 'success') {
            $updateData = [
                'status' => 'completed',
                'updated_at' => now(),
            ];
            if ($request->has('file_id') && $request->input('file_id') !== null) {
                $updateData['file_id'] = $request->input('file_id');
            }
            if ($request->has('filename') && $request->input('filename') !== null) {
                $updateData['filename'] = $request->input('filename');
            }
            if ($request->has('file_size') && $request->input('file_size') !== null) {
                $updateData['file_size'] = $request->input('file_size');
            }
            DB::table('universal_backups')
                ->where('id', $backupId)
                ->update($updateData);
        } else {
            DB::table('universal_backups')
                ->where('id', $backupId)
                ->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);
        }

        return response()->json(['success' => true]);
    }
}
