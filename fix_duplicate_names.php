<?php

/**
 * Fix Existing Users with Duplicate First/Last Names
 * This script updates users where first_name equals last_name
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
echo "FIX DUPLICATE FIRST/LAST NAMES\n";
echo "========================================\n\n";

// Find users where first_name = last_name (single name users)
$duplicateUsers = User::whereColumn('first_name', 'last_name')
    ->whereNotNull('first_name')
    ->whereNotNull('last_name')
    ->get();

echo "Found " . $duplicateUsers->count() . " users with duplicate first/last names\n\n";

$fixed = 0;
$skipped = 0;

foreach ($duplicateUsers as $user) {
    echo "User ID {$user->id}: {$user->first_name} {$user->last_name}\n";
    
    // Check if name field has more information
    if (!empty($user->name) && $user->name !== $user->first_name) {
        echo "  -> Has full name: {$user->name}\n";
        
        // Re-split the name
        $nameParts = explode(' ', preg_replace('/\s+/', ' ', trim($user->name)));
        
        if (count($nameParts) >= 2) {
            $firstName = array_shift($nameParts);
            $lastName = implode(' ', $nameParts);
            
            // Update without triggering boot events (to avoid validation)
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'updated_at' => now()
                ]);
            
            echo "  ✅ Fixed: first_name='$firstName', last_name='$lastName'\n";
            $fixed++;
        } else {
            echo "  ⚠️  Skipped: Name has only one part\n";
            $skipped++;
        }
    } else {
        echo "  ⚠️  Skipped: No additional name information available\n";
        $skipped++;
    }
    
    echo "\n";
}

echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total Found: " . $duplicateUsers->count() . "\n";
echo "✅ Fixed: $fixed\n";
echo "⚠️  Skipped: $skipped\n";
echo "\n";

if ($fixed > 0) {
    echo "🎉 Successfully fixed $fixed user(s)!\n";
} else {
    echo "ℹ️  No users needed fixing.\n";
}

echo "\n";
