<?php

namespace App\Admin\Controllers;

use App\Models\MarketPrice;
use App\Models\MarketPriceProduct;
use App\Models\District;
use App\Models\SubCounty;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class MarketPriceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Market Prices';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MarketPrice());

        $grid->disableBatchActions();
        $grid->quickSearch('market_name')->placeholder('Search by market name...');
        
        $grid->column('id', __('ID'))->sortable();
        $grid->column('date', __('Date'))->display(function ($date) {
            return date('M d, Y', strtotime($date));
        })->sortable();
        $grid->column('product.name', __('Product'))->sortable();
        $grid->column('product.category.name', __('Category'));
        $grid->column('market_name', __('Market'))->sortable();
        $grid->column('district.name', __('District'));
        $grid->column('sub_county.name', __('Sub County'));
        $grid->column('price', __('Price'))->display(function () {
            return $this->currency . ' ' . number_format($this->price, 2);
        })->sortable();
        $grid->column('price_range', __('Price Range'))->display(function () {
            if ($this->price_min && $this->price_max) {
                return $this->currency . ' ' . number_format($this->price_min, 2) . ' - ' . number_format($this->price_max, 2);
            }
            return '-';
        });
        $grid->column('unit', __('Unit'))->display(function () {
            return $this->unit ?? $this->product->unit;
        });
        $grid->column('status', __('Status'))->using([
            'Active' => 'Active',
            'Inactive' => 'Inactive'
        ])->dot([
            'Active' => 'success',
            'Inactive' => 'danger',
        ])->sortable();
        $grid->column('created_at', __('Created'))->display(function ($created_at) {
            return date('M d, Y H:i', strtotime($created_at));
        })->sortable();

        $grid->model()->with(['product.category', 'district', 'subCounty'])->orderBy('date', 'desc')->orderBy('id', 'desc');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            $filter->equal('product_id', __('Product'))->select(function () {
                return MarketPriceProduct::where('status', 'Active')->pluck('name', 'id');
            });
            
            $filter->equal('district_id', __('District'))->select(function () {
                return District::orderBy('name')->pluck('name', 'id');
            });
            
            $filter->between('date', __('Date Range'))->date();
            
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
        $show = new Show(MarketPrice::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('product.name', __('Product'));
        $show->field('product.category.name', __('Category'));
        $show->field('date', __('Date'));
        $show->field('market_name', __('Market Name'));
        $show->field('district.name', __('District'));
        $show->field('sub_county.name', __('Sub County'));
        $show->field('price', __('Price'))->as(function ($price) {
            return $this->currency . ' ' . number_format($price, 2);
        });
        $show->field('price_min', __('Minimum Price'))->as(function ($price_min) {
            return $price_min ? $this->currency . ' ' . number_format($price_min, 2) : 'N/A';
        });
        $show->field('price_max', __('Maximum Price'))->as(function ($price_max) {
            return $price_max ? $this->currency . ' ' . number_format($price_max, 2) : 'N/A';
        });
        $show->field('unit', __('Unit'));
        $show->field('quantity', __('Quantity'));
        $show->field('source', __('Source'));
        $show->field('notes', __('Notes'));
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
        $form = new Form(new MarketPrice());

        $form->select('product_id', __('Product'))
            ->options(function () {
                return MarketPriceProduct::where('status', 'Active')
                    ->with('category')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->id => $item->name . ' (' . $item->category->name . ')'];
                    });
            })
            ->rules('required')
            ->help('Select the product');
        
        $form->date('date', __('Price Date'))
            ->default(date('Y-m-d'))
            ->rules('required')
            ->help('Date when this price was recorded');
        
        $form->divider('Location Information');
        
        $form->select('district_id', __('District'))
            ->options(District::orderBy('name')->pluck('name', 'id'))
            ->load('sub_county_id', '/admin/api/sub-counties')
            ->help('Select district');
        
        $form->select('sub_county_id', __('Sub County'))
            ->help('Select sub county (optional)');
        
        $form->text('market_name', __('Market Name'))->rules('required')->help('e.g., Nakasero Market, Owino Market');
        
        $form->divider('Price Information');
        
        $form->currency('price', __('Price'))
            ->symbol('UGX')
            ->rules('required')
            ->help('Average market price');
        
        $form->currency('price_min', __('Minimum Price (Optional)'))
            ->symbol('UGX')
            ->help('Lowest price observed');
        
        $form->currency('price_max', __('Maximum Price (Optional)'))
            ->symbol('UGX')
            ->help('Highest price observed');
        
        $form->hidden('currency')->default('UGX');
        
        $form->text('unit', __('Unit (Optional)'))
            ->help('Leave empty to use product\'s default unit');
        
        $form->text('quantity', __('Quantity (Optional)'))
            ->help('e.g., per kg, per 100kg bag, per crate');
        
        $form->text('source', __('Price Source (Optional)'))
            ->help('Who reported this price? e.g., Market Survey, Farmer Report');
        
        $form->textarea('notes', __('Additional Notes (Optional)'))
            ->rows(3)
            ->help('Any additional information about this price');
        
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
