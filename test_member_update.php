<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "Testing Member Update\n";
echo "=====================\n\n";

$user = User::where('user_type', 'Customer')->first();

if (!$user) {
    echo "No customer users found!\n";
    exit;
}

echo "Found user: {$user->name} (ID: {$user->id})\n";
echo "Current phone: {$user->phone_number}\n";
echo "Current group_id: " . ($user->group_id ?? 'NULL') . "\n\n";

// Test 1: Simple update
echo "Test 1: Updating phone number\n";
$user->phone_number = '0788999888';
$result = $user->save();
echo "Save result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
$user->refresh();
echo "New phone: {$user->phone_number}\n\n";

// Test 2: Update with fillable
echo "Test 2: Updating using fill()\n";
$user->fill([
    'phone_number' => '0799888777',
    'group_id' => 1,
]);
$result = $user->save();
echo "Save result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
$user->refresh();
echo "New phone: {$user->phone_number}\n";
echo "New group_id: " . ($user->group_id ?? 'NULL') . "\n\n";

// Test 3: Update using update()
echo "Test 3: Updating using update()\n";
$result = $user->update([
    'phone_number' => '0766555444',
    'village' => 'Test Village Update',
]);
echo "Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
$user->refresh();
echo "New phone: {$user->phone_number}\n";
echo "New village: {$user->village}\n\n";

echo "All tests completed!\n";
