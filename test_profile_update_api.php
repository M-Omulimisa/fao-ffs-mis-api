<?php

/**
 * Test script for User Profile Update API
 * Tests various scenarios: valid data, missing fields, invalid data
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiResurceController;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "===========================================\n";
echo "  PROFILE UPDATE API TEST SUITE\n";
echo "===========================================\n\n";

// Find a test user or use first available user
$testUser = User::where('phone_number', 'LIKE', '256%')->first();

if (!$testUser) {
    // Get any user from the database
    $testUser = User::first();
}

if (!$testUser) {
    echo "❌ No users found in database. Please create a user first.\n";
    exit(1);
}

echo "✅ Using test user: ID {$testUser->id} ({$testUser->name})\n";
echo "   Phone: {$testUser->phone_number}\n\n";

// Test scenarios
$scenarios = [
    [
        'name' => 'VALID UPDATE - All Fields',
        'data' => [
            'user' => $testUser->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'sex' => 'Male',
            'dob' => '1995-05-15',
            'national_id' => 'CM12345678901234',
            'country' => 'Uganda',
            'district' => 'Kampala',
            'subcounty' => 'Central',
            'parish' => 'Nakasero',
            'village' => 'Zone 1',
            'address' => '123 Main Street',
            'occupation' => 'Software Developer',
            'marital_status' => 'Single',
            'education_level' => 'University',
            'household_size' => '4',
        ],
        'expected' => 'success'
    ],
    [
        'name' => 'MISSING REQUIRED - No First Name',
        'data' => [
            'user' => $testUser->id,
            'last_name' => 'Doe',
            'sex' => 'Male',
            'dob' => '1995-05-15',
        ],
        'expected' => 'error'
    ],
    [
        'name' => 'MISSING REQUIRED - No Last Name',
        'data' => [
            'user' => $testUser->id,
            'first_name' => 'John',
            'sex' => 'Male',
            'dob' => '1995-05-15',
        ],
        'expected' => 'error'
    ],
    [
        'name' => 'MISSING REQUIRED - No Gender',
        'data' => [
            'user' => $testUser->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dob' => '1995-05-15',
        ],
        'expected' => 'error'
    ],
    [
        'name' => 'MISSING REQUIRED - No DOB',
        'data' => [
            'user' => $testUser->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'sex' => 'Male',
        ],
        'expected' => 'error'
    ],
    [
        'name' => 'INVALID DOB - Future Date',
        'data' => [
            'user' => $testUser->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'sex' => 'Male',
            'dob' => '2030-01-01',
        ],
        'expected' => 'error'
    ],
    [
        'name' => 'INVALID DOB - Too Young',
        'data' => [
            'user' => $testUser->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'sex' => 'Male',
            'dob' => '2020-01-01',
        ],
        'expected' => 'error'
    ],
    [
        'name' => 'INVALID GENDER',
        'data' => [
            'user' => $testUser->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'sex' => 'Unknown',
            'dob' => '1995-05-15',
        ],
        'expected' => 'error'
    ],
    [
        'name' => 'SHORT NAMES - Less than 2 chars',
        'data' => [
            'user' => $testUser->id,
            'first_name' => 'J',
            'last_name' => 'D',
            'sex' => 'Male',
            'dob' => '1995-05-15',
        ],
        'expected' => 'error'
    ],
    [
        'name' => 'PARTIAL UPDATE - Only Basic Info',
        'data' => [
            'user' => $testUser->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'sex' => 'Female',
            'dob' => '1992-08-20',
        ],
        'expected' => 'success'
    ],
    [
        'name' => 'OPTIONAL FIELDS - With National ID',
        'data' => [
            'user' => $testUser->id,
            'first_name' => 'Mary',
            'last_name' => 'Johnson',
            'sex' => 'Female',
            'dob' => '1988-12-10',
            'national_id' => 'CM98765432109876',
            'occupation' => 'Teacher',
            'education_level' => 'Bachelor',
        ],
        'expected' => 'success'
    ],
];

// Run tests
$passedCount = 0;
$failedCount = 0;

foreach ($scenarios as $index => $scenario) {
    $testNumber = $index + 1;
    echo "-------------------------------------------\n";
    echo "TEST {$testNumber}: {$scenario['name']}\n";
    echo "-------------------------------------------\n";

    try {
        // Create mock request
        $request = Request::create('/api/users/update-profile', 'POST', $scenario['data']);
        $request->merge(['user' => $scenario['data']['user']]);

        // Call controller method
        $controller = new ApiResurceController();
        $response = $controller->users_update_profile($request);

        // Check response
        $responseData = json_decode($response->getContent(), true);
        $actualResult = $responseData['code'] == 1 ? 'success' : 'error';

        if ($actualResult === $scenario['expected']) {
            echo "✅ PASS\n";
            echo "Expected: {$scenario['expected']}, Got: {$actualResult}\n";
            echo "Message: {$responseData['message']}\n";
            $passedCount++;
        } else {
            echo "❌ FAIL\n";
            echo "Expected: {$scenario['expected']}, Got: {$actualResult}\n";
            echo "Message: {$responseData['message']}\n";
            $failedCount++;
        }

        if (isset($responseData['data']) && is_array($responseData['data'])) {
            echo "\n📊 Updated Data Preview:\n";
            echo "   Name: {$responseData['data']['name']}\n";
            echo "   Sex: {$responseData['data']['sex']}\n";
            echo "   DOB: {$responseData['data']['dob']}\n";
            if (!empty($responseData['data']['national_id_number'])) {
                echo "   National ID: {$responseData['data']['national_id_number']}\n";
            }
            if (!empty($responseData['data']['occupation'])) {
                echo "   Occupation: {$responseData['data']['occupation']}\n";
            }
        }

    } catch (\Exception $e) {
        echo "❌ EXCEPTION\n";
        echo "Error: {$e->getMessage()}\n";
        $failedCount++;
    }

    echo "\n";
}

// Summary
echo "===========================================\n";
echo "  TEST SUMMARY\n";
echo "===========================================\n";
echo "Total Tests: " . count($scenarios) . "\n";
echo "Passed: {$passedCount} ✅\n";
echo "Failed: {$failedCount} ❌\n";
echo "Success Rate: " . round(($passedCount / count($scenarios)) * 100, 2) . "%\n";
echo "===========================================\n";

if ($failedCount === 0) {
    echo "\n🎉 ALL TESTS PASSED! API is working perfectly!\n\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the errors above.\n\n";
}
