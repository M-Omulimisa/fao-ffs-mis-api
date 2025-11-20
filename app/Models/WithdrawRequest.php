<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WithdrawRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'account_balance_before',
        'status',
        'description',
        'payment_method',
        'payment_phone_number',
        'admin_note',
        'processed_by_id',
        'processed_at',
        'account_transaction_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'amount' => 'decimal:2',
        'account_balance_before' => 'decimal:2',
        'processed_by_id' => 'integer',
        'account_transaction_id' => 'integer',
        'processed_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_balance',
        'status_label',
        'can_be_processed',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    public function accountTransaction()
    {
        return $this->belongsTo(AccountTransaction::class);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return 'UGX ' . number_format($this->amount, 2);
    }

    public function getFormattedBalanceAttribute()
    {
        return 'UGX ' . number_format($this->account_balance_before, 2);
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst($this->status);
    }

    public function getCanBeProcessedAttribute()
    {
        return $this->status === 'pending' && is_null($this->account_transaction_id);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('status', 'pending')->whereNull('account_transaction_id');
    }

    /**
     * Validate if user has sufficient balance for withdrawal
     * 
     * @return bool
     */
    public function validateBalance()
    {
        $currentBalance = $this->user->calculateAccountBalance();
        return $currentBalance >= $this->amount && $this->amount > 0;
    }

    /**
     * Approve the withdraw request and create transaction
     * 
     * @param User|\App\Models\Administrator $admin
     * @param string|null $note
     * @return array
     */
    public function approve($admin, $note = null)
    {
        $response = [
            'success' => false,
            'message' => '',
            'transaction' => null,
        ];

        try {
            // Check if already processed
            if ($this->status !== 'pending') {
                $response['message'] = 'This request has already been processed.';
                return $response;
            }

            if ($this->account_transaction_id) {
                $response['message'] = 'Transaction already exists for this request.';
                return $response;
            }

            // Validate balance
            if (!$this->validateBalance()) {
                $response['message'] = 'User has insufficient balance for this withdrawal.';
                return $response;
            }

            // Get admin ID (works for both User and Administrator models)
            $adminId = is_object($admin) ? $admin->id : (int) $admin;

            // Create withdrawal transaction (negative amount)
            $transaction = AccountTransaction::create([
                'user_id' => $this->user_id,
                'amount' => -abs($this->amount), // Ensure negative for withdrawal
                'transaction_date' => now(),
                'description' => 'Withdrawal: ' . ($this->description ?? 'Approved withdraw request #' . $this->id),
                'source' => 'withdrawal',
                'created_by_id' => $adminId,
            ]);

            // Update withdraw request
            $this->status = 'approved';
            $this->processed_by_id = $adminId;
            $this->processed_at = now();
            $this->admin_note = $note;
            $this->account_transaction_id = $transaction->id;
            $this->save();

            $response['success'] = true;
            $response['message'] = 'Withdraw request approved successfully.';
            $response['transaction'] = $transaction;

            return $response;

        } catch (\Exception $e) {
            \Log::error('Withdraw approval failed: ' . $e->getMessage());
            $response['message'] = 'Failed to approve withdrawal: ' . $e->getMessage();
            return $response;
        }
    }

    /**
     * Reject the withdraw request
     * 
     * @param User|\App\Models\Administrator $admin
     * @param string $reason
     * @return array
     */
    public function reject($admin, $reason)
    {
        $response = [
            'success' => false,
            'message' => '',
        ];

        try {
            // Check if already processed
            if ($this->status !== 'pending') {
                $response['message'] = 'This request has already been processed.';
                return $response;
            }

            // Get admin ID (works for both User and Administrator models)
            $adminId = is_object($admin) ? $admin->id : (int) $admin;

            // Update withdraw request
            $this->status = 'rejected';
            $this->processed_by_id = $adminId;
            $this->processed_at = now();
            $this->admin_note = $reason;
            $this->save();

            $response['success'] = true;
            $response['message'] = 'Withdraw request rejected successfully.';

            return $response;

        } catch (\Exception $e) {
            \Log::error('Withdraw rejection failed: ' . $e->getMessage());
            $response['message'] = 'Failed to reject withdrawal: ' . $e->getMessage();
            return $response;
        }
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent deletion of processed requests
        static::deleting(function ($withdrawRequest) {
            if ($withdrawRequest->status !== 'pending') {
                throw new \Exception('Cannot delete processed withdraw requests.');
            }
            if ($withdrawRequest->account_transaction_id) {
                throw new \Exception('Cannot delete withdraw request with associated transaction.');
            }
        });
    }
}
