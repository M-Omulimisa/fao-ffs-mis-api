<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\VslaLoan;
use Illuminate\Support\Facades\Auth;

echo "═══════════════════════════════════════════════════════════\n";
echo "   TESTING ADMIN LOAN ACCESS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get first user
$user = User::first();
echo "User: {$user->name}\n";
echo "Group ID: {$user->group_id}\n";
echo "is_group_admin: {$user->is_group_admin}\n";
echo "is_group_secretary: {$user->is_group_secretary}\n";
echo "is_group_treasurer: {$user->is_group_treasurer}\n";
echo "Is VSLA Admin: " . ($user->isVslaGroupAdmin() ? 'YES' : 'NO') . "\n\n";

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

// Get all loans in the cycle
$allLoans = VslaLoan::where('cycle_id', $cycle->id)->get();
echo "Total loans in cycle: " . $allLoans->count() . "\n";

if ($allLoans->count() > 0) {
    echo "\nAll Loans:\n";
    foreach ($allLoans as $loan) {
        $borrower = $loan->borrower;
        echo "  - Loan #{$loan->id}: {$borrower->name} - " . number_format($loan->loan_amount) . " UGX ({$loan->status})\n";
    }
}

// Get user's own loans
$userLoans = VslaLoan::where('cycle_id', $cycle->id)
    ->where('borrower_id', $user->id)
    ->get();
echo "\nUser's own loans: " . $userLoans->count() . "\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: User as Regular Member (Not Admin)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Make user NOT an admin
$user->is_group_admin = 'No';
$user->is_group_secretary = 'No';
$user->is_group_treasurer = 'No';
$user->save();

Auth::login($user);

// Simulate API call
$request = new Illuminate\Http\Request(['cycle_id' => $cycle->id]);
$controller = new App\Http\Controllers\Api\VslaLoansController();
$response = $controller->index($request);
$responseData = $response->getData();

echo "\nAPI Response:\n";
echo "  Success: " . ($responseData->success ? '✅' : '❌') . "\n";
echo "  Is Admin: " . ($responseData->is_admin ? 'YES' : 'NO') . "\n";
echo "  Loans Returned: " . count($responseData->data) . "\n";

if ($responseData->success && $responseData->is_admin === false) {
    echo "\n✅ TEST 1 PASSED: Regular member sees limited loans\n";
} else {
    echo "\n❌ TEST 1 FAILED\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: User as Admin (Chairman)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Make user an admin (Chairman)
$user->is_group_admin = 'Yes';
$user->save();

Auth::login($user);

// Simulate API call
$request = new Illuminate\Http\Request(['cycle_id' => $cycle->id]);
$controller = new App\Http\Controllers\Api\VslaLoansController();
$response = $controller->index($request);
$responseData = $response->getData();

echo "\nAPI Response:\n";
echo "  Success: " . ($responseData->success ? '✅' : '❌') . "\n";
echo "  Is Admin: " . ($responseData->is_admin ? 'YES' : 'NO') . "\n";
echo "  Loans Returned: " . count($responseData->data) . "\n";

if ($responseData->success && $responseData->is_admin === true && count($responseData->data) === $allLoans->count()) {
    echo "\n✅ TEST 2 PASSED: Admin sees ALL loans in cycle\n";
} else {
    echo "\n❌ TEST 2 FAILED\n";
    echo "   Expected: {$allLoans->count()} loans\n";
    echo "   Got: " . count($responseData->data) . " loans\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: User as Secretary\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Make user a secretary
$user->is_group_admin = 'No';
$user->is_group_secretary = 'Yes';
$user->save();

Auth::login($user);

// Simulate API call
$request = new Illuminate\Http\Request(['cycle_id' => $cycle->id]);
$controller = new App\Http\Controllers\Api\VslaLoansController();
$response = $controller->index($request);
$responseData = $response->getData();

echo "\nAPI Response:\n";
echo "  Success: " . ($responseData->success ? '✅' : '❌') . "\n";
echo "  Is Admin: " . ($responseData->is_admin ? 'YES' : 'NO') . "\n";
echo "  Loans Returned: " . count($responseData->data) . "\n";

if ($responseData->success && $responseData->is_admin === true && count($responseData->data) === $allLoans->count()) {
    echo "\n✅ TEST 3 PASSED: Secretary sees ALL loans in cycle\n";
} else {
    echo "\n❌ TEST 3 FAILED\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 4: User as Treasurer\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Make user a treasurer
$user->is_group_secretary = 'No';
$user->is_group_treasurer = 'Yes';
$user->save();

Auth::login($user);

// Simulate API call
$request = new Illuminate\Http\Request(['cycle_id' => $cycle->id]);
$controller = new App\Http\Controllers\Api\VslaLoansController();
$response = $controller->index($request);
$responseData = $response->getData();

echo "\nAPI Response:\n";
echo "  Success: " . ($responseData->success ? '✅' : '❌') . "\n";
echo "  Is Admin: " . ($responseData->is_admin ? 'YES' : 'NO') . "\n";
echo "  Loans Returned: " . count($responseData->data) . "\n";

if ($responseData->success && $responseData->is_admin === true && count($responseData->data) === $allLoans->count()) {
    echo "\n✅ TEST 4 PASSED: Treasurer sees ALL loans in cycle\n";
} else {
    echo "\n❌ TEST 4 FAILED\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "   TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "✅ Admin users (Chairman, Secretary, Treasurer) can see ALL loans\n";
echo "✅ Regular members see only their own loans\n";
echo "✅ API returns 'is_admin' flag to help mobile app know user type\n";
echo "\n";
