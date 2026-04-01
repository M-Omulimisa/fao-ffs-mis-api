<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FfsGroup;
use App\Models\User;
use App\Models\Utils;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * VSLA Group Management Controller
 *
 * Provides destructive management operations for groups and members:
 *
 *   Facilitator:
 *     - Delete group (preview + execute + email)
 *     - Set member password
 *
 *   Chairperson:
 *     - Delete member (preview + execute + email)
 *     - Reset member password
 */
class VslaGroupManagementController extends Controller
{
    use ApiResponser;

    // =====================================================================
    // 1. FACILITATOR: DELETE GROUP
    // =====================================================================

    /**
     * GET /api/agent-vsla/my-groups/{id}/delete-preview
     *
     * Returns a summary of what will be deleted if the group is removed.
     * Only the facilitator who created the group or a super admin can call this.
     */
    public function deleteGroupPreview($groupId)
    {
        try {
            $user = $this->getAuthUser();
            if (!$user) return $this->error('Unauthorized', 401);

            $group = FfsGroup::find($groupId);
            if (!$group) return $this->error('Group not found', 404);

            if (!$this->canDeleteGroup($user, $group)) {
                return $this->error('Only the facilitator who created this group or a super admin can delete it', 403);
            }

            $counts = $this->countGroupData($groupId);

            $members = DB::table('users')
                ->where('group_id', $groupId)
                ->select('id', 'name', 'first_name', 'last_name', 'phone_number', 'email', 'member_code')
                ->get()
                ->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'name' => $m->name ?: trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? '')),
                        'phone' => $m->phone_number,
                        'email' => $m->email,
                        'member_code' => $m->member_code,
                    ];
                });

            return $this->success('Delete preview generated', [
                'group_id' => (int) $groupId,
                'group_name' => $group->name,
                'group_code' => $group->code,
                'data_counts' => $counts,
                'total_records' => array_sum($counts),
                'members' => $members,
                'members_count' => $members->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to generate preview: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/agent-vsla/my-groups/{id}/delete
     *
     * Permanently deletes the group and ALL related data.
     * Requires: confirmation_text = group name (exact match).
     * Sends email notification to the facilitator.
     */
    public function deleteGroup(Request $request, $groupId)
    {
        try {
            $user = $this->getAuthUser();
            if (!$user) return $this->error('Unauthorized', 401);

            $group = FfsGroup::find($groupId);
            if (!$group) return $this->error('Group not found', 404);

            if (!$this->canDeleteGroup($user, $group)) {
                return $this->error('Only the facilitator who created this group or a super admin can delete it', 403);
            }

            // Require exact group name as confirmation
            $confirmationText = trim($request->input('confirmation_text', ''));
            if (strtolower($confirmationText) !== strtolower(trim($group->name))) {
                return $this->error('You must type the exact group name "' . $group->name . '" to confirm deletion', 422);
            }

            // Snapshot data for email BEFORE deletion
            $groupSnapshot = [
                'name' => $group->name,
                'code' => $group->code,
                'district' => $group->district_text,
                'village' => $group->village,
            ];
            $membersSnapshot = DB::table('users')
                ->where('group_id', $groupId)
                ->select('id', 'name', 'first_name', 'last_name', 'phone_number', 'email', 'member_code')
                ->get();
            $dataCounts = $this->countGroupData($groupId);

            Log::warning("GROUP DELETE INITIATED: group #{$groupId} ({$group->name}) by user #{$user->id} ({$user->name})");

            DB::beginTransaction();

            $deleteCounts = $this->performGroupDelete($groupId);

            DB::commit();

            Log::warning("GROUP DELETE COMPLETED: group #{$groupId} — " . json_encode($deleteCounts));

            // Send email notification to facilitator
            $this->sendGroupDeletedEmail($user, $groupSnapshot, $membersSnapshot, $dataCounts);

            return $this->success('Group deleted successfully', [
                'group_name' => $groupSnapshot['name'],
                'deleted_counts' => $deleteCounts,
                'message' => 'The group "' . $groupSnapshot['name'] . '" and all its data have been permanently deleted.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("GROUP DELETE FAILED: group #{$groupId}: " . $e->getMessage());
            return $this->error('Delete failed — no data was changed. Error: ' . $e->getMessage(), 500);
        }
    }

    // =====================================================================
    // 2. CHAIRPERSON: DELETE MEMBER
    // =====================================================================

    /**
     * GET /api/vsla/groups/{group_id}/members/{member_id}/delete-preview
     */
    public function deleteMemberPreview($groupId, $memberId)
    {
        try {
            $user = $this->getAuthUser();
            if (!$user) return $this->error('Unauthorized', 401);

            $group = FfsGroup::find($groupId);
            if (!$group) return $this->error('Group not found', 404);

            $member = User::where('id', $memberId)->where('group_id', $groupId)->first();
            if (!$member) return $this->error('Member not found in this group', 404);

            if (!$this->canManageMembers($user, $group)) {
                return $this->error('Only the group chairperson or a super admin can delete members', 403);
            }

            // Prevent deleting the chairperson
            if ((int) $member->id === (int) $group->admin_id) {
                return $this->error('Cannot delete the group chairperson. Transfer chairperson role first.', 422);
            }

            $counts = $this->countMemberData($memberId, $groupId);

            $memberName = $member->name ?: trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));

            return $this->success('Member delete preview generated', [
                'group_id' => (int) $groupId,
                'member_id' => (int) $memberId,
                'member_name' => $memberName,
                'member_code' => $member->member_code,
                'member_phone' => $member->phone_number,
                'member_email' => $member->email,
                'data_counts' => $counts,
                'total_records' => array_sum($counts),
                'confirmation_name' => $memberName,
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to generate preview: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vsla/groups/{group_id}/members/{member_id}/delete
     *
     * Requires: confirmation_text = member's full name.
     */
    public function deleteMember(Request $request, $groupId, $memberId)
    {
        try {
            $user = $this->getAuthUser();
            if (!$user) return $this->error('Unauthorized', 401);

            $group = FfsGroup::find($groupId);
            if (!$group) return $this->error('Group not found', 404);

            $member = User::where('id', $memberId)->where('group_id', $groupId)->first();
            if (!$member) return $this->error('Member not found in this group', 404);

            if (!$this->canManageMembers($user, $group)) {
                return $this->error('Only the group chairperson or a super admin can delete members', 403);
            }

            if ((int) $member->id === (int) $group->admin_id) {
                return $this->error('Cannot delete the group chairperson. Transfer chairperson role first.', 422);
            }

            $memberName = $member->name ?: trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));

            // Require exact member name as confirmation
            $confirmationText = trim($request->input('confirmation_text', ''));
            if (strtolower($confirmationText) !== strtolower($memberName)) {
                return $this->error('You must type the member\'s name "' . $memberName . '" to confirm deletion', 422);
            }

            // Snapshot for email
            $memberSnapshot = [
                'name' => $memberName,
                'phone' => $member->phone_number,
                'email' => $member->email,
                'member_code' => $member->member_code,
                'balance' => $member->balance,
                'loan_balance' => $member->loan_balance,
            ];
            $dataCounts = $this->countMemberData($memberId, $groupId);

            Log::warning("MEMBER DELETE INITIATED: member #{$memberId} ({$memberName}) from group #{$groupId} by user #{$user->id}");

            DB::beginTransaction();

            $deleteCounts = $this->performMemberDelete($memberId, $groupId);

            DB::commit();

            Log::warning("MEMBER DELETE COMPLETED: member #{$memberId} — " . json_encode($deleteCounts));

            // Send email to chairperson
            $this->sendMemberDeletedEmail($user, $group, $memberSnapshot, $dataCounts);

            return $this->success('Member deleted successfully', [
                'member_name' => $memberSnapshot['name'],
                'deleted_counts' => $deleteCounts,
                'message' => '"' . $memberSnapshot['name'] . '" has been permanently removed from the group.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("MEMBER DELETE FAILED: member #{$memberId}: " . $e->getMessage());
            return $this->error('Delete failed — no data was changed. Error: ' . $e->getMessage(), 500);
        }
    }

    // =====================================================================
    // 3. CHAIRPERSON: RESET MEMBER PASSWORD
    // =====================================================================

    /**
     * POST /api/vsla/groups/{group_id}/members/{member_id}/reset-password
     *
     * Resets the member's password to a new one and sends email.
     * Requires: confirmation_text = member's full name.
     */
    public function resetMemberPassword(Request $request, $groupId, $memberId)
    {
        try {
            $user = $this->getAuthUser();
            if (!$user) return $this->error('Unauthorized', 401);

            $group = FfsGroup::find($groupId);
            if (!$group) return $this->error('Group not found', 404);

            $member = User::where('id', $memberId)->where('group_id', $groupId)->first();
            if (!$member) return $this->error('Member not found in this group', 404);

            if (!$this->canManageMembers($user, $group)) {
                return $this->error('Only the group chairperson or a super admin can reset passwords', 403);
            }

            $memberName = $member->name ?: trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));

            // Require exact member name as confirmation
            $confirmationText = trim($request->input('confirmation_text', ''));
            if (strtolower($confirmationText) !== strtolower($memberName)) {
                return $this->error('You must type the member\'s name "' . $memberName . '" to confirm password reset', 422);
            }

            // Generate new password (phone digits or random 6 digits)
            $newPassword = $request->input('new_password');
            if (empty($newPassword)) {
                $newPassword = !empty($member->phone_number)
                    ? preg_replace('/[^0-9]/', '', $member->phone_number)
                    : str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            }

            $member->password = Hash::make($newPassword);
            $member->save();

            Log::info("PASSWORD RESET: member #{$memberId} ({$memberName}) by user #{$user->id} (chairperson)");

            // Send email to member
            $emailSent = false;
            try {
                Utils::send_credentials_email($member, $newPassword, 'Group Member');
                $emailSent = true;
            } catch (\Exception $e) {
                Log::warning("Password reset email failed for member #{$memberId}: " . $e->getMessage());
            }

            return $this->success('Password reset successfully', [
                'member_id' => (int) $memberId,
                'member_name' => $memberName,
                'email_sent' => $emailSent,
                'message' => 'Password has been reset for "' . $memberName . '".'
                    . ($emailSent ? ' Login credentials have been emailed.' : ' No valid email on file — please share the new password manually.'),
            ]);
        } catch (\Exception $e) {
            Log::error("PASSWORD RESET FAILED: member #{$memberId}: " . $e->getMessage());
            return $this->error('Password reset failed: ' . $e->getMessage(), 500);
        }
    }

    // =====================================================================
    // 4. FACILITATOR: SET MEMBER PASSWORD
    // =====================================================================

    /**
     * POST /api/agent-vsla/my-groups/{group_id}/members/{member_id}/set-password
     *
     * Sets a member's password. Only the group's facilitator or super admin.
     * Requires: confirmation_text = member's full name.
     */
    public function setMemberPassword(Request $request, $groupId, $memberId)
    {
        try {
            $user = $this->getAuthUser();
            if (!$user) return $this->error('Unauthorized', 401);

            $group = FfsGroup::find($groupId);
            if (!$group) return $this->error('Group not found', 404);

            if (!$this->canDeleteGroup($user, $group)) {
                return $this->error('Only the facilitator of this group or a super admin can set member passwords', 403);
            }

            $member = User::where('id', $memberId)->where('group_id', $groupId)->first();
            if (!$member) return $this->error('Member not found in this group', 404);

            $memberName = $member->name ?: trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));

            // Require exact member name as confirmation
            $confirmationText = trim($request->input('confirmation_text', ''));
            if (strtolower($confirmationText) !== strtolower($memberName)) {
                return $this->error('You must type the member\'s name "' . $memberName . '" to confirm', 422);
            }

            $newPassword = $request->input('new_password');
            if (empty($newPassword)) {
                $newPassword = !empty($member->phone_number)
                    ? preg_replace('/[^0-9]/', '', $member->phone_number)
                    : str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            }

            $member->password = Hash::make($newPassword);
            $member->save();

            Log::info("PASSWORD SET: member #{$memberId} ({$memberName}) by facilitator #{$user->id}");

            $emailSent = false;
            try {
                Utils::send_credentials_email($member, $newPassword, 'Group Member');
                $emailSent = true;
            } catch (\Exception $e) {
                Log::warning("Password set email failed for member #{$memberId}: " . $e->getMessage());
            }

            return $this->success('Password set successfully', [
                'member_id' => (int) $memberId,
                'member_name' => $memberName,
                'email_sent' => $emailSent,
                'message' => 'Password has been set for "' . $memberName . '".'
                    . ($emailSent ? ' Login credentials have been emailed.' : ' No valid email on file — please share the new password manually.'),
            ]);
        } catch (\Exception $e) {
            Log::error("PASSWORD SET FAILED: member #{$memberId}: " . $e->getMessage());
            return $this->error('Password set failed: ' . $e->getMessage(), 500);
        }
    }

    // =====================================================================
    // PRIVATE HELPERS
    // =====================================================================

    private function getAuthUser()
    {
        return auth('api')->user() ?: Auth::user();
    }

    private function isSuperAdmin($user): bool
    {
        return DB::table('admin_role_users')
            ->join('admin_roles', 'admin_roles.id', '=', 'admin_role_users.role_id')
            ->where('admin_role_users.user_id', $user->id)
            ->where('admin_roles.slug', 'super_admin')
            ->exists();
    }

    private function canDeleteGroup($user, $group): bool
    {
        // Facilitator who created the group or super admin
        return (int) $group->facilitator_id === (int) $user->id
            || $this->isSuperAdmin($user);
    }

    private function canManageMembers($user, $group): bool
    {
        // Chairperson or super admin
        return (int) $group->admin_id === (int) $user->id
            || $this->isSuperAdmin($user);
    }

    /**
     * Count all records attached to a group for preview.
     */
    private function countGroupData(int $groupId): array
    {
        $cycleIds = DB::table('projects')->where('group_id', $groupId)->pluck('id');
        $meetingIds = DB::table('vsla_meetings')->where('group_id', $groupId)->pluck('id');
        $loanIds = $cycleIds->isNotEmpty()
            ? DB::table('vsla_loans')->whereIn('cycle_id', $cycleIds)->pluck('id') : collect();
        $shareoutIds = DB::table('vsla_shareouts')->where('group_id', $groupId)->pluck('id');
        $obIds = DB::table('vsla_opening_balances')->where('group_id', $groupId)->pluck('id');
        $aesaIds = DB::table('aesa_sessions')->where('group_id', $groupId)->pluck('id');

        return [
            'members' => DB::table('users')->where('group_id', $groupId)->count(),
            'cycles' => $cycleIds->count(),
            'meetings' => $meetingIds->count(),
            'meeting_attendance' => $meetingIds->isNotEmpty()
                ? DB::table('vsla_meeting_attendance')->whereIn('meeting_id', $meetingIds)->count() : 0,
            'loans' => $loanIds->count(),
            'loan_transactions' => $loanIds->isNotEmpty()
                ? DB::table('loan_transactions')->whereIn('loan_id', $loanIds)->count() : 0,
            'project_transactions' => $cycleIds->isNotEmpty()
                ? DB::table('project_transactions')->whereIn('project_id', $cycleIds)->count() : 0,
            'project_shares' => $cycleIds->isNotEmpty()
                ? DB::table('project_shares')->whereIn('project_id', $cycleIds)->count() : 0,
            'shareouts' => $shareoutIds->count(),
            'shareout_distributions' => $shareoutIds->isNotEmpty()
                ? DB::table('vsla_shareout_distributions')->whereIn('shareout_id', $shareoutIds)->count() : 0,
            'opening_balances' => $obIds->count(),
            'opening_balance_members' => $obIds->isNotEmpty()
                ? DB::table('vsla_opening_balance_members')->whereIn('opening_balance_id', $obIds)->count() : 0,
            'social_fund_transactions' => DB::table('social_fund_transactions')->where('group_id', $groupId)->count(),
            'account_transactions' => DB::table('account_transactions')->where('group_id', $groupId)->count(),
            'action_plans' => ($meetingIds->isNotEmpty()
                ? DB::table('vsla_action_plans')->whereIn('meeting_id', $meetingIds)->count() : 0)
                + ($cycleIds->isNotEmpty()
                    ? DB::table('vsla_action_plans')->whereIn('cycle_id', $cycleIds)
                        ->when($meetingIds->isNotEmpty(), fn ($q) => $q->whereNotIn('meeting_id', $meetingIds))
                        ->count() : 0),
            'aesa_sessions' => $aesaIds->count(),
            'aesa_observations' => $aesaIds->isNotEmpty()
                ? DB::table('aesa_observations')->whereIn('aesa_session_id', $aesaIds)->count() : 0,
            'vsla_profiles' => DB::table('vsla_profiles')->where('group_id', $groupId)->count(),
        ];
    }

    /**
     * Count all records attached to a single member.
     */
    private function countMemberData(int $memberId, int $groupId): array
    {
        $cycleIds = DB::table('projects')->where('group_id', $groupId)->pluck('id');
        $meetingIds = DB::table('vsla_meetings')->where('group_id', $groupId)->pluck('id');

        return [
            'loans' => $cycleIds->isNotEmpty()
                ? DB::table('vsla_loans')->whereIn('cycle_id', $cycleIds)->where('borrower_id', $memberId)->count() : 0,
            'loan_transactions' => DB::table('loan_transactions')
                ->whereIn('loan_id', function ($q) use ($cycleIds, $memberId) {
                    $q->select('id')->from('vsla_loans')
                        ->whereIn('cycle_id', $cycleIds)->where('borrower_id', $memberId);
                })->count(),
            'project_shares' => $cycleIds->isNotEmpty()
                ? DB::table('project_shares')->whereIn('project_id', $cycleIds)->where('investor_id', $memberId)->count() : 0,
            'project_transactions' => $cycleIds->isNotEmpty()
                ? DB::table('project_transactions')->whereIn('project_id', $cycleIds)
                    ->where('owner_id', $memberId)->where('owner_type', 'member')->count() : 0,
            'social_fund_transactions' => DB::table('social_fund_transactions')
                ->where('group_id', $groupId)->where('member_id', $memberId)->count(),
            'account_transactions' => DB::table('account_transactions')
                ->where('group_id', $groupId)->where('user_id', $memberId)->count(),
            'meeting_attendance' => $meetingIds->isNotEmpty()
                ? DB::table('vsla_meeting_attendance')->whereIn('meeting_id', $meetingIds)->where('member_id', $memberId)->count() : 0,
            'opening_balance_entries' => DB::table('vsla_opening_balance_members')
                ->where('member_id', $memberId)
                ->whereIn('opening_balance_id', function ($q) use ($groupId) {
                    $q->select('id')->from('vsla_opening_balances')->where('group_id', $groupId);
                })->count(),
            'shareout_distributions' => DB::table('vsla_shareout_distributions')
                ->where('member_id', $memberId)
                ->whereIn('shareout_id', function ($q) use ($groupId) {
                    $q->select('id')->from('vsla_shareouts')->where('group_id', $groupId);
                })->count(),
        ];
    }

    /**
     * Delete an entire group and ALL related data.
     * Correct FK order. Returns counts.
     */
    private function performGroupDelete(int $groupId): array
    {
        $counts = [];

        $cycleIds = DB::table('projects')->where('group_id', $groupId)->pluck('id');
        $meetingIds = DB::table('vsla_meetings')->where('group_id', $groupId)->pluck('id');
        $loanIds = $cycleIds->isNotEmpty()
            ? DB::table('vsla_loans')->whereIn('cycle_id', $cycleIds)->pluck('id') : collect();
        $shareoutIds = DB::table('vsla_shareouts')->where('group_id', $groupId)->pluck('id');
        $obIds = DB::table('vsla_opening_balances')->where('group_id', $groupId)->pluck('id');
        $aesaIds = DB::table('aesa_sessions')->where('group_id', $groupId)->pluck('id');

        // Deepest children first
        if ($loanIds->isNotEmpty()) {
            $counts['loan_transactions'] = DB::table('loan_transactions')->whereIn('loan_id', $loanIds)->delete();
        }
        if ($cycleIds->isNotEmpty()) {
            $counts['vsla_loans'] = DB::table('vsla_loans')->whereIn('cycle_id', $cycleIds)->delete();
            $counts['project_transactions'] = DB::table('project_transactions')->whereIn('project_id', $cycleIds)->delete();
            $counts['project_shares'] = DB::table('project_shares')->whereIn('project_id', $cycleIds)->delete();
        }
        if ($shareoutIds->isNotEmpty()) {
            $counts['vsla_shareout_distributions'] = DB::table('vsla_shareout_distributions')->whereIn('shareout_id', $shareoutIds)->delete();
        }
        $counts['vsla_shareouts'] = DB::table('vsla_shareouts')->where('group_id', $groupId)->delete();

        // Action plans
        $ap = 0;
        if ($meetingIds->isNotEmpty()) {
            $ap += DB::table('vsla_action_plans')->whereIn('meeting_id', $meetingIds)->delete();
        }
        if ($cycleIds->isNotEmpty()) {
            $ap += DB::table('vsla_action_plans')->whereIn('cycle_id', $cycleIds)->delete();
        }
        $counts['vsla_action_plans'] = $ap;

        // Meeting attendance & meetings
        if ($meetingIds->isNotEmpty()) {
            $counts['vsla_meeting_attendance'] = DB::table('vsla_meeting_attendance')->whereIn('meeting_id', $meetingIds)->delete();
        }
        $counts['vsla_meetings'] = DB::table('vsla_meetings')->where('group_id', $groupId)->delete();

        // Financial records
        $counts['social_fund_transactions'] = DB::table('social_fund_transactions')->where('group_id', $groupId)->delete();
        $counts['account_transactions'] = DB::table('account_transactions')->where('group_id', $groupId)->delete();

        // Opening balances
        if ($obIds->isNotEmpty()) {
            $counts['vsla_opening_balance_members'] = DB::table('vsla_opening_balance_members')->whereIn('opening_balance_id', $obIds)->delete();
        }
        $counts['vsla_opening_balances'] = DB::table('vsla_opening_balances')->where('group_id', $groupId)->delete();

        // VSLA profiles
        $counts['vsla_profiles'] = DB::table('vsla_profiles')->where('group_id', $groupId)->delete();

        // AESA
        if ($aesaIds->isNotEmpty()) {
            $counts['aesa_observations'] = DB::table('aesa_observations')->whereIn('aesa_session_id', $aesaIds)->delete();
            $counts['aesa_crop_observations'] = DB::table('aesa_crop_observations')->whereIn('aesa_session_id', $aesaIds)->delete();
        }
        $counts['aesa_sessions'] = DB::table('aesa_sessions')->where('group_id', $groupId)->delete();

        // Training session pivot
        $counts['ffs_session_target_groups'] = DB::table('ffs_session_target_groups')->where('group_id', $groupId)->delete();

        // KPI entries
        $counts['ffs_kpi_ip_entries'] = DB::table('ffs_kpi_ip_entries')->where('group_id', $groupId)->delete();
        $counts['ffs_kpi_facilitator_entries'] = DB::table('ffs_kpi_facilitator_entries')->where('group_id', $groupId)->delete();

        // Cycles (projects)
        $counts['cycles'] = DB::table('projects')->where('group_id', $groupId)->delete();

        // Clear group references from leadership BEFORE deleting members
        DB::table('ffs_groups')->where('id', $groupId)->update([
            'admin_id' => null,
            'secretary_id' => null,
            'treasurer_id' => null,
        ]);

        // Delete members (user accounts)
        $counts['members'] = DB::table('users')->where('group_id', $groupId)->delete();

        // Finally delete the group itself
        $counts['group'] = DB::table('ffs_groups')->where('id', $groupId)->delete();

        return $counts;
    }

    /**
     * Delete a single member and all their group-scoped data.
     */
    private function performMemberDelete(int $memberId, int $groupId): array
    {
        $counts = [];

        $cycleIds = DB::table('projects')->where('group_id', $groupId)->pluck('id');
        $meetingIds = DB::table('vsla_meetings')->where('group_id', $groupId)->pluck('id');

        // Loan transactions (child of loans)
        if ($cycleIds->isNotEmpty()) {
            $memberLoanIds = DB::table('vsla_loans')
                ->whereIn('cycle_id', $cycleIds)->where('borrower_id', $memberId)->pluck('id');
            if ($memberLoanIds->isNotEmpty()) {
                $counts['loan_transactions'] = DB::table('loan_transactions')->whereIn('loan_id', $memberLoanIds)->delete();
            }
            $counts['vsla_loans'] = DB::table('vsla_loans')
                ->whereIn('cycle_id', $cycleIds)->where('borrower_id', $memberId)->delete();

            $counts['project_transactions'] = DB::table('project_transactions')
                ->whereIn('project_id', $cycleIds)->where('owner_id', $memberId)->where('owner_type', 'member')->delete();
            $counts['project_shares'] = DB::table('project_shares')
                ->whereIn('project_id', $cycleIds)->where('investor_id', $memberId)->delete();
        }

        // Shareout distributions
        $counts['vsla_shareout_distributions'] = DB::table('vsla_shareout_distributions')
            ->where('member_id', $memberId)
            ->whereIn('shareout_id', function ($q) use ($groupId) {
                $q->select('id')->from('vsla_shareouts')->where('group_id', $groupId);
            })->delete();

        // Social fund
        $counts['social_fund_transactions'] = DB::table('social_fund_transactions')
            ->where('group_id', $groupId)->where('member_id', $memberId)->delete();

        // Account transactions
        $counts['account_transactions'] = DB::table('account_transactions')
            ->where('group_id', $groupId)->where('user_id', $memberId)->delete();

        // Meeting attendance
        if ($meetingIds->isNotEmpty()) {
            $counts['vsla_meeting_attendance'] = DB::table('vsla_meeting_attendance')
                ->whereIn('meeting_id', $meetingIds)->where('member_id', $memberId)->delete();
        }

        // Opening balance members
        $counts['vsla_opening_balance_members'] = DB::table('vsla_opening_balance_members')
            ->where('member_id', $memberId)
            ->whereIn('opening_balance_id', function ($q) use ($groupId) {
                $q->select('id')->from('vsla_opening_balances')->where('group_id', $groupId);
            })->delete();

        // Training session participants
        $counts['ffs_session_participants'] = DB::table('ffs_session_participants')
            ->where('user_id', $memberId)->delete();

        // Clear leadership references if this member is secretary or treasurer
        $group = FfsGroup::find($groupId);
        if ($group) {
            $updates = [];
            if ((int) ($group->secretary_id ?? 0) === $memberId) $updates['secretary_id'] = null;
            if ((int) ($group->treasurer_id ?? 0) === $memberId) $updates['treasurer_id'] = null;
            if (!empty($updates)) {
                DB::table('ffs_groups')->where('id', $groupId)->update($updates);
            }
        }

        // Delete role assignments
        $counts['admin_role_users'] = DB::table('admin_role_users')->where('user_id', $memberId)->delete();

        // Finally delete the user account
        $counts['user_deleted'] = DB::table('users')->where('id', $memberId)->delete();

        return $counts;
    }

    // =====================================================================
    // EMAIL HELPERS
    // =====================================================================

    private function sendGroupDeletedEmail($facilitator, array $groupSnapshot, $members, array $dataCounts)
    {
        try {
            $email = $facilitator->email ?? null;
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) return;

            $facilName = $facilitator->name ?: trim(($facilitator->first_name ?? '') . ' ' . ($facilitator->last_name ?? ''));
            $memberRows = $members->map(function ($m) {
                $name = $m->name ?: trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? ''));
                return "<tr><td style='padding:4px 8px;border:1px solid #e2e8f0;'>" . e($name)
                    . "</td><td style='padding:4px 8px;border:1px solid #e2e8f0;'>" . e($m->phone_number ?? '-')
                    . "</td><td style='padding:4px 8px;border:1px solid #e2e8f0;'>" . e($m->member_code ?? '-')
                    . "</td></tr>";
            })->implode('');

            $dataRows = '';
            foreach ($dataCounts as $key => $val) {
                if ($val > 0) {
                    $label = ucwords(str_replace('_', ' ', $key));
                    $dataRows .= "<tr><td style='padding:4px 8px;border:1px solid #e2e8f0;'>{$label}</td>"
                        . "<td style='padding:4px 8px;border:1px solid #e2e8f0;font-weight:700;'>{$val}</td></tr>";
                }
            }

            $body = "<p>Hi <strong>" . e($facilName) . "</strong>,</p>"
                . "<p>The following VSLA group has been permanently deleted from the system:</p>"
                . "<table style='margin:12px 0;border-collapse:collapse;'>"
                . "<tr><td style='padding:4px 12px 4px 0;color:#64748b;'>Group Name</td><td style='font-weight:700;'>" . e($groupSnapshot['name']) . "</td></tr>"
                . "<tr><td style='padding:4px 12px 4px 0;color:#64748b;'>Group Code</td><td style='font-weight:700;'>" . e($groupSnapshot['code'] ?? '-') . "</td></tr>"
                . "<tr><td style='padding:4px 12px 4px 0;color:#64748b;'>District</td><td>" . e($groupSnapshot['district'] ?? '-') . "</td></tr>"
                . "<tr><td style='padding:4px 12px 4px 0;color:#64748b;'>Village</td><td>" . e($groupSnapshot['village'] ?? '-') . "</td></tr>"
                . "</table>"
                . "<p style='font-weight:700;'>Members ({$members->count()}):</p>"
                . "<table style='border-collapse:collapse;font-size:13px;width:100%;'>"
                . "<thead><tr style='background:#f1f5f9;'><th style='padding:6px 8px;border:1px solid #e2e8f0;text-align:left;'>Name</th>"
                . "<th style='padding:6px 8px;border:1px solid #e2e8f0;text-align:left;'>Phone</th>"
                . "<th style='padding:6px 8px;border:1px solid #e2e8f0;text-align:left;'>Code</th></tr></thead>"
                . "<tbody>{$memberRows}</tbody></table>"
                . ($dataRows ? "<p style='font-weight:700;margin-top:16px;'>Deleted Records:</p>"
                    . "<table style='border-collapse:collapse;font-size:13px;'>"
                    . "<thead><tr style='background:#fef2f2;'><th style='padding:6px 8px;border:1px solid #e2e8f0;text-align:left;'>Category</th>"
                    . "<th style='padding:6px 8px;border:1px solid #e2e8f0;text-align:left;'>Count</th></tr></thead>"
                    . "<tbody>{$dataRows}</tbody></table>" : '')
                . "<p style='color:#94a3b8;font-size:12px;margin-top:20px;'>This action was performed on " . now()->format('d M Y \a\t H:i') . " and cannot be undone.</p>";

            Utils::mail_sender([
                'email' => $email,
                'name' => $facilName,
                'subject' => 'Group Deleted: ' . $groupSnapshot['name'],
                'body' => $body,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to send group deleted email: " . $e->getMessage());
        }
    }

    private function sendMemberDeletedEmail($chairperson, $group, array $memberSnapshot, array $dataCounts)
    {
        try {
            $email = $chairperson->email ?? null;
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) return;

            $chairName = $chairperson->name ?: trim(($chairperson->first_name ?? '') . ' ' . ($chairperson->last_name ?? ''));

            $dataRows = '';
            foreach ($dataCounts as $key => $val) {
                if ($val > 0) {
                    $label = ucwords(str_replace('_', ' ', $key));
                    $dataRows .= "<tr><td style='padding:4px 8px;border:1px solid #e2e8f0;'>{$label}</td>"
                        . "<td style='padding:4px 8px;border:1px solid #e2e8f0;font-weight:700;'>{$val}</td></tr>";
                }
            }

            $body = "<p>Hi <strong>" . e($chairName) . "</strong>,</p>"
                . "<p>A member has been permanently removed from your group <strong>" . e($group->name) . "</strong>:</p>"
                . "<table style='margin:12px 0;border-collapse:collapse;'>"
                . "<tr><td style='padding:4px 12px 4px 0;color:#64748b;'>Member Name</td><td style='font-weight:700;'>" . e($memberSnapshot['name']) . "</td></tr>"
                . "<tr><td style='padding:4px 12px 4px 0;color:#64748b;'>Phone</td><td>" . e($memberSnapshot['phone'] ?? '-') . "</td></tr>"
                . "<tr><td style='padding:4px 12px 4px 0;color:#64748b;'>Email</td><td>" . e($memberSnapshot['email'] ?? '-') . "</td></tr>"
                . "<tr><td style='padding:4px 12px 4px 0;color:#64748b;'>Member Code</td><td>" . e($memberSnapshot['member_code'] ?? '-') . "</td></tr>"
                . "<tr><td style='padding:4px 12px 4px 0;color:#64748b;'>Balance</td><td>" . number_format((float) ($memberSnapshot['balance'] ?? 0)) . " UGX</td></tr>"
                . "<tr><td style='padding:4px 12px 4px 0;color:#64748b;'>Loan Balance</td><td>" . number_format((float) ($memberSnapshot['loan_balance'] ?? 0)) . " UGX</td></tr>"
                . "</table>"
                . ($dataRows ? "<p style='font-weight:700;'>Deleted Records:</p>"
                    . "<table style='border-collapse:collapse;font-size:13px;'><tbody>{$dataRows}</tbody></table>" : '')
                . "<p style='color:#94a3b8;font-size:12px;margin-top:20px;'>Deleted on " . now()->format('d M Y \a\t H:i') . ". This action cannot be undone.</p>";

            Utils::mail_sender([
                'email' => $email,
                'name' => $chairName,
                'subject' => 'Member Removed: ' . $memberSnapshot['name'] . ' from ' . $group->name,
                'body' => $body,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to send member deleted email: " . $e->getMessage());
        }
    }
}
