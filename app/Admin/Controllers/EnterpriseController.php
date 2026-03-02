<?php

namespace App\Admin\Controllers;

use App\Models\Enterprise;
use App\Models\Farm;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;

class EnterpriseController extends AdminController
{
    protected $title = 'Enterprises';

    protected function grid()
    {
        $grid = new Grid(new Enterprise());

        $grid->model()->orderBy('created_at', 'desc');
        $grid->quickSearch('name', 'type')->placeholder('Search enterprises...');

        // Columns
        $grid->column('id', 'ID')->sortable()->hide();
        $grid->column('photo', 'Photo')->lightbox(['width' => 50, 'height' => 50]);
        $grid->column('name', 'Name')->sortable();
        $grid->column('type', 'Type')->label([
            'livestock' => 'info',
            'crop' => 'success',
        ])->sortable();
        $grid->column('duration', 'Duration')->display(function ($duration) {
            return $this->duration_text;
        });
        $grid->column('total_protocols', 'Protocols')->display(function () {
            return $this->productionProtocols()->count();
        })->label('primary');
        $grid->column('total_farms', 'Active Farms')->display(function () {
            return Farm::where('enterprise_id', $this->id)->where('is_active', true)->count();
        })->label('success');
        $grid->column('is_active', 'Status')->switch([
            'on' => ['value' => 1, 'text' => 'Active', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'Inactive', 'color' => 'danger'],
        ]);
        $grid->column('created_at', 'Created')->display(function ($date) {
            return date('Y-m-d', strtotime($date));
        })->sortable();

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', 'Name');
            $filter->equal('type', 'Type')->select([
                'livestock' => 'Livestock',
                'crop' => 'Crop',
            ]);
            $filter->equal('is_active', 'Status')->select([
                1 => 'Active',
                0 => 'Inactive',
            ]);
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Enterprise::findOrFail($id));

        $show->panel()->style('primary')->title('Enterprise Details');

        $show->field('id', 'ID');
        $show->field('photo', 'Photo')->image();
        $show->field('name', 'Name');
        $show->field('type_text', 'Type');
        $show->field('duration_text', 'Duration');
        $show->field('description', 'Description')->unescape();
        $show->field('is_active', 'Active')->as(function ($active) {
            return $active ? 'Yes' : 'No';
        });
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        // Show production protocols
        $show->productionProtocols('Production Protocols', function ($protocols) {
            $protocols->disableCreateButton();
            $protocols->disableExport();
            $protocols->disableBatchActions();

            $protocols->column('activity_name', 'Activity');
            $protocols->column('start_time', 'Start Week')->label('info');
            $protocols->column('end_time', 'End Week')->label('warning');
            $protocols->column('is_compulsory', 'Type')->display(function ($c) {
                return $c ? 'Mandatory' : 'Optional';
            })->label(['Mandatory' => 'danger', 'Optional' => 'success']);
            $protocols->column('is_active', 'Active')->display(function ($active) {
                return $active ? 'Yes' : 'No';
            });
        });

        // Show active farms
        $enterprise = Enterprise::find($id);
        $farmCount = $enterprise ? Farm::where('enterprise_id', $id)->count() : 0;
        if ($farmCount > 0) {
            $show->field('farms_info', 'Farms')->as(function () use ($id) {
                $farms = Farm::where('enterprise_id', $id)->with('user')->get();
                $html = '<table class="table table-bordered"><tr><th>Farm</th><th>Farmer</th><th>Status</th><th>Score</th></tr>';
                foreach ($farms as $f) {
                    $html .= '<tr><td>' . $f->name . '</td><td>' . ($f->user ? $f->user->name : '-') . '</td>';
                    $html .= '<td>' . ucfirst($f->status) . '</td><td>' . $f->overall_score . '%</td></tr>';
                }
                $html .= '</table>';
                return $html;
            })->unescape();
        }

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Enterprise());

        // ── Basic Information ────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(6)->text('name', 'Enterprise Name')
                ->rules('required|string|max:255')
                ->placeholder('e.g. Goat Rearing, Cassava Growing');
            $row->width(3)->select('type', 'Type')
                ->options([
                    'livestock' => 'Livestock',
                    'crop' => 'Crop',
                ])
                ->rules('required')
                ->default('crop');
            $row->width(3)->number('duration', 'Duration (months)')
                ->rules('required|integer|min:1|max:120')
                ->default(12)
                ->help('Enterprise cycle length');
        });

        $form->row(function ($row) {
            $row->width(6)->image('photo', 'Photo')
                ->move('enterprises')
                ->uniqueName()
                ->removable();
            $row->width(6)->switch('is_active', 'Active')->default(1);
        });

        // ── Rich Description ─────────────────────────────────────────────
        $form->quill('description', 'Enterprise Description')
            ->placeholder('Describe this enterprise: what it involves, expected outcomes, seasonal considerations, target farmers...');

        // Hidden fields
        $form->hidden('created_by_id')->default(Auth::user()->id ?? null);

        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
