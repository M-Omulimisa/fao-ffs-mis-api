<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FfsGroup;
use App\Models\Project;
use App\Models\User;
use App\Models\VslaMeeting;
use App\Models\VslaActionPlan;
use App\Models\VslaLoan;
use App\Models\AccountTransaction;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VslaGroupManifestController extends Controller
{
    use ApiResponser;
    
    /**
     * Get complete VSLA group manifest for offline storage
     * 
     * Returns all essential group data including:
     * - Group information
     * - Current cycle information
     * - Members list with financial summaries
     * - Recent meetings
     * - Action plans
     * - Dashboard data
     * - Reminders
     * 
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getManifest($groupId)
    {
        try {
            $group = FfsGroup::with([
                'admin',
                'secretary',
                'treasurer',
                'district'
            ])->find($groupId);
            
            if (!$group) {
                return $this->error('Group not found', 404);
            }
            
            // Authorization check
            $user = Auth::user();
            if (!$this->canAccessGroup($user, $group)) {
                return $this->error('Unauthorized access to group', 403);
            }
            
            // Build complete manifest
            $manifest = [
                'group_info' => $this->getGroupInfo($group),
                'cycle_info' => $this->getCurrentCycleInfo($group),
                'members' => $this->getMembersSummary($group),
                'recent_meetings' => $this->getRecentMeetings($group, 10),
                'action_plans' => $this->getActionPlans($group),
                'dashboard' => $this->getDashboardData($group),
                'reminders' => $this->getReminders($group),
                'sync_info' => [
                    'synced_at' => now()->toIso8601String(),
                    'server_time' => now()->toDateTimeString(),
                    'timezone' => config('app.timezone'),
                ],
            ];
            
            return $this->success('Group manifest retrieved successfully', $manifest);
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve manifest: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get incremental updates since last sync
     * 
     * @param Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIncrementalUpdates(Request $request, $groupId)
    {
        try {
            $since = $request->input('since');
            if (!$since) {
                return $this->error('Since parameter is required', 400);
            }
            
            $group = FfsGroup::find($groupId);
            if (!$group) {
                return $this->error('Group not found', 404);
            }
            
            $user = Auth::user();
            if (!$this->canAccessGroup($user, $group)) {
                return $this->error('Unauthorized access to group', 403);
            }
            
            $sinceDate = Carbon::parse($since);
            
            // Collect only changes since last sync
            $changes = [
                'has_updates' => false,
                'members_updated' => $this->getMembersChangedSince($groupId, $sinceDate),
                'new_meetings' => $this->getMeetingsSince($groupId, $sinceDate),
                'new_action_plans' => $this->getActionPlansSince($groupId, $sinceDate),
                'financial_updates' => $this->getFinancialUpdatesSince($groupId, $sinceDate),
                'sync_info' => [
                    'synced_at' => now()->toIso8601String(),
                    'changes_since' => $since,
                ],
            ];
            
            // Flag if there are any updates
            $changes['has_updates'] = 
                !empty($changes['members_updated']) ||
                !empty($changes['new_meetings']) ||
                !empty($changes['new_action_plans']) ||
                !empty($changes['financial_updates']);
            
            return $this->success('Incremental updates retrieved', $changes);
            
        } catch (\Exception $e) {
            return $this->error('Failed to get incremental updates: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get group information
     */
    private function getGroupInfo($group)
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'code' => $group->code ?? 'N/A',
            'type' => $group->type ?? 'VSLA',
            'status' => $group->status ?? 'Active',
            'establishment_date' => $group->establishment_date,
            'registration_date' => $group->registration_date,
            
            'location' => [
                'district_id' => $group->district_id,
                'district_name' => $group->district ? $group->district->name : null,
                'subcounty_text' => $group->subcounty_text,
                'parish_text' => $group->parish_text,
                'village' => $group->village,
            ],
            
            'meeting_details' => [
                'venue' => $group->meeting_venue,
                'day' => $group->meeting_day,
                'frequency' => $group->meeting_frequency,
                'time' => $group->meeting_time,
            ],
            
            'statistics' => $this->getGroupStatistics($group),
            
            'core_team' => [
                'admin' => $this->getUserSummary($group->admin),
                'secretary' => $this->getUserSummary($group->secretary),
                'treasurer' => $this->getUserSummary($group->treasurer),
            ],
            
            'description' => $group->description,
        ];
    }
    
    /**
     * Get group statistics
     */
    private function getGroupStatistics($group)
    {
        $members = User::where('group_id', $group->id)->get();
        
        return [
            'total_members' => $members->count(),
            'male_members' => $members->where('sex', 'Male')->count(),
            'female_members' => $members->where('sex', 'Female')->count(),
            'youth_members' => $members->where('is_youth', 1)->count(),
            'pwd_members' => $members->where('is_pwd', 1)->count(),
            'active_members' => $members->where('status', 'Active')->count(),
            'inactive_members' => $members->where('status', '!=', 'Active')->count(),
        ];
    }
    
    /**
     * Get current cycle information
     */
    private function getCurrentCycleInfo($group)
    {
        // Get active cycle - must have is_active_cycle='Yes' AND status NOT completed/closed
        $cycle = Project::where('group_id', $group->id)
            ->where('is_vsla_cycle', 'Yes')
            ->where('is_active_cycle', 'Yes')
            ->whereNotIn('status', ['completed', 'closed'])
            ->orderBy('created_at', 'desc')
            ->first();
        
        if (!$cycle) {
            return null;
        }
        
        $startDate = Carbon::parse($cycle->start_date);
        $endDate = Carbon::parse($cycle->end_date);
        $now = Carbon::now();
        
        $totalWeeks = $startDate->diffInWeeks($endDate);
        $weeksElapsed = $startDate->diffInWeeks($now);
        $weeksRemaining = max(0, $now->diffInWeeks($endDate));
        $progressPercentage = $totalWeeks > 0 ? ($weeksElapsed / $totalWeeks) * 100 : 0;
        
        return [
            'id' => $cycle->id,
            'name' => $cycle->name ?? $cycle->title ?? 'Cycle ' . ($cycle->cycle_number ?? 1),
            'cycle_number' => $cycle->cycle_number ?? 1,
            'start_date' => $cycle->start_date,
            'end_date' => $cycle->end_date,
            'status' => $cycle->status,
            'saving_type' => $cycle->saving_type ?? 'shares',
            'weeks_elapsed' => $weeksElapsed,
            'weeks_remaining' => $weeksRemaining,
            'progress_percentage' => round($progressPercentage, 2),
            
            'financial_summary' => $this->getCycleFinancialSummary($cycle),
            'targets' => $this->getCycleTargets($cycle),
        ];
    }
    
    /**
     * Get cycle financial summary
     */
    private function getCycleFinancialSummary($cycle)
    {
        // Get all transactions for this cycle
        $transactions = AccountTransaction::where('project_id', $cycle->id)->get();
        
        // Share-related calculations
        $sharePrice = $cycle->share_price ?? 5000;
        $maxSharesPerMember = $cycle->max_shares_per_member ?? 10;
        $totalSharesSold = $transactions->where('type', 'SHARE')->sum('amount') / $sharePrice;
        $totalShareValue = $totalSharesSold * $sharePrice;
        
        // Savings calculations
        $totalSavings = $transactions->whereIn('type', ['DEPOSIT', 'SAVING'])->sum('amount');
        
        // Loans calculations
        $totalLoansGiven = VslaLoan::where('project_id', $cycle->id)->sum('amount');
        $totalLoansRepaid = $transactions->where('type', 'LOAN_REPAYMENT')->sum('amount');
        $outstandingLoans = VslaLoan::where('project_id', $cycle->id)
            ->where('status', '!=', 'Paid')
            ->sum('balance');
        
        // Other funds
        $totalFinesCollected = $transactions->where('type', 'FINE')->sum('amount');
        $welfareFund = $transactions->where('type', 'WELFARE')->sum('amount');
        
        // Calculate group cash balance
        $totalIncome = $totalSavings + $totalLoansRepaid + $totalFinesCollected + $welfareFund;
        $totalOutgoing = $totalLoansGiven;
        $groupCashBalance = $totalIncome - $totalOutgoing;
        
        return [
            'share_price' => $sharePrice,
            'max_shares_per_member' => $maxSharesPerMember,
            'total_shares_sold' => (int) $totalSharesSold,
            'total_share_value' => $totalShareValue,
            
            'total_savings' => $totalSavings,
            'total_loans_disbursed' => $totalLoansGiven,
            'total_loans_repaid' => $totalLoansRepaid,
            'outstanding_loans' => $outstandingLoans,
            
            'total_fines_collected' => $totalFinesCollected,
            'welfare_fund' => $welfareFund,
            'group_cash_balance' => $groupCashBalance,
        ];
    }
    
    /**
     * Get cycle targets
     */
    private function getCycleTargets($cycle)
    {
        $targetSavings = $cycle->target_savings ?? 2000000;
        $targetLoans = $cycle->target_loans ?? 1000000;
        
        $actualSavings = AccountTransaction::where('project_id', $cycle->id)
            ->whereIn('type', ['DEPOSIT', 'SAVING'])
            ->sum('amount');
        
        $actualLoans = VslaLoan::where('project_id', $cycle->id)->sum('amount');
        
        return [
            'target_savings' => $targetSavings,
            'target_loans' => $targetLoans,
            'savings_progress' => $targetSavings > 0 ? round(($actualSavings / $targetSavings) * 100, 2) : 0,
            'loans_progress' => $targetLoans > 0 ? round(($actualLoans / $targetLoans) * 100, 2) : 0,
        ];
    }
    
    /**
     * Get members summary with financial info
     */
    private function getMembersSummary($group)
    {
        $members = User::where('group_id', $group->id)
            ->orderBy('name')
            ->get();
        
        $cycle = Project::where('group_id', $group->id)
            ->where('is_vsla_cycle', 'Yes')
            ->where('is_active_cycle', 'Yes')
            ->whereNotIn('status', ['completed', 'closed'])
            ->first();
        
        $membersList = [];
        
        foreach ($members as $member) {
            $membersList[] = [
                'id' => $member->id,
                'name' => $member->name,
                'phone' => $member->phone,
                'email' => $member->email,
                'member_code' => $member->member_code ?? 'N/A',
                'role' => $this->getMemberRole($member, $group),
                'is_active' => $member->status === 'Active',
                'joined_date' => $member->created_at ? $member->created_at->format('Y-m-d') : null,
                
                'financial_summary' => $this->getMemberFinancialSummary($member, $cycle),
                'statistics' => $this->getMemberStatistics($member, $group, $cycle),
            ];
        }
        
        return [
            'members' => $membersList,
            'summary' => [
                'total_members' => count($membersList),
                'active_members' => collect($membersList)->where('is_active', true)->count(),
                'members_with_loans' => collect($membersList)
                    ->filter(fn($m) => $m['financial_summary']['loan_balance'] > 0)
                    ->count(),
                'members_with_shares' => collect($membersList)
                    ->filter(fn($m) => $m['financial_summary']['total_shares'] > 0)
                    ->count(),
                'average_shares_per_member' => count($membersList) > 0 
                    ? round(collect($membersList)->avg('financial_summary.total_shares'), 2)
                    : 0,
                'average_savings' => count($membersList) > 0
                    ? round(collect($membersList)->avg('financial_summary.savings_balance'), 2)
                    : 0,
            ],
        ];
    }
    
    /**
     * Get member role in group
     */
    private function getMemberRole($member, $group)
    {
        if ($group->admin_id == $member->id) return 'Chairman';
        if ($group->secretary_id == $member->id) return 'Secretary';
        if ($group->treasurer_id == $member->id) return 'Treasurer';
        return 'Member';
    }
    
    /**
     * Get member financial summary
     */
    private function getMemberFinancialSummary($member, $cycle)
    {
        if (!$cycle) {
            return [
                'total_shares' => 0,
                'share_value' => 0,
                'savings_balance' => 0,
                'loan_balance' => 0,
                'fines_balance' => 0,
                'welfare_contribution' => 0,
                'net_balance' => 0,
            ];
        }
        
        $sharePrice = $cycle->share_price ?? 5000;
        
        // Get transactions
        $transactions = AccountTransaction::where('user_id', $member->id)
            ->where('project_id', $cycle->id)
            ->get();
        
        $totalShares = $transactions->where('type', 'SHARE')->sum('amount') / $sharePrice;
        $shareValue = $totalShares * $sharePrice;
        $savingsBalance = $transactions->whereIn('type', ['DEPOSIT', 'SAVING'])->sum('amount');
        $finesBalance = $transactions->where('type', 'FINE')->sum('amount');
        $welfareContribution = $transactions->where('type', 'WELFARE')->sum('amount');
        
        // Get loan balance
        $loanBalance = VslaLoan::where('user_id', $member->id)
            ->where('project_id', $cycle->id)
            ->where('status', '!=', 'Paid')
            ->sum('balance');
        
        $netBalance = $savingsBalance + $shareValue - $loanBalance;
        
        return [
            'total_shares' => (int) $totalShares,
            'share_value' => $shareValue,
            'savings_balance' => $savingsBalance,
            'loan_balance' => $loanBalance,
            'fines_balance' => $finesBalance,
            'welfare_contribution' => $welfareContribution,
            'net_balance' => $netBalance,
        ];
    }
    
    /**
     * Get member statistics
     */
    private function getMemberStatistics($member, $group, $cycle)
    {
        if (!$cycle) {
            return [
                'meetings_attended' => 0,
                'meetings_missed' => 0,
                'attendance_rate' => 0,
                'loans_taken' => 0,
                'loans_fully_repaid' => 0,
                'current_active_loans' => 0,
            ];
        }
        
        // Get meeting attendance
        $totalMeetings = VslaMeeting::where('project_id', $cycle->id)->count();
        $attendedMeetings = DB::table('vsla_meeting_attendances')
            ->join('vsla_meetings', 'vsla_meeting_attendances.vsla_meeting_id', '=', 'vsla_meetings.id')
            ->where('vsla_meetings.project_id', $cycle->id)
            ->where('vsla_meeting_attendances.user_id', $member->id)
            ->where('vsla_meeting_attendances.status', 'Present')
            ->count();
        
        $missedMeetings = $totalMeetings - $attendedMeetings;
        $attendanceRate = $totalMeetings > 0 ? ($attendedMeetings / $totalMeetings) * 100 : 0;
        
        // Get loan statistics
        $loansTaken = VslaLoan::where('user_id', $member->id)
            ->where('project_id', $cycle->id)
            ->count();
        
        $loansFullyRepaid = VslaLoan::where('user_id', $member->id)
            ->where('project_id', $cycle->id)
            ->where('status', 'Paid')
            ->count();
        
        $currentActiveLoans = VslaLoan::where('user_id', $member->id)
            ->where('project_id', $cycle->id)
            ->where('status', '!=', 'Paid')
            ->count();
        
        return [
            'meetings_attended' => $attendedMeetings,
            'meetings_missed' => $missedMeetings,
            'attendance_rate' => round($attendanceRate, 2),
            'loans_taken' => $loansTaken,
            'loans_fully_repaid' => $loansFullyRepaid,
            'current_active_loans' => $currentActiveLoans,
        ];
    }
    
    /**
     * Get recent meetings
     */
    private function getRecentMeetings($group, $limit = 10)
    {
        $cycle = Project::where('group_id', $group->id)
            ->where('is_vsla_cycle', 'Yes')
            ->where('is_active_cycle', 'Yes')
            ->whereNotIn('status', ['completed', 'closed'])
            ->first();
        
        if (!$cycle) {
            return [];
        }
        
        $meetings = VslaMeeting::where('project_id', $cycle->id)
            ->orderBy('meeting_date', 'desc')
            ->limit($limit)
            ->get();
        
        $meetingsList = [];
        
        foreach ($meetings as $meeting) {
            $meetingsList[] = [
                'id' => $meeting->id,
                'meeting_number' => $meeting->meeting_number,
                'date' => $meeting->meeting_date,
                'venue' => $meeting->venue,
                'status' => $meeting->status ?? 'completed',
                
                'attendance' => $this->getMeetingAttendance($meeting, $group),
                'financial_summary' => $this->getMeetingFinancialSummary($meeting),
                'action_plans_count' => VslaActionPlan::where('vsla_meeting_id', $meeting->id)->count(),
                'notes' => $meeting->notes,
            ];
        }
        
        return $meetingsList;
    }
    
    /**
     * Get meeting attendance
     */
    private function getMeetingAttendance($meeting, $group)
    {
        $totalMembers = User::where('group_id', $group->id)->count();
        
        $attendances = DB::table('vsla_meeting_attendances')
            ->where('vsla_meeting_id', $meeting->id)
            ->get();
        
        $membersPresent = $attendances->where('status', 'Present')->count();
        $membersAbsent = $totalMembers - $membersPresent;
        $attendanceRate = $totalMembers > 0 ? ($membersPresent / $totalMembers) * 100 : 0;
        
        // Get names of absent members
        $presentUserIds = $attendances->where('status', 'Present')->pluck('user_id');
        $absentMembers = User::where('group_id', $group->id)
            ->whereNotIn('id', $presentUserIds)
            ->pluck('name')
            ->toArray();
        
        return [
            'members_present' => $membersPresent,
            'members_absent' => $membersAbsent,
            'attendance_rate' => round($attendanceRate, 2),
            'absent_members' => $absentMembers,
        ];
    }
    
    /**
     * Get meeting financial summary
     */
    private function getMeetingFinancialSummary($meeting)
    {
        $transactions = AccountTransaction::where('vsla_meeting_id', $meeting->id)->get();
        
        $totalSavingsCollected = $transactions->whereIn('type', ['DEPOSIT', 'SAVING'])->sum('amount');
        $totalLoansRepayments = $transactions->where('type', 'LOAN_REPAYMENT')->sum('amount');
        $totalFines = $transactions->where('type', 'FINE')->sum('amount');
        $totalWelfare = $transactions->where('type', 'WELFARE')->sum('amount');
        
        // Get loans disbursed in this meeting
        $totalLoansDisbursed = VslaLoan::where('vsla_meeting_id', $meeting->id)->sum('amount');
        
        // Get shares sold
        $shareTransactions = $transactions->where('type', 'SHARE');
        $sharesSold = $shareTransactions->count();
        $shareValue = $shareTransactions->sum('amount');
        
        $meetingCashCollected = $totalSavingsCollected + $totalLoansRepayments + $totalFines + $totalWelfare + $shareValue;
        
        return [
            'total_savings_collected' => $totalSavingsCollected,
            'total_loans_disbursed' => $totalLoansDisbursed,
            'total_loan_repayments' => $totalLoansRepayments,
            'total_fines' => $totalFines,
            'total_welfare' => $totalWelfare,
            'shares_sold' => $sharesSold,
            'share_value' => $shareValue,
            'meeting_cash_collected' => $meetingCashCollected,
        ];
    }
    
    /**
     * Get action plans
     */
    private function getActionPlans($group)
    {
        $cycle = Project::where('group_id', $group->id)
            ->where('is_vsla_cycle', 'Yes')
            ->where('is_active_cycle', 'Yes')
            ->whereNotIn('status', ['completed', 'closed'])
            ->first();
        
        if (!$cycle) {
            return [
                'current_action_plans' => [],
                'completed_action_plans' => [],
                'summary' => [
                    'total_active' => 0,
                    'overdue' => 0,
                    'due_this_week' => 0,
                    'completed_this_month' => 0,
                ],
            ];
        }
        
        // Get current (pending) action plans
        $currentPlans = VslaActionPlan::where('project_id', $cycle->id)
            ->where('status', 'pending')
            ->orderBy('deadline', 'asc')
            ->get();
        
        $currentActionPlans = [];
        $overdue = 0;
        $dueThisWeek = 0;
        
        foreach ($currentPlans as $plan) {
            $deadline = Carbon::parse($plan->deadline);
            $daysRemaining = Carbon::now()->diffInDays($deadline, false);
            
            if ($daysRemaining < 0) {
                $overdue++;
            } elseif ($daysRemaining <= 7) {
                $dueThisWeek++;
            }
            
            $currentActionPlans[] = [
                'id' => $plan->id,
                'description' => $plan->description,
                'responsible_member' => $this->getUserSummary(User::find($plan->responsible_user_id)),
                'deadline' => $plan->deadline,
                'status' => $plan->status,
                'priority' => $plan->priority ?? 'medium',
                'created_at' => $plan->created_at ? $plan->created_at->format('Y-m-d') : null,
                'days_remaining' => (int) $daysRemaining,
            ];
        }
        
        // Get completed action plans (last 10)
        $completedPlans = VslaActionPlan::where('project_id', $cycle->id)
            ->where('status', 'completed')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
        
        $completedActionPlans = [];
        
        foreach ($completedPlans as $plan) {
            $completedActionPlans[] = [
                'id' => $plan->id,
                'description' => $plan->description,
                'completed_date' => $plan->updated_at ? $plan->updated_at->format('Y-m-d') : null,
                'completion_notes' => $plan->completion_notes,
                'responsible_member' => User::find($plan->responsible_user_id)->name ?? 'N/A',
            ];
        }
        
        $completedThisMonth = VslaActionPlan::where('project_id', $cycle->id)
            ->where('status', 'completed')
            ->whereMonth('updated_at', Carbon::now()->month)
            ->count();
        
        return [
            'current_action_plans' => $currentActionPlans,
            'completed_action_plans' => $completedActionPlans,
            'summary' => [
                'total_active' => count($currentActionPlans),
                'overdue' => $overdue,
                'due_this_week' => $dueThisWeek,
                'completed_this_month' => $completedThisMonth,
            ],
        ];
    }
    
    /**
     * Get dashboard data
     */
    private function getDashboardData($group)
    {
        $cycle = Project::where('group_id', $group->id)
            ->where('is_vsla_cycle', 'Yes')
            ->where('is_active_cycle', 'Yes')
            ->whereNotIn('status', ['completed', 'closed'])
            ->first();
        
        if (!$cycle) {
            return null;
        }
        
        $financialSummary = $this->getCycleFinancialSummary($cycle);
        
        // Calculate additional metrics
        $totalLoans = VslaLoan::where('project_id', $cycle->id)->sum('amount');
        $totalRepaid = AccountTransaction::where('project_id', $cycle->id)
            ->where('type', 'LOAN_REPAYMENT')
            ->sum('amount');
        
        $overdueLoans = VslaLoan::where('project_id', $cycle->id)
            ->where('status', '!=', 'Paid')
            ->where('due_date', '<', Carbon::now())
            ->get();
        
        $overdueAmount = $overdueLoans->sum('balance');
        $overdueCount = $overdueLoans->count();
        $defaultRate = $totalLoans > 0 ? ($overdueAmount / $totalLoans) * 100 : 0;
        
        // Get highest saver
        $members = User::where('group_id', $group->id)->get();
        $highestSaver = null;
        $highestAmount = 0;
        
        foreach ($members as $member) {
            $savings = AccountTransaction::where('user_id', $member->id)
                ->where('project_id', $cycle->id)
                ->whereIn('type', ['DEPOSIT', 'SAVING'])
                ->sum('amount');
            
            if ($savings > $highestAmount) {
                $highestAmount = $savings;
                $highestSaver = $member->name . ' (' . $savings . ')';
            }
        }
        
        // Calculate growth this month
        $growthThisMonth = AccountTransaction::where('project_id', $cycle->id)
            ->whereIn('type', ['DEPOSIT', 'SAVING'])
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('amount');
        
        return [
            'group_cash_position' => [
                'total_cash' => $financialSummary['group_cash_balance'],
                'available_for_lending' => max(0, $financialSummary['group_cash_balance'] - $financialSummary['welfare_fund']),
                'reserved_funds' => $financialSummary['welfare_fund'] + $financialSummary['total_fines_collected'],
                'welfare_fund' => $financialSummary['welfare_fund'],
                'fines_collected' => $financialSummary['total_fines_collected'],
                'last_updated' => Carbon::now()->format('Y-m-d'),
            ],
            
            'savings_overview' => [
                'total_savings' => $financialSummary['total_savings'],
                'member_contributions' => $financialSummary['total_savings'],
                'share_purchases' => $financialSummary['total_share_value'],
                'average_per_member' => $members->count() > 0 
                    ? round($financialSummary['total_savings'] / $members->count(), 2)
                    : 0,
                'highest_saver' => $highestSaver,
                'growth_this_month' => $growthThisMonth,
            ],
            
            'loans_overview' => [
                'total_loans_disbursed' => $financialSummary['total_loans_disbursed'],
                'total_repaid' => $totalRepaid,
                'outstanding_balance' => $financialSummary['outstanding_loans'],
                'active_loans_count' => VslaLoan::where('project_id', $cycle->id)
                    ->where('status', '!=', 'Paid')
                    ->count(),
                'overdue_loans_count' => $overdueCount,
                'overdue_amount' => $overdueAmount,
                'default_rate' => round($defaultRate, 2),
            ],
            
            'fines_and_penalties' => [
                'total_fines_collected' => $financialSummary['total_fines_collected'],
                'fines_this_month' => AccountTransaction::where('project_id', $cycle->id)
                    ->where('type', 'FINE')
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->sum('amount'),
                'most_common_fine' => 'Late attendance', // Could be calculated from transaction descriptions
                'members_with_fines' => AccountTransaction::where('project_id', $cycle->id)
                    ->where('type', 'FINE')
                    ->distinct('user_id')
                    ->count(),
            ],
        ];
    }
    
    /**
     * Get reminders and upcoming events
     */
    private function getReminders($group)
    {
        $cycle = Project::where('group_id', $group->id)
            ->where('is_vsla_cycle', 'Yes')
            ->where('is_active_cycle', 'Yes')
            ->whereNotIn('status', ['completed', 'closed'])
            ->first();
        
        if (!$cycle) {
            return null;
        }
        
        // Next meeting
        $nextMeeting = VslaMeeting::where('project_id', $cycle->id)
            ->where('meeting_date', '>=', Carbon::now())
            ->orderBy('meeting_date', 'asc')
            ->first();
        
        $nextMeetingData = null;
        if ($nextMeeting) {
            $daysUntil = Carbon::now()->diffInDays(Carbon::parse($nextMeeting->meeting_date), false);
            $nextMeetingData = [
                'date' => $nextMeeting->meeting_date,
                'venue' => $nextMeeting->venue ?? $group->meeting_venue,
                'time' => $group->meeting_time,
                'days_until' => (int) $daysUntil,
                'expected_attendees' => User::where('group_id', $group->id)
                    ->where('status', 'Active')
                    ->count(),
            ];
        }
        
        // Loans due soon (within 7 days)
        $loansDueSoon = VslaLoan::where('project_id', $cycle->id)
            ->where('status', '!=', 'Paid')
            ->where('due_date', '>=', Carbon::now())
            ->where('due_date', '<=', Carbon::now()->addDays(7))
            ->get();
        
        $loansDueSoonList = [];
        foreach ($loansDueSoon as $loan) {
            $daysRemaining = Carbon::now()->diffInDays(Carbon::parse($loan->due_date), false);
            $loansDueSoonList[] = [
                'member_name' => User::find($loan->user_id)->name ?? 'N/A',
                'amount' => $loan->balance,
                'due_date' => $loan->due_date,
                'days_remaining' => (int) $daysRemaining,
            ];
        }
        
        // Action plans due soon (within 7 days)
        $actionPlansDue = VslaActionPlan::where('project_id', $cycle->id)
            ->where('status', 'pending')
            ->where('deadline', '>=', Carbon::now())
            ->where('deadline', '<=', Carbon::now()->addDays(7))
            ->get();
        
        $actionPlansDueList = [];
        foreach ($actionPlansDue as $plan) {
            $daysRemaining = Carbon::now()->diffInDays(Carbon::parse($plan->deadline), false);
            $actionPlansDueList[] = [
                'description' => $plan->description,
                'responsible' => User::find($plan->responsible_user_id)->name ?? 'N/A',
                'due_date' => $plan->deadline,
                'days_remaining' => (int) $daysRemaining,
            ];
        }
        
        // Cycle milestones
        $endDate = Carbon::parse($cycle->end_date);
        $daysUntilEnd = Carbon::now()->diffInDays($endDate, false);
        
        return [
            'next_meeting' => $nextMeetingData,
            'loans_due_soon' => $loansDueSoonList,
            'action_plans_due' => $actionPlansDueList,
            'cycle_milestones' => [
                'cycle_end_date' => $cycle->end_date,
                'days_until_end' => (int) $daysUntilEnd,
                'shareout_planned' => $cycle->shareout_planned ?? false,
                'shareout_date' => $cycle->shareout_date,
            ],
        ];
    }
    
    /**
     * Get members changed since date
     */
    private function getMembersChangedSince($groupId, $sinceDate)
    {
        $members = User::where('group_id', $groupId)
            ->where('updated_at', '>=', $sinceDate)
            ->get();
        
        $group = FfsGroup::find($groupId);
        $cycle = Project::where('group_id', $groupId)
            ->where('is_vsla_cycle', 'Yes')
            ->where('is_active_cycle', 'Yes')
            ->whereNotIn('status', ['completed', 'closed'])
            ->first();
        
        $updatedMembers = [];
        foreach ($members as $member) {
            $updatedMembers[] = [
                'id' => $member->id,
                'name' => $member->name,
                'financial_summary' => $this->getMemberFinancialSummary($member, $cycle),
                'updated_at' => $member->updated_at->toIso8601String(),
            ];
        }
        
        return $updatedMembers;
    }
    
    /**
     * Get meetings since date
     */
    private function getMeetingsSince($groupId, $sinceDate)
    {
        $cycle = Project::where('group_id', $groupId)
            ->where('is_vsla_cycle', 'Yes')
            ->where('is_active_cycle', 'Yes')
            ->whereNotIn('status', ['completed', 'closed'])
            ->first();
        
        if (!$cycle) {
            return [];
        }
        
        $meetings = VslaMeeting::where('project_id', $cycle->id)
            ->where('created_at', '>=', $sinceDate)
            ->orderBy('meeting_date', 'desc')
            ->get();
        
        $group = FfsGroup::find($groupId);
        $meetingsList = [];
        
        foreach ($meetings as $meeting) {
            $meetingsList[] = [
                'id' => $meeting->id,
                'meeting_number' => $meeting->meeting_number,
                'date' => $meeting->meeting_date,
                'financial_summary' => $this->getMeetingFinancialSummary($meeting),
                'created_at' => $meeting->created_at->toIso8601String(),
            ];
        }
        
        return $meetingsList;
    }
    
    /**
     * Get action plans since date
     */
    private function getActionPlansSince($groupId, $sinceDate)
    {
        $cycle = Project::where('group_id', $groupId)
            ->where('is_vsla_cycle', 'Yes')
            ->where('is_active_cycle', 'Yes')
            ->whereNotIn('status', ['completed', 'closed'])
            ->first();
        
        if (!$cycle) {
            return [];
        }
        
        $plans = VslaActionPlan::where('project_id', $cycle->id)
            ->where('created_at', '>=', $sinceDate)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $plansList = [];
        foreach ($plans as $plan) {
            $plansList[] = [
                'id' => $plan->id,
                'description' => $plan->description,
                'status' => $plan->status,
                'deadline' => $plan->deadline,
                'created_at' => $plan->created_at->toIso8601String(),
            ];
        }
        
        return $plansList;
    }
    
    /**
     * Get financial updates since date
     */
    private function getFinancialUpdatesSince($groupId, $sinceDate)
    {
        $cycle = Project::where('group_id', $groupId)
            ->where('is_vsla_cycle', 'Yes')
            ->where('is_active_cycle', 'Yes')
            ->whereNotIn('status', ['completed', 'closed'])
            ->first();
        
        if (!$cycle) {
            return [];
        }
        
        $recentTransactions = AccountTransaction::where('project_id', $cycle->id)
            ->where('created_at', '>=', $sinceDate)
            ->count();
        
        if ($recentTransactions === 0) {
            return [];
        }
        
        // Return updated financial summary
        return [
            'cycle_financial_summary' => $this->getCycleFinancialSummary($cycle),
            'transactions_count' => $recentTransactions,
            'updated_at' => Carbon::now()->toIso8601String(),
        ];
    }
    
    /**
     * Get user summary
     */
    private function getUserSummary($user)
    {
        if (!$user) {
            return null;
        }
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
        ];
    }
    
    /**
     * Check if user can access group
     */
    private function canAccessGroup($user, $group)
    {
        // User must be member of the group or an admin
        return $user->group_id == $group->id || $user->user_type === 'admin';
    }
}
