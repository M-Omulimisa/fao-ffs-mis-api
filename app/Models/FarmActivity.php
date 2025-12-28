<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'production_protocol_id',
        'activity_name',
        'activity_description',
        'scheduled_date',
        'scheduled_week',
        'actual_completion_date',
        'status',
        'is_mandatory',
        'is_custom',
        'weight',
        'target_value',
        'actual_value',
        'score',
        'notes',
        'photo',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'actual_completion_date' => 'date',
        'is_mandatory' => 'boolean',
        'is_custom' => 'boolean',
        'weight' => 'integer',
        'target_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'score' => 'decimal:2',
        'scheduled_week' => 'integer',
    ];

    protected $appends = [
        'photo_url',
        'status_text',
        'weight_text',
        'weight_stars',
        'priority_color',
        'is_overdue',
        'days_until_due',
        'protocol_text',
    ];

    /**
     * Relationships
     */
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function protocol()
    {
        return $this->belongsTo(ProductionProtocol::class, 'production_protocol_id');
    }

    /**
     * Accessors
     */
    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            return url('storage/images/' . $this->photo);
        }
        return null;
    }

    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }

    public function getWeightTextAttribute()
    {
        $labels = [
            5 => 'Critical',
            4 => 'Very High',
            3 => 'High',
            2 => 'Medium',
            1 => 'Normal',
        ];
        return $labels[$this->weight] ?? 'Normal';
    }

    public function getWeightStarsAttribute()
    {
        return str_repeat('⭐', $this->weight);
    }

    public function getPriorityColorAttribute()
    {
        $colors = [
            5 => '#D32F2F', // Red
            4 => '#E64A19', // Deep Orange
            3 => '#F57C00', // Orange
            2 => '#FFA726', // Light Orange
            1 => '#9E9E9E', // Grey
        ];
        return $colors[$this->weight] ?? '#9E9E9E';
    }

    public function getIsOverdueAttribute()
    {
        if ($this->status === 'pending') {
            return Carbon::parse($this->scheduled_date)->isPast();
        }
        return false;
    }

    public function getDaysUntilDueAttribute()
    {
        $scheduledDate = Carbon::parse($this->scheduled_date);
        $now = Carbon::now();
        
        if ($scheduledDate->isFuture()) {
            return $now->diffInDays($scheduledDate);
        } else {
            return -$now->diffInDays($scheduledDate); // Negative means overdue
        }
    }

    public function getProtocolTextAttribute()
    {
        return $this->protocol ? $this->protocol->activity_name : 'Manual Activity';
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDone($query)
    {
        return $query->where('status', 'done');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_date', '<', Carbon::now());
    }

    public function scopeUpcoming($query, $days = 7)
    {
        $endDate = Carbon::now()->addDays($days);
        return $query->where('status', 'pending')
            ->whereBetween('scheduled_date', [Carbon::now(), $endDate]);
    }

    /**
     * Methods
     */
    public function markAsDone($actualDate = null, $actualValue = null, $notes = null, $photo = null)
    {
        $this->status = 'done';
        $this->actual_completion_date = $actualDate ?? Carbon::now();
        $this->actual_value = $actualValue;
        $this->notes = $notes;
        $this->photo = $photo;
        
        $this->calculateScore();
        $this->save();
        
        // Update farm statistics
        $this->farm->updateActivityCounts();
        $this->farm->updateScore();
    }

    public function markAsSkipped($notes = null)
    {
        $this->status = 'skipped';
        $this->actual_completion_date = Carbon::now();
        $this->notes = $notes;
        
        $this->calculateScore();
        $this->save();
        
        // Update farm statistics
        $this->farm->updateActivityCounts();
        $this->farm->updateScore();
    }

    public function calculateScore()
    {
        // Base points by weight
        $basePoints = [
            5 => 100,
            4 => 80,
            3 => 60,
            2 => 40,
            1 => 20,
        ];

        $base = $basePoints[$this->weight] ?? 20;

        if ($this->status === 'done') {
            // Calculate time factor
            $scheduledDate = Carbon::parse($this->scheduled_date);
            $actualDate = Carbon::parse($this->actual_completion_date);
            $daysDiff = $scheduledDate->diffInDays($actualDate, false);

            $timeFactor = 1.0;
            if ($daysDiff < 0) {
                // Done early
                $timeFactor = 1.1;
            } elseif ($daysDiff <= 2) {
                // On time (±2 days)
                $timeFactor = 1.0;
            } elseif ($daysDiff <= 7) {
                // Late (3-7 days)
                $timeFactor = 0.8;
            } elseif ($daysDiff <= 14) {
                // Very late (8-14 days)
                $timeFactor = 0.6;
            } else {
                // Extremely late (>14 days)
                $timeFactor = 0.4;
            }

            $this->score = $base * $timeFactor;
        } elseif ($this->status === 'skipped') {
            if ($this->is_mandatory) {
                // Penalty for skipping mandatory activity
                $this->score = -($base * 0.5);
            } else {
                // No penalty for skipping optional activity
                $this->score = 0;
            }
        } else {
            $this->score = 0;
        }
    }

    /**
     * Update overdue status
     */
    public static function updateOverdueStatuses()
    {
        self::where('status', 'pending')
            ->where('scheduled_date', '<', Carbon::now())
            ->update(['status' => 'overdue']);
    }

    /**
     * Boot method to handle events
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($activity) {
            // Auto-update overdue status
            if ($activity->status === 'pending' && $activity->is_overdue) {
                $activity->status = 'overdue';
            }
        });

        static::saved(function ($activity) {
            // Update farm statistics whenever activity changes
            if ($activity->wasChanged('status')) {
                $activity->farm->updateActivityCounts();
                $activity->farm->updateScore();
            }
        });
    }
}
