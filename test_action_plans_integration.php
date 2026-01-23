<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\VslaActionPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

echo "═══════════════════════════════════════════════════════════\n";
echo "   TESTING ACTION PLANS INTEGRATION WITH MEETINGS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Authenticate as first user
$user = User::first();
Auth::login($user);
echo "✓ Authenticated as: {$user->name}\n";
echo "✓ Group: {$user->group->name}\n\n";

// Get user's active cycle
$cycle = App\Models\Project::where('group_id', $user->group_id)
    ->where('is_vsla_cycle', 'Yes')
    ->where('is_active_cycle', 'Yes')
    ->first();

if (!$cycle) {
    echo "❌ No active cycle found for user's group\n";
    exit(1);
}

echo "✓ Cycle: {$cycle->id}\n\n";

// Count existing action plans before test
$beforeCount = VslaActionPlan::where('cycle_id', $cycle->id)->count();
echo "📊 Action plans before test: {$beforeCount}\n\n";

// Test 1: Submit meeting with upcoming action plans
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: Submit Meeting with Upcoming Action Plans\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$testPlanId = 'TEST_PLAN_' . uniqid();
$meetingData = [
    'local_id' => 'TEST_MEETING_' . uniqid(),
    'cycle_id' => $cycle->id,
    'group_id' => $user->group_id,
    'meeting_date' => date('Y-m-d'),
    'notes' => 'TEST: Meeting with action plans',
    'members_present' => 1,
    'members_absent' => 0,
    
    // Upcoming action plans (NEW format matching mobile app)
    'upcoming_action_plans_data' => [
        [
            'planId' => $testPlanId,
            'action' => 'Test Action Plan from Meeting',
            'description' => 'This is a detailed description/notes for the action plan',
            'assigned_to_member_id' => $user->id,
            'priority' => 'high',
            'due_date' => date('Y-m-d', strtotime('+7 days')),
        ],
        [
            'planId' => 'TEST_PLAN_2_' . uniqid(),
            'action' => 'Second Test Action Plan',
            'description' => 'Another action plan to verify multiple plans work',
            'assigned_to_member_id' => $user->id,
            'priority' => 'medium',
            'due_date' => date('Y-m-d', strtotime('+14 days')),
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
    
    echo "\n📋 Full Response:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "\n📋 Response Summary:\n";
    echo "   Success: " . ($responseData->success ? '✅ true' : '❌ false') . "\n";
    echo "   Processing Status: " . ($responseData->processing_status ?? 'N/A') . "\n";
    
    if (isset($responseData->warnings) && !empty($responseData->warnings)) {
        echo "\n⚠️  Warnings:\n";
        foreach ($responseData->warnings as $warning) {
            echo "   - {$warning->type}: {$warning->message}\n";
        }
    }
    
    if (isset($responseData->errors) && !empty($responseData->errors)) {
        echo "\n❌ Errors:\n";
        foreach ($responseData->errors as $error) {
            echo "   - {$error->type}: {$error->message}\n";
        }
    }
    
    // Count action plans after submission
    $afterCount = VslaActionPlan::where('cycle_id', $cycle->id)->count();
    $newPlans = $afterCount - $beforeCount;
    
    echo "\n📊 Action plans after test: {$afterCount}\n";
    echo "📊 New action plans created: {$newPlans}\n";
    
    // Verify the action plan was created
    $createdPlan = VslaActionPlan::where('local_id', $testPlanId)->first();
    
    if ($createdPlan) {
        echo "\n✅ Action plan created successfully!\n";
        echo "   ID: {$createdPlan->id}\n";
        echo "   Action: {$createdPlan->action}\n";
        echo "   Description: {$createdPlan->description}\n";
        echo "   Priority: {$createdPlan->priority}\n";
        echo "   Due Date: {$createdPlan->due_date}\n";
        echo "   Status: {$createdPlan->status}\n";
        echo "   Assigned To: " . ($createdPlan->assignedTo ? $createdPlan->assignedTo->name : 'N/A') . "\n";
    } else {
        echo "\n❌ Action plan NOT created!\n";
        echo "   Looking for local_id: {$testPlanId}\n";
        
        // Debug: Show all plans with this meeting
        $meetingPlans = VslaActionPlan::where('cycle_id', $cycle->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        echo "\n📋 Recent action plans in this cycle:\n";
        foreach ($meetingPlans as $plan) {
            echo "   - ID: {$plan->id}, Local ID: {$plan->local_id}, Action: {$plan->action}\n";
        }
    }
    
    if ($responseData->success && $newPlans >= 2) {
        echo "\n✅ TEST PASSED: Meeting submitted and action plans created\n";
        exit(0);
    } else {
        echo "\n❌ TEST FAILED: Action plans were not created properly\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n❌ TEST FAILED WITH EXCEPTION\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
