<?php

namespace App\Admin\Controllers;

use App\Models\FfsKpiIpEntry;
use App\Models\FfsKpiFacilitatorEntry;
use App\Models\FfsKpiIndicator;
use App\Models\ImplementingPartner;
use App\Admin\Traits\IpScopeable;
use App\Http\Controllers\Controller;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Facades\DB;

/**
 * FFS KPI Dashboard — Aggregated analytics for manually-entered KPI data.
 *
 * Super Admin: All IPs visible.
 * IP Admin:    Scoped to their own IP.
 */
class FfsKpiDashboardController extends Controller
{
    use IpScopeable;

    public function index(Content $content)
    {
        Admin::js('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js');
        Admin::style($this->getStyles());

        $ipId = $this->getAdminIpId();

        return $content
            ->title('📊 FFS KPI Dashboard')
            ->description('Project KPI performance — manually entered by Implementing Partners')
            ->row(function (Row $row) use ($ipId) {
                $this->addSummaryCards($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addProjectProgressTable($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addTargetAllocationTable($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addIpPerformanceChart($row, $ipId);
                $this->addIpPerformanceTable($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addIpContributionTable($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addDistrictDashboard($row, $ipId);
                $this->addIpDistrictCoverageTable($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addFacilitatorSummary($row, $ipId);
                $this->addMonthlyTrendChart($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addMonitoringAlerts($row, $ipId);
            });
    }

    // ── Row 1: Summary KPI Cards ──────────────────────────────────────────

    private function addSummaryCards(Row $row, ?int $ipId): void
    {
        $totalEntries = FfsKpiIpEntry::when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();

        $ipCount = FfsKpiIpEntry::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->distinct('ip_id')->count('ip_id');

        // Weighted avg performance
        $entries = FfsKpiIpEntry::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->where('target', '>', 0)
            ->get(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec','target']);
        $totalTarget  = $entries->sum('target');
        $totalAchieved = $entries->sum(fn($e) => $e->overall);
        $avgPerf = $totalTarget > 0 ? round($totalAchieved / $totalTarget * 100, 1) : 0;

        // At-risk IPs (avg performance < 85%)
        $atRiskCount = 0;
        if ($this->isSuperAdmin()) {
            $ips = ImplementingPartner::active()->pluck('id');
            foreach ($ips as $id) {
                $ipEntries = FfsKpiIpEntry::where('ip_id', $id)->where('target', '>', 0)->get(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec','target']);
                if ($ipEntries->isEmpty()) continue;
                $t = $ipEntries->sum('target');
                $a = $ipEntries->sum(fn($e) => $e->overall);
                if ($t > 0 && ($a / $t * 100) < 85) $atRiskCount++;
            }
        }

        $facilCount = FfsKpiFacilitatorEntry::when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();

        $perfColor = $avgPerf >= 85 ? '#4caf50' : ($avgPerf >= 70 ? '#ff9800' : '#f44336');
        $perfLabel = FfsKpiIpEntry::performanceLabel($avgPerf);

        $cards = [
            ['icon' => 'fa-table',       'color' => '#05179F', 'number' => number_format($totalEntries), 'label' => 'IP KPI Entries',         'detail' => 'Monthly data rows recorded'],
            ['icon' => 'fa-building',     'color' => '#388e3c', 'number' => $ipCount,                    'label' => 'IPs Reporting',           'detail' => 'Partners with KPI data'],
            ['icon' => 'fa-line-chart',   'color' => $perfColor,'number' => $avgPerf . '%',              'label' => 'Avg Performance',         'detail' => $perfLabel],
            ['icon' => 'fa-users',        'color' => '#7b1fa2', 'number' => number_format($facilCount),  'label' => 'Facilitator Entries',     'detail' => 'Session records entered'],
        ];

        $row->column(12, function (Column $col) use ($cards, $atRiskCount) {
            $html = '<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:8px;">';
            foreach ($cards as $c) {
                $html .= "
                <div class='kpi-card' style='flex:1;min-width:200px;display:flex;align-items:center;gap:14px;background:#fff;border-radius:4px;padding:16px 20px;box-shadow:0 1px 3px rgba(0,0,0,.12);'>
                  <div style='width:48px;height:48px;border-radius:50%;background:{$c['color']};display:flex;align-items:center;justify-content:center;flex-shrink:0;'>
                    <i class='fa {$c['icon']}' style='color:#fff;font-size:18px;'></i>
                  </div>
                  <div>
                    <div style='font-size:26px;font-weight:800;color:#263238;line-height:1.1;'>{$c['number']}</div>
                    <div style='font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#607d8b;font-weight:600;'>{$c['label']}</div>
                    <div style='font-size:11px;color:#90a4ae;margin-top:2px;'>{$c['detail']}</div>
                  </div>
                </div>";
            }
            if ($this->isSuperAdmin()) {
                $alertColor = $atRiskCount > 0 ? '#f44336' : '#4caf50';
                $html .= "
                <div class='kpi-card' style='flex:1;min-width:200px;display:flex;align-items:center;gap:14px;background:#fff;border-radius:4px;padding:16px 20px;box-shadow:0 1px 3px rgba(0,0,0,.12);'>
                  <div style='width:48px;height:48px;border-radius:50%;background:{$alertColor};display:flex;align-items:center;justify-content:center;flex-shrink:0;'>
                    <i class='fa fa-exclamation-triangle' style='color:#fff;font-size:18px;'></i>
                  </div>
                  <div>
                    <div style='font-size:26px;font-weight:800;color:#263238;line-height:1.1;'>{$atRiskCount}</div>
                    <div style='font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#607d8b;font-weight:600;'>At-Risk IPs</div>
                    <div style='font-size:11px;color:#90a4ae;margin-top:2px;'>Performance &lt; 85%</div>
                  </div>
                </div>";
            }
            $html .= '</div>';
            $col->append(new Box('', $html));
        });
    }

    // ── Row 2: Project KPI Progress Table ────────────────────────────────

    private function addProjectProgressTable(Row $row, ?int $ipId): void
    {
        $indicators = FfsKpiIndicator::where('type', 'ip')->orderBy('sort_order')->get();

        $html  = '<div style="overflow-x:auto;">';
        $html .= '<table class="kpi-table" style="width:100%;border-collapse:collapse;font-size:13px;">';
        $html .= '<thead><tr style="background:#05179F;color:#fff;">
            <th style="padding:10px 12px;text-align:left;width:60px;">Output</th>
            <th style="padding:10px 12px;text-align:left;">KPI Indicator</th>
            <th style="padding:10px 12px;text-align:right;white-space:nowrap;">Total Target</th>
            <th style="padding:10px 12px;text-align:right;white-space:nowrap;">Achieved</th>
            <th style="padding:10px 12px;text-align:right;white-space:nowrap;">Performance</th>
            <th style="padding:10px 12px;text-align:center;white-space:nowrap;">Status</th>
        </tr></thead><tbody>';

        $grandTarget   = 0;
        $grandAchieved = 0;

        foreach ($indicators as $ind) {
            $entries = FfsKpiIpEntry::when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->where('indicator_id', $ind->id)
                ->get(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec','target']);

            $target   = $entries->sum('target');
            $achieved = $entries->sum(fn($e) => $e->overall);
            $pct      = $target > 0 ? round($achieved / $target * 100, 1) : 0;
            $label    = FfsKpiIpEntry::performanceLabel($pct);
            $color    = FfsKpiIpEntry::performanceColor($pct);

            $grandTarget   += $target;
            $grandAchieved += $achieved;

            $outColors = [1 => '#05179F', 2 => '#388e3c', 3 => '#e65100'];
            $outColor  = $outColors[$ind->output_number] ?? '#607d8b';

            $html .= "<tr style='border-bottom:1px solid #e0e0e0;'>
                <td style='padding:9px 12px;'>
                  <span style='display:inline-block;padding:2px 8px;background:{$outColor};color:#fff;font-size:10px;font-weight:700;'>Out {$ind->output_number}</span>
                </td>
                <td style='padding:9px 12px;font-weight:500;'>" . e($ind->indicator_name) . "</td>
                <td style='padding:9px 12px;text-align:right;'>" . number_format($target, 0) . "</td>
                <td style='padding:9px 12px;text-align:right;font-weight:700;color:{$color};'>" . number_format($achieved, 0) . "</td>
                <td style='padding:9px 12px;text-align:right;font-weight:700;color:{$color};'>{$pct}%</td>
                <td style='padding:9px 12px;text-align:center;'>
                  <span style='display:inline-block;padding:2px 10px;background:{$color};color:#fff;font-size:11px;font-weight:600;'>{$label}</span>
                </td>
            </tr>";
        }

        // Grand total row
        $grandPct   = $grandTarget > 0 ? round($grandAchieved / $grandTarget * 100, 1) : 0;
        $grandColor = FfsKpiIpEntry::performanceColor($grandPct);
        $html .= "<tr style='background:#f5f5f5;font-weight:700;border-top:2px solid #bdbdbd;'>
            <td colspan='2' style='padding:10px 12px;'>PROJECT TOTAL</td>
            <td style='padding:10px 12px;text-align:right;'>" . number_format($grandTarget, 0) . "</td>
            <td style='padding:10px 12px;text-align:right;color:{$grandColor};'>" . number_format($grandAchieved, 0) . "</td>
            <td style='padding:10px 12px;text-align:right;color:{$grandColor};'>{$grandPct}%</td>
            <td style='padding:10px 12px;text-align:center;'>
              <span style='display:inline-block;padding:2px 10px;background:{$grandColor};color:#fff;font-size:11px;'>" . FfsKpiIpEntry::performanceLabel($grandPct) . "</span>
            </td>
        </tr>";

        $html .= '</tbody></table></div>';

        $row->column(12, function (Column $col) use ($html) {
            $col->append(new Box('📈 Project KPI Progress', $html));
        });
    }

    // ── Row 2b: KPI Target Allocation per IP ─────────────────────────────

    private function addTargetAllocationTable(Row $row, ?int $ipId): void
    {
        if (!$this->isSuperAdmin()) return; // only meaningful for super admin

        $indicators = FfsKpiIndicator::where('type', 'ip')->orderBy('sort_order')->get();
        $ips        = ImplementingPartner::active()->orderBy('name')->get();

        if ($ips->isEmpty() || $indicators->isEmpty()) return;

        $html  = '<div style="overflow-x:auto;">';
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:12px;">';

        // Header
        $html .= '<thead><tr style="background:#4a148c;color:#fff;">';
        $html .= '<th style="padding:9px 12px;text-align:left;white-space:nowrap;">KPI Indicator</th>';
        $html .= '<th style="padding:9px 12px;text-align:right;white-space:nowrap;">Default<br>Target</th>';
        foreach ($ips as $ip) {
            $label = $ip->short_name ?? substr($ip->name, 0, 14);
            $html .= '<th style="padding:9px 8px;text-align:right;white-space:nowrap;">' . e($label) . '<br><small style="font-weight:400;opacity:.8;">Entered Target</small></th>';
        }
        $html .= '<th style="padding:9px 12px;text-align:right;white-space:nowrap;">Project<br>Total Target</th>';
        $html .= '</tr></thead><tbody>';

        $colTotals = array_fill(0, $ips->count(), 0);
        $grandTotal = 0;

        foreach ($indicators as $ind) {
            $outColors = [1 => '#05179F', 2 => '#388e3c', 3 => '#e65100'];
            $outColor  = $outColors[$ind->output_number] ?? '#607d8b';

            $html .= "<tr style='border-bottom:1px solid #e0e0e0;'>";
            $html .= "<td style='padding:8px 12px;'>
                <span style='display:inline-block;padding:1px 6px;background:{$outColor};color:#fff;font-size:10px;font-weight:700;margin-right:6px;'>Out {$ind->output_number}</span>"
                . e($ind->indicator_name) . "</td>";
            $html .= "<td style='padding:8px 12px;text-align:right;color:#9e9e9e;'>" . number_format($ind->default_target, 0) . "</td>";

            $rowTotal = 0;
            $ipIdx    = 0;
            foreach ($ips as $ip) {
                $target = (float) FfsKpiIpEntry::where('ip_id', $ip->id)
                    ->where('indicator_id', $ind->id)
                    ->sum('target');
                $rowTotal          += $target;
                $colTotals[$ipIdx] += $target;
                $ipIdx++;

                $style = $target > 0 ? 'font-weight:700;color:#1565c0;' : 'color:#bdbdbd;';
                $html .= "<td style='padding:8px;text-align:right;{$style}'>" . ($target > 0 ? number_format($target, 0) : '—') . "</td>";
            }
            $grandTotal += $rowTotal;

            $html .= "<td style='padding:8px 12px;text-align:right;font-weight:700;'>" . number_format($rowTotal, 0) . "</td>";
            $html .= "</tr>";
        }

        // Totals footer
        $html .= "<tr style='background:#f5f5f5;font-weight:700;border-top:2px solid #9c27b0;color:#4a148c;'>";
        $html .= "<td colspan='2' style='padding:10px 12px;'>TOTAL COMMITTED TARGETS</td>";
        foreach ($colTotals as $colTotal) {
            $html .= "<td style='padding:10px 8px;text-align:right;'>" . number_format($colTotal, 0) . "</td>";
        }
        $html .= "<td style='padding:10px 12px;text-align:right;'>" . number_format($grandTotal, 0) . "</td>";
        $html .= "</tr>";

        $html .= '</tbody></table></div>';
        $html .= '<p style="margin-top:8px;font-size:11px;color:#9e9e9e;padding:0 4px;">Shows the sum of annual targets entered by each IP per indicator. Default Target is the project-level benchmark per indicator.</p>';

        $row->column(12, function (Column $col) use ($html) {
            $col->append(new Box('🎯 KPI Target Allocation — By IP and Indicator', $html));
        });
    }

    // ── Row 3: IP Performance Chart + Table ──────────────────────────────

    private function addIpPerformanceChart(Row $row, ?int $ipId): void
    {
        $data = $this->getIpPerformanceData($ipId);
        if (empty($data)) {
            $row->column(8, function (Column $col) {
                $col->append(new Box('IP Performance', '<p style="padding:20px;color:#999;">No IP KPI data recorded yet.</p>'));
            });
            return;
        }

        $labels = json_encode(array_column($data, 'name'));
        $values = json_encode(array_column($data, 'pct'));
        $colors = json_encode(array_map(fn($d) => FfsKpiIpEntry::performanceColor($d['pct']), $data));

        $html = '<canvas id="ipPerfChart" height="180"></canvas>
        <script>
        (function() {
            var t = setInterval(function() {
                if (typeof Chart === "undefined" || !document.getElementById("ipPerfChart")) return;
                clearInterval(t);
                new Chart(document.getElementById("ipPerfChart").getContext("2d"), {
                    type: "bar",
                    data: {
                        labels: ' . $labels . ',
                        datasets: [{
                            label: "Performance %",
                            data: ' . $values . ',
                            backgroundColor: ' . $colors . ',
                            borderRadius: 3,
                        }]
                    },
                    options: {
                        indexAxis: "y",
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: { label: ctx => ctx.parsed.x + "%" }
                            }
                        },
                        scales: {
                            x: {
                                min: 0, max: 120,
                                ticks: { callback: v => v + "%" },
                                grid: { color: "rgba(0,0,0,.05)" }
                            },
                            y: { grid: { display: false } }
                        }
                    }
                });
            }, 100);
        })();
        </script>';

        $row->column(8, function (Column $col) use ($html) {
            $col->append(new Box('🏆 IP Performance Comparison', $html));
        });
    }

    private function addIpPerformanceTable(Row $row, ?int $ipId): void
    {
        $data = $this->getIpPerformanceData($ipId);

        $html  = '<table style="width:100%;border-collapse:collapse;font-size:12px;">';
        $html .= '<thead><tr style="background:#263238;color:#fff;">
            <th style="padding:8px;text-align:left;">Partner</th>
            <th style="padding:8px;text-align:left;">Districts</th>
            <th style="padding:8px;text-align:right;">Target</th>
            <th style="padding:8px;text-align:right;">Achieved</th>
            <th style="padding:8px;text-align:center;">Status</th>
        </tr></thead><tbody>';

        foreach ($data as $d) {
            $color    = FfsKpiIpEntry::performanceColor($d['pct']);
            $label    = FfsKpiIpEntry::performanceLabel($d['pct']);
            $districts = !empty($d['districts']) ? e(implode(', ', $d['districts'])) : '<span style="color:#bdbdbd;">—</span>';
            $html .= "<tr style='border-bottom:1px solid #e0e0e0;'>
                <td style='padding:8px;font-weight:600;'>" . e($d['name']) . "</td>
                <td style='padding:8px;font-size:11px;color:#37474f;'>{$districts}</td>
                <td style='padding:8px;text-align:right;'>" . number_format($d['target'], 0) . "</td>
                <td style='padding:8px;text-align:right;font-weight:700;color:{$color};'>" . number_format($d['achieved'], 0) . "</td>
                <td style='padding:8px;text-align:center;'>
                  <span style='padding:2px 8px;background:{$color};color:#fff;font-size:10px;font-weight:700;'>{$d['pct']}%</span>
                </td>
            </tr>";
        }

        if (empty($data)) {
            $html .= '<tr><td colspan="5" style="padding:16px;text-align:center;color:#999;">No data yet</td></tr>';
        }

        $html .= '</tbody></table>';

        $row->column(4, function (Column $col) use ($html) {
            $col->append(new Box('Partner Summary', $html));
        });
    }

    // ── Row 4: IP Contribution Table (super admin only) ──────────────────

    private function addIpContributionTable(Row $row, ?int $ipId): void
    {
        if (!$this->isSuperAdmin()) return;

        $indicators = FfsKpiIndicator::where('type', 'ip')->orderBy('sort_order')->get();
        $ips        = ImplementingPartner::active()->orderBy('name')->get();

        if ($ips->isEmpty() || $indicators->isEmpty()) return;

        $html  = '<div style="overflow-x:auto;">';
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:12px;">';

        // Header
        $html .= '<thead><tr style="background:#37474f;color:#fff;">';
        $html .= '<th style="padding:9px 12px;text-align:left;white-space:nowrap;">KPI Indicator</th>';
        $html .= '<th style="padding:9px 12px;text-align:right;white-space:nowrap;">Target<br><small>per IP</small></th>';
        foreach ($ips as $ip) {
            $short = strlen($ip->name) > 12 ? $ip->abbreviation ?? substr($ip->name, 0, 10) : $ip->name;
            $html .= '<th style="padding:9px 8px;text-align:right;white-space:nowrap;">' . e($short) . '</th>';
        }
        $html .= '<th style="padding:9px 12px;text-align:right;white-space:nowrap;">Project<br>Total</th>';
        $html .= '<th style="padding:9px 12px;text-align:center;white-space:nowrap;">Performance</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($indicators as $ind) {
            $html .= "<tr style='border-bottom:1px solid #e0e0e0;'>";
            $html .= "<td style='padding:8px 12px;font-weight:500;'>" . e($ind->indicator_name) . "</td>";
            $html .= "<td style='padding:8px 12px;text-align:right;color:#607d8b;'>" . number_format($ind->default_target, 0) . "</td>";

            $projectTotal = 0;
            $projectTarget = 0;

            foreach ($ips as $ip) {
                $entries = FfsKpiIpEntry::where('ip_id', $ip->id)
                    ->where('indicator_id', $ind->id)
                    ->get(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec','target']);
                $achieved = $entries->sum(fn($e) => $e->overall);
                $target   = $entries->sum('target');
                $projectTotal  += $achieved;
                $projectTarget += $target;

                $color = $achieved > 0 ? ($achieved >= $target && $target > 0 ? '#388e3c' : '#e65100') : '#bdbdbd';
                $html .= "<td style='padding:8px;text-align:right;font-weight:600;color:{$color};'>" . number_format($achieved, 0) . "</td>";
            }

            $pct   = $projectTarget > 0 ? round($projectTotal / $projectTarget * 100, 1) : 0;
            $color = FfsKpiIpEntry::performanceColor($pct);
            $html .= "<td style='padding:8px 12px;text-align:right;font-weight:700;'>" . number_format($projectTotal, 0) . "</td>";
            $html .= "<td style='padding:8px 12px;text-align:center;'><span style='padding:2px 8px;background:{$color};color:#fff;font-size:11px;font-weight:700;'>{$pct}%</span></td>";
            $html .= "</tr>";
        }

        $html .= '</tbody></table></div>';

        $row->column(12, function (Column $col) use ($html) {
            $col->append(new Box('🤝 IP Contribution Table — All Partners vs All Indicators', $html));
        });
    }

    // ── Row 5: District Dashboard ─────────────────────────────────────────

    private function addDistrictDashboard(Row $row, ?int $ipId): void
    {
        $districtData = FfsKpiIpEntry::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->with(['ip'])
            ->get(['ip_id','district','jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec','indicator_id'])
            ->groupBy('district');

        if ($districtData->isEmpty()) {
            $row->column(8, fn(Column $col) => $col->append(new Box('District Implementation', '<p style="padding:20px;color:#999;">No district data recorded yet.</p>')));
            return;
        }

        $html  = '<div style="overflow-x:auto;">';
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
        $html .= '<thead><tr style="background:#1565c0;color:#fff;">
            <th style="padding:9px 12px;text-align:left;">District</th>
            <th style="padding:9px 12px;text-align:left;">Implementing Partner(s)</th>
            <th style="padding:9px 12px;text-align:right;">Entry Rows</th>
            <th style="padding:9px 12px;text-align:right;">Total Achieved</th>
            <th style="padding:9px 12px;text-align:right;">Indicators Covered</th>
        </tr></thead><tbody>';

        foreach ($districtData->sortKeys() as $district => $entries) {
            $ipNames     = $entries->filter(fn($e) => $e->ip)->map(fn($e) => $e->ip->abbreviation ?? $e->ip->name)->unique()->implode(', ');
            $totalAchiev = $entries->sum(fn($e) => $e->overall);
            $indCount    = $entries->pluck('indicator_id')->unique()->count();

            $html .= "<tr style='border-bottom:1px solid #e0e0e0;'>
                <td style='padding:9px 12px;font-weight:600;'>" . e($district) . "</td>
                <td style='padding:9px 12px;color:#1565c0;'>" . e($ipNames ?: '—') . "</td>
                <td style='padding:9px 12px;text-align:right;'>" . number_format($entries->count()) . "</td>
                <td style='padding:9px 12px;text-align:right;font-weight:700;'>" . number_format($totalAchiev, 0) . "</td>
                <td style='padding:9px 12px;text-align:right;'>{$indCount}</td>
            </tr>";
        }

        $html .= '</tbody></table></div>';

        $row->column(8, function (Column $col) use ($html) {
            $col->append(new Box('🗺️ District Implementation Dashboard', $html));
        });
    }

    // ── Row 5b: IP District Coverage (from IP profile) ───────────────────

    private function addIpDistrictCoverageTable(Row $row, ?int $ipId): void
    {
        $ips = ImplementingPartner::active()
            ->when(!$this->isSuperAdmin() && $ipId, fn($q) => $q->where('id', $ipId))
            ->orderBy('name')
            ->get();

        if ($ips->isEmpty()) return;

        $html  = '<table style="width:100%;border-collapse:collapse;font-size:12px;">';
        $html .= '<thead><tr style="background:#00695c;color:#fff;">
            <th style="padding:9px 12px;text-align:left;">Partner</th>
            <th style="padding:9px 12px;text-align:left;">Assigned Districts</th>
            <th style="padding:9px 12px;text-align:center;">Count</th>
        </tr></thead><tbody>';

        foreach ($ips as $ip) {
            $raw = $ip->districts ?? [];
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $districts = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $raw)));
            } else {
                $districts = is_array($raw) ? $raw : [];
            }
            $distText  = !empty($districts)
                ? implode(', ', $districts)
                : '<em style="color:#bdbdbd;">Not configured</em>';
            $count = count($districts);
            $html .= "<tr style='border-bottom:1px solid #e0e0e0;'>
                <td style='padding:9px 12px;font-weight:600;'>" . e($ip->abbreviation ?? $ip->name) . "</td>
                <td style='padding:9px 12px;font-size:11px;color:#37474f;'>" . $distText . "</td>
                <td style='padding:9px 12px;text-align:center;font-weight:700;color:#00695c;'>{$count}</td>
            </tr>";
        }

        $html .= '</tbody></table>';

        $row->column(4, function (Column $col) use ($html) {
            $col->append(new Box('📌 IP District Coverage', $html));
        });
    }

    // ── Row 6: Facilitator Summary + Monthly Trend ───────────────────────

    private function addFacilitatorSummary(Row $row, ?int $ipId): void
    {
        $indicators = FfsKpiIndicator::where('type', 'facilitator')->orderBy('sort_order')->get();

        $html  = '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
        $html .= '<thead><tr style="background:#7b1fa2;color:#fff;">
            <th style="padding:9px 12px;text-align:left;">Indicator</th>
            <th style="padding:9px 12px;text-align:center;">Disaggregation</th>
            <th style="padding:9px 12px;text-align:right;">Sessions</th>
            <th style="padding:9px 12px;text-align:right;">Total Value</th>
        </tr></thead><tbody>';

        foreach ($indicators as $ind) {
            $rows = FfsKpiFacilitatorEntry::when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->where('indicator_id', $ind->id)
                ->selectRaw('disaggregation, COUNT(*) as sessions, SUM(value) as total_value')
                ->groupBy('disaggregation')
                ->orderBy('disaggregation')
                ->get();

            if ($rows->isEmpty()) {
                $html .= "<tr style='border-bottom:1px solid #e0e0e0;'>
                    <td style='padding:9px 12px;font-weight:500;'>" . e($ind->indicator_name) . "</td>
                    <td colspan='3' style='padding:9px 12px;text-align:center;color:#bdbdbd;font-style:italic;'>No data entered</td>
                </tr>";
                continue;
            }

            foreach ($rows as $i => $r) {
                $name = $i === 0 ? e($ind->indicator_name) : '';
                $html .= "<tr style='border-bottom:1px solid #e0e0e0;'>
                    <td style='padding:9px 12px;font-weight:500;'>{$name}</td>
                    <td style='padding:9px 12px;text-align:center;'>
                      <span style='padding:2px 8px;background:#eceff1;color:#37474f;font-size:11px;font-weight:600;'>" . e($r->disaggregation) . "</span>
                    </td>
                    <td style='padding:9px 12px;text-align:right;'>{$r->sessions}</td>
                    <td style='padding:9px 12px;text-align:right;font-weight:700;color:#7b1fa2;'>" . number_format($r->total_value, 0) . "</td>
                </tr>";
            }
        }

        $html .= '</tbody></table>';

        $row->column(6, function (Column $col) use ($html) {
            $col->append(new Box('👥 Facilitator KPI Summary', $html));
        });
    }

    private function addMonthlyTrendChart(Row $row, ?int $ipId): void
    {
        $months = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
        $labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        // IP KPI monthly totals
        $ipTotals = [];
        foreach ($months as $m) {
            $ipTotals[] = (float) FfsKpiIpEntry::when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->whereNotNull($m)->sum($m);
        }

        // Facilitator monthly totals (by session_date month)
        $facTotals = [];
        foreach (range(1, 12) as $m) {
            $facTotals[] = (float) FfsKpiFacilitatorEntry::when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->whereMonth('session_date', $m)
                ->whereYear('session_date', date('Y'))
                ->sum('value');
        }

        $ipData  = json_encode($ipTotals);
        $facData = json_encode($facTotals);
        $labelsJ = json_encode($labels);

        $html = '<canvas id="kpiMonthlyTrend" height="220"></canvas>
        <script>
        (function() {
            var t = setInterval(function() {
                if (typeof Chart === "undefined" || !document.getElementById("kpiMonthlyTrend")) return;
                clearInterval(t);
                new Chart(document.getElementById("kpiMonthlyTrend").getContext("2d"), {
                    type: "line",
                    data: {
                        labels: ' . $labelsJ . ',
                        datasets: [
                            {
                                label: "IP KPI Monthly Totals",
                                data: ' . $ipData . ',
                                borderColor: "#05179F",
                                backgroundColor: "rgba(5,23,159,.08)",
                                tension: 0.3,
                                fill: true,
                                pointRadius: 4,
                            },
                            {
                                label: "Facilitator Session Values",
                                data: ' . $facData . ',
                                borderColor: "#7b1fa2",
                                backgroundColor: "rgba(123,31,162,.06)",
                                tension: 0.3,
                                fill: true,
                                pointRadius: 4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: "top" },
                            tooltip: { mode: "index", intersect: false }
                        },
                        scales: {
                            x: { grid: { color: "rgba(0,0,0,.04)" } },
                            y: { beginAtZero: true, grid: { color: "rgba(0,0,0,.04)" } }
                        }
                    }
                });
            }, 100);
        })();
        </script>';

        $row->column(6, function (Column $col) use ($html) {
            $col->append(new Box('📅 Monthly Reporting Trend (' . date('Y') . ')', $html));
        });
    }

    // ── Row 7: Monitoring Alerts ──────────────────────────────────────────

    private function addMonitoringAlerts(Row $row, ?int $ipId): void
    {
        // Collect all alert-worthy situations
        $alerts = [];

        $ips = ImplementingPartner::active()
            ->when(!$this->isSuperAdmin() && $ipId, fn($q) => $q->where('id', $ipId))
            ->get();

        foreach ($ips as $ip) {
            $indicators = FfsKpiIndicator::where('type', 'ip')->orderBy('sort_order')->get();
            foreach ($indicators as $ind) {
                $entries = FfsKpiIpEntry::where('ip_id', $ip->id)
                    ->where('indicator_id', $ind->id)
                    ->get(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec','target']);

                if ($entries->isEmpty()) continue;

                $target   = $entries->sum('target');
                $achieved = $entries->sum(fn($e) => $e->overall);
                if ($target <= 0) continue;

                $pct = round($achieved / $target * 100, 1);
                if ($pct >= 85) continue; // No alert needed

                $action = $pct < 70
                    ? 'Immediate field support required — escalate to PCU'
                    : 'Conduct catch-up review and strengthen field support';

                $alerts[] = [
                    'partner'   => $ip->abbreviation ?? $ip->name,
                    'indicator' => $ind->indicator_name,
                    'target'    => $target,
                    'achieved'  => $achieved,
                    'pct'       => $pct,
                    'label'     => FfsKpiIpEntry::performanceLabel($pct),
                    'color'     => FfsKpiIpEntry::performanceColor($pct),
                    'action'    => $action,
                ];
            }
        }

        $html = '';
        if (empty($alerts)) {
            $html = '<div style="padding:20px;text-align:center;">
                <i class="fa fa-check-circle" style="font-size:32px;color:#4caf50;"></i>
                <p style="margin-top:8px;color:#388e3c;font-weight:600;font-size:14px;">All tracked indicators are performing at or above the 85% threshold. No alerts.</p>
            </div>';
        } else {
            $html  = '<div style="overflow-x:auto;">';
            $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
            $html .= '<thead><tr style="background:#c62828;color:#fff;">
                <th style="padding:9px 12px;text-align:left;">Partner</th>
                <th style="padding:9px 12px;text-align:left;">Indicator</th>
                <th style="padding:9px 12px;text-align:right;">Target</th>
                <th style="padding:9px 12px;text-align:right;">Achieved</th>
                <th style="padding:9px 12px;text-align:center;">Performance</th>
                <th style="padding:9px 12px;text-align:center;">Alert</th>
                <th style="padding:9px 12px;text-align:left;">Recommended Action</th>
            </tr></thead><tbody>';

            foreach ($alerts as $a) {
                $rowBg = $a['pct'] < 70 ? '#fff8f8' : '#fffde7';
                $html .= "<tr style='background:{$rowBg};border-bottom:1px solid #e0e0e0;'>
                    <td style='padding:9px 12px;font-weight:600;'>" . e($a['partner']) . "</td>
                    <td style='padding:9px 12px;'>" . e($a['indicator']) . "</td>
                    <td style='padding:9px 12px;text-align:right;'>" . number_format($a['target'], 0) . "</td>
                    <td style='padding:9px 12px;text-align:right;font-weight:700;color:{$a['color']};'>" . number_format($a['achieved'], 0) . "</td>
                    <td style='padding:9px 12px;text-align:center;'>
                      <span style='padding:3px 10px;background:{$a['color']};color:#fff;font-size:11px;font-weight:700;'>{$a['pct']}% · {$a['label']}</span>
                    </td>
                    <td style='padding:9px 12px;text-align:center;'>
                      <span style='color:{$a['color']};font-size:18px;'>&#9888;</span>
                    </td>
                    <td style='padding:9px 12px;color:#5d4037;font-style:italic;font-size:12px;'>" . e($a['action']) . "</td>
                </tr>";
            }

            $html .= '</tbody></table></div>';
            $html .= '<div style="margin-top:8px;padding:8px 12px;background:#fff9c4;font-size:12px;color:#5d4037;">
                <strong>' . count($alerts) . ' alert(s)</strong> — indicators below the 85% performance threshold. Review recommended.
            </div>';
        }

        $row->column(12, function (Column $col) use ($html, $alerts) {
            $title = empty($alerts) ? '✅ Monitoring Alerts — All Clear' : '⚠️ Monitoring Alerts (' . count($alerts) . ' issues)';
            $col->append(new Box($title, $html));
        });
    }

    // ── Data Helpers ──────────────────────────────────────────────────────

    private function getIpPerformanceData(?int $ipId): array
    {
        $ips = ImplementingPartner::active()
            ->when(!$this->isSuperAdmin() && $ipId, fn($q) => $q->where('id', $ipId))
            ->orderBy('name')
            ->get();

        $result = [];
        foreach ($ips as $ip) {
            $entries = FfsKpiIpEntry::where('ip_id', $ip->id)
                ->where('target', '>', 0)
                ->get(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec','target']);

            if ($entries->isEmpty()) continue;

            $target   = $entries->sum('target');
            $achieved = $entries->sum(fn($e) => $e->overall);
            $pct      = $target > 0 ? round($achieved / $target * 100, 1) : 0;

            $result[] = [
                'name'      => $ip->abbreviation ?? $ip->name,
                'districts' => $ip->districts ?? [],
                'target'    => $target,
                'achieved'  => $achieved,
                'pct'       => $pct,
            ];
        }

        return $result;
    }

    // ── Styles ────────────────────────────────────────────────────────────

    private function getStyles(): string
    {
        return '
        .kpi-card { transition: box-shadow .2s; }
        .kpi-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.15) !important; }
        .kpi-table thead th { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
        .kpi-table tbody tr:hover { background: #f5f5f5 !important; }
        .box-header .box-title { font-weight: 700; font-size: 14px; }
        ';
    }
}
