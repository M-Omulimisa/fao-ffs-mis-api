<?php

namespace App\Admin\Controllers;

use App\Models\DeliveryAddress;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DeliveryAddressController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Delivery Addresses';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DeliveryAddress());
        $grid->model()->orderBy('address', 'asc');
        $grid->quickSearch('address');
        $grid->disableBatchActions();
        $grid->column('address', __('Address'))->sortable()
            ->editable();
        $grid->column('latitude', __('Latitude'))->hide();
        $grid->column('longitude', __('Longitude'))->hide();
        $grid->column('shipping_cost', __('Shipping Cost'));
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
        $show = new Show(DeliveryAddress::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('address', __('Address'));
        $show->field('latitude', __('Latitude'));
        $show->field('longitude', __('Longitude'));
        $show->field('shipping_cost', __('Shipping cost'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new DeliveryAddress());

        $form->text('address', __('Address'))->rules('required');
        $form->decimal('latitude', __('Latitude'));
        $form->decimal('longitude', __('Longitude'));
        $form->decimal('shipping_cost', __('Shipping cost'));

        return $form;
    }
}
