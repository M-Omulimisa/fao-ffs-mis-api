<?php
/**
 * Test script for Social Fund Transaction functionality
 * 
 * Tests:
 * 1. Creating contributions
 * 2. Creating withdrawals (with balance check)
 * 3. Getting balance
 * 4. Listing transactions
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SocialFundTransaction;
use App\Models\FfsGroup;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

echo "🧪 Testing Social Fund Transaction Functionality\n";
echo "================================================\n\n";

try {
    // Get a VSLA group for testing
    $group = Project::where('is_vsla_cycle', 1)
        ->whereNotNull('group_id')
        ->first();

    if (!$group) {
        echo "❌ No VSLA cycle found. Please create a VSLA cycle first.\n";
        exit(1);
    }

    $groupId = $group->group_id;
    $cycle = $group;

    echo "📊 Test Group ID: {$groupId}\n";
    echo "📊 Test Cycle: {$cycle->project_name} (ID: {$cycle->id})\n\n";

    // Test 1: Create a contribution
    echo "Test 1: Creating a contribution...\n";
    $contribution = SocialFundTransaction::create([
        'group_id' => $groupId,
        'cycle_id' => $cycle->id,
        'member_id' => null,
        'transaction_type' => 'contribution',
        'amount' => 50000,
        'transaction_date' => now()->format('Y-m-d'),
        'description' => 'Test contribution from script',
        'reason' => 'Testing social fund',
        'created_by_id' => 1,
    ]);

    echo "   ✅ Contribution created: UGX " . number_format($contribution->amount, 2) . "\n\n";

    // Test 2: Get balance
    echo "Test 2: Getting balance...\n";
    $balance = SocialFundTransaction::getGroupBalance($groupId, $cycle->id);
    echo "   💰 Current Balance: UGX " . number_format($balance, 2) . "\n\n";

    // Test 3: Create a withdrawal (if sufficient balance)
    echo "Test 3: Creating a withdrawal...\n";
    if ($balance >= 10000) {
        $withdrawal = SocialFundTransaction::create([
            'group_id' => $groupId,
            'cycle_id' => $cycle->id,
            'member_id' => null,
            'transaction_type' => 'withdrawal',
            'amount' => -10000,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Test withdrawal from script',
            'reason' => 'Emergency assistance',
            'created_by_id' => 1,
        ]);

        echo "   ✅ Withdrawal created: UGX " . number_format(abs($withdrawal->amount), 2) . "\n";
        
        $newBalance = SocialFundTransaction::getGroupBalance($groupId, $cycle->id);
        echo "   💰 New Balance: UGX " . number_format($newBalance, 2) . "\n\n";
    } else {
        echo "   ⚠️  Insufficient balance for withdrawal (balance: UGX " . number_format($balance, 2) . ")\n\n";
    }

    // Test 4: List transactions
    echo "Test 4: Listing all transactions...\n";
    $transactions = SocialFundTransaction::where('group_id', $groupId)
        ->orderBy('transaction_date', 'desc')
        ->limit(5)
        ->get();

    echo "   📋 Recent Transactions:\n";
    foreach ($transactions as $txn) {
        $type = $txn->transaction_type === 'contribution' ? '➕' : '➖';
        $amount = number_format(abs($txn->amount), 2);
        echo "      {$type} UGX {$amount} - {$txn->transaction_date} - {$txn->description}\n";
    }
    echo "\n";

    // Test 5: Summary statistics
    echo "Test 5: Summary statistics...\n";
    $query = SocialFundTransaction::where('group_id', $groupId)
        ->where('cycle_id', $cycle->id);

    $totalContributions = (clone $query)->where('transaction_type', 'contribution')->sum('amount');
    $totalWithdrawals = abs((clone $query)->where('transaction_type', 'withdrawal')->sum('amount'));
    $transactionCount = (clone $query)->count();
    $finalBalance = SocialFundTransaction::getGroupBalance($groupId, $cycle->id);

    echo "   📊 Total Contributions: UGX " . number_format($totalContributions, 2) . "\n";
    echo "   📊 Total Withdrawals: UGX " . number_format($totalWithdrawals, 2) . "\n";
    echo "   📊 Transaction Count: {$transactionCount}\n";
    echo "   💰 Final Balance: UGX " . number_format($finalBalance, 2) . "\n\n";

    // Verify balance calculation
    $expectedBalance = $totalContributions - $totalWithdrawals;
    if (abs($expectedBalance - $finalBalance) < 0.01) {
        echo "✅ Balance calculation verified!\n";
    } else {
        echo "❌ Balance mismatch! Expected: UGX " . number_format($expectedBalance, 2) . ", Got: UGX " . number_format($finalBalance, 2) . "\n";
    }

    echo "\n🎉 All tests completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
