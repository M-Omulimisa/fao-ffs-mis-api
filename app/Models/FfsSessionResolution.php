<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FfsSessionResolution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'session_id',
        'resolution',
        'description',
        'gap_category',
        'responsible_person_id',
        'target_date',
        'status',
        'follow_up_notes',
        'completed_at',
        'created_by_id',
    ];

    protected $casts = [
        'target_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'gap_category_text',
        'status_text',
        'responsible_person_name',
        'is_overdue',
    ];

    // GAP Categories
    const GAP_SOIL = 'soil';
    const GAP_WATER = 'water';
    const GAP_SEEDS = 'seeds';
    const GAP_PEST = 'pest';
    const GAP_HARVEST = 'harvest';
    const GAP_STORAGE = 'storage';
    const GAP_MARKETING = 'marketing';
    const GAP_LIVESTOCK = 'livestock';
    const GAP_OTHER = 'other';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public static function getGapCategories()
    {
        return [
            self::GAP_SOIL => 'Soil Management',
            self::GAP_WATER => 'Water Management',
            self::GAP_SEEDS => 'Seeds & Planting',
            self::GAP_PEST => 'Pest & Disease Control',
            self::GAP_HARVEST => 'Harvesting',
            self::GAP_STORAGE => 'Post-Harvest & Storage',
            self::GAP_MARKETING => 'Marketing',
            self::GAP_LIVESTOCK => 'Livestock Management',
            self::GAP_OTHER => 'Other',
        ];
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    // Relationships
    public function session()
    {
        return $this->belongsTo(FfsTrainingSession::class, 'session_id');
    }

    public function responsiblePerson()
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // Accessors
    public function getGapCategoryTextAttribute()
    {
        return self::getGapCategories()[$this->gap_category] ?? ucfirst($this->gap_category ?? 'N/A');
    }

    public function getStatusTextAttribute()
    {
        return self::getStatuses()[$this->status] ?? ucfirst($this->status);
    }

    public function getResponsiblePersonNameAttribute()
    {
        return $this->responsiblePerson ? $this->responsiblePerson->name : '-';
    }

    public function getIsOverdueAttribute()
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }
        return $this->target_date && \Carbon\Carbon::parse($this->target_date)->isPast();
    }
}
