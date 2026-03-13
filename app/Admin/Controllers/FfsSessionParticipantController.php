<?php

namespace App\Admin\Controllers;

use App\Models\FfsSessionParticipant;
use App\Models\FfsTrainingSession;
use App\Models\User;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

/**
 * FfsSessionParticipantController — manages training session attendance.
 *
 * Access tiers:
 *   Super Admin  → all attendance records (any IP)
 *   IP Manager   → records linked to their IP's sessions
 *   Facilitator  → records from their own sessions only
 */
class FfsSessionParticipantController extends AdminController
{
    use IpScopeable;

    protected $title = 'Session Attendance';

    // ─────────────────────────────────────────────────────────────────────────
    // GRID
    // ─────────────────────────────────────────────────────────────────────────

    protected function grid()
    {
        $grid = new Grid(new FfsSessionParticipant());

        $grid->model()->with(['session', 'user'])->orderBy('id', 'desc');

        $currentAdmin  = Admin::user();
        $isSuperAdmin  = $this->isSuperAdmin();
        $isFacilitator = !$isSuperAdmin && $currentAdmin
            && $this->userHasRoleSlug($currentAdmin, 'facilitator');
        $ipId = $this->getAdminIpId();

        // ── Three-tier access ──────────────────────────────────────────────
        if ($isFacilitator) {
            // Facilitators see only attendance records for sessions they lead
            $grid->model()->whereHas('session', function ($q) use ($currentAdmin) {
                $q->where('facilitator_id', $currentAdmin->id)
                  ->orWhere('co_facilitator_id', $currentAdmin->id);
            });
            $grid->disableCreateButton();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
            });
        } elseif (!$isSuperAdmin && $ipId) {
            $grid->model()->whereHas('session', function ($q) use ($ipId) {
                $q->where('ip_id', $ipId);
            });
        }

        // ── Quick search ──────────────────────────────────────────────────
        $grid->quickSearch(function ($model, $query) {
            $model->whereHas('user', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone_number', 'like', "%{$query}%");
            })->orWhereHas('session', function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%");
            });
        })->placeholder('Search participant name, phone or session…');

        // ── Filters ───────────────────────────────────────────────────────
        $grid->filter(function ($filter) use ($ipId, $isSuperAdmin, $isFacilitator, $currentAdmin) {
            $filter->disableIdFilter();

            $sessionQuery = FfsTrainingSession::orderBy('session_date', 'desc');
            if ($isFacilitator) {
                $sessionQuery->where(function ($q) use ($currentAdmin) {
                    $q->where('facilitator_id', $currentAdmin->id)
                      ->orWhere('co_facilitator_id', $currentAdmin->id);
                });
            } elseif ($ipId) {
                $sessionQuery->where('ip_id', $ipId);
            }

            $filter->equal('session_id', 'Training Session')->select(
                $sessionQuery->limit(200)->get()
                    ->mapWithKeys(fn($s) => [
                        $s->id => $s->title . ' (' . optional($s->session_date)->format('d M Y') . ')',
                    ])
            );

            $filter->equal('attendance_status', 'Status')
                ->select(FfsSessionParticipant::getAttendanceStatuses());
        });

        // ── Columns ───────────────────────────────────────────────────────
        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('session.title', 'Session')->display(function ($title) {
            return $title
                ? '<strong>' . e(\Illuminate\Support\Str::limit($title, 30)) . '</strong>'
                : '—';
        })->sortable();

        $grid->column('session.session_date', 'Date')->display(function ($date) {
            return $date ? date('d M Y', strtotime($date)) : '—';
        })->sortable();

        $grid->column('user.name', 'Participant')->display(function ($name) {
            return $name ? e($name) : '—';
        })->sortable();

        $grid->column('user.phone_number', 'Phone')->display(function () {
            return $this->user ? e($this->user->phone_number) : '—';
        });

        $grid->column('attendance_status', 'Status')->label([
            'present' => 'success',
            'absent'  => 'danger',
            'excused' => 'warning',
            'late'    => 'info',
            'pending' => 'default',
        ])->sortable();

        $grid->column('remarks', 'Remarks')->display(function ($val) {
            return $val ? e(\Illuminate\Support\Str::limit($val, 40)) : '—';
        });

        $grid->column('created_at', 'Recorded')->display(function ($date) {
            return date('d M Y H:i', strtotime($date));
        })->sortable();

        return $grid;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETAIL
    // ─────────────────────────────────────────────────────────────────────────

    protected function detail($id)
    {
        $record = FfsSessionParticipant::with(['session', 'user'])->findOrFail($id);

        // Access control
        if (!$this->isSuperAdmin()) {
            $currentAdmin  = Admin::user();
            $isFacilitator = $this->userHasRoleSlug($currentAdmin, 'facilitator');

            if ($isFacilitator) {
                $session = $record->session;
                if (
                    !$session ||
                    ((int) $session->facilitator_id    !== (int) $currentAdmin->id &&
                     (int) $session->co_facilitator_id !== (int) $currentAdmin->id)
                ) {
                    return $this->denyIpAccess();
                }
            } elseif ($record->session && !$this->verifyIpAccess($record->session)) {
                return $this->denyIpAccess();
            }
        }

        $show = new Show($record);
        $show->panel()->style('primary')->title('Attendance Record');

        $show->field('id', 'ID');
        $show->divider();

        $show->field('session_id', 'Session')->as(function ($id) {
            $s = FfsTrainingSession::find($id);
            return $s ? e($s->title) . ' (' . optional($s->session_date)->format('d M Y') . ')' : "Session #{$id}";
        });
        $show->field('session.session_date', 'Session Date')->as(function ($d) {
            return $d ? date('d M Y', strtotime($d)) : '—';
        });
        $show->divider();

        $show->field('user_id', 'Participant')->as(function ($id) {
            $u = User::find($id);
            return $u ? e($u->name) . ' (' . e($u->phone_number) . ')' : "User #{$id}";
        });

        $show->field('attendance_status', 'Attendance Status')->as(function ($v) {
            return FfsSessionParticipant::getAttendanceStatuses()[$v] ?? ucfirst($v);
        });
        $show->field('remarks', 'Remarks');
        $show->field('created_at', 'Recorded At');

        return $show;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FORM
    // ─────────────────────────────────────────────────────────────────────────

    protected function form()
    {
        $form = new Form(new FfsSessionParticipant());

        $currentAdmin  = Admin::user();
        $isSuperAdmin  = $this->isSuperAdmin();
        $isFacilitator = !$isSuperAdmin && $currentAdmin
            && $this->userHasRoleSlug($currentAdmin, 'facilitator');
        $ipId = $this->getAdminIpId();

        // ── Session & Member ──────────────────────────────────────────────
        $form->row(function ($row) use ($ipId, $isFacilitator, $currentAdmin) {
            $sessionQuery = FfsTrainingSession::orderBy('session_date', 'desc');

            if ($isFacilitator) {
                $sessionQuery->where(function ($q) use ($currentAdmin) {
                    $q->where('facilitator_id', $currentAdmin->id)
                      ->orWhere('co_facilitator_id', $currentAdmin->id);
                });
            } elseif ($ipId) {
                $sessionQuery->where('ip_id', $ipId);
            }

            $row->width(6)->select('session_id', 'Training Session')
                ->options(
                    $sessionQuery->limit(200)->get()
                        ->mapWithKeys(fn($s) => [
                            $s->id => $s->title . ' (' . optional($s->session_date)->format('d M Y') . ')',
                        ])
                )
                ->required();

            $memberQuery = User::where('user_type', 'Customer')->orderBy('name');
            if ($ipId) {
                $memberQuery->where('ip_id', $ipId);
            }

            $row->width(6)->select('user_id', 'Participant')
                ->options($memberQuery->pluck('name', 'id'))
                ->required()
                ->help('Group member attending this session');
        });

        // ── Attendance ────────────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(6)->select('attendance_status', 'Attendance Status')
                ->options(FfsSessionParticipant::getAttendanceStatuses())
                ->default('present')
                ->required();
            $row->width(6)->textarea('remarks', 'Remarks')
                ->rows(3)
                ->placeholder('Optional notes about this participant\'s attendance');
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
