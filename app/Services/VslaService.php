<?php

namespace App\Services;

use App\Models\FfsGroup;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VslaService
 *
 * Central service for VSLA business logic that is shared across multiple
 * controllers (VslaConfigurationController, VslaOpeningBalanceController, etc.)
 *
 * Key responsibility: guarantee that a group always has a valid, active VSLA
 * cycle — creating one automatically when none exists.
 */
class VslaService
{
    // ─── Default values used when auto-creating a cycle ────────────────────────

    /** Share value in UGX when no previous cycle exists to inherit from. */
    const DEFAULT_SHARE_VALUE = 5000;

    /** Monthly loan interest rate (%) applied to auto-created cycles. */
    const DEFAULT_LOAN_RATE = 5.0;

    /** Default cycle duration in months. */
    const DEFAULT_CYCLE_MONTHS = 12;

    /** Maximum loan as a multiple of a member's own shares. */
    const DEFAULT_MAX_LOAN_MULTIPLE = 3;

    // ─── ensureActiveCycle ─────────────────────────────────────────────────────

    /**
     * Find or create the active VSLA cycle for a group.
     *
     * Resolution strategy (in order):
     *
     *   1. Return an existing cycle that is flagged `is_active_cycle = Yes`
     *      and whose status is not 'completed'. This is the fast path.
     *
     *   2. If every cycle is completed/inactive, activate the most recently
     *      started one (prevents duplicate-cycle spam on retry calls).
     *
     *   3. If the group has no VSLA cycles at all, auto-create one with
     *      sensible defaults inherited from the group where possible.
     *
     * All three paths return a Project instance that is guaranteed to have:
     *   is_vsla_cycle  = 'Yes'
     *   is_active_cycle = 'Yes'
     *   group_id       = $group->id
     *
     * @param  FfsGroup   $group         The group that needs an active cycle.
     * @param  int|null   $actingUserId  ID stored in `created_by_id` for audit trail.
     * @return Project
     */
    public static function ensureActiveCycle(FfsGroup $group, ?int $actingUserId = null): Project
    {
        // ── Path 1: existing active cycle ──────────────────────────────────────
        $active = Project::where('is_vsla_cycle', 'Yes')
            ->where('group_id', $group->id)
            ->where('is_active_cycle', 'Yes')
            ->where(function ($q) {
                // A cycle with status = 'completed' cannot serve as active even
                // if the flag was never cleared.
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'completed');
            })
            ->latest('start_date')
            ->first();

        if ($active) {
            Log::debug("VslaService::ensureActiveCycle – returning existing active cycle #{$active->id} for group #{$group->id}");
            return $active;
        }

        // ── Path 2: activate the most recent existing VSLA cycle ───────────────
        // Exclude completed cycles — re-activating a closed cycle would corrupt
        // financial history.  Only non-completed cycles are eligible.
        $latest = Project::where('is_vsla_cycle', 'Yes')
            ->where('group_id', $group->id)
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'completed');
            })
            ->latest('start_date')
            ->first();

        if ($latest) {
            Log::warning("VslaService::ensureActiveCycle – no active cycle found for group #{$group->id}; activating latest cycle #{$latest->id}");

            DB::transaction(function () use ($group, $latest) {
                // Deactivate all others first so exactly one cycle is active.
                Project::where('is_vsla_cycle', 'Yes')
                    ->where('group_id', $group->id)
                    ->where('id', '!=', $latest->id)
                    ->update(['is_active_cycle' => 'No']);

                $latest->update(['is_active_cycle' => 'Yes']);
            });

            $latest->refresh();
            return $latest;
        }

        // ── Path 3: auto-create a brand-new cycle ──────────────────────────────
        Log::warning("VslaService::ensureActiveCycle – group #{$group->id} ({$group->name}) has NO VSLA cycles; auto-creating Cycle 1");

        $now     = Carbon::now();
        $endDate = $now->copy()->addMonths(self::DEFAULT_CYCLE_MONTHS)->toDateString();

        // Derive interest rates from the single stored rate
        $loanRate    = self::DEFAULT_LOAN_RATE;  // monthly %
        $weeklyRate  = round($loanRate / 4, 4);
        $monthlyRate = $loanRate;

        $newCycle = DB::transaction(function () use ($group, $now, $endDate, $loanRate, $weeklyRate, $monthlyRate, $actingUserId) {
            // Ensure no other cycle is accidentally flagged active.
            Project::where('is_vsla_cycle', 'Yes')
                ->where('group_id', $group->id)
                ->update(['is_active_cycle' => 'No']);

            return Project::create([
                // Identification
                'is_vsla_cycle'              => 'Yes',
                'is_active_cycle'            => 'Yes',
                'group_id'                   => $group->id,
                'cycle_name'                 => "Cycle 1 ({$now->year})",
                'title'                      => "Cycle 1 ({$now->year})",
                'description'                => "Auto-created VSLA Savings Cycle for {$group->name}",

                // Dates
                'start_date'                 => $now->toDateString(),
                'end_date'                   => $endDate,
                'status'                     => 'ongoing',

                // Savings settings
                'saving_type'                => 'shares',
                'share_value'                => self::DEFAULT_SHARE_VALUE,
                'share_price'                => self::DEFAULT_SHARE_VALUE,  // alias

                // Meeting
                'meeting_frequency'          => $group->meeting_frequency ?? 'Weekly',

                // Running totals (initialised to zero for a fresh cycle)
                'total_shares'               => 0,

                // Loan settings
                'loan_interest_rate'         => $loanRate,
                'interest_frequency'         => 'Monthly',
                'weekly_loan_interest_rate'  => $weeklyRate,
                'monthly_loan_interest_rate' => $monthlyRate,
                'minimum_loan_amount'        => 0,
                'maximum_loan_multiple'      => self::DEFAULT_MAX_LOAN_MULTIPLE,
                'late_payment_penalty'       => 0,

                // Audit
                'created_by_id'              => $actingUserId,
            ]);
        });

        Log::info("VslaService::ensureActiveCycle – created cycle #{$newCycle->id} for group #{$group->id}");

        return $newCycle;
    }

    // ─── resolveGroupActiveCycle ───────────────────────────────────────────────

    /**
     * Given a submitted (possibly stale) cycle_id and the user's group,
     * return the cycle that should actually be used for operations.
     *
     * If the submitted cycle belongs to the correct group → return it as-is.
     * If it belongs to a DIFFERENT group → find/create the group's own cycle.
     *
     * This corrects the common race condition where the mobile app cached a
     * stale cycle_id from a previous session or a different group.
     *
     * @param  int        $submittedCycleId  The cycle_id sent by the client.
     * @param  FfsGroup   $group             The authenticated user's group.
     * @param  int|null   $actingUserId
     * @return array{cycle: Project, corrected: bool, original_id: int}
     */
    public static function resolveGroupActiveCycle(int $submittedCycleId, FfsGroup $group, ?int $actingUserId = null): array
    {
        $submitted = Project::find($submittedCycleId);

        // Cycle belongs to the correct group — no correction needed.
        if ($submitted && (int) $submitted->group_id === (int) $group->id) {
            return [
                'cycle'       => $submitted,
                'corrected'   => false,
                'original_id' => $submittedCycleId,
            ];
        }

        // Cycle is missing or belongs to a different group — auto-correct.
        Log::warning("VslaService::resolveGroupActiveCycle – cycle #{$submittedCycleId} "
            . (($submitted && $submitted->group_id) ? "belongs to group #{$submitted->group_id}" : "not found")
            . ", but user belongs to group #{$group->id}; auto-correcting");

        $correctedCycle = self::ensureActiveCycle($group, $actingUserId);

        return [
            'cycle'       => $correctedCycle,
            'corrected'   => true,
            'original_id' => $submittedCycleId,
        ];
    }
}
