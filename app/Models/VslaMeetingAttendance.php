<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VslaMeetingAttendance extends Model
{
    protected $table = 'vsla_meeting_attendance';

    protected $fillable = [
        'local_id',
        'meeting_id',
        'member_id',
        'is_present',
        'absent_reason',
    ];

    protected $casts = [
        'is_present' => 'boolean',
    ];

    // Relationships
    public function meeting()
    {
        return $this->belongsTo(VslaMeeting::class, 'meeting_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    // Scopes
    public function scopePresent($query)
    {
        return $query->where('is_present', true);
    }

    public function scopeAbsent($query)
    {
        return $query->where('is_present', false);
    }

    public function scopeByMeeting($query, $meetingId)
    {
        return $query->where('meeting_id', $meetingId);
    }

    public function scopeByMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }
}
