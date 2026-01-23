<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FfsGroup;
use App\Models\User;
use App\Models\Project;
use App\Models\AccountTransaction;
use App\Models\VslaLoan;
use App\Models\SocialFundTransaction;
use App\Models\VslaMeeting;
use App\Models\AdvisoryPost;
use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "REAL DATABASE STATISTICS FOR DASHBOARD\n";
echo "========================================\n\n";

// FFS Groups
$totalGroups = FfsGroup::count();
$vslaGroups = FfsGroup::where('type', 'VSLA')->count();
$ffsGroups = $totalGroups - $vslaGroups;
$activeGroups = FfsGroup::where('status', 'Active')->count();

echo "📊 GROUPS:\n";
echo "  Total Groups: " . $totalGroups . "\n";
echo "  Active Groups: " . $activeGroups . "\n";
echo "  FFS Groups: " . $ffsGroups . "\n";
echo "  VSLA Groups: " . $vslaGroups . "\n\n";

// Members
$totalMembers = User::whereNotNull('group_id')->where('group_id', '!=', '')->count();
$activeMembers = User::whereNotNull('group_id')->where('status', 'Active')->count();
$genderBreakdown = User::whereNotNull('group_id')
    ->select('sex', DB::raw('count(*) as count'))
    ->groupBy('sex')
    ->get();

echo "👥 MEMBERS:\n";
echo "  Total Members: " . $totalMembers . "\n";
echo "  Active Members: " . $activeMembers . "\n";
foreach ($genderBreakdown as $gender) {
    echo "  " . ($gender->sex ?: 'Unknown') . ": " . $gender->count . "\n";
}
echo "\n";

// Cycles
$activeCycles = Project::where('is_vsla_cycle', 'Yes')->where('is_active_cycle', 'Yes')->count();
$totalCycles = Project::where('is_vsla_cycle', 'Yes')->count();

echo "🔄 CYCLES:\n";
echo "  Total Cycles: " . $totalCycles . "\n";
echo "  Active Cycles: " . $activeCycles . "\n\n";

// VSLA Finance
$totalSavings = AccountTransaction::where('account_type', 'share')->sum('amount');
$activeLoans = VslaLoan::where('status', 'active')->sum('loan_amount');
$socialFund = SocialFundTransaction::sum('amount');
$totalFines = AccountTransaction::where('account_type', 'fine')->sum('amount');

echo "💰 VSLA FINANCE:\n";
echo "  Total Savings: UGX " . number_format($totalSavings) . "\n";
echo "  Active Loans: UGX " . number_format($activeLoans) . "\n";
echo "  Social Fund: UGX " . number_format($socialFund) . "\n";
echo "  Total Fines: UGX " . number_format($totalFines) . "\n";
echo "  Cash on Hand: UGX " . number_format($totalSavings + $totalFines - $activeLoans) . "\n\n";

// Meetings
$totalMeetings = VslaMeeting::count();
$thisMonthMeetings = VslaMeeting::whereMonth('meeting_date', now()->month)->count();
$thisWeekMeetings = VslaMeeting::whereBetween('meeting_date', [now()->startOfWeek(), now()->endOfWeek()])->count();

echo "📅 MEETINGS:\n";
echo "  Total Meetings: " . $totalMeetings . "\n";
echo "  This Month: " . $thisMonthMeetings . "\n";
echo "  This Week: " . $thisWeekMeetings . "\n\n";

// Advisory Posts
$totalPosts = AdvisoryPost::count();
$publishedPosts = AdvisoryPost::where('status', 'published')->count();

echo "📰 ADVISORY:\n";
echo "  Total Posts: " . $totalPosts . "\n";
echo "  Published: " . $publishedPosts . "\n\n";

// Loans
$totalLoansDisbursed = VslaLoan::sum('loan_amount');
$totalLoansRepaid = VslaLoan::sum('amount_paid');
$activeLoanCount = VslaLoan::where('status', 'active')->count();

echo "💳 LOANS:\n";
echo "  Total Disbursed: UGX " . number_format($totalLoansDisbursed) . "\n";
echo "  Total Repaid: UGX " . number_format($totalLoansRepaid) . "\n";
echo "  Active Loans Count: " . $activeLoanCount . "\n";
echo "  Repayment Rate: " . ($totalLoansDisbursed > 0 ? round(($totalLoansRepaid / $totalLoansDisbursed) * 100, 1) : 0) . "%\n\n";

echo "========================================\n";
echo "✅ STATISTICS COLLECTION COMPLETE\n";
echo "========================================\n\n";
