<?php

namespace Pterodactyl\Console\Commands\Maintenance;

use Illuminate\Console\Command;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Services\Backups\UniversalBackupService;
use Pterodactyl\Services\Backups\InitiateBackupService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class UniversalBackupCommand extends Command
{
    protected $signature = 'ptero:universal-backup';

    protected $description = 'Executes automated Google Drive backups based on settings interval.';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private UniversalBackupService $backupService,
        private InitiateBackupService $initiateBackupService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $enabled = $this->settings->get('settings::pterodactyl:backups:schedule_enabled', '0');
        if ($enabled !== '1') {
            $this->info('Automated backups are currently disabled.');
            return;
        }

        $intervalHours = (int) $this->settings->get('settings::pterodactyl:backups:schedule_interval', '24');
        if ($intervalHours <= 0) {
            $this->warn('Invalid automated backups interval specified.');
            return;
        }

        $lastRunStr = $this->settings->get('settings::pterodactyl:backups:schedule_last_run');
        $lastRun = $lastRunStr ? strtotime($lastRunStr) : 0;

        $now = time();
        if (($now - $lastRun) < ($intervalHours * 3600)) {
            $this->info('Interval hour checks not met yet.');
            return;
        }

        $this->info('Starting automated Universal Backup...');

        $type = $this->settings->get('settings::pterodactyl:backups:schedule_type', 'both');

        // 1. Backup Database
        if ($type === 'both' || $type === 'database') {
            try {
                $this->info('Backing up panel database to Google Drive...');
                $this->backupService->backupDatabase();
            } catch (\Exception $e) {
                Log::error('Automated DB Backup failed: ' . $e->getMessage());
            }
        }

        // 2. Backup Servers
        if ($type === 'both' || $type === 'all_servers') {
            $servers = Server::all();
            $ignoreLimits = $this->settings->get('settings::pterodactyl:backups:ignore_limits', '0') === '1';
            foreach ($servers as $server) {
                try {
                    $this->info('Triggering backup on Wings for server: ' . $server->name);
                    if ($ignoreLimits) {
                        $server->backup_limit = 99999;
                    }
                    $backup = $this->initiateBackupService->handle($server, 'Auto GDrive Backup ' . date('Y-m-d H:i:s'), true);

                    DB::table('universal_backups')->insert([
                        'node_id' => $server->node_id,
                        'server_id' => $server->id,
                        'backup_type' => 'server',
                        'status' => 'backing_up',
                        'file_id' => $backup->uuid,
                        'filename' => $server->name . '_backup_' . date('Y-m-d_H-i-s') . '.tar.gz',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } catch (\Exception $e) {
                    Log::error('Automated server backup initiation failed for ' . $server->name . ': ' . $e->getMessage());
                }
            }
        }

        // Save last run time
        $this->settings->set('settings::pterodactyl:backups:schedule_last_run', date('Y-m-d H:i:s'));
        $this->info('Automated backup completed.');
    }
}
