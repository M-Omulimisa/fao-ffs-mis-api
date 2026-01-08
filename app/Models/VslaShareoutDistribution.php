<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VslaShareoutDistribution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'shareout_id',
        'member_id',
        'member_savings',
        'member_shares',
        'member_share_value',
        'member_fines_paid',
        'member_welfare_contribution',
        'share_percentage',
        'proportional_distribution',
        'loan_interest_share',
        'fine_share',
        'outstanding_loan_principal',
        'outstanding_loan_interest',
        'outstanding_loan_total',
        'total_entitled',
        'total_deductions',
        'final_payout',
        'payment_status',
        'paid_at',
        'paid_by_id',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    protected $casts = [
        'member_savings' => 'decimal:2',
        'member_share_value' => 'decimal:2',
        'member_fines_paid' => 'decimal:2',
        'member_welfare_contribution' => 'decimal:2',
        'share_percentage' => 'decimal:2',
        'proportional_distribution' => 'decimal:2',
        'loan_interest_share' => 'decimal:2',
        'fine_share' => 'decimal:2',
        'outstanding_loan_principal' => 'decimal:2',
        'outstanding_loan_interest' => 'decimal:2',
        'outstanding_loan_total' => 'decimal:2',
        'total_entitled' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'final_payout' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected $appends = [
        'member_name',
        'payment_status_label',
        'is_payable',
    ];

    // Relationships
    public function shareout()
    {
        return $this->belongsTo(VslaShareout::class, 'shareout_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by_id');
    }

    // Accessors
    public function getMemberNameAttribute()
    {
        return $this->member ? $this->member->name : 'N/A';
    }

    public function getPaymentStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'deferred' => 'Deferred',
            'waived' => 'Waived',
        ];
        return $labels[$this->payment_status] ?? ucfirst($this->payment_status);
    }

    public function getIsPayableAttribute()
    {
        return $this->payment_status === 'pending' && 
               $this->shareout && 
               $this->shareout->status === 'approved';
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeForShareout($query, $shareoutId)
    {
        return $query->where('shareout_id', $shareoutId);
    }

    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    // Helper Methods
    public function markAsPaid($userId, $method = 'cash', $reference = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'paid_by_id' => $userId,
            'payment_method' => $method,
            'payment_reference' => $reference,
        ]);
    }

    public function canMarkAsPaid(): bool
    {
        return $this->payment_status === 'pending' && 
               $this->shareout && 
               in_array($this->shareout->status, ['approved', 'processing']);
    }

    /**
     * Get detailed breakdown for display
     */
    public function getBreakdown(): array
    {
        return [
            'distribution_id' => $this->id,
            'member_id' => $this->member_id,
            'member_name' => $this->member_name,
            
            // Contributions
            'contributions' => [
                'savings' => (float) $this->member_savings,
                'shares_count' => $this->member_shares,
                'shares_value' => (float) $this->member_share_value,
                'fines_paid' => (float) $this->member_fines_paid,
                'welfare_contribution' => (float) $this->member_welfare_contribution,
            ],
            
            // Entitlements
            'entitlements' => [
                'share_percentage' => (float) $this->share_percentage,
                'proportional_distribution' => (float) $this->proportional_distribution,
                'loan_interest_share' => (float) $this->loan_interest_share,
                'fine_share' => (float) $this->fine_share,
                'total_entitled' => (float) $this->total_entitled,
            ],
            
            // Deductions
            'deductions' => [
                'outstanding_loan_principal' => (float) $this->outstanding_loan_principal,
                'outstanding_loan_interest' => (float) $this->outstanding_loan_interest,
                'outstanding_loan_total' => (float) $this->outstanding_loan_total,
                'total_deductions' => (float) $this->total_deductions,
            ],
            
            // Final Payout
            'final_payout' => (float) $this->final_payout,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,
            'is_payable' => $this->is_payable,
        ];
    }
}
