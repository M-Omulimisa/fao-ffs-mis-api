<?php
/**
 * Test script for VSLA Manifest Sync Endpoints
 * Tests both full manifest and incremental updates
 */

// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Models\User;

// Color output helpers
function colorLog($message, $color = 'green') {
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'blue' => "\033[34m",
        'yellow' => "\033[33m",
        'reset' => "\033[0m"
    ];
    echo $colors[$color] . $message . $colors['reset'] . PHP_EOL;
}

function testHeader($title) {
    echo PHP_EOL;
    colorLog("═══════════════════════════════════════════════════════", 'blue');
    colorLog("  $title", 'blue');
    colorLog("═══════════════════════════════════════════════════════", 'blue');
}

function testResult($passed, $message) {
    if ($passed) {
        colorLog("✓ PASS: $message", 'green');
    } else {
        colorLog("✗ FAIL: $message", 'red');
    }
}

// Test configuration
$testGroupId = 1; // Change this to a valid group ID
$testUserId = 1;  // Change this to a valid user ID who is a member of the group

// Get test user and generate token
$user = User::find($testUserId);
if (!$user) {
    colorLog("Error: User with ID $testUserId not found", 'red');
    exit(1);
}

// Generate JWT token
try {
    $token = auth('api')->login($user);
    if (!$token) {
        colorLog("Error: Could not generate token", 'red');
        exit(1);
    }
    colorLog("✓ Generated JWT token for user: {$user->name}", 'green');
} catch (Exception $e) {
    colorLog("Error generating token: " . $e->getMessage(), 'red');
    exit(1);
}

// Test 1: Get Full Manifest
testHeader("TEST 1: Get Full Manifest");
try {
    $startTime = microtime(true);
    
    $request = Request::create(
        "/api/vsla/groups/$testGroupId/manifest",
        'GET'
    );
    $request->headers->set('Authorization', "Bearer $token");
    $request->headers->set('Accept', 'application/json');
    
    $response = $kernel->handle($request);
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    $data = json_decode($response->getContent(), true);
    
    testResult($response->getStatusCode() === 200, "Status code is 200");
    testResult(isset($data['code']) && $data['code'] == 1, "Response code is 1");
    testResult(isset($data['data']), "Response contains data");
    
    if (isset($data['data'])) {
        testResult(isset($data['data']['group_info']), "Contains group_info");
        testResult(isset($data['data']['cycle_info']), "Contains cycle_info");
        testResult(isset($data['data']['members_list']), "Contains members_list");
        testResult(isset($data['data']['recent_meetings']), "Contains recent_meetings");
        testResult(isset($data['data']['action_plans_data']), "Contains action_plans_data");
        testResult(isset($data['data']['dashboard']), "Contains dashboard");
        testResult(isset($data['data']['last_sync_time']), "Contains last_sync_time");
        
        if (isset($data['data']['group_info'])) {
            $groupInfo = $data['data']['group_info'];
            colorLog("  Group Name: " . ($groupInfo['name'] ?? 'N/A'), 'blue');
            colorLog("  Group Code: " . ($groupInfo['group_code'] ?? 'N/A'), 'blue');
        }
        
        if (isset($data['data']['members_list']['members'])) {
            $memberCount = count($data['data']['members_list']['members']);
            colorLog("  Members Count: $memberCount", 'blue');
        }
        
        if (isset($data['data']['recent_meetings'])) {
            $meetingCount = count($data['data']['recent_meetings']);
            colorLog("  Recent Meetings: $meetingCount", 'blue');
        }
    }
    
    colorLog("  Response Time: {$responseTime}ms", 'yellow');
    
    if ($response->getStatusCode() !== 200) {
        colorLog("Response Content:", 'red');
        echo $response->getContent() . PHP_EOL;
    }
    
} catch (Exception $e) {
    testResult(false, "Exception: " . $e->getMessage());
}

// Test 2: Get Incremental Updates (no last_sync)
testHeader("TEST 2: Get Incremental Updates (Without last_sync parameter)");
try {
    $startTime = microtime(true);
    
    $request = Request::create(
        "/api/vsla/groups/$testGroupId/manifest/incremental",
        'GET'
    );
    $request->headers->set('Authorization', "Bearer $token");
    $request->headers->set('Accept', 'application/json');
    
    $response = $kernel->handle($request);
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    $data = json_decode($response->getContent(), true);
    
    testResult($response->getStatusCode() === 200, "Status code is 200");
    testResult(isset($data['code']) && $data['code'] == 1, "Response code is 1");
    testResult(isset($data['data']), "Response contains data");
    
    if (isset($data['data'])) {
        testResult(isset($data['data']['has_updates']), "Contains has_updates flag");
        
        if (isset($data['data']['has_updates'])) {
            $hasUpdates = $data['data']['has_updates'];
            colorLog("  Has Updates: " . ($hasUpdates ? 'Yes' : 'No'), 'blue');
            
            if ($hasUpdates) {
                colorLog("  Updated Components:", 'blue');
                if (isset($data['data']['updated_components'])) {
                    foreach ($data['data']['updated_components'] as $component) {
                        colorLog("    - $component", 'blue');
                    }
                }
            }
        }
        
        if (isset($data['data']['data'])) {
            colorLog("  Data Keys: " . implode(', ', array_keys($data['data']['data'])), 'blue');
        }
    }
    
    colorLog("  Response Time: {$responseTime}ms", 'yellow');
    
} catch (Exception $e) {
    testResult(false, "Exception: " . $e->getMessage());
}

// Test 3: Get Incremental Updates (with last_sync)
testHeader("TEST 3: Get Incremental Updates (With last_sync parameter)");
try {
    // Use a time 1 hour ago
    $lastSync = date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    $startTime = microtime(true);
    
    $request = Request::create(
        "/api/vsla/groups/$testGroupId/manifest/incremental?last_sync=" . urlencode($lastSync),
        'GET'
    );
    $request->headers->set('Authorization', "Bearer $token");
    $request->headers->set('Accept', 'application/json');
    
    $response = $kernel->handle($request);
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    $data = json_decode($response->getContent(), true);
    
    testResult($response->getStatusCode() === 200, "Status code is 200");
    testResult(isset($data['code']) && $data['code'] == 1, "Response code is 1");
    testResult(isset($data['data']['has_updates']), "Response indicates update status");
    
    if (isset($data['data'])) {
        $hasUpdates = $data['data']['has_updates'] ?? false;
        colorLog("  Last Sync Time: $lastSync", 'blue');
        colorLog("  Has Updates: " . ($hasUpdates ? 'Yes' : 'No'), 'blue');
        
        if ($hasUpdates && isset($data['data']['updated_components'])) {
            colorLog("  Updated Components:", 'blue');
            foreach ($data['data']['updated_components'] as $component) {
                colorLog("    - $component", 'blue');
            }
        }
    }
    
    colorLog("  Response Time: {$responseTime}ms", 'yellow');
    
} catch (Exception $e) {
    testResult(false, "Exception: " . $e->getMessage());
}

// Test 4: Test with invalid group ID
testHeader("TEST 4: Test with Invalid Group ID");
try {
    $invalidGroupId = 99999;
    
    $request = Request::create(
        "/api/vsla/groups/$invalidGroupId/manifest",
        'GET'
    );
    $request->headers->set('Authorization', "Bearer $token");
    $request->headers->set('Accept', 'application/json');
    
    $response = $kernel->handle($request);
    $data = json_decode($response->getContent(), true);
    
    $isError = $response->getStatusCode() >= 400 || (isset($data['code']) && $data['code'] == 0);
    testResult($isError, "Returns error for invalid group ID");
    
    if (isset($data['message'])) {
        colorLog("  Error Message: " . $data['message'], 'yellow');
    }
    
} catch (Exception $e) {
    testResult(true, "Exception thrown for invalid group: " . $e->getMessage());
}

// Test 5: Test without authentication
testHeader("TEST 5: Test Without Authentication");
try {
    $request = Request::create(
        "/api/vsla/groups/$testGroupId/manifest",
        'GET'
    );
    $request->headers->set('Accept', 'application/json');
    
    $response = $kernel->handle($request);
    
    testResult($response->getStatusCode() === 401, "Returns 401 for unauthenticated request");
    
} catch (Exception $e) {
    testResult(false, "Exception: " . $e->getMessage());
}

// Final Summary
testHeader("TEST SUMMARY");
colorLog("All tests completed!", 'green');
colorLog("", 'reset');
colorLog("Next Steps:", 'yellow');
colorLog("1. Test sync from mobile app", 'yellow');
colorLog("2. Verify offline data persistence", 'yellow');
colorLog("3. Test incremental sync with real data changes", 'yellow');
colorLog("4. Verify sync performance with large datasets", 'yellow');

echo PHP_EOL;
