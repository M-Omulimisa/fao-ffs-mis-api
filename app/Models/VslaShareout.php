<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class VslaShareout extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cycle_id',
        'group_id',
        'total_savings',
        'total_share_value',
        'total_loan_interest_earned',
        'total_fines_collected',
        'total_distributable_fund',
        'total_outstanding_loans',
        'total_actual_payout',
        'total_members',
        'total_shares',
        'share_unit_value',
        'final_share_value',
        'status',
        'shareout_date',
        'calculated_at',
        'approved_at',
        'completed_at',
        'calculation_notes',
        'admin_notes',
        'initiated_by_id',
        'approved_by_id',
        'completed_by_id',
    ];

    protected $casts = [
        'total_savings' => 'decimal:2',
        'total_share_value' => 'decimal:2',
        'total_loan_interest_earned' => 'decimal:2',
        'total_fines_collected' => 'decimal:2',
        'total_distributable_fund' => 'decimal:2',
        'total_outstanding_loans' => 'decimal:2',
        'total_actual_payout' => 'decimal:2',
        'share_unit_value' => 'decimal:2',
        'final_share_value' => 'decimal:2',
        'shareout_date' => 'date',
        'calculated_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'cycle_name',
        'group_name',
        'is_editable',
        'is_approvable',
        'is_completable',
    ];

    // Relationships
    public function cycle()
    {
        return $this->belongsTo(Project::class, 'cycle_id');
    }

    public function group()
    {
        return $this->belongsTo(FfsGroup::class, 'group_id');
    }

    public function distributions()
    {
        return $this->hasMany(VslaShareoutDistribution::class, 'shareout_id');
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by_id');
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'calculated' => 'Calculated',
            'approved' => 'Approved',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getCycleNameAttribute()
    {
        return $this->cycle ? $this->cycle->cycle_name : 'N/A';
    }

    public function getGroupNameAttribute()
    {
        return $this->group ? $this->group->name : 'N/A';
    }

    public function getIsEditableAttribute()
    {
        return in_array($this->status, ['draft', 'calculated']);
    }

    public function getIsApprovableAttribute()
    {
        return $this->status === 'calculated';
    }

    public function getIsCompletableAttribute()
    {
        return $this->status === 'approved';
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeCalculated($query)
    {
        return $query->where('status', 'calculated');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    public function scopeForGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    // Helper Methods
    public function canRecalculate(): bool
    {
        return in_array($this->status, ['draft', 'calculated']);
    }

    public function canApprove(): bool
    {
        return $this->status === 'calculated';
    }

    public function canComplete(): bool
    {
        return $this->status === 'approved';
    }

    public function canCancel(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled']);
    }

    public function markAsCalculated(): void
    {
        $this->update([
            'status' => 'calculated',
            'calculated_at' => now(),
        ]);
    }

    public function markAsApproved($userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_id' => $userId,
        ]);
    }

    public function markAsCompleted($userId): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by_id' => $userId,
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Get summary statistics for display
     */
    public function getSummary(): array
    {
        return [
            'shareout_id' => $this->id,
            'cycle_name' => $this->cycle_name,
            'group_name' => $this->group_name,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'shareout_date' => $this->shareout_date->format('Y-m-d'),
            
            // Financial Totals
            'total_savings' => (float) $this->total_savings,
            'total_share_value' => (float) $this->total_share_value,
            'total_loan_interest_earned' => (float) $this->total_loan_interest_earned,
            'total_fines_collected' => (float) $this->total_fines_collected,
            'total_distributable_fund' => (float) $this->total_distributable_fund,
            'total_outstanding_loans' => (float) $this->total_outstanding_loans,
            'total_actual_payout' => (float) $this->total_actual_payout,
            
            // Metadata
            'total_members' => $this->total_members,
            'total_shares' => $this->total_shares,
            'share_unit_value' => (float) $this->share_unit_value,
            'final_share_value' => (float) $this->final_share_value,
            
            // Permissions
            'can_recalculate' => $this->canRecalculate(),
            'can_approve' => $this->canApprove(),
            'can_complete' => $this->canComplete(),
            'can_cancel' => $this->canCancel(),
            
            // Distribution Count
            'distributions_count' => $this->distributions()->count(),
            'paid_distributions_count' => $this->distributions()->where('payment_status', 'paid')->count(),
        ];
    }
}
