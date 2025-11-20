<?php

namespace App\Admin\Controllers;

use App\Models\Location;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SubCountyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Sub Counties';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Location());
        $grid->model()->where('parent', '>', 0)
            ->orderBy('name', 'asc');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', 'Name');
            $filter->equal('parent', 'District')->select(Location::get_districts()->pluck('name', 'id'));
        });
        $grid->column('name', __('Name'))->sortable();
        $grid->column('parent', __('Dostrict'))
            ->display(function ($parent) {
                $mother = Location::find($parent);
                if ($mother != null) {
                    return $mother->name;
                }
                return "Unknown";
            })->sortable();
        /*
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
        $form->select('parent', __('District'))->options(Location::where('parent', '<', 1)->pluck('name', 'id'));
        $form->hidden('order', __('order'))->default(0);
        $form->hidden('count', __('Count'))->default(0);
        $form->hidden('details', __('details'))->default('District');
        $form->image('photo', __('Photo'));
        $form->switch('listed', __('Listed'))->default('No');
        return $form;
    }
}
