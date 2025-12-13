<?php

/**
 * Test Mobile App Meeting Submission
 * Tests the exact payload structure from the mobile app error logs
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\VslaMeeting;
use Illuminate\Support\Facades\Http;

echo "=== TESTING MOBILE APP MEETING SUBMISSION ===\n\n";

// Get a test user for authentication
$user = User::first();
if (!$user) {
    echo "❌ No users found in database\n";
    exit(1);
}

echo "1. Authenticating as user: {$user->name} (ID: {$user->id})\n";

// Login to get token
$loginResponse = Http::post('http://10.0.2.2:8888/fao-ffs-mis-api/api/vsla-onboarding/register-admin', [
    'phone_number' => $user->phone_number_1 ?? $user->username,
    'password' => 'password', // Assuming default password
]);

if (!$loginResponse->successful()) {
    echo "❌ Failed to login\n";
    echo "Response: " . $loginResponse->body() . "\n";
    exit(1);
}

$token = $loginResponse->json()['data']['token'] ?? null;
if (!$token) {
    echo "❌ No token received\n";
    exit(1);
}

echo "✓ Authenticated successfully\n\n";

// Prepare payload exactly as mobile app sends it
$payload = [
    'local_id' => '462866a0-757a-4aff-98a7-72bdb8dd5d3f',
    'cycle_id' => 1,
    'group_id' => 5,
    'meeting_date' => '2025-12-13',
    'meeting_number' => 1,
    'notes' => 'SOme notes',
    'members_present' => 3,
    'members_absent' => 2,
    'total_savings_collected' => 2000.0,
    'total_welfare_collected' => 0.0,
    'total_social_fund_collected' => 0.0,
    'total_fines_collected' => 5000.0,
    'total_loans_disbursed' => 4000.0,
    'total_shares_sold' => 9,
    'total_share_value' => 45000.0,
    'attendance_data' => [
        [
            'memberId' => 273,
            'memberName' => 'Biirah Sabia',
            'memberCode' => 'MEM-2025-0021',
            'phoneNumber' => '0701222222',
            'isPresent' => true,
            'arrivalTime' => '7:25 AM',
            'absentReason' => null,
        ],
        [
            'memberId' => 215,
            'memberName' => 'Bwambale Muhidin',
            'memberCode' => 'AMU-MEM-25-0004',
            'phoneNumber' => '+256772111111',
            'isPresent' => true,
            'arrivalTime' => '7:25 AM',
            'absentReason' => null,
        ],
        [
            'memberId' => 216,
            'memberName' => 'Kule Swaleh',
            'memberCode' => 'AMU-MEM-25-0005',
            'phoneNumber' => '+256772222222',
            'isPresent' => true,
            'arrivalTime' => '7:25 AM',
            'absentReason' => null,
        ],
    ],
    'transactions_data' => [],
    'loans_data' => [],
    'share_purchases_data' => [],
    'previous_action_plans_data' => [],
    'upcoming_action_plans_data' => [],
];

echo "2. Submitting meeting with payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Submit meeting
$response = Http::withToken($token)
    ->post('http://10.0.2.2:8888/fao-ffs-mis-api/api/vsla-meetings/submit', $payload);

echo "3. Response received:\n";
echo "Status Code: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n\n";

if ($response->successful()) {
    echo "✅ Meeting submitted successfully!\n";
    $data = $response->json();
    echo "   Meeting ID: " . ($data['meeting_id'] ?? 'N/A') . "\n";
    echo "   Meeting Number: " . ($data['meeting_number'] ?? 'N/A') . "\n";
    echo "   Processing Status: " . ($data['processing_status'] ?? 'N/A') . "\n";
} else {
    echo "❌ Meeting submission failed!\n";
    $data = $response->json();
    if (isset($data['data']['errors'])) {
        echo "   Errors:\n";
        foreach ($data['data']['errors'] as $field => $errors) {
            echo "   - $field: " . implode(', ', $errors) . "\n";
        }
    }
}

echo "\n=== TEST COMPLETE ===\n";
