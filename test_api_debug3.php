<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(211);
\Illuminate\Support\Facades\Auth::login($user);

$controller = new \App\Http\Controllers\Api\VslaGroupManifestController();

// Test 1: Call getManifest with group_id directly
echo "=== Test 1: getManifest(134) ===\n";
try {
    $response = $controller->getManifest(134);
    echo "Status: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    echo "Success: " . ($data["success"] ?? "n/a") . "\n";
    if (isset($data["message"])) echo "Message: " . $data["message"] . "\n";
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 2: Call via HTTP simulation
echo "\n=== Test 2: HTTP simulation ===\n";
try {
    $request = \Illuminate\Http\Request::create("/api/groups/134/manifest", "GET");
    $request->headers->set('Accept', 'application/json');
    app()->instance('request', $request);
    
    $response = app()->make(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    $content = $response->getContent();
    $data = json_decode($content, true);
    if ($data) {
        echo "Success: " . ($data["success"] ?? "n/a") . "\n";
        if (isset($data["message"])) echo "Message: " . $data["message"] . "\n";
    } else {
        echo "Raw: " . substr($content, 0, 500) . "\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
