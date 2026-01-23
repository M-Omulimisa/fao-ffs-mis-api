<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

echo "═══════════════════════════════════════════════════════════\n";
echo "   TESTING GROUP ID FROM USER PROFILE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Authenticate as first user
$user = User::first();
Auth::login($user);
echo "✓ Authenticated as: {$user->name}\n";
echo "✓ User's Group ID: {$user->group_id}\n";
echo "✓ Group Name: {$user->group->name}\n";
echo "✓ Group Type: {$user->group->type}\n\n";

// Get user's active cycle
$cycle = App\Models\Project::where('group_id', $user->group_id)
    ->where('is_vsla_cycle', 'Yes')
    ->where('is_active_cycle', 'Yes')
    ->first();

if (!$cycle) {
    echo "❌ No active cycle found\n";
    exit(1);
}

echo "✓ Cycle ID: {$cycle->id}\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: Submit Meeting WITHOUT group_id in request\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$meetingData = [
    'local_id' => 'TEST_NO_GROUP_' . uniqid(),
    'cycle_id' => $cycle->id,
    // NOTE: group_id is NOT provided - should be taken from user profile
    'meeting_date' => date('Y-m-d'),
    'notes' => 'TEST: Meeting without group_id in request',
    'members_present' => 1,
    'members_absent' => 0,
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
    
    echo "\n📋 Response:\n";
    echo "   Success: " . ($responseData->success ? '✅ true' : '❌ false') . "\n";
    echo "   Message: {$responseData->message}\n";
    
    if ($responseData->success) {
        echo "   Meeting ID: {$responseData->meeting_id}\n";
        echo "   Meeting Number: {$responseData->meeting_number}\n";
        
        // Verify group_id was set from user profile
        $meeting = App\Models\VslaMeeting::find($responseData->meeting_id);
        echo "   Meeting's Group ID: {$meeting->group_id}\n";
        
        if ($meeting->group_id == $user->group_id) {
            echo "\n✅ TEST PASSED: group_id correctly taken from user profile\n";
        } else {
            echo "\n❌ TEST FAILED: group_id mismatch\n";
            echo "   Expected: {$user->group_id}\n";
            echo "   Got: {$meeting->group_id}\n";
        }
        exit(0);
    } else {
        echo "\n❌ TEST FAILED: Meeting submission failed\n";
        if (isset($responseData->errors)) {
            foreach ($responseData->errors as $error) {
                echo "   - {$error}\n";
            }
        }
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n❌ TEST FAILED WITH EXCEPTION\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
