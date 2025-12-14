<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FfsGroup;
use App\Models\Project;
use App\Models\VslaMeeting;
use App\Models\AccountTransaction;
use App\Models\VslaLoan;
use App\Models\ProjectShare;
use App\Models\VslaMeetingAttendance;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * VSLA Dashboard Controller
 * 
 * Provides personalized dashboard data based on user role:
 * - Admin users (chairman, secretary, treasurer): Full group access
 * - Regular members: Personal data only
 */
class VslaDashboardController extends Controller
{
    use ApiResponser;

    /**
     * Get personalized VSLA dashboard data
     * 
     * GET /api/vsla/dashboard
     * 
     * Returns different data based on user role:
     * - Admins: Complete group statistics and admin menu
     * - Members: Personal statistics and member menu
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboard(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }

            $groupId = $request->query('group_id');
            $cycleId = $request->query('cycle_id');

            if (!$groupId) {
                return $this->error('group_id is required', 422);
            }

            // Verify user belongs to this group
            $group = FfsGroup::find($groupId);
            if (!$group) {
                return $this->error('VSLA group not found', 404);
            }

            // Check if user is member of this group
            if ($user->group_id != $groupId) {
                return $this->error('You are not a member of this VSLA group', 403);
            }

            // Determine user role
            $isAdmin = $this->isAdminUser($user);
            $userPosition = $this->getUserPosition($user);

            // Get cycle info
            $cycleInfo = $this->getCycleInfo($cycleId);

            // Build response based on role
            if ($isAdmin) {
                $data = $this->getAdminDashboardData($user, $group, $cycleId, $cycleInfo, $userPosition);
            } else {
                $data = $this->getMemberDashboardData($user, $group, $cycleId, $cycleInfo);
            }

            return $this->success('Dashboard data retrieved successfully', $data);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch dashboard data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if user is an admin (chairman, secretary, or treasurer)
     */
    private function isAdminUser(User $user): bool
    {
        $adminPositions = ['chairman', 'secretary', 'treasurer'];
        $position = strtolower($user->vsla_position ?? '');
        
        return in_array($position, $adminPositions);
    }

    /**
     * Get user's position
     */
    private function getUserPosition(User $user): string
    {
        return $user->vsla_position ?? 'member';
    }

    /**
     * Get cycle information
     */
    private function getCycleInfo($cycleId): ?array
    {
        if (!$cycleId) {
            return null;
        }

        $cycle = Project::find($cycleId);
        if (!$cycle) {
            return null;
        }

        $start = $cycle->vsla_cycle_start_date ? \Carbon\Carbon::parse($cycle->vsla_cycle_start_date) : null;
        $end = $cycle->vsla_cycle_end_date ? \Carbon\Carbon::parse($cycle->vsla_cycle_end_date) : null;

        if (!$start || !$end) {
            return [
                'id' => $cycle->id,
                'name' => $cycle->title,
                'start_date' => null,
                'end_date' => null,
                'status' => $cycle->is_active_cycle === 'Yes' ? 'active' : 'inactive',
                'weeks_elapsed' => 0,
                'total_weeks' => 0,
                'progress_percentage' => 0,
            ];
        }

        $now = now();
        $totalDays = $start->diffInDays($end);
        $elapsedDays = $start->diffInDays($now);
        $percentage = $totalDays > 0 ? min(100, round(($elapsedDays / $totalDays) * 100)) : 0;

        return [
            'id' => $cycle->id,
            'name' => $cycle->title,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'status' => $cycle->is_active_cycle === 'Yes' ? 'active' : 'inactive',
            'weeks_elapsed' => round($elapsedDays / 7),
            'total_weeks' => round($totalDays / 7),
            'progress_percentage' => $percentage,
        ];
    }

    /**
     * Get dashboard data for admin users
     */
    private function getAdminDashboardData(User $user, FfsGroup $group, $cycleId, $cycleInfo, $userPosition): array
    {
        $groupId = $group->id;

        // Financial summary
        $financialSummary = $this->getGroupFinancialSummary($groupId, $cycleId);

        // Meeting stats
        $meetingStats = $this->getMeetingStats($groupId, $cycleId);

        // Loan stats
        $loanStats = $this->getLoanStats($groupId, $cycleId);

        // Member stats
        $memberStats = $this->getMemberStats($groupId, $cycleId);

        // Menu items for admin
        $menuItems = $this->getAdminMenuItems($meetingStats, $memberStats, $loanStats);

        return [
            'user_role' => 'admin',
            'user_position' => $userPosition,
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone_number,
                'vsla_position' => $userPosition,
            ],
            'group_info' => [
                'id' => $group->id,
                'name' => $group->name,
                'code' => $group->code,
                'total_members' => $memberStats['total_members'],
                'active_members' => $memberStats['active_members'],
            ],
            'cycle_info' => $cycleInfo,
            'financial_summary' => $financialSummary,
            'meeting_stats' => $meetingStats,
            'loan_stats' => $loanStats,
            'member_stats' => $memberStats,
            'menu_items' => $menuItems,
        ];
    }

    /**
     * Get dashboard data for regular members
     */
    private function getMemberDashboardData(User $user, FfsGroup $group, $cycleId, $cycleInfo): array
    {
        $groupId = $group->id;
        $userId = $user->id;

        // Personal summary
        $mySummary = $this->getMemberPersonalSummary($userId, $groupId, $cycleId);

        // Limited group summary
        $groupSummary = $this->getLimitedGroupSummary($groupId, $cycleId);

        // Menu items for member
        $menuItems = $this->getMemberMenuItems($mySummary);

        // Get member count for menu
        $totalMembers = User::where('group_id', $groupId)->count();

        return [
            'user_role' => 'member',
            'user_position' => 'member',
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone_number,
                'vsla_position' => 'member',
            ],
            'group_info' => [
                'id' => $group->id,
                'name' => $group->name,
                'code' => $group->code,
                'total_members' => $totalMembers,
                'active_members' => $totalMembers, // Simplified for members
            ],
            'cycle_info' => $cycleInfo,
            'my_summary' => $mySummary,
            'group_summary' => $groupSummary,
            'menu_items' => $menuItems,
        ];
    }

    /**
     * Get comprehensive financial summary for the group
     */
    private function getGroupFinancialSummary($groupId, $cycleId): array
    {
        $query = AccountTransaction::where('group_id', $groupId);
        if ($cycleId) {
            $query->where('cycle_id', $cycleId);
        }

        // Total savings (group received from members)
        $totalSavings = (clone $query)->where('account_type', 'savings')
            ->where('owner_type', 'group')
            ->sum('amount');

        // Total shares value
        $totalSharesValue = (clone $query)->where('account_type', 'share')
            ->where('owner_type', 'group')
            ->sum('amount');

        // Total loans disbursed
        $totalLoansDisbursed = abs((clone $query)->where('account_type', 'loan')
            ->where('owner_type', 'group')
            ->sum('amount'));

        // Total fines collected
        $totalFines = (clone $query)->where('account_type', 'fine')
            ->where('owner_type', 'group')
            ->sum('amount');

        // Total welfare
        $totalWelfare = (clone $query)->where('account_type', 'welfare')
            ->where('owner_type', 'group')
            ->sum('amount');

        // Total social fund
        $totalSocialFund = (clone $query)->where('account_type', 'social_fund')
            ->where('owner_type', 'group')
            ->sum('amount');

        // Calculate cash at hand (total in - total out)
        $totalIn = $totalSavings + $totalSharesValue + $totalFines + $totalWelfare + $totalSocialFund;
        $totalOut = $totalLoansDisbursed;
        $cashAtHand = $totalIn - $totalOut;

        // Get loans outstanding from VslaLoan
        $loansQuery = VslaLoan::where('status', 'active');
        if ($cycleId) {
            $loansQuery->where('cycle_id', $cycleId);
        }
        $totalLoansOutstanding = $loansQuery->sum('balance');

        return [
            'total_savings' => $totalSavings,
            'total_shares_value' => $totalSharesValue,
            'total_loans_disbursed' => $totalLoansDisbursed,
            'total_loans_outstanding' => $totalLoansOutstanding,
            'total_fines_collected' => $totalFines,
            'total_welfare' => $totalWelfare,
            'total_social_fund' => $totalSocialFund,
            'cash_at_hand' => $cashAtHand,
            'formatted' => [
                'total_savings' => 'UGX ' . number_format($totalSavings, 0),
                'total_shares_value' => 'UGX ' . number_format($totalSharesValue, 0),
                'total_loans_disbursed' => 'UGX ' . number_format($totalLoansDisbursed, 0),
                'total_loans_outstanding' => 'UGX ' . number_format($totalLoansOutstanding, 0),
                'total_fines_collected' => 'UGX ' . number_format($totalFines, 0),
                'total_welfare' => 'UGX ' . number_format($totalWelfare, 0),
                'total_social_fund' => 'UGX ' . number_format($totalSocialFund, 0),
                'cash_at_hand' => 'UGX ' . number_format($cashAtHand, 0),
            ],
        ];
    }

    /**
     * Get meeting statistics
     */
    private function getMeetingStats($groupId, $cycleId): array
    {
        $query = VslaMeeting::where('group_id', $groupId);
        if ($cycleId) {
            $query->where('cycle_id', $cycleId);
        }

        $totalMeetings = $query->count();
        $lastMeeting = (clone $query)->orderBy('meeting_date', 'desc')->first();
        
        // Check for ongoing meeting
        $ongoingMeeting = VslaMeeting::where('group_id', $groupId)
            ->where('processing_status', 'pending')
            ->first();

        return [
            'total_meetings' => $totalMeetings,
            'last_meeting_date' => $lastMeeting ? $lastMeeting->meeting_date : null,
            'next_meeting_date' => null, // TODO: Calculate from cycle settings
            'has_ongoing_meeting' => $ongoingMeeting ? true : false,
            'ongoing_meeting_id' => $ongoingMeeting ? $ongoingMeeting->id : null,
        ];
    }

    /**
     * Get loan statistics
     */
    private function getLoanStats($groupId, $cycleId): array
    {
        $query = VslaLoan::query();
        if ($cycleId) {
            $query->where('cycle_id', $cycleId);
        } else {
            // Filter by group members
            $memberIds = User::where('group_id', $groupId)->pluck('id');
            $query->whereIn('borrower_id', $memberIds);
        }

        $activeLoans = (clone $query)->where('status', 'active')->count();
        $pendingRequests = (clone $query)->where('status', 'pending')->count();
        $disbursedThisCycle = (clone $query)->whereIn('status', ['active', 'completed'])->count();
        
        $totalDisbursed = (clone $query)->whereIn('status', ['active', 'completed'])->sum('loan_amount');
        $totalRepaid = (clone $query)->where('status', 'active')->sum(DB::raw('loan_amount + interest_amount - balance'));

        return [
            'active_loans' => $activeLoans,
            'pending_requests' => $pendingRequests,
            'loans_disbursed_this_cycle' => $disbursedThisCycle,
            'total_disbursed_amount' => $totalDisbursed,
            'total_repaid_amount' => $totalRepaid,
        ];
    }

    /**
     * Get member statistics
     */
    private function getMemberStats($groupId, $cycleId): array
    {
        $totalMembers = User::where('group_id', $groupId)->count();
        $activeMembers = User::where('group_id', $groupId)
            ->where('status', 'Active')
            ->count();
        
        $membersWithSavings = AccountTransaction::where('group_id', $groupId)
            ->where('account_type', 'savings')
            ->where('owner_type', 'member')
            ->when($cycleId, fn($q) => $q->where('cycle_id', $cycleId))
            ->distinct('user_id')
            ->count('user_id');

        $membersWithLoans = VslaLoan::where('status', 'active')
            ->when($cycleId, fn($q) => $q->where('cycle_id', $cycleId))
            ->whereIn('borrower_id', User::where('group_id', $groupId)->pluck('id'))
            ->distinct('borrower_id')
            ->count('borrower_id');

        return [
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'inactive_members' => $totalMembers - $activeMembers,
            'members_with_savings' => $membersWithSavings,
            'members_with_loans' => $membersWithLoans,
        ];
    }

    /**
     * Get personal summary for a member
     */
    private function getMemberPersonalSummary($userId, $groupId, $cycleId): array
    {
        $query = AccountTransaction::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->where('owner_type', 'member');
        
        if ($cycleId) {
            $query->where('cycle_id', $cycleId);
        }

        // My savings (negative because member pays)
        $mySavings = abs((clone $query)->where('account_type', 'savings')->sum('amount'));

        // My shares
        $mySharesQuery = ProjectShare::where('investor_id', $userId);
        if ($cycleId) {
            $mySharesQuery->where('project_id', $cycleId);
        }
        $mySharesCount = $mySharesQuery->sum('number_of_shares');
        $mySharesValue = $mySharesQuery->sum('total_amount_paid');

        // My loans
        $myLoansQuery = VslaLoan::where('borrower_id', $userId)
            ->where('status', 'active');
        if ($cycleId) {
            $myLoansQuery->where('cycle_id', $cycleId);
        }
        $myActiveLoans = $myLoansQuery->count();
        $myLoan = $myLoansQuery->first();
        $myLoanAmount = $myLoan ? $myLoan->loan_amount : 0;
        $myLoanBalance = $myLoan ? $myLoan->balance : 0;

        // My fines
        $myFines = abs((clone $query)->where('account_type', 'fine')->sum('amount'));

        // My welfare
        $myWelfare = abs((clone $query)->where('account_type', 'welfare')->sum('amount'));

        // My social fund
        $mySocialFund = abs((clone $query)->where('account_type', 'social_fund')->sum('amount'));

        // My attendance rate
        $attendanceQuery = VslaMeetingAttendance::where('user_id', $userId);
        if ($cycleId) {
            $meetingIds = VslaMeeting::where('cycle_id', $cycleId)->pluck('id');
            $attendanceQuery->whereIn('meeting_id', $meetingIds);
        }
        $totalAttendance = $attendanceQuery->count();
        $presentCount = (clone $attendanceQuery)->where('status', 'present')->count();
        $attendanceRate = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 0;

        return [
            'my_savings' => $mySavings,
            'my_shares_value' => $mySharesValue,
            'my_shares_count' => $mySharesCount,
            'my_active_loans' => $myActiveLoans,
            'my_loan_amount' => $myLoanAmount,
            'my_loan_balance' => $myLoanBalance,
            'my_fines_paid' => $myFines,
            'my_welfare' => $myWelfare,
            'my_social_fund' => $mySocialFund,
            'my_attendance_rate' => $attendanceRate,
            'formatted' => [
                'my_savings' => 'UGX ' . number_format($mySavings, 0),
                'my_shares_value' => 'UGX ' . number_format($mySharesValue, 0),
                'my_loan_amount' => 'UGX ' . number_format($myLoanAmount, 0),
                'my_loan_balance' => 'UGX ' . number_format($myLoanBalance, 0),
                'my_fines_paid' => 'UGX ' . number_format($myFines, 0),
                'my_welfare' => 'UGX ' . number_format($myWelfare, 0),
                'my_social_fund' => 'UGX ' . number_format($mySocialFund, 0),
            ],
        ];
    }

    /**
     * Get limited group summary for members
     */
    private function getLimitedGroupSummary($groupId, $cycleId): array
    {
        $query = AccountTransaction::where('group_id', $groupId)
            ->where('owner_type', 'group');
        
        if ($cycleId) {
            $query->where('cycle_id', $cycleId);
        }

        $totalSavings = (clone $query)->where('account_type', 'savings')->sum('amount');
        $totalMembers = User::where('group_id', $groupId)->count();

        $totalIn = (clone $query)->where('amount', '>', 0)->sum('amount');
        $totalOut = abs((clone $query)->where('amount', '<', 0)->sum('amount'));
        $cashAtHand = $totalIn - $totalOut;

        return [
            'total_savings' => $totalSavings,
            'total_members' => $totalMembers,
            'cash_at_hand' => $cashAtHand,
            'formatted' => [
                'total_savings' => 'UGX ' . number_format($totalSavings, 0),
                'cash_at_hand' => 'UGX ' . number_format($cashAtHand, 0),
            ],
        ];
    }

    /**
     * Get menu items for admin users
     */
    private function getAdminMenuItems($meetingStats, $memberStats, $loanStats): array
    {
        return [
            [
                'id' => 'create_meeting',
                'title' => $meetingStats['has_ongoing_meeting'] ? 'Continue Meeting' : 'Create Meeting',
                'icon' => 'calendar_today',
                'route' => '/vsla/meetings/hub',
                'visible' => true,
                'enabled' => true,
                'badge' => null,
            ],
            [
                'id' => 'meetings',
                'title' => 'Meetings',
                'icon' => 'event',
                'route' => '/vsla/meetings',
                'visible' => true,
                'enabled' => true,
                'badge' => (string) $meetingStats['total_meetings'],
            ],
            [
                'id' => 'attendance',
                'title' => 'Attendance',
                'icon' => 'how_to_reg',
                'route' => '/vsla/attendance',
                'visible' => true,
                'enabled' => false, // Coming soon
                'badge' => null,
            ],
            [
                'id' => 'shares',
                'title' => 'Shares',
                'icon' => 'pie_chart',
                'route' => '/vsla/shares',
                'visible' => true,
                'enabled' => false, // Coming soon
                'badge' => null,
            ],
            [
                'id' => 'loans',
                'title' => 'Loans',
                'icon' => 'account_balance',
                'route' => '/vsla/loans',
                'visible' => true,
                'enabled' => true,
                'badge' => (string) $loanStats['active_loans'],
            ],
            [
                'id' => 'loan_transactions',
                'title' => 'Loan Transactions',
                'icon' => 'receipt_long',
                'route' => '/vsla/loan-transactions',
                'visible' => true,
                'enabled' => false, // Coming soon
                'badge' => null,
            ],
            [
                'id' => 'action_plans',
                'title' => 'Action Plans',
                'icon' => 'assignment',
                'route' => '/vsla/action-plans',
                'visible' => true,
                'enabled' => false, // Coming soon
                'badge' => null,
            ],
            [
                'id' => 'members',
                'title' => 'Members',
                'icon' => 'people',
                'route' => '/vsla/members',
                'visible' => true,
                'enabled' => true,
                'badge' => (string) $memberStats['total_members'],
            ],
            [
                'id' => 'group_report',
                'title' => 'Group Report',
                'icon' => 'assessment',
                'route' => '/vsla/reports',
                'visible' => true,
                'enabled' => false, // Coming soon
                'badge' => null,
            ],
            [
                'id' => 'configurations',
                'title' => 'Configurations',
                'icon' => 'settings',
                'route' => '/vsla/settings',
                'visible' => true,
                'enabled' => false, // Coming soon
                'badge' => null,
            ],
        ];
    }

    /**
     * Get menu items for regular members
     */
    private function getMemberMenuItems($mySummary): array
    {
        return [
            [
                'id' => 'attendance',
                'title' => 'My Attendance',
                'icon' => 'how_to_reg',
                'route' => '/vsla/my-attendance',
                'visible' => true,
                'enabled' => false, // Coming soon
                'badge' => null,
            ],
            [
                'id' => 'shares',
                'title' => 'My Shares',
                'icon' => 'pie_chart',
                'route' => '/vsla/my-shares',
                'visible' => true,
                'enabled' => false, // Coming soon
                'badge' => (string) $mySummary['my_shares_count'],
            ],
            [
                'id' => 'loans',
                'title' => 'My Loans',
                'icon' => 'account_balance',
                'route' => '/vsla/my-loans',
                'visible' => true,
                'enabled' => true,
                'badge' => (string) $mySummary['my_active_loans'],
            ],
            [
                'id' => 'loan_transactions',
                'title' => 'My Loan Transactions',
                'icon' => 'receipt_long',
                'route' => '/vsla/my-loan-transactions',
                'visible' => true,
                'enabled' => false, // Coming soon
                'badge' => null,
            ],
            [
                'id' => 'action_plans',
                'title' => 'Action Plans',
                'icon' => 'assignment',
                'route' => '/vsla/action-plans',
                'visible' => true,
                'enabled' => false, // Coming soon
                'badge' => null,
            ],
            [
                'id' => 'members',
                'title' => 'Members',
                'icon' => 'people',
                'route' => '/vsla/members',
                'visible' => true,
                'enabled' => true,
                'badge' => null,
            ],
            [
                'id' => 'group_report',
                'title' => 'Group Report',
                'icon' => 'assessment',
                'route' => '/vsla/reports',
                'visible' => true,
                'enabled' => false, // Coming soon
                'badge' => null,
            ],
        ];
    }
}
