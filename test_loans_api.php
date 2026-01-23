<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\VslaLoan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

echo "═══════════════════════════════════════════════════════════\n";
echo "   TESTING VSLA LOANS API\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Authenticate as first user
$user = User::first();
Auth::login($user);
echo "✓ Authenticated as: {$user->name}\n";

// Get user's cycle
$cycle = App\Models\Project::where('group_id', $user->group_id)
    ->where('is_vsla_cycle', 'Yes')
    ->where('is_active_cycle', 'Yes')
    ->first();

if (!$cycle) {
    echo "❌ No active cycle found\n";
    exit(1);
}

echo "✓ Cycle ID: {$cycle->id}\n\n";

// Count loans in cycle
$loansCount = VslaLoan::where('cycle_id', $cycle->id)->count();
echo "📊 Total loans in cycle: {$loansCount}\n\n";

if ($loansCount === 0) {
    echo "⚠️  No loans found. Creating test loan via meeting...\n\n";
    
    // Create a test meeting with a loan
    $meetingData = [
        'local_id' => 'TEST_LOAN_MEETING_' . uniqid(),
        'cycle_id' => $cycle->id,
        'group_id' => $user->group_id,
        'meeting_date' => date('Y-m-d'),
        'notes' => 'TEST: Meeting with loan',
        'members_present' => 1,
        'members_absent' => 0,
        
        'loans_data' => [
            [
                'borrower_id' => $user->id,
                'loan_amount' => 500000,
                'interest_rate' => 5,
                'repayment_period_months' => 3,
                'loan_purpose' => 'Small business expansion',
            ]
        ],
        
        'attendance_data' => [
            [
                'memberId' => $user->id,
                'memberName' => $user->name,
                'isPresent' => true
            ]
        ]
    ];
    
    try {
        $request = new Request($meetingData);
        $controller = new App\Http\Controllers\Api\VslaMeetingController(
            new App\Services\MeetingProcessingService()
        );
        
        $response = $controller->submit($request);
        $responseData = $response->getData();
        
        if ($responseData->success) {
            echo "✅ Test meeting with loan created successfully\n";
            echo "   Meeting ID: {$responseData->meeting_id}\n\n";
            
            $loansCount = VslaLoan::where('cycle_id', $cycle->id)->count();
            echo "📊 Loans after meeting: {$loansCount}\n\n";
        } else {
            echo "❌ Failed to create test meeting\n";
            echo "   Message: {$responseData->message}\n";
            exit(1);
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Test the loans API endpoint
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Testing GET /api/vsla/loans\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $request = new Request(['cycle_id' => $cycle->id]);
    $controller = new App\Http\Controllers\Api\VslaLoansController();
    
    $response = $controller->index($request);
    $responseData = $response->getData();
    
    echo "\n📋 Response:\n";
    echo "   Success: " . ($responseData->success ? '✅ true' : '❌ false') . "\n";
    echo "   Code: {$responseData->code}\n";
    echo "   Message: {$responseData->message}\n";
    echo "   Loans Count: " . count($responseData->data) . "\n\n";
    
    if ($responseData->success && count($responseData->data) > 0) {
        echo "📋 Sample Loan:\n";
        $loan = $responseData->data[0];
        echo "   ID: {$loan->id}\n";
        echo "   Loan Number: {$loan->loan_number}\n";
        echo "   Borrower: {$loan->borrower_name}\n";
        echo "   Amount: " . number_format($loan->loan_amount, 2) . "\n";
        echo "   Interest Rate: {$loan->interest_rate}%\n";
        echo "   Total Due: " . number_format($loan->total_amount_due, 2) . "\n";
        echo "   Balance: " . number_format($loan->balance, 2) . "\n";
        echo "   Status: {$loan->status}\n";
        echo "   Disbursement Date: {$loan->disbursement_date}\n";
        echo "   Due Date: {$loan->due_date}\n";
        
        echo "\n✅ TEST PASSED: Loans API working correctly\n";
        exit(0);
    } else {
        echo "\n❌ TEST FAILED: No loans returned\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n❌ TEST FAILED WITH EXCEPTION\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
