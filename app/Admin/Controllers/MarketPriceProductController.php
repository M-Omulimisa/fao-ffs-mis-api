<?php

namespace App\Admin\Controllers;

use App\Models\MarketPriceProduct;
use App\Models\MarketPriceCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class MarketPriceProductController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Market Price Products';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MarketPriceProduct());

        $grid->disableBatchActions();
        $grid->quickSearch('name')->placeholder('Search by product name...');
        
        $grid->column('id', __('ID'))->sortable();
        $grid->column('photo', __('Photo'))->image('', 60, 60);
        $grid->column('name', __('Product Name'))->sortable();
        $grid->column('category.name', __('Category'))->sortable();
        $grid->column('unit', __('Unit'))->label([
            'kg' => 'primary',
            'piece' => 'info',
            'bunch' => 'success',
            'liter' => 'warning',
            'bag' => 'default',
            'ton' => 'danger'
        ])->sortable();
        $grid->column('prices_count', __('Price Records'))->display(function () {
            return $this->prices()->count();
        });
        $grid->column('latest_price', __('Latest Price'))->display(function () {
            $latest = $this->latestPrice;
            if ($latest) {
                return $latest->currency . ' ' . number_format($latest->price, 2) . ' (' . date('M d, Y', strtotime($latest->date)) . ')';
            }
            return 'N/A';
        });
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

        $grid->model()->with(['category', 'latestPrice'])->orderBy('id', 'desc');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('category_id', __('Category'))->select(
                MarketPriceCategory::where('status', 'Active')->pluck('name', 'id')
            );
            $filter->equal('status', __('Status'))->select([
                'Active' => 'Active',
                'Inactive' => 'Inactive'
            ]);
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
        $show = new Show(MarketPriceProduct::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('category.name', __('Category'));
        $show->field('name', __('Product Name'));
        $show->field('description', __('Description'));
        $show->field('photo', __('Photo'))->image();
        $show->field('unit', __('Unit'));
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
        $form = new Form(new MarketPriceProduct());

        $form->select('category_id', __('Category'))
            ->options(MarketPriceCategory::where('status', 'Active')->pluck('name', 'id'))
            ->rules('required')
            ->help('Select the product category');
        
        $form->text('name', __('Product Name'))->rules('required')->help('e.g., Apples, Maize, Tomatoes');
        $form->textarea('description', __('Description'))->rows(3);
        $form->image('photo', __('Product Photo'))->uniqueName()->help('Upload product image');
        
        $form->select('unit', __('Unit of Measurement'))
            ->options([
                'kg' => 'Kilogram (kg)',
                'piece' => 'Piece',
                'bunch' => 'Bunch',
                'liter' => 'Liter',
                'bag' => 'Bag',
                'ton' => 'Ton'
            ])
            ->default('kg')
            ->rules('required');
        
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
