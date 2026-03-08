<?php

namespace App\Admin\Controllers;

use App\Models\KpiBenchmark;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Facades\Admin;
use App\Services\KpiService;
use App\Models\FfsGroup;
use App\Models\ImplementingPartner;

/**
 * KpiBenchmarkController — manage the single-record facilitator KPI
 * benchmark table and display a live KPI dashboard.
 */
class KpiBenchmarkController extends AdminController
{
    protected $title = 'KPI Benchmarks';

    // ─── Dashboard landing ────────────────────────────────
    public function index(Content $content)
    {
        return $content
            ->title('KPI Dashboard')
            ->description('Facilitator & IP performance tracking')
            ->row(function (Row $row) {
                $this->renderDashboard($row);
            });
    }

    private function renderDashboard(Row $row)
    {
        $bench = KpiBenchmark::current();
        $user  = Admin::user();
        $isSuperAdmin = $user && $user->isRole('super_admin');
        $ipId  = $isSuperAdmin ? null : ($user->ip_id ?? null);

        // ── Benchmark summary card ───────────────────────────
        $row->column(12, function (Column $col) use ($bench) {
            $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
            $html .= "<h4 style='margin:0 0 12px;'><i class='fa fa-sliders'></i> Facilitator KPI Benchmarks</h4>";
            $html .= "<div style='display:flex;gap:12px;flex-wrap:wrap;'>";
            $items = [
                ['Min Groups', $bench->min_groups_per_facilitator, 'fa-users', '#2196F3'],
                ['Trainings/Week', $bench->min_trainings_per_week, 'fa-graduation-cap', '#4caf50'],
                ['Meetings/Group/Week', $bench->min_meetings_per_group_per_week, 'fa-calendar-check-o', '#ff9800'],
                ['Members/Group', $bench->min_members_per_group, 'fa-user', '#9c27b0'],
                ['AESA/Week', $bench->min_aesa_sessions_per_week, 'fa-leaf', '#009688'],
                ['Attendance %', $bench->min_meeting_attendance_pct . '%', 'fa-check-circle', '#e91e63'],
            ];
            foreach ($items as $i) {
                $html .= "<div style='flex:1;min-width:140px;text-align:center;padding:12px;border:1px solid #eee;'>
                    <i class='fa {$i[2]}' style='font-size:18px;color:{$i[3]};'></i>
                    <div style='font-size:24px;font-weight:700;color:{$i[3]};margin:4px 0;'>{$i[1]}</div>
                    <div style='font-size:11px;text-transform:uppercase;color:#666;'>{$i[0]}</div>
                </div>";
            }
            $html .= "</div>";
            $html .= "<div style='margin-top:8px;text-align:right;'>";
            $html .= "<a href='" . admin_url('kpi-benchmarks/1/edit') . "' class='btn btn-sm btn-primary'><i class='fa fa-pencil'></i> Edit Benchmarks</a>";
            $html .= "</div></div>";
            $col->append($html);
        });

        // ── Facilitator scorecards ───────────────────────────
        $facilitatorIds = FfsGroup::where('status', 'Active')
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->whereNotNull('facilitator_id')
            ->distinct()
            ->pluck('facilitator_id');

        $row->column(12, function (Column $col) use ($facilitatorIds, $bench) {
            $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
            $html .= "<h4 style='margin:0 0 12px;'><i class='fa fa-bar-chart'></i> Facilitator Performance This Week</h4>";

            if ($facilitatorIds->isEmpty()) {
                $html .= "<p class='text-muted'>No facilitators with active groups found.</p></div>";
                $col->append($html);
                return;
            }

            $html .= "<table class='table table-bordered table-striped' style='margin:0;'>";
            $html .= "<thead><tr style='background:#f5f5f5;'>
                <th>Facilitator</th>
                <th style='text-align:center;'>Groups<br><small class='text-muted'>/{$bench->min_groups_per_facilitator}</small></th>
                <th style='text-align:center;'>Trainings<br><small class='text-muted'>/{$bench->min_trainings_per_week}/wk</small></th>
                <th style='text-align:center;'>Meetings<br><small class='text-muted'>/{$bench->min_meetings_per_group_per_week}/grp/wk</small></th>
                <th style='text-align:center;'>Members<br><small class='text-muted'>/{$bench->min_members_per_group}/grp</small></th>
                <th style='text-align:center;'>AESA<br><small class='text-muted'>/{$bench->min_aesa_sessions_per_week}/wk</small></th>
                <th style='text-align:center;'>Attendance<br><small class='text-muted'>/{$bench->min_meeting_attendance_pct}%</small></th>
                <th style='text-align:center;'>Overall</th>
            </tr></thead><tbody>";

            foreach ($facilitatorIds as $fId) {
                $card = KpiService::facilitatorScorecard($fId);
                $fac  = \App\Models\User::find($fId);
                $name = $fac ? ($fac->name ?: $fac->first_name . ' ' . $fac->last_name) : "User #{$fId}";
                $a    = $card['actuals'];
                $s    = $card['scores'];
                $overall = $card['overall_score'];

                $overallColor = $overall >= 80 ? '#4caf50' : ($overall >= 50 ? '#ff9800' : '#f44336');

                $html .= "<tr>";
                $html .= "<td><strong>{$name}</strong></td>";
                $html .= self::scoreCell($a['total_groups'], $s['groups']);
                $html .= self::scoreCell($a['trainings_this_week'], $s['trainings']);
                $html .= self::scoreCell($a['meetings_per_group'], $s['meetings']);
                $html .= self::scoreCell($a['avg_members_per_group'], $s['members']);
                $html .= self::scoreCell($a['aesa_this_week'], $s['aesa']);
                $html .= self::scoreCell($a['attendance_pct'] . '%', $s['attendance']);
                $html .= "<td style='text-align:center;font-weight:bold;color:{$overallColor};font-size:16px;'>{$overall}%</td>";
                $html .= "</tr>";
            }

            $html .= "</tbody></table></div>";
            $col->append($html);
        });

        // ── IP scorecards (super admin only) ─────────────────
        if ($isSuperAdmin) {
            $row->column(12, function (Column $col) {
                $ips = ImplementingPartner::active()->get();
                $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
                $html .= "<h4 style='margin:0 0 12px;'><i class='fa fa-building'></i> IP Performance Summary This Week</h4>";

                if ($ips->isEmpty()) {
                    $html .= "<p class='text-muted'>No active IPs.</p></div>";
                    $col->append($html);
                    return;
                }

                $html .= "<table class='table table-bordered table-striped' style='margin:0;'>";
                $html .= "<thead><tr style='background:#f5f5f5;'>
                    <th>IP</th>
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

                    $html .= "<tr>";
                    $html .= "<td><strong>{$ip->name}</strong><br><small class='text-muted'>{$ip->short_name}</small></td>";
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
        }

        // ══════════════════════════════════════════════════════
        // CHARTS
        // ══════════════════════════════════════════════════════

        // ── Chart 1: Facilitator overall scores bar chart ────
        $row->column(6, function (Column $col) use ($facilitatorIds) {
            $names = [];
            $scores = [];
            $colors = [];
            foreach ($facilitatorIds as $fId) {
                $card = KpiService::facilitatorScorecard($fId);
                $fac  = \App\Models\User::find($fId);
                $name = $fac ? ($fac->first_name ?: 'User') : "#{$fId}";
                $names[] = $name;
                $scores[] = $card['overall_score'];
                $colors[] = $card['overall_score'] >= 80 ? '#4caf50' : ($card['overall_score'] >= 50 ? '#ff9800' : '#f44336');
            }
            $namesJson  = json_encode($names);
            $scoresJson = json_encode($scores);
            $colorsJson = json_encode($colors);

            $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>
                <h4 style='margin:0 0 12px;'><i class='fa fa-bar-chart'></i> Facilitator Overall Scores</h4>
                <canvas id='facilScoresChart' height='260'></canvas>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function(){
                var ctx = document.getElementById('facilScoresChart');
                if(!ctx) return;
                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: {$namesJson},
                        datasets: [{
                            label: 'Overall Score %',
                            data: {$scoresJson},
                            backgroundColor: {$colorsJson},
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: { yAxes: [{ ticks: { beginAtZero: true, max: 100 } }] },
                        legend: { display: false },
                        annotation: {
                            annotations: [{
                                type: 'line', mode: 'horizontal',
                                scaleID: 'y-axis-0', value: 80,
                                borderColor: '#4caf50', borderWidth: 2, borderDash: [5,5],
                                label: { content: '80% target', enabled: true, position: 'right' }
                            }]
                        }
                    }
                });
            });
            </script>";
            $col->append($html);
        });

        // ── Chart 2: KPI distribution radar (avg across all facilitators) ──
        $row->column(6, function (Column $col) use ($facilitatorIds) {
            $kpiLabels = ['Groups', 'Trainings', 'Meetings', 'Members', 'AESA', 'Attendance'];
            $avgScores = ['groups' => 0, 'trainings' => 0, 'meetings' => 0, 'members' => 0, 'aesa' => 0, 'attendance' => 0];
            $count = $facilitatorIds->count();
            foreach ($facilitatorIds as $fId) {
                $card = KpiService::facilitatorScorecard($fId);
                foreach ($avgScores as $key => &$val) {
                    $val += $card['scores'][$key] ?? 0;
                }
            }
            unset($val);
            if ($count > 0) {
                foreach ($avgScores as &$val) {
                    $val = round($val / $count, 1);
                }
                unset($val);
            }
            $labelsJson = json_encode($kpiLabels);
            $dataJson   = json_encode(array_values($avgScores));

            $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>
                <h4 style='margin:0 0 12px;'><i class='fa fa-bullseye'></i> Avg KPI Performance (All Facilitators)</h4>
                <canvas id='kpiRadarChart' height='260'></canvas>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function(){
                var ctx = document.getElementById('kpiRadarChart');
                if(!ctx) return;
                new Chart(ctx.getContext('2d'), {
                    type: 'radar',
                    data: {
                        labels: {$labelsJson},
                        datasets: [{
                            label: 'Avg Score %',
                            data: {$dataJson},
                            backgroundColor: 'rgba(33,150,243,0.2)',
                            borderColor: '#2196F3',
                            pointBackgroundColor: '#2196F3',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        scale: { ticks: { beginAtZero: true, max: 100 } }
                    }
                });
            });
            </script>";
            $col->append($html);
        });

        // ── Chart 3: Score distribution pie ──────────────────
        $row->column(6, function (Column $col) use ($facilitatorIds) {
            $excellent = 0; $good = 0; $needs = 0; $below = 0;
            foreach ($facilitatorIds as $fId) {
                $card = KpiService::facilitatorScorecard($fId);
                $s = $card['overall_score'];
                if ($s >= 80) $excellent++;
                elseif ($s >= 60) $good++;
                elseif ($s >= 40) $needs++;
                else $below++;
            }
            $dataJson = json_encode([$excellent, $good, $needs, $below]);

            $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>
                <h4 style='margin:0 0 12px;'><i class='fa fa-pie-chart'></i> Performance Distribution</h4>
                <canvas id='perfPieChart' height='260'></canvas>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function(){
                var ctx = document.getElementById('perfPieChart');
                if(!ctx) return;
                new Chart(ctx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Excellent (≥80%)', 'Good (60-79%)', 'Needs Improvement (40-59%)', 'Below Target (<40%)'],
                        datasets: [{
                            data: {$dataJson},
                            backgroundColor: ['#4caf50','#ff9800','#ffc107','#f44336'],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: { responsive: true }
                });
            });
            </script>";
            $col->append($html);
        });

        // ── Chart 4: Actuals vs Targets grouped bar (aggregated) ──
        $row->column(6, function (Column $col) use ($facilitatorIds, $bench) {
            $totalActuals = ['groups' => 0, 'trainings' => 0, 'meetings' => 0, 'members' => 0, 'aesa' => 0];
            $count = $facilitatorIds->count();
            foreach ($facilitatorIds as $fId) {
                $card = KpiService::facilitatorScorecard($fId);
                $a = $card['actuals'];
                $totalActuals['groups']    += $a['total_groups'];
                $totalActuals['trainings'] += $a['trainings_this_week'];
                $totalActuals['meetings']  += $a['meetings_this_week'];
                $totalActuals['members']   += $a['total_members'];
                $totalActuals['aesa']      += $a['aesa_this_week'];
            }
            // Per-facilitator average
            if ($count > 0) {
                $avgActuals = [
                    round($totalActuals['groups'] / $count, 1),
                    round($totalActuals['trainings'] / $count, 1),
                    round($totalActuals['meetings'] / $count, 1),
                    round($totalActuals['members'] / $count, 1),
                    round($totalActuals['aesa'] / $count, 1),
                ];
            } else {
                $avgActuals = [0,0,0,0,0];
            }
            $targets = [
                $bench->min_groups_per_facilitator,
                $bench->min_trainings_per_week,
                $bench->min_meetings_per_group_per_week,
                $bench->min_members_per_group,
                $bench->min_aesa_sessions_per_week,
            ];
            $actualsJson = json_encode($avgActuals);
            $targetsJson = json_encode($targets);

            $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>
                <h4 style='margin:0 0 12px;'><i class='fa fa-balance-scale'></i> Avg Actuals vs Targets (Per Facilitator)</h4>
                <canvas id='actualsVsTargetsChart' height='260'></canvas>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function(){
                var ctx = document.getElementById('actualsVsTargetsChart');
                if(!ctx) return;
                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Groups','Trainings/wk','Meetings/wk','Members/grp','AESA/wk'],
                        datasets: [
                            { label: 'Avg Actual', data: {$actualsJson}, backgroundColor: '#2196F3' },
                            { label: 'Target', data: {$targetsJson}, backgroundColor: '#e0e0e0' }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: { yAxes: [{ ticks: { beginAtZero: true } }] }
                    }
                });
            });
            </script>";
            $col->append($html);
        });
    }

    /**
     * Helper: render a table cell with color-coded score.
     */
    private static function scoreCell(string $value, float $score): string
    {
        $color = $score >= 80 ? '#4caf50' : ($score >= 50 ? '#ff9800' : '#f44336');
        $bg    = $score >= 80 ? '#e8f5e9' : ($score >= 50 ? '#fff3e0' : '#ffebee');
        return "<td style='text-align:center;background:{$bg};'>
            <div style='font-weight:600;'>{$value}</div>
            <small style='color:{$color};font-weight:600;'>{$score}%</small>
        </td>";
    }

    // ─── Grid (fallback list — only 1 record) ────────────
    protected function grid()
    {
        $grid = new Grid(new KpiBenchmark());
        $grid->disableCreateButton();
        $grid->disableBatchActions();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
        });

        $grid->column('id', 'ID');
        $grid->column('min_groups_per_facilitator', 'Min Groups');
        $grid->column('min_trainings_per_week', 'Trainings/Week');
        $grid->column('min_meetings_per_group_per_week', 'Meetings/Group/Week');
        $grid->column('min_members_per_group', 'Members/Group');
        $grid->column('min_aesa_sessions_per_week', 'AESA/Week');
        $grid->column('min_meeting_attendance_pct', 'Attendance %');
        $grid->column('updated_at', 'Last Updated');

        return $grid;
    }

    // ─── Form ────────────────────────────────────────────
    protected function form()
    {
        $form = new Form(new KpiBenchmark());

        $form->display('id', 'ID');

        $form->divider('Facilitator KPI Targets');

        $form->number('min_groups_per_facilitator', 'Min Groups per Facilitator')
            ->default(3)->min(1)->max(50)
            ->help('Minimum number of active groups each facilitator should manage');

        $form->number('min_trainings_per_week', 'Min Trainings per Week')
            ->default(2)->min(0)->max(20)
            ->help('Minimum training sessions a facilitator should conduct per week');

        $form->number('min_meetings_per_group_per_week', 'Min Meetings per Group per Week')
            ->default(1)->min(0)->max(7)
            ->help('Minimum VSLA meetings submitted per group per week');

        $form->number('min_members_per_group', 'Min Members per Group')
            ->default(30)->min(5)->max(100)
            ->help('Minimum registered members each group should have');

        $form->number('min_aesa_sessions_per_week', 'Min AESA Sessions per Week')
            ->default(1)->min(0)->max(10)
            ->help('Minimum AESA observation sessions per facilitator per week');

        $form->decimal('min_meeting_attendance_pct', 'Min Meeting Attendance %')
            ->default(75)->help('Target meeting attendance percentage (0-100)');

        $form->hidden('updated_by_id')->default(Admin::user()->id ?? null);

        $form->saving(function (Form $form) {
            $form->updated_by_id = Admin::user()->id ?? null;
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->disableCreatingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
            $tools->disableList();
        });

        return $form;
    }

    // ─── Detail view (not really needed) ─────────────────
    protected function detail($id)
    {
        $show = new Show(KpiBenchmark::findOrFail($id));
        $show->field('min_groups_per_facilitator', 'Min Groups per Facilitator');
        $show->field('min_trainings_per_week', 'Min Trainings per Week');
        $show->field('min_meetings_per_group_per_week', 'Min Meetings/Group/Week');
        $show->field('min_members_per_group', 'Min Members per Group');
        $show->field('min_aesa_sessions_per_week', 'Min AESA/Week');
        $show->field('min_meeting_attendance_pct', 'Min Attendance %');
        $show->field('updated_at', 'Last Updated');
        return $show;
    }
}
