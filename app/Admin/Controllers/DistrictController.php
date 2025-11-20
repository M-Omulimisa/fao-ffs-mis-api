<?php

namespace App\Admin\Controllers;

use App\Models\Location;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DistrictController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Ditricts';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Location());
        $grid->model()->where('parent', '<', 1)
            ->orderBy('name', 'asc');
        $grid->column('name', __('Name'))->sortable();
        /*         $grid->column('parent', __('Parent'));
        $grid->column('photo', __('Photo'));
        $grid->column('details', __('Details'));
        $grid->column('order', __('Order'));
        $grid->column('listed', __('Listed')); */
        $grid->column('count', __('Count'))->sortable();
        $grid->quickSearch('name')->placeholder('Search by name');
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
        $show = new Show(Location::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('name', __('Name'));
        $show->field('parent', __('Parent'));
        $show->field('photo', __('Photo'));
        $show->field('details', __('Details'));
        $show->field('order', __('Order'));
        $show->field('listed', __('Listed'));
        $show->field('count', __('Count'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Location());

        $form->text('name', __('District Name'));
        $form->hidden('parent', __('Parent'))->default(0);
        $form->hidden('order', __('order'))->default(0);
        $form->hidden('count', __('Count'))->default(0);
        $form->hidden('details', __('details'))->default('District');
        $form->image('photo', __('Photo'));
        $form->radioCard('listed', __('Display in Top Cities'))->options([
            'Yes' => 'Yes',
            'No' => 'No'
        ])->default('No');
        return $form;
    }
}
