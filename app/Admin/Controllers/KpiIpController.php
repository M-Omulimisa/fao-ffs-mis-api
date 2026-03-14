<?php

namespace App\Admin\Controllers;

use App\Models\ImplementingPartner;
use App\Services\KpiService;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

/**
 * KpiIpController — Implementing Partner KPI performance dashboard.
 *
 * Access tiers:
 *   Super Admin → all active IPs
 *   IP Manager  → only their own IP
 *   Facilitator → redirected (no IP-level view)
 */
class KpiIpController extends AdminController
{
    use IpScopeable;

    protected $title = 'IP KPIs';

    public function index(Content $content)
    {
        return $content
            ->title('IP KPIs')
            ->description('Implementing Partner performance against targets')
            ->row(function (Row $row) {
                $this->renderPage($row);
            });
    }

    private function renderPage(Row $row)
    {
        $isSuperAdmin = $this->isSuperAdmin();
        $ipId         = $this->getAdminIpId();

        $ips = ImplementingPartner::active()
            ->when(!$isSuperAdmin && $ipId, fn($q) => $q->where('id', $ipId))
            ->get();

        if ($ips->isEmpty()) {
            $row->column(12, function (Column $col) {
                $col->append("<div style='background:#fff;border:1px solid #ddd;padding:24px;text-align:center;'>
                    <p class='text-muted'><i class='fa fa-info-circle'></i> No active Implementing Partners found.</p>
                </div>");
            });
            return;
        }

        // ── Compute all scorecards once ────────────────────────────────
        $scorecards = [];
        foreach ($ips as $ip) {
            $scorecards[$ip->id] = KpiService::ipScorecard($ip->id);
        }

        // ── Aggregate summary stats ────────────────────────────────────
        $row->column(12, function (Column $col) use ($ips, $scorecards) {
            $totalFac    = 0;
            $totalGroups = 0;
            $totalMembers = 0;
            $sumOverall  = 0;

            foreach ($ips as $ip) {
                $card          = $scorecards[$ip->id];
                $totalFac     += $card['actuals']['total_facilitators'];
                $totalGroups  += $card['actuals']['total_groups'];
                $totalMembers += $card['actuals']['total_members'];
                $sumOverall   += $card['overall_score'];
            }

            $avgOverall   = $ips->count() > 0 ? round($sumOverall / $ips->count(), 1) : 0;
            $overallColor = $avgOverall >= 80 ? '#4caf50' : ($avgOverall >= 50 ? '#ff9800' : '#f44336');

            $html  = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
            $html .= "<h4 style='margin:0 0 12px;'><i class='fa fa-building'></i>&nbsp; IP Overview</h4>";
            $html .= "<div style='display:flex;gap:12px;flex-wrap:wrap;'>";
            foreach ([
                ['IPs',           $ips->count(), 'fa-building',     '#2196F3'],
                ['Facilitators',  $totalFac,     'fa-user-circle',  '#4caf50'],
                ['Groups',        $totalGroups,  'fa-users',        '#ff9800'],
                ['Members',       $totalMembers, 'fa-user',         '#9c27b0'],
                ['Avg Score',     $avgOverall . '%', 'fa-trophy',   $overallColor],
            ] as $i) {
                $html .= "<div style='flex:1;min-width:140px;text-align:center;padding:12px;border:1px solid #eee;'>
                    <i class='fa {$i[2]}' style='font-size:18px;color:{$i[3]};'></i>
                    <div style='font-size:24px;font-weight:700;color:{$i[3]};margin:4px 0;'>{$i[1]}</div>
                    <div style='font-size:11px;text-transform:uppercase;color:#666;'>{$i[0]}</div>
                </div>";
            }
            $html .= "</div></div>";
            $col->append($html);
        });

        // ── IP performance table ───────────────────────────────────────
        $row->column(12, function (Column $col) use ($ips, $scorecards) {
            $weekLabel = '';
            if ($ips->count() > 0) {
                $first = $scorecards[$ips->first()->id];
                $weekLabel = date('d M', strtotime($first['week_start']))
                    . ' – ' . date('d M Y', strtotime($first['week_end']));
            }

            $html  = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
            $html .= "<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;'>";
            $html .= "<h4 style='margin:0;'><i class='fa fa-table'></i>&nbsp; IP Performance This Week</h4>";
            $html .= "<span class='label label-info'>{$weekLabel}</span>";
            $html .= "</div>";
            $html .= "<div style='overflow-x:auto;'>";
            $html .= "<table class='table table-bordered table-striped table-condensed' style='margin:0;font-size:13px;'>";
            $html .= "<thead><tr style='background:#f5f5f5;'>
                <th>IP</th>
                <th style='text-align:center;'>Start Date</th>
                <th style='text-align:center;'>Facilitators</th>
                <th style='text-align:center;'>Groups</th>
                <th style='text-align:center;'>Members</th>
                <th style='text-align:center;'>Trainings/wk</th>
                <th style='text-align:center;'>Meetings/wk</th>
                <th style='text-align:center;'>Avg Fac Score</th>
                <th style='text-align:center;'>% Facs Met KPI</th>
                <th style='text-align:center;'>Overall</th>
            </tr></thead><tbody>";

            foreach ($ips as $ip) {
                $card    = $scorecards[$ip->id];
                $a       = $card['actuals'];
                $t       = $card['targets'];
                $s       = $card['scores'];
                $fp      = $card['facilitator_performance'];
                $overall = $card['overall_score'];
                $c       = $overall >= 80 ? '#4caf50' : ($overall >= 50 ? '#ff9800' : '#f44336');
                $bg      = $overall >= 80 ? '#e8f5e9' : ($overall >= 50 ? '#fff3e0' : '#ffebee');

                $html .= "<tr>";
                $html .= "<td><strong>" . e($ip->name) . "</strong>"
                    . ($ip->short_name ? "<br><small class='text-muted'>" . e($ip->short_name) . "</small>" : "")
                    . "</td>";
                $html .= "<td style='text-align:center;font-size:12px;color:#666;'>{$card['start_date']}</td>";
                $html .= self::scoreCell("{$a['total_facilitators']}/{$t['facilitators']}", $s['facilitators']);
                $html .= self::scoreCell("{$a['total_groups']}/{$t['groups']}", $s['groups']);
                $html .= self::scoreCell("{$a['total_members']}/{$t['members']}", $s['members']);
                $html .= self::scoreCell("{$a['trainings_this_week']}/{$t['trainings_per_week']}", $s['trainings']);
                $html .= self::scoreCell("{$a['meetings_this_week']}/{$t['meetings_per_week']}", $s['meetings']);
                $html .= "<td style='text-align:center;'><strong>{$fp['avg_score']}%</strong></td>";
                $html .= "<td style='text-align:center;'>{$fp['pct_meeting_kpi']}%</td>";
                $html .= "<td style='text-align:center;background:{$bg};'>
                    <div style='font-weight:700;font-size:18px;color:{$c};'>{$overall}%</div>
                    <div style='font-size:10px;color:#888;'>" . self::perfLabel($overall) . "</div>
                </td>";
                $html .= "</tr>";
            }

            $html .= "</tbody></table></div></div>";
            $col->append($html);
        });

        // ── Per-IP facilitator breakdown (expandable) ──────────────────
        foreach ($ips as $ip) {
            $row->column(12, function (Column $col) use ($ip, $scorecards) {
                $facScorecards = $scorecards[$ip->id]['facilitator_performance']['scorecards'] ?? [];

                $html  = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
                $html .= "<h4 style='margin:0 0 0;cursor:pointer;' onclick=\"
                    var el=this.nextElementSibling;
                    el.style.display=el.style.display==='none'?'block':'none';
                    this.querySelector('.fa-caret').className='fa fa-caret-'+(el.style.display==='none'?'right':'down')+' fa-caret';
                \">";
                $html .= "<i class='fa fa-caret-right fa-caret'></i> " . e($ip->name)
                    . " &mdash; Facilitator Breakdown"
                    . " <small class='text-muted'>(" . count($facScorecards) . " facilitator(s) &mdash; click to expand)</small></h4>";
                $html .= "<div style='display:none;margin-top:12px;'>";

                if (empty($facScorecards)) {
                    $html .= "<p class='text-muted'><i class='fa fa-info-circle'></i> No facilitators with active groups for this IP.</p>";
                } else {
                    $html .= "<div style='overflow-x:auto;'>";
                    $html .= "<table class='table table-bordered table-condensed' style='margin:0;font-size:12px;'>";
                    $html .= "<thead><tr style='background:#f9f9f9;'>
                        <th>Facilitator</th>
                        <th style='text-align:center;'>Groups</th>
                        <th style='text-align:center;'>Trainings</th>
                        <th style='text-align:center;'>Meetings/grp</th>
                        <th style='text-align:center;'>Members/grp</th>
                        <th style='text-align:center;'>AESA</th>
                        <th style='text-align:center;'>Attendance</th>
                        <th style='text-align:center;'>Overall</th>
                    </tr></thead><tbody>";

                    foreach ($facScorecards as $fs) {
                        $overall = $fs['overall_score'];
                        $c       = $overall >= 80 ? '#4caf50' : ($overall >= 50 ? '#ff9800' : '#f44336');
                        $bg      = $overall >= 80 ? '#e8f5e9' : ($overall >= 50 ? '#fff3e0' : '#ffebee');
                        $a       = $fs['actuals'];
                        $s       = $fs['scores'];
                        $html .= "<tr>";
                        $html .= "<td><strong>" . e($fs['facilitator_name'] ?? "User #{$fs['facilitator_id']}") . "</strong></td>";
                        $html .= self::scoreCell($a['total_groups'], $s['groups']);
                        $html .= self::scoreCell($a['trainings_this_week'], $s['trainings']);
                        $html .= self::scoreCell($a['meetings_per_group'], $s['meetings']);
                        $html .= self::scoreCell($a['avg_members_per_group'], $s['members']);
                        $html .= self::scoreCell($a['aesa_this_week'], $s['aesa']);
                        $html .= self::scoreCell(round($a['attendance_pct'], 1) . '%', $s['attendance']);
                        $html .= "<td style='text-align:center;background:{$bg};'>
                            <div style='font-weight:700;color:{$c};'>{$overall}%</div>
                            <div style='font-size:10px;color:#888;'>" . self::perfLabel($overall) . "</div>
                        </td>";
                        $html .= "</tr>";
                    }

                    $html .= "</tbody></table></div>";
                }

                $html .= "</div></div>";
                $col->append($html);
            });
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private static function scoreCell($value, float $score): string
    {
        $color = $score >= 80 ? '#4caf50' : ($score >= 50 ? '#ff9800' : '#f44336');
        $bg    = $score >= 80 ? '#e8f5e9' : ($score >= 50 ? '#fff3e0' : '#ffebee');
        return "<td style='text-align:center;background:{$bg};padding:6px 4px;'>
            <div style='font-weight:600;'>{$value}</div>
            <small style='color:{$color};font-weight:600;'>{$score}%</small>
        </td>";
    }

    private static function perfLabel(float $score): string
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Fair';
        return 'Below Target';
    }
}
