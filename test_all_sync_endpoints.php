<?php
/**
 * Comprehensive test: Simulate FULL mobile sync flow
 * Tests every API endpoint called during OfflineSyncScreen._syncAll()
 */
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Bootstrap the application by handling a dummy request
$kernel->handle($dummyRequest = Illuminate\Http\Request::capture());

use Illuminate\Http\Request;
use App\Models\User;

// Setup auth
$user = User::find(211);
if (!$user) { echo "User 211 not found!\n"; exit(1); }
auth()->setUser($user);
echo "✅ Authenticated as user {$user->id} ({$user->first_name} {$user->last_name})\n";
echo "   group_id: {$user->group_id}\n\n";

$groupId = $user->group_id;
$results = [];

function testEndpoint($name, $path, $method = 'GET', $params = []) {
    global $results;
    try {
        $request = Request::create('/api/' . $path, $method, $params);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('User-Id', auth()->id());
        
        // Route the request
        $response = app()->handle($request);
        $status = $response->getStatusCode();
        $body = json_decode($response->getContent(), true);
        
        $icon = $status === 200 ? '✅' : '❌';
        $msg = $body['message'] ?? ($body['error'] ?? 'no message');
        echo "{$icon} [{$status}] {$name}: {$msg}\n";
        
        if ($status !== 200) {
            echo "   Response: " . substr($response->getContent(), 0, 500) . "\n";
        }
        
        $results[$name] = ['status' => $status, 'message' => $msg];
        return $body;
    } catch (\Throwable $e) {
        echo "💥 EXCEPTION {$name}: {$e->getMessage()}\n";
        echo "   File: {$e->getFile()}:{$e->getLine()}\n";
        echo "   Trace: " . substr($e->getTraceAsString(), 0, 500) . "\n";
        $results[$name] = ['status' => 'EXCEPTION', 'message' => $e->getMessage()];
        return null;
    }
}

echo "=== SYNC MODULE: manifest (full sync) ===\n";
testEndpoint('getManifest', "vsla/groups/{$groupId}/manifest");

echo "\n=== SYNC MODULE: cycles ===\n";
testEndpoint('getCycles', 'vsla/cycles');

echo "\n=== SYNC MODULE: dashboard ===\n";
testEndpoint('getDashboard', 'vsla/dashboard', 'GET', ['group_id' => $groupId]);
testEndpoint('getDashboardSummary', 'vsla/transactions/dashboard-summary', 'GET', ['group_id' => $groupId]);

echo "\n=== SYNC MODULE: transactions ===\n";
testEndpoint('getMemberStatement', 'vsla/transactions/member-statement', 'GET', ['user_id' => 211, 'project_id' => 1]);
testEndpoint('getMemberBalance', 'vsla/transactions/member-balance/211');
testEndpoint('getRecentTransactions', 'vsla/transactions/recent', 'GET', ['project_id' => 1, 'group_id' => $groupId]);
testEndpoint('getGroupStatement', 'vsla/transactions/group-statement', 'GET', ['group_id' => $groupId]);

echo "\n=== SYNC MODULE: loans ===\n";
testEndpoint('getLoans', 'vsla/loans', 'GET', ['project_id' => 1]);

echo "\n=== SYNC MODULE: meetings ===\n";
testEndpoint('getMeetings', 'vsla-meetings');
testEndpoint('getMeetingStats', 'vsla-meetings/stats');

echo "\n=== SYNC MODULE: social_fund ===\n";
testEndpoint('getSocialFundBalance', 'social-fund/balance', 'GET', ['group_id' => $groupId]);
testEndpoint('getSocialFundTransactions', 'social-fund/transactions', 'GET', ['group_id' => $groupId]);

echo "\n=== SYNC MODULE: group_info ===\n";
testEndpoint('getGroupInfo', "vsla/groups/{$groupId}");

echo "\n=== SYNC MODULE: shareouts ===\n";
testEndpoint('getShareoutHistory', 'vsla/shareouts/history');

echo "\n=== SYNC MODULE: savings ===\n";
testEndpoint('getGroupSavings', 'vsla/transactions/group-savings', 'GET', ['group_id' => $groupId]);

echo "\n=== SYNC MODULE: action_plans (via manifest) ===\n";
// Already tested above with getManifest

echo "\n=== SYNC MODULE: attendance ===\n";
testEndpoint('getAttendance', 'vsla/attendance');
testEndpoint('getAttendanceStats', 'vsla/attendance/stats');

echo "\n=== SYNC MODULE: account_balance ===\n";
testEndpoint('getAccountTransactions', 'account-transactions', 'GET', ['user_id' => 211]);

echo "\n=== SYNC MODULE: advisory ===\n";
testEndpoint('getAdvisoryCategories', 'advisory/categories');
testEndpoint('getAdvisoryPosts', 'advisory/posts');
testEndpoint('getFeaturedPosts', 'advisory/posts/featured');
testEndpoint('getQuestions', 'advisory/questions');
testEndpoint('getMyQuestions', 'advisory/questions/my/list');

echo "\n=== SYNC MODULE: market_prices ===\n";
testEndpoint('getMarketCategories', 'market-price-categories');
testEndpoint('getMarketProducts', 'market-price-products');
testEndpoint('getMarketPricesLatest', 'market-prices-latest');

echo "\n=== SYNC MODULE: group_report ===\n";
// Get active cycle id first
$cyclesResult = testEndpoint('getCycles2', 'vsla/cycles');
if ($cyclesResult && isset($cyclesResult['data']['active_cycle']['id'])) {
    $cycleId = $cyclesResult['data']['active_cycle']['id'];
    echo "   Active cycle ID: {$cycleId}\n";
}

echo "\n=== SYNC MODULE: group_stats ===\n";
testEndpoint('getGroupStats', 'vsla-groups/stats');

echo "\n=== SYNC MODULE: group_members ===\n";
testEndpoint('getGroupMembers', 'vsla/group-members');

echo "\n=== SYNC MODULE: members ===\n";
testEndpoint('getMembers', 'members');

echo "\n=== OTHER COMMON ENDPOINTS ===\n";
testEndpoint('getUserProfile', 'users/me');
testEndpoint('getAppConfig', 'app-config');

echo "\n\n========== SUMMARY ==========\n";
$passed = 0;
$failed = 0;
$errors = [];
foreach ($results as $name => $r) {
    if ($r['status'] === 200) {
        $passed++;
    } else {
        $failed++;
        $errors[] = "{$name}: [{$r['status']}] {$r['message']}";
    }
}
echo "Passed: {$passed}, Failed: {$failed}\n";
if (!empty($errors)) {
    echo "\nFailing endpoints:\n";
    foreach ($errors as $e) {
        echo "  ❌ {$e}\n";
    }
}
