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
            // Protect admin/staff users from deletion
            $adminUserIds = DB::table('admin_role_users')
                ->whereIn('user_id', $ids)
                ->pluck('user_id')
                ->unique()
                ->toArray();

            $deletableIds = array_diff($ids, $adminUserIds);

            if (empty($deletableIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete admin users. All selected users have admin roles.',
                    'skipped' => count($adminUserIds),
                ]);
            }

            $query = User::whereIn('id', $deletableIds);
            if ($this->hasIpScope()) {
                $query->where('ip_id', $this->effectiveIpId());
            }

            $count = $query->count();
            $query->delete();

            $skipped = count($adminUserIds);
            $msg = "{$count} user(s) deleted successfully";
            if ($skipped > 0) {
                $msg .= ". {$skipped} admin user(s) were skipped (protected).";
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'deleted' => $count,
                'skipped' => $skipped,
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

            $update = ['facilitator_id' => $facilitatorId];
            if (!empty($facilitator->ip_id)) {
                $update['ip_id'] = $facilitator->ip_id;
            }

            $count = $query->update($update);

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

            // Protect admin users from deletion
            $adminUserIds = DB::table('admin_role_users')
                ->whereIn('user_id', $deleteIds)
                ->pluck('user_id')
                ->unique()
                ->toArray();

            $safeDeleteIds = array_diff($deleteIds, $adminUserIds);

            if (empty($safeDeleteIds)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete: all selected duplicates have admin roles.',
                ]);
            }

            $query = User::whereIn('id', $safeDeleteIds);

            if ($this->hasIpScope()) {
                $query->where('ip_id', $this->effectiveIpId());
            }

            $count = $query->delete();

            DB::commit();

            $skipped = count($adminUserIds);
            $msg = "Merged: kept user #{$keepId}, deleted {$count} duplicate(s)";
            if ($skipped > 0) {
                $msg .= ". {$skipped} admin user(s) were skipped (protected).";
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'deleted' => $count,
                'skipped' => $skipped,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Mark item(s) as resolved
     */
    public function resolveItem(Request $request)
    {
        $checkKey = $request->input('check_key');
        $entityType = $request->input('entity_type');
        $entityIds = (array) $request->input('entity_ids', []);

        if (!$checkKey || !$entityType || empty($entityIds)) {
            return response()->json(['success' => false, 'message' => 'Missing required fields']);
        }

        $adminId = \Encore\Admin\Facades\Admin::user()->id ?? null;
        $count = 0;

        foreach ($entityIds as $entityId) {
            DB::table('health_check_resolutions')->updateOrInsert(
                ['check_key' => $checkKey, 'entity_type' => $entityType, 'entity_id' => $entityId],
                ['resolved_by' => $adminId, 'updated_at' => now(), 'created_at' => DB::raw('COALESCE(created_at, NOW())')]
            );
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} item(s) marked as resolved",
            'resolved' => $count,
        ]);
    }

    /**
     * Remove resolved status from item(s)
     */
    public function unresolveItem(Request $request)
    {
        $checkKey = $request->input('check_key');
        $entityType = $request->input('entity_type');
        $entityIds = (array) $request->input('entity_ids', []);

        if (!$checkKey || !$entityType || empty($entityIds)) {
            return response()->json(['success' => false, 'message' => 'Missing required fields']);
        }

        $count = DB::table('health_check_resolutions')
            ->where('check_key', $checkKey)
            ->where('entity_type', $entityType)
            ->whereIn('entity_id', $entityIds)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} item(s) unmarked",
            'unresolved' => $count,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // INTELLIGENT AUTO-FIX OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Scan orphaned members and find potential group matches (by phone/name).
     * With apply=1, applies the selected fixes.
     */
    public function autoFixOrphanedMembers(Request $request)
    {
        $this->initContext();
        $apply = $request->boolean('apply');

        // ── Apply mode ──
        if ($apply) {
            $fixes = $request->input('fixes', []);
            if (empty($fixes)) {
                return response()->json(['success' => false, 'message' => 'No fixes selected']);
            }

            DB::beginTransaction();
            try {
                $count = 0;
                foreach ($fixes as $fix) {
                    $update = ['group_id' => $fix['group_id']];
                    if (!empty($fix['ip_id'])) {
                        $update['ip_id'] = $fix['ip_id'];
                    }
                    $count += User::where('id', $fix['user_id'])
                        ->where(function ($q) {
                            $q->whereNull('group_id')->orWhere('group_id', 0);
                        })
                        ->update($update);
                }
                DB::commit();
                return response()->json(['success' => true, 'fixed' => $count, 'message' => "{$count} member(s) assigned to groups"]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        }

        // ── Scan mode ──
        $staffRoleIds = [1, 2, 3, 6, 7];
        $staffUserIds = DB::table('admin_role_users')
            ->whereIn('role_id', $staffRoleIds)
            ->pluck('user_id')
            ->unique();

        $query = User::query()
            ->where(function ($q) {
                $q->whereNull('group_id')->orWhere('group_id', 0);
            })
            ->when($staffUserIds->isNotEmpty(), fn($q) => $q->whereNotIn('id', $staffUserIds))
            ->select('id', 'name', 'phone_number', 'email', 'ip_id');
        $query = $this->applyScopeQuery($query);
        $orphans = $query->get();

        if ($orphans->isEmpty()) {
            return response()->json(['success' => true, 'fixes' => [], 'no_match_count' => 0, 'total' => 0]);
        }

        $orphanIds = $orphans->pluck('id')->toArray();

        // ── Batch phone match (high confidence) ──
        $orphanPhones = $orphans
            ->filter(fn($o) => $o->phone_number && strlen(trim($o->phone_number)) > 5)
            ->pluck('phone_number')
            ->unique()
            ->values();

        $phoneMatchMap = [];
        if ($orphanPhones->isNotEmpty()) {
            $phoneMatches = User::whereIn('phone_number', $orphanPhones)
                ->whereNotIn('id', $orphanIds)
                ->whereNotNull('group_id')
                ->where('group_id', '>', 0)
                ->with('group:id,name,ip_id')
                ->get();

            foreach ($phoneMatches as $m) {
                if ($m->group && !isset($phoneMatchMap[$m->phone_number])) {
                    $phoneMatchMap[$m->phone_number] = $m;
                }
            }
        }

        // ── Batch name match (medium confidence) ──
        $orphanNames = $orphans
            ->filter(fn($o) => $o->name && strlen(trim($o->name)) > 2)
            ->map(fn($o) => strtolower(trim($o->name)))
            ->unique()
            ->values();

        $nameMatchMap = [];
        if ($orphanNames->isNotEmpty()) {
            // Build parameterised IN list
            $placeholders = $orphanNames->map(fn() => '?')->implode(',');
            $nameMatches = User::whereRaw("LOWER(TRIM(name)) IN ({$placeholders})", $orphanNames->toArray())
                ->whereNotIn('id', $orphanIds)
                ->whereNotNull('group_id')
                ->where('group_id', '>', 0)
                ->with('group:id,name,ip_id')
                ->get();

            foreach ($nameMatches as $m) {
                if ($m->group) {
                    $key = strtolower(trim($m->name));
                    if (!isset($nameMatchMap[$key])) {
                        $nameMatchMap[$key] = $m;
                    }
                }
            }
        }

        // ── Build fixes list ──
        $fixes = [];
        foreach ($orphans as $o) {
            // Try phone first
            if ($o->phone_number && isset($phoneMatchMap[$o->phone_number])) {
                $m = $phoneMatchMap[$o->phone_number];
                $fixes[] = [
                    'user_id'           => $o->id,
                    'user_name'         => $o->name,
                    'user_phone'        => $o->phone_number,
                    'match_type'        => 'phone',
                    'matched_user_name' => $m->name,
                    'group_id'          => $m->group_id,
                    'group_name'        => $m->group->name,
                    'ip_id'             => $m->group->ip_id,
                    'confidence'        => 'high',
                ];
                continue;
            }

            // Then name
            $nameKey = strtolower(trim($o->name));
            if (isset($nameMatchMap[$nameKey])) {
                $m = $nameMatchMap[$nameKey];
                $fixes[] = [
                    'user_id'           => $o->id,
                    'user_name'         => $o->name,
                    'user_phone'        => $o->phone_number,
                    'match_type'        => 'name',
                    'matched_user_name' => $m->name,
                    'group_id'          => $m->group_id,
                    'group_name'        => $m->group->name,
                    'ip_id'             => $m->group->ip_id,
                    'confidence'        => 'medium',
                ];
            }
        }

        return response()->json([
            'success'        => true,
            'fixes'          => $fixes,
            'no_match_count' => count($orphans) - count($fixes),
            'total'          => count($orphans),
        ]);
    }

    /**
     * Auto-fix users with no IP by copying IP from their group.
     * With apply=1, executes the fix.
     */
    public function autoFixUsersNoIp(Request $request)
    {
        $this->initContext();
        $apply = $request->boolean('apply');

        $ipScope = '';
        if ($this->hasIpScope()) {
            $ipScope = ' AND g.ip_id = ' . (int) $this->effectiveIpId();
        }

        if ($apply) {
            $fixed = DB::update("
                UPDATE users u
                INNER JOIN ffs_groups g ON u.group_id = g.id
                SET u.ip_id = g.ip_id
                WHERE (u.ip_id IS NULL OR u.ip_id = 0)
                AND u.group_id IS NOT NULL
                AND u.group_id > 0
                AND g.ip_id IS NOT NULL
                AND g.ip_id > 0
                {$ipScope}
            ");

            return response()->json(['success' => true, 'fixed' => $fixed, 'message' => "{$fixed} user(s) assigned IP from their group"]);
        }

        // Scan: count fixable
        $fixable = DB::table('users as u')
            ->join('ffs_groups as g', 'u.group_id', '=', 'g.id')
            ->where(function ($q) {
                $q->whereNull('u.ip_id')->orWhere('u.ip_id', 0);
            })
            ->whereNotNull('u.group_id')
            ->where('u.group_id', '>', 0)
            ->whereNotNull('g.ip_id')
            ->where('g.ip_id', '>', 0)
            ->when($this->hasIpScope(), fn($q) => $q->where('g.ip_id', $this->effectiveIpId()))
            ->count();

        // Count those that cannot be auto-fixed (no group at all)
        $noGroupCount = User::query()
            ->where(function ($q) {
                $q->whereNull('ip_id')->orWhere('ip_id', 0);
            })
            ->where(function ($q) {
                $q->whereNull('group_id')->orWhere('group_id', 0);
            })
            ->count();

        return response()->json([
            'success'   => true,
            'fixable'   => $fixable,
            'unfixable' => $noGroupCount,
            'message'   => "{$fixable} user(s) can be auto-fixed, {$noGroupCount} have no group",
        ]);
    }

    /**
     * Auto-fix groups with no facilitator by assigning the least-loaded facilitator in the same IP.
     * With apply=1, executes the selected fixes.
     */
    public function autoFixGroupsNoFacilitator(Request $request)
    {
        $this->initContext();
        $apply = $request->boolean('apply');

        if ($apply) {
            $fixes = $request->input('fixes', []);
            if (empty($fixes)) {
                return response()->json(['success' => false, 'message' => 'No fixes selected']);
            }

            $count = 0;
            foreach ($fixes as $fix) {
                $facilitator = User::find($fix['facilitator_id']);

                $update = ['facilitator_id' => $fix['facilitator_id']];
                if ($facilitator && !empty($facilitator->ip_id)) {
                    $update['ip_id'] = $facilitator->ip_id;
                }

                $count += FfsGroup::where('id', $fix['group_id'])
                    ->whereNull('facilitator_id')
                    ->update($update);
            }

            return response()->json(['success' => true, 'fixed' => $count, 'message' => "{$count} group(s) assigned facilitators"]);
        }

        // ── Scan ──
        $query = FfsGroup::query()
            ->whereNull('facilitator_id')
            ->select('id', 'name', 'ip_id');
        $query = $this->applyScopeQuery($query);
        $groups = $query->get();

        if ($groups->isEmpty()) {
            return response()->json(['success' => true, 'fixes' => [], 'no_fix_count' => 0, 'total' => 0]);
        }

        // Get all facilitators (role_id = 3) grouped by IP
        $facilitatorUserIds = DB::table('admin_role_users')
            ->where('role_id', 3)
            ->pluck('user_id');

        $facilitators = User::whereIn('id', $facilitatorUserIds)
            ->select('id', 'name', 'ip_id')
            ->get()
            ->groupBy('ip_id');

        // Batch count: groups per facilitator
        $facGroupCounts = FfsGroup::whereNotNull('facilitator_id')
            ->whereIn('facilitator_id', $facilitatorUserIds)
            ->selectRaw('facilitator_id, COUNT(*) as cnt')
            ->groupBy('facilitator_id')
            ->pluck('cnt', 'facilitator_id');

        $fixes = [];
        $noFix = 0;

        foreach ($groups as $g) {
            $ipFacs = $facilitators[$g->ip_id] ?? collect();
            if ($ipFacs->isEmpty()) {
                $noFix++;
                continue;
            }

            // Pick facilitator with fewest existing groups
            $best = $ipFacs->sortBy(fn($f) => $facGroupCounts[$f->id] ?? 0)->first();

            $fixes[] = [
                'group_id'           => $g->id,
                'group_name'         => $g->name,
                'facilitator_id'     => $best->id,
                'facilitator_name'   => $best->name,
                'facilitator_groups' => $facGroupCounts[$best->id] ?? 0,
            ];
        }

        return response()->json([
            'success'      => true,
            'fixes'        => $fixes,
            'no_fix_count' => $noFix,
            'total'        => count($groups),
        ]);
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
     * Get resolved entity IDs for a specific check_key + entity_type.
     */
    private function getResolvedIds(string $checkKey, string $entityType): array
    {
        return DB::table('health_check_resolutions')
            ->where('check_key', $checkKey)
            ->where('entity_type', $entityType)
            ->pluck('entity_id')
            ->toArray();
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
     * Check for groups with exactly the same name (case-insensitive)
     */
    private function checkGroupsSimilarNames()
    {
        $query = FfsGroup::query()
            ->with(['implementingPartner:id,name', 'district:id,name'])
            ->withCount('members');
        $query = $this->applyScopeQuery($query);

        $resolvedIds = $this->getResolvedIds('groups_similar_names', 'group');

        $groups = $query->get()
            ->groupBy(fn($g) => strtolower(trim($g->name)))
            ->filter(fn($g) => count($g) > 1);

        $allItems = $groups->map(fn($dupeGroup) => [
            'title' => 'Duplicate Group Name',
            'groups' => $dupeGroup->map(fn($g) => $this->buildGroupItem($g, [
                'members' => $g->members_count,
            ]))->values()->toArray(),
            'action' => 'review',
        ])->values()->toArray();

        // Filter out clusters where ALL groups in the cluster are resolved
        $items = [];
        $resolvedCount = 0;
        foreach ($allItems as $cluster) {
            $clusterGroupIds = collect($cluster['groups'])->pluck('id')->toArray();
            $allResolved = !empty($clusterGroupIds) && empty(array_diff($clusterGroupIds, $resolvedIds));
            if ($allResolved) {
                $resolvedCount++;
            } else {
                $items[] = $cluster;
            }
        }

        return [
            'title' => 'Groups with Duplicate Names',
            'description' => 'Groups that share the exact same name. These are likely duplicates that should be merged or renamed.',
            'severity' => 'warning',
            'icon' => 'fa-copy',
            'color' => 'warning',
            'items' => $items,
            'entity' => 'group',
            'resolved_count' => $resolvedCount,
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

        $resolvedIds = $this->getResolvedIds('groups_oversized', 'group');

        $allItems = $query->get()
            ->filter(fn($g) => $g->members_count > 35)
            ->sortByDesc('members_count')
            ->map(fn($g) => $this->buildGroupItem($g, [
                'members' => $g->members_count,
            ]))->values()->toArray();

        $items = array_values(array_filter($allItems, fn($i) => !in_array($i['id'], $resolvedIds)));
        $resolvedCount = count($allItems) - count($items);

        return [
            'title' => 'Oversized Groups (35+ Members)',
            'description' => 'VSLA best practice recommends max 30 members per group. Oversized groups may need splitting.',
            'severity' => 'info',
            'icon' => 'fa-warning',
            'color' => 'info',
            'items' => $items,
            'entity' => 'group',
            'resolved_count' => $resolvedCount,
        ];
    }

    /**
     * Check for groups with no members
     */
    private function checkGroupsEmpty()
    {
        $query = FfsGroup::query()
            ->with(['implementingPartner:id,name', 'district:id,name'])
            ->withCount('members');
        $query = $this->applyScopeQuery($query);

        $resolvedIds = $this->getResolvedIds('groups_empty', 'group');

        $allItems = $query->get()
            ->filter(fn($g) => $g->members_count === 0)
            ->sortBy('name')
            ->map(fn($g) => $this->buildGroupItem($g, [
                'status' => $g->status,
                'members' => $g->members_count,
                'registration_date' => $g->registration_date?->format('Y-m-d'),
            ]))->values()->toArray();

        $items = array_values(array_filter($allItems, fn($i) => !in_array($i['id'], $resolvedIds)));
        $resolvedCount = count($allItems) - count($items);

        return [
            'title' => 'Empty Groups (No Members)',
            'description' => 'Groups with no registered members. These may be incomplete registrations or inactive groups.',
            'severity' => 'critical',
            'icon' => 'fa-users-slash',
            'color' => 'danger',
            'items' => $items,
            'entity' => 'group',
            'resolved_count' => $resolvedCount,
        ];
    }

    /**
     * Check for groups with no facilitator assigned
     */
    private function checkGroupsNoFacilitator()
    {
        $query = FfsGroup::query()
            ->with(['implementingPartner:id,name', 'district:id,name'])
            ->withCount('members')
            ->whereNull('facilitator_id')
            ->orderBy('name');

        $query = $this->applyScopeQuery($query);

        $resolvedIds = $this->getResolvedIds('groups_no_facilitator', 'group');

        $allItems = $query->get()
            ->map(fn($g) => $this->buildGroupItem($g, [
                'members' => $g->members_count,
                'status' => $g->status,
            ]))->toArray();

        $items = array_values(array_filter($allItems, fn($i) => !in_array($i['id'], $resolvedIds)));
        $resolvedCount = count($allItems) - count($items);

        return [
            'title' => 'Groups Without Facilitator',
            'description' => 'Groups that do not have a facilitator assigned. Every group must have a facilitator.',
            'severity' => 'critical',
            'icon' => 'fa-user-slash',
            'color' => 'danger',
            'items' => $items,
            'entity' => 'group',
            'resolved_count' => $resolvedCount,
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
            'danger',
            'duplicate_chairperson'
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
            'danger',
            'duplicate_phone'
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
            'warning',
            'duplicate_email'
        );
    }

    /**
     * Generic duplicate field checker
     */
    private function checkDuplicateField($field, $filters, $title, $description, $severity, $icon, $color, $checkKey = null)
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
            return compact('title', 'description', 'severity', 'icon', 'color') + ['items' => [], 'entity' => 'user', 'resolved_count' => 0];
        }

        $resolvedIds = $checkKey ? $this->getResolvedIds($checkKey, 'user') : [];

        $users = User::query()
            ->whereIn($field, $duplicateValues)
            ->select('id', 'name', $field, 'email', 'phone_number', 'group_id')
            ->with(['group' => fn($q) => $q->select('id', 'name')])
            ->when($this->hasIpScope(), fn($q) => $q->where('ip_id', $this->effectiveIpId()))
            ->get()
            ->groupBy($field);

        $allItems = $users->map(fn($groupedUsers, $key) => [
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

        // Filter: a duplicate cluster is resolved if ALL user IDs in the cluster are resolved
        $items = [];
        $resolvedCount = 0;
        foreach ($allItems as $cluster) {
            $clusterUserIds = collect($cluster['users'])->pluck('id')->toArray();
            $allResolved = !empty($resolvedIds) && !empty($clusterUserIds) && empty(array_diff($clusterUserIds, $resolvedIds));
            if ($allResolved) {
                $resolvedCount++;
            } else {
                $items[] = $cluster;
            }
        }

        return compact('title', 'description', 'severity', 'icon', 'color', 'items') + ['entity' => 'user', 'resolved_count' => $resolvedCount];
    }

    /**
     * Check for users with no assigned IP
     */
    private function checkUsersNoIp()
    {
        // Exclude admin/staff users — they don't need an IP assignment
        $staffRoleIds = [1, 2, 3, 6, 7];
        $staffUserIds = DB::table('admin_role_users')
            ->whereIn('role_id', $staffRoleIds)
            ->pluck('user_id')
            ->unique();

        $query = User::query()
            ->where(function($q) {
                $q->whereNull('ip_id')->orWhere('ip_id', 0);
            })
            ->when($staffUserIds->isNotEmpty(), fn($q) => $q->whereNotIn('id', $staffUserIds))
            ->select('id', 'name', 'email', 'phone_number', 'group_id')
            ->with(['group' => fn($q) => $q->select('id', 'name')])
            ->orderBy('name');

        if ($this->hasIpScope()) {
            $query->whereIn('group_id', FfsGroup::where('ip_id', $this->effectiveIpId())->pluck('id'));
        }

        $resolvedIds = $this->getResolvedIds('users_no_ip', 'user');

        $allItems = $query->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'group' => $u->group?->name,
                'group_id' => $u->group_id,
                'email' => $u->email,
                'phone' => $u->phone_number,
            ])->toArray();

        $items = array_values(array_filter($allItems, fn($i) => !in_array($i['id'], $resolvedIds)));
        $resolvedCount = count($allItems) - count($items);

        return [
            'title' => 'Users Without Implementing Partner',
            'description' => 'All users must be assigned to an Implementing Partner (IP). This is mandatory for system scoping.',
            'severity' => 'critical',
            'icon' => 'fa-building',
            'color' => 'danger',
            'items' => $items,
            'entity' => 'user',
            'resolved_count' => $resolvedCount,
        ];
    }

    /**
     * Check for orphaned members
     */
    private function checkOrphanedMembers()
    {
        $staffRoleIds = [1, 2, 3, 6, 7];

        $staffUserIds = \DB::table('admin_role_users')
            ->whereIn('role_id', $staffRoleIds)
            ->pluck('user_id')
            ->unique();

        $query = User::query()
            ->where(function($q) {
                $q->whereNull('group_id')->orWhere('group_id', 0);
            })
            ->when($staffUserIds->isNotEmpty(), function($q) use ($staffUserIds) {
                $q->whereNotIn('id', $staffUserIds);
            })
            ->select('id', 'name', 'email', 'phone_number', 'ip_id')
            ->orderBy('name');

        $query = $this->applyScopeQuery($query);

        $resolvedIds = $this->getResolvedIds('orphaned_members', 'user');

        $allItems = $query->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone_number,
                'ip' => $u->ip_id,
            ])->toArray();

        $items = array_values(array_filter($allItems, fn($i) => !in_array($i['id'], $resolvedIds)));
        $resolvedCount = count($allItems) - count($items);

        return [
            'title' => 'Orphaned Members (No Group)',
            'description' => 'Members that are not assigned to any group. Most members should belong to a group.',
            'severity' => 'warning',
            'icon' => 'fa-person-hiking',
            'color' => 'warning',
            'items' => $items,
            'entity' => 'user',
            'resolved_count' => $resolvedCount,
        ];
    }

    /**
     * Check for inactive groups with members
     */
    private function checkInactiveGroupsWithMembers()
    {
        $query = FfsGroup::query()
            ->with(['implementingPartner:id,name', 'district:id,name'])
            ->withCount('members')
            ->where('status', '!=', 'Active');

        $query = $this->applyScopeQuery($query);

        $resolvedIds = $this->getResolvedIds('inactive_groups_with_members', 'group');

        $allItems = $query->get()
            ->filter(fn($g) => $g->members_count > 0)
            ->sortBy('name')
            ->map(fn($g) => $this->buildGroupItem($g, [
                'status' => $g->status,
                'members' => $g->members_count,
            ]))->values()->toArray();

        $items = array_values(array_filter($allItems, fn($i) => !in_array($i['id'], $resolvedIds)));
        $resolvedCount = count($allItems) - count($items);

        return [
            'title' => 'Inactive Groups with Members',
            'description' => 'Groups marked as inactive but still have members. Consider deactivating members or reactivating the group.',
            'severity' => 'info',
            'icon' => 'fa-circle-pause',
            'color' => 'info',
            'items' => $items,
            'entity' => 'group',
            'resolved_count' => $resolvedCount,
        ];
    }
}
