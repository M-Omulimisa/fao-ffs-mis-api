<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate actual mobile app request flow
$user = \App\Models\User::find(211);
auth()->setUser($user);

echo "=== Full getManifest test for user 211, group_id={$user->group_id} ===\n\n";

$controller = new \App\Http\Controllers\Api\VslaGroupManifestController();

// Test with the user's actual group_id
$groupId = $user->group_id;
echo "Calling getManifest({$groupId})...\n";

try {
    $response = $controller->getManifest($groupId);
    $status = $response->getStatusCode();
    echo "Status: {$status}\n";
    
    $data = json_decode($response->getContent(), true);
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . ($data['message'] ?? '') . "\n\n";
    
    if ($status == 200 && isset($data['data'])) {
        echo "Data keys: " . implode(', ', array_keys($data['data'])) . "\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Also test getCycles
echo "\n=== getCycles test ===\n";
try {
    $cycleController = new \App\Http\Controllers\Api\VslaConfigurationController();
    $response = $cycleController->getCycles();
    $status = $response->getStatusCode();
    echo "Status: {$status}\n";
    $data = json_decode($response->getContent(), true);
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . ($data['message'] ?? '') . "\n";
    if (isset($data['data']['cycles'])) {
        echo "Cycles count: " . count($data['data']['cycles']) . "\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test SocialFundTransaction model
echo "\n=== SocialFundTransaction check ===\n";
try {
    $exists = class_exists(\App\Models\SocialFundTransaction::class);
    echo "Class exists: " . ($exists ? 'yes' : 'NO') . "\n";
    if ($exists) {
        $hasMethod = method_exists(\App\Models\SocialFundTransaction::class, 'getGroupBalance');
        echo "getGroupBalance method: " . ($hasMethod ? 'yes' : 'NO') . "\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
