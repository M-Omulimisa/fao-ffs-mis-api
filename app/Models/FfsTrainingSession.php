<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FfsTrainingSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'group_id',
        'facilitator_id',
        'title',
        'description',
        'topic',
        'session_date',
        'start_time',
        'end_time',
        'venue',
        'session_type',
        'status',
        'expected_participants',
        'actual_participants',
        'materials_used',
        'notes',
        'challenges',
        'recommendations',
        'photo',
        'created_by_id',
    ];

    protected $casts = [
        'session_date' => 'date',
        'expected_participants' => 'integer',
        'actual_participants' => 'integer',
    ];

    protected $appends = [
        'session_type_text',
        'status_text',
        'group_name',
        'facilitator_name',
        'participants_count',
        'resolutions_count',
    ];

    // Constants
    const TYPE_CLASSROOM = 'classroom';
    const TYPE_FIELD = 'field';
    const TYPE_DEMONSTRATION = 'demonstration';
    const TYPE_WORKSHOP = 'workshop';

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public static function getSessionTypes()
    {
        return [
            self::TYPE_CLASSROOM => 'Classroom',
            self::TYPE_FIELD => 'Field Visit',
            self::TYPE_DEMONSTRATION => 'Demonstration',
            self::TYPE_WORKSHOP => 'Workshop',
        ];
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_ONGOING => 'Ongoing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    // Relationships
    public function group()
    {
        return $this->belongsTo(FfsGroup::class, 'group_id');
    }

    public function facilitator()
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function participants()
    {
        return $this->hasMany(FfsSessionParticipant::class, 'session_id');
    }

    public function resolutions()
    {
        return $this->hasMany(FfsSessionResolution::class, 'session_id');
    }

    // Accessors
    public function getSessionTypeTextAttribute()
    {
        return self::getSessionTypes()[$this->session_type] ?? ucfirst($this->session_type);
    }

    public function getStatusTextAttribute()
    {
        return self::getStatuses()[$this->status] ?? ucfirst($this->status);
    }

    public function getGroupNameAttribute()
    {
        return $this->group ? $this->group->name : '-';
    }

    public function getFacilitatorNameAttribute()
    {
        return $this->facilitator ? $this->facilitator->name : '-';
    }

    public function getParticipantsCountAttribute()
    {
        return $this->participants()->count();
    }

    public function getResolutionsCountAttribute()
    {
        return $this->resolutions()->count();
    }

    /**
     * Update actual participant count from the participants pivot
     */
    /**
     * Update actual participant count from the participants pivot.
     * Counts both 'present' and 'late' as they physically attended.
     */
    public function refreshParticipantCount()
    {
        $this->actual_participants = $this->participants()
            ->whereIn('attendance_status', ['present', 'late'])
            ->count();
        $this->save();
    }

    /**
     * Valid status transitions.
     * Prevents illogical flows like completed -> scheduled.
     */
    public static function getAllowedTransitions()
    {
        return [
            'scheduled'  => ['ongoing', 'cancelled'],
            'ongoing'    => ['completed', 'cancelled'],
            'completed'  => [], // terminal state
            'cancelled'  => ['scheduled'], // can reschedule
        ];
    }

    /**
     * Check if a status transition is allowed.
     */
    public function canTransitionTo($newStatus)
    {
        if ($this->status === $newStatus) return true; // no change
        $allowed = self::getAllowedTransitions()[$this->status] ?? [];
        return in_array($newStatus, $allowed);
    }
}
