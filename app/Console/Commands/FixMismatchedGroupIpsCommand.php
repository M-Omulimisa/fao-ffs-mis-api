<?php

namespace App\Console\Commands;

use App\Services\GroupIpMismatchFixerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class FixMismatchedGroupIpsCommand extends Command
{
    protected $signature = 'system:fix-mismatched-group-ips
                            {--ip_id= : Restrict to facilitator IP ID}
                            {--batch=50 : Batch size per loop}
                            {--progress_key= : Cache key for live progress reporting}';

    protected $description = 'Fix groups whose ip_id does not match facilitator ip_id and align related records';

    public function handle(GroupIpMismatchFixerService $fixer): int
    {
        $ipId = $this->option('ip_id') !== null ? (int) $this->option('ip_id') : null;
        $batchSize = (int) $this->option('batch');
        $progressKey = $this->option('progress_key');

        $report = $fixer->run($ipId, $batchSize, function (array $state) use ($progressKey) {
            if ($progressKey) {
                Cache::put($progressKey, array_merge($state, ['updated_at' => now()->toDateTimeString()]), now()->addHours(2));
            }
        });

        $this->info('Group IP mismatch fix completed');
        $this->line('Total mismatches: ' . $report['total']);
        $this->line('Fixed: ' . $report['fixed']);
        $this->line('Failed: ' . $report['failed']);

        if ($progressKey) {
            Cache::put($progressKey, [
                'status' => 'completed',
                'total' => $report['total'],
                'processed' => $report['total'],
                'fixed' => $report['fixed'],
                'failed' => $report['failed'],
                'percent' => 100,
                'message' => 'Group IP mismatch fix completed',
                'failures' => array_slice($report['failures'], 0, 50),
                'updated_at' => now()->toDateTimeString(),
            ], now()->addHours(2));
        }

        return self::SUCCESS;
    }
}
