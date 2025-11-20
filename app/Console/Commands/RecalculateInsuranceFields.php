<?php

namespace App\Console\Commands;

use App\Models\InsuranceProgram;
use App\Models\InsuranceSubscription;
use Illuminate\Console\Command;

class RecalculateInsuranceFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance:recalculate {type=all : Type to recalculate (programs, subscriptions, or all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate computed fields for insurance programs and subscriptions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');
        
        if ($type === 'programs' || $type === 'all') {
            $this->recalculatePrograms();
        }
        
        if ($type === 'subscriptions' || $type === 'all') {
            $this->recalculateSubscriptions();
        }
        
        if (!in_array($type, ['programs', 'subscriptions', 'all'])) {
            $this->error("Invalid type. Use: programs, subscriptions, or all");
            return 1;
        }
        
        return 0;
    }
    
    protected function recalculatePrograms()
    {
        $this->info("Recalculating insurance program statistics...");
        
        $bar = $this->output->createProgressBar(InsuranceProgram::count());
        $bar->start();
        
        $updated = 0;
        $errors = 0;
        
        InsuranceProgram::chunk(100, function ($programs) use ($bar, &$updated, &$errors) {
            foreach ($programs as $program) {
                try {
                    $program->updateStatistics();
                    $updated++;
                } catch (\Exception $e) {
                    $this->error("\nError updating program {$program->id}: " . $e->getMessage());
                    $errors++;
                }
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✓ Program statistics updated!");
        $this->info("Updated: {$updated} programs");
        
        if ($errors > 0) {
            $this->warn("Errors: {$errors} programs");
        }
    }
    
    protected function recalculateSubscriptions()
    {
        $this->info("Recalculating insurance subscription balances...");
        
        $bar = $this->output->createProgressBar(InsuranceSubscription::count());
        $bar->start();
        
        $updated = 0;
        $errors = 0;
        
        InsuranceSubscription::chunk(100, function ($subscriptions) use ($bar, &$updated, &$errors) {
            foreach ($subscriptions as $subscription) {
                try {
                    $subscription->updateBalances();
                    $updated++;
                } catch (\Exception $e) {
                    $this->error("\nError updating subscription {$subscription->id}: " . $e->getMessage());
                    $errors++;
                }
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✓ Subscription balances updated!");
        $this->info("Updated: {$updated} subscriptions");
        
        if ($errors > 0) {
            $this->warn("Errors: {$errors} subscriptions");
        }
    }
}
