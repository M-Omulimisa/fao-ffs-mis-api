<?php

namespace App\Admin\Controllers;

use App\Models\ImplementingPartner;
use App\Services\KpiService;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

class KpiIpController extends AdminController
{
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
        $user = Admin::user();
        $isSuperAdmin = $user && $user->isRole('super_admin');
        $ipId = $isSuperAdmin ? null : ($user->ip_id ?? null);

        // If not super admin, show only their own IP
        $ips = ImplementingPartner::active()
            ->when($ipId, fn($q) => $q->where('id', $ipId))
            ->get();

        if ($ips->isEmpty()) {
            $row->column(12, function (Column $col) {
                $col->append("<div style='background:#fff;border:1px solid #ddd;padding:24px;text-align:center;'>
                    <p class='text-muted'>No active Implementing Partners found.</p>
                </div>");
            });
            return;
        }

        // ── Summary cards row ────────────────────────────────
        $row->column(12, function (Column $col) use ($ips) {
            $totalFac = 0; $totalGroups = 0; $totalMembers = 0; $avgOverall = 0;
            $cards = [];
            foreach ($ips as $ip) {
                $card = KpiService::ipScorecard($ip->id);
                $cards[] = ['ip' => $ip, 'card' => $card];
                $totalFac += $card['actuals']['total_facilitators'];
                $totalGroups += $card['actuals']['total_groups'];
                $totalMembers += $card['actuals']['total_members'];
                $avgOverall += $card['overall_score'];
            }
            if ($ips->count() > 0) $avgOverall = round($avgOverall / $ips->count(), 1);
            $overallColor = $avgOverall >= 80 ? '#4caf50' : ($avgOverall >= 50 ? '#ff9800' : '#f44336');

            $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
            $html .= "<h4 style='margin:0 0 12px;'><i class='fa fa-building'></i> IP Overview</h4>";
            $html .= "<div style='display:flex;gap:12px;flex-wrap:wrap;'>";
            $summaryItems = [
                ['IPs', $ips->count(), 'fa-building', '#2196F3'],
                ['Facilitators', $totalFac, 'fa-user-circle', '#4caf50'],
                ['Groups', $totalGroups, 'fa-users', '#ff9800'],
                ['Members', $totalMembers, 'fa-user', '#9c27b0'],
                ['Avg Score', $avgOverall . '%', 'fa-trophy', $overallColor],
            ];
            foreach ($summaryItems as $i) {
                $html .= "<div style='flex:1;min-width:140px;text-align:center;padding:12px;border:1px solid #eee;'>
                    <i class='fa {$i[2]}' style='font-size:18px;color:{$i[3]};'></i>
                    <div style='font-size:24px;font-weight:700;color:{$i[3]};margin:4px 0;'>{$i[1]}</div>
                    <div style='font-size:11px;text-transform:uppercase;color:#666;'>{$i[0]}</div>
                </div>";
            }
            $html .= "</div></div>";
            $col->append($html);
        });

        // ── Detailed IP performance table ────────────────────
        $row->column(12, function (Column $col) use ($ips) {
            $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
            $html .= "<h4 style='margin:0 0 12px;'><i class='fa fa-table'></i> IP Performance This Week</h4>";
            $html .= "<table class='table table-bordered table-striped' style='margin:0;'>";
            $html .= "<thead><tr style='background:#f5f5f5;'>
                <th>IP</th>
                <th style='text-align:center;'>Start Date</th>
                <th style='text-align:center;'>Facilitators</th>
                <th style='text-align:center;'>Groups</th>
                <th style='text-align:center;'>Members</th>
                <th style='text-align:center;'>Trainings/wk</th>
                <th style='text-align:center;'>Meetings/wk</th>
                <th style='text-align:center;'>Avg Fac Score</th>
                <th style='text-align:center;'>% Facs Met</th>
                <th style='text-align:center;'>Overall</th>
            </tr></thead><tbody>";

            foreach ($ips as $ip) {
                $card  = KpiService::ipScorecard($ip->id);
                $a     = $card['actuals'];
                $t     = $card['targets'];
                $s     = $card['scores'];
                $fp    = $card['facilitator_performance'];
                $overall = $card['overall_score'];
                $overallColor = $overall >= 80 ? '#4caf50' : ($overall >= 50 ? '#ff9800' : '#f44336');
                $startDate = $card['start_date'] ?? '-';

                $html .= "<tr>";
                $html .= "<td><strong>" . e($ip->name) . "</strong><br><small class='text-muted'>" . e($ip->short_name) . "</small></td>";
                $html .= "<td style='text-align:center;'>{$startDate}</td>";
                $html .= self::scoreCell("{$a['total_facilitators']}/{$t['facilitators']}", $s['facilitators']);
                $html .= self::scoreCell("{$a['total_groups']}/{$t['groups']}", $s['groups']);
                $html .= self::scoreCell("{$a['total_members']}/{$t['members']}", $s['members']);
                $html .= self::scoreCell("{$a['trainings_this_week']}/{$t['trainings_per_week']}", $s['trainings']);
                $html .= self::scoreCell("{$a['meetings_this_week']}/{$t['meetings_per_week']}", $s['meetings']);
                $html .= "<td style='text-align:center;'>{$fp['avg_score']}%</td>";
                $html .= "<td style='text-align:center;'>{$fp['pct_meeting_kpi']}%</td>";
                $html .= "<td style='text-align:center;font-weight:bold;color:{$overallColor};font-size:16px;'>{$overall}%</td>";
                $html .= "</tr>";
            }

            $html .= "</tbody></table></div>";
            $col->append($html);
        });

        // ── Per-IP facilitator breakdown (expandable) ────────
        foreach ($ips as $ip) {
            $row->column(12, function (Column $col) use ($ip) {
                $card = KpiService::ipScorecard($ip->id);
                $fp   = $card['facilitator_performance'];
                $facScorecards = $fp['scorecards'] ?? [];

                $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
                $html .= "<h4 style='margin:0 0 12px;cursor:pointer;' onclick=\"this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none';\">";
                $html .= "<i class='fa fa-caret-right'></i> " . e($ip->name) . " — Facilitator Breakdown <small class='text-muted'>(click to expand)</small></h4>";
                $html .= "<div style='display:none;'>";

                if (empty($facScorecards)) {
                    $html .= "<p class='text-muted'>No facilitators with active groups for this IP.</p>";
                } else {
                    $html .= "<table class='table table-bordered table-condensed' style='margin:0;'>";
                    $html .= "<thead><tr style='background:#f9f9f9;'>
                        <th>Facilitator</th>
                        <th style='text-align:center;'>Groups</th>
                        <th style='text-align:center;'>Trainings</th>
                        <th style='text-align:center;'>Meetings</th>
                        <th style='text-align:center;'>Members</th>
                        <th style='text-align:center;'>AESA</th>
                        <th style='text-align:center;'>Attendance</th>
                        <th style='text-align:center;'>Overall</th>
                    </tr></thead><tbody>";

                    foreach ($facScorecards as $fs) {
                        $overall = $fs['overall_score'];
                        $overallColor = $overall >= 80 ? '#4caf50' : ($overall >= 50 ? '#ff9800' : '#f44336');
                        $html .= "<tr>";
                        $html .= "<td>" . e($fs['facilitator_name']) . "</td>";
                        $html .= self::scoreCell($fs['scores']['groups'] . '%', $fs['scores']['groups']);
                        $html .= self::scoreCell($fs['scores']['trainings'] . '%', $fs['scores']['trainings']);
                        $html .= self::scoreCell($fs['scores']['meetings'] . '%', $fs['scores']['meetings']);
                        $html .= self::scoreCell($fs['scores']['members'] . '%', $fs['scores']['members']);
                        $html .= self::scoreCell($fs['scores']['aesa'] . '%', $fs['scores']['aesa']);
                        $html .= self::scoreCell($fs['scores']['attendance'] . '%', $fs['scores']['attendance']);
                        $html .= "<td style='text-align:center;font-weight:bold;color:{$overallColor};'>{$overall}%</td>";
                        $html .= "</tr>";
                    }
                    $html .= "</tbody></table>";
                }

                $html .= "</div></div>";
                $col->append($html);
            });
        }
    }

    private static function scoreCell(string $value, float $score): string
    {
        $color = $score >= 80 ? '#4caf50' : ($score >= 50 ? '#ff9800' : '#f44336');
        $bg    = $score >= 80 ? '#e8f5e9' : ($score >= 50 ? '#fff3e0' : '#ffebee');
        return "<td style='text-align:center;background:{$bg};'>
            <div style='font-weight:600;'>{$value}</div>
            <small style='color:{$color};font-weight:600;'>{$score}%</small>
        </td>";
    }
}
