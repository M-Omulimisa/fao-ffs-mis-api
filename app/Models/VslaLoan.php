<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class VslaLoan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cycle_id',
        'meeting_id',
        'borrower_id',
        'loan_amount',
        'interest_rate',
        'duration_months',
        'total_amount_due',
        'amount_paid',
        'balance',
        'disbursement_date',
        'due_date',
        'purpose',
        'status',
        'created_by_id',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'total_amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'disbursement_date' => 'date',
        'due_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($loan) {
            // Auto-calculate total_amount_due if not set
            if (empty($loan->total_amount_due)) {
                $principal = floatval($loan->loan_amount);
                $interestRate = floatval($loan->interest_rate ?? 0);
                $interest = $principal * ($interestRate / 100);
                $loan->total_amount_due = $principal + $interest;
            }

            // Auto-calculate balance if not set
            if (is_null($loan->balance)) {
                $loan->balance = $loan->total_amount_due;
            }

            // Auto-calculate due_date if not set
            if (empty($loan->due_date) && !empty($loan->disbursement_date) && !empty($loan->duration_months)) {
                $loan->due_date = Carbon::parse($loan->disbursement_date)
                    ->addMonths($loan->duration_months);
            }
        });
    }

    // Relationships
    public function cycle()
    {
        return $this->belongsTo(Project::class, 'cycle_id');
    }

    public function meeting()
    {
        return $this->belongsTo(VslaMeeting::class, 'meeting_id');
    }

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function loanTransactions()
    {
        return $this->hasMany(LoanTransaction::class, 'loan_id');
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        $labels = [
            'active' => 'Active',
            'paid' => 'Paid',
            'defaulted' => 'Defaulted',
        ];
        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getIsOverdueAttribute()
    {
        if ($this->status === 'paid') {
            return false;
        }
        return $this->due_date && Carbon::parse($this->due_date)->isPast();
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return Carbon::parse($this->due_date)->diffInDays(Carbon::now());
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'active')
            ->where('due_date', '<', now());
    }

    public function scopeByCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    public function scopeByBorrower($query, $borrowerId)
    {
        return $query->where('borrower_id', $borrowerId);
    }

    // Methods
    public function recordPayment($amount)
    {
        $this->amount_paid += $amount;
        $this->balance = $this->total_amount_due - $this->amount_paid;

        if ($this->balance <= 0) {
            $this->status = 'paid';
            $this->balance = 0;
        }

        $this->save();
    }
}
