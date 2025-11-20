<?php

/**
 * Project System Test Script
 * 
 * This script creates dummy data and tests the entire project ecosystem:
 * - Projects
 * - Project Shares
 * - Project Transactions
 * - Disbursements
 * - Account Transactions
 * 
 * Run with: php artisan tinker < test-project-system.php
 */

use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\ProjectTransaction;
use App\Models\Disbursement;
use App\Models\User;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "PROJECT SYSTEM COMPREHENSIVE TEST\n";
echo "========================================\n\n";

DB::beginTransaction();

try {
    // Step 1: Create a test project
    echo "Step 1: Creating test project...\n";
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
    echo "✓ Project created: ID={$project->id}, Title={$project->title}\n";
    echo "  Initial: shares_sold={$project->shares_sold}, total_investment={$project->total_investment}\n\n";

    // Step 2: Get test investors
    echo "Step 2: Getting test investors...\n";
    $investors = User::whereNotIn('user_type', ['Admin'])->take(3)->get();
    if ($investors->count() < 3) {
        throw new \Exception("Need at least 3 non-admin users for testing");
    }
    echo "✓ Found {$investors->count()} test investors\n\n";

    // Step 3: Create shares for investors
    echo "Step 3: Creating shares for investors...\n";
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
        
        // Create corresponding transaction
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
        
        echo "✓ {$data['investor']->name}: {$data['shares']} shares = UGX " . number_format($data['amount'], 0) . "\n";
    }
    
    $project->refresh();
    echo "\nAfter share purchases:\n";
    echo "  shares_sold={$project->shares_sold}, total_investment=" . number_format($project->total_investment, 0) . "\n\n";

    // Step 4: Add project profits
    echo "Step 4: Adding project profits...\n";
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
        echo "✓ " . $profit['description'] . ": UGX " . number_format($profit['amount'], 0) . "\n";
    }
    
    $project->refresh();
    echo "\nAfter profits:\n";
    echo "  total_profits=" . number_format($project->total_profits, 0) . "\n\n";

    // Step 5: Add project expenses
    echo "Step 5: Adding project expenses...\n";
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
        echo "✓ " . $expense['description'] . ": UGX " . number_format($expense['amount'], 0) . "\n";
    }
    
    $project->refresh();
    echo "\nAfter expenses:\n";
    echo "  total_expenses=" . number_format($project->total_expenses, 0) . "\n";
    echo "  net_profit=" . number_format($project->net_profit, 0) . "\n\n";

    // Step 6: Calculate available for disbursement
    echo "Step 6: Calculating available funds for disbursement...\n";
    $availableFunds = $project->total_profits - $project->total_expenses - $project->total_returns;
    echo "✓ Available for disbursement: UGX " . number_format($availableFunds, 0) . "\n\n";

    // Step 7: Create first disbursement
    echo "Step 7: Creating first disbursement (UGX 800,000)...\n";
    $disbursement1 = Disbursement::create([
        'project_id' => $project->id,
        'amount' => 800000,
        'disbursement_date' => now(),
        'description' => 'First profit distribution to investors',
        'created_by_id' => 1,
    ]);
    
    $accountTransactions1 = AccountTransaction::where('related_disbursement_id', $disbursement1->id)->get();
    echo "✓ Disbursement created: ID={$disbursement1->id}\n";
    echo "✓ Created {$accountTransactions1->count()} account transactions:\n";
    foreach ($accountTransactions1 as $at) {
        echo "  - {$at->user->name}: UGX " . number_format($at->amount, 2) . "\n";
    }
    
    $project->refresh();
    echo "\nAfter first disbursement:\n";
    echo "  total_returns=" . number_format($project->total_returns, 0) . "\n";
    echo "  available_for_disbursement=" . number_format($project->available_for_disbursement, 0) . "\n\n";

    // Step 8: Try to create disbursement with insufficient funds (should fail)
    echo "Step 8: Testing validation - trying to disburse UGX 2,000,000 (should fail)...\n";
    try {
        $disbursement2 = Disbursement::create([
            'project_id' => $project->id,
            'amount' => 2000000, // More than available
            'disbursement_date' => now(),
            'description' => 'Should fail due to insufficient funds',
            'created_by_id' => 1,
        ]);
        echo "✗ ERROR: Disbursement should have been blocked!\n";
    } catch (\Exception $e) {
        echo "✓ Correctly blocked: " . $e->getMessage() . "\n\n";
    }

    // Step 9: Create valid second disbursement
    echo "Step 9: Creating second valid disbursement...\n";
    $remainingFunds = $project->available_for_disbursement;
    $secondDisbursementAmount = min($remainingFunds, 1000000);
    
    $disbursement3 = Disbursement::create([
        'project_id' => $project->id,
        'amount' => $secondDisbursementAmount,
        'disbursement_date' => now(),
        'description' => 'Second profit distribution',
        'created_by_id' => 1,
    ]);
    
    $accountTransactions3 = AccountTransaction::where('related_disbursement_id', $disbursement3->id)->get();
    echo "✓ Disbursement created: UGX " . number_format($secondDisbursementAmount, 0) . "\n";
    echo "✓ Created {$accountTransactions3->count()} account transactions\n";
    
    $project->refresh();
    echo "\nAfter second disbursement:\n";
    echo "  total_returns=" . number_format($project->total_returns, 0) . "\n";
    echo "  available_for_disbursement=" . number_format($project->available_for_disbursement, 0) . "\n\n";

    // Step 10: Final verification
    echo "Step 10: Final verification and summary...\n";
    echo "========================================\n";
    echo "PROJECT FINANCIAL SUMMARY\n";
    echo "========================================\n";
    echo "Total Investment:    UGX " . number_format($project->total_investment, 0) . "\n";
    echo "Total Profits:       UGX " . number_format($project->total_profits, 0) . "\n";
    echo "Total Expenses:      UGX " . number_format($project->total_expenses, 0) . "\n";
    echo "Total Returns:       UGX " . number_format($project->total_returns, 0) . "\n";
    echo "Net Profit:          UGX " . number_format($project->net_profit, 0) . "\n";
    echo "Available Funds:     UGX " . number_format($project->available_for_disbursement, 0) . "\n";
    echo "ROI:                 " . number_format($project->roi_percentage, 2) . "%\n";
    echo "========================================\n";
    
    // Verification calculations
    echo "\nVERIFICATION:\n";
    $expectedNetProfit = $project->total_profits - $project->total_expenses;
    $expectedAvailable = $expectedNetProfit - $project->total_returns;
    $calculationCorrect = (abs($expectedNetProfit - $project->net_profit) < 0.01) && 
                          (abs($expectedAvailable - $project->available_for_disbursement) < 0.01);
    
    if ($calculationCorrect) {
        echo "✓ All calculations are CORRECT!\n";
    } else {
        echo "✗ ERROR: Calculation mismatch detected!\n";
        echo "  Expected Net Profit: " . number_format($expectedNetProfit, 2) . "\n";
        echo "  Actual Net Profit: " . number_format($project->net_profit, 2) . "\n";
        echo "  Expected Available: " . number_format($expectedAvailable, 2) . "\n";
        echo "  Actual Available: " . number_format($project->available_for_disbursement, 2) . "\n";
    }
    
    // Verify share distribution
    echo "\nINVESTOR SHARE DISTRIBUTION:\n";
    $totalShares = $project->shares()->sum('number_of_shares');
    $investorShares = $project->shares()
        ->selectRaw('investor_id, SUM(number_of_shares) as total_shares')
        ->groupBy('investor_id')
        ->get();
    
    foreach ($investorShares as $inv) {
        $percentage = ($inv->total_shares / $totalShares) * 100;
        $user = User::find($inv->investor_id);
        echo "  {$user->name}: {$inv->total_shares} shares (" . number_format($percentage, 2) . "%)\n";
    }
    
    echo "\n========================================\n";
    echo "TEST COMPLETED SUCCESSFULLY! ✓\n";
    echo "========================================\n\n";
    
    // Rollback to keep database clean
    echo "Rolling back test data...\n";
    DB::rollBack();
    echo "✓ Database rolled back - no test data persisted\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n✗ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}
