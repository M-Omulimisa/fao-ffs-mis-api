<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'description',
        'reference_number',
        'payment_method',
        'payment_phone_number',
        'payment_account_number',
        'status',
        'transaction_date',
        'remarks',
        'receipt_photo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    protected $appends = ['formatted_amount', 'is_deposit', 'is_withdrawal'];

    /**
     * Static method to create a transaction with validation and balance update
     */
    public static function createTransaction($data)
    {
        // Validate required fields
        if (!isset($data['user_id']) || $data['user_id'] < 1) {
            throw new Exception("User ID is required.");
        }

        if (!isset($data['amount']) || $data['amount'] == 0) {
            throw new Exception("Amount cannot be zero.");
        }

        $amount = (float) $data['amount'];
        $type = $data['type'] ?? 'DEPOSIT';

        // Ensure amount sign matches type
        if ($type == 'WITHDRAWAL') {
            $amount = abs($amount) * -1; // Make negative
        } else {
            $amount = abs($amount); // Make positive
        }

        // Generate description if not provided
        if (!isset($data['description']) || empty($data['description'])) {
            $user = User::find($data['user_id']);
            if ($type == 'DEPOSIT') {
                $data['description'] = ($user ? $user->name : 'User') . ' deposited UGX ' . number_format(abs($amount));
            } else {
                $data['description'] = ($user ? $user->name : 'User') . ' withdrew UGX ' . number_format(abs($amount));
            }
        }

        // Generate reference number if not provided
        if (!isset($data['reference_number']) || empty($data['reference_number'])) {
            $data['reference_number'] = 'TXN-' . strtoupper(uniqid());
        }

        // Set transaction date to today if not provided
        if (!isset($data['transaction_date']) || empty($data['transaction_date'])) {
            $data['transaction_date'] = date('Y-m-d');
        }

        // Set created_by if authenticated
        if (Auth::check()) {
            $data['created_by'] = Auth::id();
        }

        // Create transaction
        $data['amount'] = $amount;
        $transaction = self::create($data);

        // Update user balance
        self::updateUserBalance($transaction->user_id);

        return $transaction;
    }

    /**
     * Update user's balance based on all their transactions
     */
    public static function updateUserBalance($user_id)
    {
        $user = User::find($user_id);
        if ($user) {
            $balance = self::where('user_id', $user_id)
                ->where('status', 'COMPLETED')
                ->sum('amount');
            
            $user->balance = $balance;
            $user->save();
        }
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Before creating
        self::creating(function ($transaction) {
            // Check for duplicate reference number
            if (!empty($transaction->reference_number)) {
                $existing = self::where('reference_number', $transaction->reference_number)
                    ->where('id', '!=', $transaction->id)
                    ->first();
                if ($existing) {
                    throw new Exception("Duplicate transaction reference number: {$transaction->reference_number}");
                }
            }

            // Set created_by if not set
            if (Auth::check() && !$transaction->created_by) {
                $transaction->created_by = Auth::id();
            }

            // Ensure amount sign matches type
            if ($transaction->type == 'WITHDRAWAL' && $transaction->amount > 0) {
                $transaction->amount = $transaction->amount * -1;
            } elseif ($transaction->type == 'DEPOSIT' && $transaction->amount < 0) {
                $transaction->amount = abs($transaction->amount);
            }
        });

        // After creating
        self::created(function ($transaction) {
            self::updateUserBalance($transaction->user_id);
        });

        // After updating
        self::updated(function ($transaction) {
            self::updateUserBalance($transaction->user_id);
        });

        // After deleting
        self::deleted(function ($transaction) {
            self::updateUserBalance($transaction->user_id);
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeDeposits($query)
    {
        return $query->where('type', 'DEPOSIT');
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'WITHDRAWAL');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
                ->orWhere('reference_number', 'like', "%{$search}%")
                ->orWhere('payment_phone_number', 'like', "%{$search}%")
                ->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
        });
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return 'UGX ' . number_format(abs($this->amount), 0);
    }

    public function getIsDepositAttribute()
    {
        return $this->type === 'DEPOSIT';
    }

    public function getIsWithdrawalAttribute()
    {
        return $this->type === 'WITHDRAWAL';
    }

    // Helper methods
    public function isCompleted()
    {
        return $this->status === 'COMPLETED';
    }

    public function isPending()
    {
        return $this->status === 'PENDING';
    }

    public function approve()
    {
        $this->status = 'COMPLETED';
        $this->save();
        return $this;
    }

    public function cancel()
    {
        $this->status = 'CANCELLED';
        $this->save();
        return $this;
    }
}
