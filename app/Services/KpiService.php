<?php

namespace App\Services;

use App\Models\FfsGroup;
use App\Models\FfsTrainingSession;
use App\Models\ImplementingPartner;
use App\Models\KpiBenchmark;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * KpiService — computes KPI actuals vs. targets for facilitators and IPs.
 *
 * All "per-week" metrics use a configurable period; defaults to the current
 * ISO week (Mon → Sun).  Pass $weekStart to score a different week.
 */
class KpiService
{
    // =====================================================================
    // FACILITATOR KPI
    // =====================================================================

    /**
     * Compute KPI scorecard for a single facilitator.
     *
     * @param  int          $facilitatorId  users.id of the facilitator
     * @param  Carbon|null  $weekStart      Monday of the week to evaluate
     * @return array        ['benchmarks' => [...], 'actuals' => [...], 'scores' => [...], 'overall_score' => float]
     */
    public static function facilitatorScorecard(int $facilitatorId, ?Carbon $weekStart = null): array
    {
        $bench = KpiBenchmark::current();
        $weekStart = ($weekStart ?? Carbon::now()->startOfWeek(Carbon::MONDAY))->copy()->startOfDay();
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $effectiveWeekStart = $weekStart->copy();
        $activeDaysInWeek = 7;

        // ── facilitator start date ───────────────────────────────
        $facilitator = User::find($facilitatorId);
        $facilitatorStartDate = null;
        $weeksActive = null;
        if ($facilitator && $facilitator->facilitator_start_date) {
            $facilitatorStartDate = Carbon::parse($facilitator->facilitator_start_date)->toDateString();
            $start = Carbon::parse($facilitatorStartDate);
            $weeksActive = max(1, (int) $start->diffInWeeks(Carbon::now()));

            // Prorate weekly targets if facilitator started during the selected week.
            if ($start->greaterThan($weekStart)) {
                if ($start->greaterThan($weekEnd)) {
                    $activeDaysInWeek = 0;
                    $effectiveWeekStart = $weekEnd->copy()->addSecond();
                } else {
                    $effectiveWeekStart = $start->copy()->startOfDay();
                    $activeDaysInWeek = max(1, $effectiveWeekStart->diffInDays($weekEnd) + 1);
                }
            }
        }

        // ── actuals ──────────────────────────────────────────────
        $groupIds = FfsGroup::where('facilitator_id', $facilitatorId)
            ->where('status', 'Active')
            ->pluck('id');

        $totalGroups   = $groupIds->count();
        $totalMembers  = $groupIds->isNotEmpty()
            ? User::whereIn('group_id', $groupIds)->count()
            : 0;
        $avgMembersPerGroup = $totalGroups > 0
            ? round($totalMembers / $totalGroups, 1)
            : 0;

        $trainingsThisWeek = FfsTrainingSession::where('facilitator_id', $facilitatorId)
            ->whereBetween('session_date', [$effectiveWeekStart, $weekEnd])
            ->count();

        // Meetings: via group_id → vsla_meetings
        $meetingsThisWeek = $groupIds->isNotEmpty()
            ? DB::table('vsla_meetings')
                ->whereIn('group_id', $groupIds)
                ->whereBetween('meeting_date', [$effectiveWeekStart, $weekEnd])
                ->count()
            : 0;
        $meetingsPerGroup = $totalGroups > 0
            ? round($meetingsThisWeek / $totalGroups, 2)
            : 0;

        // AESA sessions
        $aesaThisWeek = DB::table('aesa_sessions')
            ->where('facilitator_id', $facilitatorId)
            ->whereBetween('observation_date', [$effectiveWeekStart, $weekEnd])
            ->count();

        // Attendance average for this week's meetings
        $attendancePct = 0.0;
        if ($meetingsThisWeek > 0 && $groupIds->isNotEmpty()) {
            $meetingStats = DB::table('vsla_meetings')
                ->whereIn('group_id', $groupIds)
                ->whereBetween('meeting_date', [$effectiveWeekStart, $weekEnd])
                ->selectRaw('SUM(members_present) as present, SUM(members_present + members_absent) as total')
                ->first();
            if ($meetingStats && $meetingStats->total > 0) {
                $attendancePct = round(($meetingStats->present / $meetingStats->total) * 100, 1);
            }
        }

        $weeklyTargetFactor = min(1, max(0, $activeDaysInWeek / 7));
        $targetTrainings = round($bench->min_trainings_per_week * $weeklyTargetFactor, 2);
        $targetMeetingsPerGroup = round($bench->min_meetings_per_group_per_week * $weeklyTargetFactor, 2);
        $targetAesa = round($bench->min_aesa_sessions_per_week * $weeklyTargetFactor, 2);

        // ── scoring ──────────────────────────────────────────────
        $scores = [
            'groups'     => self::pctScore($totalGroups, $bench->min_groups_per_facilitator),
            'trainings'  => self::pctScore($trainingsThisWeek, $targetTrainings),
            'meetings'   => self::pctScore($meetingsPerGroup, $targetMeetingsPerGroup),
            'members'    => self::pctScore($avgMembersPerGroup, $bench->min_members_per_group),
            'aesa'       => self::pctScore($aesaThisWeek, $targetAesa),
            'attendance' => $bench->min_meeting_attendance_pct > 0
                ? min(100, round(($attendancePct / $bench->min_meeting_attendance_pct) * 100, 1))
                : 100,
        ];
        $overallScore = count($scores) > 0
            ? round(array_sum($scores) / count($scores), 1)
            : 0;

        return [
            'facilitator_id'    => $facilitatorId,
            'facilitator_name'  => $facilitator ? ($facilitator->name ?: trim($facilitator->first_name . ' ' . $facilitator->last_name)) : null,
            'start_date'        => $facilitatorStartDate,
            'weeks_active'      => $weeksActive,
            'week_start'        => $weekStart->toDateString(),
            'week_end'          => $weekEnd->toDateString(),
            'active_days_in_week' => $activeDaysInWeek,
            'benchmarks' => [
                'min_groups'              => $bench->min_groups_per_facilitator,
                'min_trainings_per_week'  => $targetTrainings,
                'min_meetings_per_group_per_week' => $targetMeetingsPerGroup,
                'min_members_per_group'   => $bench->min_members_per_group,
                'min_aesa_per_week'       => $targetAesa,
                'min_attendance_pct'      => $bench->min_meeting_attendance_pct,
            ],
            'actuals' => [
                'total_groups'          => $totalGroups,
                'total_members'         => $totalMembers,
                'avg_members_per_group' => $avgMembersPerGroup,
                'trainings_this_week'   => $trainingsThisWeek,
                'meetings_this_week'    => $meetingsThisWeek,
                'meetings_per_group'    => $meetingsPerGroup,
                'aesa_this_week'        => $aesaThisWeek,
                'attendance_pct'        => $attendancePct,
            ],
            'scores' => $scores,
            'overall_score' => $overallScore,
        ];
    }

    // =====================================================================
    // IP KPI
    // =====================================================================

    /**
     * Compute KPI scorecard for an Implementing Partner.
     */
    public static function ipScorecard(int $ipId, ?Carbon $weekStart = null): array
    {
        $ip = ImplementingPartner::findOrFail($ipId);
        $weekStart = ($weekStart ?? Carbon::now()->startOfWeek(Carbon::MONDAY))->copy()->startOfDay();
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $effectiveWeekStart = $weekStart->copy();
        $activeDaysInWeek = 7;

        if ($ip->start_date) {
            $ipStart = Carbon::parse($ip->start_date)->startOfDay();
            if ($ipStart->greaterThan($weekStart)) {
                if ($ipStart->greaterThan($weekEnd)) {
                    $activeDaysInWeek = 0;
                    $effectiveWeekStart = $weekEnd->copy()->addSecond();
                } else {
                    $effectiveWeekStart = $ipStart;
                    $activeDaysInWeek = max(1, $effectiveWeekStart->diffInDays($weekEnd) + 1);
                }
            }
        }

        // ── IP-level actuals ─────────────────────────────────────
        $groupIds = FfsGroup::where('ip_id', $ipId)
            ->where('status', 'Active')
            ->pluck('id');

        $facilitatorIds = FfsGroup::where('ip_id', $ipId)
            ->where('status', 'Active')
            ->whereNotNull('facilitator_id')
            ->distinct()
            ->pluck('facilitator_id');

        $totalFacilitators = $facilitatorIds->count();
        $totalGroups       = $groupIds->count();
        $totalMembers      = $groupIds->isNotEmpty()
            ? User::whereIn('group_id', $groupIds)->count()
            : 0;

        $trainingsThisWeek = FfsTrainingSession::where('ip_id', $ipId)
            ->whereBetween('session_date', [$effectiveWeekStart, $weekEnd])
            ->count();

        $meetingsThisWeek = $groupIds->isNotEmpty()
            ? DB::table('vsla_meetings')
                ->whereIn('group_id', $groupIds)
                ->whereBetween('meeting_date', [$effectiveWeekStart, $weekEnd])
                ->count()
            : 0;

        // ── per-facilitator scorecards (for aggregate metrics) ───
        $facilitatorScorecards = [];
        $facilitatorsMetKpi = 0;
        foreach ($facilitatorIds as $fId) {
            $card = self::facilitatorScorecard($fId, $weekStart);
            $facilitatorScorecards[] = $card;
            if ($card['overall_score'] >= 80) {
                $facilitatorsMetKpi++;
            }
        }

        $avgFacilitatorScore = $totalFacilitators > 0
            ? round(collect($facilitatorScorecards)->avg('overall_score'), 1)
            : 0;
        $pctFacilitatorsMet  = $totalFacilitators > 0
            ? round(($facilitatorsMetKpi / $totalFacilitators) * 100, 1)
            : 0;

        // Groups meeting member KPI
        $bench = KpiBenchmark::current();
        $groupsMeetingMemberKpi = 0;
        if ($groupIds->isNotEmpty()) {
            $groupMemberCounts = User::whereIn('group_id', $groupIds)
                ->select('group_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('group_id')
                ->pluck('cnt', 'group_id');
            foreach ($groupIds as $gId) {
                if (($groupMemberCounts[$gId] ?? 0) >= $bench->min_members_per_group) {
                    $groupsMeetingMemberKpi++;
                }
            }
        }
        $pctGroupsMet = $totalGroups > 0
            ? round(($groupsMeetingMemberKpi / $totalGroups) * 100, 1)
            : 0;

        // ── IP targets & scores ──────────────────────────────────
        $targets = [
            'facilitators'      => $ip->kpi_target_facilitators ?? 5,
            'groups'            => $ip->kpi_target_groups ?? 15,
            'trainings_per_week' => $ip->kpi_target_trainings_per_week ?? 30,
            'meetings_per_week' => $ip->kpi_target_meetings_per_week ?? 15,
            'members'           => $ip->kpi_target_members ?? 450,
        ];

        $weeklyTargetFactor = min(1, max(0, $activeDaysInWeek / 7));
        $targets['trainings_per_week'] = round($targets['trainings_per_week'] * $weeklyTargetFactor, 2);
        $targets['meetings_per_week'] = round($targets['meetings_per_week'] * $weeklyTargetFactor, 2);

        $scores = [
            'facilitators' => self::pctScore($totalFacilitators, $targets['facilitators']),
            'groups'       => self::pctScore($totalGroups, $targets['groups']),
            'trainings'    => self::pctScore($trainingsThisWeek, $targets['trainings_per_week']),
            'meetings'     => self::pctScore($meetingsThisWeek, $targets['meetings_per_week']),
            'members'      => self::pctScore($totalMembers, $targets['members']),
        ];
        $overallScore = count($scores) > 0
            ? round(array_sum($scores) / count($scores), 1)
            : 0;

        return [
            'ip_id'      => $ipId,
            'ip_name'    => $ip->name,
            'start_date' => $ip->start_date ? Carbon::parse($ip->start_date)->toDateString() : null,
            'week_start' => $weekStart->toDateString(),
            'week_end'   => $weekEnd->toDateString(),
            'active_days_in_week' => $activeDaysInWeek,
            'targets'    => $targets,
            'actuals'    => [
                'total_facilitators'   => $totalFacilitators,
                'total_groups'         => $totalGroups,
                'total_members'        => $totalMembers,
                'trainings_this_week'  => $trainingsThisWeek,
                'meetings_this_week'   => $meetingsThisWeek,
            ],
            'scores'         => $scores,
            'overall_score'  => $overallScore,
            'facilitator_performance' => [
                'avg_score'          => $avgFacilitatorScore,
                'pct_meeting_kpi'    => $pctFacilitatorsMet,
                'pct_groups_meeting_member_kpi' => $pctGroupsMet,
                'scorecards'         => $facilitatorScorecards,
            ],
        ];
    }

    // =====================================================================
    // TREND DATA (last N weeks)
    // =====================================================================

    /**
     * Get facilitator scorecards for the last N weeks (for trend charts).
     */
    public static function facilitatorTrend(int $facilitatorId, int $weeks = 8): array
    {
        $trend = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $card = self::facilitatorScorecard($facilitatorId, $weekStart);
            $trend[] = [
                'week_start'    => $card['week_start'],
                'week_end'      => $card['week_end'],
                'overall_score' => $card['overall_score'],
                'scores'        => $card['scores'],
                'actuals'       => $card['actuals'],
            ];
        }
        return $trend;
    }

    /**
     * Get IP scorecards for the last N weeks (for trend charts).
     */
    public static function ipTrend(int $ipId, int $weeks = 8): array
    {
        $trend = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $card = self::ipScorecard($ipId, $weekStart);
            $trend[] = [
                'week_start'    => $card['week_start'],
                'week_end'      => $card['week_end'],
                'overall_score' => $card['overall_score'],
                'scores'        => $card['scores'],
                'actuals'       => $card['actuals'],
            ];
        }
        return $trend;
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    /**
     * Calculate percentage score (actual / target × 100), capped at 100.
     */
    private static function pctScore(float $actual, float $target): float
    {
        if ($target <= 0) return 100;
        return min(100, round(($actual / $target) * 100, 1));
    }
}
