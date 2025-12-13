<?php
/**
 * Test VSLA Double-Entry Accounting Implementation
 * 
 * This script:
 * 1. Clears existing test data for meeting 5
 * 2. Reprocesses share purchases using NEW double-entry logic
 * 3. Verifies AccountTransaction records are created correctly
 * 4. Validates double-entry balance
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VslaMeeting;
use App\Models\AccountTransaction;
use App\Models\ProjectShare;
use App\Services\MeetingProcessingService;
use Illuminate\Support\Facades\DB;

echo "=== TESTING VSLA DOUBLE-ENTRY ACCOUNTING ===\n\n";

// Get meeting 5
$meeting = VslaMeeting::find(5);
if (!$meeting) {
    echo "❌ Meeting 5 not found!\n";
    exit(1);
}

echo "Meeting ID: {$meeting->id}\n";
echo "Meeting Number: {$meeting->meeting_number}\n";
echo "Meeting Date: {$meeting->meeting_date}\n";
echo "Cycle ID: {$meeting->cycle_id}\n";
echo "Created By: {$meeting->created_by_id}\n\n";

// Decode share purchases data
$sharesData = is_array($meeting->share_purchases_data) 
    ? $meeting->share_purchases_data 
    : (json_decode($meeting->share_purchases_data, true) ?? []);
echo "Share Purchases: " . count($sharesData) . " records\n";
foreach ($sharesData as $i => $purchase) {
    $num = $i + 1;
    $investorId = $purchase['investor_id'] ?? 'N/A';
    $investorName = $purchase['investor_name'] ?? 'N/A';
    $numShares = $purchase['number_of_shares'] ?? 0;
    $amount = $purchase['total_amount_paid'] ?? 0;
    echo "  {$num}. {$investorName} (ID: {$investorId}) - {$numShares} shares @ UGX " . number_format($amount, 2) . "\n";
}
echo "\n";

// Step 1: Clear existing records for this meeting date
echo "Step 1: Clearing existing test data for date {$meeting->meeting_date}...\n";

$deletedShares = ProjectShare::where('purchase_date', $meeting->meeting_date)->delete();
echo "  Deleted {$deletedShares} ProjectShare records\n";

$deletedGroupTxns = AccountTransaction::whereNull('user_id')
    ->where('transaction_date', $meeting->meeting_date)
    ->where('source', 'share_purchase')
    ->delete();
echo "  Deleted {$deletedGroupTxns} group AccountTransaction records\n";

$deletedMemberTxns = AccountTransaction::whereNotNull('user_id')
    ->where('transaction_date', $meeting->meeting_date)
    ->where('source', 'share_purchase')
    ->delete();
echo "  Deleted {$deletedMemberTxns} member AccountTransaction records\n\n";

// Step 2: Reprocess shares using the service
echo "Step 2: Reprocessing shares using MeetingProcessingService...\n";

$service = new MeetingProcessingService();
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('processSharePurchases');
$method->setAccessible(true);

$result = $method->invoke($service, $meeting);

if ($result['success']) {
    echo "✅ Share processing completed successfully\n";
    if (!empty($result['warnings'])) {
        echo "⚠️  Warnings:\n";
        foreach ($result['warnings'] as $warning) {
            echo "  - {$warning['message']}\n";
        }
    }
} else {
    echo "❌ Share processing failed\n";
    foreach ($result['errors'] as $error) {
        echo "  - {$error['message']}\n";
    }
    exit(1);
}
echo "\n";

// Step 3: Verify created records
echo "Step 3: Verifying created records...\n\n";

// Check ProjectShares
$shares = ProjectShare::where('purchase_date', $meeting->meeting_date)->get();
echo "ProjectShares created: " . $shares->count() . "\n";
foreach ($shares as $share) {
    echo "  - Investor {$share->investor_id}: {$share->number_of_shares} shares @ UGX " . number_format($share->total_amount_paid, 2) . "\n";
}
echo "\n";

// Check Group AccountTransactions
$groupTxns = AccountTransaction::whereNull('user_id')
    ->where('transaction_date', $meeting->meeting_date)
    ->where('source', 'share_purchase')
    ->get();
    
echo "Group AccountTransactions (user_id=NULL): " . $groupTxns->count() . "\n";
$groupTotal = 0;
foreach ($groupTxns as $txn) {
    $groupTotal += $txn->amount;
    echo "  - Amount: UGX " . number_format($txn->amount, 2) . " | {$txn->description}\n";
}
echo "  GROUP TOTAL: UGX " . number_format($groupTotal, 2) . "\n\n";

// Check Member AccountTransactions
$memberTxns = AccountTransaction::whereNotNull('user_id')
    ->where('transaction_date', $meeting->meeting_date)
    ->where('source', 'share_purchase')
    ->get();
    
echo "Member AccountTransactions: " . $memberTxns->count() . "\n";
$memberTotal = 0;
foreach ($memberTxns as $txn) {
    $memberTotal += $txn->amount;
    echo "  - User {$txn->user_id}: UGX " . number_format($txn->amount, 2) . " | {$txn->description}\n";
}
echo "  MEMBER TOTAL: UGX " . number_format($memberTotal, 2) . "\n\n";

// Step 4: Validate double-entry balance
echo "Step 4: Validating double-entry accounting...\n\n";

$expectedCount = count($sharesData);
$expectedAmount = array_sum(array_column($sharesData, 'total_amount_paid'));

echo "Expected:\n";
echo "  - {$expectedCount} share purchases\n";
echo "  - " . ($expectedCount * 2) . " AccountTransaction records (double-entry)\n";
echo "  - UGX " . number_format($expectedAmount, 2) . " total amount\n\n";

echo "Actual:\n";
echo "  - {$shares->count()} ProjectShare records\n";
echo "  - " . ($groupTxns->count() + $memberTxns->count()) . " AccountTransaction records\n";
echo "  - Group total: UGX " . number_format($groupTotal, 2) . "\n";
echo "  - Member total: UGX " . number_format($memberTotal, 2) . "\n\n";

// Validation checks
$allPassed = true;

if ($shares->count() !== $expectedCount) {
    echo "❌ FAIL: Expected {$expectedCount} shares, got {$shares->count()}\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Correct number of shares created\n";
}

if ($groupTxns->count() !== $expectedCount) {
    echo "❌ FAIL: Expected {$expectedCount} group transactions, got {$groupTxns->count()}\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Correct number of group transactions\n";
}

if ($memberTxns->count() !== $expectedCount) {
    echo "❌ FAIL: Expected {$expectedCount} member transactions, got {$memberTxns->count()}\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Correct number of member transactions\n";
}

if (abs($groupTotal - $expectedAmount) > 0.01) {
    echo "❌ FAIL: Group total mismatch. Expected UGX " . number_format($expectedAmount, 2) . ", got UGX " . number_format($groupTotal, 2) . "\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Group total matches expected amount\n";
}

if (abs($memberTotal - $expectedAmount) > 0.01) {
    echo "❌ FAIL: Member total mismatch. Expected UGX " . number_format($expectedAmount, 2) . ", got UGX " . number_format($memberTotal, 2) . "\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Member total matches expected amount\n";
}

if (abs($groupTotal - $memberTotal) > 0.01) {
    echo "❌ FAIL: Double-entry imbalance! Group total ≠ Member total\n";
    $allPassed = false;
} else {
    echo "✅ PASS: Double-entry balanced (Group = Members)\n";
}

echo "\n";

// Step 5: Calculate balances
echo "Step 5: Calculating balances...\n\n";

// Group balance for this cycle (all sources)
$groupBalance = AccountTransaction::whereNull('user_id')->sum('amount');
echo "Overall Group Balance: UGX " . number_format($groupBalance, 2) . "\n";

// Individual member balances
$memberIds = $memberTxns->pluck('user_id')->unique();
foreach ($memberIds as $memberId) {
    $balance = AccountTransaction::where('user_id', $memberId)->sum('amount');
    $user = \App\Models\User::find($memberId);
    $name = $user ? $user->name : "User {$memberId}";
    echo "  {$name}: UGX " . number_format($balance, 2) . "\n";
}

echo "\n";

if ($allPassed) {
    echo "✅✅✅ ALL TESTS PASSED! Double-Entry Accounting is working correctly! ✅✅✅\n";
} else {
    echo "❌❌❌ SOME TESTS FAILED! Please review the implementation. ❌❌❌\n";
    exit(1);
}
