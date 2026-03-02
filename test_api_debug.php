<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(211);
\Illuminate\Support\Facades\Auth::login($user);

echo "=== TEST 1: getCycles ===\n";
$controller = new \App\Http\Controllers\Api\VslaConfigurationController();
try {
    $response = $controller->getCycles();
    echo "Status: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    echo "Success: " . ($data["success"] ?? "n/a") . "\n";
    if (isset($data["message"])) echo "Message: " . $data["message"] . "\n";
    if (isset($data["data"])) {
        if (is_array($data["data"])) {
            echo "Data count: " . count($data["data"]) . "\n";
        } else {
            echo "Data: " . substr(json_encode($data["data"]), 0, 200) . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . substr($e->getTraceAsString(), 0, 500) . "\n";
}

echo "\n=== TEST 2: Group Manifest (full sync) ===\n";
$group = \App\Models\FfsGroup::where('admin_id', $user->id)
    ->orWhere('secretary_id', $user->id)
    ->orWhere('treasurer_id', $user->id)
    ->first();
if (!$group && $user->group_id) {
    $group = \App\Models\FfsGroup::find($user->group_id);
}
echo "Group: " . ($group ? $group->id . " - " . $group->name : "NONE") . "\n";

if ($group) {
    try {
        $manifestController = new \App\Http\Controllers\Api\VslaGroupManifestController();
        $request = \Illuminate\Http\Request::create("/api/groups/{$group->id}/manifest", "GET");
        app()->instance('request', $request);
        $response = $manifestController->getManifest($request, $group->id);
        echo "Status: " . $response->getStatusCode() . "\n";
        $data = json_decode($response->getContent(), true);
        echo "Success: " . ($data["success"] ?? "n/a") . "\n";
        if (isset($data["message"])) echo "Message: " . $data["message"] . "\n";
        if (isset($data["data"])) {
            echo "Data keys: " . implode(", ", array_keys($data["data"])) . "\n";
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "Trace: " . substr($e->getTraceAsString(), 0, 500) . "\n";
    }
}
