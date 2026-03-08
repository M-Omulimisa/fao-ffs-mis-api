<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * KpiBenchmark — single-record table holding global facilitator KPI targets.
 *
 * Only ONE row should ever exist; use KpiBenchmark::current() to fetch it.
 */
class KpiBenchmark extends Model
{
    protected $table = 'kpi_benchmarks';

    protected $fillable = [
        'min_groups_per_facilitator',
        'min_trainings_per_week',
        'min_meetings_per_group_per_week',
        'min_members_per_group',
        'min_aesa_sessions_per_week',
        'min_meeting_attendance_pct',
        'updated_by_id',
    ];

    protected $casts = [
        'min_groups_per_facilitator'      => 'integer',
        'min_trainings_per_week'          => 'integer',
        'min_meetings_per_group_per_week' => 'integer',
        'min_members_per_group'           => 'integer',
        'min_aesa_sessions_per_week'      => 'integer',
        'min_meeting_attendance_pct'      => 'float',
    ];

    /**
     * Get the single benchmark record; create with defaults if missing.
     */
    public static function current(): self
    {
        return self::first() ?? self::create([
            'min_groups_per_facilitator'      => 3,
            'min_trainings_per_week'          => 2,
            'min_meetings_per_group_per_week' => 1,
            'min_members_per_group'           => 30,
            'min_aesa_sessions_per_week'      => 1,
            'min_meeting_attendance_pct'      => 75.00,
        ]);
    }
}
