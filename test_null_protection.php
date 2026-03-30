<?php
// Run: php test_null_protection.php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "==========================================\n";
echo "  FULL NULL PROTECTION AUDIT TEST\n";
echo "==========================================\n";

$user = User::find(228);
$origName = $user->getRawOriginal('name');
$origEmail = $user->getRawOriginal('email');
$origFirst = $user->getRawOriginal('first_name');
$origLast = $user->getRawOriginal('last_name');

echo "User: {$user->name} | Email: {$user->email}\n";
echo "First: {$user->first_name} | Last: {$user->last_name}\n\n";

$pass = 0;
$fail = 0;

function check($label, $actual, $expected) {
    global $pass, $fail;
    $ok = ($actual === $expected);
    echo ($ok ? "[PASS]" : "[FAIL]") . " {$label}: got [{$actual}] expected [{$expected}]\n";
    $ok ? $pass++ : $fail++;
}

// ── Layer 1: Mutator tests ──
echo "--- LAYER 1: MUTATOR PROTECTION ---\n";

$user->name = null;
check("1. name=null blocked", $user->getRawOriginal('name'), $origName);

$user->name = '';
check("2. name='' blocked", $user->getRawOriginal('name'), $origName);

$user->first_name = null;
check("3. first_name=null blocked", $user->getRawOriginal('first_name'), $origFirst);

$user->last_name = null;
check("4. last_name=null blocked", $user->getRawOriginal('last_name'), $origLast);

$user->email = null;
check("5. email=null blocked", $user->getRawOriginal('email'), $origEmail);

$user->email = '';
check("6. email='' blocked", $user->getRawOriginal('email'), $origEmail);

// Valid changes
$user->name = 'Test Person';
check("7. valid name accepted", $user->name, 'Test Person');
$user = User::find(228); // reload

$user->email = 'new@test.com';
check("8. valid email accepted", $user->email, 'new@test.com');
$user = User::find(228); // reload

// ── Layer 2: Boot event tests (save) ──
echo "\n--- LAYER 2: BOOT EVENT PROTECTION (save) ---\n";

DB::beginTransaction();
$fresh = User::find(228);
$fresh->email = null;
$fresh->address = 'test'; // make it dirty to trigger save
$fresh->save();
$reload = User::find(228);
check("9. email=null blocked on save()", $reload->getRawOriginal('email'), $origEmail);
DB::rollBack();

DB::beginTransaction();
$fresh = User::find(228);
$fresh->name = null;
$fresh->address = 'test';
$fresh->save();
$reload = User::find(228);
check("10. name=null blocked on save()", $reload->getRawOriginal('name'), $origName);
DB::rollBack();

DB::beginTransaction();
$fresh = User::find(228);
$fresh->first_name = null;
$fresh->last_name = null;
$fresh->address = 'test';
$fresh->save();
$reload = User::find(228);
check("11. first_name=null blocked on save()", $reload->getRawOriginal('first_name'), $origFirst);
check("12. last_name=null blocked on save()", $reload->getRawOriginal('last_name'), $origLast);
DB::rollBack();

// ── Layer 3: saveQuietly() bypass test ──
echo "\n--- LAYER 3: saveQuietly() BYPASS TEST ---\n";

DB::beginTransaction();
$fresh = User::find(228);
$fresh->email = null;
$fresh->saveQuietly();
$reload = User::find(228);
check("13. email=null blocked on saveQuietly()", $reload->getRawOriginal('email'), $origEmail);
DB::rollBack();

DB::beginTransaction();
$fresh = User::find(228);
$fresh->name = null;
$fresh->saveQuietly();
$reload = User::find(228);
check("14. name=null blocked on saveQuietly()", $reload->getRawOriginal('name'), $origName);
DB::rollBack();

// ── Layer 4: handleNameSplitting doesn't wipe ──
echo "\n--- LAYER 4: handleNameSplitting SAFETY ---\n";

DB::beginTransaction();
$fresh = User::find(228);
$fresh->first_name = null;
$fresh->last_name = null;
$fresh->save();
$reload = User::find(228);
$nameOk = !empty($reload->getRawOriginal('name'));
$firstOk = !empty($reload->getRawOriginal('first_name'));
$lastOk = !empty($reload->getRawOriginal('last_name'));
check("15. name survived handleNameSplitting", $nameOk ? 'kept' : 'WIPED', 'kept');
check("16. first_name survived", $firstOk ? 'kept' : 'WIPED', 'kept');
check("17. last_name survived", $lastOk ? 'kept' : 'WIPED', 'kept');
DB::rollBack();

// ── Summary ──
echo "\n==========================================\n";
echo "  RESULTS: {$pass} passed, {$fail} failed\n";
echo "==========================================\n";

if ($fail > 0) {
    echo "** WARNING: SOME PROTECTIONS ARE NOT WORKING **\n";
    exit(1);
}
echo "ALL PROTECTIONS VERIFIED.\n";
