<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\OneSignalService;

class NotificationModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'type',
        'target_type',
        'template',
        'target_users',
        'target_segments',
        'target_devices',
        'filters',
        'onesignal_id',
        'recipients',
        'status',
        'delivery_type',
        'scheduled_at',
        'recurring_pattern',
        'start_at',
        'end_at',
        'error_message',
        'data',
        'send_after_time_passed',
        'ttl',
        'priority_countries',
        'url',
        'icon_type',
        'picture_type',
        'large_icon',
        'large_icon_upload',
        'big_picture',
        'big_picture_upload',
        'sent_at',
        'created_by',
        'click_count',
    ];

    protected $casts = [
        'target_users' => 'array',
        'target_segments' => 'array',
        'target_devices' => 'array',
        'filters' => 'array',
        'data' => 'array',
        'priority_countries' => 'array',
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'send_after_time_passed' => 'boolean',
    ];

    protected $dates = [
        'sent_at',
        'scheduled_at',
        'start_at',
        'end_at',
    ];

    /**
     * MUTATORS - Convert form data to proper database format
     */
    
    /**
     * Mutator for target_users - ensures array is stored as JSON
     */
    public function setTargetUsersAttribute($value)
    {
        if (is_string($value)) {
            // Handle comma-separated string or JSON string
            if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
                $decoded = json_decode($value, true);
                $this->attributes['target_users'] = json_encode($decoded ?? []);
            } else {
                // Comma-separated string
                $array = array_filter(array_map('trim', explode(',', $value)));
                $this->attributes['target_users'] = json_encode($array);
            }
        } elseif (is_array($value)) {
            $this->attributes['target_users'] = json_encode(array_filter($value));
        } else {
            $this->attributes['target_users'] = json_encode([]);
        }
    }

    /**
     * Mutator for target_segments - handles tags field input
     */
    public function setTargetSegmentsAttribute($value)
    {
        if (is_string($value)) {
            if (empty(trim($value))) {
                $this->attributes['target_segments'] = json_encode([]);
            } elseif (str_starts_with($value, '[') || str_starts_with($value, '{')) {
                // JSON string
                $decoded = json_decode($value, true);
                $this->attributes['target_segments'] = json_encode($decoded ?? []);
            } else {
                // Comma-separated tags or single string
                $segments = array_filter(array_map('trim', explode(',', $value)));
                $this->attributes['target_segments'] = json_encode($segments);
            }
        } elseif (is_array($value)) {
            $this->attributes['target_segments'] = json_encode(array_filter($value));
        } else {
            $this->attributes['target_segments'] = json_encode([]);
        }
    }

    /**
     * Mutator for target_devices - handles checkbox arrays
     */
    public function setTargetDevicesAttribute($value)
    {
        if (is_string($value)) {
            if (empty(trim($value))) {
                $this->attributes['target_devices'] = json_encode([]);
            } else {
                $decoded = json_decode($value, true);
                $this->attributes['target_devices'] = json_encode($decoded ?? []);
            }
        } elseif (is_array($value)) {
            $this->attributes['target_devices'] = json_encode(array_filter($value));
        } else {
            $this->attributes['target_devices'] = json_encode([]);
        }
    }

    /**
     * Mutator for priority_countries - handles multiselect arrays
     */
    public function setPriorityCountriesAttribute($value)
    {
        if (is_string($value)) {
            if (empty(trim($value))) {
                $this->attributes['priority_countries'] = json_encode([]);
            } else {
                $decoded = json_decode($value, true);
                $this->attributes['priority_countries'] = json_encode($decoded ?? []);
            }
        } elseif (is_array($value)) {
            $this->attributes['priority_countries'] = json_encode(array_filter($value));
        } else {
            $this->attributes['priority_countries'] = json_encode([]);
        }
    }

    /**
     * Mutator for filters - handles complex filter arrays
     */
    public function setFiltersAttribute($value)
    {
        if (is_string($value)) {
            if (empty(trim($value))) {
                $this->attributes['filters'] = json_encode([]);
            } else {
                $decoded = json_decode($value, true);
                $this->attributes['filters'] = json_encode($decoded ?? []);
            }
        } elseif (is_array($value)) {
            $this->attributes['filters'] = json_encode($value);
        } else {
            $this->attributes['filters'] = json_encode([]);
        }
    }

    /**
     * Mutator for data - handles key-value pairs from admin form
     */
    public function setDataAttribute($value)
    {
        if (is_string($value)) {
            if (empty(trim($value))) {
                $this->attributes['data'] = json_encode([]);
            } else {
                $decoded = json_decode($value, true);
                $this->attributes['data'] = json_encode($decoded ?? []);
            }
        } elseif (is_array($value)) {
            // Filter out empty values
            $filtered = array_filter($value, function($val) {
                return !is_null($val) && $val !== '';
            });
            $this->attributes['data'] = json_encode($filtered);
        } else {
            $this->attributes['data'] = json_encode([]);
        }
    }

    /**
     * ACCESSORS - Convert database JSON back to usable format
     */
    
    /**
     * Accessor for target_users - returns array
     */
    public function getTargetUsersAttribute($value)
    {
        if (is_null($value) || $value === '') {
            return [];
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Accessor for target_segments - returns array  
     */
    public function getTargetSegmentsAttribute($value)
    {
        if (is_null($value) || $value === '') {
            return [];
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Accessor for target_devices - returns array
     */
    public function getTargetDevicesAttribute($value)
    {
        if (is_null($value) || $value === '') {
            return [];
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Accessor for priority_countries - returns array
     */
    public function getPriorityCountriesAttribute($value)
    {
        if (is_null($value) || $value === '') {
            return [];
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Accessor for filters - returns array
     */
    public function getFiltersAttribute($value)
    {
        if (is_null($value) || $value === '') {
            return [];
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Accessor for data - returns array
     */
    public function getDataAttribute($value)
    {
        if (is_null($value) || $value === '') {
            return [];
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get the user who created this notification
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Send this notification via OneSignal
     */
    public function send()
    {
        $oneSignal = new OneSignalService();
        
        // Update status to pending
        $this->update(['status' => 'pending']);

        try {
            $result = null;

            // Determine sending method based on target
            if ($this->target_users && count($this->target_users) > 0) {
                // Send to specific users
                $result = $oneSignal->sendToUsers(
                    $this->target_users,
                    $this->title,
                    $this->message,
                    $this->data ?? [],
                    $this->url,
                    $this->large_icon,
                    $this->big_picture
                );
            } elseif ($this->target_segments && count($this->target_segments) > 0) {
                // Send to segments
                $result = $oneSignal->sendToSegments(
                    $this->target_segments,
                    $this->title,
                    $this->message,
                    $this->data ?? [],
                    $this->url,
                    $this->large_icon,
                    $this->big_picture
                );
            } elseif ($this->filters && count($this->filters) > 0) {
                // Send with filters
                $result = $oneSignal->sendAdvanced(
                    $this->filters,
                    $this->title,
                    $this->message,
                    $this->data ?? [],
                    $this->url,
                    null, // subtitle
                    $this->large_icon,
                    $this->big_picture
                );
            } else {
                // Send to all users
                $result = $oneSignal->sendToAll(
                    $this->title,
                    $this->message,
                    $this->data ?? [],
                    $this->url,
                    $this->large_icon,
                    $this->big_picture
                );
            }

            if ($result['success']) {
                $this->update([
                    'status' => 'sent',
                    'onesignal_id' => $result['notification_id'],
                    'recipients' => $result['recipients'],
                    'sent_at' => now(),
                    'error_message' => null,
                ]);
            } else {
                $this->update([
                    'status' => 'failed',
                    'error_message' => $result['error'],
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            $this->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel this notification
     */
    public function cancel()
    {
        if ($this->onesignal_id && $this->status === 'sent') {
            $oneSignal = new OneSignalService();
            $result = $oneSignal->cancelNotification($this->onesignal_id);
            
            if ($result['success']) {
                $this->update(['status' => 'cancelled']);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get notification stats from OneSignal
     */
    public function getStats()
    {
        if ($this->onesignal_id) {
            $oneSignal = new OneSignalService();
            return $oneSignal->getNotification($this->onesignal_id);
        }
        
        return null;
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get formatted target description
     */
    public function getTargetDescriptionAttribute()
    {
        if ($this->target_users && count($this->target_users) > 0) {
            return count($this->target_users) . ' specific users';
        } elseif ($this->target_segments && count($this->target_segments) > 0) {
            return 'Segments: ' . implode(', ', $this->target_segments);
        } elseif ($this->filters && count($this->filters) > 0) {
            return 'Custom filters applied';
        } else {
            return 'All users';
        }
    }
}
