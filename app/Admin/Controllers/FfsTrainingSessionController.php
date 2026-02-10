<?php

namespace App\Admin\Controllers;

use App\Models\FfsTrainingSession;
use App\Models\FfsGroup;
use App\Models\User;
use App\Models\ImplementingPartner;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FfsTrainingSessionController extends AdminController
{
    use IpScopeable;

    protected $title = 'FFS Training Sessions';

    protected function grid()
    {
        $grid = new Grid(new FfsTrainingSession());

        $grid->model()->with(['group', 'facilitator'])->orderBy('session_date', 'desc');

        // IP Scoping: IP admins see only their own sessions
        $this->applyIpScope($grid);

        $grid->quickSearch('title', 'topic')->placeholder('Search by title or topic');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $this->addIpFilter($filter);
            $filter->equal('group_id', 'Group')->select(FfsGroup::pluck('name', 'id'));
            $filter->equal('facilitator_id', 'Facilitator')->select(
                User::where('user_type', 'Admin')->orWhere('user_type', 'Employee')->pluck('name', 'id')
            );
            $filter->equal('session_type', 'Type')->select(FfsTrainingSession::getSessionTypes());
            $filter->equal('status', 'Status')->select(FfsTrainingSession::getStatuses());
            $filter->between('session_date', 'Session Date')->date();
        });

        // Columns
        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('title', 'Title')->display(function ($title) {
            return "<strong>" . \Illuminate\Support\Str::limit($title, 30) . "</strong>";
        })->sortable();

        $grid->column('group.name', 'Group')->display(function ($name) {
            return $name ? "<span class='label label-info'>{$name}</span>" : '-';
        })->sortable();

        $grid->column('facilitator.name', 'Facilitator')->sortable();

        $grid->column('topic', 'Topic')->display(function ($topic) {
            return $topic ? \Illuminate\Support\Str::limit($topic, 25) : '-';
        });

        $grid->column('session_date', 'Date')->display(function ($date) {
            return date('M d, Y', strtotime($date));
        })->sortable();

        $grid->column('time_slot', 'Time')->display(function () {
            if (!$this->start_time) return '-';
            $start = date('H:i', strtotime($this->start_time));
            $end = $this->end_time ? date('H:i', strtotime($this->end_time)) : '';
            return $end ? "{$start} - {$end}" : $start;
        });

        $grid->column('session_type', 'Type')->label([
            'classroom' => 'primary',
            'field' => 'success',
            'demonstration' => 'warning',
            'workshop' => 'info',
        ])->sortable();

        $grid->column('attendance', 'Attendance')->display(function () {
            $present = $this->participants()->where('attendance_status', 'present')->count();
            $total = $this->participants()->count();
            $expected = $this->expected_participants;
            if ($total == 0 && $expected == 0) return '<span class="text-muted">-</span>';
            $color = $total > 0 && ($present / max($total, 1)) >= 0.75 ? 'success' : 'warning';
            return "<span class='label label-{$color}'>{$present}/{$total}" . ($expected > 0 ? " (exp: {$expected})" : '') . "</span>";
        });

        $grid->column('resolutions_count', 'Resolutions')->display(function () {
            $count = $this->resolutions()->count();
            return $count > 0 ? "<span class='label label-primary'>{$count}</span>" : '0';
        });

        $grid->column('status', 'Status')->label([
            'scheduled' => 'default',
            'ongoing' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
        ])->sortable();

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(FfsTrainingSession::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('title', 'Title');
        $show->field('group_name', 'Group');
        $show->field('facilitator_name', 'Facilitator');
        $show->field('topic', 'Topic');
        $show->field('description', 'Description');
        $show->field('session_date', 'Date');
        $show->field('start_time', 'Start Time');
        $show->field('end_time', 'End Time');
        $show->field('venue', 'Venue');
        $show->field('session_type_text', 'Session Type');
        $show->field('status_text', 'Status');
        $show->field('expected_participants', 'Expected Participants');
        $show->field('actual_participants', 'Actual Participants');
        $show->field('materials_used', 'Materials Used');
        $show->field('notes', 'Notes');
        $show->field('challenges', 'Challenges');
        $show->field('recommendations', 'Recommendations');
        $show->field('created_at', 'Created');

        return $show;
    }

    protected function form()
    {
        $form = new Form(new FfsTrainingSession());

        $form->text('title', 'Title')->required();
        $form->select('group_id', 'Group')->options(FfsGroup::pluck('name', 'id'))->required();
        $form->select('facilitator_id', 'Facilitator')->options(
            User::where('user_type', 'Admin')
                ->orWhere('user_type', 'Employee')
                ->pluck('name', 'id')
        );
        $form->text('topic', 'Topic');
        $form->textarea('description', 'Description')->rows(3);
        $form->date('session_date', 'Session Date')->required();
        $form->time('start_time', 'Start Time');
        $form->time('end_time', 'End Time');
        $form->text('venue', 'Venue');
        $form->select('session_type', 'Session Type')
            ->options(FfsTrainingSession::getSessionTypes())
            ->default('classroom');
        $form->select('status', 'Status')
            ->options(FfsTrainingSession::getStatuses())
            ->default('scheduled');
        $form->number('expected_participants', 'Expected Participants')->default(0);
        $form->textarea('materials_used', 'Materials Used')->rows(2);
        $form->textarea('notes', 'Notes')->rows(2);
        $form->textarea('challenges', 'Challenges')->rows(2);
        $form->textarea('recommendations', 'Recommendations')->rows(2);
        $form->image('photo', 'Photo')->removable();

        $form->hidden('created_by_id');
        $form->saving(function (Form $form) {
            if (!$form->model()->id) {
                $form->created_by_id = \Encore\Admin\Facades\Admin::user()->id;
            }
        });

        return $form;
    }
}
