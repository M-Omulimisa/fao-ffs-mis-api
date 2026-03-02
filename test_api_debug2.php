<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(211);
\Illuminate\Support\Facades\Auth::login($user);

$group = \App\Models\FfsGroup::with(['admin','secretary','treasurer','district'])->find(134);

$controller = new \App\Http\Controllers\Api\VslaGroupManifestController();
$ref = new ReflectionClass($controller);

$tests = [
    'getGroupInfo',
    'getCurrentCycleInfo',
    'getMembersSummary',
    'getActiveLoans',
    'getSocialFundBalance',
    'getActionPlans',
    'getDashboardData',
    'getReminders',
];

foreach ($tests as $method) {
    echo "Testing $method... ";
    try {
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        $result = $m->invoke($controller, $group);
        echo "OK\n";
    } catch (\Throwable $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        echo "  File: " . basename($e->getFile()) . ":" . $e->getLine() . "\n";
    }
}

echo "\nTesting getRecentMeetings... ";
try {
    $m = $ref->getMethod('getRecentMeetings');
    $m->setAccessible(true);
    $result = $m->invoke($controller, $group, 10);
    echo "OK\n";
} catch (\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    echo "  File: " . basename($e->getFile()) . ":" . $e->getLine() . "\n";
}
