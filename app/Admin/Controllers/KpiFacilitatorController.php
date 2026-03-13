<?php

namespace App\Admin\Controllers;

use App\Models\KpiBenchmark;
use App\Models\FfsGroup;
use App\Models\User;
use App\Services\KpiService;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

/**
 * KpiFacilitatorController — facilitator KPI benchmarks & live scorecard.
 *
 * Routes (both point here):
 *   kpi-benchmarks  (backward-compatible alias)
 *   kpi-facilitators
 *
 * Access tiers:
 *   Super Admin  → all facilitators (any IP)
 *   IP Manager   → only their IP's facilitators
 *   Facilitator  → only their own scorecard row
 */
class KpiFacilitatorController extends AdminController
{
    use IpScopeable;

    protected $title = 'Facilitator KPIs';

    public function index(Content $content)
    {
        return $content
            ->title('Facilitator KPIs')
            ->description('Benchmark settings & individual facilitator performance')
            ->row(function (Row $row) {
                $this->renderPage($row);
            });
    }

    private function renderPage(Row $row)
    {
        $bench        = KpiBenchmark::current();
        $isSuperAdmin = $this->isSuperAdmin();
        $currentAdmin = Admin::user();
        $isFacilitator = !$isSuperAdmin && $currentAdmin
            && $this->userHasRoleSlug($currentAdmin, 'facilitator');
        $ipId = $this->getAdminIpId();

        // ── Collect facilitator IDs scoped to tier ─────────────────────────
        $facilitatorQuery = FfsGroup::where('status', 'Active')
            ->whereNotNull('facilitator_id');

        if ($isFacilitator) {
            // Facilitators see only themselves
            $facilitatorQuery->where('facilitator_id', $currentAdmin->id);
        } elseif ($ipId) {
            $facilitatorQuery->where('ip_id', $ipId);
        }

        $facilitatorIds = $facilitatorQuery->distinct()->pluck('facilitator_id');

        // ── Precompute ALL scorecards once (avoids repeated DB queries) ────
        $scorecards = [];
        foreach ($facilitatorIds as $fId) {
            $card = KpiService::facilitatorScorecard($fId);
            $fac  = User::find($fId);
            $scorecards[$fId] = $card + [
                '_name' => $fac
                    ? ($fac->name ?: trim($fac->first_name . ' ' . $fac->last_name))
                    : "User #{$fId}",
            ];
        }

        // ── Week range header ──────────────────────────────────────────────
        $weekStart = $scorecards ? reset($scorecards)['week_start'] : null;
        $weekEnd   = $scorecards ? reset($scorecards)['week_end']   : null;
        $weekLabel = ($weekStart && $weekEnd)
            ? date('d M', strtotime($weekStart)) . ' – ' . date('d M Y', strtotime($weekEnd))
            : 'Current Week';

        // ── Benchmark summary card ─────────────────────────────────────────
        $row->column(12, function (Column $col) use ($bench, $isFacilitator) {
            $editUrl = admin_url("kpi-facilitators/{$bench->id}/edit");
            $html = "<div style='background:#fff;border:1px solid #ddd;border-left:4px solid #2196F3;padding:16px;margin-bottom:16px;border-radius:2px;'>";
            $html .= "<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;'>";
            $html .= "<h4 style='margin:0;'><i class='fa fa-sliders' style='color:#2196F3;'></i>&nbsp; Facilitator KPI Benchmarks</h4>";
            if (!$isFacilitator) {
                $html .= "<a href='{$editUrl}' class='btn btn-sm btn-default'><i class='fa fa-pencil'></i> Edit Benchmarks</a>";
            }
            $html .= "</div>";
            $html .= "<div style='display:flex;gap:10px;flex-wrap:wrap;'>";
            $items = [
                ['Min Groups',           $bench->min_groups_per_facilitator,          'fa-users',           '#2196F3'],
                ['Trainings/Week',       $bench->min_trainings_per_week,              'fa-graduation-cap',  '#4caf50'],
                ['Meetings/Group/Week',  $bench->min_meetings_per_group_per_week,     'fa-calendar-check-o','#ff9800'],
                ['Members/Group',        $bench->min_members_per_group,               'fa-user',            '#9c27b0'],
                ['AESA/Week',            $bench->min_aesa_sessions_per_week,          'fa-leaf',            '#009688'],
                ['Attendance %',         $bench->min_meeting_attendance_pct . '%',    'fa-check-circle',    '#e91e63'],
            ];
            foreach ($items as $i) {
                $html .= "<div style='flex:1;min-width:130px;text-align:center;padding:10px 8px;border:1px solid #eee;border-radius:2px;background:#fafafa;'>
                    <i class='fa {$i[2]}' style='font-size:16px;color:{$i[3]};'></i>
                    <div style='font-size:22px;font-weight:700;color:{$i[3]};margin:4px 0;line-height:1;'>{$i[1]}</div>
                    <div style='font-size:10px;text-transform:uppercase;color:#888;letter-spacing:.5px;'>{$i[0]}</div>
                </div>";
            }
            $html .= "</div></div>";
            $col->append($html);
        });

        // ── Facilitator scorecard table ────────────────────────────────────
        $row->column(12, function (Column $col) use ($scorecards, $bench, $weekLabel) {
            $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
            $html .= "<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;'>";
            $html .= "<h4 style='margin:0;'><i class='fa fa-bar-chart'></i>&nbsp; Facilitator Performance</h4>";
            $html .= "<span class='label label-info'>{$weekLabel}</span>";
            $html .= "</div>";

            if (empty($scorecards)) {
                $html .= "<p class='text-muted' style='padding:12px 0;'><i class='fa fa-info-circle'></i> No facilitators with active groups found.</p></div>";
                $col->append($html);
                return;
            }

            $html .= "<div style='overflow-x:auto;'>";
            $html .= "<table class='table table-bordered table-striped table-condensed' style='margin:0;font-size:13px;'>";
            $html .= "<thead><tr style='background:#f5f5f5;'>
                <th>Facilitator</th>
                <th style='text-align:center;white-space:nowrap;'>Start Date</th>
                <th style='text-align:center;white-space:nowrap;'>Weeks Active</th>
                <th style='text-align:center;'>Groups<br><small class='text-muted'>target: {$bench->min_groups_per_facilitator}</small></th>
                <th style='text-align:center;'>Trainings<br><small class='text-muted'>target: {$bench->min_trainings_per_week}/wk</small></th>
                <th style='text-align:center;'>Meetings<br><small class='text-muted'>target: {$bench->min_meetings_per_group_per_week}/grp/wk</small></th>
                <th style='text-align:center;'>Members<br><small class='text-muted'>target: {$bench->min_members_per_group}/grp</small></th>
                <th style='text-align:center;'>AESA<br><small class='text-muted'>target: {$bench->min_aesa_sessions_per_week}/wk</small></th>
                <th style='text-align:center;'>Attendance<br><small class='text-muted'>target: {$bench->min_meeting_attendance_pct}%</small></th>
                <th style='text-align:center;'>Overall</th>
            </tr></thead><tbody>";

            foreach ($scorecards as $fId => $card) {
                $name       = e($card['_name']);
                $startDate  = $card['start_date']   ?? '—';
                $weeksActive = $card['weeks_active'] ?? '—';
                $a = $card['actuals'];
                $s = $card['scores'];
                $overall = $card['overall_score'];
                $overallColor = $overall >= 80 ? '#4caf50' : ($overall >= 50 ? '#ff9800' : '#f44336');
                $overallBg    = $overall >= 80 ? '#e8f5e9' : ($overall >= 50 ? '#fff3e0' : '#ffebee');

                $html .= "<tr>";
                $html .= "<td><strong>{$name}</strong></td>";
                $html .= "<td style='text-align:center;font-size:12px;color:#666;'>{$startDate}</td>";
                $html .= "<td style='text-align:center;'>{$weeksActive}</td>";
                $html .= self::scoreCell($a['total_groups'], $s['groups']);
                $html .= self::scoreCell($a['trainings_this_week'], $s['trainings']);
                $html .= self::scoreCell($a['meetings_per_group'], $s['meetings']);
                $html .= self::scoreCell($a['avg_members_per_group'], $s['members']);
                $html .= self::scoreCell($a['aesa_this_week'], $s['aesa']);
                $html .= self::scoreCell(round($a['attendance_pct'], 1) . '%', $s['attendance']);
                $html .= "<td style='text-align:center;background:{$overallBg};'>
                    <div style='font-weight:700;font-size:18px;color:{$overallColor};'>{$overall}%</div>
                    <div style='font-size:10px;color:#888;'>" . self::perfLabel($overall) . "</div>
                </td>";
                $html .= "</tr>";
            }

            $html .= "</tbody></table></div></div>";
            $col->append($html);
        });

        // ── Summary stats row (only useful when multiple facilitators) ─────
        if (count($scorecards) > 1) {
            $row->column(12, function (Column $col) use ($scorecards) {
                $count     = count($scorecards);
                $avgOverall = $count > 0 ? round(array_sum(array_column($scorecards, 'overall_score')) / $count, 1) : 0;
                $excellent  = count(array_filter($scorecards, fn($c) => $c['overall_score'] >= 80));
                $good       = count(array_filter($scorecards, fn($c) => $c['overall_score'] >= 60 && $c['overall_score'] < 80));
                $needs      = count(array_filter($scorecards, fn($c) => $c['overall_score'] >= 40 && $c['overall_score'] < 60));
                $below      = count(array_filter($scorecards, fn($c) => $c['overall_score'] < 40));
                $avgColor   = $avgOverall >= 80 ? '#4caf50' : ($avgOverall >= 50 ? '#ff9800' : '#f44336');

                $html = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;'>";
                $html .= "<h4 style='margin:0 0 12px;'><i class='fa fa-trophy'></i>&nbsp; Performance Summary</h4>";
                $html .= "<div style='display:flex;gap:10px;flex-wrap:wrap;'>";
                $items = [
                    ['Total Facilitators', $count,           '#607d8b'],
                    ['Avg Overall Score',  $avgOverall . '%', $avgColor],
                    ['Excellent (≥80%)',   $excellent,        '#4caf50'],
                    ['Good (60-79%)',       $good,             '#ff9800'],
                    ['Needs Improvement',  $needs,            '#ffc107'],
                    ['Below Target (<40%)',$below,            '#f44336'],
                ];
                foreach ($items as $i) {
                    $html .= "<div style='flex:1;min-width:120px;text-align:center;padding:10px 8px;border:1px solid #eee;border-radius:2px;'>
                        <div style='font-size:22px;font-weight:700;color:{$i[2]};'>{$i[1]}</div>
                        <div style='font-size:10px;text-transform:uppercase;color:#888;'>{$i[0]}</div>
                    </div>";
                }
                $html .= "</div></div>";
                $col->append($html);
            });
        }
    }

    // ─── Shared helpers ───────────────────────────────────────────────────

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
        if ($score >= 40) return 'Needs Improvement';
        return 'Below Target';
    }

    // ─── Grid (benchmark settings — single record) ───────────────────────

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

    // ─── Form (edit benchmarks) ───────────────────────────────────────────

    protected function form()
    {
        $form = new Form(new KpiBenchmark());

        $form->display('id', 'ID');
        $form->divider('Facilitator KPI Benchmark Targets');
        $form->html('<div class="alert alert-info"><i class="fa fa-info-circle"></i> These benchmarks define the minimum targets used to compute each facilitator\'s weekly KPI score (0–100%).</div>');

        $form->row(function ($row) {
            $row->width(4)->number('min_groups_per_facilitator', 'Min Groups per Facilitator')
                ->default(3)->min(1)->max(50)
                ->help('Minimum active groups each facilitator should manage');
            $row->width(4)->number('min_trainings_per_week', 'Min Trainings per Week')
                ->default(2)->min(0)->max(20)
                ->help('Minimum FFS sessions a facilitator should conduct weekly');
            $row->width(4)->number('min_meetings_per_group_per_week', 'Min Meetings / Group / Week')
                ->default(1)->min(0)->max(7)
                ->help('Minimum VSLA meeting submissions per group per week');
        });

        $form->row(function ($row) {
            $row->width(4)->number('min_members_per_group', 'Min Members per Group')
                ->default(30)->min(5)->max(100)
                ->help('Minimum registered members per group');
            $row->width(4)->number('min_aesa_sessions_per_week', 'Min AESA Sessions per Week')
                ->default(1)->min(0)->max(10)
                ->help('Minimum AESA observation sessions per facilitator weekly');
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

    protected function detail($id)
    {
        $show = new \Encore\Admin\Show(KpiBenchmark::findOrFail($id));
        $show->panel()->style('primary')->title('KPI Benchmark Settings');
        $show->field('min_groups_per_facilitator',      'Min Groups per Facilitator');
        $show->field('min_trainings_per_week',          'Min Trainings per Week');
        $show->field('min_meetings_per_group_per_week', 'Min Meetings / Group / Week');
        $show->field('min_members_per_group',           'Min Members per Group');
        $show->field('min_aesa_sessions_per_week',      'Min AESA per Week');
        $show->field('min_meeting_attendance_pct',      'Min Attendance %');
        $show->field('updated_at', 'Last Updated');
        return $show;
    }
}
