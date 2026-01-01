<?php

namespace App\Admin\Controllers;

use App\Models\MarketPriceCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class MarketPriceCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Market Price Categories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MarketPriceCategory());

        $grid->disableBatchActions();
        $grid->quickSearch('name')->placeholder('Search by name...');
        
        $grid->column('id', __('ID'))->sortable();
        $grid->column('photo', __('Photo'))->image('', 60, 60);
        $grid->column('name', __('Category Name'))->sortable();
        $grid->column('products_count', __('Products'))->display(function () {
            return $this->products()->count();
        })->sortable();
        $grid->column('order', __('Order'))->editable()->sortable();
        $grid->column('status', __('Status'))->using([
            'Active' => 'Active',
            'Inactive' => 'Inactive'
        ])->dot([
            'Active' => 'success',
            'Inactive' => 'danger',
        ])->sortable();
        $grid->column('created_at', __('Created'))->display(function ($created_at) {
            return date('M d, Y', strtotime($created_at));
        })->sortable();

        $grid->model()->orderBy('order', 'asc')->orderBy('id', 'desc');

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
        $show = new Show(MarketPriceCategory::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Category Name'));
        $show->field('description', __('Description'));
        $show->field('photo', __('Photo'))->image();
        $show->field('icon', __('Icon'));
        $show->field('order', __('Order'));
        $show->field('status', __('Status'));
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
        $form = new Form(new MarketPriceCategory());

        $form->text('name', __('Category Name'))->rules('required')->help('e.g., Fruits, Vegetables, Grains, Livestock');
        $form->textarea('description', __('Description'))->rows(3);
        $form->image('photo', __('Category Photo'))->uniqueName()->help('Upload category image');
        $form->icon('icon', __('Icon'))->default('fa-shopping-basket')->help('Select an icon for the category');
        $form->number('order', __('Display Order'))->default(0)->help('Lower number appears first');
        $form->radio('status', __('Status'))
            ->options(['Active' => 'Active', 'Inactive'=> 'Inactive'])
            ->default('Active')
            ->rules('required');

        $form->saving(function (Form $form) {
            $form->created_by = Admin::user()->id;
        });

        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });

        return $form;
    }
}
