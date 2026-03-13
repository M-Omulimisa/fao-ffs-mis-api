<?php

namespace App\Admin\Controllers;

use App\Models\FfsSessionResolution;
use App\Models\FfsTrainingSession;
use App\Models\User;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

/**
 * FfsSessionResolutionController — manages meeting resolutions / GAP items.
 *
 * Access tiers:
 *   Super Admin  → all resolutions (any IP)
 *   IP Manager   → resolutions linked to their IP's sessions
 *   Facilitator  → resolutions from their own sessions only
 */
class FfsSessionResolutionController extends AdminController
{
    use IpScopeable;

    protected $title = 'Meeting Resolutions (GAP)';

    // ─────────────────────────────────────────────────────────────────────────
    // GRID
    // ─────────────────────────────────────────────────────────────────────────

    protected function grid()
    {
        $grid = new Grid(new FfsSessionResolution());

        $grid->model()->with(['session', 'responsiblePerson'])->orderBy('id', 'desc');

        $currentAdmin  = Admin::user();
        $isSuperAdmin  = $this->isSuperAdmin();
        $isFacilitator = !$isSuperAdmin && $currentAdmin
            && $this->userHasRoleSlug($currentAdmin, 'facilitator');
        $ipId = $this->getAdminIpId();

        // ── Three-tier access ──────────────────────────────────────────────
        if ($isFacilitator) {
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
        $grid->quickSearch('resolution')->placeholder('Search resolution…');

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

            $filter->equal('gap_category', 'GAP Category')
                ->select(FfsSessionResolution::getGapCategories());

            $filter->equal('status', 'Status')
                ->select(FfsSessionResolution::getStatuses());

            $filter->between('target_date', 'Target Date')->date();
        });

        // ── Columns ───────────────────────────────────────────────────────
        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('session.title', 'Session')->display(function ($title) {
            return $title
                ? e(\Illuminate\Support\Str::limit($title, 28))
                : '—';
        })->sortable();

        $grid->column('resolution', 'Resolution')->display(function ($val) {
            return '<strong>' . e(\Illuminate\Support\Str::limit($val, 40)) . '</strong>';
        })->sortable();

        $grid->column('gap_category', 'GAP Category')->display(function ($cat) {
            $categories = FfsSessionResolution::getGapCategories();
            $label = $categories[$cat] ?? ucfirst($cat ?? '—');
            $colors = [
                'soil'      => '#8B4513',
                'water'     => '#1E90FF',
                'seeds'     => '#228B22',
                'pest'      => '#FF4500',
                'harvest'   => '#DAA520',
                'storage'   => '#708090',
                'marketing' => '#9932CC',
                'livestock' => '#CD853F',
                'other'     => '#808080',
            ];
            $bg = $colors[$cat] ?? '#808080';
            return "<span class='badge' style='background:{$bg};color:#fff'>" . e($label) . '</span>';
        })->sortable();

        $grid->column('responsiblePerson.name', 'Responsible')->display(function ($name) {
            return $name
                ? e($name)
                : '<span class="text-muted">Unassigned</span>';
        });

        $grid->column('target_date', 'Target Date')->display(function ($date) {
            if (!$date) return '—';
            $formatted = date('d M Y', strtotime($date));
            return $this->is_overdue
                ? "<span class='text-danger'><strong>{$formatted}</strong> <small>(Overdue)</small></span>"
                : $formatted;
        })->sortable();

        $grid->column('status', 'Status')->label([
            'pending'     => 'default',
            'in_progress' => 'warning',
            'completed'   => 'success',
            'cancelled'   => 'danger',
        ])->sortable();

        $grid->column('created_at', 'Created')->display(function ($date) {
            return date('d M Y', strtotime($date));
        })->sortable()->hide();

        return $grid;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETAIL
    // ─────────────────────────────────────────────────────────────────────────

    protected function detail($id)
    {
        $record = FfsSessionResolution::with(['session', 'responsiblePerson'])->findOrFail($id);

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
        $show->panel()->style('primary')->title('Resolution / GAP Detail');

        $show->field('id', 'ID');
        $show->divider();

        $show->field('session_id', 'Session')->as(function ($id) {
            $s = FfsTrainingSession::find($id);
            return $s ? e($s->title) . ' (' . optional($s->session_date)->format('d M Y') . ')' : "Session #{$id}";
        });
        $show->divider();

        $show->field('resolution',       'Resolution Summary');
        $show->field('description',      'Detailed Description')->unescape();
        $show->field('gap_category_text','GAP Category');
        $show->divider();

        $show->field('responsible_person_name', 'Responsible Person');
        $show->field('target_date',  'Target Date');
        $show->field('status_text',  'Status');
        $show->divider();

        $show->field('follow_up_notes', 'Follow-up Notes')->unescape();
        $show->field('completed_at',    'Completed At');
        $show->field('created_at',      'Created');

        return $show;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FORM
    // ─────────────────────────────────────────────────────────────────────────

    protected function form()
    {
        $form = new Form(new FfsSessionResolution());

        $currentAdmin  = Admin::user();
        $isSuperAdmin  = $this->isSuperAdmin();
        $isFacilitator = !$isSuperAdmin && $currentAdmin
            && $this->userHasRoleSlug($currentAdmin, 'facilitator');
        $ipId = $this->getAdminIpId();

        // ── Session & GAP Category ────────────────────────────────────────
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

            $row->width(8)->select('session_id', 'Training Session')
                ->options(
                    $sessionQuery->limit(200)->get()
                        ->mapWithKeys(fn($s) => [
                            $s->id => $s->title . ' (' . optional($s->session_date)->format('d M Y') . ')',
                        ])
                )
                ->required();

            $row->width(4)->select('gap_category', 'GAP Category')
                ->options(FfsSessionResolution::getGapCategories())
                ->default('other');
        });

        // ── Resolution summary ────────────────────────────────────────────
        $form->text('resolution', 'Resolution Summary')
            ->required()
            ->placeholder('e.g. "Farmers to adopt row planting to improve yields"')
            ->help('One-line summary of the resolution or GAP identified');

        // ── Detailed description ──────────────────────────────────────────
        $form->quill('description', 'Detailed Description')
            ->placeholder('Provide full context — why it matters, what was discussed, background…');

        // ── Assignment & Tracking ─────────────────────────────────────────
        $form->divider('Assignment & Tracking');

        $form->row(function ($row) use ($ipId) {
            $memberQuery = User::where('user_type', 'Customer')->orderBy('name');
            if ($ipId) {
                $memberQuery->where('ip_id', $ipId);
            }

            $row->width(4)->select('responsible_person_id', 'Responsible Person')
                ->options($memberQuery->pluck('name', 'id'))
                ->help('Group member responsible for follow-up');

            $row->width(4)->date('target_date', 'Target Date')
                ->default(date('Y-m-d', strtotime('+14 days')))
                ->help('Deadline for this resolution');

            $row->width(4)->select('status', 'Status')
                ->options(FfsSessionResolution::getStatuses())
                ->default('pending');
        });

        // ── Follow-up notes ───────────────────────────────────────────────
        $form->quill('follow_up_notes', 'Follow-up Notes')
            ->placeholder('Document progress updates, follow-up actions, and observations…');

        // ── Save logic ────────────────────────────────────────────────────
        $form->hidden('created_by_id');
        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->input('created_by_id', Admin::user()->id);
            }
            // Auto-set completed_at when status transitions to completed
            if ($form->status === 'completed' && !$form->model()->completed_at) {
                $form->input('completed_at', now()->toDateTimeString());
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
