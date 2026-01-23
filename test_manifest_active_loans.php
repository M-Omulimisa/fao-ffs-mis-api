<?php
/**
 * Test script to verify active loans are returned in manifest API
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FfsGroup;
use App\Models\VslaLoan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== Testing Active Loans in Manifest API ===\n\n";

try {
    // Find a test user with a group
    $user = User::where('status', 'Active')
        ->whereNotNull('group_id')
        ->where('group_id', '>', 0)
        ->first();
    
    if (!$user) {
        echo "❌ No user with group found\n";
        exit(1);
    }
    
    echo "👤 Test User: {$user->name}\n";
    echo "   Group ID: {$user->group_id}\n\n";
    
    // Login as this user
    Auth::login($user);
    
    // Get the group
    $group = FfsGroup::find($user->group_id);
    
    if (!$group) {
        echo "❌ Group not found\n";
        exit(1);
    }
    
    echo "👥 Group: {$group->name}\n\n";
    
    // Check active loans directly
    $loans = VslaLoan::where('balance', '>', 0)
        ->whereIn('status', ['active', 'overdue'])
        ->get();
    
    echo "📊 Active Loans in Database:\n";
    echo "   Count: {$loans->count()}\n\n";
    
    if ($loans->count() > 0) {
        foreach ($loans as $loan) {
            echo "   Loan #{$loan->id}:\n";
            echo "   - Balance: UGX " . number_format($loan->balance, 2) . "\n";
            echo "   - Status: {$loan->status}\n";
            echo "   - Cycle ID: {$loan->cycle_id}\n\n";
        }
    }
    
    // Make API request
    echo "🌐 Making API request to vsla/groups/{$group->id}/manifest...\n\n";
    
    $controller = new \App\Http\Controllers\Api\VslaGroupManifestController();
    $response = $controller->getManifest($group->id);
    $responseData = $response->getData(true);
    
    echo "📥 API Response:\n";
    echo "   Code: {$responseData['code']}\n";
    echo "   Success: " . ($responseData['success'] ? 'Yes' : 'No') . "\n\n";
    
    if ($responseData['success'] && isset($responseData['data'])) {
        $manifest = $responseData['data'];
        
        echo "📦 Manifest Data:\n";
        echo "   Group: " . ($manifest['group_info']['name'] ?? 'N/A') . "\n";
        echo "   Cycle: " . ($manifest['cycle_info']['name'] ?? 'N/A') . "\n";
        echo "   Members: " . count($manifest['members']['members'] ?? []) . "\n";
        echo "   Recent Meetings: " . count($manifest['recent_meetings'] ?? []) . "\n";
        echo "   Active Loans: " . count($manifest['active_loans'] ?? []) . " ⭐\n\n";
        
        if (isset($manifest['active_loans']) && count($manifest['active_loans']) > 0) {
            echo "✅ Active Loans Found in Manifest:\n\n";
            
            foreach ($manifest['active_loans'] as $index => $loan) {
                echo "   Loan #" . ($index + 1) . ":\n";
                echo "   - ID: {$loan['id']}\n";
                echo "   - Loan Number: {$loan['loan_number']}\n";
                echo "   - Borrower: {$loan['borrower_name']}\n";
                echo "   - Balance: UGX " . number_format($loan['balance'], 2) . "\n";
                echo "   - Status: {$loan['status']}\n";
                echo "   - Overdue: " . ($loan['is_overdue'] ? 'Yes' : 'No') . "\n\n";
            }
            
            echo "🎉 SUCCESS: Active loans are properly included in manifest!\n";
            echo "   The mobile app should receive {$loans->count()} active loans when syncing.\n\n";
            
        } else {
            echo "⚠️  WARNING: No active loans in manifest\n";
            echo "   Database has {$loans->count()} active loans\n";
            echo "   But manifest returned 0 active loans\n";
            echo "   This indicates an issue with the getActiveLoans() method\n\n";
        }
        
    } else {
        echo "❌ API request failed\n";
        echo "   Message: " . ($responseData['message'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    exit(1);
}
