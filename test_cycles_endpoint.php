<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Http\Controllers\Api\VslaConfigurationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "\n========================================\n";
echo "TESTING CYCLES ENDPOINT FOR REGULAR MEMBER\n";
echo "========================================\n\n";

// Test with user 211 (regular member, group_id = 13)
$user = User::find(211);

if (!$user) {
    echo "❌ User 211 not found!\n";
    exit(1);
}

echo "📋 Test User:\n";
echo "   ID: {$user->id}\n";
echo "   Name: {$user->name}\n";
echo "   Group ID: {$user->group_id}\n";
echo "   User Type: {$user->user_type}\n\n";

// Authenticate the user
Auth::login($user);

// Create controller instance
$controller = new VslaConfigurationController();

// Create a mock request
$request = Request::create('/api/vsla/cycles', 'GET');

echo "🔄 Calling getCycles()...\n\n";

try {
    $response = $controller->getCycles();
    $data = $response->getData(true);
    
    if ($data['success']) {
        echo "✅ SUCCESS!\n\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📊 CYCLES DATA\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        if (isset($data['data'])) {
            $responseData = $data['data'];
            echo "Group: {$responseData['group_name']} (ID: {$responseData['group_id']})\n";
            echo "Total Cycles: {$responseData['total_cycles']}\n\n";
            
            if (isset($responseData['cycles']) && is_array($responseData['cycles'])) {
                foreach ($responseData['cycles'] as $cycle) {
                    echo "Cycle ID: {$cycle['id']}\n";
                    echo "Name: " . ($cycle['cycle_name'] ?? 'N/A') . "\n";
                    echo "Start Date: {$cycle['start_date']}\n";
                    echo "End Date: {$cycle['end_date']}\n";
                    echo "Is Active: " . ($cycle['is_active_cycle'] ? 'Yes' : 'No') . "\n";
                    echo "Status: {$cycle['status']}\n";
                    echo "Progress: {$cycle['progress_percentage']}%\n";
                    echo "───────────────────────────────────────\n";
                }
            }
            
            if (isset($responseData['active_cycle']) && $responseData['active_cycle']) {
                echo "\n🎯 Active Cycle: {$responseData['active_cycle']['cycle_name']} (ID: {$responseData['active_cycle']['id']})\n";
            } else {
                echo "\n⚠️  No active cycle found\n";
            }
        }
        
        echo "\n✅ TEST PASSED - Regular members can now access cycles!\n";
    } else {
        echo "❌ FAILED: {$data['message']}\n";
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ ALL TESTS PASSED\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
