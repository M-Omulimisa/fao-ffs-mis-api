<?php

namespace App\Admin\Controllers;

use App\Models\FfsSessionResolution;
use App\Models\FfsTrainingSession;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FfsSessionResolutionController extends AdminController
{
    protected $title = 'Meeting Resolutions (GAP)';

    protected function grid()
    {
        $grid = new Grid(new FfsSessionResolution());

        $grid->model()->with(['session', 'responsiblePerson'])->orderBy('id', 'desc');
        $grid->quickSearch('resolution')->placeholder('Search by resolution');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('session_id', 'Training Session')->select(
                FfsTrainingSession::orderBy('session_date', 'desc')
                    ->limit(100)
                    ->get()
                    ->pluck('title', 'id')
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

        $show->field('id', 'ID');
        $show->field('session.title', 'Session');
        $show->field('resolution', 'Resolution');
        $show->field('description', 'Description');
        $show->field('gap_category_text', 'GAP Category');
        $show->field('responsible_person_name', 'Responsible Person');
        $show->field('target_date', 'Target Date');
        $show->field('status_text', 'Status');
        $show->field('follow_up_notes', 'Follow-up Notes');
        $show->field('completed_at', 'Completed At');
        $show->field('created_at', 'Created');

        return $show;
    }

    protected function form()
    {
        $form = new Form(new FfsSessionResolution());

        $form->select('session_id', 'Training Session')->options(
            FfsTrainingSession::orderBy('session_date', 'desc')
                ->limit(100)
                ->get()
                ->mapWithKeys(function ($s) {
                    return [$s->id => $s->title . ' (' . ($s->session_date ? $s->session_date->format('M d, Y') : '') . ')'];
                })
        )->required();

        $form->text('resolution', 'Resolution')->required()->help('Short summary of the resolution/GAP');
        $form->textarea('description', 'Description')->rows(3);
        $form->select('gap_category', 'GAP Category')
            ->options(FfsSessionResolution::getGapCategories());
        $form->select('responsible_person_id', 'Responsible Person')->options(
            User::where('user_type', 'Customer')->orderBy('name')->pluck('name', 'id')
        );
        $form->date('target_date', 'Target Date');
        $form->select('status', 'Status')
            ->options(FfsSessionResolution::getStatuses())
            ->default('pending');
        $form->textarea('follow_up_notes', 'Follow-up Notes')->rows(2);

        $form->hidden('created_by_id');
        $form->saving(function (Form $form) {
            if (!$form->model()->id) {
                $form->created_by_id = \Encore\Admin\Facades\Admin::user()->id;
            }
            // Auto-set completed_at
            if ($form->status === 'completed' && !$form->model()->completed_at) {
                $form->model()->completed_at = now();
            }
        });

        return $form;
    }
}
