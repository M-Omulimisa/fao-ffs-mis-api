<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Farm extends Model
{
    use HasFactory;

    protected $fillable = [
        'enterprise_id',
        'user_id',
        'name',
        'description',
        'status',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'gps_latitude',
        'gps_longitude',
        'location_text',
        'photo',
        'overall_score',
        'completed_activities_count',
        'total_activities_count',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
        'gps_latitude' => 'decimal:7',
        'gps_longitude' => 'decimal:7',
        'overall_score' => 'decimal:2',
        'completed_activities_count' => 'integer',
        'total_activities_count' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'photo_url',
        'status_text',
        'progress_percentage',
        'days_running',
        'enterprise_text',
        'farmer_text',
    ];

    /**
     * Relationships
     */
    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activities()
    {
        return $this->hasMany(FarmActivity::class);
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

    public function getProgressPercentageAttribute()
    {
        if ($this->total_activities_count == 0) {
            return 0;
        }
        return round(($this->completed_activities_count / $this->total_activities_count) * 100, 1);
    }

    public function getDaysRunningAttribute()
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = $this->actual_end_date
            ? Carbon::parse($this->actual_end_date)
            : Carbon::now();
        
        return $startDate->diffInDays($endDate);
    }

    public function getEnterpriseTextAttribute()
    {
        return $this->enterprise ? $this->enterprise->name : '';
    }

    public function getFarmerTextAttribute()
    {
        return $this->user ? $this->user->name : '';
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Methods
     */
    public function updateScore()
    {
        $totalScore = $this->activities()->sum('score');
        $totalPossible = $this->calculateTotalPossibleScore();
        
        if ($totalPossible > 0) {
            $this->overall_score = ($totalScore / $totalPossible) * 100;
        } else {
            $this->overall_score = 0;
        }
        
        $this->save();
    }

    public function calculateTotalPossibleScore()
    {
        $basePoints = [
            5 => 100,
            4 => 80,
            3 => 60,
            2 => 40,
            1 => 20,
        ];

        $total = 0;
        foreach ($this->activities as $activity) {
            $total += $basePoints[$activity->weight] ?? 20;
        }

        return $total;
    }

    public function updateActivityCounts()
    {
        $this->completed_activities_count = $this->activities()
            ->whereIn('status', ['done', 'skipped'])
            ->count();
        
        $this->total_activities_count = $this->activities()->count();
        
        $this->save();
    }

    public function getStatusBreakdown()
    {
        return [
            'done' => $this->activities()->where('status', 'done')->count(),
            'pending' => $this->activities()->where('status', 'pending')->count(),
            'skipped' => $this->activities()->where('status', 'skipped')->count(),
            'overdue' => $this->activities()->where('status', 'overdue')->count(),
        ];
    }

    /**
     * Auto-generate activities from enterprise protocols
     */
    public function generateActivitiesFromProtocols()
    {
        $protocols = $this->enterprise->productionProtocols;
        $startDate = Carbon::parse($this->start_date);

        foreach ($protocols as $protocol) {
            // Calculate scheduled date based on start time (in weeks)
            $scheduledDate = $startDate->copy()->addWeeks($protocol->start_time);

            FarmActivity::create([
                'farm_id' => $this->id,
                'production_protocol_id' => $protocol->id,
                'activity_name' => $protocol->activity_name,
                'activity_description' => $protocol->activity_description,
                'scheduled_date' => $scheduledDate,
                'scheduled_week' => $protocol->start_time,
                'status' => 'pending',
                'is_mandatory' => $protocol->is_compulsory,
                'weight' => $protocol->is_compulsory ? 5 : 3, // Default weight based on mandatory status
            ]);
        }

        $this->updateActivityCounts();
    }

    /**
     * Boot method to handle events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($farm) {
            // Calculate expected end date based on enterprise duration
            if ($farm->enterprise && !$farm->expected_end_date) {
                $startDate = Carbon::parse($farm->start_date);
                $farm->expected_end_date = $startDate->copy()->addMonths($farm->enterprise->duration);
            }
        });

        static::created(function ($farm) {
            // Auto-generate activities after farm creation
            $farm->generateActivitiesFromProtocols();
        });
    }
}
