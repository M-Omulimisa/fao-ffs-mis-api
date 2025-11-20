<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceSubscriptionPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'insurance_subscription_id',
        'user_id',
        'insurance_program_id',
        'period_name',
        'period_start_date',
        'period_end_date',
        'year',
        'month_number',
        'week_number',
        'billing_frequency',
        'due_date',
        'amount',
        'paid_amount',
        'penalty_amount',
        'total_amount',
        'payment_status',
        'payment_date',
        'overdue_date',
        'days_overdue',
        'payment_method',
        'payment_reference',
        'transaction_id',
        'coverage_affected',
        'coverage_suspended_date',
        'description',
        'notes',
        'created_by',
        'updated_by',
        'paid_by',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'overdue_date' => 'date',
        'coverage_suspended_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model = self::validate($model);
            return true;
        });

        static::updating(function ($model) {
            $model = self::validate($model);
            
            // Auto-calculate penalty if overdue
            if ($model->payment_status == 'Overdue' && $model->penalty_amount == 0) {
                $model->calculatePenalty();
            }
            
            // Update total amount
            $model->total_amount = $model->amount + $model->penalty_amount;
            
            return true;
        });

        static::created(function ($model) {
            // Update subscription balances
            $subscription = InsuranceSubscription::find($model->insurance_subscription_id);
            if ($subscription) {
                $subscription->updateBalances();
            }
        });

        static::updated(function ($model) {
            // Update subscription balances
            $subscription = InsuranceSubscription::find($model->insurance_subscription_id);
            if ($subscription) {
                $subscription->updateBalances();
            }
        });

        static::deleting(function ($model) {
            // Update subscription balances
            $subscription = InsuranceSubscription::find($model->insurance_subscription_id);
            if ($subscription) {
                $subscription->updateBalances();
            }
        });
    }

    public static function validate($model)
    {
        // Validate payment status
        if (!in_array($model->payment_status, ['Pending', 'Paid', 'Partial', 'Overdue', 'Waived'])) {
            throw new \Exception("Invalid payment status.", 1);
        }

        // Validate amounts
        $model->amount = abs((float)$model->amount);
        $model->paid_amount = abs((float)$model->paid_amount);
        $model->penalty_amount = abs((float)$model->penalty_amount);

        // If paid, validate paid amount and set payment date
        if ($model->payment_status == 'Paid') {
            if ($model->paid_amount <= 0) {
                throw new \Exception("Paid amount must be greater than zero for paid status.", 1);
            }
            
            if (empty($model->payment_date)) {
                $model->payment_date = Carbon::now()->format('Y-m-d');
            }
        }

        // If not paid, reset paid amount and payment date
        if (in_array($model->payment_status, ['Pending', 'Overdue'])) {
            if ($model->payment_status == 'Pending') {
                $model->paid_amount = 0;
                $model->payment_date = null;
            }
        }

        // Calculate total amount
        $model->total_amount = $model->amount + $model->penalty_amount;

        // Check if overdue
        if ($model->payment_status != 'Paid' && $model->payment_status != 'Waived') {
            $due_date = Carbon::parse($model->due_date);
            $now = Carbon::now();
            
            if ($now->gt($due_date)) {
                $model->payment_status = 'Overdue';
                $model->days_overdue = $now->diffInDays($due_date);
                
                if (empty($model->overdue_date)) {
                    $model->overdue_date = $due_date->addDay()->format('Y-m-d');
                }
            }
        }

        return $model;
    }

    /**
     * Calculate penalty for late payment
     */
    public function calculatePenalty()
    {
        $subscription = InsuranceSubscription::find($this->insurance_subscription_id);
        if (!$subscription) {
            return;
        }

        $program = InsuranceProgram::find($subscription->insurance_program_id);
        if (!$program) {
            return;
        }

        if ($program->late_payment_penalty <= 0) {
            return;
        }

        // Calculate penalty based on type
        if ($program->penalty_type == 'Fixed') {
            $this->penalty_amount = $program->late_payment_penalty;
        } else { // Percentage
            $this->penalty_amount = ($this->amount * $program->late_payment_penalty) / 100;
        }

        $this->total_amount = $this->amount + $this->penalty_amount;
    }

    /**
     * Mark payment as paid
     */
    public function markAsPaid($amount, $payment_method = null, $reference = null)
    {
        $this->payment_status = 'Paid';
        $this->paid_amount = $amount;
        $this->payment_date = Carbon::now()->format('Y-m-d');
        
        if ($payment_method) {
            $this->payment_method = $payment_method;
        }
        
        if ($reference) {
            $this->payment_reference = $reference;
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * Check and update overdue status
     */
    public static function updateOverduePayments()
    {
        $now = Carbon::now();
        
        $overduePayments = self::whereIn('payment_status', ['Pending', 'Partial'])
            ->where('due_date', '<', $now->format('Y-m-d'))
            ->get();
        
        foreach ($overduePayments as $payment) {
            $payment->payment_status = 'Overdue';
            $payment->days_overdue = $now->diffInDays(Carbon::parse($payment->due_date));
            
            if (empty($payment->overdue_date)) {
                $payment->overdue_date = Carbon::parse($payment->due_date)->addDay()->format('Y-m-d');
            }
            
            $payment->calculatePenalty();
            $payment->save();
        }
    }

    /**
     * Relationships
     */
    public function insuranceSubscription()
    {
        return $this->belongsTo(InsuranceSubscription::class, 'insurance_subscription_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function insuranceProgram()
    {
        return $this->belongsTo(InsuranceProgram::class, 'insurance_program_id');
    }

    /**
     * Accessors
     */
    public function getFormattedAmountAttribute()
    {
        return 'UGX ' . number_format($this->amount, 0);
    }

    public function getFormattedPaidAmountAttribute()
    {
        return 'UGX ' . number_format($this->paid_amount, 0);
    }

    public function getFormattedTotalAmountAttribute()
    {
        return 'UGX ' . number_format($this->total_amount, 0);
    }

    public function getIsOverdueAttribute()
    {
        if (in_array($this->payment_status, ['Paid', 'Waived'])) {
            return false;
        }
        
        return Carbon::now()->gt(Carbon::parse($this->due_date));
    }

    public function getStatusColorAttribute()
    {
        switch ($this->payment_status) {
            case 'Paid':
                return 'green';
            case 'Pending':
                return 'orange';
            case 'Overdue':
                return 'red';
            case 'Partial':
                return 'yellow';
            case 'Waived':
                return 'blue';
            default:
                return 'grey';
        }
    }
}
