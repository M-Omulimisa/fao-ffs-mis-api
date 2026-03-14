<?php

namespace App\Admin\Controllers;

use App\Models\KpiBenchmark;
use App\Models\FfsGroup;
use App\Models\ImplementingPartner;
use App\Services\KpiService;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

/**
 * KpiBenchmarkController — KPI Overview Hub + benchmark settings editor.
 *
 * index()  → Overview dashboard (benchmark cards + quick summary tables + navigation links)
 * edit()   → Edit the benchmark targets
 *
 * Detailed per-facilitator KPIs → KpiFacilitatorController (kpi-facilitators)
 * Detailed per-IP KPIs          → KpiIpController          (kpi-ips)
 * Visual charts                 → KpiStatsController       (kpi-stats)
 */
class KpiBenchmarkController extends AdminController
{
    use IpScopeable;

    protected $title = 'KPI Overview';

    // ─── Overview dashboard ───────────────────────────────────────────────

    public function index(Content $content)
    {
        return $content
            ->title('KPI Overview')
            ->description('Benchmark targets & performance snapshot')
            ->row(function (Row $row) {
                $this->renderOverview($row);
            });
    }

    private function renderOverview(Row $row)
    {
        $bench        = KpiBenchmark::current();
        $isSuperAdmin = $this->isSuperAdmin();
        $ipId         = $this->getAdminIpId();
        $editUrl      = admin_url("kpi-benchmarks/{$bench->id}/edit");

        // ── Navigation links ───────────────────────────────────────────
        $row->column(12, function (Column $col) use ($isSuperAdmin) {
            $html  = "<div style='background:#fff;border:1px solid #ddd;padding:14px 16px;margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;'>";
            $html .= "<span style='font-size:13px;color:#666;margin-right:4px;'>Quick links:</span>";
            $html .= "<a href='" . admin_url('kpi-facilitators') . "' class='btn btn-sm btn-primary'><i class='fa fa-user'></i> Facilitator KPIs</a>";
            if ($isSuperAdmin) {
                $html .= "<a href='" . admin_url('kpi-ips') . "' class='btn btn-sm btn-info'><i class='fa fa-building'></i> IP KPIs</a>";
            }
            $html .= "<a href='" . admin_url('kpi-stats') . "' class='btn btn-sm btn-default'><i class='fa fa-bar-chart'></i> Charts &amp; Analytics</a>";
            $html .= "</div>";
            $col->append($html);
        });

        // ── Benchmark targets card ─────────────────────────────────────
        $row->column(12, function (Column $col) use ($bench, $editUrl) {
            $html  = "<div style='background:#fff;border:1px solid #ddd;border-left:4px solid #2196F3;padding:16px;margin-bottom:16px;border-radius:2px;'>";
            $html .= "<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;'>";
            $html .= "<h4 style='margin:0;'><i class='fa fa-sliders' style='color:#2196F3;'></i>&nbsp; Facilitator KPI Benchmarks</h4>";
            $html .= "<a href='{$editUrl}' class='btn btn-sm btn-primary'><i class='fa fa-pencil'></i> Edit</a>";
            $html .= "</div>";
            $html .= "<div style='display:flex;gap:10px;flex-wrap:wrap;'>";
            $items = [
                ['Min Groups / Facilitator',     $bench->min_groups_per_facilitator,                  'fa-users',            '#2196F3'],
                ['Min Trainings / Week',          $bench->min_trainings_per_week,                      'fa-graduation-cap',   '#4caf50'],
                ['Min Meetings / Group / Week',   $bench->min_meetings_per_group_per_week,             'fa-calendar-check-o', '#ff9800'],
                ['Min Members / Group',           $bench->min_members_per_group,                       'fa-user',             '#9c27b0'],
                ['Min AESA / Week',               $bench->min_aesa_sessions_per_week,                  'fa-leaf',             '#009688'],
                ['Min Attendance %',              $bench->min_meeting_attendance_pct . '%',            'fa-check-circle',     '#e91e63'],
            ];
            foreach ($items as $i) {
                $html .= "<div style='flex:1;min-width:130px;text-align:center;padding:10px 8px;border:1px solid #eee;border-radius:2px;background:#fafafa;'>
                    <i class='fa {$i[2]}' style='font-size:16px;color:{$i[3]};'></i>
                    <div style='font-size:22px;font-weight:700;color:{$i[3]};margin:4px 0;line-height:1;'>{$i[1]}</div>
                    <div style='font-size:10px;text-transform:uppercase;color:#888;letter-spacing:.5px;'>{$i[0]}</div>
                </div>";
            }
            $html .= "</div>";
            $html .= "<p style='margin:10px 0 0;font-size:12px;color:#999;'>Last updated: {$bench->updated_at}</p>";
            $html .= "</div>";
            $col->append($html);
        });

        // ── Facilitator quick scorecard ────────────────────────────────
        $facilitatorIds = FfsGroup::where('status', 'Active')
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->whereNotNull('facilitator_id')
            ->distinct()
            ->pluck('facilitator_id');

        $row->column($isSuperAdmin ? 6 : 12, function (Column $col) use ($facilitatorIds) {
            $html  = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
            $html .= "<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;'>";
            $html .= "<h4 style='margin:0;'><i class='fa fa-user' style='color:#2196F3;'></i>&nbsp; Facilitators — This Week</h4>";
            $html .= "<a href='" . admin_url('kpi-facilitators') . "' class='btn btn-xs btn-default'>View All &rarr;</a>";
            $html .= "</div>";

            if ($facilitatorIds->isEmpty()) {
                $html .= "<p class='text-muted' style='padding:8px 0;'><i class='fa fa-info-circle'></i> No facilitators with active groups.</p>";
            } else {
                $scorecards = [];
                foreach ($facilitatorIds as $fId) {
                    $scorecards[] = KpiService::facilitatorScorecard($fId);
                }
                $count      = count($scorecards);
                $avgOverall = $count > 0 ? round(array_sum(array_column($scorecards, 'overall_score')) / $count, 1) : 0;
                $excellent  = count(array_filter($scorecards, fn($c) => $c['overall_score'] >= 80));
                $avgColor   = $avgOverall >= 80 ? '#4caf50' : ($avgOverall >= 50 ? '#ff9800' : '#f44336');

                $html .= "<div style='display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;'>";
                $html .= self::miniStat($count, 'Total', '#607d8b');
                $html .= self::miniStat($avgOverall . '%', 'Avg Score', $avgColor);
                $html .= self::miniStat($excellent, 'Excellent (≥80%)', '#4caf50');
                $html .= "</div>";

                $html .= "<table class='table table-condensed' style='margin:0;font-size:12px;'>";
                $html .= "<thead><tr><th>Facilitator</th><th style='text-align:center;'>Score</th><th style='text-align:center;'>Status</th></tr></thead><tbody>";
                foreach ($scorecards as $card) {
                    $overall = $card['overall_score'];
                    $color   = $overall >= 80 ? '#4caf50' : ($overall >= 50 ? '#ff9800' : '#f44336');
                    $bg      = $overall >= 80 ? '#e8f5e9' : ($overall >= 50 ? '#fff3e0' : '#ffebee');
                    $label   = $overall >= 80 ? 'Excellent' : ($overall >= 60 ? 'Good' : ($overall >= 40 ? 'Fair' : 'Below'));
                    $name    = e($card['facilitator_name'] ?? "User #{$card['facilitator_id']}");
                    $html .= "<tr>
                        <td>{$name}</td>
                        <td style='text-align:center;background:{$bg};font-weight:700;color:{$color};'>{$overall}%</td>
                        <td style='text-align:center;'><span style='font-size:10px;color:{$color};font-weight:600;'>{$label}</span></td>
                    </tr>";
                }
                $html .= "</tbody></table>";
            }

            $html .= "</div>";
            $col->append($html);
        });

        // ── IP quick scorecard (super admin only) ──────────────────────
        if ($isSuperAdmin) {
            $row->column(6, function (Column $col) {
                $ips  = ImplementingPartner::active()->get();
                $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
                $html .= "<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;'>";
                $html .= "<h4 style='margin:0;'><i class='fa fa-building' style='color:#4caf50;'></i>&nbsp; IPs — This Week</h4>";
                $html .= "<a href='" . admin_url('kpi-ips') . "' class='btn btn-xs btn-default'>View All &rarr;</a>";
                $html .= "</div>";

                if ($ips->isEmpty()) {
                    $html .= "<p class='text-muted'><i class='fa fa-info-circle'></i> No active IPs.</p>";
                } else {
                    $html .= "<table class='table table-condensed' style='margin:0;font-size:12px;'>";
                    $html .= "<thead><tr><th>IP</th><th style='text-align:center;'>Score</th><th style='text-align:center;'>Facilitators</th></tr></thead><tbody>";
                    foreach ($ips as $ip) {
                        $card    = KpiService::ipScorecard($ip->id);
                        $overall = $card['overall_score'];
                        $color   = $overall >= 80 ? '#4caf50' : ($overall >= 50 ? '#ff9800' : '#f44336');
                        $bg      = $overall >= 80 ? '#e8f5e9' : ($overall >= 50 ? '#fff3e0' : '#ffebee');
                        $facMet  = $card['facilitator_performance']['pct_meeting_kpi'];
                        $html .= "<tr>
                            <td><strong>" . e($ip->short_name ?: $ip->name) . "</strong></td>
                            <td style='text-align:center;background:{$bg};font-weight:700;color:{$color};'>{$overall}%</td>
                            <td style='text-align:center;'><small>{$facMet}% met KPI</small></td>
                        </tr>";
                    }
                    $html .= "</tbody></table>";
                }

                $html .= "</div>";
                $col->append($html);
            });
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private static function miniStat($value, string $label, string $color): string
    {
        return "<div style='flex:1;min-width:80px;text-align:center;padding:8px;border:1px solid #eee;border-radius:2px;'>
            <div style='font-size:20px;font-weight:700;color:{$color};'>{$value}</div>
            <div style='font-size:10px;color:#888;text-transform:uppercase;'>{$label}</div>
        </div>";
    }

    // ─── Grid (fallback — only used internally by AdminController resource) ──

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
        $grid->column('min_groups_per_facilitator',      'Min Groups / Facilitator');
        $grid->column('min_trainings_per_week',          'Trainings / Week');
        $grid->column('min_meetings_per_group_per_week', 'Meetings / Group / Week');
        $grid->column('min_members_per_group',           'Members / Group');
        $grid->column('min_aesa_sessions_per_week',      'AESA / Week');
        $grid->column('min_meeting_attendance_pct',      'Attendance %');
        $grid->column('updated_at',                      'Last Updated');

        return $grid;
    }

    // ─── Form (edit benchmark targets) ───────────────────────────────────

    protected function form()
    {
        $form = new Form(new KpiBenchmark());

        $form->display('id', 'ID');
        $form->divider('Facilitator KPI Benchmark Targets');
        $form->html('<div class="alert alert-info"><i class="fa fa-info-circle"></i> These benchmarks define the minimum weekly targets used to score each facilitator (0–100 %).</div>');

        $form->row(function ($row) {
            $row->width(4)->number('min_groups_per_facilitator', 'Min Groups / Facilitator')
                ->default(3)->min(1)->max(50)
                ->help('Minimum active groups each facilitator must manage');
            $row->width(4)->number('min_trainings_per_week', 'Min Trainings / Week')
                ->default(2)->min(0)->max(20)
                ->help('Minimum FFS sessions per facilitator per week');
            $row->width(4)->number('min_meetings_per_group_per_week', 'Min Meetings / Group / Week')
                ->default(1)->min(0)->max(7)
                ->help('Minimum VSLA meeting submissions per group per week');
        });

        $form->row(function ($row) {
            $row->width(4)->number('min_members_per_group', 'Min Members / Group')
                ->default(30)->min(5)->max(100)
                ->help('Minimum registered members per group');
            $row->width(4)->number('min_aesa_sessions_per_week', 'Min AESA / Week')
                ->default(1)->min(0)->max(10)
                ->help('Minimum AESA observation sessions per facilitator per week');
            $row->width(4)->decimal('min_meeting_attendance_pct', 'Min Attendance %')
                ->default(75)
                ->help('Target meeting attendance percentage (0–100)');
        });

        $form->hidden('updated_by_id');
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

    // ─── Detail view ─────────────────────────────────────────────────────

    protected function detail($id)
    {
        $show = new Show(KpiBenchmark::findOrFail($id));
        $show->panel()->style('primary')->title('KPI Benchmark Settings');
        $show->field('min_groups_per_facilitator',      'Min Groups / Facilitator');
        $show->field('min_trainings_per_week',          'Min Trainings / Week');
        $show->field('min_meetings_per_group_per_week', 'Min Meetings / Group / Week');
        $show->field('min_members_per_group',           'Min Members / Group');
        $show->field('min_aesa_sessions_per_week',      'Min AESA / Week');
        $show->field('min_meeting_attendance_pct',      'Min Attendance %');
        $show->field('updated_at', 'Last Updated');
        return $show;
    }
}
