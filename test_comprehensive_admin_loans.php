<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\VslaLoan;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

echo "═══════════════════════════════════════════════════════════\n";
echo "   COMPREHENSIVE ADMIN LOAN ACCESS TEST\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get first user
$user1 = User::where('id', 1)->first();
$user2 = User::where('id', 2)->first();

if (!$user1 || !$user2) {
    echo "❌ Users not found\n";
    exit(1);
}

echo "User 1: {$user1->name} (ID: {$user1->id})\n";
echo "User 2: {$user2->name} (ID: {$user2->id})\n";
echo "Group ID: {$user1->group_id}\n\n";

// Get cycle 9
$cycle = Project::find(9);
if (!$cycle) {
    echo "❌ Cycle 9 not found\n";
    exit(1);
}

echo "Cycle: {$cycle->name} (ID: {$cycle->id})\n";
echo "Cycle Group ID: {$cycle->group_id}\n\n";

// Create test loans
echo "Creating test loans...\n";

// Clear existing loans for this cycle
VslaLoan::where('cycle_id', $cycle->id)->delete();

// Create loans
$loan1 = VslaLoan::create([
    'cycle_id' => $cycle->id,
    'borrower_id' => $user1->id,
    'loan_amount' => 100000,
    'interest_rate' => 10,
    'total_amount_due' => 110000,
    'balance' => 110000,
    'amount_paid' => 0,
    'disbursement_date' => now(),
    'due_date' => now()->addMonths(3),
    'duration_months' => 3,
    'purpose' => 'Test loan for User 1',
    'status' => 'active',
]);

$loan2 = VslaLoan::create([
    'cycle_id' => $cycle->id,
    'borrower_id' => $user2->id,
    'loan_amount' => 200000,
    'interest_rate' => 10,
    'total_amount_due' => 220000,
    'balance' => 220000,
    'amount_paid' => 0,
    'disbursement_date' => now(),
    'due_date' => now()->addMonths(3),
    'duration_months' => 3,
    'purpose' => 'Test loan for User 2',
    'status' => 'active',
]);

echo "✅ Created 2 test loans\n";
echo "   - Loan #{$loan1->id}: {$user1->name} - 100,000 UGX\n";
echo "   - Loan #{$loan2->id}: {$user2->name} - 200,000 UGX\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: Regular Member (User 1) - Not Admin\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Set user1 as regular member
$user1->is_group_admin = 'No';
$user1->is_group_secretary = 'No';
$user1->is_group_treasurer = 'No';
$user1->save();

Auth::login($user1);

// Simulate API call
$request = new Illuminate\Http\Request(['cycle_id' => $cycle->id]);
$controller = new App\Http\Controllers\Api\VslaLoansController();
$response = $controller->index($request);
$responseData = $response->getData();

echo "\nUser: {$user1->name}\n";
echo "Is Admin: " . ($user1->isVslaGroupAdmin() ? 'YES' : 'NO') . "\n";
echo "\nAPI Response:\n";
echo "  Success: " . ($responseData->success ? '✅' : '❌') . "\n";
echo "  Is Admin Flag: " . ($responseData->is_admin ? 'YES' : 'NO') . "\n";
echo "  Loans Returned: " . count($responseData->data) . "\n";

if (count($responseData->data) > 0) {
    echo "\n  Loans:\n";
    foreach ($responseData->data as $loan) {
        echo "    - #{$loan->id}: {$loan->borrower_name} - " . number_format($loan->loan_amount) . " UGX\n";
    }
}

$test1Pass = $responseData->success 
    && $responseData->is_admin === false 
    && count($responseData->data) === 1
    && $responseData->data[0]->borrower_id == $user1->id;

if ($test1Pass) {
    echo "\n✅ TEST 1 PASSED: Regular member sees only their own loan\n";
} else {
    echo "\n❌ TEST 1 FAILED\n";
    echo "   Expected: 1 loan (their own)\n";
    echo "   Got: " . count($responseData->data) . " loans\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: Admin (Chairman) - Can See All Loans\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Set user1 as chairman
$user1->is_group_admin = 'Yes';
$user1->save();
$user1->refresh();

Auth::login($user1);

// Simulate API call
$request = new Illuminate\Http\Request(['cycle_id' => $cycle->id]);
$controller = new App\Http\Controllers\Api\VslaLoansController();
$response = $controller->index($request);
$responseData = $response->getData();

echo "\nUser: {$user1->name}\n";
echo "Is Admin: " . ($user1->isVslaGroupAdmin() ? 'YES' : 'NO') . "\n";
echo "\nAPI Response:\n";
echo "  Success: " . ($responseData->success ? '✅' : '❌') . "\n";
echo "  Is Admin Flag: " . ($responseData->is_admin ? 'YES' : 'NO') . "\n";
echo "  Loans Returned: " . count($responseData->data) . "\n";

if (count($responseData->data) > 0) {
    echo "\n  Loans:\n";
    foreach ($responseData->data as $loan) {
        echo "    - #{$loan->id}: {$loan->borrower_name} - " . number_format($loan->loan_amount) . " UGX\n";
    }
}

$test2Pass = $responseData->success 
    && $responseData->is_admin === true 
    && count($responseData->data) === 2;

if ($test2Pass) {
    echo "\n✅ TEST 2 PASSED: Admin (Chairman) sees ALL loans in cycle\n";
} else {
    echo "\n❌ TEST 2 FAILED\n";
    echo "   Expected: 2 loans (all loans in cycle)\n";
    echo "   Got: " . count($responseData->data) . " loans\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: Secretary - Can See All Loans\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Set user1 as secretary
$user1->is_group_admin = 'No';
$user1->is_group_secretary = 'Yes';
$user1->save();
$user1->refresh();

Auth::login($user1);

// Simulate API call
$request = new Illuminate\Http\Request(['cycle_id' => $cycle->id]);
$controller = new App\Http\Controllers\Api\VslaLoansController();
$response = $controller->index($request);
$responseData = $response->getData();

echo "\nUser: {$user1->name}\n";
echo "Is Admin: " . ($user1->isVslaGroupAdmin() ? 'YES' : 'NO') . "\n";
echo "\nAPI Response:\n";
echo "  Success: " . ($responseData->success ? '✅' : '❌') . "\n";
echo "  Is Admin Flag: " . ($responseData->is_admin ? 'YES' : 'NO') . "\n";
echo "  Loans Returned: " . count($responseData->data) . "\n";

$test3Pass = $responseData->success 
    && $responseData->is_admin === true 
    && count($responseData->data) === 2;

if ($test3Pass) {
    echo "\n✅ TEST 3 PASSED: Secretary sees ALL loans in cycle\n";
} else {
    echo "\n❌ TEST 3 FAILED\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 4: Treasurer - Can See All Loans\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Set user1 as treasurer
$user1->is_group_secretary = 'No';
$user1->is_group_treasurer = 'Yes';
$user1->save();
$user1->refresh();

Auth::login($user1);

// Simulate API call
$request = new Illuminate\Http\Request(['cycle_id' => $cycle->id]);
$controller = new App\Http\Controllers\Api\VslaLoansController();
$response = $controller->index($request);
$responseData = $response->getData();

echo "\nUser: {$user1->name}\n";
echo "Is Admin: " . ($user1->isVslaGroupAdmin() ? 'YES' : 'NO') . "\n";
echo "\nAPI Response:\n";
echo "  Success: " . ($responseData->success ? '✅' : '❌') . "\n";
echo "  Is Admin Flag: " . ($responseData->is_admin ? 'YES' : 'NO') . "\n";
echo "  Loans Returned: " . count($responseData->data) . "\n";

$test4Pass = $responseData->success 
    && $responseData->is_admin === true 
    && count($responseData->data) === 2;

if ($test4Pass) {
    echo "\n✅ TEST 4 PASSED: Treasurer sees ALL loans in cycle\n";
} else {
    echo "\n❌ TEST 4 FAILED\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "   FINAL SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n";

if ($test1Pass && $test2Pass && $test3Pass && $test4Pass) {
    echo "✅ ALL TESTS PASSED!\n\n";
    echo "Implementation Summary:\n";
    echo "  • Regular members see only their own loans\n";
    echo "  • Chairman sees ALL loans in the SACCO\n";
    echo "  • Secretary sees ALL loans in the SACCO\n";
    echo "  • Treasurer sees ALL loans in the SACCO\n";
    echo "  • API returns 'is_admin' flag for mobile app\n";
    echo "  • User.isVslaGroupAdmin() helper method created\n";
} else {
    echo "❌ SOME TESTS FAILED\n";
}

echo "\n";
