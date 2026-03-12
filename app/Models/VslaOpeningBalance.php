<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $group_id  References ffs_groups.id (NOT projects.id)
 * @property int $cycle_id  References projects.id
 */
class VslaOpeningBalance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'group_id',
        'cycle_id',
        'submitted_by_id',
        'status',
        'submission_date',
        'notes',
        'is_processed',
        'processed_at',
        'processing_notes',
    ];

    protected $casts = [
        'submission_date' => 'datetime',
        'processed_at'    => 'datetime',
        'is_processed'    => 'boolean',
    ];

    // ─── Automatic processing hook ─────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        /**
         * Auto-process hook: fires after any save (insert or update).
         *
         * Conditions to trigger automatic processing:
         *   1. status = 'submitted'          (not yet / marked for processing)
         *   2. is_processed = false          (not already done)
         *   3. NOT wasRecentlyCreated        (skip the initial header create in
         *                                      store() — members don't exist yet)
         *   4. memberEntries exist in DB     (there is data to process)
         *
         * This catches:
         *   – Records stuck in 'submitted' state due to prior errors
         *   – Records created outside the normal store() HTTP flow
         *   – Skipped records that were later re-saved / touched
         */
        static::saved(function (self $ob): void {
            if (
                $ob->status === 'submitted'
                && !$ob->is_processed
                && !$ob->wasRecentlyCreated
                && $ob->memberEntries()->exists()
            ) {
                try {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($ob): void {
                        $service   = new \App\Services\OpeningBalanceService();
                        $summary   = $service->process($ob, $ob->submitted_by_id ?? $ob->group_id);

                        // Use updateQuietly() to avoid re-triggering this hook
                        $ob->updateQuietly([
                            'status'           => 'processed',
                            'is_processed'     => true,
                            'processed_at'     => now(),
                            'processing_notes' => json_encode($summary['log']),
                        ]);

                        \Illuminate\Support\Facades\Log::info(
                            "OpeningBalance auto-processed id={$ob->id} "
                            . "shares={$summary['shares_created']} "
                            . "loans={$summary['loans_created']} "
                            . "sf={$summary['social_fund_records']}"
                        );
                    });
                } catch (\Throwable $e) {
                    // Never throw from a model hook — log and continue
                    \Illuminate\Support\Facades\Log::error(
                        "OpeningBalance auto-process FAILED id={$ob->id}: " . $e->getMessage()
                    );
                }
            }
        });
    }

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function group()
    {
        return $this->belongsTo(FfsGroup::class, 'group_id');
    }

    public function cycle()
    {
        return $this->belongsTo(Project::class, 'cycle_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function memberEntries()
    {
        return $this->hasMany(VslaOpeningBalanceMember::class, 'opening_balance_id');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }
}
