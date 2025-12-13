<?php

namespace App\Admin\Controllers;

use App\Models\VslaActionPlan;
use App\Models\Project;
use App\Models\VslaMeeting;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VslaActionPlanController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'VSLA Action Plans';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VslaActionPlan());

        $grid->model()->with(['cycle', 'meeting', 'assignedTo', 'creator'])->orderBy('id', 'desc');
        
        $grid->disableCreateButton(); // Action plans created from meetings
        $grid->disableExport();

        $grid->quickSearch('action')->placeholder('Search by action');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->equal('cycle_id', 'Cycle')
                ->select(Project::where('is_vsla_cycle', 'Yes')->pluck('title', 'id'));

            $filter->equal('meeting_id', 'Meeting')
                ->select(VslaMeeting::orderBy('id', 'desc')->limit(50)->pluck('meeting_number', 'id'));

            $filter->equal('assigned_to_member_id', 'Assigned To')
                ->select(User::orderBy('name')->pluck('name', 'id'));

            $filter->equal('status', 'Status')->select([
                'pending' => 'Pending',
                'in-progress' => 'In Progress',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ]);

            $filter->equal('priority', 'Priority')->select([
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
            ]);

            $filter->between('due_date', 'Due Date')->date();
        });

        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('cycle.ffs_group.name', __('VSLA Group'))
            ->display(function () {
                if (!$this->cycle || !$this->cycle->ffs_group) {
                    return '<span class="label label-default">No Group</span>';
                }
                $code = $this->cycle->ffs_group->code ?? '';
                $name = \Illuminate\Support\Str::limit($this->cycle->ffs_group->name, 18);
                return "<span class='label label-info'>{$name}" . ($code ? " ({$code})" : '') . "</span>";
            });
        
        $grid->column('cycle.title', __('Cycle'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 20);
            });

        $grid->column('meeting.meeting_number', __('Meeting #'))
            ->display(function ($number) {
                return "<strong>#{$number}</strong>";
            })
            ->sortable();

        $grid->column('action', __('Action'))
            ->display(function ($action) {
                return \Illuminate\Support\Str::limit($action, 35);
            })
            ->sortable();

        $grid->column('description', __('Description'))
            ->display(function ($description) {
                if (!$description) return '<span class="text-muted">-</span>';
                $truncated = mb_strlen($description) > 100 ? mb_substr($description, 0, 100) . '...' : $description;
                return e($truncated);
            })
            ->width(250);

        $grid->column('assignedTo.name', __('Assigned To'))
            ->display(function ($name) {
                return \Illuminate\Support\Str::limit($name, 25);
            });

        $grid->column('priority', __('Priority'))
            ->display(function ($priority) {
                $icons = [
                    'low' => '▼',
                    'medium' => '■',
                    'high' => '▲',
                ];
                $colors = [
                    'low' => 'info',
                    'medium' => 'warning',
                    'high' => 'danger',
                ];
                $icon = $icons[$priority] ?? '■';
                $color = $colors[$priority] ?? 'default';
                return "<span class='label label-{$color}'>{$icon} " . ucfirst($priority) . "</span>";
            })
            ->sortable();

        $grid->column('due_date', __('Due Date & Status'))
            ->display(function ($date) {
                $dueDate = date('M d, Y', strtotime($date));
                $today = strtotime('today');
                $due = strtotime($date);
                $diff = floor(($due - $today) / 86400);
                
                if ($this->status === 'completed') {
                    return "<div><small>{$dueDate}</small><br><span class='label label-success'>✓ Completed</span></div>";
                } elseif ($this->status === 'cancelled') {
                    return "<div><small>{$dueDate}</small><br><span class='label label-default'>Cancelled</span></div>";
                } elseif ($diff < 0) {
                    $days = abs($diff);
                    return "<div><small>{$dueDate}</small><br><span class='label label-danger'>⚠ {$days} days overdue</span></div>";
                } elseif ($diff <= 3) {
                    return "<div><small>{$dueDate}</small><br><span class='label label-warning'>⏰ Due in {$diff} days</span></div>";
                } else {
                    return "<div><small>{$dueDate}</small><br><span class='label label-info'>{$diff} days remaining</span></div>";
                }
            })
            ->sortable();

        $grid->column('status', __('Progress'))
            ->display(function ($status) {
                $labels = [
                    'pending' => ['⭕', 'warning', 'Not Started'],
                    'in-progress' => ['▶', 'primary', 'In Progress'],
                    'completed' => ['✓', 'success', 'Done'],
                    'cancelled' => ['✗', 'default', 'Cancelled'],
                ];
                $info = $labels[$status] ?? ['■', 'default', ucfirst($status)];
                return "<span class='label label-{$info[1]}'>{$info[0]} {$info[2]}</span>";
            })
            ->sortable();

        $grid->column('completed_at', __('Completed'))
            ->display(function ($date) {
                return $date ? date('M d, Y', strtotime($date)) : '-';
            })
            ->sortable();

        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('M d, Y', strtotime($date));
            })
            ->sortable();

        $grid->actions(function ($actions) {
            $actions->disableEdit(); // Action plans are historical data
            $actions->disableDelete();
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(VslaActionPlan::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('local_id', __('Local ID'));
        
        $show->divider('Action Plan Details');
        $show->field('cycle.title', __('Cycle'));
        $show->field('meeting.meeting_number', __('Meeting Number'));
        $show->field('meeting.meeting_date', __('Meeting Date'));
        
        $show->field('action', __('Action'));
        $show->field('description', __('Description'));
        
        $show->divider('Assignment');
        $show->field('assignedTo.name', __('Assigned To'));
        $show->field('assignedTo.phone_number', __('Phone'));
        $show->field('assignedTo.email', __('Email'));
        
        $show->field('priority', __('Priority'));
        $show->field('due_date', __('Due Date'));
        
        $show->divider('Status');
        $show->field('status', __('Status'));
        $show->field('is_overdue', __('Is Overdue'))->as(function ($val) {
            return $val ? 'Yes' : 'No';
        });
        $show->field('days_overdue', __('Days Overdue'));
        
        $show->field('completion_notes', __('Completion Notes'));
        $show->field('completed_at', __('Completed At'));
        
        $show->field('creator.name', __('Created By'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new VslaActionPlan());

        // Action plans should not be manually created or edited
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->display('id', __('ID'));
        $form->display('action', __('Action'));
        $form->display('assignedTo.name', __('Assigned To'));
        $form->display('status', __('Status'));

        return $form;
    }
}
