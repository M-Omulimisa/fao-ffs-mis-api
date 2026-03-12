<?php

namespace App\Admin\Controllers;

use App\Models\FfsGroup;
use App\Models\ImplementingPartner;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\SocialFundTransaction;
use App\Models\User;
use App\Models\VslaLoan;
use App\Models\VslaMeeting;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Operations Dashboard — Super Admin
 *
 * Comprehensive monitoring of:
 *  1. System-wide KPI strip
 *  2. Facilitator Leaderboard (groups created per facilitator per IP)
 *  3. Daily Group Registration Chart (trend over time)
 *  4. IP Comparison Table (groups, members, financials vs targets)
 *  5. Financial Health (savings, loans, social fund)
 *  6. Loan Portfolio Analysis (PAR, status breakdown)
 *  7. Recent Activity Feed
 *
 * Accessible at: GET /admin/operations-dashboard
 * All sections respect IP-scoping: super admins see all; IP admins see only their IP.
 */
class OperationsDashboardController extends AdminController
{
    protected $title = 'Operations Dashboard';

    // ─── Colour palette ──────────────────────────────────────────────────────
    const PRIMARY   = '#05179F';
    const SUCCESS   = '#2e7d32';
    const WARNING   = '#e65100';
    const DANGER    = '#c62828';
    const INFO      = '#01579b';
    const NEUTRAL   = '#546e7a';

    // ─── Entry point ─────────────────────────────────────────────────────────

    public function index(Content $content)
    {
        Admin::js('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js');
        Admin::style($this->css());

        $user         = Admin::user();
        $isSuperAdmin = $user && ($user->isRole('super_admin') || $user->isRole('administrator'));
        $myIpId       = $isSuperAdmin ? null : ($user->ip_id ?? null);

        // ── Filters (GET params) ──────────────────────────────────────────────
        $filterIpId  = $isSuperAdmin ? request('ip_id')  : $myIpId;
        $period      = request('period', '30');   // days
        $dateFrom    = request('date_from') ? Carbon::parse(request('date_from'))->startOfDay()
                                            : Carbon::now()->subDays((int)$period)->startOfDay();
        $dateTo      = request('date_to')   ? Carbon::parse(request('date_to'))->endOfDay()
                                            : Carbon::now()->endOfDay();

        $params = compact('isSuperAdmin', 'myIpId', 'filterIpId', 'dateFrom', 'dateTo', 'period');

        // ── Pre-compute all metrics once ──────────────────────────────────────
        $metrics = $this->computeMetrics($params);

        return $content
            ->title('Operations Dashboard')
            ->description('Live system-wide monitoring — groups, facilitators, finances, and loan portfolio')

            // Row 0: Filter bar
            ->row(function (Row $row) use ($params, $isSuperAdmin) {
                $row->column(12, fn(Column $col) =>
                    $col->append($this->filterBar($params, $isSuperAdmin)));
            })

            // Row 1: KPI Strip
            ->row(function (Row $row) use ($metrics) {
                $row->column(12, fn(Column $col) =>
                    $col->append($this->kpiStrip($metrics)));
            })

            // Row 2: Facilitator Leaderboard (7) + Daily Chart (5)
            ->row(function (Row $row) use ($metrics, $params) {
                $row->column(7, fn(Column $col) =>
                    $col->append($this->facilitatorLeaderboard($metrics, $params)));
                $row->column(5, fn(Column $col) =>
                    $col->append($this->dailyGroupChart($metrics)));
            })

            // Row 3: IP Comparison Table (full width)
            ->row(function (Row $row) use ($metrics, $params) {
                $row->column(12, fn(Column $col) =>
                    $col->append($this->ipComparisonTable($metrics, $params)));
            })

            // Row 4: Financial Health (4+4+4)
            ->row(function (Row $row) use ($metrics) {
                $row->column(4, fn(Column $col) =>
                    $col->append($this->savingsSummaryCard($metrics)));
                $row->column(4, fn(Column $col) =>
                    $col->append($this->loanSummaryCard($metrics)));
                $row->column(4, fn(Column $col) =>
                    $col->append($this->socialFundCard($metrics)));
            })

            // Row 5: Loan Portfolio Chart (5) + Loan Status Table (7)
            ->row(function (Row $row) use ($metrics) {
                $row->column(5, fn(Column $col) =>
                    $col->append($this->loanDoughnutChart($metrics)));
                $row->column(7, fn(Column $col) =>
                    $col->append($this->loanStatusTable($metrics)));
            })

            // Row 6: Recent Group Registrations
            ->row(function (Row $row) use ($metrics) {
                $row->column(12, fn(Column $col) =>
                    $col->append($this->recentGroupsTable($metrics)));
            });
    }

    // ─── Metrics computation ─────────────────────────────────────────────────

    private function computeMetrics(array $p): array
    {
        $ipId     = $p['filterIpId'];
        $dateFrom = $p['dateFrom'];
        $dateTo   = $p['dateTo'];

        // ── Counts ────────────────────────────────────────────────────────────
        $totalIps          = ImplementingPartner::active()->count();
        $totalGroups       = FfsGroup::when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
        $groupsInPeriod    = FfsGroup::when($ipId, fn($q) => $q->where('ip_id', $ipId))
                                ->whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $totalMembers      = User::whereNotNull('group_id')
                                ->when($ipId, fn($q) => $q->whereHas('group', fn($g) => $g->where('ip_id', $ipId)))
                                ->count();
        $totalFacilitators = FfsGroup::when($ipId, fn($q) => $q->where('ip_id', $ipId))
                                ->whereNotNull('facilitator_id')
                                ->distinct('facilitator_id')
                                ->count('facilitator_id');
        $activeCycles      = Project::where('is_vsla_cycle', 'Yes')
                                ->where('is_active_cycle', 'Yes')
                                ->when($ipId, fn($q) => $q->whereHas('group', fn($g) => $g->where('ip_id', $ipId)))
                                ->count();
        $totalMeetings     = VslaMeeting::when($ipId, fn($q) => $q->whereHas('group', fn($g) => $g->where('ip_id', $ipId)))
                                ->whereBetween('created_at', [$dateFrom, $dateTo])->count();

        // ── Financial ─────────────────────────────────────────────────────────
        $totalSavings = ProjectShare::when($ipId, fn($q) => $q->whereHas('project', fn($pq) =>
                            $pq->whereHas('group', fn($g) => $g->where('ip_id', $ipId))))
                            ->sum('total_amount_paid');

        $totalLoanDisbursed = VslaLoan::when($ipId, fn($q) => $q->whereHas('cycle', fn($c) =>
                                $c->whereHas('group', fn($g) => $g->where('ip_id', $ipId))))
                                ->sum('loan_amount');

        $totalLoanOutstanding = VslaLoan::where('status', 'active')
                                ->when($ipId, fn($q) => $q->whereHas('cycle', fn($c) =>
                                    $c->whereHas('group', fn($g) => $g->where('ip_id', $ipId))))
                                ->sum('balance');

        $overdueBalance = VslaLoan::where('status', 'active')
                                ->where('due_date', '<', now())
                                ->when($ipId, fn($q) => $q->whereHas('cycle', fn($c) =>
                                    $c->whereHas('group', fn($g) => $g->where('ip_id', $ipId))))
                                ->sum('balance');

        $parRate = $totalLoanOutstanding > 0
            ? round(($overdueBalance / $totalLoanOutstanding) * 100, 1)
            : 0.0;

        $totalSocialFund = SocialFundTransaction::where('transaction_type', 'contribution')
                            ->when($ipId, fn($q) => $q->whereIn('group_id', function($sub) use ($ipId) {
                                $sub->select('id')->from('ffs_groups')->where('ip_id', $ipId);
                            }))->sum('amount');

        // ── Loan breakdown ────────────────────────────────────────────────────
        $loanBase = VslaLoan::when($ipId, fn($q) => $q->whereHas('cycle', fn($c) =>
            $c->whereHas('group', fn($g) => $g->where('ip_id', $ipId))));

        $loanStatusCounts = (clone $loanBase)->select('status', DB::raw('COUNT(*) as cnt, SUM(loan_amount) as total'))
            ->groupBy('status')->pluck('cnt', 'status')->toArray();
        $loanStatusAmounts = (clone $loanBase)->select('status', DB::raw('SUM(loan_amount) as total'))
            ->groupBy('status')->pluck('total', 'status')->toArray();

        // ── Daily group creation (last N days) ────────────────────────────────
        $dailyGroups = FfsGroup::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('cnt', 'date')
            ->toArray();

        // Fill missing dates with 0
        $dailyLabels = [];
        $dailyCounts = [];
        $cursor = $dateFrom->copy()->startOfDay();
        while ($cursor->lte($dateTo)) {
            $key = $cursor->format('Y-m-d');
            $dailyLabels[] = $cursor->format('M d');
            $dailyCounts[] = $dailyGroups[$key] ?? 0;
            $cursor->addDay();
        }

        // ── Facilitator leaderboard ───────────────────────────────────────────
        $weekStart  = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        $lbQuery = DB::table('ffs_groups as g')
            ->join('users as fac', 'g.facilitator_id', '=', 'fac.id')
            ->join('implementing_partners as ipt', 'g.ip_id', '=', 'ipt.id')
            ->whereNull('g.deleted_at')
            ->whereNotNull('g.facilitator_id');
        if ($ipId) {
            $lbQuery->where('g.ip_id', $ipId);
        }
        $leaderboard = $lbQuery
            ->selectRaw("
                g.facilitator_id,
                fac.name as facilitator_name,
                ipt.short_name as ip_name,
                COUNT(g.id) as total_groups,
                SUM(CASE WHEN g.created_at >= ? THEN 1 ELSE 0 END) as groups_this_week,
                SUM(CASE WHEN g.created_at >= ? THEN 1 ELSE 0 END) as groups_this_month,
                SUM(g.total_members) as total_members,
                MAX(g.created_at) as last_activity
            ", [$weekStart, $monthStart])
            ->groupBy('g.facilitator_id', 'fac.name', 'ipt.short_name')
            ->orderByDesc('total_groups')
            ->limit(25)
            ->get()
            ->toArray();

        // ── IP comparison ─────────────────────────────────────────────────────
        $ips = ImplementingPartner::active()
            ->when($ipId, fn($q) => $q->where('id', $ipId))
            ->orderBy('name')
            ->get();

        $ipStats = $ips->map(function ($ip) use ($weekStart, $monthStart) {
            $groups       = FfsGroup::where('ip_id', $ip->id)->count();
            $groupsWeek   = FfsGroup::where('ip_id', $ip->id)->where('created_at', '>=', $weekStart)->count();
            $groupsMonth  = FfsGroup::where('ip_id', $ip->id)->where('created_at', '>=', $monthStart)->count();
            $members      = User::whereNotNull('group_id')
                                ->whereHas('group', fn($g) => $g->where('ip_id', $ip->id))->count();
            $facilitators = FfsGroup::where('ip_id', $ip->id)->whereNotNull('facilitator_id')
                                ->distinct('facilitator_id')->count('facilitator_id');
            $cycles       = Project::where('is_vsla_cycle', 'Yes')->where('is_active_cycle', 'Yes')
                                ->whereHas('group', fn($g) => $g->where('ip_id', $ip->id))->count();
            $savings      = ProjectShare::whereHas('project', fn($pq) =>
                                $pq->whereHas('group', fn($g) => $g->where('ip_id', $ip->id)))->sum('total_amount_paid');
            $loans        = VslaLoan::whereHas('cycle', fn($c) =>
                                $c->whereHas('group', fn($g) => $g->where('ip_id', $ip->id)))->sum('loan_amount');
            $outstanding  = VslaLoan::where('status', 'active')
                                ->whereHas('cycle', fn($c) =>
                                    $c->whereHas('group', fn($g) => $g->where('ip_id', $ip->id)))->sum('balance');

            $targetGroups = $ip->kpi_target_groups ?? 0;
            $progress     = $targetGroups > 0 ? min(100, round(($groups / $targetGroups) * 100)) : 0;

            return [
                'id'            => $ip->id,
                'name'          => $ip->name,
                'short_name'    => $ip->short_name,
                'groups'        => $groups,
                'groups_week'   => $groupsWeek,
                'groups_month'  => $groupsMonth,
                'members'       => $members,
                'facilitators'  => $facilitators,
                'cycles'        => $cycles,
                'savings'       => (float) $savings,
                'loans'         => (float) $loans,
                'outstanding'   => (float) $outstanding,
                'target_groups' => $targetGroups,
                'progress'      => $progress,
            ];
        })->toArray();

        // ── Recent groups ─────────────────────────────────────────────────────
        $recentGroups = FfsGroup::with(['implementingPartner', 'facilitator'])
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->whereNotNull('facilitator_id')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($g) => [
                'name'        => $g->name,
                'type'        => $g->type,
                'district'    => $g->district_id,
                'ip'          => $g->implementingPartner?->short_name ?? '—',
                'facilitator' => $g->facilitator?->name ?? '—',
                'members'     => $g->total_members ?? 0,
                'date'        => $g->created_at?->format('d M Y'),
            ])->toArray();

        return compact(
            'totalIps', 'totalGroups', 'groupsInPeriod', 'totalMembers',
            'totalFacilitators', 'activeCycles', 'totalMeetings',
            'totalSavings', 'totalLoanDisbursed', 'totalLoanOutstanding',
            'overdueBalance', 'parRate', 'totalSocialFund',
            'loanStatusCounts', 'loanStatusAmounts',
            'dailyLabels', 'dailyCounts',
            'leaderboard', 'ipStats', 'recentGroups'
        );
    }

    // ─── Section renderers ───────────────────────────────────────────────────

    private function filterBar(array $p, bool $isSuperAdmin): string
    {
        $ipOptions = '<option value="">All IPs</option>';
        if ($isSuperAdmin) {
            foreach (ImplementingPartner::active()->orderBy('name')->get() as $ip) {
                $sel = ($p['filterIpId'] == $ip->id) ? ' selected' : '';
                $ipOptions .= "<option value='{$ip->id}'{$sel}>" . e($ip->name) . "</option>";
            }
        }

        $periodOptions = '';
        foreach ([7 => 'Last 7 days', 14 => 'Last 14 days', 30 => 'Last 30 days', 60 => 'Last 60 days', 90 => 'Last 90 days'] as $val => $label) {
            $sel = ($p['period'] == $val) ? ' selected' : '';
            $periodOptions .= "<option value='{$val}'{$sel}>{$label}</option>";
        }

        $ipField = $isSuperAdmin
            ? "<label style='margin-bottom:4px;font-size:11px;font-weight:600;color:#666;display:block;'>IMPLEMENTING PARTNER</label>
               <select name='ip_id' class='form-control input-sm' style='min-width:200px;border-radius:0;'>$ipOptions</select>"
            : '';

        $dateFrom = $p['dateFrom']->format('Y-m-d');
        $dateTo   = $p['dateTo']->format('Y-m-d');

        return "
        <form method='GET' id='ops-filter-form' style='background:#fff;border:1px solid #ddd;padding:12px 16px;margin-bottom:12px;'>
            <div style='display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;'>
                {$ipField}
                <div>
                    <label style='margin-bottom:4px;font-size:11px;font-weight:600;color:#666;display:block;'>QUICK PERIOD</label>
                    <select name='period' class='form-control input-sm' style='min-width:140px;border-radius:0;' onchange='this.form.submit()'>
                        {$periodOptions}
                    </select>
                </div>
                <div>
                    <label style='margin-bottom:4px;font-size:11px;font-weight:600;color:#666;display:block;'>DATE FROM</label>
                    <input type='date' name='date_from' class='form-control input-sm' style='border-radius:0;' value='{$dateFrom}'>
                </div>
                <div>
                    <label style='margin-bottom:4px;font-size:11px;font-weight:600;color:#666;display:block;'>DATE TO</label>
                    <input type='date' name='date_to' class='form-control input-sm' style='border-radius:0;' value='{$dateTo}'>
                </div>
                <div>
                    <button type='submit' class='btn btn-sm btn-primary' style='border-radius:0;background:" . self::PRIMARY . ";border-color:" . self::PRIMARY . ";'>
                        <i class='fa fa-filter'></i> Apply Filter
                    </button>
                    <a href='?period=30' class='btn btn-sm btn-default' style='border-radius:0;margin-left:4px;'>
                        <i class='fa fa-refresh'></i> Reset
                    </a>
                </div>
            </div>
        </form>";
    }

    private function kpiStrip(array $m): string
    {
        $cards = [
            ['Active IPs',          number_format($m['totalIps']),           'fa-building',     self::PRIMARY,  ''],
            ['Total Groups',        number_format($m['totalGroups']),         'fa-users',        self::INFO,     ''],
            ['Groups (Period)',     number_format($m['groupsInPeriod']),      'fa-plus-circle',  self::SUCCESS,  'New in selected period'],
            ['Total Members',       number_format($m['totalMembers']),        'fa-user',         '#7b1fa2',      ''],
            ['Facilitators',        number_format($m['totalFacilitators']),   'fa-user-circle',  '#00838f',      ''],
            ['Active Cycles',       number_format($m['activeCycles']),        'fa-refresh',      '#5d4037',      'VSLA cycles currently active'],
            ['Meetings (Period)',    number_format($m['totalMeetings']),       'fa-calendar',     self::NEUTRAL,  ''],
            ['Total Savings',       'UGX ' . $this->shortNum($m['totalSavings']), 'fa-money', self::SUCCESS, ''],
        ];

        $html = "<div style='display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;'>";
        foreach ($cards as [$label, $value, $icon, $color, $hint]) {
            $hintHtml = $hint ? "<div style='font-size:10px;color:#999;margin-top:2px;'>{$hint}</div>" : '';
            $html .= "
            <div style='flex:1;min-width:120px;background:#fff;border:1px solid #ddd;border-top:3px solid {$color};padding:12px 10px;'>
                <div style='display:flex;align-items:center;gap:8px;'>
                    <div style='width:32px;height:32px;background:{$color};display:flex;align-items:center;justify-content:center;flex-shrink:0;'>
                        <i class='fa {$icon}' style='color:#fff;font-size:14px;'></i>
                    </div>
                    <div>
                        <div style='font-size:18px;font-weight:700;color:{$color};line-height:1.1;'>{$value}</div>
                        <div style='font-size:10px;font-weight:600;color:#666;text-transform:uppercase;'>{$label}</div>
                        {$hintHtml}
                    </div>
                </div>
            </div>";
        }
        $html .= "</div>";
        return $html;
    }

    private function facilitatorLeaderboard(array $m, array $p): string
    {
        $dateLabel = $p['dateFrom']->format('d M') . ' – ' . $p['dateTo']->format('d M Y');
        $html  = $this->sectionHeader('fa-trophy', 'Facilitator Leaderboard', "Groups created per facilitator &nbsp;&middot;&nbsp; Period: {$dateLabel}");
        $html .= "<div style='overflow-x:auto;'>";
        $html .= "<table class='table table-bordered table-condensed table-hover' style='margin:0;font-size:12px;'>
            <thead><tr style='background:" . self::PRIMARY . ";color:#fff;'>
                <th style='width:40px;text-align:center;padding:8px 4px;'>Rank</th>
                <th style='padding:8px;'>Facilitator</th>
                <th style='text-align:center;padding:8px;'>IP</th>
                <th style='text-align:center;padding:8px;'>Total<br>Groups</th>
                <th style='text-align:center;padding:8px;'>This<br>Week</th>
                <th style='text-align:center;padding:8px;'>This<br>Month</th>
                <th style='text-align:center;padding:8px;'>Members</th>
                <th style='text-align:center;padding:8px;'>Last Activity</th>
            </tr></thead><tbody>";

        if (empty($m['leaderboard'])) {
            $html .= "<tr><td colspan='8' style='text-align:center;padding:20px;color:#999;'>No data available</td></tr>";
        }

        foreach ($m['leaderboard'] as $rank => $row) {
            $rankNum = $rank + 1;
            if ($rank === 0) {
                $rankBadge = "<div style='width:28px;height:28px;border-radius:50%;background:#F9A825;display:inline-flex;align-items:center;justify-content:center;margin:0 auto;'><i class='fa fa-trophy' style='color:#fff;font-size:12px;'></i></div>";
            } elseif ($rank === 1) {
                $rankBadge = "<div style='width:28px;height:28px;border-radius:50%;background:#9E9E9E;display:inline-flex;align-items:center;justify-content:center;margin:0 auto;'><i class='fa fa-trophy' style='color:#fff;font-size:12px;'></i></div>";
            } elseif ($rank === 2) {
                $rankBadge = "<div style='width:28px;height:28px;border-radius:50%;background:#A1662A;display:inline-flex;align-items:center;justify-content:center;margin:0 auto;'><i class='fa fa-trophy' style='color:#fff;font-size:12px;'></i></div>";
            } else {
                $rankBadge = "<div style='width:28px;height:28px;border-radius:50%;background:#e0e0e0;display:inline-flex;align-items:center;justify-content:center;margin:0 auto;font-size:11px;font-weight:700;color:#555;'>{$rankNum}</div>";
            }

            $weekBg  = $row['groups_this_week']  > 0 ? 'background:#e8f5e9;font-weight:600;' : 'color:#999;';
            $monBg   = $row['groups_this_month'] > 0 ? 'background:#e8f5e9;font-weight:600;' : 'color:#999;';
            $lastAct = $row['last_activity'] ? Carbon::parse($row['last_activity'])->diffForHumans() : '&mdash;';
            $rowBg   = $rank < 3 ? "background:#fffde7;" : "";

            $html .= "<tr style='{$rowBg}'>
                <td style='text-align:center;padding:6px 4px;'>{$rankBadge}</td>
                <td style='padding:6px 8px;'><strong>" . e($row['facilitator_name']) . "</strong></td>
                <td style='text-align:center;padding:6px;'>
                    <span style='background:#e3f2fd;color:#01579b;padding:2px 8px;font-size:11px;display:inline-block;'>" . e($row['ip_name']) . "</span>
                </td>
                <td style='text-align:center;font-weight:700;font-size:15px;color:" . self::PRIMARY . ";padding:6px;'>{$row['total_groups']}</td>
                <td style='text-align:center;padding:6px;{$weekBg}'>{$row['groups_this_week']}</td>
                <td style='text-align:center;padding:6px;{$monBg}'>{$row['groups_this_month']}</td>
                <td style='text-align:center;padding:6px;'>" . number_format($row['total_members']) . "</td>
                <td style='text-align:center;font-size:11px;color:#666;padding:6px;'>{$lastAct}</td>
            </tr>";
        }

        $html .= "</tbody></table></div></div></div>";
        return $html;
    }

    private function dailyGroupChart(array $m): string
    {
        $labels = json_encode($m['dailyLabels']);
        $counts = json_encode($m['dailyCounts']);
        $total  = array_sum($m['dailyCounts']);
        $max    = !empty($m['dailyCounts']) ? max($m['dailyCounts']) : 0;
        $avg    = count($m['dailyCounts']) > 0 ? round($total / count($m['dailyCounts']), 1) : 0;

        $html  = $this->sectionHeader('fa-line-chart', 'Daily Group Registrations', "{$total} total &middot; Peak: {$max}/day &middot; Avg: {$avg}/day");
        $html .= "<canvas id='dailyGroupsChart' height='260'></canvas>
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            var ctx = document.getElementById('dailyGroupsChart');
            if(!ctx) return;
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: {$labels},
                    datasets: [{
                        label: 'Groups Created',
                        data: {$counts},
                        borderColor: '" . self::PRIMARY . "',
                        backgroundColor: 'rgba(5,23,159,0.08)',
                        borderWidth: 2,
                        pointBackgroundColor: '" . self::PRIMARY . "',
                        pointRadius: 3,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { ticks: { maxRotation: 45, font: { size: 9 } } }
                    }
                }
            });
        });
        </script></div></div>";
        return $html;
    }

    private function ipComparisonTable(array $m, array $p): string
    {
        $html  = $this->sectionHeader('fa-table', 'Implementing Partner Comparison', 'Groups, members, financial health and progress against KPI targets');
        $html .= "<div style='overflow-x:auto;'>";
        $html .= "<table class='table table-bordered table-condensed table-hover' style='margin:0;font-size:12px;'>
            <thead>
                <tr style='background:" . self::PRIMARY . ";color:#fff;'>
                    <th rowspan='2'>Implementing Partner</th>
                    <th colspan='3' style='text-align:center;border-bottom:1px solid rgba(255,255,255,0.3);'>Groups</th>
                    <th rowspan='2' style='text-align:center;'>Members</th>
                    <th rowspan='2' style='text-align:center;'>Facilitators</th>
                    <th rowspan='2' style='text-align:center;'>Active Cycles</th>
                    <th colspan='3' style='text-align:center;border-bottom:1px solid rgba(255,255,255,0.3);'>Financials (UGX)</th>
                    <th rowspan='2' style='text-align:center;min-width:120px;'>Progress vs Target</th>
                </tr>
                <tr style='background:" . self::PRIMARY . ";color:#fff;'>
                    <th style='text-align:center;font-weight:400;font-size:10px;'>All Time</th>
                    <th style='text-align:center;font-weight:400;font-size:10px;'>This Week</th>
                    <th style='text-align:center;font-weight:400;font-size:10px;'>This Month</th>
                    <th style='text-align:center;font-weight:400;font-size:10px;'>Savings</th>
                    <th style='text-align:center;font-weight:400;font-size:10px;'>Loans Out</th>
                    <th style='text-align:center;font-weight:400;font-size:10px;'>Outstanding</th>
                </tr>
            </thead><tbody>";

        foreach ($m['ipStats'] as $ip) {
            $progressColor = $ip['progress'] >= 80 ? self::SUCCESS : ($ip['progress'] >= 50 ? self::WARNING : self::DANGER);
            $progressBar   = "
            <div style='display:flex;align-items:center;gap:6px;'>
                <div style='flex:1;height:10px;background:#eee;'>
                    <div style='height:10px;width:{$ip['progress']}%;background:{$progressColor};'></div>
                </div>
                <span style='font-size:11px;font-weight:700;color:{$progressColor};'>{$ip['progress']}%</span>
            </div>
            <div style='font-size:10px;color:#999;margin-top:2px;'>{$ip['groups']} / {$ip['target_groups']} target</div>";

            $html .= "<tr>
                <td>
                    <strong>" . e($ip['name']) . "</strong>
                    " . ($ip['short_name'] ? "<br><small style='color:#999;'>" . e($ip['short_name']) . "</small>" : '') . "
                </td>
                <td style='text-align:center;font-weight:700;'>{$ip['groups']}</td>
                <td style='text-align:center;" . ($ip['groups_week'] > 0 ? 'background:#e8f5e9;' : '') . "'>{$ip['groups_week']}</td>
                <td style='text-align:center;" . ($ip['groups_month'] > 0 ? 'background:#e8f5e9;' : '') . "'>{$ip['groups_month']}</td>
                <td style='text-align:center;'>" . number_format($ip['members']) . "</td>
                <td style='text-align:center;'>{$ip['facilitators']}</td>
                <td style='text-align:center;'>{$ip['cycles']}</td>
                <td style='text-align:center;font-size:11px;'>" . $this->shortNum($ip['savings']) . "</td>
                <td style='text-align:center;font-size:11px;'>" . $this->shortNum($ip['loans']) . "</td>
                <td style='text-align:center;font-size:11px;color:" . self::WARNING . ";'>" . $this->shortNum($ip['outstanding']) . "</td>
                <td style='min-width:130px;padding:8px;'>{$progressBar}</td>
            </tr>";
        }

        if (empty($m['ipStats'])) {
            $html .= "<tr><td colspan='12' style='text-align:center;padding:20px;color:#999;'>No data available</td></tr>";
        }

        $html .= "</tbody></table></div></div></div>";
        return $html;
    }

    private function savingsSummaryCard(array $m): string
    {
        $totalMembers = max(1, $m['totalMembers']);
        $totalGroups  = max(1, $m['totalGroups']);
        $avgPerMember = round($m['totalSavings'] / $totalMembers);
        $avgPerGroup  = round($m['totalSavings'] / $totalGroups);

        $html  = $this->sectionHeader('fa-piggy-bank', 'Savings Overview', 'Total share purchases across all VSLA cycles');
        $html .= $this->financialRow('Total Savings', $m['totalSavings'], self::SUCCESS, true);
        $html .= $this->financialRow('Avg per Member', $avgPerMember, self::SUCCESS);
        $html .= $this->financialRow('Avg per Group',  $avgPerGroup, self::SUCCESS);
        $html .= "</div></div>";
        return $html;
    }

    private function loanSummaryCard(array $m): string
    {
        $parColor = $m['parRate'] > 10 ? self::DANGER : ($m['parRate'] > 5 ? self::WARNING : self::SUCCESS);
        $html  = $this->sectionHeader('fa-money', 'Loan Portfolio', 'Disbursements, outstanding balances and Portfolio at Risk');
        $html .= $this->financialRow('Total Disbursed', $m['totalLoanDisbursed'], self::INFO, true);
        $html .= $this->financialRow('Total Outstanding', $m['totalLoanOutstanding'], self::WARNING);
        $html .= $this->financialRow('Overdue (PAR)', $m['overdueBalance'], self::DANGER);
        $html .= "
        <div style='display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-top:1px solid #f0f0f0;margin-top:4px;'>
            <span style='font-size:12px;color:#666;font-weight:600;'>PAR Rate</span>
            <span style='font-size:20px;font-weight:700;color:{$parColor};'>{$m['parRate']}%</span>
        </div>
        <div style='height:8px;background:#eee;margin-top:4px;'>
            <div style='height:8px;width:" . min(100, $m['parRate']) . "%;background:{$parColor};'></div>
        </div>
        <div style='font-size:10px;color:#999;margin-top:4px;'>PAR = Overdue balance / Total outstanding balance. Target: &lt;5%</div>
        </div></div>";
        return $html;
    }

    private function socialFundCard(array $m): string
    {
        $totalGroups  = max(1, $m['totalGroups']);
        $totalMembers = max(1, $m['totalMembers']);
        $html  = $this->sectionHeader('fa-heart', 'Social Fund', 'Welfare contributions across all groups');
        $html .= $this->financialRow('Total Contributed', $m['totalSocialFund'], '#ad1457', true);
        $html .= $this->financialRow('Avg per Group',  round($m['totalSocialFund'] / $totalGroups), '#ad1457');
        $html .= $this->financialRow('Avg per Member', round($m['totalSocialFund'] / $totalMembers), '#ad1457');

        $activeCycles = max(1, $m['activeCycles']);
        $avgPerCycle  = round($m['totalSocialFund'] / $activeCycles);
        $html .= $this->financialRow('Avg per Cycle', $avgPerCycle, '#ad1457');
        $html .= "</div></div>";
        return $html;
    }

    private function loanDoughnutChart(array $m): string
    {
        $statusMap = [
            'active'    => ['Active',    self::INFO,    $m['loanStatusCounts']['active']    ?? 0],
            'paid'      => ['Paid',      self::SUCCESS, $m['loanStatusCounts']['paid']      ?? 0],
            'defaulted' => ['Defaulted', self::DANGER,  $m['loanStatusCounts']['defaulted'] ?? 0],
        ];
        $labels = json_encode(array_column(array_values($statusMap), 0));
        $counts = json_encode(array_column(array_values($statusMap), 2));
        $colors = json_encode(array_column(array_values($statusMap), 1));
        $total  = array_sum($m['loanStatusCounts'] ?? []);

        $html  = $this->sectionHeader('fa-pie-chart', 'Loan Status Breakdown', "{$total} total loans");
        $html .= "<canvas id='loanDoughnutChart' height='220'></canvas>
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            var ctx = document.getElementById('loanDoughnutChart');
            if(!ctx) return;
            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: {$labels},
                    datasets: [{ data: {$counts}, backgroundColor: {$colors}, borderWidth: 2, borderColor: '#fff' }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } }
                    }
                }
            });
        });
        </script></div></div>";
        return $html;
    }

    private function loanStatusTable(array $m): string
    {
        $rows = [
            ['Active',    self::INFO,    $m['loanStatusCounts']['active']    ?? 0, $m['loanStatusAmounts']['active']    ?? 0],
            ['Paid',      self::SUCCESS, $m['loanStatusCounts']['paid']      ?? 0, $m['loanStatusAmounts']['paid']      ?? 0],
            ['Defaulted', self::DANGER,  $m['loanStatusCounts']['defaulted'] ?? 0, $m['loanStatusAmounts']['defaulted'] ?? 0],
        ];
        $totalCount  = array_sum(array_column($rows, 2));
        $totalAmount = array_sum(array_column($rows, 3));

        $html  = $this->sectionHeader('fa-list', 'Loan Details by Status', 'Counts, amounts and repayment health');
        $html .= "<table class='table table-bordered table-condensed' style='margin:0;font-size:12px;'>
            <thead><tr style='background:#f5f5f5;'>
                <th>Status</th>
                <th style='text-align:right;'>Count</th>
                <th style='text-align:right;'>Total Amount (UGX)</th>
                <th style='text-align:right;'>% of Total</th>
            </tr></thead><tbody>";

        foreach ($rows as [$status, $color, $count, $amount]) {
            $pct = $totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0;
            $html .= "<tr>
                <td><span style='background:{$color};color:#fff;padding:2px 8px;font-size:11px;'>{$status}</span></td>
                <td style='text-align:right;font-weight:700;'>" . number_format($count) . "</td>
                <td style='text-align:right;'>" . number_format($amount) . "</td>
                <td style='text-align:right;'>
                    <div style='display:flex;align-items:center;justify-content:flex-end;gap:6px;'>
                        <div style='width:60px;height:6px;background:#eee;'>
                            <div style='height:6px;width:{$pct}%;background:{$color};'></div>
                        </div>
                        <span style='font-size:11px;color:{$color};font-weight:600;'>{$pct}%</span>
                    </div>
                </td>
            </tr>";
        }

        // PAR alert row
        $parColor = $m['parRate'] > 10 ? self::DANGER : ($m['parRate'] > 5 ? self::WARNING : self::SUCCESS);
        $html .= "<tr style='background:#fff9c4;'>
            <td><i class='fa fa-exclamation-triangle' style='color:{$parColor};'></i> <strong>Portfolio at Risk (PAR)</strong></td>
            <td colspan='2' style='text-align:right;'>Overdue balance: <strong>UGX " . number_format($m['overdueBalance']) . "</strong></td>
            <td style='text-align:right;font-weight:700;font-size:16px;color:{$parColor};'>{$m['parRate']}%</td>
        </tr>";

        $html .= "<tr style='background:#f5f5f5;font-weight:700;'>
            <td>TOTAL</td>
            <td style='text-align:right;'>" . number_format($totalCount) . "</td>
            <td style='text-align:right;'>UGX " . number_format($totalAmount) . "</td>
            <td></td>
        </tr>";

        $html .= "</tbody></table></div></div>";
        return $html;
    }

    private function recentGroupsTable(array $m): string
    {
        $html  = $this->sectionHeader('fa-clock-o', 'Recently Registered Groups', 'Last 20 groups added to the system');
        $html .= "<div style='overflow-x:auto;'>";
        $html .= "<table class='table table-bordered table-condensed table-hover' style='margin:0;font-size:12px;'>
            <thead><tr style='background:#f5f5f5;'>
                <th>Group Name</th>
                <th>Type</th>
                <th>IP</th>
                <th>Facilitator</th>
                <th style='text-align:center;'>Members</th>
                <th>Registered</th>
            </tr></thead><tbody>";

        if (empty($m['recentGroups'])) {
            $html .= "<tr><td colspan='6' style='text-align:center;padding:20px;color:#999;'>No recent groups found</td></tr>";
        }

        foreach ($m['recentGroups'] as $g) {
            $typeColor = match(strtoupper($g['type'] ?? '')) {
                'VSLA'        => '#01579b',
                'FFS', 'FFS GROUP' => self::SUCCESS,
                'FBS'         => '#4a148c',
                default       => self::NEUTRAL,
            };
            $html .= "<tr>
                <td><strong>" . e($g['name']) . "</strong></td>
                <td><span style='background:{$typeColor};color:#fff;padding:2px 6px;font-size:10px;'>" . e($g['type'] ?? '—') . "</span></td>
                <td><span style='font-size:11px;color:" . self::INFO . ";'>" . e($g['ip']) . "</span></td>
                <td>" . e($g['facilitator']) . "</td>
                <td style='text-align:center;'>{$g['members']}</td>
                <td style='font-size:11px;color:#666;'>{$g['date']}</td>
            </tr>";
        }

        $html .= "</tbody></table></div></div></div>";
        return $html;
    }

    // ─── Shared helpers ──────────────────────────────────────────────────────

    private function sectionHeader(string $icon, string $title, string $subtitle = ''): string
    {
        $sub = $subtitle
            ? "<div style='font-size:11px;color:rgba(255,255,255,0.75);margin-top:2px;'>{$subtitle}</div>"
            : '';
        return "
        <div style='background:#fff;border:1px solid #ddd;margin-bottom:12px;'>
            <div style='background:" . self::PRIMARY . ";padding:10px 14px;'>
                <div style='font-size:14px;font-weight:700;color:#fff;'>
                    <i class='fa {$icon}' style='margin-right:6px;'></i>{$title}
                </div>
                {$sub}
            </div>
            <div style='padding:14px;'>";
    }

    private function financialRow(string $label, float $amount, string $color, bool $large = false): string
    {
        $fontSize  = $large ? '20px' : '14px';
        $formatted = 'UGX ' . number_format($amount);
        return "
        <div style='display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f0f0f0;'>
            <span style='font-size:12px;color:#555;'>{$label}</span>
            <span style='font-size:{$fontSize};font-weight:700;color:{$color};'>{$formatted}</span>
        </div>";
    }

    /** Format large numbers as short strings: 1,250,000 → 1.25M */
    private function shortNum(float $n): string
    {
        if ($n >= 1_000_000_000) return number_format($n / 1_000_000_000, 1) . 'B';
        if ($n >= 1_000_000)     return number_format($n / 1_000_000, 1) . 'M';
        if ($n >= 1_000)         return number_format($n / 1_000, 0) . 'K';
        return number_format($n);
    }

    private function css(): string
    {
        return '
        .ops-dash table th { white-space: nowrap; }
        .ops-dash table td { vertical-align: middle !important; }
        .content-wrapper { background: #f4f6f9 !important; }
        ';
    }
}
