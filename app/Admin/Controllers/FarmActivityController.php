<?php

namespace App\Admin\Controllers;

use App\Models\Farm;
use App\Models\FarmActivity;
use App\Models\ProductionProtocol;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FarmActivityController extends AdminController
{
    protected $title = 'Farm Activities';

    protected function grid()
    {
        $grid = new Grid(new FarmActivity());

        $grid->model()->with(['farm', 'farm.user', 'farm.enterprise', 'protocol'])
            ->orderBy('scheduled_date', 'asc');
        $grid->quickSearch('activity_name')->placeholder('Search by activity name...');

        // Columns
        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('farm.name', 'Farm')->display(function ($name) {
            return $name ? "<strong>{$name}</strong>" : '-';
        })->sortable();

        $grid->column('farm.user.name', 'Farmer')->sortable();

        $grid->column('farm.enterprise.name', 'Enterprise')->display(function ($name) {
            return $name ? "<span class='label label-info'>{$name}</span>" : '-';
        });

        $grid->column('activity_name', 'Activity')->sortable();

        $grid->column('scheduled_week', 'Week')->display(function ($w) {
            return "Wk {$w}";
        })->label('info')->sortable();

        $grid->column('scheduled_date', 'Scheduled')->display(function ($date) {
            return $date ? date('M d, Y', strtotime($date)) : '-';
        })->sortable();

        $grid->column('actual_completion_date', 'Completed')->display(function ($date) {
            return $date ? date('M d, Y', strtotime($date)) : '-';
        });

        $grid->column('status', 'Status')->label([
            'pending' => 'default',
            'done' => 'success',
            'skipped' => 'warning',
            'overdue' => 'danger',
        ])->sortable();

        $grid->column('scoring_info', 'Score/Weight')->display(function () {
            $weight = str_repeat('★', min($this->weight, 5));
            $mandatory = $this->is_mandatory ? '<span class="text-danger">M</span>' : '<span class="text-muted">O</span>';
            return "{$this->score}% {$mandatory} {$weight}";
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('farm_id', 'Farm')
                ->select(Farm::orderBy('name')->pluck('name', 'id'));
            $filter->equal('status', 'Status')->select([
                'pending' => 'Pending',
                'done' => 'Done',
                'skipped' => 'Skipped',
                'overdue' => 'Overdue',
            ]);
            $filter->equal('is_mandatory', 'Type')->select([
                1 => 'Mandatory',
                0 => 'Optional',
            ]);
            $filter->between('scheduled_date', 'Scheduled Date')->date();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(FarmActivity::findOrFail($id));

        $show->panel()->style('primary')->title('Farm Activity Details');

        $show->field('id', 'ID');
        $show->field('farm.name', 'Farm');
        $show->field('farm.user.name', 'Farmer');
        $show->field('activity_name', 'Activity');
        $show->field('activity_description', 'Instructions')->unescape();
        $show->divider();

        $show->field('scheduled_week', 'Scheduled Week');
        $show->field('scheduled_date', 'Scheduled Date');
        $show->field('actual_completion_date', 'Actual Completion Date');
        $show->field('status', 'Status');
        $show->divider();

        $show->field('is_mandatory', 'Mandatory')->as(function ($m) { return $m ? 'Yes' : 'No'; });
        $show->field('weight', 'Scoring Weight');
        $show->field('target_value', 'Target Value');
        $show->field('actual_value', 'Actual Value');
        $show->field('score', 'Score')->as(function ($s) { return $s . '%'; });
        $show->divider();

        $show->field('notes', 'Notes')->unescape();
        $show->field('photo', 'Photo')->image();
        $show->field('is_custom', 'Custom Activity')->as(function ($c) { return $c ? 'Yes (added manually)' : 'No (from protocol)'; });
        $show->field('protocol.activity_name', 'Source Protocol');
        $show->field('created_at', 'Created');

        return $show;
    }

    protected function form()
    {
        $form = new Form(new FarmActivity());

        // ── Farm & Activity ──────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(6)->select('farm_id', 'Farm')
                ->options(
                    Farm::with('user')->orderBy('name')->get()
                        ->mapWithKeys(fn($f) => [$f->id => $f->name . ' – ' . ($f->user ? $f->user->name : 'Unknown')])
                )
                ->rules('required');
            $row->width(6)->text('activity_name', 'Activity Name')
                ->rules('required|max:255')
                ->placeholder('e.g. Land Preparation, First Weeding');
        });

        // ── Rich description ─────────────────────────────────────────────
        $form->quill('activity_description', 'Activity Instructions')
            ->placeholder('Describe what the farmer should do, tools needed, best practices...');

        // ── Schedule & Status ────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(3)->number('scheduled_week', 'Scheduled Week')
                ->default(1)
                ->rules('required|integer|min:0');
            $row->width(3)->date('scheduled_date', 'Scheduled Date')
                ->default(date('Y-m-d'))
                ->rules('required');
            $row->width(3)->date('actual_completion_date', 'Actual Completion')
                ->help('Date activity was actually done');
            $row->width(3)->select('status', 'Status')
                ->options([
                    'pending' => 'Pending',
                    'done' => 'Done',
                    'skipped' => 'Skipped',
                    'overdue' => 'Overdue',
                ])
                ->default('pending')
                ->rules('required');
        });

        // ── Scoring ──────────────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(3)->switch('is_mandatory', 'Mandatory')->default(0);
            $row->width(3)->number('weight', 'Scoring Weight')
                ->default(1)
                ->rules('required|integer|min:1|max:10')
                ->help('Impact on overall farm score');
            $row->width(3)->decimal('target_value', 'Target Value')
                ->placeholder('Expected quantity/measure');
            $row->width(3)->decimal('actual_value', 'Actual Value')
                ->placeholder('Achieved quantity/measure');
        });

        $form->row(function ($row) {
            $row->width(4)->decimal('score', 'Score (%)')
                ->default(0)
                ->rules('nullable|numeric|min:0|max:100');
            $row->width(4)->switch('is_custom', 'Custom Activity')
                ->default(0)
                ->help('Check if this was added manually (not from protocol)');
            $row->width(4)->select('production_protocol_id', 'Source Protocol')
                ->options(
                    ProductionProtocol::where('is_active', true)->orderBy('activity_name')
                        ->pluck('activity_name', 'id')
                )
                ->help('Link to the protocol template');
        });

        // ── Notes & Media ────────────────────────────────────────────────
        $form->quill('notes', 'Notes & Observations')
            ->placeholder('Record any observations, issues, or outcomes from this activity...');

        $form->image('photo', 'Activity Photo')->removable()->uniqueName();

        // ── Saving logic ────────────────────────────────────────────────
        $form->saving(function (Form $form) {
            // Auto-calculate score when marked as done
            if ($form->status === 'done') {
                $targetVal = (float)($form->target_value ?? 0);
                $actualVal = (float)($form->actual_value ?? 0);
                if ($targetVal > 0 && $actualVal > 0) {
                    $score = min(100, round(($actualVal / $targetVal) * 100, 2));
                    $form->input('score', $score);
                } elseif (empty($form->score) || $form->score == 0) {
                    $form->input('score', 100); // Done = 100% if no target/actual
                }

                // Auto-set completion date
                if (empty($form->actual_completion_date)) {
                    $form->input('actual_completion_date', date('Y-m-d'));
                }
            }
        });

        $form->saved(function (Form $form) {
            // Recalculate farm totals after any activity change
            $farm = Farm::find($form->model()->farm_id);
            if ($farm) {
                $total = $farm->activities()->count();
                $completed = $farm->activities()->where('status', 'done')->count();
                $weightedScore = $farm->activities()
                    ->selectRaw('SUM(score * weight) as weighted_score, SUM(weight) as total_weight')
                    ->first();
                $overallScore = ($weightedScore->total_weight > 0)
                    ? round($weightedScore->weighted_score / $weightedScore->total_weight, 2)
                    : 0;

                $farm->update([
                    'total_activities_count' => $total,
                    'completed_activities_count' => $completed,
                    'overall_score' => $overallScore,
                ]);
            }
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
