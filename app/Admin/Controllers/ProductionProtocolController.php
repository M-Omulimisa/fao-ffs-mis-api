<?php

namespace App\Admin\Controllers;

use App\Models\Enterprise;
use App\Models\ProductionProtocol;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;

class ProductionProtocolController extends AdminController
{
    protected $title = 'Production Protocols';

    protected function grid()
    {
        $grid = new Grid(new ProductionProtocol());

        $grid->model()->with('enterprise')->orderBy('id', 'desc');
        $grid->quickSearch('activity_name')->placeholder('Search by activity name...');

        // Columns
        $grid->column('id', 'ID')->sortable()->hide();
        $grid->column('enterprise.name', 'Enterprise')->sortable();
        $grid->column('photo', 'Photo')->lightbox(['width' => 40, 'height' => 40]);
        $grid->column('activity_name', 'Activity')->sortable();
        $grid->column('start_time', 'Start Week')->sortable()->label('info');
        $grid->column('end_time', 'End Week')->sortable()->label('warning');
        $grid->column('duration_weeks', 'Duration')->display(function () {
            return $this->duration_text ?? (($this->end_time - $this->start_time) . ' weeks');
        })->label('primary');
        $grid->column('is_compulsory', 'Type')->display(function ($compulsory) {
            return $compulsory ? 'Mandatory' : 'Optional';
        })->label([
            'Mandatory' => 'danger',
            'Optional' => 'success',
        ])->sortable();
        $grid->column('weight', 'Weight')->display(function ($w) {
            return str_repeat('★', min($w, 5));
        });
        $grid->column('order', 'Order')->editable()->sortable();
        $grid->column('is_active', 'Status')->switch([
            'on' => ['value' => 1, 'text' => 'Active', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'Inactive', 'color' => 'danger'],
        ]);

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('enterprise_id', 'Enterprise')
                ->select(Enterprise::where('is_active', true)->pluck('name', 'id'));
            $filter->like('activity_name', 'Activity Name');
            $filter->equal('is_compulsory', 'Type')->select([
                1 => 'Mandatory',
                0 => 'Optional',
            ]);
            $filter->equal('is_active', 'Status')->select([
                1 => 'Active',
                0 => 'Inactive',
            ]);
        });

        $grid->expandFilter();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(ProductionProtocol::findOrFail($id));

        $show->panel()->style('primary')->title('Production Protocol Details');

        $show->field('id', 'ID');
        $show->field('enterprise.name', 'Enterprise');
        $show->field('photo', 'Photo')->image();
        $show->field('activity_name', 'Activity Name');
        $show->field('activity_description', 'Activity Description')->unescape();
        $show->field('start_time', 'Start Week');
        $show->field('end_time', 'End Week');
        $show->field('is_compulsory', 'Type')->as(function ($c) {
            return $c ? 'Mandatory' : 'Optional';
        });
        $show->field('weight', 'Scoring Weight');
        $show->field('order', 'Display Order');
        $show->field('is_active', 'Active')->as(function ($active) {
            return $active ? 'Yes' : 'No';
        });
        $show->field('created_at', 'Created At');

        return $show;
    }

    protected function form()
    {
        $form = new Form(new ProductionProtocol());

        // ── Enterprise & Activity ────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(6)->select('enterprise_id', 'Enterprise')
                ->options(Enterprise::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                ->rules('required')
                ->help('Select the enterprise this protocol belongs to');
            $row->width(6)->text('activity_name', 'Activity Name')
                ->rules('required|string|max:255')
                ->placeholder('e.g. Land Preparation, Planting, Weeding');
        });

        // ── Rich description ─────────────────────────────────────────────
        $form->quill('activity_description', 'Activity Instructions')
            ->placeholder('Provide detailed step-by-step instructions for performing this activity. Include best practices, tools needed, safety precautions...');

        // ── Timing ───────────────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(3)->number('start_time', 'Start Week')
                ->rules('required|integer|min:0')
                ->default(0)
                ->help('Week # when activity starts (0 = beginning)');
            $row->width(3)->number('end_time', 'End Week')
                ->rules('required|integer|min:0')
                ->default(1)
                ->help('Week # when activity ends');
            $row->width(3)->switch('is_compulsory', 'Mandatory')->default(1)
                ->help('Must the farmer do this?');
            $row->width(3)->number('weight', 'Scoring Weight')
                ->default(1)
                ->rules('required|integer|min:1|max:10')
                ->help('Higher = more impact on score (1-10)');
        });

        // ── Display & Status ─────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(4)->number('order', 'Display Order')
                ->default(0)
                ->help('Lower numbers appear first');
            $row->width(4)->image('photo', 'Photo')
                ->move('protocols')
                ->uniqueName()
                ->removable();
            $row->width(4)->switch('is_active', 'Active')->default(1);
        });

        // Hidden fields
        $form->hidden('created_by_id')->default(Auth::user()->id ?? null);

        // Validation
        $form->saving(function (Form $form) {
            if ($form->end_time < $form->start_time) {
                admin_error('Error', 'End week must be greater than or equal to start week');
                return back()->withInput();
            }

            if ($form->enterprise_id) {
                $enterprise = Enterprise::find($form->enterprise_id);
                if ($enterprise) {
                    $maxWeeks = $enterprise->duration * 4;
                    if ($form->end_time > $maxWeeks) {
                        admin_error('Error', "End week cannot exceed enterprise duration of {$maxWeeks} weeks ({$enterprise->duration} months)");
                        return back()->withInput();
                    }
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
