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
