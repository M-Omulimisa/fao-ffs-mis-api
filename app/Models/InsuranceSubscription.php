<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class InsuranceSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'insurance_program_id',
        'start_date',
        'end_date',
        'next_billing_date',
        'status',
        'payment_status',
        'coverage_status',
        'coverage_start_date',
        'coverage_end_date',
        'premium_amount',
        'total_expected',
        'total_paid',
        'total_balance',
        'payments_completed',
        'payments_pending',
        'notes',
        'beneficiaries',
        'policy_number',
        'prepared',
        'suspended_date',
        'cancelled_date',
        'suspension_reason',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'coverage_start_date' => 'date',
        'coverage_end_date' => 'date',
        'suspended_date' => 'date',
        'cancelled_date' => 'date',
        'premium_amount' => 'decimal:2',
        'total_expected' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_balance' => 'decimal:2',
        'beneficiaries' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model = self::validate($model);
            $model->prepared = 'No';
            $model->total_expected = 0;
            $model->total_paid = 0;
            $model->total_balance = 0;
            $model->payments_completed = 0;
            $model->payments_pending = 0;
            
            // Generate policy number if not set
            if (empty($model->policy_number)) {
                $model->policy_number = self::generatePolicyNumber();
            }
            
            return true;
        });

        static::updating(function ($model) {
            $model = self::validate($model);
            return true;
        });

        static::created(function ($model) {
            // Generate payment records
            self::prepare($model);
            
            // Update program statistics
            $program = InsuranceProgram::find($model->insurance_program_id);
            if ($program) {
                $program->updateStatistics();
            }
        });

        static::updated(function ($model) {
            // Update program statistics
            $program = InsuranceProgram::find($model->insurance_program_id);
            if ($program) {
                $program->updateStatistics();
            }
        });

        static::deleting(function ($model) {
            // Delete all payment records
            InsuranceSubscriptionPayment::where('insurance_subscription_id', $model->id)->delete();
            
            // Update program statistics
            $program = InsuranceProgram::find($model->insurance_program_id);
            if ($program) {
                $program->updateStatistics();
            }
        });
    }

    public static function validate($model)
    {
        // Validate dates
        $start_date = Carbon::parse($model->start_date);
        $end_date = Carbon::parse($model->end_date);

        if ($start_date->gt($end_date)) {
            throw new \Exception("Subscription start date cannot be greater than end date.", 1);
        }

        $days = $start_date->diffInDays($end_date);
        if ($days < 7) {
            throw new \Exception("Subscription period should be at least 7 days.", 1);
        }

        // Validate status
        if (!in_array($model->status, ['Active', 'Suspended', 'Cancelled', 'Expired', 'Pending'])) {
            throw new \Exception("Invalid status.", 1);
        }

        // Validate payment status
        if (!in_array($model->payment_status, ['Current', 'Late', 'Defaulted'])) {
            throw new \Exception("Invalid payment status.", 1);
        }

        // Validate coverage status
        if (!in_array($model->coverage_status, ['Active', 'Suspended', 'Terminated'])) {
            throw new \Exception("Invalid coverage status.", 1);
        }

        // Check if user already has an active subscription to THIS SPECIFIC PROGRAM
        if ($model->status == 'Active') {
            $existingActive = self::where('user_id', $model->user_id)
                ->where('insurance_program_id', $model->insurance_program_id)
                ->where('status', 'Active')
                ->where('id', '!=', $model->id ?? 0)
                ->first();
            
            if ($existingActive) {
                throw new \Exception("User already has an active subscription to this insurance program. You cannot enroll in the same program twice.", 1);
            }
        }

        // Verify program exists
        $program = InsuranceProgram::find($model->insurance_program_id);
        if (!$program) {
            throw new \Exception("Insurance program not found.", 1);
        }

        // Verify user exists
        $user = User::find($model->user_id);
        if (!$user) {
            throw new \Exception("User not found.", 1);
        }

        // Set premium amount from program if not set
        if (empty($model->premium_amount) || $model->premium_amount <= 0) {
            $model->premium_amount = $program->premium_amount;
        }

        return $model;
    }

    /**
     * Generate unique policy number
     */
    public static function generatePolicyNumber()
    {
        do {
            $policyNumber = 'POL-' . strtoupper(uniqid());
        } while (self::where('policy_number', $policyNumber)->exists());
        
        return $policyNumber;
    }

    /**
     * Prepare subscription by generating payment records
     */
    public static function prepare($model)
    {
        if ($model->prepared == 'Yes') {
            return;
        }

        $program = InsuranceProgram::find($model->insurance_program_id);
        if (!$program) {
            throw new \Exception("Insurance program not found.", 1);
        }

        $subscription_start = Carbon::parse($model->start_date);
        $subscription_end = Carbon::parse($model->end_date);

        // Set reasonable limits
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        
        $maxIterations = 1000;
        $iterations = 0;

        // Start from subscription start date (not next billing date)
        $billing_date = $subscription_start->copy();

        while ($billing_date->lt($subscription_end) && $iterations < $maxIterations) {
            $iterations++;

            // Calculate period dates
            $period_start = $billing_date->copy();
            $period_end = self::calculatePeriodEnd($period_start, $program->billing_frequency);
            
            // Ensure period end doesn't exceed subscription end
            if ($period_end->gt($subscription_end)) {
                $period_end = $subscription_end->copy();
            }

            // Generate period name
            $period_name = self::generatePeriodName($period_start, $program->billing_frequency);

            // Calculate due date (same as billing date for now)
            $due_date = $billing_date->copy();

            // Create description
            $description = $program->name . " - " . $period_name;

            // Check if payment already exists to avoid duplicates
            $existingPayment = InsuranceSubscriptionPayment::where('insurance_subscription_id', $model->id)
                ->where('period_start_date', $period_start->format('Y-m-d'))
                ->first();

            if (!$existingPayment) {
                // Create payment record
                InsuranceSubscriptionPayment::create([
                    'insurance_subscription_id' => $model->id,
                    'user_id' => $model->user_id,
                    'insurance_program_id' => $model->insurance_program_id,
                    'period_name' => $period_name,
                    'period_start_date' => $period_start->format('Y-m-d'),
                    'period_end_date' => $period_end->format('Y-m-d'),
                    'year' => $period_start->format('Y'),
                    'month_number' => $period_start->format('m'),
                    'week_number' => $period_start->format('W'),
                    'billing_frequency' => $program->billing_frequency,
                    'due_date' => $due_date->format('Y-m-d'),
                    'amount' => $model->premium_amount,
                    'paid_amount' => 0,
                    'penalty_amount' => 0,
                    'total_amount' => $model->premium_amount,
                    'payment_status' => 'Pending',
                    'description' => $description,
                ]);
            }

            // Move to next billing period based on frequency
            switch ($program->billing_frequency) {
                case 'Weekly':
                    $billing_date->addWeek();
                    break;
                case 'Monthly':
                    $billing_date->addMonth();
                    break;
                case 'Quarterly':
                    $billing_date->addMonths(3);
                    break;
                case 'Annually':
                    $billing_date->addYear();
                    break;
                default:
                    $billing_date->addMonth();
            }
        }

        if ($iterations >= $maxIterations) {
            throw new \Exception("Subscription period too long. Maximum $maxIterations billing periods allowed.", 1);
        }

        // Mark as prepared
        DB::table('insurance_subscriptions')
            ->where('id', $model->id)
            ->update(['prepared' => 'Yes']);

        // Update balances
        $model->updateBalances();
    }

    /**
     * Calculate next billing date based on frequency
     */
    public static function calculateNextBillingDate($current_date, $program)
    {
        $date = Carbon::parse($current_date);
        
        switch ($program->billing_frequency) {
            case 'Weekly':
                return $date->addWeek()->startOfWeek()->addDays($program->billing_day - 1);
            case 'Monthly':
                return $date->addMonth()->startOfMonth()->addDays($program->billing_day - 1);
            case 'Quarterly':
                return $date->addMonths(3)->startOfMonth()->addDays($program->billing_day - 1);
            case 'Annually':
                return $date->addYear()->startOfMonth()->addDays($program->billing_day - 1);
            default:
                return $date->addMonth();
        }
    }

    /**
     * Calculate period end date
     */
    public static function calculatePeriodEnd($period_start, $frequency)
    {
        $date = Carbon::parse($period_start);
        
        switch ($frequency) {
            case 'Weekly':
                return $date->addWeek()->subDay();
            case 'Monthly':
                return $date->addMonth()->subDay();
            case 'Quarterly':
                return $date->addMonths(3)->subDay();
            case 'Annually':
                return $date->addYear()->subDay();
            default:
                return $date->addMonth()->subDay();
        }
    }

    /**
     * Generate period name
     */
    public static function generatePeriodName($date, $frequency)
    {
        $carbon = Carbon::parse($date);
        
        switch ($frequency) {
            case 'Weekly':
                return strtoupper($carbon->format('Y-m-d'));
            case 'Monthly':
                return strtoupper($carbon->format('F-Y'));
            case 'Quarterly':
                $quarter = ceil($carbon->month / 3);
                return "Q{$quarter}-" . $carbon->format('Y');
            case 'Annually':
                return $carbon->format('Y');
            default:
                return strtoupper($carbon->format('F-Y'));
        }
    }

    /**
     * Update subscription balances
     */
    public function updateBalances()
    {
        return DB::transaction(function () {
            $subscription = self::lockForUpdate()->find($this->id);
            
            if (!$subscription) {
                return false;
            }

            // Calculate totals from payment records
            $subscription->total_expected = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subscription->id)
                ->sum('amount');

            $subscription->total_paid = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subscription->id)
                ->where('payment_status', 'Paid')
                ->sum('paid_amount');

            $subscription->total_balance = $subscription->total_expected - $subscription->total_paid;

            $subscription->payments_completed = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subscription->id)
                ->where('payment_status', 'Paid')
                ->count();

            $subscription->payments_pending = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subscription->id)
                ->whereIn('payment_status', ['Pending', 'Partial', 'Overdue'])
                ->count();

            $subscription->save();
            
            return $subscription;
        });
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function insuranceProgram()
    {
        return $this->belongsTo(InsuranceProgram::class, 'insurance_program_id');
    }

    public function payments()
    {
        return $this->hasMany(InsuranceSubscriptionPayment::class, 'insurance_subscription_id');
    }

    public function pendingPayments()
    {
        return $this->hasMany(InsuranceSubscriptionPayment::class, 'insurance_subscription_id')
            ->whereIn('payment_status', ['Pending', 'Overdue']);
    }

    public function paidPayments()
    {
        return $this->hasMany(InsuranceSubscriptionPayment::class, 'insurance_subscription_id')
            ->where('payment_status', 'Paid');
    }
}
