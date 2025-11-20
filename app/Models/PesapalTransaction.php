<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesapalTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_tracking_id',
        'merchant_reference',
        'amount',
        'currency',
        'status',
        'status_code',
        'payment_method',
        'confirmation_code',
        'payment_account',
        'redirect_url',
        'callback_url',
        'notification_id',
        'description',
        'pesapal_response',
        'error_message'
    ];

    /**
     * Get the order associated with this transaction
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted()
    {
        return $this->status === 'COMPLETED' || $this->status_code === '1';
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed()
    {
        return in_array($this->status, ['FAILED', 'INVALID']) || $this->status_code === '2';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending()
    {
        return $this->status === 'PENDING' || is_null($this->status) || $this->status_code === '0';
    }

    /**
     * Get the IPN logs for this transaction
     */
    public function ipnLogs()
    {
        return $this->hasMany(PesapalIpnLog::class, 'order_tracking_id', 'order_tracking_id');
    }
}
