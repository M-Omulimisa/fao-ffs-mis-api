<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

echo "═══════════════════════════════════════════════════════════\n";
echo "   TESTING ACTION PLANS ERROR HANDLING\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Authenticate as user 211
$user = User::find(211);
Auth::login($user);
echo "✓ Authenticated as: {$user->name}\n\n";

// Test meeting with action plans data
echo "📋 Testing Meeting with Action Plans\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$meetingData = [
    'local_id' => 'TEST_ACTION_PLANS_' . uniqid(),
    'cycle_id' => 13,
    'group_id' => 13,
    'meeting_date' => '2026-01-19',
    'notes' => 'TEST: Meeting with action plans',
    'members_present' => 1,
    'members_absent' => 0,
    
    // Include action plans data (even though table doesn't exist)
    'upcoming_action_plans_data' => [
        [
            'planId' => 'PLAN_' . uniqid(),
            'action' => 'Test Action',
            'description' => 'This is a test action plan',
            'assignedToMemberId' => 213,
            'priority' => 'high',
            'dueDate' => '2026-02-01',
        ]
    ],
    
    'attendance_data' => [
        [
            'memberId' => 213,
            'memberName' => 'Biirah Sabia',
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
    
    echo "\n📋 Response:\n";
    echo "   Success: " . ($responseData->success ? 'true' : 'false') . "\n";
    echo "   Processing Status: " . ($responseData->processing_status ?? 'N/A') . "\n";
    echo "   Has Errors: " . (isset($responseData->has_errors) && $responseData->has_errors ? 'true' : 'false') . "\n";
    echo "   Has Warnings: " . (isset($responseData->has_warnings) && $responseData->has_warnings ? 'true' : 'false') . "\n";
    
    if (isset($responseData->errors) && !empty($responseData->errors)) {
        echo "\n❌ Errors:\n";
        foreach ($responseData->errors as $error) {
            echo "   - Type: {$error->type}\n";
            echo "     Message: {$error->message}\n";
        }
    }
    
    if (isset($responseData->warnings) && !empty($responseData->warnings)) {
        echo "\n⚠️  Warnings:\n";
        foreach ($responseData->warnings as $warning) {
            echo "   - Type: {$warning->type}\n";
            echo "     Message: {$warning->message}\n";
        }
    }
    
    // Check result
    if ($responseData->success && !(isset($responseData->has_errors) && $responseData->has_errors)) {
        echo "\n✅ TEST PASSED: Meeting submitted successfully with action plans warning\n";
        echo "   Meeting was not blocked by missing action plans table\n";
        exit(0);
    } else {
        echo "\n❌ TEST FAILED: Meeting submission failed or had errors\n";
        if (isset($responseData->message)) {
            echo "   Message: {$responseData->message}\n";
        }
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n❌ TEST FAILED WITH EXCEPTION\n";
    echo "   Error: {$e->getMessage()}\n";
    exit(1);
}
