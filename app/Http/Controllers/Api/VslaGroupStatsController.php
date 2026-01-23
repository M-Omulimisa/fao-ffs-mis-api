<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FfsGroup;
use App\Models\VslaMeeting;
use App\Models\SocialFundTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VslaGroupStatsController extends Controller
{
    /**
     * Get comprehensive group statistics for a specific cycle
     */
    public function getGroupStats(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|integer|exists:ffs_groups,id',
            'cycle_id' => 'required|integer|exists:projects,id',
        ]);

        $groupId = $validated['group_id'];
        $cycleId = $validated['cycle_id'];

        try {
            // Get group info
            $group = FfsGroup::find($groupId);
            $cycle = DB::table('projects')->find($cycleId);

            if (!$group || !$cycle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group or cycle not found',
                ], 404);
            }

            // Get members count
            $totalMembers = DB::table('users')
                ->where('group_id', $groupId)
                ->count();
            
            $activeMembers = DB::table('users')
                ->where('group_id', $groupId)
                ->where('status', 'Active')
                ->count();

            if (!$group || !$cycle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group or cycle not found',
                ], 404);
            }

            // Calculate stats
            $stats = [
                'group_info' => $this->getGroupInfo($group, $cycle, $totalMembers, $activeMembers),
                'financial_overview' => $this->getFinancialOverview($groupId, $cycleId),
                'meeting_stats' => $this->getMeetingStats($groupId, $cycleId),
                'member_stats' => $this->getMemberStats($groupId, $cycleId),
                'cycle_progress' => $this->getCycleProgress($cycle),
                'recent_activities' => $this->getRecentActivities($groupId, $cycleId),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching group stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getGroupInfo($group, $cycle, $totalMembers, $activeMembers)
    {
        return [
            'group_name' => $group->name,
            'group_id' => $group->id,
            'cycle_name' => $cycle->name ?? $cycle->project_name ?? 'Cycle',
            'cycle_id' => $cycle->id,
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
        ];
    }

    private function getFinancialOverview($groupId, $cycleId)
    {
        // Using account_transactions table like the dashboard does
        $query = DB::table('account_transactions')
            ->where('group_id', $groupId)
            ->where('cycle_id', $cycleId);

        // Total shares value
        $totalShares = DB::table('account_transactions')
            ->where('group_id', $groupId)
            ->where('cycle_id', $cycleId)
            ->where('account_type', 'share')
            ->where('owner_type', 'group')
            ->sum('amount');

        // Total savings
        $totalSavings = DB::table('account_transactions')
            ->where('group_id', $groupId)
            ->where('cycle_id', $cycleId)
            ->where('account_type', 'savings')
            ->where('owner_type', 'group')
            ->sum('amount');

        // Active loans (from vsla_loans table)
        $activeLoans = DB::table('vsla_loans')
            ->where('cycle_id', $cycleId)
            ->where('status', 'active')
            ->sum('balance');

        // Total loan disbursed
        $totalDisbursed = abs(DB::table('account_transactions')
            ->where('group_id', $groupId)
            ->where('cycle_id', $cycleId)
            ->where('account_type', 'loan')
            ->where('owner_type', 'group')
            ->sum('amount'));

        // Total loan interest earned - calculate from account transactions if available
        $totalInterestEarned = 0;

        // Total repaid
        $totalRepaid = $totalDisbursed - $activeLoans;

        // Social fund balance
        $socialFundBalance = SocialFundTransaction::where('group_id', $groupId)
            ->where('cycle_id', $cycleId)
            ->sum('amount');

        // Fines collected
        $finesCollected = DB::table('account_transactions')
            ->where('group_id', $groupId)
            ->where('cycle_id', $cycleId)
            ->where('account_type', 'fine')
            ->where('owner_type', 'group')
            ->sum('amount');

        // Calculate total cash on hand
        $totalCash = $totalShares + $totalSavings + $socialFundBalance + $finesCollected + $totalInterestEarned - $activeLoans;

        return [
            'total_shares_value' => floatval($totalShares),
            'total_savings' => floatval($totalSavings),
            'active_loans_portfolio' => floatval($activeLoans),
            'total_loan_disbursed' => floatval($totalDisbursed),
            'total_loan_repaid' => floatval($totalRepaid),
            'loan_interest_earned' => floatval($totalInterestEarned),
            'social_fund_balance' => floatval($socialFundBalance),
            'fines_collected' => floatval($finesCollected),
            'total_cash_on_hand' => floatval($totalCash),
        ];
    }

    private function getMeetingStats($groupId, $cycleId)
    {
        $meetings = VslaMeeting::where('group_id', $groupId)
            ->where('cycle_id', $cycleId)
            ->get();

        $totalMeetings = $meetings->count();
        
        // Calculate average attendance
        $totalAttendance = 0;
        $meetingsWithAttendance = 0;

        foreach ($meetings as $meeting) {
            if ($meeting->attendance_data) {
                // attendance_data might already be an array if it's a JSON column in PostgreSQL
                $attendance = is_array($meeting->attendance_data) 
                    ? $meeting->attendance_data 
                    : json_decode($meeting->attendance_data, true);
                
                if (is_array($attendance)) {
                    $present = collect($attendance)->where('status', 'present')->count();
                    $totalAttendance += $present;
                    $meetingsWithAttendance++;
                }
            }
        }

        $averageAttendance = $meetingsWithAttendance > 0 
            ? round(($totalAttendance / $meetingsWithAttendance), 1)
            : 0;

        // Get last meeting
        $lastMeeting = $meetings->sortByDesc('meeting_date')->first();

        return [
            'total_meetings' => $totalMeetings,
            'average_attendance' => floatval($averageAttendance),
            'last_meeting_date' => $lastMeeting ? $lastMeeting->meeting_date : null,
            'next_meeting_date' => $lastMeeting && $lastMeeting->next_meeting_date 
                ? $lastMeeting->next_meeting_date 
                : null,
        ];
    }

    private function getMemberStats($groupId, $cycleId)
    {
        // Top saver
        $topSaverData = DB::table('account_transactions')
            ->select('user_id', DB::raw('SUM(amount) as total'))
            ->where('group_id', $groupId)
            ->where('cycle_id', $cycleId)
            ->where('account_type', 'share')
            ->where('owner_type', 'member')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->first();

        $topSaver = null;
        if ($topSaverData) {
            $member = DB::table('users')->find($topSaverData->user_id);
            if ($member) {
                $topSaver = [
                    'name' => $member->name,
                    'amount' => floatval($topSaverData->total),
                ];
            }
        }

        // Top borrower
        $topBorrowerData = DB::table('vsla_loans')
            ->select('borrower_id', DB::raw('SUM(loan_amount) as total'))
            ->where('cycle_id', $cycleId)
            ->whereIn('status', ['active', 'completed'])
            ->groupBy('borrower_id')
            ->orderByDesc('total')
            ->first();

        $topBorrower = null;
        if ($topBorrowerData) {
            $member = DB::table('users')->find($topBorrowerData->borrower_id);
            if ($member) {
                $topBorrower = [
                    'name' => $member->name,
                    'amount' => floatval($topBorrowerData->total),
                ];
            }
        }

        // Members with active loans
        $membersWithLoans = DB::table('vsla_loans')
            ->where('cycle_id', $cycleId)
            ->where('status', 'active')
            ->distinct('borrower_id')
            ->count('borrower_id');

        return [
            'top_saver' => $topSaver,
            'top_borrower' => $topBorrower,
            'members_with_active_loans' => $membersWithLoans,
        ];
    }

    private function getCycleProgress($cycle)
    {
        $startDate = \Carbon\Carbon::parse($cycle->start_date);
        $endDate = \Carbon\Carbon::parse($cycle->end_date);
        $now = \Carbon\Carbon::now();

        $totalDays = $startDate->diffInDays($endDate);
        $daysPassed = $startDate->diffInDays($now);
        $daysRemaining = $now->diffInDays($endDate);

        $progress = $totalDays > 0 ? min(100, ($daysPassed / $totalDays) * 100) : 0;

        return [
            'start_date' => $cycle->start_date,
            'end_date' => $cycle->end_date,
            'total_days' => $totalDays,
            'days_passed' => $daysPassed,
            'days_remaining' => $daysRemaining,
            'progress_percentage' => round($progress, 1),
            'status' => $cycle->status,
        ];
    }

    private function getRecentActivities($groupId, $cycleId)
    {
        $activities = [];

        // Recent meetings
        $recentMeetings = VslaMeeting::where('group_id', $groupId)
            ->where('cycle_id', $cycleId)
            ->orderByDesc('meeting_date')
            ->limit(3)
            ->get(['meeting_date', 'meeting_number']);

        foreach ($recentMeetings as $meeting) {
            $activities[] = [
                'type' => 'meeting',
                'title' => 'Meeting #' . $meeting->meeting_number,
                'date' => $meeting->meeting_date,
            ];
        }

        // Recent loans
        $recentLoansData = DB::table('vsla_loans')
            ->where('cycle_id', $cycleId)
            ->orderByDesc('created_at')
            ->limit(2)
            ->get();

        foreach ($recentLoansData as $loan) {
            $member = DB::table('users')->find($loan->borrower_id);
            $activities[] = [
                'type' => 'loan',
                'title' => 'Loan to ' . ($member->name ?? 'Unknown'),
                'amount' => floatval($loan->loan_amount),
                'date' => date('Y-m-d', strtotime($loan->created_at)),
            ];
        }

        // Sort by date
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($activities, 0, 5);
    }
}
