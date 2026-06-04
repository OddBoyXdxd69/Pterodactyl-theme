<?php

namespace Pterodactyl\Services\Backups;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class UniversalBackupService
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    /**
     * Get a valid access token for Google Drive API.
     */
    public function getAccessToken(): ?string
    {
        $authMethod = $this->settings->get('settings::pterodactyl:backups:gdrive_auth_method', 'oauth2');

        if ($authMethod === 'service_account') {
            $saJson = $this->settings->get('settings::pterodactyl:backups:gdrive_service_account');
            if (empty($saJson)) {
                return null;
            }

            try {
                $sa = json_decode($saJson, true);
                if (empty($sa['private_key']) || empty($sa['client_email'])) {
                    return null;
                }

                $privateKey = $sa['private_key'];
                $clientEmail = $sa['client_email'];

                $now = time();
                $claimSet = [
                    'iss' => $clientEmail,
                    'scope' => 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/drive',
                    'aud' => 'https://oauth2.googleapis.com/token',
                    'exp' => $now + 3600,
                    'iat' => $now
                ];

                $header = ['alg' => 'RS256', 'typ' => 'JWT'];

                $base64UrlEncode = function ($data) {
                    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
                };

                $encodedHeader = $base64UrlEncode(json_encode($header));
                $encodedClaimSet = $base64UrlEncode(json_encode($claimSet));

                $signatureInput = $encodedHeader . '.' . $encodedClaimSet;
                openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
                $encodedSignature = $base64UrlEncode($signature);

                $jwt = $signatureInput . '.' . $encodedSignature;

                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt
                ]);

                return $response->json('access_token');
            } catch (\Exception $e) {
                Log::error('Service Account JWT Authentication failed: ' . $e->getMessage());
                return null;
            }
        } else {
            // OAuth2 Refresh Token
            $clientId = $this->settings->get('settings::pterodactyl:backups:gdrive_client_id');
            $clientSecret = $this->settings->get('settings::pterodactyl:backups:gdrive_client_secret');
            $refreshToken = $this->settings->get('settings::pterodactyl:backups:gdrive_refresh_token');

            if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
                return null;
            }

            try {
                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token'
                ]);

                return $response->json('access_token');
            } catch (\Exception $e) {
                Log::error('OAuth2 Refresh Token Authentication failed: ' . $e->getMessage());
                return null;
            }
        }
    }

    /**
     * Upload a local file to Google Drive.
     */
    public function uploadFile(string $localPath, string $filename, ?string $folderId = null): ?string
    {
        $token = $this->getAccessToken();
        if (empty($token)) {
            return null;
        }

        if (empty($folderId)) {
            $folderId = $this->settings->get('settings::pterodactyl:backups:gdrive_folder_id');
        }

        try {
            $metadata = ['name' => $filename];
            if (!empty($folderId)) {
                $metadata['parents'] = [$folderId];
            }

            $response = Http::withToken($token)
                ->attach('metadata', json_encode($metadata), 'metadata.json', ['Content-Type' => 'application/json'])
                ->attach('file', file_get_contents($localPath), $filename)
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::error('Google Drive Upload failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Google Drive Upload error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * List files in the Google Drive folder.
     */
    public function listFiles(?string $folderId = null): array
    {
        $token = $this->getAccessToken();
        if (empty($token)) {
            return [];
        }

        if (empty($folderId)) {
            $folderId = $this->settings->get('settings::pterodactyl:backups:gdrive_folder_id');
        }

        try {
            $q = "trashed = false";
            if (!empty($folderId)) {
                $q .= " and '{$folderId}' in parents";
            }

            $response = Http::withToken($token)
                ->get('https://www.googleapis.com/drive/v3/files', [
                    'q' => $q,
                    'fields' => 'files(id,name,mimeType,size,createdTime)',
                    'orderBy' => 'createdTime desc',
                    'pageSize' => 100
                ]);

            if ($response->successful()) {
                return $response->json('files') ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Google Drive List files failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a file from Google Drive.
     */
    public function deleteFile(string $fileId): bool
    {
        $token = $this->getAccessToken();
        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->delete("https://www.googleapis.com/drive/v3/files/{$fileId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Google Drive Delete file failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Perform database backup and upload it.
     */
    public function backupDatabase(): bool
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $db = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');

        $tempFile = tempnam(sys_get_temp_dir(), 'ptero_db_');
        $filename = 'panel_backup_' . date('Y-m-d_H-i-s') . '.sql';

        $cmd = sprintf(
            'mysqldump --no-tablespaces --host=%s --port=%s --user=%s --password=%s %s > %s 2>/dev/null',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($db),
            escapeshellarg($tempFile)
        );

        exec($cmd, $output, $result);

        if ($result !== 0) {
            Log::error('Database dump failed with exit code ' . $result);
            @unlink($tempFile);
            return false;
        }

        $fileSize = filesize($tempFile);
        $fileId = $this->uploadFile($tempFile, $filename);

        @unlink($tempFile);

        if ($fileId) {
            // Delete previous database backups from GDrive and DB
            try {
                $oldDbBackups = DB::table('universal_backups')
                    ->where('backup_type', 'database')
                    ->where('status', 'completed')
                    ->get();

                foreach ($oldDbBackups as $old) {
                    if ($old->file_id) {
                        $this->deleteFile($old->file_id);
                    }
                    DB::table('universal_backups')->where('id', $old->id)->delete();
                }
            } catch (\Exception $ex) {
                Log::warning('Failed to purge old database backups: ' . $ex->getMessage());
            }

            DB::table('universal_backups')->insert([
                'backup_type' => 'database',
                'status' => 'completed',
                'file_id' => $fileId,
                'filename' => $filename,
                'file_size' => $fileSize,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            return true;
        }

        return false;
    }

    /**
     * Restore database from file.
     */
    public function restoreDatabase(string $fileId): bool
    {
        $token = $this->getAccessToken();
        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->get("https://www.googleapis.com/drive/v3/files/{$fileId}", [
                    'alt' => 'media'
                ]);

            if (!$response->successful()) {
                return false;
            }

            $sqlContent = $response->body();
            $tempFile = tempnam(sys_get_temp_dir(), 'ptero_restore_');
            file_put_contents($tempFile, $sqlContent);

            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port');
            $db = config('database.connections.mysql.database');
            $user = config('database.connections.mysql.username');
            $pass = config('database.connections.mysql.password');

            $cmd = sprintf(
                'mysql --host=%s --port=%s --user=%s --password=%s %s < %s 2>/dev/null',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($db),
                escapeshellarg($tempFile)
            );

            exec($cmd, $output, $result);
            @unlink($tempFile);

            return $result === 0;
        } catch (\Exception $e) {
            Log::error('Database restore failed: ' . $e->getMessage());
            return false;
        }
    }
}
