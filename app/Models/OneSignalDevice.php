<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OneSignalDevice extends Model
{
    use HasFactory;

    protected $table = 'onesignal_devices';

    protected $fillable = [
        'user_id',
        'player_id',
        'device_type',
        'device_model',
        'os_version',
        'app_version',
        'timezone',
        'language',
        'country',
        'is_active',
        'last_seen_at',
        'push_token',
        'tags',
        'external_user_id',
        'notification_types',
        'sdk_version',
        'created_from_api',
        'invalid_identifier',
        'badge_count',
        'session_count',
        'amount_spent',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'tags' => 'array',
        'notification_types' => 'array',
        'created_from_api' => 'boolean',
        'invalid_identifier' => 'boolean',
        'amount_spent' => 'decimal:2',
    ];

    protected $dates = [
        'last_seen_at',
    ];

    /**
     * Get the user that owns the device
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for devices seen recently (within last 30 days)
     */
    public function scopeRecentlyActive($query, $days = 30)
    {
        return $query->where('last_seen_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for specific device type
     */
    public function scopeDeviceType($query, $type)
    {
        return $query->where('device_type', $type);
    }

    /**
     * Get platform emoji
     */
    public function getPlatformEmojiAttribute()
    {
        switch (strtolower($this->device_type)) {
            case 'android':
                return '🤖';
            case 'ios':
                return '📱';
            case 'web':
                return '🌐';
            default:
                return '📱';
        }
    }

    /**
     * Get device status badge
     */
    public function getStatusBadgeAttribute()
    {
        if (!$this->is_active) {
            return '<span class="label label-default">Inactive</span>';
        }
        
        if ($this->last_seen_at && $this->last_seen_at->diffInDays() <= 7) {
            return '<span class="label label-success">Active</span>';
        } elseif ($this->last_seen_at && $this->last_seen_at->diffInDays() <= 30) {
            return '<span class="label label-warning">Recently Active</span>';
        } else {
            return '<span class="label label-danger">Stale</span>';
        }
    }

    /**
     * Get formatted last seen
     */
    public function getLastSeenFormatAttribute()
    {
        if (!$this->last_seen_at) {
            return 'Never';
        }
        
        return $this->last_seen_at->diffForHumans();
    }

    /**
     * Update device activity
     */
    public function updateActivity()
    {
        $this->update([
            'last_seen_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Mark device as inactive
     */
    public function markInactive()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get device statistics
     */
    public static function getStatistics()
    {
        return [
            'total' => self::count(),
            'active' => self::active()->count(),
            'android' => self::deviceType('android')->count(),
            'ios' => self::deviceType('ios')->count(),
            'web' => self::deviceType('web')->count(),
            'recently_active' => self::recentlyActive()->count(),
            'stale' => self::where('last_seen_at', '<', now()->subDays(30))->count(),
        ];
    }

    /**
     * Sync device with OneSignal data
     */
    public function syncWithOneSignal($oneSignalData)
    {
        $this->update([
            'device_model' => $oneSignalData['device_model'] ?? $this->device_model,
            'os_version' => $oneSignalData['device_os'] ?? $this->os_version,
            'timezone' => $oneSignalData['timezone'] ?? $this->timezone,
            'language' => $oneSignalData['language'] ?? $this->language,
            'country' => $oneSignalData['country'] ?? $this->country,
            'sdk_version' => $oneSignalData['sdk'] ?? $this->sdk_version,
            'session_count' => $oneSignalData['session_count'] ?? $this->session_count,
            'amount_spent' => $oneSignalData['amount_spent'] ?? $this->amount_spent,
            'badge_count' => $oneSignalData['badge_count'] ?? $this->badge_count,
            'tags' => $oneSignalData['tags'] ?? $this->tags,
            'last_seen_at' => isset($oneSignalData['last_active']) 
                ? \Carbon\Carbon::createFromTimestamp($oneSignalData['last_active']) 
                : $this->last_seen_at,
            'is_active' => $oneSignalData['invalid_identifier'] ? false : true,
        ]);
    }
}
