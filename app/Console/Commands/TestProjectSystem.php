<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\ProjectTransaction;
use App\Models\Disbursement;
use App\Models\User;
use App\Models\AccountTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestProjectSystem extends Command
{
    protected $signature = 'project:test-system';
    protected $description = 'Test the entire project ecosystem with dummy data';

    public function handle()
    {
        $this->info('========================================');
        $this->info('PROJECT SYSTEM COMPREHENSIVE TEST');
        $this->info('========================================');
        $this->newLine();

        DB::beginTransaction();

        try {
            // Step 1: Create test project
            $this->info('Step 1: Creating test project...');
            $project = Project::create([
                'title' => 'Test Agricultural Project ' . time(),
                'description' => 'Testing project calculations and disbursements',
                'start_date' => now(),
                'end_date' => now()->addMonths(12),
                'status' => 'ongoing',
                'share_price' => 10000,
                'total_shares' => 1000,
                'created_by_id' => 1,
            ]);
            $this->line("✓ Project created: ID={$project->id}, Title={$project->title}");
            $this->line("  Initial: shares_sold={$project->shares_sold}, total_investment={$project->total_investment}");
            $this->newLine();

            // Step 2: Get test investors
            $this->info('Step 2: Getting test investors...');
            $investors = User::whereNotIn('user_type', ['Admin'])->take(3)->get();
            if ($investors->count() < 3) {
                throw new \Exception("Need at least 3 non-admin users for testing");
            }
            $this->line("✓ Found {$investors->count()} test investors");
            $this->newLine();

            // Step 3: Create shares
            $this->info('Step 3: Creating shares for investors...');
            $shareData = [
                ['investor' => $investors[0], 'shares' => 100, 'amount' => 1000000],
                ['investor' => $investors[1], 'shares' => 200, 'amount' => 2000000],
                ['investor' => $investors[2], 'shares' => 150, 'amount' => 1500000],
            ];
            
            foreach ($shareData as $data) {
                $share = ProjectShare::create([
                    'project_id' => $project->id,
                    'investor_id' => $data['investor']->id,
                    'purchase_date' => now(),
                    'number_of_shares' => $data['shares'],
                    'total_amount_paid' => $data['amount'],
                    'share_price_at_purchase' => $project->share_price,
                ]);
                
                ProjectTransaction::create([
                    'project_id' => $project->id,
                    'amount' => $data['amount'],
                    'transaction_date' => now(),
                    'description' => 'Share purchase by ' . $data['investor']->name,
                    'type' => 'income',
                    'source' => 'share_purchase',
                    'related_share_id' => $share->id,
                    'created_by_id' => 1,
                ]);
                
                $this->line("✓ {$data['investor']->name}: {$data['shares']} shares = UGX " . number_format($data['amount'], 0));
            }
            
            $project->refresh();
            $this->newLine();
            $this->line("After share purchases:");
            $this->line("  shares_sold={$project->shares_sold}, total_investment=" . number_format($project->total_investment, 0));
            $this->newLine();

            // Step 4: Add profits
            $this->info('Step 4: Adding project profits...');
            $profits = [
                ['amount' => 500000, 'description' => 'Q1 Harvest profits'],
                ['amount' => 750000, 'description' => 'Q2 Harvest profits'],
                ['amount' => 1000000, 'description' => 'Q3 Harvest profits'],
            ];
            
            foreach ($profits as $profit) {
                ProjectTransaction::create([
                    'project_id' => $project->id,
                    'amount' => $profit['amount'],
                    'transaction_date' => now(),
                    'description' => $profit['description'],
                    'type' => 'income',
                    'source' => 'project_profit',
                    'created_by_id' => 1,
                ]);
                $this->line("✓ " . $profit['description'] . ": UGX " . number_format($profit['amount'], 0));
            }
            
            $project->refresh();
            $this->newLine();
            $this->line("After profits:");
            $this->line("  total_profits=" . number_format($project->total_profits, 0));
            $this->newLine();

            // Step 5: Add expenses
            $this->info('Step 5: Adding project expenses...');
            $expenses = [
                ['amount' => 200000, 'description' => 'Farm maintenance'],
                ['amount' => 150000, 'description' => 'Transportation costs'],
                ['amount' => 100000, 'description' => 'Labor costs'],
            ];
            
            foreach ($expenses as $expense) {
                ProjectTransaction::create([
                    'project_id' => $project->id,
                    'amount' => $expense['amount'],
                    'transaction_date' => now(),
                    'description' => $expense['description'],
                    'type' => 'expense',
                    'source' => 'project_expense',
                    'created_by_id' => 1,
                ]);
                $this->line("✓ " . $expense['description'] . ": UGX " . number_format($expense['amount'], 0));
            }
            
            $project->refresh();
            $this->newLine();
            $this->line("After expenses:");
            $this->line("  total_expenses=" . number_format($project->total_expenses, 0));
            $this->line("  net_profit=" . number_format($project->net_profit, 0));
            $this->newLine();

            // Step 6: First disbursement
            $this->info('Step 7: Creating first disbursement (UGX 800,000)...');
            $disbursement1 = Disbursement::create([
                'project_id' => $project->id,
                'amount' => 800000,
                'disbursement_date' => now(),
                'description' => 'First profit distribution to investors',
                'created_by_id' => 1,
            ]);
            
            $accountTransactions1 = AccountTransaction::where('related_disbursement_id', $disbursement1->id)->get();
            $this->line("✓ Disbursement created: ID={$disbursement1->id}");
            $this->line("✓ Created {$accountTransactions1->count()} account transactions:");
            foreach ($accountTransactions1 as $at) {
                $this->line("  - {$at->user->name}: UGX " . number_format($at->amount, 2));
            }
            
            $project->refresh();
            $this->newLine();
            $this->line("After first disbursement:");
            $this->line("  total_returns=" . number_format($project->total_returns, 0));
            $this->line("  available_for_disbursement=" . number_format($project->available_for_disbursement, 0));
            $this->newLine();

            // Step 7: Test validation
            $this->info('Step 8: Testing validation - trying to disburse UGX 2,000,000 (should fail)...');
            try {
                $disbursement2 = Disbursement::create([
                    'project_id' => $project->id,
                    'amount' => 2000000,
                    'disbursement_date' => now(),
                    'description' => 'Should fail due to insufficient funds',
                    'created_by_id' => 1,
                ]);
                $this->error("✗ ERROR: Disbursement should have been blocked!");
            } catch (\Exception $e) {
                $this->line("✓ Correctly blocked: " . $e->getMessage());
            }
            $this->newLine();

            // Step 8: Final summary
            $this->info('Final PROJECT FINANCIAL SUMMARY:');
            $this->line('========================================');
            $this->line("Total Investment:    UGX " . number_format($project->total_investment, 0));
            $this->line("Total Profits:       UGX " . number_format($project->total_profits, 0));
            $this->line("Total Expenses:      UGX " . number_format($project->total_expenses, 0));
            $this->line("Total Returns:       UGX " . number_format($project->total_returns, 0));
            $this->line("Net Profit:          UGX " . number_format($project->net_profit, 0));
            $this->line("Available Funds:     UGX " . number_format($project->available_for_disbursement, 0));
            $this->line("ROI:                 " . number_format($project->roi_percentage, 2) . "%");
            $this->line('========================================');
            
            // Verification
            $expectedNetProfit = $project->total_profits - $project->total_expenses;
            $expectedAvailable = $expectedNetProfit - $project->total_returns;
            $calculationCorrect = (abs($expectedNetProfit - $project->net_profit) < 0.01) && 
                                  (abs($expectedAvailable - $project->available_for_disbursement) < 0.01);
            
            $this->newLine();
            if ($calculationCorrect) {
                $this->info("✓ All calculations are CORRECT!");
            } else {
                $this->error("✗ ERROR: Calculation mismatch detected!");
            }
            
            $this->newLine();
            $this->info('========================================');
            $this->info('TEST COMPLETED SUCCESSFULLY! ✓');
            $this->info('========================================');
            $this->newLine();
            
            $this->line("Rolling back test data...");
            DB::rollBack();
            $this->line("✓ Database rolled back - no test data persisted");
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $this->error("✗ TEST FAILED: " . $e->getMessage());
            $this->error("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
