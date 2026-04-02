<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FfsGroup;
use App\Models\User;
use App\Models\Project;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VSLA Group Reset Controller
 *
 * Allows group chairperson to reset all transactional data while preserving
 * core group structure (members, cycles, group info).
 *
 * DELETES: meetings, attendance, transactions, loans, loan_transactions,
 *          shares, shareouts, distributions, opening balances, action plans,
 *          social fund transactions, account transactions, AESA sessions,
 *          VSLA profiles, KPI entries, training session pivot rows.
 *
 * PRESERVES: ffs_groups row, users (members), projects (cycles).
 * RESETS: member balances to 0, cycle financial totals to 0.
 */
class VslaGroupResetController extends Controller
{
    use ApiResponser;

    /**
     * GET /api/vsla/groups/{group_id}/reset-preview
     *
     * Returns a summary of what will be deleted vs preserved, with counts.
     * This powers the confirmation screen on the mobile app.
     */
    public function resetPreview($groupId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->error('User not authenticated', 401);
            }

            $group = FfsGroup::find($groupId);
            if (!$group) {
                return $this->error('Group not found', 404);
            }

            // User must belong to this group
            if ((int) $user->group_id !== (int) $groupId) {
                return $this->error('You do not belong to this group', 403);
            }

            $cycleIds = DB::table('projects')
                ->where('group_id', $groupId)
                ->where('is_vsla_cycle', 'Yes')
                ->pluck('id');

            $meetingIds = DB::table('vsla_meetings')
                ->where('group_id', $groupId)
                ->pluck('id');

            $loanIds = $cycleIds->isNotEmpty()
                ? DB::table('vsla_loans')->whereIn('cycle_id', $cycleIds)->pluck('id')
                : collect();

            $shareoutIds = DB::table('vsla_shareouts')
                ->where('group_id', $groupId)
                ->pluck('id');

            $openingBalanceIds = DB::table('vsla_opening_balances')
                ->where('group_id', $groupId)
                ->pluck('id');

            $aesaSessionIds = DB::table('aesa_sessions')
                ->where('group_id', $groupId)
                ->pluck('id');

            // Count everything that will be deleted
            $willDelete = [
                'meetings' => $meetingIds->count(),
                'meeting_attendance' => $meetingIds->isNotEmpty()
                    ? DB::table('vsla_meeting_attendance')->whereIn('meeting_id', $meetingIds)->count()
                    : 0,
                'loans' => $loanIds->count(),
                'loan_transactions' => $loanIds->isNotEmpty()
                    ? DB::table('loan_transactions')->whereIn('loan_id', $loanIds)->count()
                    : 0,
                'project_transactions' => $cycleIds->isNotEmpty()
                    ? DB::table('project_transactions')->whereIn('project_id', $cycleIds)->count()
                    : 0,
                'project_shares' => $cycleIds->isNotEmpty()
                    ? DB::table('project_shares')->whereIn('project_id', $cycleIds)->count()
                    : 0,
                'shareouts' => $shareoutIds->count(),
                'shareout_distributions' => $shareoutIds->isNotEmpty()
                    ? DB::table('vsla_shareout_distributions')->whereIn('shareout_id', $shareoutIds)->count()
                    : 0,
                'action_plans' => (
                    ($meetingIds->isNotEmpty()
                        ? DB::table('vsla_action_plans')->whereIn('meeting_id', $meetingIds)->count()
                        : 0)
                    + ($cycleIds->isNotEmpty()
                        ? DB::table('vsla_action_plans')->whereIn('cycle_id', $cycleIds)
                            ->when($meetingIds->isNotEmpty(), fn($q) => $q->whereNotIn('meeting_id', $meetingIds))
                            ->count()
                        : 0)
                ),
                'opening_balances' => $openingBalanceIds->count(),
                'opening_balance_members' => $openingBalanceIds->isNotEmpty()
                    ? DB::table('vsla_opening_balance_members')->whereIn('opening_balance_id', $openingBalanceIds)->count()
                    : 0,
                'social_fund_transactions' => DB::table('social_fund_transactions')->where('group_id', $groupId)->count(),
                'account_transactions' => DB::table('account_transactions')->where('group_id', $groupId)->count(),
                'aesa_sessions' => $aesaSessionIds->count(),
                'aesa_observations' => $aesaSessionIds->isNotEmpty()
                    ? DB::table('aesa_observations')->whereIn('aesa_session_id', $aesaSessionIds)->count()
                    : 0,
                'aesa_crop_observations' => $aesaSessionIds->isNotEmpty()
                    ? DB::table('aesa_crop_observations')->whereIn('aesa_session_id', $aesaSessionIds)->count()
                    : 0,
                'vsla_profiles' => DB::table('vsla_profiles')->where('group_id', $groupId)->count(),
                'training_session_links' => DB::table('ffs_session_target_groups')->where('group_id', $groupId)->count(),
            ];

            $totalRecords = array_sum($willDelete);

            // Count what will be preserved
            $willPreserve = [
                'group_info' => 1,
                'members' => DB::table('users')->where('group_id', $groupId)->count(),
                'cycles' => $cycleIds->count(),
            ];

            // Count what will be reset to zero
            $willReset = [
                'member_balances' => DB::table('users')
                    ->where('group_id', $groupId)
                    ->where(function ($q) {
                        $q->where('balance', '!=', 0)->orWhere('loan_balance', '!=', 0);
                    })
                    ->count(),
                'cycle_totals' => $cycleIds->count(),
            ];

            return $this->success('Reset preview generated', [
                'group_id' => (int) $groupId,
                'group_name' => $group->name,
                'will_delete' => $willDelete,
                'will_preserve' => $willPreserve,
                'will_reset' => $willReset,
                'total_records_to_delete' => $totalRecords,
            ]);
        } catch (\Exception $e) {
            Log::error("VslaGroupResetController::resetPreview failed for group #{$groupId}: " . $e->getMessage());
            return $this->error('Failed to generate reset preview: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vsla/groups/{group_id}/reset
     *
     * Performs the actual reset. Requires confirmation_text = "delete all".
     * Wrapped in a DB transaction so it's all-or-nothing.
     */
    public function resetGroupData(Request $request, $groupId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->error('User not authenticated', 401);
            }

            $group = FfsGroup::find($groupId);
            if (!$group) {
                return $this->error('Group not found', 404);
            }

            // User must belong to this group
            if ((int) $user->group_id !== (int) $groupId) {
                return $this->error('You do not belong to this group', 403);
            }

            // Require explicit confirmation
            $confirmationText = strtolower(trim($request->input('confirmation_text', '')));
            if ($confirmationText !== 'delete all') {
                return $this->error('You must type "delete all" to confirm this action', 422);
            }

            Log::warning("GROUP RESET INITIATED: group #{$groupId} ({$group->name}) by user #{$user->id} ({$user->name})");

            DB::beginTransaction();

            $counts = $this->performReset($groupId);

            DB::commit();

            Log::warning("GROUP RESET COMPLETED: group #{$groupId} — " . json_encode($counts));

            return $this->success('Group data has been reset successfully', [
                'group_id' => (int) $groupId,
                'group_name' => $group->name,
                'deleted_counts' => $counts,
                'message' => 'All transactional data has been removed. Members, cycles, and group info have been preserved. Member balances and cycle totals have been reset to zero.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("GROUP RESET FAILED: group #{$groupId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->error('Reset failed — no data was changed. Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Execute the full cascade delete in correct FK order.
     * Returns an array of [table => deleted_count] for auditing.
     */
    private function performReset(int $groupId): array
    {
        $counts = [];

        // ── 1. Gather IDs ────────────────────────────────────────────────
        $cycleIds = DB::table('projects')
            ->where('group_id', $groupId)
            ->where('is_vsla_cycle', 'Yes')
            ->pluck('id');

        $meetingIds = DB::table('vsla_meetings')
            ->where('group_id', $groupId)
            ->pluck('id');

        $loanIds = $cycleIds->isNotEmpty()
            ? DB::table('vsla_loans')->whereIn('cycle_id', $cycleIds)->pluck('id')
            : collect();

        $shareoutIds = DB::table('vsla_shareouts')
            ->where('group_id', $groupId)
            ->pluck('id');

        $openingBalanceIds = DB::table('vsla_opening_balances')
            ->where('group_id', $groupId)
            ->pluck('id');

        $aesaSessionIds = DB::table('aesa_sessions')
            ->where('group_id', $groupId)
            ->pluck('id');

        // ── 2. Delete deepest children first (respect FK constraints) ────

        // 2a. Loan transactions (child of vsla_loans)
        if ($loanIds->isNotEmpty()) {
            $counts['loan_transactions'] = DB::table('loan_transactions')
                ->whereIn('loan_id', $loanIds)->delete();
        }

        // 2b. Loans (child of cycles)
        if ($cycleIds->isNotEmpty()) {
            $counts['vsla_loans'] = DB::table('vsla_loans')
                ->whereIn('cycle_id', $cycleIds)->delete();
        }

        // 2c. Project transactions (child of cycles)
        if ($cycleIds->isNotEmpty()) {
            $counts['project_transactions'] = DB::table('project_transactions')
                ->whereIn('project_id', $cycleIds)->delete();
        }

        // 2d. Project shares (child of cycles)
        if ($cycleIds->isNotEmpty()) {
            $counts['project_shares'] = DB::table('project_shares')
                ->whereIn('project_id', $cycleIds)->delete();
        }

        // 2e. Shareout distributions (child of shareouts)
        if ($shareoutIds->isNotEmpty()) {
            $counts['vsla_shareout_distributions'] = DB::table('vsla_shareout_distributions')
                ->whereIn('shareout_id', $shareoutIds)->delete();
        }

        // 2f. Shareouts
        $counts['vsla_shareouts'] = DB::table('vsla_shareouts')
            ->where('group_id', $groupId)->delete();

        // 2g. Action plans (linked via meeting_id and/or cycle_id)
        $actionPlanCount = 0;
        if ($meetingIds->isNotEmpty()) {
            $actionPlanCount += DB::table('vsla_action_plans')
                ->whereIn('meeting_id', $meetingIds)->delete();
        }
        if ($cycleIds->isNotEmpty()) {
            $actionPlanCount += DB::table('vsla_action_plans')
                ->whereIn('cycle_id', $cycleIds)->delete();
        }
        $counts['vsla_action_plans'] = $actionPlanCount;

        // 2h. Meeting attendance (child of meetings)
        if ($meetingIds->isNotEmpty()) {
            $counts['vsla_meeting_attendance'] = DB::table('vsla_meeting_attendance')
                ->whereIn('meeting_id', $meetingIds)->delete();
        }

        // 2i. Meetings
        $counts['vsla_meetings'] = DB::table('vsla_meetings')
            ->where('group_id', $groupId)->delete();

        // 2j. Social fund transactions
        $counts['social_fund_transactions'] = DB::table('social_fund_transactions')
            ->where('group_id', $groupId)->delete();

        // 2k. Account transactions
        $counts['account_transactions'] = DB::table('account_transactions')
            ->where('group_id', $groupId)->delete();

        // 2l. Opening balance members (child of opening balances)
        if ($openingBalanceIds->isNotEmpty()) {
            $counts['vsla_opening_balance_members'] = DB::table('vsla_opening_balance_members')
                ->whereIn('opening_balance_id', $openingBalanceIds)->delete();
        }

        // 2m. Opening balances
        $counts['vsla_opening_balances'] = DB::table('vsla_opening_balances')
            ->where('group_id', $groupId)->delete();

        // 2n. VSLA profiles
        $counts['vsla_profiles'] = DB::table('vsla_profiles')
            ->where('group_id', $groupId)->delete();

        // 2o. AESA child tables (observations)
        if ($aesaSessionIds->isNotEmpty()) {
            $counts['aesa_observations'] = DB::table('aesa_observations')
                ->whereIn('aesa_session_id', $aesaSessionIds)->delete();
            $counts['aesa_crop_observations'] = DB::table('aesa_crop_observations')
                ->whereIn('aesa_session_id', $aesaSessionIds)->delete();
        }

        // 2p. AESA sessions
        $counts['aesa_sessions'] = DB::table('aesa_sessions')
            ->where('group_id', $groupId)->delete();

        // 2q. Training session pivot (detach group from sessions)
        $counts['ffs_session_target_groups'] = DB::table('ffs_session_target_groups')
            ->where('group_id', $groupId)->delete();

        // 2r. KPI entries (set null or delete)
        $counts['ffs_kpi_ip_entries'] = DB::table('ffs_kpi_ip_entries')
            ->where('group_id', $groupId)->delete();
        $counts['ffs_kpi_facilitator_entries'] = DB::table('ffs_kpi_facilitator_entries')
            ->where('group_id', $groupId)->delete();

        // ── 3. Reset member balances to 0 ────────────────────────────────
        $counts['members_balance_reset'] = DB::table('users')
            ->where('group_id', $groupId)
            ->update([
                'balance' => 0,
                'loan_balance' => 0,
            ]);

        // ── 4. Reset cycle financial totals to 0 ─────────────────────────
        if ($cycleIds->isNotEmpty()) {
            $counts['cycles_totals_reset'] = DB::table('projects')
                ->whereIn('id', $cycleIds)
                ->update([
                    'total_investment' => 0,
                    'total_returns' => 0,
                    'total_expenses' => 0,
                    'total_profits' => 0,
                    'shares_sold' => 0,
                ]);
        }

        return $counts;
    }
}
