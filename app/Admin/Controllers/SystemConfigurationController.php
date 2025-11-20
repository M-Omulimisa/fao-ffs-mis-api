<?php

namespace App\Admin\Controllers;

use App\Models\SystemConfiguration;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SystemConfigurationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'System Configuration';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SystemConfiguration());

        $grid->model()->where('id', 1); // Show only the record with ID 1
        $grid->column('company_name', __('Company name'));
        $grid->disableCreateButton(); // Disable the create button 
        $grid->column('company_email', __('Company email'));
        $grid->column('company_phone', __('Company phone'));
        $grid->column('company_pobox', __('Company pobox'))->hide();
        $grid->column('company_address', __('Company address'))->hide();
        $grid->column('company_website', __('Company website'))->hide();
        $grid->column('company_logo', __('Company logo'))
            ->lightbox(['width' => 100, 'height' => 100]);
        $grid->column('company_details', __('Company details'))->hide();
        $grid->column('insurance_start_date', __('Insurance start date'))->sortable();
        $grid->column('insurance_price', __('Insurance price'))->hide(); 

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
        $show = new Show(SystemConfiguration::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('company_name', __('Company name'));
        $show->field('company_email', __('Company email'));
        $show->field('company_phone', __('Company phone'));
        $show->field('company_pobox', __('Company pobox'));
        $show->field('company_address', __('Company address'));
        $show->field('company_website', __('Company website'));
        $show->field('company_logo', __('Company logo'));
        $show->field('company_details', __('Company details'));
        $show->field('insurance_start_date', __('Insurance start date'));
        $show->field('insurance_price', __('Insurance price'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SystemConfiguration());

        $form->text('company_name', __('Company name'));
        $form->text('company_email', __('Company email'));
        $form->text('company_phone', __('Company phone'));
        $form->text('company_pobox', __('Company pobox'));
        $form->text('company_address', __('Company address'));
        $form->text('company_website', __('Company website'));
        $form->image('company_logo', __('Company logo'));
        $form->textarea('company_details', __('Company details'));
        $form->datetime('insurance_start_date', __('Insurance start date'))->default(date('Y-m-d H:i:s'));
        $form->decimal('insurance_price', __('Insurance price'));
        
        $form->disableCreatingCheck(); // Disable the creating check
        $form->disableViewCheck(); // Disable the view check
        return $form;
    }
}
