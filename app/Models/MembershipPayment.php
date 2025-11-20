<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Exception;

class MembershipPayment extends Model
{
    use HasFactory, SoftDeletes;

    const DEFAULT_AMOUNT = 20000; // UGX 20,000 per membership payment
    const MEMBERSHIP_TYPE_LIFE = 'LIFE';
    const MEMBERSHIP_TYPE_ANNUAL = 'ANNUAL';
    const MEMBERSHIP_TYPE_MONTHLY = 'MONTHLY';
    
    const STATUS_PENDING = 'PENDING';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_FAILED = 'FAILED';
    const STATUS_REFUNDED = 'REFUNDED';

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
        'description',
        'notes',
        'receipt_photo',
        'pesapal_order_tracking_id',
        'pesapal_merchant_reference',
        'pesapal_response',
        'confirmation_code',
        'universal_payment_id',
        'created_by',
        'updated_by',
        'confirmed_by',
        'registered_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'confirmed_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Validate user_id
            if (empty($model->user_id) || $model->user_id < 1) {
                throw new Exception("User ID is required for membership payment.");
            }

            // Check if user already has confirmed membership
            $existing = self::where('user_id', $model->user_id)
                ->where('status', self::STATUS_CONFIRMED)
                ->first();
            
            if ($existing) {
                // Allow if it's expired
                if ($existing->expiry_date && $existing->expiry_date < now()) {
                    // Expired, can create new payment
                } else {
                    throw new Exception("User already has an active membership payment.");
                }
            }

            // Generate payment reference if not provided
            if (empty($model->payment_reference)) {
                $model->payment_reference = 'MEM-' . strtoupper(uniqid()) . '-' . $model->user_id;
            }

            // Set default amount if not provided
            if (empty($model->amount) || $model->amount <= 0) {
                $model->amount = self::DEFAULT_AMOUNT;
            }

            // Set default membership type
            if (empty($model->membership_type)) {
                $model->membership_type = self::MEMBERSHIP_TYPE_LIFE;
            }

            // Set default status
            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }

            // Set payment date if not provided
            if (empty($model->payment_date)) {
                $model->payment_date = now();
            }

            // Set created_by if authenticated
            if (Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }

            // Generate description if not provided
            if (empty($model->description)) {
                $user = User::find($model->user_id);
                $userName = $user ? $user->name : 'User';
                $model->description = "Membership payment for {$userName} - {$model->membership_type}";
            }

            return true;
        });

        static::updating(function ($model) {
            // Set updated_by if authenticated
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }

            return true;
        });

        static::updated(function ($model) {
            // If status changed to CONFIRMED, update user's membership status
            if ($model->status == self::STATUS_CONFIRMED && $model->wasChanged('status')) {
                self::updateUserMembership($model);
            }
        });
    }

    /**
     * Update user's membership status after payment confirmation
     */
    public static function updateUserMembership($membershipPayment)
    {
        $user = User::find($membershipPayment->user_id);
        
        if (!$user) {
            return false;
        }

        $user->is_membership_paid = true;
        $user->membership_paid_at = $membershipPayment->confirmed_at ?? now();
        $user->membership_amount = $membershipPayment->amount;
        $user->membership_payment_id = $membershipPayment->id;
        $user->membership_type = $membershipPayment->membership_type;
        $user->membership_expiry_date = $membershipPayment->expiry_date;
        $user->save();

        return true;
    }

    /**
     * Confirm membership payment
     */
    public function confirm($confirmedBy = null)
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->confirmed_at = now();
        
        if ($confirmedBy) {
            $this->confirmed_by = $confirmedBy;
        } elseif (Auth::check()) {
            $this->confirmed_by = Auth::id();
        }

        // Calculate expiry date based on membership type
        if ($this->membership_type == self::MEMBERSHIP_TYPE_ANNUAL) {
            $this->expiry_date = now()->addYear();
        } elseif ($this->membership_type == self::MEMBERSHIP_TYPE_MONTHLY) {
            $this->expiry_date = now()->addMonth();
        } else {
            // LIFE membership never expires
            $this->expiry_date = null;
        }

        $this->save();

        // Update user membership status
        self::updateUserMembership($this);

        return true;
    }

    /**
     * Check if membership is expired
     */
    public function isExpired()
    {
        if ($this->status != self::STATUS_CONFIRMED) {
            return true;
        }

        if ($this->membership_type == self::MEMBERSHIP_TYPE_LIFE) {
            return false; // LIFE membership never expires
        }

        if ($this->expiry_date && $this->expiry_date < now()) {
            return true;
        }

        return false;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        return 'UGX ' . number_format($this->amount, 0);
    }

    /**
     * Relationships
     */
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

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by_id');
    }

    /**
     * Static helper to check if user has valid membership
     */
    public static function userHasValidMembership($userId)
    {
        $payment = self::where('user_id', $userId)
            ->where('status', self::STATUS_CONFIRMED)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$payment) {
            return false;
        }

        return !$payment->isExpired();
    }

    /**
     * Static helper to get user's active membership
     */
    public static function getUserActiveMembership($userId)
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_CONFIRMED)
            ->where(function($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
