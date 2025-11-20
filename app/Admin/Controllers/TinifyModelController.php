<?php

namespace App\Admin\Controllers;

use App\Models\TinifyModel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TinifyModelController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'TinifyModel';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TinifyModel());

        $grid->column('id', __('Id'));
        $grid->column('api_key', __('Api key'));
        $grid->column('status', __('Status'));
        $grid->column('usage_count', __('Usage count'));
        $grid->column('monthly_limit', __('Monthly limit'));
        $grid->column('compressions_this_month', __('Compressions this month'));
        $grid->column('last_used_at', __('Last used at'));
        $grid->column('last_reset_at', __('Last reset at'));
        $grid->column('notes', __('Notes'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

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
        $show = new Show(TinifyModel::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('api_key', __('Api key'));
        $show->field('status', __('Status'));
        $show->field('usage_count', __('Usage count'));
        $show->field('monthly_limit', __('Monthly limit'));
        $show->field('compressions_this_month', __('Compressions this month'));
        $show->field('last_used_at', __('Last used at'));
        $show->field('last_reset_at', __('Last reset at'));
        $show->field('notes', __('Notes'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new TinifyModel());

        $form->text('api_key', __('Api key'));
        $form->text('status', __('Status'))->default('active');
        $form->number('usage_count', __('Usage count'));
        $form->number('monthly_limit', __('Monthly limit'))->default(500);
        $form->number('compressions_this_month', __('Compressions this month'));
        $form->datetime('last_used_at', __('Last used at'))->default(date('Y-m-d H:i:s'));
        $form->datetime('last_reset_at', __('Last reset at'))->default(date('Y-m-d H:i:s'));
        $form->textarea('notes', __('Notes'));

        return $form;
    }
}
