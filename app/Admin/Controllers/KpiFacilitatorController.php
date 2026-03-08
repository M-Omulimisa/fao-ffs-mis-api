<?php

namespace App\Admin\Controllers;

use App\Models\KpiBenchmark;
use App\Models\FfsGroup;
use App\Models\User;
use App\Services\KpiService;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

class KpiFacilitatorController extends AdminController
{
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
            $html .= "<a href='" . admin_url('kpi-facilitators/1/edit') . "' class='btn btn-sm btn-primary'><i class='fa fa-pencil'></i> Edit Benchmarks</a>";
            $html .= "</div></div>";
            $col->append($html);
        });

        // ── Facilitator scorecards table ─────────────────────
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
                <th style='text-align:center;'>Start Date</th>
                <th style='text-align:center;'>Weeks Active</th>
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
                $fac  = User::find($fId);
                $name = $fac ? ($fac->name ?: $fac->first_name . ' ' . $fac->last_name) : "User #{$fId}";
                $startDate  = $card['start_date'] ?? '-';
                $weeksActive = $card['weeks_active'] ?? '-';
                $a    = $card['actuals'];
                $s    = $card['scores'];
                $overall = $card['overall_score'];
                $overallColor = $overall >= 80 ? '#4caf50' : ($overall >= 50 ? '#ff9800' : '#f44336');

                $html .= "<tr>";
                $html .= "<td><strong>{$name}</strong></td>";
                $html .= "<td style='text-align:center;'>{$startDate}</td>";
                $html .= "<td style='text-align:center;'>{$weeksActive}</td>";
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

    // ─── Grid (shows the single benchmark record) ────────
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

    // ─── Form (edit benchmarks) ──────────────────────────
    protected function form()
    {
        $form = new Form(new KpiBenchmark());

        $form->display('id', 'ID');
        $form->divider('Facilitator KPI Benchmarks');

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

    protected function detail($id)
    {
        $show = new \Encore\Admin\Show(KpiBenchmark::findOrFail($id));
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
