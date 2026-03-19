<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AesaSession;
use App\Models\AesaObservation;
use App\Models\AesaCropObservation;
use App\Models\FfsGroup;
use App\Admin\Traits\IpScopeable;
use Carbon\Carbon;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;

/**
 * AESA Statistics & Analytics Dashboard
 *
 * Comprehensive analytics for Agro-Ecosystem Analysis sessions:
 * - KPI summary cards
 * - Session activity trends
 * - Animal health distribution
 * - Risk assessment overview
 * - Disease & symptom prevalence
 * - Parasite burden analysis
 * - Ecosystem conditions radar
 * - Geographic coverage
 * - Top performing groups
 */
class AesaStatsController extends Controller
{
    use IpScopeable;

    public function index(Content $content)
    {
        Admin::js('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js');

        Admin::style($this->getStyles());

        $ipId = $this->getAdminIpId();

        return $content
            ->title('🌿 AESA Analytics Dashboard')
            ->description('Agro-Ecosystem Analysis — Animal Health & Crop Field Observation Insights')
            ->row(function (Row $row) use ($ipId) {
                $this->addKPICards($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addSessionTrendChart($row, $ipId);
                $this->addAnimalTypeChart($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addHealthStatusChart($row, $ipId);
                $this->addRiskLevelChart($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addSymptomPrevalenceChart($row, $ipId);
                $this->addParasiteBurdenChart($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addEcosystemConditionsChart($row, $ipId);
                $this->addBodyConditionTrendChart($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addCropTypeChart($row, $ipId);
                $this->addCropVigorChart($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addCropPestPrevalenceChart($row, $ipId);
                $this->addCropDiseasePrevalenceChart($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addTopGroupsTable($row, $ipId);
                $this->addGeographicCoverageTable($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addCommonProblemsChart($row, $ipId);
                $this->addActionsTakenChart($row, $ipId);
            })
            ->row(function (Row $row) use ($ipId) {
                $this->addRecentSessionsTable($row, $ipId);
            });
    }

    // ═══════════════════════════════════════════════════════════
    // KPI CARDS
    // ═══════════════════════════════════════════════════════════

    private function addKPICards(Row $row, $ipId)
    {
        $totalSessions = AesaSession::when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
        $thisMonthSessions = AesaSession::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->whereMonth('observation_date', now()->month)
            ->whereYear('observation_date', now()->year)
            ->count();
        $lastMonthSessions = AesaSession::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->whereMonth('observation_date', now()->subMonth()->month)
            ->whereYear('observation_date', now()->subMonth()->year)
            ->count();

        $totalObs = AesaObservation::when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
        $avgObsPerSession = $totalSessions > 0 ? round($totalObs / $totalSessions, 1) : 0;

        $avgHealthScore = AesaObservation::when($ipId, fn($q) => $q->where('ip_id', $ipId))->count() > 0
            ? $this->computeAverageHealthScore($ipId)
            : 0;

        $healthyCount = AesaObservation::where('animal_health_status', 'Healthy')
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
        $sickCount = AesaObservation::where('animal_health_status', 'Sick')
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
        $healthyPct = $totalObs > 0 ? round(($healthyCount / $totalObs) * 100, 1) : 0;

        $highRisk = AesaObservation::whereIn('risk_level', ['High', 'Critical'])
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
        $highRiskPct = $totalObs > 0 ? round(($highRisk / $totalObs) * 100, 1) : 0;

        $draftSessions = AesaSession::where('status', 'draft')
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
        $submittedSessions = AesaSession::where('status', 'submitted')
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
        $reviewedSessions = AesaSession::where('status', 'reviewed')
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();

        $uniqueGroups = AesaSession::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->whereNotNull('group_id')->distinct('group_id')->count('group_id');

        // Crop KPI data
        $totalCropObs   = AesaCropObservation::when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
        $avgCropScore   = $totalCropObs > 0 ? $this->computeAverageCropHealthScore($ipId) : 0;
        $cropHighRisk   = AesaCropObservation::where('risk_level', 'High')
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
        $cropHighRiskPct = $totalCropObs > 0 ? round(($cropHighRisk / $totalCropObs) * 100, 1) : 0;
        $cropGoodVigor  = AesaCropObservation::whereIn('crop_vigor', ['Good', 'Excellent'])
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
        $cropGoodVigorPct = $totalCropObs > 0 ? round(($cropGoodVigor / $totalCropObs) * 100, 1) : 0;

        $cropScoreColor = '#f44336'; $cropScoreLabel = 'Critical';
        if ($avgCropScore >= 75) { $cropScoreColor = '#4caf50'; $cropScoreLabel = 'Good'; }
        elseif ($avgCropScore >= 50) { $cropScoreColor = '#ff9800'; $cropScoreLabel = 'Fair'; }
        elseif ($avgCropScore >= 30) { $cropScoreColor = '#ff5722'; $cropScoreLabel = 'Poor'; }

        $sessionTrend = $lastMonthSessions > 0
            ? round((($thisMonthSessions - $lastMonthSessions) / $lastMonthSessions) * 100)
            : ($thisMonthSessions > 0 ? 100 : 0);
        $trendIcon = $sessionTrend >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
        $trendColor = $sessionTrend >= 0 ? '#4caf50' : '#f44336';
        $trendSign = $sessionTrend >= 0 ? '+' : '';

        $scoreColor = '#f44336';
        $scoreLabel = 'Critical';
        if ($avgHealthScore >= 80) { $scoreColor = '#4caf50'; $scoreLabel = 'Good'; }
        elseif ($avgHealthScore >= 60) { $scoreColor = '#ff9800'; $scoreLabel = 'Fair'; }
        elseif ($avgHealthScore >= 40) { $scoreColor = '#ff5722'; $scoreLabel = 'Poor'; }

        // Row 1: Main KPIs
        $row->column(3, function (Column $column) use ($totalSessions, $thisMonthSessions, $sessionTrend, $trendIcon, $trendColor, $trendSign) {
            $content = "
                <div class='aesa-kpi'>
                    <div class='aesa-kpi-icon' style='background:#05179F;'><i class='fa fa-clipboard-list'></i></div>
                    <div class='aesa-kpi-body'>
                        <div class='aesa-kpi-number'>{$totalSessions}</div>
                        <div class='aesa-kpi-label'>Total Sessions</div>
                        <div class='aesa-kpi-detail'>
                            <span style='color:{$trendColor};'><i class='fa {$trendIcon}'></i> {$trendSign}{$sessionTrend}%</span>
                            &nbsp;{$thisMonthSessions} this month
                        </div>
                    </div>
                </div>";
            $column->append(new Box('', $content));
        });

        $row->column(3, function (Column $column) use ($totalObs, $avgObsPerSession) {
            $content = "
                <div class='aesa-kpi'>
                    <div class='aesa-kpi-icon' style='background:#2196f3;'><i class='fa fa-eye'></i></div>
                    <div class='aesa-kpi-body'>
                        <div class='aesa-kpi-number'>{$totalObs}</div>
                        <div class='aesa-kpi-label'>Animal Observations</div>
                        <div class='aesa-kpi-detail'>Avg. {$avgObsPerSession} per session</div>
                    </div>
                </div>";
            $column->append(new Box('', $content));
        });

        $row->column(3, function (Column $column) use ($avgHealthScore, $scoreColor, $scoreLabel) {
            $content = "
                <div class='aesa-kpi'>
                    <div class='aesa-kpi-icon' style='background:{$scoreColor};'><i class='fa fa-heartbeat'></i></div>
                    <div class='aesa-kpi-body'>
                        <div class='aesa-kpi-number' style='color:{$scoreColor};'>{$avgHealthScore}</div>
                        <div class='aesa-kpi-label'>Avg. Health Score</div>
                        <div class='aesa-kpi-detail'>Overall: <strong style='color:{$scoreColor};'>{$scoreLabel}</strong></div>
                    </div>
                </div>";
            $column->append(new Box('', $content));
        });

        $row->column(3, function (Column $column) use ($highRisk, $highRiskPct, $sickCount) {
            $alertColor = $highRiskPct > 25 ? '#f44336' : ($highRiskPct > 10 ? '#ff9800' : '#4caf50');
            $content = "
                <div class='aesa-kpi'>
                    <div class='aesa-kpi-icon' style='background:{$alertColor};'><i class='fa fa-exclamation-triangle'></i></div>
                    <div class='aesa-kpi-body'>
                        <div class='aesa-kpi-number' style='color:{$alertColor};'>{$highRisk}</div>
                        <div class='aesa-kpi-label'>High/Critical Risk</div>
                        <div class='aesa-kpi-detail'>{$highRiskPct}% of animals | {$sickCount} sick</div>
                    </div>
                </div>";
            $column->append(new Box('', $content));
        });

        // Row 2: Secondary KPIs
        $row->column(3, function (Column $column) use ($healthyPct, $healthyCount) {
            $content = "
                <div class='aesa-kpi-mini'>
                    <i class='fa fa-check-circle' style='color:#4caf50;'></i>
                    <span class='aesa-kpi-mini-number'>{$healthyPct}%</span>
                    <span class='aesa-kpi-mini-label'>Healthy ({$healthyCount})</span>
                </div>";
            $column->append(new Box('', $content));
        });

        $row->column(3, function (Column $column) use ($uniqueGroups) {
            $content = "
                <div class='aesa-kpi-mini'>
                    <i class='fa fa-users' style='color:#05179F;'></i>
                    <span class='aesa-kpi-mini-number'>{$uniqueGroups}</span>
                    <span class='aesa-kpi-mini-label'>Groups Assessed</span>
                </div>";
            $column->append(new Box('', $content));
        });

        $row->column(3, function (Column $column) use ($submittedSessions, $reviewedSessions) {
            $content = "
                <div class='aesa-kpi-mini'>
                    <i class='fa fa-paper-plane' style='color:#4caf50;'></i>
                    <span class='aesa-kpi-mini-number'>{$submittedSessions}</span>
                    <span class='aesa-kpi-mini-label'>Submitted | {$reviewedSessions} Reviewed</span>
                </div>";
            $column->append(new Box('', $content));
        });

        $row->column(3, function (Column $column) use ($draftSessions) {
            $content = "
                <div class='aesa-kpi-mini'>
                    <i class='fa fa-edit' style='color:#ff9800;'></i>
                    <span class='aesa-kpi-mini-number'>{$draftSessions}</span>
                    <span class='aesa-kpi-mini-label'>Drafts Pending</span>
                </div>";
            $column->append(new Box('', $content));
        });

        // Row 3: Crop KPI mini-cards
        $row->column(3, function (Column $column) use ($totalCropObs) {
            $color = $totalCropObs > 0 ? '#388e3c' : '#999';
            $content = "
                <div class='aesa-kpi-mini'>
                    <i class='fa fa-leaf' style='color:{$color};'></i>
                    <span class='aesa-kpi-mini-number'>{$totalCropObs}</span>
                    <span class='aesa-kpi-mini-label'>Crop Observations</span>
                </div>";
            $column->append(new Box('', $content));
        });

        $row->column(3, function (Column $column) use ($avgCropScore, $cropScoreColor, $cropScoreLabel) {
            $content = "
                <div class='aesa-kpi-mini'>
                    <i class='fa fa-seedling' style='color:{$cropScoreColor};'></i>
                    <span class='aesa-kpi-mini-number' style='color:{$cropScoreColor};'>{$avgCropScore}</span>
                    <span class='aesa-kpi-mini-label'>Avg. Crop Score ({$cropScoreLabel})</span>
                </div>";
            $column->append(new Box('', $content));
        });

        $row->column(3, function (Column $column) use ($cropGoodVigorPct, $cropGoodVigor) {
            $content = "
                <div class='aesa-kpi-mini'>
                    <i class='fa fa-check-circle' style='color:#4caf50;'></i>
                    <span class='aesa-kpi-mini-number'>{$cropGoodVigorPct}%</span>
                    <span class='aesa-kpi-mini-label'>Good/Excellent Vigor ({$cropGoodVigor})</span>
                </div>";
            $column->append(new Box('', $content));
        });

        $row->column(3, function (Column $column) use ($cropHighRisk, $cropHighRiskPct) {
            $alertColor = $cropHighRiskPct > 25 ? '#f44336' : ($cropHighRiskPct > 10 ? '#ff9800' : '#4caf50');
            $content = "
                <div class='aesa-kpi-mini'>
                    <i class='fa fa-exclamation-circle' style='color:{$alertColor};'></i>
                    <span class='aesa-kpi-mini-number' style='color:{$alertColor};'>{$cropHighRisk}</span>
                    <span class='aesa-kpi-mini-label'>High-Risk Crops ({$cropHighRiskPct}%)</span>
                </div>";
            $column->append(new Box('', $content));
        });
    }

    // ═══════════════════════════════════════════════════════════
    // CHARTS
    // ═══════════════════════════════════════════════════════════

    private function addSessionTrendChart(Row $row, $ipId)
    {
        $row->column(8, function (Column $column) use ($ipId) {
            $months = [];
            $sessionData = [];
            $obsData = [];
            $cropData = [];

            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                $sessionData[] = AesaSession::whereYear('observation_date', $date->year)
                    ->whereMonth('observation_date', $date->month)
                    ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                    ->count();
                $obsData[] = AesaObservation::whereHas('session', function ($q) use ($date, $ipId) {
                    $q->whereYear('observation_date', $date->year)
                      ->whereMonth('observation_date', $date->month);
                    if ($ipId) $q->where('ip_id', $ipId);
                })->count();
                $cropData[] = AesaCropObservation::whereHas('session', function ($q) use ($date, $ipId) {
                    $q->whereYear('observation_date', $date->year)
                      ->whereMonth('observation_date', $date->month);
                    if ($ipId) $q->where('ip_id', $ipId);
                })->count();
            }

            $content = "
                <canvas id='sessionTrendChart' height='85'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('sessionTrendChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('sessionTrendChart').getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: " . json_encode($months) . ",
                                datasets: [{
                                    label: 'Sessions',
                                    data: " . json_encode($sessionData) . ",
                                    borderColor: '#05179F',
                                    backgroundColor: 'rgba(5,23,159,0.08)',
                                    borderWidth: 3, fill: true, tension: 0.4,
                                    pointRadius: 4, pointBackgroundColor: '#05179F'
                                }, {
                                    label: 'Animals Observed',
                                    data: " . json_encode($obsData) . ",
                                    borderColor: '#4caf50',
                                    backgroundColor: 'rgba(76,175,80,0.08)',
                                    borderWidth: 3, fill: true, tension: 0.4,
                                    pointRadius: 4, pointBackgroundColor: '#4caf50'
                                }, {
                                    label: 'Crop Plots Observed',
                                    data: " . json_encode($cropData) . ",
                                    borderColor: '#ff9800',
                                    backgroundColor: 'rgba(255,152,0,0.08)',
                                    borderWidth: 3, fill: true, tension: 0.4,
                                    pointRadius: 4, pointBackgroundColor: '#ff9800'
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true,
                                plugins: {
                                    legend: { position: 'top', labels: { usePointStyle: true, font: { size: 12, weight: 'bold' } } }
                                },
                                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('📈 Session & Observation Trend (12 Months)', $content));
        });
    }

    private function addAnimalTypeChart(Row $row, $ipId)
    {
        $row->column(4, function (Column $column) use ($ipId) {
            $types = AesaObservation::select('animal_type', DB::raw('COUNT(*) as cnt'))
                ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->whereNotNull('animal_type')
                ->where('animal_type', '!=', '')
                ->groupBy('animal_type')
                ->orderByDesc('cnt')
                ->limit(8)
                ->get();

            $labels = $types->pluck('animal_type')->toArray();
            $counts = $types->pluck('cnt')->toArray();
            $colors = ['#05179F', '#4caf50', '#ff9800', '#f44336', '#9c27b0', '#00bcd4', '#795548', '#607d8b'];

            $content = "
                <canvas id='animalTypeChart' height='220'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('animalTypeChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('animalTypeChart').getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: " . json_encode($labels) . ",
                                datasets: [{ data: " . json_encode($counts) . ", backgroundColor: " . json_encode(array_slice($colors, 0, count($labels))) . ", borderWidth: 2, borderColor: '#fff' }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true,
                                plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 8, usePointStyle: true } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('🐾 Animal Types', $content));
        });
    }

    private function addHealthStatusChart(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            $statuses = ['Healthy', 'Sick', 'Under Treatment', 'Recovering'];
            $counts = [];
            foreach ($statuses as $s) {
                $counts[] = AesaObservation::where('animal_health_status', $s)
                    ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
            }
            $colors = ['#4caf50', '#f44336', '#ff9800', '#2196f3'];

            $content = "
                <canvas id='healthStatusChart' height='100'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('healthStatusChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('healthStatusChart').getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: " . json_encode($statuses) . ",
                                datasets: [{
                                    label: 'Animals',
                                    data: " . json_encode($counts) . ",
                                    backgroundColor: " . json_encode($colors) . ",
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true, indexAxis: 'y',
                                plugins: { legend: { display: false } },
                                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('🏥 Health Status Distribution', $content));
        });
    }

    private function addRiskLevelChart(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            $levels = ['Low', 'Medium', 'High', 'Critical'];
            $counts = [];
            foreach ($levels as $l) {
                $counts[] = AesaObservation::where('risk_level', $l)
                    ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
            }
            $colors = ['#4caf50', '#ff9800', '#f44336', '#b71c1c'];
            $total = array_sum($counts);
            $pcts = array_map(fn($c) => $total > 0 ? round(($c / $total) * 100, 1) : 0, $counts);

            // Progress bar style
            $bars = '';
            foreach ($levels as $i => $level) {
                $bars .= "
                    <div style='display:flex;align-items:center;margin-bottom:10px;'>
                        <div style='width:80px;font-weight:600;font-size:12px;'>{$level}</div>
                        <div style='flex:1;background:#f0f0f0;height:24px;margin:0 10px;position:relative;'>
                            <div style='background:{$colors[$i]};height:100%;width:{$pcts[$i]}%;transition:width 0.5s;'></div>
                        </div>
                        <div style='width:70px;text-align:right;font-weight:700;color:{$colors[$i]};font-size:13px;'>{$counts[$i]} ({$pcts[$i]}%)</div>
                    </div>";
            }

            $content = "<div style='padding:15px;'>{$bars}</div>";
            $column->append(new Box('⚠️ Risk Level Assessment', $content));
        });
    }

    private function addSymptomPrevalenceChart(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            $symptoms = [
                'wounds_injuries' => 'Wounds & Injuries',
                'skin_infection'  => 'Skin Infection',
                'swelling'        => 'Swelling',
                'coughing'        => 'Coughing',
                'diarrhea'        => 'Diarrhea',
            ];
            $labels = [];
            $counts = [];
            foreach ($symptoms as $col => $label) {
                $labels[] = $label;
                $counts[] = AesaObservation::where($col, true)
                    ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
            }

            $content = "
                <canvas id='symptomChart' height='120'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('symptomChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('symptomChart').getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: " . json_encode($labels) . ",
                                datasets: [{
                                    label: 'Animals Affected',
                                    data: " . json_encode($counts) . ",
                                    backgroundColor: ['#e53935', '#d81b60', '#8e24aa', '#5e35b1', '#3949ab'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true,
                                plugins: { legend: { display: false } },
                                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('🩺 Symptom Prevalence', $content));
        });
    }

    private function addParasiteBurdenChart(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            $parasites = ['ticks_level', 'fleas_level', 'lice_level', 'mites_level'];
            $paraLabels = ['Ticks', 'Fleas', 'Lice', 'Mites'];
            $severities = ['None', 'Low', 'Moderate', 'High', 'Severe'];
            $sevColors = ['#e0e0e0', '#a5d6a7', '#fff176', '#ffb74d', '#ef5350'];

            $datasets = [];
            foreach ($severities as $si => $sev) {
                $data = [];
                foreach ($parasites as $p) {
                    $data[] = AesaObservation::where($p, $sev)
                        ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
                }
                $datasets[] = [
                    'label' => $sev,
                    'data' => $data,
                    'backgroundColor' => $sevColors[$si],
                ];
            }

            $content = "
                <canvas id='parasiteChart' height='120'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('parasiteChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('parasiteChart').getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: " . json_encode($paraLabels) . ",
                                datasets: " . json_encode($datasets) . "
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true,
                                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } },
                                plugins: { legend: { position: 'top', labels: { usePointStyle: true, font: { size: 11 } } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('🪲 Parasite Burden Analysis', $content));
        });
    }

    private function addEcosystemConditionsChart(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            // Compute average condition scores (Good=3, Fair=2, Poor=1)
            $fields = [
                'feed_availability'  => 'Feed',
                'water_availability' => 'Water',
                'grazing_condition'  => 'Grazing',
                'housing_condition'  => 'Housing',
                'hygiene_condition'  => 'Hygiene',
            ];
            $scoreMap = ['Good' => 3, 'Adequate' => 3, 'Fair' => 2, 'Moderate' => 2, 'Limited' => 2, 'Poor' => 1, 'Scarce' => 1, 'Inadequate' => 1];

            $labels = [];
            $scores = [];
            foreach ($fields as $col => $label) {
                $labels[] = $label;
                $values = AesaObservation::when($ipId, fn($q) => $q->where('ip_id', $ipId))
                    ->whereNotNull($col)
                    ->where($col, '!=', '')
                    ->pluck($col);

                if ($values->isEmpty()) {
                    $scores[] = 0;
                    continue;
                }

                $total = 0;
                $count = 0;
                foreach ($values as $v) {
                    if (isset($scoreMap[$v])) {
                        $total += $scoreMap[$v];
                        $count++;
                    }
                }
                $scores[] = $count > 0 ? round(($total / $count) * (100 / 3), 1) : 0;
            }

            $content = "
                <canvas id='ecosystemChart' height='180'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('ecosystemChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('ecosystemChart').getContext('2d'), {
                            type: 'radar',
                            data: {
                                labels: " . json_encode($labels) . ",
                                datasets: [{
                                    label: 'Condition Score (%)',
                                    data: " . json_encode($scores) . ",
                                    borderColor: '#05179F',
                                    backgroundColor: 'rgba(5,23,159,0.15)',
                                    borderWidth: 3,
                                    pointBackgroundColor: '#05179F',
                                    pointRadius: 5
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true,
                                scales: { r: { beginAtZero: true, max: 100, ticks: { stepSize: 25, font: { size: 10 } } } },
                                plugins: { legend: { display: false } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('🌿 Ecosystem Conditions', $content));
        });
    }

    private function addBodyConditionTrendChart(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            $conditions = ['Good', 'Fair', 'Poor'];
            $colors = ['#4caf50', '#ff9800', '#f44336'];
            $months = [];
            $datasets = [];

            foreach ($conditions as $ci => $cond) {
                $datasets[$ci] = [
                    'label' => $cond,
                    'data' => [],
                    'borderColor' => $colors[$ci],
                    'backgroundColor' => 'transparent',
                    'borderWidth' => 3,
                    'tension' => 0.4,
                    'pointRadius' => 3,
                ];
            }

            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                foreach ($conditions as $ci => $cond) {
                    $datasets[$ci]['data'][] = AesaObservation::where('body_condition', $cond)
                        ->whereHas('session', function ($q) use ($date, $ipId) {
                            $q->whereYear('observation_date', $date->year)
                              ->whereMonth('observation_date', $date->month);
                            if ($ipId) $q->where('ip_id', $ipId);
                        })->count();
                }
            }

            $content = "
                <canvas id='bodyConditionChart' height='180'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('bodyConditionChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('bodyConditionChart').getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: " . json_encode($months) . ",
                                datasets: " . json_encode(array_values($datasets)) . "
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true,
                                plugins: { legend: { position: 'top', labels: { usePointStyle: true, font: { size: 11 } } } },
                                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('📊 Body Condition Trend (6 Months)', $content));
        });
    }

    private function addCommonProblemsChart(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            $problems = AesaObservation::select('main_problem', DB::raw('COUNT(*) as cnt'))
                ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->whereNotNull('main_problem')
                ->where('main_problem', '!=', '')
                ->groupBy('main_problem')
                ->orderByDesc('cnt')
                ->limit(8)
                ->get();

            $labels = $problems->pluck('main_problem')->toArray();
            $counts = $problems->pluck('cnt')->toArray();

            $content = "
                <canvas id='problemsChart' height='120'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('problemsChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('problemsChart').getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: " . json_encode($labels) . ",
                                datasets: [{
                                    label: 'Occurrences',
                                    data: " . json_encode($counts) . ",
                                    backgroundColor: '#f44336',
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true, indexAxis: 'y',
                                plugins: { legend: { display: false } },
                                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('🔍 Most Common Problems', $content));
        });
    }

    private function addActionsTakenChart(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            $actions = AesaObservation::select('immediate_action', DB::raw('COUNT(*) as cnt'))
                ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->whereNotNull('immediate_action')
                ->where('immediate_action', '!=', '')
                ->groupBy('immediate_action')
                ->orderByDesc('cnt')
                ->limit(8)
                ->get();

            $labels = $actions->pluck('immediate_action')->toArray();
            $counts = $actions->pluck('cnt')->toArray();

            $content = "
                <canvas id='actionsChart' height='120'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('actionsChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('actionsChart').getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: " . json_encode($labels) . ",
                                datasets: [{
                                    label: 'Actions',
                                    data: " . json_encode($counts) . ",
                                    backgroundColor: '#2196f3',
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true, indexAxis: 'y',
                                plugins: { legend: { display: false } },
                                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('💊 Immediate Actions Taken', $content));
        });
    }

    // ═══════════════════════════════════════════════════════════
    // TABLES
    // ═══════════════════════════════════════════════════════════

    private function addTopGroupsTable(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            $groups = AesaSession::select('group_id', DB::raw('COUNT(*) as session_count'))
                ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->whereNotNull('group_id')
                ->groupBy('group_id')
                ->orderByDesc('session_count')
                ->limit(10)
                ->get();

            $rows = '';
            $rank = 0;
            foreach ($groups as $g) {
                $rank++;
                $group = FfsGroup::find($g->group_id);
                $name = $group ? $group->name : "Group #{$g->group_id}";
                $district = $group ? ($group->district_text ?: '—') : '—';

                $obsCount = AesaObservation::whereHas('session', function ($q) use ($g) {
                    $q->where('group_id', $g->group_id);
                })->count();

                $cropCount = AesaCropObservation::whereHas('session', function ($q) use ($g) {
                    $q->where('group_id', $g->group_id);
                })->count();

                $medal = $rank <= 3
                    ? "<span style='display:inline-block;width:24px;height:24px;background:" . ['', '#ffd700', '#c0c0c0', '#cd7f32'][$rank] . ";color:#fff;text-align:center;line-height:24px;font-weight:700;font-size:12px;'>{$rank}</span>"
                    : "<span style='display:inline-block;width:24px;text-align:center;font-weight:600;color:#999;'>{$rank}</span>";

                $rows .= "
                    <tr>
                        <td style='padding:8px;'>{$medal}</td>
                        <td style='padding:8px;font-weight:600;'>{$name}</td>
                        <td style='padding:8px;color:#666;'>{$district}</td>
                        <td style='padding:8px;text-align:center;font-weight:700;color:#05179F;'>{$g->session_count}</td>
                        <td style='padding:8px;text-align:center;'>{$obsCount}</td>
                        <td style='padding:8px;text-align:center;color:#388e3c;font-weight:600;'>{$cropCount}</td>
                    </tr>";
            }

            $content = empty($rows)
                ? '<div style="padding:20px;text-align:center;color:#999;">No session data yet</div>'
                : "
                    <table class='table table-bordered' style='margin:0;font-size:12px;'>
                        <thead style='background:#05179F;color:#fff;'>
                            <tr>
                                <th style='padding:8px;width:40px;'>#</th>
                                <th style='padding:8px;'>Group</th>
                                <th style='padding:8px;'>District</th>
                                <th style='padding:8px;text-align:center;'>Sessions</th>
                                <th style='padding:8px;text-align:center;'>Animals</th>
                                <th style='padding:8px;text-align:center;'>Crops</th>
                            </tr>
                        </thead>
                        <tbody>{$rows}</tbody>
                    </table>";

            $column->append(new Box('🏆 Top Groups by Activity', $content));
        });
    }

    private function addGeographicCoverageTable(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            $districts = AesaSession::select('district_text', DB::raw('COUNT(*) as session_count'), DB::raw('COUNT(DISTINCT group_id) as group_count'))
                ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->whereNotNull('district_text')
                ->where('district_text', '!=', '')
                ->groupBy('district_text')
                ->orderByDesc('session_count')
                ->limit(10)
                ->get();

            $rows = '';
            foreach ($districts as $d) {
                $obsCount = AesaObservation::whereHas('session', function ($q) use ($d, $ipId) {
                    $q->where('district_text', $d->district_text);
                    if ($ipId) $q->where('ip_id', $ipId);
                })->count();

                $sickCount = AesaObservation::where('animal_health_status', 'Sick')
                    ->whereHas('session', function ($q) use ($d, $ipId) {
                        $q->where('district_text', $d->district_text);
                        if ($ipId) $q->where('ip_id', $ipId);
                    })->count();
                $sickColor = $sickCount > 0 ? '#f44336' : '#4caf50';

                $rows .= "
                    <tr>
                        <td style='padding:8px;font-weight:600;'>{$d->district_text}</td>
                        <td style='padding:8px;text-align:center;'>{$d->session_count}</td>
                        <td style='padding:8px;text-align:center;'>{$d->group_count}</td>
                        <td style='padding:8px;text-align:center;'>{$obsCount}</td>
                        <td style='padding:8px;text-align:center;color:{$sickColor};font-weight:600;'>{$sickCount}</td>
                    </tr>";
            }

            $content = empty($rows)
                ? '<div style="padding:20px;text-align:center;color:#999;">No geographic data yet</div>'
                : "
                    <table class='table table-bordered' style='margin:0;font-size:12px;'>
                        <thead style='background:#05179F;color:#fff;'>
                            <tr>
                                <th style='padding:8px;'>District</th>
                                <th style='padding:8px;text-align:center;'>Sessions</th>
                                <th style='padding:8px;text-align:center;'>Groups</th>
                                <th style='padding:8px;text-align:center;'>Animals</th>
                                <th style='padding:8px;text-align:center;'>Sick</th>
                            </tr>
                        </thead>
                        <tbody>{$rows}</tbody>
                    </table>";

            $column->append(new Box('🗺️ Geographic Coverage', $content));
        });
    }

    private function addRecentSessionsTable(Row $row, $ipId)
    {
        $row->column(12, function (Column $column) use ($ipId) {
            $sessions = AesaSession::with(['group', 'facilitator'])
                ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->orderByDesc('observation_date')
                ->limit(10)
                ->get();

            $rows = '';
            foreach ($sessions as $s) {
                $statusColors = ['draft' => '#ff9800', 'submitted' => '#4caf50', 'reviewed' => '#2196f3'];
                $statusColor = $statusColors[$s->status] ?? '#999';
                $statusLabel = ucfirst($s->status);
                $date = $s->observation_date ? date('d M Y', strtotime($s->observation_date)) : '—';
                $group = $s->group ? $s->group->name : ($s->group_name_other ?: '—');
                $facilitator = $s->facilitator
                    ? trim(($s->facilitator->first_name ?? '') . ' ' . ($s->facilitator->last_name ?? ''))
                    : ($s->facilitator_name ?: '—');
                $obsCount  = $s->observations()->count();
                $cropCount = $s->cropObservations()->count();
                $url = admin_url('aesa-admin-sessions/' . $s->id);

                $rows .= "
                    <tr>
                        <td style='padding:8px;'><a href='{$url}' style='font-weight:600;'>{$s->data_sheet_number}</a></td>
                        <td style='padding:8px;'>{$group}</td>
                        <td style='padding:8px;'>{$date}</td>
                        <td style='padding:8px;'>{$facilitator}</td>
                        <td style='padding:8px;text-align:center;font-weight:700;color:#05179F;'>{$obsCount}</td>
                        <td style='padding:8px;text-align:center;font-weight:700;color:#388e3c;'>{$cropCount}</td>
                        <td style='padding:8px;'>
                            <span style='display:inline-block;padding:2px 10px;background:{$statusColor};color:#fff;font-size:11px;font-weight:600;text-transform:uppercase;'>{$statusLabel}</span>
                        </td>
                    </tr>";
            }

            $content = empty($rows)
                ? '<div style="padding:20px;text-align:center;color:#999;">No sessions yet</div>'
                : "
                    <div style='overflow-x:auto;'>
                        <table class='table table-bordered' style='margin:0;font-size:12px;'>
                            <thead style='background:#05179F;color:#fff;'>
                                <tr>
                                    <th style='padding:8px;'>Data Sheet #</th>
                                    <th style='padding:8px;'>Group</th>
                                    <th style='padding:8px;'>Date</th>
                                    <th style='padding:8px;'>Facilitator</th>
                                    <th style='padding:8px;text-align:center;'>Animals</th>
                                    <th style='padding:8px;text-align:center;'>Crops</th>
                                    <th style='padding:8px;'>Status</th>
                                </tr>
                            </thead>
                            <tbody>{$rows}</tbody>
                        </table>
                    </div>";

            $column->append(new Box('📋 Recent Sessions', $content));
        });
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Compute average health score across all observations
     */
    private function computeAverageHealthScore($ipId): int
    {
        // health_score is an accessor, so we compute from raw columns
        $observations = AesaObservation::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->select('body_condition', 'eyes_condition', 'coat_condition', 'appetite', 'movement', 'behaviour',
                'ticks_level', 'fleas_level', 'lice_level', 'mites_level',
                'wounds_injuries', 'skin_infection', 'swelling', 'coughing', 'diarrhea')
            ->limit(2000)
            ->get();

        if ($observations->isEmpty()) return 0;

        $totalScore = 0;
        $count = 0;
        foreach ($observations as $obs) {
            $score = $obs->health_score;
            if (is_numeric($score)) {
                $totalScore += $score;
                $count++;
            }
        }

        return $count > 0 ? round($totalScore / $count) : 0;
    }

    /**
     * Compute average crop health score across all crop observations
     */
    private function computeAverageCropHealthScore($ipId): int
    {
        $observations = AesaCropObservation::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->select('crop_vigor', 'leaf_condition', 'stem_condition', 'root_condition',
                'aphids_level', 'caterpillars_armyworms_level', 'beetles_level',
                'grasshoppers_level', 'whiteflies_level', 'other_insect_pests_level',
                'leaf_spot_level', 'blight_level', 'rust_level', 'wilt_level', 'mosaic_virus_level')
            ->limit(2000)
            ->get();

        if ($observations->isEmpty()) return 0;

        $totalScore = 0;
        $count = 0;
        foreach ($observations as $obs) {
            $score = $obs->crop_health_score;
            if (is_numeric($score)) {
                $totalScore += $score;
                $count++;
            }
        }
        return $count > 0 ? round($totalScore / $count) : 0;
    }

    // ── Crop Charts ──────────────────────────────────────────────────────────

    private function addCropTypeChart(Row $row, $ipId)
    {
        $row->column(4, function (Column $column) use ($ipId) {
            $types = AesaCropObservation::select('crop_type', DB::raw('COUNT(*) as cnt'))
                ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->whereNotNull('crop_type')
                ->groupBy('crop_type')
                ->orderByDesc('cnt')
                ->limit(8)
                ->get();

            $labels = $types->pluck('crop_type')->toArray();
            $counts = $types->pluck('cnt')->toArray();
            $colors = ['#388e3c', '#ffa000', '#1976d2', '#e64a19', '#7b1fa2', '#00838f', '#f57f17', '#5d4037'];

            $content = "
                <canvas id='cropTypeChart' height='220'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('cropTypeChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('cropTypeChart').getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: " . json_encode($labels) . ",
                                datasets: [{ data: " . json_encode($counts) . ", backgroundColor: " . json_encode(array_slice($colors, 0, count($labels))) . ", borderWidth: 2, borderColor: '#fff' }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true,
                                plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 8, usePointStyle: true } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('🌿 Crop Types', $content));
        });
    }

    private function addCropVigorChart(Row $row, $ipId)
    {
        $row->column(8, function (Column $column) use ($ipId) {
            $vigors = ['Excellent', 'Good', 'Moderate', 'Poor'];
            $counts = [];
            foreach ($vigors as $v) {
                $counts[] = AesaCropObservation::where('crop_vigor', $v)
                    ->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
            }
            $colors = ['rgba(25,118,210,0.8)', 'rgba(56,142,60,0.8)', 'rgba(255,160,0,0.8)', 'rgba(244,67,54,0.8)'];

            $content = "
                <canvas id='cropVigorChart' height='110'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('cropVigorChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('cropVigorChart').getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: " . json_encode($vigors) . ",
                                datasets: [{
                                    label: 'Crop Plots',
                                    data: " . json_encode($counts) . ",
                                    backgroundColor: " . json_encode($colors) . ",
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true,
                                plugins: { legend: { display: false } },
                                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('🌱 Crop Vigor Distribution', $content));
        });
    }

    private function addCropPestPrevalenceChart(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            $pestFields = [
                'aphids_level'                => 'Aphids',
                'caterpillars_armyworms_level' => 'Caterpillars/Armyworms',
                'beetles_level'               => 'Beetles',
                'grasshoppers_level'          => 'Grasshoppers',
                'whiteflies_level'            => 'Whiteflies',
            ];

            $labels = array_values($pestFields);
            $lowCounts = $medCounts = $highCounts = [];
            foreach (array_keys($pestFields) as $field) {
                $lowCounts[]  = AesaCropObservation::where($field, 'Low')->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
                $medCounts[]  = AesaCropObservation::where($field, 'Medium')->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
                $highCounts[] = AesaCropObservation::where($field, 'High')->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
            }

            $content = "
                <canvas id='cropPestChart' height='120'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('cropPestChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('cropPestChart').getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: " . json_encode($labels) . ",
                                datasets: [
                                    { label: 'Low', data: " . json_encode($lowCounts) . ", backgroundColor: 'rgba(139,195,74,0.8)' },
                                    { label: 'Medium', data: " . json_encode($medCounts) . ", backgroundColor: 'rgba(255,152,0,0.8)' },
                                    { label: 'High', data: " . json_encode($highCounts) . ", backgroundColor: 'rgba(244,67,54,0.8)' }
                                ]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true,
                                plugins: { legend: { position: 'top', labels: { usePointStyle: true, font: { size: 11 } } } },
                                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('🐛 Pest Prevalence (Crop)', $content));
        });
    }

    private function addCropDiseasePrevalenceChart(Row $row, $ipId)
    {
        $row->column(6, function (Column $column) use ($ipId) {
            $diseaseFields = [
                'leaf_spot_level'   => 'Leaf Spot',
                'blight_level'      => 'Blight',
                'rust_level'        => 'Rust',
                'wilt_level'        => 'Wilt',
                'mosaic_virus_level' => 'Mosaic Virus',
            ];

            $labels = array_values($diseaseFields);
            $lowCounts = $medCounts = $highCounts = [];
            foreach (array_keys($diseaseFields) as $field) {
                $lowCounts[]  = AesaCropObservation::where($field, 'Low')->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
                $medCounts[]  = AesaCropObservation::where($field, 'Medium')->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
                $highCounts[] = AesaCropObservation::where($field, 'High')->when($ipId, fn($q) => $q->where('ip_id', $ipId))->count();
            }

            $content = "
                <canvas id='cropDiseaseChart' height='120'></canvas>
                <script>
                (function() {
                    var wait = setInterval(function() {
                        if (typeof Chart === 'undefined' || !document.getElementById('cropDiseaseChart')) return;
                        clearInterval(wait);
                        new Chart(document.getElementById('cropDiseaseChart').getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: " . json_encode($labels) . ",
                                datasets: [
                                    { label: 'Low', data: " . json_encode($lowCounts) . ", backgroundColor: 'rgba(139,195,74,0.8)' },
                                    { label: 'Medium', data: " . json_encode($medCounts) . ", backgroundColor: 'rgba(255,152,0,0.8)' },
                                    { label: 'High', data: " . json_encode($highCounts) . ", backgroundColor: 'rgba(244,67,54,0.8)' }
                                ]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: true,
                                plugins: { legend: { position: 'top', labels: { usePointStyle: true, font: { size: 11 } } } },
                                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } }
                            }
                        });
                    }, 100);
                })();
                </script>";
            $column->append(new Box('🦠 Disease Prevalence (Crop)', $content));
        });
    }

    /**
     * CSS styles for the AESA stats dashboard
     */
    private function getStyles(): string
    {
        return '
            .box { border: 1px solid #ddd; border-radius: 0; box-shadow: none; }
            .box-header { background: #fff; border-bottom: 1px solid #ddd; border-radius: 0; padding: 10px 15px; }
            .box-header .box-title { font-weight: 700; font-size: 13px; color: #333; }
            .box-body { padding: 0; }

            .aesa-kpi {
                display: flex;
                align-items: center;
                padding: 15px;
                background: #fff;
            }
            .aesa-kpi-icon {
                width: 48px;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 20px;
                flex-shrink: 0;
                margin-right: 14px;
            }
            .aesa-kpi-body { flex: 1; min-width: 0; }
            .aesa-kpi-number {
                font-size: 28px;
                font-weight: 800;
                color: #05179F;
                line-height: 1.1;
            }
            .aesa-kpi-label {
                font-size: 11px;
                color: #666;
                font-weight: 700;
                text-transform: uppercase;
                margin-top: 2px;
            }
            .aesa-kpi-detail {
                font-size: 11px;
                color: #999;
                margin-top: 3px;
            }

            .aesa-kpi-mini {
                display: flex;
                align-items: center;
                padding: 12px 15px;
                background: #fff;
                gap: 8px;
            }
            .aesa-kpi-mini i { font-size: 16px; }
            .aesa-kpi-mini-number {
                font-size: 18px;
                font-weight: 800;
                color: #05179F;
            }
            .aesa-kpi-mini-label {
                font-size: 11px;
                color: #666;
                font-weight: 600;
            }
        ';
    }
}
