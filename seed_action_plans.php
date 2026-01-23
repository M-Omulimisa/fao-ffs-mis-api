<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VslaActionPlan;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;

// Get active cycle for the first user's group
$user = User::first();
$group = $user->group;

if (!$group) {
    echo "User has no group\n";
    exit;
}

$cycle = Project::where('group_id', $group->id)
    ->where('is_vsla_cycle', 'Yes')
    ->where('is_active_cycle', 'Yes')
    ->first();

if (!$cycle) {
    echo "No active VSLA cycle found for group: {$group->name}\n";
    exit;
}

// Get users from the same group
$users = User::where('group_id', $group->id)->limit(5)->get();

if ($users->isEmpty()) {
    echo "No users found in group\n";
    exit;
}

echo "Creating action plans for cycle: {$cycle->id} ({$cycle->name}) in group: {$group->name}\n";

// Clear existing action plans for this cycle
VslaActionPlan::where('cycle_id', $cycle->id)->forceDelete();

// Create active/pending action plans
$pendingPlans = [
    [
        'description' => 'Review and update group constitution to include new members',
        'priority' => 'high',
        'due_date' => Carbon::now()->addDays(2),
        'status' => 'pending',
    ],
    [
        'description' => 'Organize training session on financial literacy for all members',
        'priority' => 'high',
        'due_date' => Carbon::now()->addDays(5),
        'status' => 'pending',
    ],
    [
        'description' => 'Conduct field visit to neighboring VSLA group to learn best practices',
        'priority' => 'medium',
        'due_date' => Carbon::now()->addDays(10),
        'status' => 'pending',
    ],
    [
        'description' => 'Purchase lockbox and additional record books for the group',
        'priority' => 'high',
        'due_date' => Carbon::now()->addDays(-3), // Overdue
        'status' => 'pending',
    ],
    [
        'description' => 'Prepare quarterly financial report for members review',
        'priority' => 'medium',
        'due_date' => Carbon::now()->addDays(14),
        'status' => 'pending',
    ],
    [
        'description' => 'Follow up with members who have outstanding loan payments',
        'priority' => 'high',
        'due_date' => Carbon::now()->addDays(-1), // Overdue
        'status' => 'pending',
    ],
    [
        'description' => 'Schedule and plan end-of-cycle shareout ceremony',
        'priority' => 'low',
        'due_date' => Carbon::now()->addDays(30),
        'status' => 'pending',
    ],
    [
        'description' => 'Update member contact information in group records',
        'priority' => 'low',
        'due_date' => Carbon::now()->addDays(20),
        'status' => 'pending',
    ],
];

foreach ($pendingPlans as $index => $planData) {
    $plan = VslaActionPlan::create([
        'cycle_id' => $cycle->id,
        'description' => $planData['description'],
        'priority' => $planData['priority'],
        'due_date' => $planData['due_date'],
        'status' => $planData['status'],
        'assigned_to_member_id' => $users[$index % $users->count()]->id,
        'created_by_id' => $users[0]->id,
    ]);
    echo "Created pending action plan: {$plan->description}\n";
}

// Create completed action plans
$completedPlans = [
    [
        'description' => 'Conduct member orientation for three new group members',
        'priority' => 'medium',
        'completion_notes' => 'All new members successfully oriented. They demonstrated good understanding of VSLA principles.',
    ],
    [
        'description' => 'Verify and update savings records from last meeting',
        'priority' => 'high',
        'completion_notes' => 'All records verified and updated. Found minor discrepancy which has been corrected.',
    ],
    [
        'description' => 'Reconcile cash box amount with recorded transactions',
        'priority' => 'high',
        'completion_notes' => 'Cash box balanced perfectly with all records.',
    ],
    [
        'description' => 'Collect membership renewal fees for the new cycle',
        'priority' => 'medium',
        'completion_notes' => 'Successfully collected fees from 28 out of 30 members. Two members requested extension.',
    ],
    [
        'description' => 'Submit monthly activity report to district facilitator',
        'priority' => 'low',
        'completion_notes' => 'Report submitted on time via email and hard copy delivered.',
    ],
];

foreach ($completedPlans as $index => $planData) {
    $completedDate = Carbon::now()->subDays(rand(5, 30));
    $plan = VslaActionPlan::create([
        'cycle_id' => $cycle->id,
        'description' => $planData['description'],
        'priority' => $planData['priority'],
        'due_date' => $completedDate->copy()->subDays(5),
        'status' => 'completed',
        'completion_notes' => $planData['completion_notes'],
        'completed_at' => $completedDate,
        'assigned_to_member_id' => $users[$index % $users->count()]->id,
        'created_by_id' => $users[0]->id,
    ]);
    echo "Created completed action plan: {$plan->description}\n";
}

echo "\nSeeding completed!\n";
echo "Total pending plans: " . VslaActionPlan::where('cycle_id', $cycle->id)->where('status', 'pending')->count() . "\n";
echo "Total completed plans: " . VslaActionPlan::where('cycle_id', $cycle->id)->where('status', 'completed')->count() . "\n";
