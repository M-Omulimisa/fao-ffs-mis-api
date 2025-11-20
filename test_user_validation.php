<?php

/**
 * Test Script for User Name Splitting and Validation
 * Tests all scenarios to ensure zero errors
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "========================================\n";
echo "USER VALIDATION & NAME SPLITTING TESTS\n";
echo "========================================\n\n";

// Test counter
$testsPassed = 0;
$testsFailed = 0;

// Helper function to run tests
function runTest($testName, $testFunction) {
    global $testsPassed, $testsFailed;
    
    echo "Testing: $testName\n";
    echo str_repeat("-", 60) . "\n";
    
    try {
        $result = $testFunction();
        if ($result === true) {
            echo "✅ PASSED\n\n";
            $testsPassed++;
        } else {
            echo "❌ FAILED: $result\n\n";
            $testsFailed++;
        }
    } catch (Exception $e) {
        echo "❌ FAILED: " . $e->getMessage() . "\n\n";
        $testsFailed++;
    }
}

// Clean up function
function cleanupTestUser($email) {
    try {
        User::where('email', $email)->forceDelete();
    } catch (Exception $e) {
        // Ignore
    }
}

// ============================================
// TEST 1: Full Name Splitting (3 parts)
// ============================================
runTest("Full Name Splitting - 3 Parts (John Michael Doe)", function() {
    cleanupTestUser('test1@nametest.com');
    
    $user = new User();
    $user->name = "John Michael Doe";
    $user->email = "test1@nametest.com";
    $user->phone_number = "0791000001";
    $user->password = bcrypt('password');
    $user->user_type = "Customer";
    $user->save();
    
    $user->refresh();
    
    $firstName = $user->first_name;
    $lastName = $user->last_name;
    $fullName = $user->name;
    
    cleanupTestUser('test1@nametest.com');
    
    if ($firstName !== "John") {
        return "Expected first_name='John', got '$firstName'";
    }
    if ($lastName !== "Michael Doe") {
        return "Expected last_name='Michael Doe', got '$lastName'";
    }
    if ($fullName !== "John Michael Doe") {
        return "Expected name='John Michael Doe', got '$fullName'";
    }
    
    echo "  first_name: $firstName\n";
    echo "  last_name: $lastName\n";
    echo "  name: $fullName\n";
    
    return true;
});

// ============================================
// TEST 2: Full Name Splitting (2 parts)
// ============================================
runTest("Full Name Splitting - 2 Parts (Jane Smith)", function() {
    cleanupTestUser('test2@nametest.com');
    
    $user = new User();
    $user->name = "Jane Smith";
    $user->email = "test2@nametest.com";
    $user->phone_number = "0791000002";
    $user->password = bcrypt('password');
    $user->user_type = "Customer";
    $user->save();
    
    $user->refresh();
    
    $firstName = $user->first_name;
    $lastName = $user->last_name;
    
    cleanupTestUser('test2@nametest.com');
    
    if ($firstName !== "Jane") {
        return "Expected first_name='Jane', got '$firstName'";
    }
    if ($lastName !== "Smith") {
        return "Expected last_name='Smith', got '$lastName'";
    }
    
    echo "  first_name: $firstName\n";
    echo "  last_name: $lastName\n";
    
    return true;
});

// ============================================
// TEST 3: Single Name
// ============================================
runTest("Full Name Splitting - Single Name (Patrick)", function() {
    cleanupTestUser('test3@nametest.com');
    
    $user = new User();
    $user->name = "Patrick";
    $user->email = "test3@nametest.com";
    $user->phone_number = "0791000003";
    $user->password = bcrypt('password');
    $user->user_type = "Customer";
    $user->save();
    
    $user->refresh();
    
    $firstName = $user->first_name;
    $lastName = $user->last_name;
    
    cleanupTestUser('test3@nametest.com');
    
    if ($firstName !== "Patrick") {
        return "Expected first_name='Patrick', got '$firstName'";
    }
    if ($lastName !== "Patrick") {
        return "Expected last_name='Patrick', got '$lastName'";
    }
    
    echo "  first_name: $firstName\n";
    echo "  last_name: $lastName\n";
    
    return true;
});

// ============================================
// TEST 4: First + Last Name to Full Name
// ============================================
runTest("Reverse Generation - First + Last to Full Name", function() {
    cleanupTestUser('test4@nametest.com');
    
    $user = new User();
    $user->first_name = "Sarah";
    $user->last_name = "Williams";
    $user->email = "test4@nametest.com";
    $user->phone_number = "0791000004";
    $user->password = bcrypt('password');
    $user->user_type = "Customer";
    $user->save();
    
    $user->refresh();
    
    $fullName = $user->name;
    
    cleanupTestUser('test4@nametest.com');
    
    if ($fullName !== "Sarah Williams") {
        return "Expected name='Sarah Williams', got '$fullName'";
    }
    
    echo "  name (auto-generated): $fullName\n";
    
    return true;
});

// ============================================
// TEST 5: Multiple Spaces Handling
// ============================================
runTest("Multiple Spaces Handling", function() {
    cleanupTestUser('test5@nametest.com');
    
    $user = new User();
    $user->name = "  John   Michael    Doe  ";
    $user->email = "test5@nametest.com";
    $user->phone_number = "0791000005";
    $user->password = bcrypt('password');
    $user->user_type = "Customer";
    $user->save();
    
    $user->refresh();
    
    $firstName = $user->first_name;
    $lastName = $user->last_name;
    
    cleanupTestUser('test5@nametest.com');
    
    if ($firstName !== "John") {
        return "Expected first_name='John' (trimmed), got '$firstName'";
    }
    if ($lastName !== "Michael Doe") {
        return "Expected last_name='Michael Doe' (spaces normalized), got '$lastName'";
    }
    
    echo "  first_name: $firstName\n";
    echo "  last_name: $lastName\n";
    
    return true;
});

// ============================================
// TEST 6: Email Duplicate Prevention (Create)
// ============================================
runTest("Email Duplicate Prevention - Create", function() {
    cleanupTestUser('duplicate@test.com');
    
    // Create first user
    $user1 = new User();
    $user1->name = "First User";
    $user1->email = "duplicate@test.com";
    $user1->phone_number = "0791000006";
    $user1->password = bcrypt('password');
    $user1->user_type = "Customer";
    $user1->save();
    
    // Try to create second user with same email
    $errorCaught = false;
    $errorMessage = "";
    
    try {
        $user2 = new User();
        $user2->name = "Second User";
        $user2->email = "duplicate@test.com"; // Duplicate!
        $user2->phone_number = "0791000007";
        $user2->password = bcrypt('password');
        $user2->user_type = "Customer";
        $user2->save();
    } catch (Exception $e) {
        $errorCaught = true;
        $errorMessage = $e->getMessage();
    }
    
    cleanupTestUser('duplicate@test.com');
    
    if (!$errorCaught) {
        return "Expected exception to be thrown for duplicate email";
    }
    
    if (strpos($errorMessage, 'duplicate@test.com') === false) {
        return "Error message should contain the duplicate email";
    }
    
    echo "  Error correctly thrown: $errorMessage\n";
    
    return true;
});

// ============================================
// TEST 7: Phone Duplicate Prevention (Create)
// ============================================
runTest("Phone Duplicate Prevention - Create", function() {
    cleanupTestUser('phone1@test.com');
    cleanupTestUser('phone2@test.com');
    
    // Create first user
    $user1 = new User();
    $user1->name = "First User";
    $user1->email = "phone1@test.com";
    $user1->phone_number = "0791234567";
    $user1->password = bcrypt('password');
    $user1->user_type = "Customer";
    $user1->save();
    
    // Try to create second user with same phone
    $errorCaught = false;
    $errorMessage = "";
    
    try {
        $user2 = new User();
        $user2->name = "Second User";
        $user2->email = "phone2@test.com";
        $user2->phone_number = "0791234567"; // Duplicate!
        $user2->password = bcrypt('password');
        $user2->user_type = "Customer";
        $user2->save();
    } catch (Exception $e) {
        $errorCaught = true;
        $errorMessage = $e->getMessage();
    }
    
    cleanupTestUser('phone1@test.com');
    cleanupTestUser('phone2@test.com');
    
    if (!$errorCaught) {
        return "Expected exception to be thrown for duplicate phone";
    }
    
    if (strpos($errorMessage, '0791234567') === false) {
        return "Error message should contain the duplicate phone number";
    }
    
    echo "  Error correctly thrown: $errorMessage\n";
    
    return true;
});

// ============================================
// TEST 8: Update Same User (Should Work)
// ============================================
runTest("Update Same User - Keep Same Email/Phone", function() {
    cleanupTestUser('update@test.com');
    
    // Create user
    $user = new User();
    $user->name = "Original Name";
    $user->email = "update@test.com";
    $user->phone_number = "0791000008";
    $user->password = bcrypt('password');
    $user->user_type = "Customer";
    $user->save();
    
    $userId = $user->id;
    
    // Update user with same email/phone (should work)
    $user->name = "Updated Name";
    // email and phone_number stay the same
    $user->save();
    
    $user->refresh();
    
    cleanupTestUser('update@test.com');
    
    if ($user->first_name !== "Updated") {
        return "Expected first_name='Updated', got '{$user->first_name}'";
    }
    if ($user->last_name !== "Name") {
        return "Expected last_name='Name', got '{$user->last_name}'";
    }
    
    echo "  User updated successfully without duplicate errors\n";
    echo "  first_name: {$user->first_name}\n";
    echo "  last_name: {$user->last_name}\n";
    
    return true;
});

// ============================================
// TEST 9: Update to Duplicate Email (Should Fail)
// ============================================
runTest("Update to Duplicate Email - Should Fail", function() {
    cleanupTestUser('existing@test.com');
    cleanupTestUser('toupdate@test.com');
    
    // Create first user
    $user1 = new User();
    $user1->name = "Existing User";
    $user1->email = "existing@test.com";
    $user1->phone_number = "0791000009";
    $user1->password = bcrypt('password');
    $user1->user_type = "Customer";
    $user1->save();
    
    // Create second user
    $user2 = new User();
    $user2->name = "To Update User";
    $user2->email = "toupdate@test.com";
    $user2->phone_number = "0791000010";
    $user2->password = bcrypt('password');
    $user2->user_type = "Customer";
    $user2->save();
    
    // Try to update user2 with user1's email
    $errorCaught = false;
    $errorMessage = "";
    
    try {
        $user2->email = "existing@test.com"; // Duplicate!
        $user2->save();
    } catch (Exception $e) {
        $errorCaught = true;
        $errorMessage = $e->getMessage();
    }
    
    cleanupTestUser('existing@test.com');
    cleanupTestUser('toupdate@test.com');
    
    if (!$errorCaught) {
        return "Expected exception to be thrown when updating to duplicate email";
    }
    
    echo "  Error correctly thrown: $errorMessage\n";
    
    return true;
});

// ============================================
// TEST 10: Null Email and Phone (Should Work)
// ============================================
runTest("Null Email and Phone - Should Work", function() {
    // Create user with null email and phone
    $user = new User();
    $user->name = "No Contact User";
    $user->email = null;
    $user->phone_number = null;
    $user->password = bcrypt('password');
    $user->user_type = "Customer";
    $user->username = "nocontact" . time(); // Use username instead
    $user->save();
    
    $userId = $user->id;
    
    $user->refresh();
    
    // Clean up
    User::where('id', $userId)->forceDelete();
    
    if ($user->first_name !== "No") {
        return "Expected first_name='No', got '{$user->first_name}'";
    }
    
    echo "  User created successfully with null email/phone\n";
    echo "  first_name: {$user->first_name}\n";
    echo "  last_name: {$user->last_name}\n";
    
    return true;
});

// ============================================
// TEST 11: Secondary Phone Duplicate Prevention
// ============================================
runTest("Secondary Phone Duplicate Prevention", function() {
    cleanupTestUser('phone2test1@test.com');
    cleanupTestUser('phone2test2@test.com');
    
    // Create first user
    $user1 = new User();
    $user1->name = "First User";
    $user1->email = "phone2test1@test.com";
    $user1->phone_number = "0791111111";
    $user1->password = bcrypt('password');
    $user1->user_type = "Customer";
    $user1->save();
    
    // Try to create second user with phone_number_2 = user1's phone_number
    $errorCaught = false;
    $errorMessage = "";
    
    try {
        $user2 = new User();
        $user2->name = "Second User";
        $user2->email = "phone2test2@test.com";
        $user2->phone_number = "0792222222";
        $user2->phone_number_2 = "0791111111"; // Duplicate of user1's primary phone!
        $user2->password = bcrypt('password');
        $user2->user_type = "Customer";
        $user2->save();
    } catch (Exception $e) {
        $errorCaught = true;
        $errorMessage = $e->getMessage();
    }
    
    cleanupTestUser('phone2test1@test.com');
    cleanupTestUser('phone2test2@test.com');
    
    if (!$errorCaught) {
        return "Expected exception when phone_number_2 duplicates another user's phone_number";
    }
    
    echo "  Error correctly thrown: $errorMessage\n";
    
    return true;
});

// ============================================
// TEST 12: Name Update on Existing Record
// ============================================
runTest("Name Update - Should Re-split", function() {
    cleanupTestUser('nameupdate@test.com');
    
    // Create user
    $user = new User();
    $user->name = "John Doe";
    $user->email = "nameupdate@test.com";
    $user->phone_number = "0791000011";
    $user->password = bcrypt('password');
    $user->user_type = "Customer";
    $user->save();
    
    // Update name
    $user->name = "Michael Robert Johnson";
    $user->save();
    
    $user->refresh();
    
    cleanupTestUser('nameupdate@test.com');
    
    if ($user->first_name !== "Michael") {
        return "Expected first_name='Michael', got '{$user->first_name}'";
    }
    if ($user->last_name !== "Robert Johnson") {
        return "Expected last_name='Robert Johnson', got '{$user->last_name}'";
    }
    
    echo "  Name re-split correctly on update\n";
    echo "  first_name: {$user->first_name}\n";
    echo "  last_name: {$user->last_name}\n";
    
    return true;
});

// ============================================
// SUMMARY
// ============================================
echo "\n";
echo "========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "✅ Passed: $testsPassed\n";
echo "❌ Failed: $testsFailed\n";
echo "\n";

if ($testsFailed === 0) {
    echo "🎉 ALL TESTS PASSED! Implementation is PERFECT!\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the implementation.\n";
    exit(1);
}
