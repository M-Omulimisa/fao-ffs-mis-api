<?php

/**
 * Test Script: Verify Disbursement Project Transaction Fix
 * 
 * This script tests that disbursements correctly create ProjectTransaction records
 * Run: php test-disbursement-fix.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\Project;
use App\Models\ProjectTransaction;
use App\Models\ProjectShare;
use App\Models\Disbursement;
use App\Models\AccountTransaction;
use App\Models\User;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n========================================\n";
echo "Testing Disbursement Project Transaction\n";
echo "========================================\n\n";

try {
    // Find any project
    $project = Project::first();
    
    if (!$project) {
        echo "❌ No project found. Please create a project first.\n";
        exit(1);
    }
    
    echo "✓ Using Project: {$project->title} (ID: {$project->id})\n";
    
    // Check project's current state
    $income = ProjectTransaction::where('project_id', $project->id)
        ->where('type', 'income')
        ->sum('amount');
    
    $expenses = ProjectTransaction::where('project_id', $project->id)
        ->where('type', 'expense')
        ->sum('amount');
    
    $availableFunds = $income - $expenses;
    
    echo "  - Total Income: UGX " . number_format($income, 2) . "\n";
    echo "  - Total Expenses: UGX " . number_format($expenses, 2) . "\n";
    echo "  - Available Funds: UGX " . number_format($availableFunds, 2) . "\n\n";
    
    // Check if project has investors
    $totalShares = $project->shares()->sum('number_of_shares');
    $investorCount = $project->shares()
        ->selectRaw('investor_id')
        ->groupBy('investor_id')
        ->get()
        ->count();
    
    if ($totalShares <= 0) {
        echo "❌ Project has no shares/investors. Cannot test disbursement.\n";
        exit(1);
    }
    
    echo "✓ Project has {$investorCount} investor(s) with {$totalShares} total shares\n\n";
    
    // Get admin user
    $admin = User::where('user_type', 'admin')->first();
    if (!$admin) {
        $admin = User::first();
    }
    
    if (!$admin) {
        echo "❌ No user found to create disbursement.\n";
        exit(1);
    }
    
    // Test amount (10% of available funds or 10,000 minimum)
    $testAmount = max(10000, $availableFunds * 0.1);
    
    if ($testAmount > $availableFunds) {
        echo "❌ Insufficient funds for test disbursement.\n";
        exit(1);
    }
    
    echo "Creating test disbursement of UGX " . number_format($testAmount, 2) . "...\n";
    
    // Count transactions before
    $projectTxBefore = ProjectTransaction::where('project_id', $project->id)
        ->where('source', 'returns_distribution')
        ->count();
    
    $accountTxBefore = AccountTransaction::where('source', 'disbursement')
        ->whereHas('relatedDisbursement', function($q) use ($project) {
            $q->where('project_id', $project->id);
        })
        ->count();
    
    // Create disbursement
    $disbursement = Disbursement::create([
        'project_id' => $project->id,
        'amount' => $testAmount,
        'disbursement_date' => now(),
        'description' => 'Test disbursement - ' . now()->format('Y-m-d H:i:s'),
        'created_by_id' => $admin->id,
    ]);
    
    echo "✓ Disbursement created (ID: {$disbursement->id})\n\n";
    
    // Count transactions after
    $projectTxAfter = ProjectTransaction::where('project_id', $project->id)
        ->where('source', 'returns_distribution')
        ->count();
    
    $accountTxAfter = AccountTransaction::where('source', 'disbursement')
        ->whereHas('relatedDisbursement', function($q) use ($project) {
            $q->where('project_id', $project->id);
        })
        ->count();
    
    echo "Verification Results:\n";
    echo "--------------------\n";
    
    // Check 1: ProjectTransaction created
    $projectTxCreated = ($projectTxAfter > $projectTxBefore);
    echo ($projectTxCreated ? "✓" : "❌") . " ProjectTransaction created: ";
    echo "Before: {$projectTxBefore}, After: {$projectTxAfter}\n";
    
    // Check 2: Correct ProjectTransaction
    $projectTx = ProjectTransaction::where('project_id', $project->id)
        ->where('source', 'returns_distribution')
        ->where('amount', $testAmount)
        ->where('type', 'expense')
        ->orderBy('id', 'desc')
        ->first();
    
    $correctProjectTx = ($projectTx !== null);
    echo ($correctProjectTx ? "✓" : "❌") . " ProjectTransaction has correct attributes\n";
    if ($correctProjectTx) {
        echo "  - Amount: UGX " . number_format($projectTx->amount, 2) . "\n";
        echo "  - Type: {$projectTx->type}\n";
        echo "  - Source: {$projectTx->source}\n";
    }
    
    // Check 3: AccountTransactions created
    $accountTxCreated = ($accountTxAfter > $accountTxBefore);
    echo ($accountTxCreated ? "✓" : "❌") . " AccountTransactions created: ";
    echo "Before: {$accountTxBefore}, After: {$accountTxAfter}\n";
    
    // Check 4: Correct number of AccountTransactions
    $expectedAccountTx = $investorCount;
    $actualAccountTx = AccountTransaction::where('related_disbursement_id', $disbursement->id)->count();
    $correctAccountCount = ($actualAccountTx == $expectedAccountTx);
    echo ($correctAccountCount ? "✓" : "❌") . " Correct number of investor distributions: ";
    echo "Expected: {$expectedAccountTx}, Actual: {$actualAccountTx}\n";
    
    // Check 5: Total distributed matches disbursement amount
    $totalDistributed = AccountTransaction::where('related_disbursement_id', $disbursement->id)
        ->sum('amount');
    $correctTotal = (abs($totalDistributed - $testAmount) < 0.01);
    echo ($correctTotal ? "✓" : "❌") . " Total distributed matches disbursement: ";
    echo "UGX " . number_format($totalDistributed, 2) . " of UGX " . number_format($testAmount, 2) . "\n";
    
    // Check 6: Project balance updated
    $project->refresh();
    $newExpenses = ProjectTransaction::where('project_id', $project->id)
        ->where('type', 'expense')
        ->sum('amount');
    $expensesIncreased = ($newExpenses > $expenses);
    echo ($expensesIncreased ? "✓" : "❌") . " Project expenses increased: ";
    echo "Old: UGX " . number_format($expenses, 2) . ", New: UGX " . number_format($newExpenses, 2) . "\n";
    
    echo "\n";
    
    // Overall result
    $allPassed = $projectTxCreated && $correctProjectTx && $accountTxCreated && 
                 $correctAccountCount && $correctTotal && $expensesIncreased;
    
    if ($allPassed) {
        echo "========================================\n";
        echo "✓ ALL TESTS PASSED!\n";
        echo "========================================\n";
        echo "\nDisbursement system is working correctly.\n";
        echo "Project transactions are being created automatically.\n\n";
    } else {
        echo "========================================\n";
        echo "❌ SOME TESTS FAILED!\n";
        echo "========================================\n";
        echo "\nPlease review the results above.\n\n";
    }
    
    // Cleanup option
    echo "Test disbursement ID: {$disbursement->id}\n";
    echo "To clean up, you can delete this disbursement from the admin panel.\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    exit(1);
}
