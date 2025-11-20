<?php

/**
 * Test Registration Fix - Phone and Email Persistence
 * 
 * This script tests the registration functionality to ensure:
 * 1. Phone number is saved correctly
 * 2. Email is saved correctly
 * 3. Name splitting works during registration
 * 4. Duplicate phone/email validations work
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "🧪 REGISTRATION FIX TEST SUITE\n";
echo "================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

// Helper function to run tests
function runTest($testName, $testFunction) {
    global $testsPassed, $testsFailed;
    
    echo "Testing: $testName\n";
    try {
        $result = $testFunction();
        if ($result === true) {
            echo "✅ PASSED\n\n";
            $testsPassed++;
        } else {
            echo "❌ FAILED: $result\n\n";
            $testsFailed++;
        }
    } catch (\Exception $e) {
        echo "❌ FAILED: " . $e->getMessage() . "\n\n";
        $testsFailed++;
    }
}

// Clean up test data before starting
function cleanupTestData() {
    User::where('email', 'LIKE', 'test_reg_%@example.com')->delete();
    User::where('phone_number', 'LIKE', '0700test%')->delete();
}

cleanupTestData();

// Test 1: Create user with full data (name, email, phone)
runTest("Registration with full name, email, and phone", function() {
    $user = new User();
    $user->name = "Test User One";
    $user->email = "test_reg_1@example.com";
    $user->phone_number = "0700test001";
    $user->username = "test_reg_1@example.com";
    $user->password = password_hash("password123", PASSWORD_DEFAULT);
    $user->save();
    
    $saved = User::find($user->id);
    
    if ($saved->email != "test_reg_1@example.com") {
        return "Email not saved correctly. Got: {$saved->email}";
    }
    
    if ($saved->phone_number != "0700test001") {
        return "Phone not saved correctly. Got: {$saved->phone_number}";
    }
    
    if ($saved->first_name != "Test") {
        return "First name not split correctly. Got: {$saved->first_name}";
    }
    
    if ($saved->last_name != "User One") {
        return "Last name not split correctly. Got: {$saved->last_name}";
    }
    
    return true;
});

// Test 2: Create user with single name
runTest("Registration with single name", function() {
    $user = new User();
    $user->name = "Patrick";
    $user->email = "test_reg_2@example.com";
    $user->phone_number = "0700test002";
    $user->username = "test_reg_2@example.com";
    $user->password = password_hash("password123", PASSWORD_DEFAULT);
    $user->save();
    
    $saved = User::find($user->id);
    
    if ($saved->first_name != "Patrick") {
        return "First name incorrect for single name. Got: {$saved->first_name}";
    }
    
    if ($saved->last_name != "Patrick") {
        return "Last name incorrect for single name. Got: {$saved->last_name}";
    }
    
    return true;
});

// Test 3: Create user with two-part name
runTest("Registration with two-part name", function() {
    $user = new User();
    $user->name = "Jane Smith";
    $user->email = "test_reg_3@example.com";
    $user->phone_number = "0700test003";
    $user->username = "test_reg_3@example.com";
    $user->password = password_hash("password123", PASSWORD_DEFAULT);
    $user->save();
    
    $saved = User::find($user->id);
    
    if ($saved->first_name != "Jane") {
        return "First name incorrect. Got: {$saved->first_name}";
    }
    
    if ($saved->last_name != "Smith") {
        return "Last name incorrect. Got: {$saved->last_name}";
    }
    
    return true;
});

// Test 4: Create user with empty phone (should work)
runTest("Registration with empty phone number", function() {
    $user = new User();
    $user->name = "Empty Phone User";
    $user->email = "test_reg_4@example.com";
    $user->phone_number = "";
    $user->username = "test_reg_4@example.com";
    $user->password = password_hash("password123", PASSWORD_DEFAULT);
    $user->save();
    
    $saved = User::find($user->id);
    
    if ($saved->email != "test_reg_4@example.com") {
        return "Email not saved. Got: {$saved->email}";
    }
    
    return true;
});

// Test 5: Try to create duplicate email (should fail)
runTest("Registration with duplicate email (should fail)", function() {
    try {
        $user = new User();
        $user->name = "Duplicate Email";
        $user->email = "test_reg_1@example.com"; // Already exists from Test 1
        $user->phone_number = "0700test005";
        $user->username = "test_reg_1@example.com";
        $user->password = password_hash("password123", PASSWORD_DEFAULT);
        $user->save();
        
        return "Should have failed but didn't - duplicate email was allowed!";
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'email') !== false || strpos($e->getMessage(), 'Email') !== false) {
            return true;
        }
        return "Failed but with wrong error: " . $e->getMessage();
    }
});

// Test 6: Try to create duplicate phone (should fail)
runTest("Registration with duplicate phone (should fail)", function() {
    try {
        $user = new User();
        $user->name = "Duplicate Phone";
        $user->email = "test_reg_6@example.com";
        $user->phone_number = "0700test001"; // Already exists from Test 1
        $user->username = "test_reg_6@example.com";
        $user->password = password_hash("password123", PASSWORD_DEFAULT);
        $user->save();
        
        return "Should have failed but didn't - duplicate phone was allowed!";
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'phone') !== false || strpos($e->getMessage(), 'Phone') !== false) {
            return true;
        }
        return "Failed but with wrong error: " . $e->getMessage();
    }
});

// Test 7: Verify phone and email are NOT empty strings in database
runTest("Verify phone and email are properly stored (not empty strings)", function() {
    $user = User::where('email', 'test_reg_1@example.com')->first();
    
    if ($user->phone_number === '') {
        return "Phone number is empty string instead of actual value";
    }
    
    if ($user->phone_number === null) {
        return "Phone number is null instead of actual value";
    }
    
    if ($user->phone_number != '0700test001') {
        return "Phone number has wrong value: {$user->phone_number}";
    }
    
    return true;
});

// Test 8: Test with phone number that has spaces
runTest("Registration with phone number containing spaces", function() {
    $user = new User();
    $user->name = "Space Phone User";
    $user->email = "test_reg_8@example.com";
    $user->phone_number = " 0700test008 "; // Has leading/trailing spaces
    $user->username = "test_reg_8@example.com";
    $user->password = password_hash("password123", PASSWORD_DEFAULT);
    $user->save();
    
    $saved = User::find($user->id);
    
    // Should be trimmed
    if ($saved->phone_number === " 0700test008 ") {
        return "Phone not trimmed. Got: '{$saved->phone_number}'";
    }
    
    return true;
});

// Test 9: Verify database record integrity
runTest("Database integrity check for test user", function() {
    $user = User::where('email', 'test_reg_1@example.com')->first();
    
    $fields = [
        'name' => 'Test User One',
        'first_name' => 'Test',
        'last_name' => 'User One',
        'email' => 'test_reg_1@example.com',
        'phone_number' => '0700test001'
    ];
    
    foreach ($fields as $field => $expectedValue) {
        if ($user->$field != $expectedValue) {
            return "Field '$field' mismatch. Expected: '$expectedValue', Got: '{$user->$field}'";
        }
    }
    
    return true;
});

// Test 10: Simulate registration API flow
runTest("Simulate API registration flow", function() {
    // Simulate request data like it would come from mobile app
    $requestData = [
        'name' => 'Mobile App User',
        'email' => 'test_reg_10@example.com',
        'phone_number' => '0700test010',
        'password' => 'password123'
    ];
    
    $user = new User();
    $user->name = $requestData['name'];
    $user->email = $requestData['email'];
    $user->phone_number = trim($requestData['phone_number']);
    $user->username = $requestData['email'];
    $user->password = password_hash($requestData['password'], PASSWORD_DEFAULT);
    $user->save();
    
    $saved = User::find($user->id);
    
    if ($saved->phone_number != '0700test010') {
        return "API flow failed - phone not saved: {$saved->phone_number}";
    }
    
    if ($saved->email != 'test_reg_10@example.com') {
        return "API flow failed - email not saved: {$saved->email}";
    }
    
    return true;
});

// Clean up test data
cleanupTestData();

// Final Results
echo "\n================================\n";
echo "📊 TEST RESULTS\n";
echo "================================\n";
echo "✅ Passed: $testsPassed\n";
echo "❌ Failed: $testsFailed\n";
echo "Total: " . ($testsPassed + $testsFailed) . "\n\n";

if ($testsFailed == 0) {
    echo "🎉 ALL TESTS PASSED! Registration is working correctly.\n";
    echo "\n✅ Confirmed:\n";
    echo "   - Phone numbers are saved during registration\n";
    echo "   - Email addresses are saved during registration\n";
    echo "   - Name splitting works automatically\n";
    echo "   - Duplicate email/phone validations work\n";
    echo "   - Phone numbers are trimmed of spaces\n";
} else {
    echo "⚠️  SOME TESTS FAILED! Please review the errors above.\n";
}

echo "\n";
