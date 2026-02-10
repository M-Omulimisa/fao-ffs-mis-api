<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FfsSessionParticipant extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'attendance_status',
        'remarks',
    ];

    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT = 'absent';
    const STATUS_EXCUSED = 'excused';
    const STATUS_LATE = 'late';

    public static function getAttendanceStatuses()
    {
        return [
            self::STATUS_PRESENT => 'Present',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_EXCUSED => 'Excused',
            self::STATUS_LATE => 'Late',
        ];
    }

    // Relationships
    public function session()
    {
        return $this->belongsTo(FfsTrainingSession::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Accessors
    public function getAttendanceStatusTextAttribute()
    {
        return self::getAttendanceStatuses()[$this->attendance_status] ?? ucfirst($this->attendance_status);
    }
}
