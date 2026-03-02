<?php

namespace App\Admin\Controllers;

use App\Models\FfsSessionResolution;
use App\Models\FfsTrainingSession;
use App\Models\FfsGroup;
use App\Models\User;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FfsSessionResolutionController extends AdminController
{
    use IpScopeable;

    protected $title = 'Meeting Resolutions (GAP)';

    protected function grid()
    {
        $grid = new Grid(new FfsSessionResolution());

        $grid->model()->with(['session', 'responsiblePerson'])->orderBy('id', 'desc');

        // IP Scoping via session's ip_id
        $ipId = $this->getAdminIpId();
        if ($ipId) {
            $grid->model()->whereHas('session', function ($q) use ($ipId) {
                $q->where('ip_id', $ipId);
            });
        }

        $grid->quickSearch('resolution')->placeholder('Search by resolution');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('session_id', 'Training Session')->select(
                FfsTrainingSession::orderBy('session_date', 'desc')
                    ->limit(200)
                    ->get()
                    ->mapWithKeys(fn($s) => [$s->id => $s->title . ' (' . optional($s->session_date)->format('M d, Y') . ')'])
            );
            $filter->equal('gap_category', 'GAP Category')->select(
                FfsSessionResolution::getGapCategories()
            );
            $filter->equal('status', 'Status')->select(
                FfsSessionResolution::getStatuses()
            );
            $filter->between('target_date', 'Target Date')->date();
        });

        // Columns
        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('session.title', 'Session')->display(function ($title) {
            return $title ? \Illuminate\Support\Str::limit($title, 25) : '-';
        })->sortable();

        $grid->column('resolution', 'Resolution')->display(function ($val) {
            return "<strong>" . \Illuminate\Support\Str::limit($val, 35) . "</strong>";
        })->sortable();

        $grid->column('gap_category', 'GAP Category')->display(function ($cat) {
            $categories = FfsSessionResolution::getGapCategories();
            $label = $categories[$cat] ?? ucfirst($cat ?? '-');
            $colors = [
                'soil' => '#8B4513', 'water' => '#1E90FF', 'seeds' => '#228B22',
                'pest' => '#FF4500', 'harvest' => '#DAA520', 'storage' => '#708090',
                'marketing' => '#9932CC', 'livestock' => '#CD853F', 'other' => '#808080',
            ];
            $color = $colors[$cat] ?? '#808080';
            return "<span class='badge' style='background:{$color}'>{$label}</span>";
        })->sortable();

        $grid->column('responsiblePerson.name', 'Responsible')->display(function ($name) {
            return $name ?? '<span class="text-muted">Unassigned</span>';
        });

        $grid->column('target_date', 'Target Date')->display(function ($date) {
            if (!$date) return '-';
            $formatted = date('M d, Y', strtotime($date));
            $isOverdue = $this->is_overdue;
            return $isOverdue ? "<span class='text-danger'><strong>{$formatted}</strong> (Overdue)</span>" : $formatted;
        })->sortable();

        $grid->column('status', 'Status')->label([
            'pending' => 'default',
            'in_progress' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
        ])->sortable();

        $grid->column('created_at', 'Created')->display(function ($date) {
            return date('M d, Y', strtotime($date));
        })->sortable();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(FfsSessionResolution::findOrFail($id));

        $show->panel()->style('primary')->title('Resolution / GAP Detail');

        $show->field('id', 'ID');
        $show->field('session.title', 'Session');
        $show->field('resolution', 'Resolution');
        $show->field('description', 'Detailed Description')->unescape();
        $show->field('gap_category_text', 'GAP Category');
        $show->field('responsible_person_name', 'Responsible Person');
        $show->field('target_date', 'Target Date');
        $show->field('status_text', 'Status');
        $show->field('follow_up_notes', 'Follow-up Notes')->unescape();
        $show->field('completed_at', 'Completed At');
        $show->field('created_at', 'Created');

        return $show;
    }

    protected function form()
    {
        $form = new Form(new FfsSessionResolution());

        $ipId = $this->getAdminIpId();

        // ── Session & Category ───────────────────────────────────────────
        $form->row(function ($row) use ($ipId) {
            $sessionQuery = FfsTrainingSession::orderBy('session_date', 'desc');
            if ($ipId) $sessionQuery->where('ip_id', $ipId);

            $row->width(8)->select('session_id', 'Training Session')->options(
                $sessionQuery->limit(200)->get()
                    ->mapWithKeys(fn($s) => [$s->id => $s->title . ' (' . optional($s->session_date)->format('M d, Y') . ')'])
            )->required();
            $row->width(4)->select('gap_category', 'GAP Category')
                ->options(FfsSessionResolution::getGapCategories())
                ->default('other');
        });

        // ── Resolution Summary ───────────────────────────────────────────
        $form->text('resolution', 'Resolution Summary')
            ->required()
            ->placeholder('Brief summary, e.g. "Farmers to adopt row planting"')
            ->help('Short summary of the resolution or GAP identified');

        // ── Rich description ────────────────────────────────────────────
        $form->quill('description', 'Detailed Description')
            ->placeholder('Provide detailed context about this resolution, why it matters, and what was discussed...');

        // ── Assignment & Tracking ────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(4)->select('responsible_person_id', 'Responsible Person')->options(
                User::where('user_type', 'Customer')->orderBy('name')->pluck('name', 'id')
            )->help('Group member to follow up');
            $row->width(4)->date('target_date', 'Target Date')
                ->default(date('Y-m-d', strtotime('+14 days')))
                ->help('Deadline for resolution');
            $row->width(4)->select('status', 'Status')
                ->options(FfsSessionResolution::getStatuses())
                ->default('pending');
        });

        // ── Follow-up notes ─────────────────────────────────────────────
        $form->quill('follow_up_notes', 'Follow-up Notes')
            ->placeholder('Document follow-up actions, progress, and observations...');

        // ── Save logic ──────────────────────────────────────────────────
        $form->hidden('created_by_id');
        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->input('created_by_id', \Encore\Admin\Facades\Admin::user()->id);
            }
            // Auto-set completed_at
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
