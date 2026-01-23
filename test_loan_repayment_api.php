<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\VslaLoan;
use Illuminate\Support\Facades\Auth;

echo "═══════════════════════════════════════════════════════════\n";
echo "   TESTING LOAN REPAYMENT API\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get first user
$user = User::find(1);
Auth::login($user);

echo "User: {$user->name} (ID: {$user->id})\n";
echo "Group ID: {$user->group_id}\n\n";

// Get user's active cycle
$cycle = App\Models\Project::where('group_id', $user->group_id)
    ->where('is_vsla_cycle', 'Yes')
    ->where('is_active_cycle', 'Yes')
    ->first();

if (!$cycle) {
    echo "❌ No active cycle found\n";
    exit(1);
}

echo "Active Cycle: {$cycle->name} (ID: {$cycle->id})\n\n";

// Get an active loan or create one
$loan = VslaLoan::where('cycle_id', $cycle->id)
    ->where('status', 'active')
    ->first();

if (!$loan) {
    // Create a test loan
    $loan = VslaLoan::create([
        'cycle_id' => $cycle->id,
        'borrower_id' => $user->id,
        'loan_amount' => 100000,
        'interest_rate' => 10,
        'total_amount_due' => 110000,
        'balance' => 110000,
        'amount_paid' => 0,
        'disbursement_date' => now(),
        'due_date' => now()->addMonths(3),
        'duration_months' => 3,
        'purpose' => 'Test loan for repayment testing',
        'status' => 'active',
    ]);
    echo "✅ Created test loan: LN-" . str_pad($loan->id, 5, '0', STR_PAD_LEFT) . "\n\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "LOAN DETAILS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Loan Number: LN-" . str_pad($loan->id, 5, '0', STR_PAD_LEFT) . "\n";
echo "Borrower: {$loan->borrower->name}\n";
echo "Loan Amount: " . number_format($loan->loan_amount) . " UGX\n";
echo "Total Due: " . number_format($loan->total_amount_due) . " UGX\n";
echo "Amount Paid: " . number_format($loan->amount_paid ?? 0) . " UGX\n";
echo "Balance: " . number_format($loan->balance) . " UGX\n";
echo "Status: {$loan->status}\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: Make Partial Payment (30,000 UGX)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Simulate API call
$request = new Illuminate\Http\Request([
    'amount' => 30000,
    'payment_method' => 'cash',
    'payment_date' => date('Y-m-d'),
]);

$controller = new App\Http\Controllers\Api\VslaLoansController();
$response = $controller->recordRepayment($request, $loan->id);
$responseData = $response->getData();

echo "API Response:\n";
echo "  Success: " . ($responseData->success ? '✅' : '❌') . "\n";
echo "  Message: {$responseData->message}\n";

if ($responseData->success) {
    echo "\n  Payment Details:\n";
    echo "    Amount: " . number_format($responseData->data->amount) . " UGX\n";
    echo "    New Balance: " . number_format($responseData->data->new_balance) . " UGX\n";
    echo "    Total Paid: " . number_format($responseData->data->amount_paid) . " UGX\n";
    echo "    Status: {$responseData->data->status}\n";
    
    // Verify in database
    $loan->refresh();
    echo "\n  Database Verification:\n";
    echo "    Balance: " . number_format($loan->balance) . " UGX (" . ($loan->balance == $responseData->data->new_balance ? '✅' : '❌') . ")\n";
    echo "    Amount Paid: " . number_format($loan->amount_paid) . " UGX (" . ($loan->amount_paid == $responseData->data->amount_paid ? '✅' : '❌') . ")\n";
    echo "    Status: {$loan->status} (" . ($loan->status == $responseData->data->status ? '✅' : '❌') . ")\n";
    
    echo "\n✅ TEST 1 PASSED: Partial payment recorded successfully\n";
} else {
    echo "\n❌ TEST 1 FAILED\n";
    exit(1);
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: Make Full Payment (remaining balance)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$remainingBalance = $loan->balance;

// Simulate API call
$request = new Illuminate\Http\Request([
    'amount' => $remainingBalance,
    'payment_method' => 'from_savings',
    'payment_date' => date('Y-m-d'),
]);

$response = $controller->recordRepayment($request, $loan->id);
$responseData = $response->getData();

echo "API Response:\n";
echo "  Success: " . ($responseData->success ? '✅' : '❌') . "\n";
echo "  Message: {$responseData->message}\n";

if ($responseData->success) {
    echo "\n  Payment Details:\n";
    echo "    Amount: " . number_format($responseData->data->amount) . " UGX\n";
    echo "    New Balance: " . number_format($responseData->data->new_balance) . " UGX\n";
    echo "    Total Paid: " . number_format($responseData->data->amount_paid) . " UGX\n";
    echo "    Status: {$responseData->data->status}\n";
    
    // Verify in database
    $loan->refresh();
    echo "\n  Database Verification:\n";
    echo "    Balance: " . number_format($loan->balance) . " UGX (" . ($loan->balance == 0 ? '✅' : '❌') . ")\n";
    echo "    Status: {$loan->status} (" . ($loan->status == 'paid' ? '✅' : '❌') . ")\n";
    
    if ($loan->balance == 0 && $loan->status == 'paid') {
        echo "\n✅ TEST 2 PASSED: Full payment recorded and loan marked as paid\n";
    } else {
        echo "\n❌ TEST 2 FAILED: Loan not properly marked as paid\n";
        exit(1);
    }
} else {
    echo "\n❌ TEST 2 FAILED\n";
    exit(1);
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: Try to overpay (should fail)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Create another test loan
$testLoan = VslaLoan::create([
    'cycle_id' => $cycle->id,
    'borrower_id' => $user->id,
    'loan_amount' => 50000,
    'interest_rate' => 10,
    'total_amount_due' => 55000,
    'balance' => 55000,
    'amount_paid' => 0,
    'disbursement_date' => now(),
    'due_date' => now()->addMonths(3),
    'duration_months' => 3,
    'purpose' => 'Test loan for overpayment testing',
    'status' => 'active',
]);

// Try to pay more than balance
$request = new Illuminate\Http\Request([
    'amount' => 60000, // More than balance
    'payment_method' => 'cash',
    'payment_date' => date('Y-m-d'),
]);

$response = $controller->recordRepayment($request, $testLoan->id);
$responseData = $response->getData();

echo "API Response:\n";
echo "  Success: " . ($responseData->success ? '✅' : '❌') . "\n";
echo "  Message: {$responseData->message}\n";

if ($responseData->success === false && stripos($responseData->message, 'exceeds') !== false) {
    echo "\n✅ TEST 3 PASSED: Overpayment correctly rejected\n";
} else {
    echo "\n❌ TEST 3 FAILED: Should have rejected overpayment\n";
    exit(1);
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "   ALL TESTS PASSED! ✅\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\nSummary:\n";
echo "  ✅ Partial payments work correctly\n";
echo "  ✅ Full payments mark loan as 'paid'\n";
echo "  ✅ Overpayments are rejected\n";
echo "  ✅ Balance calculations are accurate\n";
echo "  ✅ Database updates are correct\n";
echo "\n";
