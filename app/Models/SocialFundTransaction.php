<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Social Fund Transaction Model
 * 
 * Tracks all transactions related to VSLA Social Fund:
 * - Contributions from members (positive amount)
 * - Withdrawals for emergencies (negative amount)
 */
class SocialFundTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'cycle_id',
        'member_id',
        'meeting_id',
        'transaction_type',
        'amount',
        'transaction_date',
        'description',
        'reason',
        'created_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Get the group that owns the transaction
     */
    public function group()
    {
        return $this->belongsTo(FfsGroup::class, 'group_id');
    }

    /**
     * Get the cycle associated with the transaction
     */
    public function cycle()
    {
        return $this->belongsTo(Project::class, 'cycle_id');
    }

    /**
     * Get the member who made the transaction
     */
    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    /**
     * Get the meeting associated with the transaction
     */
    public function meeting()
    {
        return $this->belongsTo(VslaMeeting::class, 'meeting_id');
    }

    /**
     * Get the user who created the transaction
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Scope: Filter by group
     */
    public function scopeForGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    /**
     * Scope: Filter by cycle
     */
    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope: Contributions only
     */
    public function scopeContributions($query)
    {
        return $query->where('transaction_type', 'contribution');
    }

    /**
     * Scope: Withdrawals only
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('transaction_type', 'withdrawal');
    }

    /**
     * Calculate balance for a group
     */
    public static function getGroupBalance($groupId, $cycleId = null)
    {
        $query = self::where('group_id', $groupId);
        
        if ($cycleId) {
            $query->where('cycle_id', $cycleId);
        }

        return $query->sum('amount');
    }
}
