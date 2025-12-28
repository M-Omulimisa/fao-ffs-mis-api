<?php

namespace App\Admin\Controllers;

use App\Models\Enterprise;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;

class EnterpriseController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Enterprises';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Enterprise());

        // Set default sort order
        $grid->model()->orderBy('created_at', 'desc');
        $grid->quickSearch('name', 'type');

        // Columns
        $grid->column('id', __('ID'))->sortable();
        $grid->column('photo', __('Photo'))->lightbox(['width' => 50, 'height' => 50]);
        $grid->column('name', __('Name'))->sortable();
        $grid->column('type', __('Type'))->label([
            'livestock' => 'info',
            'crop' => 'success',
        ])->sortable();
        $grid->column('duration', __('Duration'))->display(function ($duration) {
            return $this->duration_text;
        });
        $grid->column('total_protocols', __('Protocols'))->display(function () {
            return $this->productionProtocols()->count();
        })->label('primary');
        $grid->column('is_active', __('Status'))->switch([
            'on' => ['value' => 1, 'text' => 'Active', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'Inactive', 'color' => 'danger'],
        ]);
        $grid->column('created_at', __('Created'))->display(function ($date) {
            return date('Y-m-d H:i', strtotime($date));
        })->sortable();

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', __('Name'));
            $filter->equal('type', __('Type'))->select([
                'livestock' => 'Livestock',
                'crop' => 'Crop',
            ]);
            $filter->equal('is_active', __('Status'))->select([
                1 => 'Active',
                0 => 'Inactive',
            ]);
        });

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        // Batch actions
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
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
        $show = new Show(Enterprise::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('photo', __('Photo'))->image();
        $show->field('name', __('Name'));
        $show->field('type_text', __('Type'));
        $show->field('duration_text', __('Duration'));
        $show->field('description', __('Description'));
        $show->field('is_active', __('Active'))->as(function ($active) {
            return $active ? 'Yes' : 'No';
        });
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        // Show production protocols
        $show->productionProtocols('Production Protocols', function ($protocols) {
            $protocols->disableCreateButton();
            $protocols->disableExport();
            $protocols->disableBatchActions();

            $protocols->column('activity_name', __('Activity'));
            $protocols->column('start_time_text', __('Start'));
            $protocols->column('end_time_text', __('End'));
            $protocols->column('duration_text', __('Duration'));
            $protocols->column('compulsory_text', __('Type'));
            $protocols->column('is_active', __('Active'))->display(function ($active) {
                return $active ? 'Yes' : 'No';
            });
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Enterprise());

        // Basic Information
        $form->text('name', __('Name'))
            ->rules('required|string|max:255')
            ->help('Enter a unique name for the enterprise');

        $form->select('type', __('Type'))
            ->options([
                'livestock' => 'Livestock',
                'crop' => 'Crop',
            ])
            ->rules('required')
            ->help('Select whether this is a livestock or crop-based enterprise');

        $form->number('duration', __('Duration (months)'))
            ->rules('required|integer|min:1|max:120')
            ->default(12)
            ->help('Enter the duration in months (e.g., 12 for one year)');

        $form->image('photo', __('Photo'))
            ->move('enterprises')
            ->uniqueName()
            ->help('Upload an image representing this enterprise');

        $form->textarea('description', __('Description'))
            ->rows(5)
            ->help('Provide a detailed description of this enterprise');

        // Status
        $form->switch('is_active', __('Active'))
            ->default(1)
            ->help('Toggle to activate or deactivate this enterprise');

        // Hidden fields
        $form->hidden('created_by_id')->default(Auth::user()->id);

        // Saving callback
        $form->saving(function (Form $form) {
            // Additional validation or processing if needed
        });

        return $form;
    }
}
