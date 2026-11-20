<?php

namespace App\Console\Commands;

use App\Services\System\TenantActivitySyncService;
use Illuminate\Console\Command;

class SyncTenantActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-logs:sync {--outlet= : Specific outlet ID to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize historical backend transactions into tenant activity logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $outletId = $this->option('outlet') ? (int) $this->option('outlet') : null;

        $this->info('Starting activity logs synchronization from backend transactions...');
        $count = TenantActivitySyncService::syncOutletLogs($outletId);
        $this->info("Completed. {$count} activity log entries synchronized successfully.");

        return Command::SUCCESS;
    }
}
