<?php

/**
 * DTEHM Investment Projects Registration Script
 * 
 * This script registers the 4 main investment projects in the system:
 * 1. Medicine Stockist Program
 * 2. Agribusiness Training Program  
 * 3. Real Estate Investment
 * 4. Boda Boda Fleet Investment
 * 
 * Run: php register_dtehm_projects.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=====================================================\n";
echo "DTEHM INVESTMENT PROJECTS REGISTRATION\n";
echo "=====================================================\n\n";

// Get admin user (creator)
$adminUser = User::where('user_type', 'Admin')->orWhere('username', 'admin')->first();
if (!$adminUser) {
    $adminUser = User::first();
}

if (!$adminUser) {
    echo "❌ ERROR: No admin user found in the system.\n";
    echo "Please create an admin user first.\n";
    exit(1);
}

echo "✓ Using admin user: {$adminUser->name} (ID: {$adminUser->id})\n\n";

// Define projects
$projects = [
    [
        'title' => 'Medicine Distribution Partnership',
        'slug' => 'medicine-stockist-program',
        'description' => "**Build Wealth While Healing Communities**\n\nBecome a DTEHM medicine distribution partner and earn returns while bringing holistic healthcare to patients in your community.\n\n**What You Get:**\n• DTEHM medicine stock worth UGX 20M for patient distribution\n• Comprehensive training on product knowledge & sales\n• Marketing support & ongoing business guidance\n• Direct supply chain from DTEHM Health Ministries\n• Flexible restocking & inventory management\n\n**Perfect for:** Entrepreneurs passionate about health and wellness who want to combine business with social impact.",
        'share_price' => 5000000, // UGX 5M per share
        'total_shares' => 20, // 20 shares (20 x 5M = 100M max)
        'min_investment' => 5000000, // UGX 5M minimum
        'max_investment' => 100000000, // UGX 100M maximum
        'status' => 'ongoing',
        'start_date' => now(),
    ],
    [
        'title' => 'Farm-to-Profit Initiative',
        'slug' => 'agribusiness-training-program',
        'description' => "**Learn, Grow, Earn with DTEHM Experts**\n\nMaster modern fish farming and poultry production with hands-on training from DTEHM agricultural specialists. We provide everything you need to start your profitable farm.\n\n**What You Get:**\n• Expert-led training in fish ponds & poultry management\n• Startup inputs & farming essentials provided\n• Ongoing technical support & farm visits\n• Market linkage for your produce\n• Proven farming techniques for maximum yield\n\n**Ideal for:** Aspiring farmers, rural development enthusiasts, and food security advocates ready to build sustainable income.",
        'share_price' => 1000000, // UGX 1M per share
        'total_shares' => 20, // 20 shares (20 x 1M = 20M max)
        'min_investment' => 1000000, // UGX 1M minimum
        'max_investment' => 20000000, // UGX 20M maximum
        'status' => 'ongoing',
        'start_date' => now(),
    ],
    [
        'title' => 'Property Wealth Builder',
        'slug' => 'real-estate-investment',
        'description' => "**Invest Small, Earn Big in Real Estate**\n\nAccess DTEHM's exclusive real estate portfolio with minimal entry capital. Build long-term wealth through property appreciation and rental income.\n\n**What You Get:**\n• Co-ownership in DTEHM real estate projects\n• Prime locations with high appreciation potential\n• Professional property management included\n• Transparent quarterly earnings reports\n• Low entry barrier for first-time investors\n\n**Perfect for:** Anyone wanting to enter real estate investment without massive capital requirements.",
        'share_price' => 10000, // UGX 10K per share
        'total_shares' => 50000, // 50,000 shares (50K x 10K = 500M max)
        'min_investment' => 100000, // UGX 100K minimum (most affordable!)
        'max_investment' => 500000000, // UGX 500M maximum
        'status' => 'ongoing',
        'start_date' => now(),
    ],
    [
        'title' => 'Motorcycle Taxi Fleet',
        'slug' => 'boda-boda-investment',
        'description' => "**Ride the Wave of Uganda's Transport Revolution**\n\nInvest in DTEHM's managed motorcycle taxi fleet and earn daily returns from Uganda's fastest-growing transport sector.\n\n**What You Get:**\n• Well-maintained motorcycles in high-demand areas\n• Vetted, trained riders with insurance coverage\n• Daily income tracking & transparent reporting\n• Fleet management & maintenance included\n• Rider accountability systems\n\n**Ideal for:** Investors seeking regular cash flow from Uganda's thriving boda boda transport industry.",
        'share_price' => 100000, // UGX 100K per share
        'total_shares' => 200, // 200 shares (200 x 100K = 20M max)
        'min_investment' => 2000000, // UGX 2M minimum (20 shares)
        'max_investment' => 20000000, // UGX 20M maximum
        'status' => 'ongoing',
        'start_date' => now(),
    ],
];

echo "Registering " . count($projects) . " investment projects...\n\n";

DB::beginTransaction();

try {
    $registeredCount = 0;
    $skippedCount = 0;
    
    foreach ($projects as $projectData) {
        // Check if project already exists
        $exists = Project::where('title', $projectData['title'])
            ->orWhere(function($query) use ($projectData) {
                if (isset($projectData['slug'])) {
                    // Check by slug if you have slug field, otherwise skip
                }
            })
            ->first();
        
        if ($exists) {
            echo "⚠ SKIPPED: '{$projectData['title']}' already exists (ID: {$exists->id})\n";
            $skippedCount++;
            continue;
        }
        
        // Create project
        $project = Project::create([
            'title' => $projectData['title'],
            'description' => $projectData['description'],
            'share_price' => $projectData['share_price'],
            'total_shares' => $projectData['total_shares'], // Set the actual total shares
            'shares_sold' => 0, // No shares sold yet
            'status' => $projectData['status'],
            'start_date' => $projectData['start_date'],
            'created_by_id' => $adminUser->id,
            'total_investment' => 0,
            'total_returns' => 0,
            'total_expenses' => 0,
            'total_profits' => 0,
        ]);
        
        $registeredCount++;
        
        echo "✓ CREATED: '{$project->title}' (ID: {$project->id})\n";
        echo "  ├─ Share Price: UGX " . number_format($project->share_price, 0) . "\n";
        echo "  ├─ Min Investment: UGX " . number_format($projectData['min_investment'], 0) . "\n";
        echo "  ├─ Max Investment: UGX " . number_format($projectData['max_investment'], 0) . "\n";
        echo "  ├─ Max Shares: " . $projectData['total_shares'] . "\n";
        echo "  └─ Status: " . ucfirst($project->status) . "\n\n";
    }
    
    DB::commit();
    
    echo "=====================================================\n";
    echo "REGISTRATION SUMMARY\n";
    echo "=====================================================\n";
    echo "✓ Successfully created: {$registeredCount} projects\n";
    if ($skippedCount > 0) {
        echo "⚠ Skipped (duplicates): {$skippedCount} projects\n";
    }
    echo "\nTotal projects in system: " . Project::count() . "\n";
    echo "\n✅ ALL DONE!\n\n";
    
    // Show active projects
    echo "Active Investment Projects:\n";
    echo "---------------------------------------------------\n";
    
    $activeProjects = Project::where('status', 'ongoing')->get();
    foreach ($activeProjects as $p) {
        echo "{$p->id}. {$p->title}\n";
        echo "   Share Price: UGX " . number_format($p->share_price, 0) . " | Status: {$p->status}\n";
    }
    echo "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
    exit(1);
}

echo "=====================================================\n";
echo "Next Steps:\n";
echo "=====================================================\n";
echo "1. Login to admin panel: " . url('/admin') . "\n";
echo "2. Go to 'Projects' menu\n";
echo "3. Upload project images (optional)\n";
echo "4. Review and publish projects\n";
echo "5. Start accepting investments!\n\n";
