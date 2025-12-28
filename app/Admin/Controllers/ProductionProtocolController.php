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
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Production Protocols';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ProductionProtocol());

        // Set default sort order
        $grid->model()->orderBy('enterprise_id', 'asc')->orderBy('start_time', 'asc');

        // Columns
        $grid->column('id', __('ID'))->sortable();
        $grid->column('enterprise.name', __('Enterprise'))->sortable();
        $grid->column('activity_name', __('Activity'))->sortable();
        $grid->column('start_time', __('Start Week'))->sortable()->label('info');
        $grid->column('end_time', __('End Week'))->sortable()->label('warning');
        $grid->column('duration_weeks', __('Duration'))->display(function () {
            return $this->duration_text;
        })->label('primary');
        $grid->column('is_compulsory', __('Type'))->display(function ($compulsory) {
            return $compulsory ? 'Mandatory' : 'Optional';
        })->label([
            'Mandatory' => 'danger',
            'Optional' => 'success',
        ])->sortable();
        $grid->column('order', __('Order'))->editable()->sortable();
        $grid->column('is_active', __('Status'))->switch([
            'on' => ['value' => 1, 'text' => 'Active', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'Inactive', 'color' => 'danger'],
        ]);

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            $filter->equal('enterprise_id', __('Enterprise'))
                ->select(Enterprise::where('is_active', true)->pluck('name', 'id'));
            
            $filter->like('activity_name', __('Activity Name'));
            
            $filter->equal('is_compulsory', __('Type'))->select([
                1 => 'Mandatory',
                0 => 'Optional',
            ]);
            
            $filter->equal('is_active', __('Status'))->select([
                1 => 'Active',
                0 => 'Inactive',
            ]);
            
            $filter->between('start_time', __('Start Week'));
        });

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        // Batch actions
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });

        // Enable row selection
        $grid->expandFilter();

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
        $show = new Show(ProductionProtocol::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('enterprise.name', __('Enterprise'));
        $show->field('photo', __('Photo'))->image();
        $show->field('activity_name', __('Activity Name'));
        $show->field('activity_description', __('Activity Description'));
        $show->field('start_time_text', __('Start Time'));
        $show->field('end_time_text', __('End Time'));
        $show->field('duration_text', __('Duration'));
        $show->field('compulsory_text', __('Type'));
        $show->field('order', __('Display Order'));
        $show->field('is_active', __('Active'))->as(function ($active) {
            return $active ? 'Yes' : 'No';
        });
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ProductionProtocol());

        // Enterprise Selection
        $form->select('enterprise_id', __('Enterprise'))
            ->options(function () {
                return Enterprise::where('is_active', true)
                    ->orderBy('name', 'asc')
                    ->pluck('name', 'id');
            })
            ->rules('required')
            ->help('Select the enterprise this protocol belongs to');

        // Activity Information
        $form->text('activity_name', __('Activity Name'))
            ->rules('required|string|max:255')
            ->help('Enter a descriptive name for this activity');

        $form->textarea('activity_description', __('Activity Description'))
            ->rows(5)
            ->help('Provide detailed instructions for performing this activity');

        // Timing
        $form->number('start_time', __('Start Week'))
            ->rules('required|integer|min:0')
            ->default(0)
            ->help('Week number when this activity should start (0 = beginning)');

        $form->number('end_time', __('End Week'))
            ->rules('required|integer|min:0')
            ->default(1)
            ->help('Week number when this activity should end');

        // Activity Properties
        $form->switch('is_compulsory', __('Mandatory'))
            ->default(1)
            ->help('Toggle whether this activity is mandatory or optional');

        $form->number('order', __('Display Order'))
            ->default(0)
            ->help('Order in which this activity appears (lower numbers appear first)');

        $form->image('photo', __('Photo'))
            ->move('protocols')
            ->uniqueName()
            ->help('Upload an image illustrating this activity');

        // Status
        $form->switch('is_active', __('Active'))
            ->default(1)
            ->help('Toggle to activate or deactivate this protocol');

        // Hidden fields
        $form->hidden('created_by_id')->default(Auth::user()->id);

        // Validation rules
        $form->saving(function (Form $form) {
            // Validate that end_time >= start_time
            if ($form->end_time < $form->start_time) {
                admin_error('Error', 'End week must be greater than or equal to start week');
                return back()->withInput();
            }

            // Validate against enterprise duration
            if ($form->enterprise_id) {
                $enterprise = Enterprise::find($form->enterprise_id);
                if ($enterprise) {
                    $maxWeeks = $enterprise->duration * 4;
                    if ($form->end_time > $maxWeeks) {
                        admin_error('Error', "End week cannot exceed enterprise duration of {$maxWeeks} weeks");
                        return back()->withInput();
                    }
                }
            }
        });

        return $form;
    }
}
