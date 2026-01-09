<?php
/**
 * Add Test Data for Shareout Module
 * Creates member share purchases for testing cycle 7 shareout
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\User;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();
    
    // Get cycle 7
    $cycle = Project::find(7);
    if (!$cycle) {
        die("❌ Cycle 7 not found!\n");
    }
    
    echo "✅ Found Cycle: {$cycle->name} (ID: {$cycle->id})\n";
    echo "   Group ID: {$cycle->group_id}\n";
    echo "   Share Value: {$cycle->share_value}\n\n";
    
    // Get members from the same group
    $members = User::where('group_id', $cycle->group_id)
        ->whereIn('user_type', ['farmer', 'Customer'])
        ->limit(5)
        ->get();
    
    if ($members->isEmpty()) {
        die("❌ No members found in group {$cycle->group_id}!\n");
    }
    
    echo "✅ Found {$members->count()} members in group {$cycle->group_id}\n\n";
    
    // Add share purchases for each member
    $shareValue = $cycle->share_value ?? 1000;
    $createdShares = 0;
    
    foreach ($members as $index => $member) {
        // Random number of shares (5-20)
        $numberOfShares = rand(5, 20);
        $totalPaid = $numberOfShares * $shareValue;
        
        // Check if share record already exists
        $existingShare = ProjectShare::where('project_id', $cycle->id)
            ->where('investor_id', $member->id)
            ->first();
        
        if ($existingShare) {
            echo "   ⏭️  Member #{$member->id} ({$member->name}) already has shares\n";
            continue;
        }
        
        // Create share purchase record
        $share = ProjectShare::create([
            'project_id' => $cycle->id,
            'investor_id' => $member->id,
            'number_of_shares' => $numberOfShares,
            'share_price_at_purchase' => $shareValue,
            'total_amount_paid' => $totalPaid,
            'purchase_date' => now()->subDays(rand(10, 60)),
        ]);
        
        $createdShares++;
        echo "   ✅ Created share record for Member #{$member->id} ({$member->name})\n";
        echo "      - Shares: {$numberOfShares}\n";
        echo "      - Total Paid: UGX " . number_format($totalPaid) . "\n\n";
    }
    
    DB::commit();
    
    echo "\n🎉 SUCCESS!\n";
    echo "   Created {$createdShares} share purchase records\n";
    echo "   Cycle 7 is now ready for shareout testing\n\n";
    echo "📱 Go back to the mobile app and retry Step 3 (Calculate)\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
