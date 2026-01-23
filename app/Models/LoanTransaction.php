<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * LoanTransaction Model
 * 
 * Tracks every event in a loan's lifecycle for detailed audit trail and balance calculation.
 * 
 * Transaction Types:
 * - principal: Initial loan amount disbursed (negative)
 * - interest: Interest charged on loan (negative)
 * - payment: Member repayment (positive)
 * - penalty: Late payment or other penalties (negative)
 * - waiver: Debt forgiveness/reduction (positive)
 * - adjustment: Manual correction (positive or negative)
 * 
 * Balance Calculation:
 * Loan Balance = SUM(all amounts)
 * - Negative balance = member owes money
 * - Zero balance = loan fully paid
 * - Positive balance = overpayment (rare)
 * 
 * Integration with AccountTransaction:
 * - When loan disbursed: 2 AccountTransactions created (group + member)
 * - When loan paid: 2 AccountTransactions created (group + member)
 * - LoanTransactions provide loan-specific detail
 * - AccountTransactions provide group/member cash flow
 */
class LoanTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_id',
        'amount',
        'transaction_date',
        'description',
        'type',
        'payment_method',
        'transaction_type',
        'created_by_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_date',
        'type_label',
        'is_debit',
    ];

    // Transaction type constants
    const TYPE_PRINCIPAL = 'principal';
    const TYPE_INTEREST = 'interest';
    const TYPE_PAYMENT = 'payment';
    const TYPE_PENALTY = 'penalty';
    const TYPE_WAIVER = 'waiver';
    const TYPE_ADJUSTMENT = 'adjustment';

    // Relationships
    public function loan()
    {
        return $this->belongsTo(VslaLoan::class, 'loan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        $prefix = $this->amount >= 0 ? '+' : '';
        return $prefix . 'UGX ' . number_format(abs($this->amount), 2);
    }

    public function getFormattedDateAttribute()
    {
        return $this->transaction_date->format('d M Y');
    }

    public function getTypeLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getIsDebitAttribute()
    {
        return $this->amount < 0;
    }

    // Scopes
    public function scopeForLoan($query, $loanId)
    {
        return $query->where('loan_id', $loanId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDebits($query)
    {
        return $query->where('amount', '<', 0);
    }

    public function scopeCredits($query)
    {
        return $query->where('amount', '>=', 0);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // Helper methods
    public static function calculateLoanBalance($loanId)
    {
        return self::where('loan_id', $loanId)->sum('amount');
    }

    public static function getLoanHistory($loanId)
    {
        return self::where('loan_id', $loanId)
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();
    }
}
