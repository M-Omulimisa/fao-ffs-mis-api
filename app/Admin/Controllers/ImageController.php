<?php

namespace App\Admin\Controllers;

use App\Models\Image;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ImageController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Image';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Image());

        $grid->model()->orderBy('id', 'desc');
        $grid->column('id', __('Id'))->sortable();
        $grid->column('created_at', __('Created at'))->hide(); 
        $grid->column('src', __('Src'))->lightbox(['width' => 50, 'height' => 50])->sortable();
        $grid->column('thumbnail', __('Thumbnail'))->lightbox(['width' => 50, 'height' => 50])->sortable();
        $grid->column('parent_id', __('Parent id'))->sortable()->editable();
        $grid->column('size', __('Size'))->sortable();
        $grid->column('type', __('Type'))->sortable()->editable();
        $grid->column('product_id', __('Product id'))->editable()->sortable();
        $grid->column('parent_endpoint', __('Parent endpoint'))->hide();
        $grid->column('note', __('Note'));
        $grid->column('is_processed', __('Is processed'))->sortable()->editable();
        $grid->column('parent_local_id', __('Parent local id'))->sortable()->editable();

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
        $show = new Show(Image::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('administrator_id', __('Administrator id'));
        $show->field('src', __('Src'));
        $show->field('thumbnail', __('Thumbnail'));
        $show->field('parent_id', __('Parent id'));
        $show->field('size', __('Size'));
        $show->field('deleted_at', __('Deleted at'));
        $show->field('type', __('Type'));
        $show->field('product_id', __('Product id'));
        $show->field('parent_endpoint', __('Parent endpoint'));
        $show->field('note', __('Note'));
        $show->field('is_processed', __('Is processed'));
        $show->field('parent_local_id', __('Parent local id'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Image());

        $form->number('administrator_id', __('Administrator id'));
        $form->image('src', __('Src'));
        $form->image('thumbnail', __('Thumbnail'));
        $form->number('parent_id', __('Parent id'));
        $form->number('size', __('Size'));
        $form->text('type', __('Type'));
        $form->number('product_id', __('Product id'));
        $form->textarea('parent_endpoint', __('Parent endpoint'));
        $form->textarea('note', __('Note'));
        $form->text('is_processed', __('Is processed'))->default('No');
        $form->text('parent_local_id', __('Parent local id'));

        return $form;
    }
}
