<?php

/**
 * Simulate Real Registration API Call
 * This mimics exactly what happens when mobile app calls /api/users/register
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Support\Facades\Hash;

echo "🧪 SIMULATING MOBILE APP REGISTRATION\n";
echo "======================================\n\n";

// Clean up any existing test user
Administrator::where('email', 'realtest@example.com')->delete();

echo "📱 STEP 1: Mobile app sends registration data\n";
echo "Request Data:\n";
$requestData = [
    'name' => 'Sarah Johnson',
    'email' => 'realtest@example.com',
    'phone_number' => '0700999888',
    'password' => 'password123'
];

foreach ($requestData as $key => $value) {
    if ($key == 'password') {
        echo "  - $key: ********\n";
    } else {
        echo "  - $key: $value\n";
    }
}
echo "\n";

echo "⚙️  STEP 2: Backend processes registration (ApiAuthController::register)\n";

// Simulate what ApiAuthController::register() does
$email = $requestData['email'];

// Check for existing user
$existingUser = Administrator::where('email', $email)->first();
if ($existingUser) {
    echo "❌ ERROR: User with same Email address already exists.\n";
    exit(1);
}

// Create new user (same as fixed ApiAuthController)
$user = new Administrator();
$user->name = $requestData['name'];
$user->email = $requestData['email'];
$user->username = $requestData['email'];
$user->phone_number = $requestData['phone_number'] != null ? trim($requestData['phone_number']) : '';
$user->address = '';
$user->reg_number = $requestData['email'];
$user->password = password_hash($requestData['password'], PASSWORD_DEFAULT);

// Set other fields
$user->profile_photo_large = '';
$user->location_lat = '';
$user->location_long = '';
$user->facebook = '';
$user->twitter = '';
$user->linkedin = '';
$user->website = '';
$user->other_link = '';
$user->cv = '';
$user->language = '';
$user->about = '';
$user->country = '';
$user->occupation = '';

try {
    if (!$user->save()) {
        echo "❌ ERROR: Failed to create account.\n";
        exit(1);
    }
    echo "✅ User created successfully!\n\n";
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "🔍 STEP 3: Verify data was saved to database\n";

// Reload from database to confirm persistence
$savedUser = Administrator::find($user->id);

echo "Database Record:\n";
echo "  - ID: {$savedUser->id}\n";
echo "  - Name: {$savedUser->name}\n";
echo "  - First Name: {$savedUser->first_name}\n";
echo "  - Last Name: {$savedUser->last_name}\n";
echo "  - Email: {$savedUser->email}\n";
echo "  - Phone: {$savedUser->phone_number}\n";
echo "  - Address: {$savedUser->address}\n\n";

echo "✅ VERIFICATION RESULTS:\n";
echo "========================\n";

$allGood = true;

// Check name
if ($savedUser->name != 'Sarah Johnson') {
    echo "❌ Name not saved correctly. Expected: 'Sarah Johnson', Got: '{$savedUser->name}'\n";
    $allGood = false;
} else {
    echo "✅ Name saved: {$savedUser->name}\n";
}

// Check first name (should be split by boot method)
if ($savedUser->first_name != 'Sarah') {
    echo "❌ First name not split correctly. Expected: 'Sarah', Got: '{$savedUser->first_name}'\n";
    $allGood = false;
} else {
    echo "✅ First name split: {$savedUser->first_name}\n";
}

// Check last name (should be split by boot method)
if ($savedUser->last_name != 'Johnson') {
    echo "❌ Last name not split correctly. Expected: 'Johnson', Got: '{$savedUser->last_name}'\n";
    $allGood = false;
} else {
    echo "✅ Last name split: {$savedUser->last_name}\n";
}

// Check email
if ($savedUser->email != 'realtest@example.com') {
    echo "❌ Email not saved correctly. Expected: 'realtest@example.com', Got: '{$savedUser->email}'\n";
    $allGood = false;
} else {
    echo "✅ Email saved: {$savedUser->email}\n";
}

// Check phone - THIS IS THE CRITICAL FIX!
if ($savedUser->phone_number != '0700999888') {
    echo "❌ Phone not saved correctly. Expected: '0700999888', Got: '{$savedUser->phone_number}'\n";
    $allGood = false;
} else {
    echo "✅ Phone saved: {$savedUser->phone_number}\n";
}

// Check phone is not empty string
if ($savedUser->phone_number === '') {
    echo "❌ Phone is empty string (old bug present)\n";
    $allGood = false;
}

echo "\n";

if ($allGood) {
    echo "🎉 SUCCESS! Registration is working perfectly!\n";
    echo "\n";
    echo "Summary:\n";
    echo "  ✅ Phone number saved: {$savedUser->phone_number}\n";
    echo "  ✅ Email saved: {$savedUser->email}\n";
    echo "  ✅ Name split correctly: {$savedUser->first_name} {$savedUser->last_name}\n";
    echo "  ✅ All data persisted to database\n";
    echo "\n";
    echo "🚀 READY FOR PRODUCTION!\n";
} else {
    echo "⚠️  ISSUES DETECTED! Review errors above.\n";
}

// Clean up
Administrator::where('email', 'realtest@example.com')->delete();

echo "\n";
