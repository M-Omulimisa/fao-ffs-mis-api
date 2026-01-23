<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VslaMeeting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'local_id',
        'cycle_id',
        'group_id',
        'created_by_id',
        'meeting_date',
        'meeting_number',
        'notes',
        'members_present',
        'members_absent',
        'total_savings_collected',
        'total_welfare_collected',
        'total_social_fund_collected',
        'total_fines_collected',
        'total_loans_disbursed',
        'total_loans_repaid',
        'total_shares_sold',
        'total_share_value',
        'attendance_data',
        'transactions_data',
        'loan_repayments_data',
        'social_fund_contributions_data',
        'loans_data',
        'share_purchases_data',
        'previous_action_plans_data',
        'upcoming_action_plans_data',
        'processing_status',
        'processed_at',
        'processed_by_id',
        'has_errors',
        'has_warnings',
        'errors',
        'warnings',
        'submitted_from_app_at',
        'received_at',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'attendance_data' => 'array',
        'transactions_data' => 'array',
        'loan_repayments_data' => 'array',
        'social_fund_contributions_data' => 'array',
        'loans_data' => 'array',
        'share_purchases_data' => 'array',
        'previous_action_plans_data' => 'array',
        'upcoming_action_plans_data' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
        'has_errors' => 'boolean',
        'has_warnings' => 'boolean',
        'processed_at' => 'datetime',
        'submitted_from_app_at' => 'datetime',
        'received_at' => 'datetime',
        'total_savings_collected' => 'decimal:2',
        'total_welfare_collected' => 'decimal:2',
        'total_social_fund_collected' => 'decimal:2',
        'total_fines_collected' => 'decimal:2',
        'total_loans_disbursed' => 'decimal:2',
        'total_loans_repaid' => 'decimal:2',
        'total_share_value' => 'decimal:2',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    public function attendance()
    {
        return $this->hasMany(VslaMeetingAttendance::class, 'meeting_id');
    }

    public function actionPlans()
    {
        return $this->hasMany(VslaActionPlan::class, 'meeting_id');
    }

    public function loans()
    {
        return $this->hasMany(VslaLoan::class, 'meeting_id');
    }

    // Accessors
    public function getTotalMembersAttribute()
    {
        return $this->members_present + $this->members_absent;
    }

    public function getAttendanceRateAttribute()
    {
        if ($this->total_members == 0) {
            return 0;
        }
        return round(($this->members_present / $this->total_members) * 100, 2);
    }

    public function getTotalCashCollectedAttribute()
    {
        return $this->total_savings_collected +
            $this->total_welfare_collected +
            $this->total_social_fund_collected +
            $this->total_fines_collected +
            $this->total_share_value;
    }

    public function getNetCashFlowAttribute()
    {
        return $this->total_cash_collected - $this->total_loans_disbursed;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('processing_status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('processing_status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('processing_status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('processing_status', 'failed');
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('processing_status', 'needs_review');
    }

    public function scopeHasErrors($query)
    {
        return $query->where('has_errors', true);
    }

    public function scopeHasWarnings($query)
    {
        return $query->where('has_warnings', true);
    }

    public function scopeByCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    public function scopeByGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    // Status Management Methods
    public function markAsProcessing()
    {
        $this->update(['processing_status' => 'processing']);
    }

    public function markAsCompleted()
    {
        $this->update([
            'processing_status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed($errors = [])
    {
        $this->update([
            'processing_status' => 'failed',
            'has_errors' => true,
            'errors' => $errors,
        ]);
    }

    public function markAsNeedsReview($warnings = [])
    {
        $this->update([
            'processing_status' => 'completed', // Use 'completed' since 'needs_review' doesn't exist in enum
            'has_warnings' => true,
            'warnings' => $warnings,
            'processed_at' => now(),
        ]);
    }

    public function markAsCompletedWithWarnings($warnings = [])
    {
        $this->update([
            'processing_status' => 'completed',
            'has_warnings' => true,
            'warnings' => $warnings,
            'processed_at' => now(),
        ]);
    }

    // Helper Methods
    public function isPending()
    {
        return $this->processing_status === 'pending';
    }

    public function isCompleted()
    {
        return $this->processing_status === 'completed';
    }

    public function isFailed()
    {
        return $this->processing_status === 'failed';
    }

    public function canBeProcessed()
    {
        return in_array($this->processing_status, ['pending', 'failed']);
    }

    public function addError($type, $message, $field = null)
    {
        $errors = $this->errors ?? [];
        $errors[] = [
            'type' => $type,
            'message' => $message,
            'field' => $field,
            'timestamp' => now()->toDateTimeString(),
        ];
        $this->errors = $errors;
        $this->has_errors = true;
    }

    public function addWarning($type, $message, $suggestion = null)
    {
        $warnings = $this->warnings ?? [];
        $warnings[] = [
            'type' => $type,
            'message' => $message,
            'suggestion' => $suggestion,
            'timestamp' => now()->toDateTimeString(),
        ];
        $this->warnings = $warnings;
        $this->has_warnings = true;
    }
}
