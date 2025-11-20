<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DtehmMembership extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtehm_memberships';

    // Constants
    const DEFAULT_AMOUNT = 76000;
    const MEMBERSHIP_TYPE = 'DTEHM';
    
    const STATUS_PENDING = 'PENDING';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_FAILED = 'FAILED';
    const STATUS_REFUNDED = 'REFUNDED';
    
    const PAYMENT_METHOD_CASH = 'CASH';
    const PAYMENT_METHOD_MOBILE_MONEY = 'MOBILE_MONEY';
    const PAYMENT_METHOD_BANK_TRANSFER = 'BANK_TRANSFER';
    const PAYMENT_METHOD_PESAPAL = 'PESAPAL';

    protected $fillable = [
        'user_id',
        'payment_reference',
        'amount',
        'status',
        'payment_method',
        'payment_phone_number',
        'payment_account_number',
        'payment_date',
        'confirmed_at',
        'membership_type',
        'expiry_date',
        'receipt_photo',
        'description',
        'notes',
        'pesapal_merchant_reference',
        'pesapal_tracking_id',
        'pesapal_payment_status_code',
        'pesapal_payment_status_description',
        'confirmation_code',
        'universal_payment_id',
        'created_by',
        'updated_by',
        'confirmed_by',
        'registered_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'confirmed_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    protected $appends = ['formatted_amount'];

    // Boot method for automatic validation and setup
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($membership) {
            // Validate user_id
            if (!$membership->user_id) {
                throw new \Exception('User ID is required for DTEHM membership payment');
            }

            // Check if user already has active DTEHM membership
            $existingMembership = self::where('user_id', $membership->user_id)
                ->where('status', self::STATUS_PENDING)
                ->first();

            if ($existingMembership) {
                throw new \Exception('User already has an active DTEHM membership payment pending');
            }

            // Auto-generate payment reference if not set
            if (!$membership->payment_reference) {
                $membership->payment_reference = self::generatePaymentReference();
            }

            // Set default amount if not set
            if (!$membership->amount) {
                $membership->amount = self::DEFAULT_AMOUNT;
            }

            // Set membership type
            $membership->membership_type = self::MEMBERSHIP_TYPE;

            // Set default status
            if (!$membership->status) {
                $membership->status = self::STATUS_PENDING;
            }

            // Set payment date to now if not set
            if (!$membership->payment_date) {
                $membership->payment_date = now();
            }
        });

        static::updating(function ($membership) {
            // If status changed to CONFIRMED and not already confirmed
            if ($membership->isDirty('status') && $membership->status === self::STATUS_CONFIRMED && !$membership->confirmed_at) {
                $membership->confirmed_at = now();
            }
        });

        static::updated(function ($membership) {
            // If confirmed, update user model
            if ($membership->status === self::STATUS_CONFIRMED && $membership->confirmed_at) {
                self::updateUserMembership($membership);
            }
        });
    }

    /**
     * Generate unique payment reference
     */
    public static function generatePaymentReference()
    {
        do {
            $reference = 'DTEHM-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)) . '-' . rand(1, 99);
        } while (self::where('payment_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Confirm DTEHM membership payment
     */
    public function confirm($confirmedBy = null)
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->confirmed_at = now();
        
        if ($confirmedBy) {
            $this->confirmed_by = $confirmedBy;
        }

        // DTEHM membership is lifetime (no expiry)
        $this->expiry_date = null;

        $this->save();

        // Update user model
        self::updateUserMembership($this);

        return $this;
    }

    /**
     * Update user model with DTEHM membership details
     */
    protected static function updateUserMembership($payment)
    {
        $user = User::find($payment->user_id);
        if ($user) {
            $user->is_dtehm_member = true;
            $user->dtehm_membership_paid_at = $payment->confirmed_at;
            $user->dtehm_membership_amount = $payment->amount;
            $user->dtehm_membership_payment_id = $payment->id;
            $user->save();
        }
    }

    /**
     * Check if user has valid DTEHM membership
     */
    public static function userHasValidDtehmMembership($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        return $user->is_dtehm_member;
    }

    /**
     * Get user's active DTEHM membership
     */
    public static function getUserActiveDtehmMembership($userId)
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_CONFIRMED)
            ->orderBy('confirmed_at', 'desc')
            ->first();
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function universalPayment()
    {
        return $this->belongsTo(UniversalPayment::class, 'universal_payment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by_id');
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return 'UGX ' . number_format($this->amount, 0);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}
