<?php

namespace App\Admin\Controllers;

use App\Models\Farm;
use App\Models\Enterprise;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FarmController extends AdminController
{
    protected $title = 'Farmer Farms / Plots';

    protected function grid()
    {
        $grid = new Grid(new Farm());

        $grid->model()->with(['enterprise', 'user'])->orderBy('created_at', 'desc');
        $grid->quickSearch('name')->placeholder('Search by farm name...');

        // Columns
        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('name', 'Farm Name')->display(function ($name) {
            return "<strong>{$name}</strong>";
        })->sortable();

        $grid->column('enterprise.name', 'Enterprise')->display(function ($name) {
            return $name ? "<span class='label label-info'>{$name}</span>" : '-';
        })->sortable();

        $grid->column('user.name', 'Farmer')->sortable();

        $grid->column('status', 'Status')->label([
            'planning' => 'default',
            'active' => 'success',
            'completed' => 'primary',
            'abandoned' => 'danger',
        ])->sortable();

        $grid->column('start_date', 'Started')->display(function ($date) {
            return $date ? date('M d, Y', strtotime($date)) : '-';
        })->sortable();

        $grid->column('progress', 'Progress')->display(function () {
            $total = $this->total_activities_count ?: 0;
            $completed = $this->completed_activities_count ?: 0;
            $pct = $total > 0 ? round(($completed / $total) * 100) : 0;
            $color = $pct >= 75 ? 'success' : ($pct >= 40 ? 'warning' : 'danger');
            return "<div class='progress' style='margin:0;min-width:80px'>"
                . "<div class='progress-bar progress-bar-{$color}' style='width:{$pct}%'>{$completed}/{$total}</div>"
                . "</div>";
        });

        $grid->column('overall_score', 'Score')->display(function ($score) {
            $color = $score >= 75 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
            return "<span class='label label-{$color}'>{$score}%</span>";
        })->sortable();

        $grid->column('location_text', 'Location')->display(function ($loc) {
            return $loc ? \Illuminate\Support\Str::limit($loc, 25) : '-';
        });

        $grid->column('is_active', 'Active')->switch([
            'on' => ['value' => 1, 'text' => 'Yes', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'No', 'color' => 'danger'],
        ]);

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', 'Farm Name');
            $filter->equal('enterprise_id', 'Enterprise')
                ->select(Enterprise::where('is_active', true)->pluck('name', 'id'));
            $filter->equal('user_id', 'Farmer')
                ->select(User::where('user_type', 'Customer')->orderBy('name')->pluck('name', 'id'));
            $filter->equal('status', 'Status')->select([
                'planning' => 'Planning',
                'active' => 'Active',
                'completed' => 'Completed',
                'abandoned' => 'Abandoned',
            ]);
            $filter->between('start_date', 'Start Date')->date();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Farm::findOrFail($id));

        $show->panel()->style('primary')->title('Farm / Plot Details');

        $show->field('id', 'ID');
        $show->field('name', 'Farm Name');
        $show->field('enterprise.name', 'Enterprise');
        $show->field('user.name', 'Farmer');
        $show->field('description', 'Description')->unescape();
        $show->divider();

        $show->field('status', 'Status');
        $show->field('start_date', 'Start Date');
        $show->field('expected_end_date', 'Expected End Date');
        $show->field('actual_end_date', 'Actual End Date');
        $show->divider();

        $show->field('location_text', 'Location');
        $show->field('gps_latitude', 'GPS Latitude');
        $show->field('gps_longitude', 'GPS Longitude');
        $show->field('photo', 'Photo')->image();
        $show->divider();

        $show->field('overall_score', 'Overall Score')->as(function ($s) { return $s . '%'; });
        $show->field('completed_activities_count', 'Completed Activities');
        $show->field('total_activities_count', 'Total Activities');
        $show->field('is_active', 'Active')->as(function ($a) { return $a ? 'Yes' : 'No'; });
        $show->field('created_at', 'Created');

        // Show farm activities inline
        $show->activities('Farm Activities', function ($activities) {
            $activities->disableCreateButton();
            $activities->disableExport();
            $activities->disableBatchActions();

            $activities->column('activity_name', 'Activity');
            $activities->column('scheduled_week', 'Week')->label('info');
            $activities->column('scheduled_date', 'Scheduled')->display(function ($d) {
                return $d ? date('M d', strtotime($d)) : '-';
            });
            $activities->column('status', 'Status')->label([
                'pending' => 'default', 'done' => 'success',
                'skipped' => 'warning', 'overdue' => 'danger',
            ]);
            $activities->column('score', 'Score')->display(function ($s) {
                return $s . '%';
            });
            $activities->column('is_mandatory', 'Type')->display(function ($m) {
                return $m ? 'Mandatory' : 'Optional';
            })->label(['Mandatory' => 'danger', 'Optional' => 'success']);
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Farm());

        // ── Farm Identity ────────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(6)->text('name', 'Farm / Plot Name')
                ->rules('required|max:255')
                ->placeholder('e.g. Cassava Plot A, Goat Pen 1');
            $row->width(6)->select('enterprise_id', 'Enterprise')
                ->options(Enterprise::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                ->rules('required');
        });

        $form->row(function ($row) {
            $row->width(6)->select('user_id', 'Farmer / Owner')
                ->options(User::where('user_type', 'Customer')->orderBy('name')->pluck('name', 'id'))
                ->rules('required')
                ->help('Select the farmer who owns this farm/plot');
            $row->width(6)->select('status', 'Status')
                ->options([
                    'planning' => 'Planning',
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'abandoned' => 'Abandoned',
                ])
                ->default('planning')
                ->rules('required');
        });

        // ── Rich description ─────────────────────────────────────────────
        $form->quill('description', 'Farm Description')
            ->placeholder('Describe the farm: size, soil type, irrigation, history, any special notes...');

        // ── Dates ────────────────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(4)->date('start_date', 'Start Date')
                ->default(date('Y-m-d'))
                ->rules('required');
            $row->width(4)->date('expected_end_date', 'Expected End Date')
                ->default(date('Y-m-d', strtotime('+12 months')))
                ->rules('required');
            $row->width(4)->date('actual_end_date', 'Actual End Date')
                ->help('Fill when farm completes');
        });

        // ── Location & Media ─────────────────────────────────────────────
        $form->divider('Location & Media');

        $form->row(function ($row) {
            $row->width(4)->text('location_text', 'Location Description')
                ->placeholder('e.g. Behind the school, near river bank');
            $row->width(4)->decimal('gps_latitude', 'GPS Latitude')
                ->placeholder('e.g. 0.347596');
            $row->width(4)->decimal('gps_longitude', 'GPS Longitude')
                ->placeholder('e.g. 32.582520');
        });

        $form->row(function ($row) {
            $row->width(6)->image('photo', 'Farm Photo')->removable()->uniqueName();
            $row->width(6)->switch('is_active', 'Active')->default(1);
        });

        // ── Scoring (read-only in admin, auto-calculated) ────────────────
        if ($form->isEditing()) {
            $form->divider('Scoring (Auto-calculated)');
            $form->row(function ($row) {
                $row->width(4)->number('overall_score', 'Overall Score (%)')->disable();
                $row->width(4)->number('completed_activities_count', 'Completed Activities')->disable();
                $row->width(4)->number('total_activities_count', 'Total Activities')->disable();
            });
        }

        // ── Saving logic ────────────────────────────────────────────────
        $form->saved(function (Form $form) {
            // If new farm, auto-generate activities from enterprise protocols
            if ($form->isCreating()) {
                $farm = $form->model();
                $enterprise = Enterprise::with('productionProtocols')->find($farm->enterprise_id);
                if ($enterprise) {
                    $protocols = $enterprise->productionProtocols()
                        ->where('is_active', true)
                        ->orderBy('start_time')
                        ->get();

                    $startDate = $farm->start_date ?? now();
                    $totalActivities = 0;

                    foreach ($protocols as $protocol) {
                        $scheduledDate = $startDate->copy()->addWeeks($protocol->start_time);

                        $farm->activities()->create([
                            'production_protocol_id' => $protocol->id,
                            'activity_name' => $protocol->activity_name,
                            'activity_description' => $protocol->activity_description,
                            'scheduled_date' => $scheduledDate,
                            'scheduled_week' => $protocol->start_time,
                            'status' => 'pending',
                            'is_mandatory' => $protocol->is_compulsory,
                            'weight' => $protocol->weight,
                        ]);
                        $totalActivities++;
                    }

                    // Update totals
                    $farm->update([
                        'total_activities_count' => $totalActivities,
                        'completed_activities_count' => 0,
                    ]);
                }
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
