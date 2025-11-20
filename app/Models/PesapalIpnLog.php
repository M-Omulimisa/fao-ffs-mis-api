<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesapalIpnLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_tracking_id',
        'merchant_reference',
        'notification_type',
        'request_method',
        'payload',
        'ip_address',
        'user_agent',
        'status',
        'processed_at',
        'processing_notes',
        'response_sent'
    ];

    /**
     * Get the transaction associated with this IPN log
     */
    public function transaction()
    {
        return $this->belongsTo(PesapalTransaction::class, 'order_tracking_id', 'order_tracking_id');
    }

    /**
     * Check if this IPN has been processed
     */
    public function isProcessed()
    {
        return $this->status === 'PROCESSED';
    }

    /**
     * Mark this IPN as processed
     */
    public function markAsProcessed($notes = null)
    {
        $this->status = 'PROCESSED';
        $this->processed_at = now();
        if ($notes) {
            $this->processing_notes = $notes;
        }
        $this->save();
    }

    /**
     * Mark this IPN as error
     */
    public function markAsError($error_message)
    {
        $this->status = 'ERROR';
        $this->processing_notes = $error_message;
        $this->save();
    }
}
