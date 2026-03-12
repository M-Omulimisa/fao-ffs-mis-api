<?php

namespace App\Console\Commands;

use App\Models\VslaOpeningBalance;
use App\Services\OpeningBalanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPendingOpeningBalances extends Command
{
    protected $signature = 'opening-balance:process-pending
                            {--cycle_id= : Process a specific cycle only}
                            {--dry-run   : Report what would be processed without writing anything}';

    protected $description = 'Process any submitted-but-unprocessed VSLA opening balance records';

    public function handle(OpeningBalanceService $service): int
    {
        $dryRun  = $this->option('dry-run');
        $cycleId = $this->option('cycle_id');

        $query = VslaOpeningBalance::where('status', 'submitted')
            ->where('is_processed', false)
            ->whereHas('memberEntries');

        if ($cycleId) {
            $query->where('cycle_id', (int) $cycleId);
        }

        $pending = $query->get();

        if ($pending->isEmpty()) {
            $this->info('No pending opening balance records found.');
            return 0;
        }

        $this->info("Found {$pending->count()} pending record(s)." . ($dryRun ? ' [DRY RUN]' : ''));

        $processed = 0;
        $failed    = 0;

        foreach ($pending as $ob) {
            $label = "id={$ob->id} group={$ob->group_id} cycle={$ob->cycle_id}";

            if ($dryRun) {
                $memberCount = $ob->memberEntries()->count();
                $this->line("  Would process: {$label} ({$memberCount} members)");
                continue;
            }

            try {
                DB::transaction(function () use ($ob, $service): void {
                    $summary = $service->process($ob, $ob->submitted_by_id ?? $ob->group_id);

                    $ob->updateQuietly([
                        'status'           => 'processed',
                        'is_processed'     => true,
                        'processed_at'     => now(),
                        'processing_notes' => json_encode($summary['log']),
                    ]);

                    Log::info(
                        "opening-balance:process-pending processed {$ob->id} "
                        . "shares={$summary['shares_created']} "
                        . "loans={$summary['loans_created']} "
                        . "sf={$summary['social_fund_records']}"
                    );
                });

                $this->info("  OK: {$label}");
                $processed++;
            } catch (\Throwable $e) {
                $this->error("  FAILED: {$label} — {$e->getMessage()}");
                Log::error("opening-balance:process-pending FAILED {$label}: " . $e->getMessage());
                $failed++;
            }
        }

        if (!$dryRun) {
            $this->newLine();
            $this->info("Done. Processed: {$processed}, Failed: {$failed}");
        }

        return $failed > 0 ? 1 : 0;
    }
}
