<?php

namespace App\Admin\Controllers;

use App\Models\FfsSessionParticipant;
use App\Models\FfsTrainingSession;
use App\Models\User;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FfsSessionParticipantController extends AdminController
{
    use IpScopeable;

    protected $title = 'Session Attendance';

    protected function grid()
    {
        $grid = new Grid(new FfsSessionParticipant());

        $grid->model()->with(['session', 'user'])->orderBy('id', 'desc');

        // IP Scoping via session
        $ipId = $this->getAdminIpId();
        if ($ipId) {
            $grid->model()->whereHas('session', function ($q) use ($ipId) {
                $q->where('ip_id', $ipId);
            });
        }

        $grid->quickSearch(function ($model, $query) {
            $model->whereHas('user', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            });
        })->placeholder('Search by participant name');

        // Filters
        $grid->filter(function ($filter) use ($ipId) {
            $filter->disableIdFilter();

            $sessionQuery = FfsTrainingSession::orderBy('session_date', 'desc');
            if ($ipId) $sessionQuery->where('ip_id', $ipId);

            $filter->equal('session_id', 'Training Session')->select(
                $sessionQuery->limit(200)->get()
                    ->mapWithKeys(fn($s) => [$s->id => $s->title . ' (' . optional($s->session_date)->format('M d, Y') . ')'])
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
            'pending' => 'default',
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

        $show->panel()->style('primary')->title('Attendance Record');
        $show->field('id', 'ID');
        $show->field('session.title', 'Session');
        $show->field('session.session_date', 'Session Date');
        $show->field('user.name', 'Participant');
        $show->field('attendance_status', 'Attendance Status');
        $show->field('remarks', 'Remarks');
        $show->field('created_at', 'Created');

        return $show;
    }

    protected function form()
    {
        $form = new Form(new FfsSessionParticipant());

        $ipId = $this->getAdminIpId();

        // ── Session & Member ─────────────────────────────────────────────
        $form->row(function ($row) use ($ipId) {
            $sessionQuery = FfsTrainingSession::orderBy('session_date', 'desc');
            if ($ipId) $sessionQuery->where('ip_id', $ipId);

            $row->width(6)->select('session_id', 'Training Session')->options(
                $sessionQuery->limit(200)->get()
                    ->mapWithKeys(fn($s) => [$s->id => $s->title . ' (' . optional($s->session_date)->format('M d, Y') . ')'])
            )->required();

            // Show all members (Customer type) — regardless of group since sessions can span groups
            $memberQuery = User::where('user_type', 'Customer')->orderBy('name');
            if ($ipId) $memberQuery->where('ip_id', $ipId);

            $row->width(6)->select('user_id', 'Participant')->options(
                $memberQuery->pluck('name', 'id')
            )->required();
        });

        // ── Attendance Status ────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(6)->select('attendance_status', 'Attendance Status')
                ->options(FfsSessionParticipant::getAttendanceStatuses())
                ->default('present')
                ->required();
            $row->width(6)->textarea('remarks', 'Remarks')
                ->rows(2)
                ->placeholder('Optional notes on attendance');
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
