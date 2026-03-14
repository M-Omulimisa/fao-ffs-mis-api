<?php

namespace App\Admin\Controllers;

use App\Models\KpiBenchmark;
use App\Models\FfsGroup;
use App\Models\ImplementingPartner;
use App\Services\KpiService;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

/**
 * KpiStatsController — visual charts & analytics for KPI data.
 *
 * Uses Chart.js v4 loaded on demand.
 * Computes facilitator scorecards only once and passes shared data to all charts.
 *
 * Access tiers:
 *   Super Admin  → all facilitators + IP comparison chart
 *   IP Manager   → only their IP's facilitators
 *   Facilitator  → only their own scorecard
 */
class KpiStatsController extends AdminController
{
    use IpScopeable;

    protected $title = 'KPI Charts';

    public function index(Content $content)
    {
        return $content
            ->title('KPI Charts & Analytics')
            ->description('Visual performance analytics')
            ->row(function (Row $row) {
                $this->renderCharts($row);
            });
    }

    private function renderCharts(Row $row)
    {
        $bench        = KpiBenchmark::current();
        $isSuperAdmin = $this->isSuperAdmin();
        $currentAdmin = Admin::user();
        $isFacilitator = !$isSuperAdmin && $currentAdmin
            && $this->userHasRoleSlug($currentAdmin, 'facilitator');
        $ipId = $this->getAdminIpId();

        // ── Scope facilitator IDs ──────────────────────────────────────
        $query = FfsGroup::where('status', 'Active')->whereNotNull('facilitator_id');
        if ($isFacilitator) {
            $query->where('facilitator_id', $currentAdmin->id);
        } elseif ($ipId) {
            $query->where('ip_id', $ipId);
        }
        $facilitatorIds = $query->distinct()->pluck('facilitator_id');

        // ── Compute scorecards ONCE ────────────────────────────────────
        $scorecards = [];
        foreach ($facilitatorIds as $fId) {
            $scorecards[$fId] = KpiService::facilitatorScorecard($fId);
        }

        // ── Build per-chart data arrays from shared scorecards ─────────
        $names   = []; $scores  = []; $colors  = [];
        $avgKpi  = ['groups' => 0, 'trainings' => 0, 'meetings' => 0, 'members' => 0, 'aesa' => 0, 'attendance' => 0];
        $excellent = 0; $good = 0; $needs = 0; $below = 0;
        $totActuals = ['groups' => 0, 'trainings' => 0, 'meetings' => 0, 'members' => 0, 'aesa' => 0];
        $count = count($scorecards);

        foreach ($scorecards as $fId => $card) {
            $name      = $card['facilitator_name'] ?? "#{$fId}";
            $firstName = explode(' ', trim($name))[0];
            $names[]   = $firstName;
            $scores[]  = $card['overall_score'];
            $colors[]  = $card['overall_score'] >= 80 ? '#4caf50' : ($card['overall_score'] >= 50 ? '#ff9800' : '#f44336');

            foreach ($avgKpi as $k => &$v) {
                $v += $card['scores'][$k] ?? 0;
            }
            unset($v);

            $s = $card['overall_score'];
            if ($s >= 80)      $excellent++;
            elseif ($s >= 60)  $good++;
            elseif ($s >= 40)  $needs++;
            else               $below++;

            $a = $card['actuals'];
            $totActuals['groups']    += $a['total_groups'];
            $totActuals['trainings'] += $a['trainings_this_week'];
            $totActuals['meetings']  += $a['meetings_this_week'];
            $totActuals['members']   += $a['total_members'];
            $totActuals['aesa']      += $a['aesa_this_week'];
        }

        if ($count > 0) {
            foreach ($avgKpi as &$v) { $v = round($v / $count, 1); }
            unset($v);
            $avgActuals = [
                round($totActuals['groups']    / $count, 1),
                round($totActuals['trainings'] / $count, 1),
                round($totActuals['meetings']  / $count, 1),
                round($totActuals['members']   / $count, 1),
                round($totActuals['aesa']      / $count, 1),
            ];
        } else {
            $avgActuals = [0, 0, 0, 0, 0];
        }

        $targets = [
            $bench->min_groups_per_facilitator,
            $bench->min_trainings_per_week,
            $bench->min_meetings_per_group_per_week,
            $bench->min_members_per_group,
            $bench->min_aesa_sessions_per_week,
        ];

        // ── Chart 1: Facilitator overall scores bar ────────────────────
        $row->column(6, function (Column $col) use ($names, $scores, $colors) {
            $namesJson  = json_encode($names);
            $scoresJson = json_encode($scores);
            $colorsJson = json_encode($colors);

            $col->append(self::chartCard(
                'facilScoresChart',
                '<i class="fa fa-bar-chart"></i> Facilitator Overall Scores',
                "
                (function(){
                    ensureChart(function(){
                        var ctx=document.getElementById('facilScoresChart');
                        if(!ctx)return;
                        destroyChart(ctx);
                        new Chart(ctx,{
                            type:'bar',
                            data:{
                                labels:{$namesJson},
                                datasets:[{label:'Score %',data:{$scoresJson},backgroundColor:{$colorsJson},borderWidth:0}]
                            },
                            options:{
                                responsive:true,
                                plugins:{legend:{display:false}},
                                scales:{
                                    y:{beginAtZero:true,max:100,ticks:{callback:function(v){return v+'%';}}}
                                }
                            }
                        });
                    });
                })();"
            ));
        });

        // ── Chart 2: Radar — avg KPI performance ──────────────────────
        $row->column(6, function (Column $col) use ($avgKpi) {
            $labelsJson = json_encode(['Groups', 'Trainings', 'Meetings', 'Members', 'AESA', 'Attendance']);
            $dataJson   = json_encode(array_values($avgKpi));

            $col->append(self::chartCard(
                'kpiRadarChart',
                '<i class="fa fa-bullseye"></i> Avg KPI Performance (All Facilitators)',
                "
                (function(){
                    ensureChart(function(){
                        var ctx=document.getElementById('kpiRadarChart');
                        if(!ctx)return;
                        destroyChart(ctx);
                        new Chart(ctx,{
                            type:'radar',
                            data:{
                                labels:{$labelsJson},
                                datasets:[{
                                    label:'Avg Score %',
                                    data:{$dataJson},
                                    backgroundColor:'rgba(33,150,243,0.2)',
                                    borderColor:'#2196F3',
                                    pointBackgroundColor:'#2196F3',
                                    borderWidth:2
                                }]
                            },
                            options:{
                                responsive:true,
                                plugins:{legend:{display:false}},
                                scales:{r:{beginAtZero:true,max:100}}
                            }
                        });
                    });
                })();"
            ));
        });

        // ── Chart 3: Doughnut — performance distribution ───────────────
        $row->column(6, function (Column $col) use ($excellent, $good, $needs, $below) {
            $dataJson = json_encode([$excellent, $good, $needs, $below]);

            $col->append(self::chartCard(
                'perfPieChart',
                '<i class="fa fa-pie-chart"></i> Performance Distribution',
                "
                (function(){
                    ensureChart(function(){
                        var ctx=document.getElementById('perfPieChart');
                        if(!ctx)return;
                        destroyChart(ctx);
                        new Chart(ctx,{
                            type:'doughnut',
                            data:{
                                labels:['Excellent (≥80%)','Good (60-79%)','Fair (40-59%)','Below Target (<40%)'],
                                datasets:[{data:{$dataJson},backgroundColor:['#4caf50','#ff9800','#ffc107','#f44336'],borderWidth:2,borderColor:'#fff'}]
                            },
                            options:{responsive:true,plugins:{legend:{position:'bottom'}}}
                        });
                    });
                })();"
            ));
        });

        // ── Chart 4: Grouped bar — avg actuals vs targets ──────────────
        $row->column(6, function (Column $col) use ($avgActuals, $targets) {
            $actualsJson = json_encode($avgActuals);
            $targetsJson = json_encode($targets);

            $col->append(self::chartCard(
                'actualsVsTargetsChart',
                '<i class="fa fa-balance-scale"></i> Avg Actuals vs Targets (Per Facilitator)',
                "
                (function(){
                    ensureChart(function(){
                        var ctx=document.getElementById('actualsVsTargetsChart');
                        if(!ctx)return;
                        destroyChart(ctx);
                        new Chart(ctx,{
                            type:'bar',
                            data:{
                                labels:['Groups','Trainings/wk','Meetings/wk','Members/grp','AESA/wk'],
                                datasets:[
                                    {label:'Avg Actual',data:{$actualsJson},backgroundColor:'#2196F3'},
                                    {label:'Target',data:{$targetsJson},backgroundColor:'#e0e0e0'}
                                ]
                            },
                            options:{
                                responsive:true,
                                plugins:{legend:{position:'top'}},
                                scales:{y:{beginAtZero:true}}
                            }
                        });
                    });
                })();"
            ));
        });

        // ── Chart 5: IP comparison (super admin only) ──────────────────
        if ($isSuperAdmin) {
            $row->column(12, function (Column $col) {
                $ips     = ImplementingPartner::active()->get();
                $ipNames = []; $ipScores = []; $ipColors = [];
                foreach ($ips as $ip) {
                    $card      = KpiService::ipScorecard($ip->id);
                    $ipNames[] = $ip->short_name ?: $ip->name;
                    $ipScores[] = $card['overall_score'];
                    $ipColors[] = $card['overall_score'] >= 80 ? '#4caf50' : ($card['overall_score'] >= 50 ? '#ff9800' : '#f44336');
                }
                $namesJson  = json_encode($ipNames);
                $scoresJson = json_encode($ipScores);
                $colorsJson = json_encode($ipColors);

                $col->append(self::chartCard(
                    'ipComparisonChart',
                    '<i class="fa fa-building"></i> IP Overall Scores Comparison',
                    "
                    (function(){
                        ensureChart(function(){
                            var ctx=document.getElementById('ipComparisonChart');
                            if(!ctx)return;
                            destroyChart(ctx);
                            new Chart(ctx,{
                                type:'bar',
                                data:{
                                    labels:{$namesJson},
                                    datasets:[{label:'Overall Score %',data:{$scoresJson},backgroundColor:{$colorsJson},borderWidth:0}]
                                },
                                options:{
                                    indexAxis:'y',
                                    responsive:true,
                                    plugins:{legend:{display:false}},
                                    scales:{x:{beginAtZero:true,max:100,ticks:{callback:function(v){return v+'%';}}}}
                                }
                            });
                        });
                    })();",
                    200
                ));
            });
        }

        // ── Shared Chart.js loader (injected once) ─────────────────────
        $row->column(12, function (Column $col) {
            $col->append("<script>
            (function(){
                function ensureChart(cb){
                    if(typeof Chart!=='undefined'){cb();return;}
                    var s=document.createElement('script');
                    s.src='https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
                    s.onload=cb;
                    document.head.appendChild(s);
                }
                function destroyChart(ctx){
                    var ex=Chart.getChart?Chart.getChart(ctx):null;
                    if(ex)ex.destroy();
                }
                window.ensureChart=ensureChart;
                window.destroyChart=destroyChart;
            })();
            </script>");
        });
    }

    // ─── Helper: render a chart container card with inline script ────────

    private static function chartCard(
        string $canvasId,
        string $title,
        string $script,
        int    $height = 260
    ): string {
        return "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>
            <h4 style='margin:0 0 12px;'>{$title}</h4>
            <canvas id='{$canvasId}' height='{$height}'></canvas>
        </div>
        <script>
        (function(){
            function run(){{$script}}
            if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',run);}else{run();}
            document.addEventListener('pjax:complete',run);
        })();
        </script>";
    }
}
