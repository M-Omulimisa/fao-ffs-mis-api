<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PesapalLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_type',
        'action',
        'method',
        'endpoint',
        'order_id',
        'tracking_id',
        'merchant_reference',
        'request_data',
        'request_headers',
        'success',
        'status_code',
        'message',
        'response_data',
        'response_headers',
        'amount',
        'currency',
        'customer_name',
        'customer_email',
        'customer_phone',
        'description',
        'response_time_ms',
        'started_at',
        'completed_at',
        'error_message',
        'error_trace',
        'test_scenario',
        'environment',
        'user_agent',
        'ip_address'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'success' => 'boolean',
        'amount' => 'decimal:2',
        'response_time_ms' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function getStatusBadgeAttribute()
    {
        return $this->success ? 'success' : 'failed';
    }

    public function getStatusIconAttribute()
    {
        return $this->success ? '✅' : '❌';
    }

    public function getFormattedAmountAttribute()
    {
        return $this->amount ? number_format($this->amount) . ' ' . $this->currency : 'N/A';
    }

    public function getResponseTimeAttribute()
    {
        return $this->response_time_ms ? $this->response_time_ms . 'ms' : 'N/A';
    }

    public function getDurationAttribute()
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInMilliseconds($this->completed_at) . 'ms';
        }
        return 'N/A';
    }

    // Static methods for logging
    public static function logTest($data)
    {
        $log = new self();
        $log->fill($data);
        $log->started_at = $log->started_at ?: now();
        $log->environment = config('app.env');
        $log->user_agent = request()->header('User-Agent');
        $log->ip_address = request()->ip();
        $log->save();
        
        return $log;
    }

    public static function completeLog($logId, $responseData)
    {
        $log = self::find($logId);
        if ($log) {
            $log->update([
                'completed_at' => now(),
                'response_data' => $responseData['response_data'] ?? null,
                'success' => $responseData['success'] ?? false,
                'message' => $responseData['message'] ?? null,
                'status_code' => $responseData['status_code'] ?? null,
                'error_message' => $responseData['error_message'] ?? null,
                'response_time_ms' => $responseData['response_time_ms'] ?? null
            ]);
            
            return $log;
        }
        
        return null;
    }

    // Statistics methods
    public static function getStats($days = 7)
    {
        $query = self::recent($days);
        
        return [
            'total' => $query->count(),
            'successful' => $query->successful()->count(),
            'failed' => $query->failed()->count(),
            'success_rate' => $query->count() > 0 ? round(($query->successful()->count() / $query->count()) * 100, 2) : 0,
            'avg_response_time' => $query->whereNotNull('response_time_ms')->avg('response_time_ms') ?: 0,
            'total_amount' => $query->successful()->sum('amount') ?: 0
        ];
    }

    public static function getActionStats($action, $days = 7)
    {
        return self::forAction($action)->recent($days)->get()->groupBy('success')->map->count();
    }
}
