<?php

namespace App\Services;

use App\Models\FfsGroup;
use App\Models\ImplementingPartner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GroupIpAlignmentService
{
    /**
     * Resolve facilitator's IP and ensure it exists in implementing_partners.
     */
    public function resolveValidFacilitatorIpId(?int $facilitatorId): ?int
    {
        if (empty($facilitatorId)) {
            return null;
        }

        $facilitator = User::select('id', 'ip_id')->find($facilitatorId);
        if (!$facilitator || empty($facilitator->ip_id)) {
            return null;
        }

        $ipExists = ImplementingPartner::where('id', (int) $facilitator->ip_id)->exists();
        return $ipExists ? (int) $facilitator->ip_id : null;
    }

    /**
     * Align one group and all related records to facilitator IP.
     * Uses transaction + deterministic SQL update order to reduce deadlock risk.
     */
    public function alignGroupAndRelatedData(int $groupId): bool
    {
        return (bool) DB::transaction(function () use ($groupId) {
            /** @var FfsGroup|null $group */
            $group = FfsGroup::query()->lockForUpdate()->find($groupId);
            if (!$group) {
                return false;
            }

            $targetIpId = $this->resolveValidFacilitatorIpId((int) $group->facilitator_id);
            if (!$targetIpId) {
                // No valid facilitator IP -> do not force overwrite existing data.
                return false;
            }

            $ip = ImplementingPartner::select('id', 'short_name', 'name')->find($targetIpId);
            $ipName = $ip ? trim((string) ($ip->short_name ?: $ip->name)) : null;

            // 1) Core group record
            DB::update(
                'UPDATE ffs_groups SET ip_id = ?, ip_name = ? WHERE id = ? AND (ip_id IS NULL OR ip_id <> ?)',
                [$targetIpId, $ipName, $groupId, $targetIpId]
            );

            // 2) Group members/profiles
            DB::update(
                'UPDATE users SET ip_id = ? WHERE group_id = ? AND (ip_id IS NULL OR ip_id <> ?)',
                [$targetIpId, $groupId, $targetIpId]
            );

            // 3) VSLA domain records that carry ip_id
            $this->updateIfTableAndColumn('vsla_meetings', 'group_id', $groupId, $targetIpId);
            $this->updateIfTableAndColumn('vsla_profiles', 'group_id', $groupId, $targetIpId);
            $this->updateIfTableAndColumn('vsla_opening_balances', 'group_id', $groupId, $targetIpId);
            $this->updateIfTableAndColumn('vsla_shareouts', 'group_id', $groupId, $targetIpId);

            // 4) AESA / training / KPI records that carry ip_id
            $this->updateIfTableAndColumn('aesa_sessions', 'group_id', $groupId, $targetIpId);
            $this->updateIfTableAndColumn('ffs_training_sessions', 'group_id', $groupId, $targetIpId);
            $this->updateIfTableAndColumn('ffs_kpi_ip_entries', 'group_id', $groupId, $targetIpId);
            $this->updateIfTableAndColumn('ffs_kpi_facilitator_entries', 'group_id', $groupId, $targetIpId);

            // 5) Sessions linked through pivot target groups
            if (Schema::hasTable('ffs_training_sessions')
                && Schema::hasTable('ffs_session_target_groups')
                && Schema::hasColumn('ffs_training_sessions', 'ip_id')) {
                DB::update(
                    'UPDATE ffs_training_sessions ts
                     INNER JOIN ffs_session_target_groups stg ON stg.session_id = ts.id
                     SET ts.ip_id = ?
                     WHERE stg.group_id = ? AND (ts.ip_id IS NULL OR ts.ip_id <> ?)',
                    [$targetIpId, $groupId, $targetIpId]
                );
            }

            // 6) Cycles/projects when schema includes ip_id
            $this->updateIfTableAndColumn('projects', 'group_id', $groupId, $targetIpId);

            // 7) Related child records through session relation (if schema supports ip_id)
            if (Schema::hasTable('aesa_crop_observations')
                && Schema::hasTable('aesa_sessions')
                && Schema::hasColumn('aesa_crop_observations', 'ip_id')) {
                DB::update(
                    'UPDATE aesa_crop_observations aco
                     INNER JOIN aesa_sessions s ON s.id = aco.aesa_session_id
                     SET aco.ip_id = ?
                     WHERE s.group_id = ? AND (aco.ip_id IS NULL OR aco.ip_id <> ?)',
                    [$targetIpId, $groupId, $targetIpId]
                );
            }

            return true;
        }, 5);
    }

    /**
     * Align all groups for a facilitator (used when facilitator ip_id changes).
     */
    public function alignAllGroupsForFacilitator(int $facilitatorId): void
    {
        $groupIds = FfsGroup::query()
            ->where('facilitator_id', $facilitatorId)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        foreach ($groupIds as $groupId) {
            try {
                $this->alignGroupAndRelatedData((int) $groupId);
            } catch (\Throwable $e) {
                Log::warning('Group IP alignment failed', [
                    'group_id' => (int) $groupId,
                    'facilitator_id' => $facilitatorId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Update table.ip_id where a group link column exists and schema supports ip_id.
     */
    private function updateIfTableAndColumn(string $table, string $groupColumn, int $groupId, int $targetIpId): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'ip_id') || !Schema::hasColumn($table, $groupColumn)) {
            return;
        }

        DB::update(
            "UPDATE {$table} SET ip_id = ? WHERE {$groupColumn} = ? AND (ip_id IS NULL OR ip_id <> ?)",
            [$targetIpId, $groupId, $targetIpId]
        );
    }
}
