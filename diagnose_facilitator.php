<?php
/**
 * Check facilitator scoping and what Lee Perkins (field_facilitator) would see
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\FfsGroup;
use App\Models\User;

echo "=== FIELD FACILITATOR SCOPING CHECK ===\n\n";

// Lee Perkins is user #220 (field_facilitator)
echo "--- Groups assigned to Lee Perkins (#220) as facilitator ---\n";
$groupsByFac = FfsGroup::where('facilitator_id', 220)->get();
echo "  Count: " . $groupsByFac->count() . "\n";
foreach ($groupsByFac as $g) {
    echo "  Group #{$g->id}: {$g->name} (type={$g->type}, ip_id={$g->ip_id})\n";
}

echo "\n--- Groups with facilitator_id = Cruz Wise (#219) ---\n";
$groupsByCruz = FfsGroup::where('facilitator_id', 219)->get();
echo "  Count: " . $groupsByCruz->count() . "\n";
foreach ($groupsByCruz as $g) {
    echo "  Group #{$g->id}: {$g->name} (type={$g->type}, ip_id={$g->ip_id})\n";
}

echo "\n--- All facilitator_id values in ffs_groups ---\n";
$facIds = DB::table('ffs_groups')
    ->select('facilitator_id', DB::raw('COUNT(*) as cnt'))
    ->groupBy('facilitator_id')
    ->orderBy('cnt', 'desc')
    ->get();
foreach ($facIds as $f) {
    $facName = $f->facilitator_id ? (User::find($f->facilitator_id)->name ?? '??') : 'NULL';
    echo "  facilitator_id=" . ($f->facilitator_id ?? 'NULL') . " ({$facName}): {$f->cnt} groups\n";
}

echo "\n--- Groups with ip_id=1 ---\n";
echo "  Total: " . FfsGroup::where('ip_id', 1)->count() . "\n";
echo "  With facilitator_id: " . FfsGroup::where('ip_id', 1)->whereNotNull('facilitator_id')->count() . "\n";
echo "  Without facilitator_id: " . FfsGroup::where('ip_id', 1)->whereNull('facilitator_id')->count() . "\n";

echo "\n--- What Cycles page would show for Lee Perkins ---\n";
// CycleController doesn't have field_facilitator scoping
// It just uses IP scoping via group
$ipId = 1;
$groupIds = FfsGroup::where('ip_id', $ipId)->pluck('id');
$cycles = \App\Models\Project::where('is_vsla_cycle', 'Yes')
    ->whereIn('group_id', $groupIds)
    ->count();
echo "  VSLA Cycles for ip_id={$ipId}: {$cycles}\n";

// But does field_facilitator scoping apply to cycles too?
$facGroupIds = FfsGroup::where('ip_id', $ipId)->where('facilitator_id', 220)->pluck('id');
$facCycles = \App\Models\Project::where('is_vsla_cycle', 'Yes')
    ->whereIn('group_id', $facGroupIds)
    ->count();
echo "  If also filtered by facilitator=220: {$facCycles}\n";

echo "\n--- What Members page would show for Lee Perkins ---\n";
// MemberController grid scoping
$totalMembers = User::where('ip_id', $ipId)->count();
echo "  Members with ip_id={$ipId}: {$totalMembers}\n";
$facMembers = User::where('ip_id', $ipId)
    ->whereIn('group_id', FfsGroup::where('facilitator_id', 220)->pluck('id'))
    ->count();
echo "  Members in groups facilitated by #220: {$facMembers}\n";

echo "\n--- Check MemberController for field_facilitator scoping ---\n";
// Let's see what the member controller does
$memberControllerFile = file_get_contents(__DIR__ . '/app/Admin/Controllers/MemberController.php');
if (preg_match('/field_facilitator|facilitator_id/', $memberControllerFile, $matches)) {
    echo "  MemberController HAS field_facilitator/facilitator_id references\n";
    
    // Find all lines with these references
    $lines = explode("\n", $memberControllerFile);
    foreach ($lines as $i => $line) {
        if (preg_match('/field_facilitator|facilitator_id/', $line)) {
            echo "  Line " . ($i+1) . ": " . trim($line) . "\n";
        }
    }
} else {
    echo "  MemberController has NO field_facilitator/facilitator_id references\n";
}

echo "\n=== DONE ===\n";
