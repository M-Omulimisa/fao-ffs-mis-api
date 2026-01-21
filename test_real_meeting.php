<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

echo "Testing Real Meeting Submission with Action Plans\n";
echo "==================================================\n\n";

// Authenticate
$user = User::find(211);
Auth::login($user);

// Real meeting data matching mobile app format
$meetingData = [
    'local_id' => 'test-real-' . uniqid(),
    'cycle_id' => 13,
    'group_id' => 13,
    'meeting_date' => '2026-01-19',
    'meeting_number' => 1,
    'notes' => 'Real test meeting',
    'members_present' => 1,
    'members_absent' => 0,
    'total_fines_collected' => 2000.0,
    'total_loans_disbursed' => 50000.0,
    'total_shares_sold' => 4,
    'total_share_value' => 20000.0,
    
    'attendance_data' => [
        [
            'memberId' => 213,
            'memberName' => 'Biirah Sabia',
            'isPresent' => true
        ]
    ],
    
    'transactions_data' => [
        [
            'memberId' => 212,
            'memberName' => 'Bwambale Muhidin',
            'accountType' => 'fine',
            'amount' => 2000.0,
            'description' => 'Late arrival'
        ]
    ],
    
    'loans_data' => [
        [
            'local_id' => 'loan-' . uniqid(),
            'borrower_id' => 211,
            'borrower_name' => 'Muhindo Mubaraka Bentley',
            'loan_amount' => 50000.0,
            'interest_rate' => 10.0,
            'loan_purpose' => 'school fees',
            'repayment_period_months' => 3
        ]
    ],
    
    'share_purchases_data' => [
        [
            'local_id' => 'share-' . uniqid(),
            'investor_id' => 213,
            'investor_name' => 'Biirah Sabia',
            'number_of_shares' => 4,
            'share_price_at_purchase' => 5000.0,
            'total_amount_paid' => 20000.0
        ]
    ],
    
    // THIS IS THE KEY PART - action plans data
    'upcoming_action_plans_data' => [
        [
            'local_id' => 'plan-' . uniqid(),
            'description' => 'Action 3.1',
            'responsible_member_id' => 213,
            'responsible_member_name' => 'Biirah Sabia',
            'due_date' => '2026-02-19',
            'priority' => 'medium',
            'notes' => 'some message'
        ],
        [
            'local_id' => 'plan-' . uniqid(),
            'description' => 'action 3.2',
            'responsible_member_id' => 212,
            'responsible_member_name' => 'Bwambale Muhidin',
            'due_date' => '2026-02-27',
            'priority' => 'medium',
            'notes' => 'some details'
        ]
    ]
];

try {
    $request = new Request($meetingData);
    $controller = new App\Http\Controllers\Api\VslaMeetingController(
        new App\Services\MeetingProcessingService()
    );
    
    echo "Submitting meeting...\n";
    $response = $controller->submit($request);
    $responseData = $response->getData();
    
    echo "\nFull Response:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "\n✅ SUCCESS!\n";
    echo "Success: " . ($responseData->success ? 'true' : 'false') . "\n";
    if (isset($responseData->meeting_id)) {
        echo "Meeting ID: {$responseData->meeting_id}\n";
    }
    echo "Has Errors: " . (isset($responseData->has_errors) && $responseData->has_errors ? 'true' : 'false') . "\n";
    echo "Has Warnings: " . (isset($responseData->has_warnings) && $responseData->has_warnings ? 'true' : 'false') . "\n\n";
    
    if (isset($responseData->warnings) && !empty($responseData->warnings)) {
        echo "⚠️  Warnings:\n";
        foreach ($responseData->warnings as $warning) {
            echo "  - " . (is_object($warning) ? $warning->message : json_encode($warning)) . "\n";
        }
    }
    
    if (isset($responseData->errors) && !empty($responseData->errors)) {
        echo "\n❌ Errors:\n";
        foreach ($responseData->errors as $error) {
            echo "  - " . (is_object($error) ? $error->message : json_encode($error)) . "\n";
        }
    }
    
    echo "\n✅ Test passed - meeting submitted successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ FAILED\n";
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}
