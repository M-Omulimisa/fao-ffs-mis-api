<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TinifyModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_key',
        'status',
        'usage_count',
        'monthly_limit',
        'compressions_this_month',
        'last_used_at',
        'last_reset_at',
        'notes'
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'last_reset_at' => 'datetime',
    ];

    /**
     * Get a random active API key for compression
     */
    public static function getRandomActiveKey()
    {
        // Reset monthly counters if needed
        self::resetMonthlyCountersIfNeeded();
        
        // Get active keys that haven't exceeded monthly limit
        $availableKeys = self::where([])
            ->get();

        if ($availableKeys->isEmpty()) {
            return null;
        }

        // Return random key
        return $availableKeys->random();
    }

    /**
     * Mark this key as used and increment usage counters
     */
    public function markAsUsed()
    {
        $this->increment('usage_count');
        $this->increment('compressions_this_month');
        $this->last_used_at = now();
        $this->save();
    }

    /**
     * Mark this key as failed (for rate limiting or errors)
     */
    public function markAsFailed($reason = null)
    {
        $this->status = 'inactive';
        if ($reason) {
            $this->notes = $reason;
        }
        $this->save();
    }

    /**
     * Reset monthly counters for all keys if new month
     */
    public static function resetMonthlyCountersIfNeeded()
    {
        $keysNeedingReset = self::where(function($query) {
            $query->whereNull('last_reset_at')
                  ->orWhere('last_reset_at', '<', Carbon::now()->startOfMonth());
        })->get();

        foreach ($keysNeedingReset as $key) {
            $key->compressions_this_month = 0;
            $key->last_reset_at = now();
            $key->save();
        }
    }

    /**
     * Get usage statistics
     */
    public static function getUsageStats()
    {
        return [
            'total_keys' => self::count(),
            'active_keys' => self::where('status', 'active')->count(),
            'total_compressions' => self::sum('usage_count'),
            'this_month_compressions' => self::sum('compressions_this_month'),
            'available_capacity' => self::where('status', 'active')
                ->selectRaw('SUM(monthly_limit - compressions_this_month) as available')
                ->value('available') ?? 0
        ];
    }

    /**
     * Get individual key statistics for display
     */
    public static function getIndividualKeyStats()
    {
        // Reset monthly counters if needed first
        self::resetMonthlyCountersIfNeeded();
        
        return self::all()->map(function ($key) {
            return [
                'id' => $key->id,
                'status' => $key->status,
                'monthly_usage' => $key->compressions_this_month,
                'monthly_limit' => $key->monthly_limit,
                'total_usage' => $key->usage_count,
                'last_used_at' => $key->last_used_at,
            ];
        })->toArray();
    }
}
