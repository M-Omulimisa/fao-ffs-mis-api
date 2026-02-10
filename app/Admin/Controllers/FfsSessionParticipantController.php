<?php

namespace App\Admin\Controllers;

use App\Models\FfsSessionParticipant;
use App\Models\FfsTrainingSession;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FfsSessionParticipantController extends AdminController
{
    protected $title = 'Session Participants';

    protected function grid()
    {
        $grid = new Grid(new FfsSessionParticipant());

        $grid->model()->with(['session', 'user'])->orderBy('id', 'desc');
        $grid->quickSearch(function ($model, $query) {
            $model->whereHas('user', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            });
        })->placeholder('Search by participant name');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('session_id', 'Training Session')->select(
                FfsTrainingSession::orderBy('session_date', 'desc')
                    ->limit(100)
                    ->get()
                    ->pluck('title', 'id')
            );
            $filter->equal('attendance_status', 'Status')->select(
                FfsSessionParticipant::getAttendanceStatuses()
            );
        });

        // Columns
        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('session.title', 'Session')->display(function ($title) {
            return $title ? \Illuminate\Support\Str::limit($title, 30) : '-';
        })->sortable();

        $grid->column('session.session_date', 'Date')->display(function ($date) {
            return $date ? date('M d, Y', strtotime($date)) : '-';
        })->sortable();

        $grid->column('user.name', 'Participant')->sortable();

        $grid->column('attendance_status', 'Status')->label([
            'present' => 'success',
            'absent' => 'danger',
            'excused' => 'warning',
            'late' => 'info',
        ])->sortable();

        $grid->column('remarks', 'Remarks')->display(function ($val) {
            return $val ? \Illuminate\Support\Str::limit($val, 40) : '-';
        });

        $grid->column('created_at', 'Recorded')->display(function ($date) {
            return date('M d, Y H:i', strtotime($date));
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(FfsSessionParticipant::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('session.title', 'Session');
        $show->field('user.name', 'Participant');
        $show->field('attendance_status', 'Attendance Status');
        $show->field('remarks', 'Remarks');
        $show->field('created_at', 'Created');

        return $show;
    }

    protected function form()
    {
        $form = new Form(new FfsSessionParticipant());

        $form->select('session_id', 'Training Session')->options(
            FfsTrainingSession::orderBy('session_date', 'desc')
                ->limit(100)
                ->get()
                ->mapWithKeys(function ($s) {
                    return [$s->id => $s->title . ' (' . ($s->session_date ? $s->session_date->format('M d, Y') : '') . ')'];
                })
        )->required();

        $form->select('user_id', 'Participant')->options(
            User::where('user_type', 'Customer')->orderBy('name')->pluck('name', 'id')
        )->required();

        $form->select('attendance_status', 'Attendance Status')
            ->options(FfsSessionParticipant::getAttendanceStatuses())
            ->default('present');

        $form->textarea('remarks', 'Remarks')->rows(2);

        return $form;
    }
}
