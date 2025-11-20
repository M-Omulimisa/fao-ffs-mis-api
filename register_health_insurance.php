<?php

/**
 * DTEHM Health Insurance Registration Script
 * 
 * This script registers the comprehensive Health Insurance program
 * with medical coverage and events management investment benefits.
 * 
 * Program Details:
 * - Launch: July 2025
 * - Duration: 12 months
 * - Premium: UGX 16,000/month
 * - Coverage: Heart disease, Cancer, Stroke, Epilepsy, Accidents
 * - Investment: UGX 8,000 in events management equipment
 * 
 * Usage: php register_health_insurance.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InsuranceProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Output formatting helpers
function echo_header($text) {
    $line = str_repeat('=', 60);
    echo "\n{$line}\n";
    echo strtoupper($text) . "\n";
    echo "{$line}\n\n";
}

function echo_success($text) {
    echo "✓ {$text}\n";
}

function echo_info($text, $indent = 0) {
    $spaces = str_repeat(' ', $indent);
    echo "{$spaces}{$text}\n";
}

function echo_error($text) {
    echo "✗ ERROR: {$text}\n";
}

try {
    echo_header('DTEHM Health Insurance Registration');
    
    // Get admin user
    $adminUser = User::where('username', 'admin')
        ->orWhere('email', 'admin@admin.com')
        ->first();
    
    if (!$adminUser) {
        $adminUser = User::first();
    }
    
    if (!$adminUser) {
        throw new Exception("No admin user found. Please create an admin user first.");
    }
    
    echo_success("Using admin user: {$adminUser->name} (ID: {$adminUser->id})");
    echo "\n";
    
    // Check for existing Health Insurance program
    $existingProgram = InsuranceProgram::where('name', 'LIKE', '%Health Insurance%')
        ->orWhere('name', 'LIKE', '%Comprehensive Health%')
        ->first();
    
    if ($existingProgram) {
        echo_info("⚠ Health Insurance program already exists:");
        echo_info("   ID: {$existingProgram->id}", 2);
        echo_info("   Name: {$existingProgram->name}", 2);
        echo_info("   Status: {$existingProgram->status}", 2);
        echo_info("   Premium: UGX " . number_format($existingProgram->premium_amount, 0), 2);
        echo "\n";
        echo_info("Skipping registration to avoid duplicates.");
        echo "\n";
        exit(0);
    }
    
    // Prepare insurance program data
    $programData = [
        'name' => 'Comprehensive Health Insurance',
        'description' => 'Complete medical coverage for critical health conditions including heart disease, cancer, stroke, epilepsy, and accidents. Includes innovative investment benefits through events management equipment portfolio with profit sharing and free equipment access for members.',
        
        // Financial Details
        'coverage_amount' => 50000000, // UGX 50M coverage limit
        'premium_amount' => 16000, // UGX 16,000 per month
        
        // Billing Configuration
        'billing_frequency' => 'Monthly',
        'billing_day' => 1, // 1st of each month
        'duration_months' => 12, // 12-month program
        
        // Penalties & Grace Period
        'grace_period_days' => 7,
        'late_payment_penalty' => 2000, // UGX 2,000 penalty
        'penalty_type' => 'Fixed',
        
        // Age Requirements
        'min_age' => 18,
        'max_age' => 70,
        
        // Program Requirements
        'requirements' => json_encode([
            'Valid national identification (ID or Passport)',
            'Completed health questionnaire',
            'Age between 18 and 70 years',
            'Ugandan resident or valid work permit',
            'Mobile phone number for notifications',
            'Ability to pay UGX 16,000 monthly premium'
        ]),
        
        // Program Benefits
        'benefits' => json_encode([
            'Medical Coverage - Heart Diseases (coronary artery disease, heart attacks, hypertension)',
            'Medical Coverage - Cancer (screening, treatment, chemotherapy, radiation therapy)',
            'Medical Coverage - Stroke (emergency response, rehabilitation, recovery support)',
            'Medical Coverage - Epilepsy (seizure management, medication, neurological care)',
            'Medical Coverage - Accidents & Injuries (emergency care, surgery, hospitalization)',
            'FREE Events Equipment Access (sound systems, chairs, tents, decorations)',
            'Bi-annual Profit Sharing (from equipment rentals to non-members)',
            'Priority Equipment Booking (weddings, meetings, ceremonies)',
            'Community Investment Returns (UGX 8,000/month invested in events portfolio)',
            'No Rental Fees (unlimited equipment usage during subscription)',
            'Financial Protection (coverage up to UGX 50M for medical expenses)',
            'Monthly Payment Flexibility (affordable UGX 16,000/month)'
        ]),
        
        // Terms & Conditions
        'terms_and_conditions' => 'This Health Insurance program provides comprehensive medical coverage for specified conditions including heart diseases, cancer, stroke, epilepsy, and accidents/injuries. Monthly premium of UGX 16,000 includes UGX 8,000 for medical coverage and UGX 8,000 invested in DTEHM events management portfolio. Active subscribers receive FREE access to all events equipment (sound systems, chairs, tents, publicity materials) for personal use. Equipment is provided on first-come, first-served basis subject to availability. Non-members pay standard rental rates, with all profits distributed to insurance members bi-annually or annually. Coverage activates upon enrollment and first premium payment. Minimum subscription period is 12 months. Late payments subject to UGX 2,000 penalty after 7-day grace period. Medical coverage subject to program terms, conditions, and exclusions. Pre-existing conditions may have waiting periods. Claims require proper documentation and medical verification. Profit distribution based on actual business performance and is not guaranteed. Equipment usage subject to booking policies and availability. Subscriber responsible for equipment damage or loss. Program runs from July 2025 through June 2026. Early cancellation may result in forfeiture of benefits. Full terms available in subscription agreement.',
        
        // Status & Dates
        'status' => 'Active',
        'start_date' => Carbon::create(2025, 7, 1)->format('Y-m-d'), // July 1, 2025
        'end_date' => Carbon::create(2026, 6, 30)->format('Y-m-d'), // June 30, 2026
        
        // Branding
        'icon' => 'insurance/health-insurance-icon.png',
        'color' => '#05179F', // DTEHM brand color
        
        // Metadata
        'created_by' => $adminUser->id,
        'updated_by' => $adminUser->id,
    ];
    
    echo_info("Registering Health Insurance program...");
    echo "\n";
    
    // Start database transaction
    DB::beginTransaction();
    
    try {
        // Create the insurance program
        $program = InsuranceProgram::create($programData);
        
        echo_success("CREATED: '{$program->name}' (ID: {$program->id})");
        echo_info("├─ Premium: UGX " . number_format($program->premium_amount, 0) . "/month", 2);
        echo_info("├─ Coverage Limit: UGX " . number_format($program->coverage_amount, 0), 2);
        echo_info("├─ Duration: {$program->duration_months} months", 2);
        echo_info("├─ Billing: {$program->billing_frequency} (Day {$program->billing_day})", 2);
        echo_info("├─ Grace Period: {$program->grace_period_days} days", 2);
        echo_info("├─ Late Penalty: UGX " . number_format($program->late_payment_penalty, 0), 2);
        echo_info("├─ Age Range: {$program->min_age} - {$program->max_age} years", 2);
        echo_info("├─ Start Date: " . Carbon::parse($program->start_date)->format('F j, Y'), 2);
        echo_info("├─ End Date: " . Carbon::parse($program->end_date)->format('F j, Y'), 2);
        echo_info("└─ Status: {$program->status}", 2);
        echo "\n";
        
        // Count requirements and benefits
        $requirements = json_decode($program->requirements, true);
        $benefits = json_decode($program->benefits, true);
        
        echo_info("Program Requirements: " . count($requirements) . " items");
        foreach ($requirements as $index => $requirement) {
            echo_info(($index + 1) . ". {$requirement}", 2);
        }
        echo "\n";
        
        echo_info("Program Benefits: " . count($benefits) . " items");
        foreach ($benefits as $index => $benefit) {
            echo_info(($index + 1) . ". {$benefit}", 2);
        }
        echo "\n";
        
        DB::commit();
        
        echo_header('Registration Summary');
        echo_success("Successfully created: 1 insurance program");
        echo "\n";
        
        // Get total insurance programs
        $totalPrograms = InsuranceProgram::count();
        echo_info("Total insurance programs in system: {$totalPrograms}");
        echo "\n";
        
        echo_success('ALL DONE!');
        echo "\n";
        
        // Display all active programs
        $activePrograms = InsuranceProgram::where('status', 'Active')->get();
        
        if ($activePrograms->count() > 0) {
            echo_info("Active Insurance Programs:");
            echo_info(str_repeat('-', 60));
            
            foreach ($activePrograms as $index => $prog) {
                echo_info(($index + 1) . ". {$prog->name}");
                echo_info("   Premium: UGX " . number_format($prog->premium_amount, 0) . "/{$prog->billing_frequency}", 2);
                echo_info("   Coverage: UGX " . number_format($prog->coverage_amount, 0), 2);
                echo_info("   Duration: {$prog->duration_months} months", 2);
                echo_info("   Status: {$prog->status}", 2);
            }
        }
        
        echo "\n";
        echo_header('Next Steps');
        echo_info("1. Login to admin panel: http://localhost:8888/dtehm-insurance-api/admin");
        echo_info("2. Go to 'Insurance Programs' menu");
        echo_info("3. Review the Health Insurance program details");
        echo_info("4. Upload program icon (health insurance themed image)");
        echo_info("5. Verify all coverage details and benefits");
        echo_info("6. Start accepting subscriptions from July 2025!");
        echo "\n";
        
        echo_info("Program Features:");
        echo_info("• Medical coverage for 5 critical conditions", 2);
        echo_info("• Investment in events management portfolio", 2);
        echo_info("• FREE equipment access for members", 2);
        echo_info("• Bi-annual profit sharing", 2);
        echo_info("• Affordable UGX 16,000/month premium", 2);
        echo "\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
    
} catch (\Exception $e) {
    echo "\n";
    echo_error($e->getMessage());
    echo "\n";
    echo_info("Stack Trace:");
    echo_info($e->getTraceAsString());
    echo "\n";
    exit(1);
}
