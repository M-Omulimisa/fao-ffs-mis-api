<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KpiService;
use App\Models\KpiBenchmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * KpiController — mobile API endpoints for KPI dashboards.
 *
 * Facilitators see their own scorecard.
 * IP managers see their IP scorecard (includes all facilitator breakdowns).
 * Super admins can query any facilitator or IP.
 */
class KpiController extends Controller
{
    /**
     * GET /api/kpi/facilitator-scorecard
     *
     * Returns the current user's facilitator scorecard,
     * or a specific facilitator if ?facilitator_id= is passed (admin only).
     */
    public function facilitatorScorecard(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['code' => 0, 'message' => 'Unauthenticated'], 401);
        }

        $facilitatorId = $request->input('facilitator_id', $user->id);

        // Non-admin users can only see their own
        if ((int) $facilitatorId !== (int) $user->id) {
            $adminUser = \Encore\Admin\Facades\Admin::user();
            if (!$adminUser || !$adminUser->isRole('super_admin')) {
                // Check if request user is IP manager for this facilitator
                $isIpManager = \App\Models\FfsGroup::where('facilitator_id', $facilitatorId)
                    ->where('ip_id', $user->ip_id)
                    ->exists();
                if (!$isIpManager) {
                    $facilitatorId = $user->id;
                }
            }
        }

        $weekStart = $request->has('week_start')
            ? \Carbon\Carbon::parse($request->input('week_start'))->startOfDay()
            : null;

        $scorecard = KpiService::facilitatorScorecard((int) $facilitatorId, $weekStart);

        return response()->json([
            'code'    => 1,
            'message' => 'Facilitator scorecard',
            'data'    => $scorecard,
        ]);
    }

    /**
     * GET /api/kpi/ip-scorecard
     *
     * Returns IP scorecard for the current user's IP,
     * or a specific IP if ?ip_id= is passed (super admin only).
     */
    public function ipScorecard(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['code' => 0, 'message' => 'Unauthenticated'], 401);
        }

        $ipId = $request->input('ip_id', $user->ip_id);

        if (!$ipId) {
            return response()->json([
                'code'    => 0,
                'message' => 'No Implementing Partner associated with your account',
            ], 400);
        }

        $weekStart = $request->has('week_start')
            ? \Carbon\Carbon::parse($request->input('week_start'))->startOfDay()
            : null;

        $scorecard = KpiService::ipScorecard((int) $ipId, $weekStart);

        return response()->json([
            'code'    => 1,
            'message' => 'IP scorecard',
            'data'    => $scorecard,
        ]);
    }

    /**
     * GET /api/kpi/facilitator-trend
     *
     * Returns the facilitator's weekly score trend (last 8 weeks).
     */
    public function facilitatorTrend(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['code' => 0, 'message' => 'Unauthenticated'], 401);
        }

        $facilitatorId = $request->input('facilitator_id', $user->id);
        $weeks = min(12, max(1, (int) $request->input('weeks', 8)));

        $trend = KpiService::facilitatorTrend((int) $facilitatorId, $weeks);

        return response()->json([
            'code'    => 1,
            'message' => 'Facilitator trend',
            'data'    => $trend,
        ]);
    }

    /**
     * GET /api/kpi/benchmarks
     *
     * Returns the current facilitator KPI benchmark targets.
     */
    public function benchmarks(): JsonResponse
    {
        $bench = KpiBenchmark::current();

        return response()->json([
            'code'    => 1,
            'message' => 'KPI benchmarks',
            'data'    => [
                'min_groups_per_facilitator'      => $bench->min_groups_per_facilitator,
                'min_trainings_per_week'          => $bench->min_trainings_per_week,
                'min_meetings_per_group_per_week' => $bench->min_meetings_per_group_per_week,
                'min_members_per_group'           => $bench->min_members_per_group,
                'min_aesa_sessions_per_week'      => $bench->min_aesa_sessions_per_week,
                'min_meeting_attendance_pct'      => $bench->min_meeting_attendance_pct,
            ],
        ]);
    }
}
