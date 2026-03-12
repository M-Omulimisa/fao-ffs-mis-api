<?php

namespace App\Admin\Controllers;

use App\Models\KpiBenchmark;
use App\Models\FfsGroup;
use App\Models\ImplementingPartner;
use App\Models\User;
use App\Services\KpiService;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

class KpiStatsController extends AdminController
{
    protected $title = 'KPI Stats';

    public function index(Content $content)
    {
        // Chart configs in this controller use the Chart.js v2 API.
        Admin::js('https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js');

        return $content
            ->title('KPI Stats')
            ->description('Visual charts & analytics')
            ->row(function (Row $row) {
                $this->renderCharts($row);
            });
    }

    private function renderCharts(Row $row)
    {
        $bench = KpiBenchmark::current();
        $user  = Admin::user();
        $isSuperAdmin = $user && $user->isRole('super_admin');
        $ipId  = $isSuperAdmin ? null : ($user->ip_id ?? null);

        $facilitatorIds = FfsGroup::where('status', 'Active')
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->whereNotNull('facilitator_id')
            ->distinct()
            ->pluck('facilitator_id');

        // ── Chart 1: Facilitator overall scores bar chart ────
        $row->column(6, function (Column $col) use ($facilitatorIds) {
            $names = []; $scores = []; $colors = [];
            foreach ($facilitatorIds as $fId) {
                $card = KpiService::facilitatorScorecard($fId);
                $fac  = User::find($fId);
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
            (function(){
                function ensureChartJs(cb){if(typeof Chart!=='undefined'){cb();return;}var s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';s.onload=cb;document.head.appendChild(s);}
                function run(){ensureChartJs(function(){
                    var ctx=document.getElementById('facilScoresChart');
                    if(!ctx)return;
                    var ex=Chart.getChart?Chart.getChart(ctx):null;if(ex)ex.destroy();
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
                            legend: { display: false }
                        }
                    });
                });}
                if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',run);}else{run();}
                document.addEventListener('pjax:complete',run);
            })();
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
            (function(){
                function ensureChartJs(cb){if(typeof Chart!=='undefined'){cb();return;}var s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';s.onload=cb;document.head.appendChild(s);}
                function run(){ensureChartJs(function(){
                    var ctx=document.getElementById('kpiRadarChart');
                    if(!ctx)return;
                    var ex=Chart.getChart?Chart.getChart(ctx):null;if(ex)ex.destroy();
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
                });}
                if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',run);}else{run();}
                document.addEventListener('pjax:complete',run);
            })();
            </script>";
            $col->append($html);
        });

        // ── Chart 3: Score distribution doughnut ─────────────
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
            (function(){
                function ensureChartJs(cb){if(typeof Chart!=='undefined'){cb();return;}var s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';s.onload=cb;document.head.appendChild(s);}
                function run(){ensureChartJs(function(){
                    var ctx=document.getElementById('perfPieChart');
                    if(!ctx)return;
                    var ex=Chart.getChart?Chart.getChart(ctx):null;if(ex)ex.destroy();
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
                });}
                if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',run);}else{run();}
                document.addEventListener('pjax:complete',run);
            })();
            </script>";
            $col->append($html);
        });

        // ── Chart 4: Actuals vs Targets grouped bar ──────────
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
            (function(){
                function ensureChartJs(cb){if(typeof Chart!=='undefined'){cb();return;}var s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';s.onload=cb;document.head.appendChild(s);}
                function run(){ensureChartJs(function(){
                    var ctx=document.getElementById('actualsVsTargetsChart');
                    if(!ctx)return;
                    var ex=Chart.getChart?Chart.getChart(ctx):null;if(ex)ex.destroy();
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
                });}
                if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',run);}else{run();}
                document.addEventListener('pjax:complete',run);
            })();
            </script>";
            $col->append($html);
        });

        // ── Chart 5: IP comparison bar (super admin only) ────
        if ($isSuperAdmin) {
            $row->column(12, function (Column $col) {
                $ips = ImplementingPartner::active()->get();
                $ipNames = []; $ipScores = []; $ipColors = [];
                foreach ($ips as $ip) {
                    $card = KpiService::ipScorecard($ip->id);
                    $ipNames[] = $ip->short_name ?: $ip->name;
                    $ipScores[] = $card['overall_score'];
                    $ipColors[] = $card['overall_score'] >= 80 ? '#4caf50' : ($card['overall_score'] >= 50 ? '#ff9800' : '#f44336');
                }
                $namesJson  = json_encode($ipNames);
                $scoresJson = json_encode($ipScores);
                $colorsJson = json_encode($ipColors);

                $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>
                    <h4 style='margin:0 0 12px;'><i class='fa fa-building'></i> IP Overall Scores Comparison</h4>
                    <canvas id='ipComparisonChart' height='200'></canvas>
                </div>
                <script>
                (function(){
                    function ensureChartJs(cb){if(typeof Chart!=='undefined'){cb();return;}var s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';s.onload=cb;document.head.appendChild(s);}
                    function run(){ensureChartJs(function(){
                        var ctx=document.getElementById('ipComparisonChart');
                        if(!ctx)return;
                        var ex=Chart.getChart?Chart.getChart(ctx):null;if(ex)ex.destroy();
                        new Chart(ctx.getContext('2d'), {
                            type: 'horizontalBar',
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
                                scales: { xAxes: [{ ticks: { beginAtZero: true, max: 100 } }] },
                                legend: { display: false }
                            }
                        });
                    });}
                    if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',run);}else{run();}
                    document.addEventListener('pjax:complete',run);
                })();
                </script>";
                $col->append($html);
            });
        }
    }
}
