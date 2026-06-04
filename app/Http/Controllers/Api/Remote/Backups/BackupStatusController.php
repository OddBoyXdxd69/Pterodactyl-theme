<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Pterodactyl\Models\Backup;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Remote\ReportBackupCompleteRequest;

class BackupStatusController extends Controller
{
    /**
     * BackupStatusController constructor.
     */
    public function __construct(private BackupManager $backupManager)
    {
    }

    /**
     * Handles updating the state of a backup.
     *
     * @throws \Throwable
     */
    public function index(ReportBackupCompleteRequest $request, string $backup): JsonResponse
    {
        // Get the node associated with the request.
        /** @var \Pterodactyl\Models\Node $node */
        $node = $request->attributes->get('node');

        /** @var Backup $model */
        $model = Backup::query()
            ->where('uuid', $backup)
            ->firstOrFail();

        // Check that the backup is "owned" by the node making the request.
        /** @var \Pterodactyl\Models\Server $server */
        $server = $model->server;
        if ($server->node_id !== $node->id) {
            throw new HttpForbiddenException('Requesting node does not have permission to access this server.');
        }

        if ($model->is_successful) {
            throw new BadRequestHttpException('Cannot update the status of a backup that is already marked as completed.');
        }

        $action = $request->boolean('successful') ? 'server:backup.complete' : 'server:backup.fail';
        $log = Activity::event($action)->subject($model, $model->server)->property('name', $model->name);

        $log->transaction(function () use ($model, $request) {
            $successful = $request->boolean('successful');

            $model->fill([
                'is_successful' => $successful,
                'is_locked' => $successful ? $model->is_locked : false,
                'checksum' => $successful ? ($request->input('checksum_type') . ':' . $request->input('checksum')) : null,
                'bytes' => $successful ? $request->input('size') : 0,
                'completed_at' => CarbonImmutable::now(),
            ])->save();

            // Check if we are using the s3 backup adapter.
            $adapter = $this->backupManager->adapter();
            if ($adapter instanceof S3Filesystem) {
                $this->completeMultipartUpload($model, $adapter, $successful, $request->input('parts'));
            }

            // Google Drive Universal Backups Hook
            $universalBackup = \Illuminate\Support\Facades\DB::table('universal_backups')
                ->where('file_id', $model->uuid)
                ->where('status', 'backing_up')
                ->first();

            if ($universalBackup) {
                if ($successful) {
                    dispatch(new \Pterodactyl\Jobs\UploadBackupToGDriveJob($model->id, $universalBackup->id));
                } else {
                    \Illuminate\Support\Facades\DB::table('universal_backups')
                        ->where('id', $universalBackup->id)
                        ->update(['status' => 'failed', 'updated_at' => now()]);
                }
            }
        });

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Handles toggling the restoration status of a server.
     *
     * @throws \Throwable
     */
    public function restore(Request $request, string $backup): JsonResponse
    {
        /** @var Backup $model */
        $model = Backup::query()->where('uuid', $backup)->firstOrFail();

        $node = $request->attributes->get('node');
        if (! $model->server->node->is($node)) {
            throw new HttpForbiddenException('Requesting node does not have permission to access this server.');
        }

        $model->server->update(['status' => null]);

        // Google Drive Universal Restore Hook
        $universalBackup = \Illuminate\Support\Facades\DB::table('universal_backups')
            ->where('server_id', $model->server_id)
            ->where('status', 'restoring')
            ->first();

        if ($universalBackup) {
            $successful = $request->boolean('successful');
            \Illuminate\Support\Facades\DB::table('universal_backups')
                ->where('id', $universalBackup->id)
                ->update([
                    'status' => $successful ? 'completed' : 'failed',
                    'updated_at' => now()
                ]);

            $model->delete();
        }

        Activity::event($request->boolean('successful') ? 'server:backup.restore-complete' : 'server.backup.restore-failed')
            ->subject($model, $model->server)
            ->property('name', $model->name)
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Marks a multipart upload in a given S3-compatible instance as failed or successful.
     *
     * @throws \Exception
     * @throws DisplayException
     */
    protected function completeMultipartUpload(Backup $backup, S3Filesystem $adapter, bool $successful, ?array $parts): void
    {
        if (empty($backup->upload_id)) {
            if (!$successful) {
                return;
            }

            throw new DisplayException('Cannot complete backup request: no upload_id present on model.');
        }

        $params = [
            'Bucket' => $adapter->getBucket(),
            'Key' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid),
            'UploadId' => $backup->upload_id,
        ];

        $client = $adapter->getClient();
        if (!$successful) {
            $client->execute($client->getCommand('AbortMultipartUpload', $params));

            return;
        }

        $params['MultipartUpload'] = [
            'Parts' => [],
        ];

        if (is_null($parts)) {
            $params['MultipartUpload']['Parts'] = $client->execute($client->getCommand('ListParts', $params))['Parts'];
        } else {
            foreach ($parts as $part) {
                $params['MultipartUpload']['Parts'][] = [
                    'ETag' => $part['etag'],
                    'PartNumber' => $part['part_number'],
                ];
            }
        }

        $client->execute($client->getCommand('CompleteMultipartUpload', $params));
    }
}
