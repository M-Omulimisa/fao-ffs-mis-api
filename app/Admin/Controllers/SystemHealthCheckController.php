<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\FfsGroup;
use App\Models\ImplementingPartner;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemHealthCheckController extends AdminController
{
    use IpScopeable;

    protected $title = 'System Health Check';

    private $ipId;
    private $isSuperAdmin;
    private $filterIpId = null;

    /**
     * Initialize IP scoping context.
     * Super admins can optionally filter by a specific IP via ?filter_ip_id=
     */
    private function initContext()
    {
        $this->ipId = $this->getAdminIpId();
        $this->isSuperAdmin = $this->isSuperAdmin();

        // Allow super admins to filter by a specific IP
        if ($this->isSuperAdmin && request()->filled('filter_ip_id')) {
            $this->filterIpId = (int) request('filter_ip_id');
        }
    }

    /**
     * Scope a query by IP — respects the optional super-admin filter.
     * Overrides the trait's scopeQuery when a filter is active.
     */
    private function applyScopeQuery($query)
    {
        if ($this->filterIpId) {
            $query->where('ip_id', $this->filterIpId);
        } elseif ($this->ipId) {
            $query->where('ip_id', $this->ipId);
        }
        return $query;
    }

    /**
     * Get the effective IP id (filter or natural scope).
     */
    private function effectiveIpId()
    {
        return $this->filterIpId ?: $this->ipId;
    }

    /**
     * Whether an IP scope is active (either natural or filtered).
     */
    private function hasIpScope(): bool
    {
        return $this->filterIpId || ($this->ipId && !$this->isSuperAdmin);
    }

    /**
     * Display system health check dashboard
     */
    public function index(Content $content)
    {
        $this->initContext();

        // Collect all checks
        $checks = [
            'groups_similar_names' => $this->checkGroupsSimilarNames(),
            'groups_oversized' => $this->checkGroupsOversized(),
            'groups_empty' => $this->checkGroupsEmpty(),
            'groups_no_facilitator' => $this->checkGroupsNoFacilitator(),
            'duplicate_chairperson' => $this->checkDuplicateChairperson(),
            'duplicate_phone' => $this->checkDuplicatePhoneNumbers(),
            'duplicate_email' => $this->checkDuplicateEmailAddresses(),
            'users_no_ip' => $this->checkUsersNoIp(),
            'orphaned_members' => $this->checkOrphanedMembers(),
            'inactive_groups_with_members' => $this->checkInactiveGroupsWithMembers(),
        ];

        // Calculate summary in single pass
        $summary = collect($checks)->reduce(function($carry, $check) {
            $count = count($check['items']);
            $carry['total_issues'] += $count;
            if ($check['severity'] === 'critical') $carry['critical_issues'] += $count;
            elseif ($check['severity'] === 'warning') $carry['warning_issues'] += $count;
            elseif ($check['severity'] === 'info') $carry['info_issues'] += $count;
            return $carry;
        }, ['total_issues' => 0, 'critical_issues' => 0, 'warning_issues' => 0, 'info_issues' => 0]);

        // Get facilitators for assignment dropdown (users who are assigned as facilitators)
        $facilitatorIds = FfsGroup::whereNotNull('facilitator_id')
            ->distinct()
            ->pluck('facilitator_id');

        // Also include users with field_facilitator role (role_id = 3)
        $facilitatorRoleUserIds = \DB::table('admin_role_users')
            ->where('role_id', 3)
            ->pluck('user_id');

        $allFacilitatorIds = $facilitatorIds->merge($facilitatorRoleUserIds)->unique();

        $facilitators = User::whereIn('id', $allFacilitatorIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Get IPs for assignment dropdown
        $ips = ImplementingPartner::query()
            ->where('status', 'Active')
            ->select('id', 'name', 'short_name')
            ->orderBy('name')
            ->get();

        return $content
            ->title('System Health Check')
            ->description('Monitor data integrity and perform batch operations')
            ->body(view('admin.system-health-check', [
                'checks' => $checks,
                'summary' => $summary,
                'facilitators' => $facilitators,
                'ips' => $ips,
                'filterIpId' => $this->filterIpId,
                'isSuperAdmin' => $this->isSuperAdmin,
            ]));
    }

    // ─────────────────────────────────────────────────────────────
    // AJAX BATCH OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Delete multiple groups
     */
    public function batchDeleteGroups(Request $request)
    {
        $this->initContext();
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No groups selected']);
        }

        try {
            $query = FfsGroup::whereIn('id', $ids);
            if ($this->hasIpScope()) {
                $query->where('ip_id', $this->effectiveIpId());
            }

            $count = $query->count();
            $query->delete();

            return response()->json([
                'success' => true,
                'message' => "{$count} group(s) deleted successfully",
                'deleted' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete multiple users
     */
    public function batchDeleteUsers(Request $request)
    {
        $this->initContext();
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No users selected']);
        }

        try {
            $query = User::whereIn('id', $ids);
            if ($this->hasIpScope()) {
                $query->where('ip_id', $this->effectiveIpId());
            }

            $count = $query->count();
            $query->delete();

            return response()->json([
                'success' => true,
                'message' => "{$count} user(s) deleted successfully",
                'deleted' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete all orphaned members (no group, excluding admins and facilitators)
     */
    public function deleteAllOrphanedMembers(Request $request)
    {
        $this->initContext();

        try {
            // Staff/admin role IDs — must match checkOrphanedMembers()
            $staffRoleIds = [1, 2, 3, 6, 7];

            $staffUserIds = \DB::table('admin_role_users')
                ->whereIn('role_id', $staffRoleIds)
                ->pluck('user_id')
                ->unique();

            $query = User::query()
                ->where(function($q) {
                    $q->whereNull('group_id')->orWhere('group_id', 0);
                });

            if ($staffUserIds->isNotEmpty()) {
                $query->whereNotIn('id', $staffUserIds);
            }

            if ($this->hasIpScope()) {
                $query->where('ip_id', $this->effectiveIpId());
            }

            $count = $query->count();

            if ($count === 0) {
                return response()->json(['success' => true, 'message' => 'No orphaned members found', 'deleted' => 0]);
            }

            $query->delete();

            return response()->json([
                'success' => true,
                'message' => "{$count} orphaned member(s) deleted successfully",
                'deleted' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign facilitator to multiple groups
     */
    public function batchAssignFacilitator(Request $request)
    {
        $this->initContext();
        $ids = $request->input('ids', []);
        $facilitatorId = $request->input('facilitator_id');

        if (empty($ids) || !$facilitatorId) {
            return response()->json(['success' => false, 'message' => 'Missing required fields']);
        }

        $facilitator = User::find($facilitatorId);
        if (!$facilitator) {
            return response()->json(['success' => false, 'message' => 'Facilitator not found']);
        }

        try {
            $query = FfsGroup::whereIn('id', $ids);
            if ($this->hasIpScope()) {
                $query->where('ip_id', $this->effectiveIpId());
            }

            $count = $query->update(['facilitator_id' => $facilitatorId]);

            return response()->json([
                'success' => true,
                'message' => "{$count} group(s) assigned to {$facilitator->name}",
                'updated' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign IP to multiple users
     */
    public function batchAssignIp(Request $request)
    {
        $this->initContext();
        $ids = $request->input('ids', []);
        $ipId = $request->input('ip_id');

        if (empty($ids) || !$ipId) {
            return response()->json(['success' => false, 'message' => 'Missing required fields']);
        }

        $ip = ImplementingPartner::find($ipId);
        if (!$ip) {
            return response()->json(['success' => false, 'message' => 'IP not found']);
        }

        try {
            $count = User::whereIn('id', $ids)->update(['ip_id' => $ipId]);

            return response()->json([
                'success' => true,
                'message' => "{$count} user(s) assigned to {$ip->name}",
                'updated' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Clear duplicate field (phone/email) for selected users
     */
    public function batchClearField(Request $request)
    {
        $this->initContext();
        $ids = $request->input('ids', []);
        $field = $request->input('field');

        if (empty($ids) || !in_array($field, ['phone_number', 'email'])) {
            return response()->json(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $query = User::whereIn('id', $ids);
            if ($this->hasIpScope()) {
                $query->where('ip_id', $this->effectiveIpId());
            }

            $count = $query->update([$field => null]);
            $fieldName = $field === 'phone_number' ? 'phone number' : 'email';

            return response()->json([
                'success' => true,
                'message' => "{$fieldName} cleared for {$count} user(s)",
                'updated' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Activate/Deactivate multiple groups
     */
    public function batchUpdateGroupStatus(Request $request)
    {
        $this->initContext();
        $ids = $request->input('ids', []);
        $status = $request->input('status');

        if (empty($ids) || !in_array($status, ['Active', 'Inactive', 'Suspended'])) {
            return response()->json(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $query = FfsGroup::whereIn('id', $ids);
            if ($this->hasIpScope()) {
                $query->where('ip_id', $this->effectiveIpId());
            }

            $count = $query->update(['status' => $status]);

            return response()->json([
                'success' => true,
                'message' => "{$count} group(s) set to {$status}",
                'updated' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Merge duplicate users (keep first, delete others)
     */
    public function mergeDuplicateUsers(Request $request)
    {
        $this->initContext();
        $ids = $request->input('ids', []);
        $keepId = $request->input('keep_id');

        if (count($ids) < 2 || !$keepId) {
            return response()->json(['success' => false, 'message' => 'Select at least 2 users and specify which to keep']);
        }

        if (!in_array($keepId, $ids)) {
            return response()->json(['success' => false, 'message' => 'Keep ID must be one of the selected users']);
        }

        try {
            DB::beginTransaction();

            $deleteIds = array_diff($ids, [$keepId]);
            $query = User::whereIn('id', $deleteIds);

            if ($this->hasIpScope()) {
                $query->where('ip_id', $this->effectiveIpId());
            }

            $count = $query->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Merged: kept user #{$keepId}, deleted {$count} duplicate(s)",
                'deleted' => $count
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * Resolve accurate IP name and district for a group.
     * Falls back to the relationship data when the stored text columns are empty.
     * If the group still has no IP, attempts auto-fix from created_by or facilitator.
     */
    private function resolveGroupDisplayData(FfsGroup $g): array
    {
        // --- IP resolution ---
        $ipName = null;

        // 1. Try the relationship (ip_id → implementing_partners.name)
        if ($g->ip_id && $g->relationLoaded('implementingPartner') && $g->implementingPartner) {
            $ipName = $g->implementingPartner->name;
        }

        // 2. Fall back to the stored ip_name text column
        if (!$ipName && $g->ip_name) {
            $ipName = $g->ip_name;
        }

        // 3. Auto-fix: no ip_id at all — try to derive from created_by or facilitator
        if (!$g->ip_id) {
            $ipName = $this->autoFixGroupIp($g);
        }

        // --- District resolution ---
        $district = null;

        // 1. Try the relationship (district_id → locations.name)
        if ($g->district_id && $g->relationLoaded('district') && $g->district) {
            $district = $g->district->name;
        }

        // 2. Fall back to the stored district_text column
        if (!$district && $g->district_text) {
            $district = $g->district_text;
        }

        return [
            'ip' => $ipName ?: 'No IP',
            'district' => $district ?: 'N/A',
        ];
    }

    /**
     * Auto-fix a group's missing IP by looking up the user who created or facilitates the group.
     * Returns the resolved IP name (or null).
     */
    private function autoFixGroupIp(FfsGroup $g): ?string
    {
        $resolvedIpId = null;

        // Try 1: created_by_id user's IP
        if ($g->created_by_id) {
            $creatorIpId = DB::table('users')->where('id', $g->created_by_id)->value('ip_id');
            if ($creatorIpId) {
                $resolvedIpId = $creatorIpId;
            }
        }

        // Try 2: facilitator's IP (if creator had no IP)
        if (!$resolvedIpId && $g->facilitator_id) {
            $facilitatorIpId = DB::table('users')->where('id', $g->facilitator_id)->value('ip_id');
            if ($facilitatorIpId) {
                $resolvedIpId = $facilitatorIpId;
            }
        }

        // Try 3: the group admin (chairperson) — any member with is_group_admin = 'Yes'
        if (!$resolvedIpId) {
            $chairIpId = DB::table('users')
                ->where('group_id', $g->id)
                ->where('is_group_admin', 'Yes')
                ->whereNotNull('ip_id')
                ->where('ip_id', '>', 0)
                ->value('ip_id');
            if ($chairIpId) {
                $resolvedIpId = $chairIpId;
            }
        }

        if ($resolvedIpId) {
            // Persist the fix
            DB::table('ffs_groups')->where('id', $g->id)->update(['ip_id' => $resolvedIpId]);

            // Also update ip_name text column for future reads
            $ipName = DB::table('implementing_partners')->where('id', $resolvedIpId)->value('name');
            if ($ipName) {
                DB::table('ffs_groups')->where('id', $g->id)->update(['ip_name' => $ipName]);
            }

            return $ipName;
        }

        return null;
    }

    /**
     * Build the standard group data array used by all check item views.
     */
    private function buildGroupItem(FfsGroup $g, array $extraFields = []): array
    {
        $display = $this->resolveGroupDisplayData($g);

        return array_merge([
            'id' => $g->id,
            'name' => $g->name,
            'type' => $g->type,
            'ip' => $display['ip'],
            'district' => $display['district'],
        ], $extraFields);
    }

    // ─────────────────────────────────────────────────────────────
    // CHECK FUNCTIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Check for groups with similar names
     */
    private function checkGroupsSimilarNames()
    {
        $query = FfsGroup::query()
            ->with(['implementingPartner:id,name', 'district:id,name'])
            ->withCount('members');
        $query = $this->applyScopeQuery($query);

        $groups = $query->get()
            ->groupBy(fn($g) => soundex($g->name))
            ->filter(fn($g) => count($g) > 1);

        $items = $groups->map(fn($soundexGroup) => [
            'title' => 'Similar Group Names Detected',
            'groups' => $soundexGroup->map(fn($g) => $this->buildGroupItem($g, [
                'members' => $g->members_count,
            ]))->values()->toArray(),
            'action' => 'review',
        ])->values()->toArray();

        return [
            'title' => 'Groups with Similar Names',
            'description' => 'Groups that may be duplicates or have confusingly similar names (phonetically similar)',
            'severity' => 'warning',
            'icon' => 'fa-copy',
            'color' => 'warning',
            'items' => $items,
            'entity' => 'group',
        ];
    }

    /**
     * Check for groups with more than 35 members
     */
    private function checkGroupsOversized()
    {
        $query = FfsGroup::query()
            ->with(['implementingPartner:id,name', 'district:id,name'])
            ->withCount('members');
        $query = $this->applyScopeQuery($query);

        $items = $query->get()
            ->filter(fn($g) => $g->members_count > 35)
            ->sortByDesc('members_count')
            ->map(fn($g) => $this->buildGroupItem($g, [
                'members' => $g->members_count,
            ]))->values()->toArray();

        return [
            'title' => 'Oversized Groups (35+ Members)',
            'description' => 'VSLA best practice recommends max 30 members per group. Oversized groups may need splitting.',
            'severity' => 'info',
            'icon' => 'fa-warning',
            'color' => 'info',
            'items' => $items,
            'entity' => 'group',
        ];
    }

    /**
     * Check for groups with no members
     */
    private function checkGroupsEmpty()
    {
        $query = FfsGroup::query()->withCount('members');
        $query = $this->applyScopeQuery($query);

        $items = $query->get()
            ->filter(fn($g) => $g->members_count === 0)
            ->sortBy('name')
            ->map(fn($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'type' => $g->type,
                'status' => $g->status,
                'members' => $g->members_count,
                'registration_date' => $g->registration_date?->format('Y-m-d'),
                'ip' => $g->ip_name,
            ])->values()->toArray();

        return [
            'title' => 'Empty Groups (No Members)',
            'description' => 'Groups with no registered members. These may be incomplete registrations or inactive groups.',
            'severity' => 'critical',
            'icon' => 'fa-users-slash',
            'color' => 'danger',
            'items' => $items,
            'entity' => 'group',
        ];
    }

    /**
     * Check for groups with no facilitator assigned
     */
    private function checkGroupsNoFacilitator()
    {
        $query = FfsGroup::query()
            ->withCount('members')
            ->whereNull('facilitator_id')
            ->orderBy('name');

        $query = $this->applyScopeQuery($query);

        $items = $query->get()
            ->map(fn($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'type' => $g->type,
                'members' => $g->members_count,
                'status' => $g->status,
                'ip' => $g->ip_name,
            ])->toArray();

        return [
            'title' => 'Groups Without Facilitator',
            'description' => 'Groups that do not have a facilitator assigned. Every group must have a facilitator.',
            'severity' => 'critical',
            'icon' => 'fa-user-slash',
            'color' => 'danger',
            'items' => $items,
            'entity' => 'group',
        ];
    }

    /**
     * Check for same chairperson assigned to multiple groups
     */
    private function checkDuplicateChairperson()
    {
        return $this->checkDuplicateField(
            'phone_number',
            ['is_group_admin' => 'Yes'],
            'Duplicate Chairperson (Same Phone)',
            'Same phone number assigned as chairperson to multiple groups. Each chairperson should manage only one group.',
            'critical',
            'fa-id-badge',
            'danger'
        );
    }

    /**
     * Check for duplicate phone numbers across users
     */
    private function checkDuplicatePhoneNumbers()
    {
        return $this->checkDuplicateField(
            'phone_number',
            [],
            'Duplicate Phone Numbers',
            'Multiple users with the same phone number. Phone numbers should be unique across the system.',
            'critical',
            'fa-phone',
            'danger'
        );
    }

    /**
     * Check for duplicate email addresses across users
     */
    private function checkDuplicateEmailAddresses()
    {
        return $this->checkDuplicateField(
            'email',
            [],
            'Duplicate Email Addresses',
            'Multiple users with the same email address. Email addresses should be unique across the system.',
            'warning',
            'fa-envelope',
            'warning'
        );
    }

    /**
     * Generic duplicate field checker
     */
    private function checkDuplicateField($field, $filters, $title, $description, $severity, $icon, $color)
    {
        $duplicateValues = User::query()
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->when(!empty($filters), fn($q) => $q->where($filters))
            ->when($this->hasIpScope(), fn($q) => $q->where('ip_id', $this->effectiveIpId()))
            ->groupBy($field)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($field);

        if ($duplicateValues->isEmpty()) {
            return compact('title', 'description', 'severity', 'icon', 'color') + ['items' => [], 'entity' => 'user'];
        }

        $users = User::query()
            ->whereIn($field, $duplicateValues)
            ->select('id', 'name', $field, 'email', 'phone_number', 'group_id')
            ->with(['group' => fn($q) => $q->select('id', 'name')])
            ->when($this->hasIpScope(), fn($q) => $q->where('ip_id', $this->effectiveIpId()))
            ->get()
            ->groupBy($field);

        $items = $users->map(fn($groupedUsers, $key) => [
            $field === 'email' ? 'email' : 'phone' => $key,
            'users' => $groupedUsers->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'group' => $u->group?->name,
                'group_id' => $u->group_id ?? null,
                'email' => $u->email,
                'phone' => $u->phone_number,
            ])->values()->toArray(),
        ])->values()->toArray();

        return compact('title', 'description', 'severity', 'icon', 'color', 'items') + ['entity' => 'user'];
    }

    /**
     * Check for users with no assigned IP
     */
    private function checkUsersNoIp()
    {
        $query = User::query()
            ->where(function($q) {
                $q->whereNull('ip_id')->orWhere('ip_id', 0);
            })
            ->select('id', 'name', 'email', 'phone_number', 'group_id')
            ->with(['group' => fn($q) => $q->select('id', 'name')])
            ->orderBy('name');

        if ($this->hasIpScope()) {
            $query->whereIn('group_id', FfsGroup::where('ip_id', $this->effectiveIpId())->pluck('id'));
        }

        $items = $query->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'group' => $u->group?->name,
                'group_id' => $u->group_id,
                'email' => $u->email,
                'phone' => $u->phone_number,
            ])->toArray();

        return [
            'title' => 'Users Without Implementing Partner',
            'description' => 'All users must be assigned to an Implementing Partner (IP). This is mandatory for system scoping.',
            'severity' => 'critical',
            'icon' => 'fa-building',
            'color' => 'danger',
            'items' => $items,
            'entity' => 'user',
        ];
    }

    /**
     * Check for orphaned members
     */
    private function checkOrphanedMembers()
    {
        // Staff/admin role IDs that should never appear as orphaned members:
        // 1 = Super Admin, 2 = IP Manager, 3 = Field Facilitator,
        // 6 = M&E Officer, 7 = Content Manager
        $staffRoleIds = [1, 2, 3, 6, 7];

        // Get user IDs that have any staff/admin role
        $staffUserIds = \DB::table('admin_role_users')
            ->whereIn('role_id', $staffRoleIds)
            ->pluck('user_id')
            ->unique();

        $query = User::query()
            ->where(function($q) {
                $q->whereNull('group_id')->orWhere('group_id', 0);
            })
            // Exclude users with staff/admin roles
            ->when($staffUserIds->isNotEmpty(), function($q) use ($staffUserIds) {
                $q->whereNotIn('id', $staffUserIds);
            })
            ->select('id', 'name', 'email', 'phone_number', 'ip_id')
            ->orderBy('name');

        $query = $this->applyScopeQuery($query);

        $items = $query->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone_number,
                'ip' => $u->ip_id,
            ])->toArray();

        return [
            'title' => 'Orphaned Members (No Group)',
            'description' => 'Members that are not assigned to any group. Most members should belong to a group.',
            'severity' => 'warning',
            'icon' => 'fa-person-hiking',
            'color' => 'warning',
            'items' => $items,
            'entity' => 'user',
        ];
    }

    /**
     * Check for inactive groups with members
     */
    private function checkInactiveGroupsWithMembers()
    {
        $query = FfsGroup::query()
            ->withCount('members')
            ->where('status', '!=', 'Active');

        $query = $this->applyScopeQuery($query);

        $items = $query->get()
            ->filter(fn($g) => $g->members_count > 0)
            ->sortBy('name')
            ->map(fn($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'type' => $g->type,
                'status' => $g->status,
                'members' => $g->members_count,
                'ip' => $g->ip_name,
            ])->values()->toArray();

        return [
            'title' => 'Inactive Groups with Members',
            'description' => 'Groups marked as inactive but still have members. Consider deactivating members or reactivating the group.',
            'severity' => 'info',
            'icon' => 'fa-circle-pause',
            'color' => 'info',
            'items' => $items,
            'entity' => 'group',
        ];
    }
}
