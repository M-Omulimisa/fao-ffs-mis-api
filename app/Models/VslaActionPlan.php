<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VslaActionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'local_id',
        'meeting_id',
        'cycle_id',
        'action',
        'description',
        'assigned_to_member_id',
        'priority',
        'due_date',
        'status',
        'completion_notes',
        'completed_at',
        'created_by_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function meeting()
    {
        return $this->belongsTo(VslaMeeting::class, 'meeting_id');
    }

    public function cycle()
    {
        return $this->belongsTo(Project::class, 'cycle_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_member_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            return false;
        }
        return $this->due_date && \Carbon\Carbon::parse($this->due_date)->isPast();
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return \Carbon\Carbon::parse($this->due_date)->diffInDays(\Carbon\Carbon::now());
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in-progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled'])
            ->where('due_date', '<', now());
    }

    public function scopeByCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    public function scopeByMeeting($query, $meetingId)
    {
        return $query->where('meeting_id', $meetingId);
    }

    public function scopeAssignedTo($query, $memberId)
    {
        return $query->where('assigned_to_member_id', $memberId);
    }

    // Methods
    public function start()
    {
        $this->update(['status' => 'in-progress']);
    }

    public function complete($notes = null)
    {
        $this->update([
            'status' => 'completed',
            'completion_notes' => $notes,
            'completed_at' => now(),
        ]);
    }

    public function cancel($notes = null)
    {
        $this->update([
            'status' => 'cancelled',
            'completion_notes' => $notes,
        ]);
    }
}
