<?php

namespace App\Admin\Controllers;

use App\Models\ProductCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ProductCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Categories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ProductCategory());
        $grid->disableBatchActions();

        $grid->column('id', __('#ID'))->sortable();
        
        $grid->column('category', __('Category'))->sortable();
        
        $grid->column('icon', __('Icon'))
            ->display(function ($icon) {
                return $icon ? "<i class='$icon'></i> $icon" : '<span style="color: #999;">No icon</span>';
            })
            ->sortable();
        
        $grid->column('is_parent', __('Category Type'))
            ->display(function ($is_parent) {
                return $is_parent == 'Yes'
                    ? "<span style='color: green; font-weight: bold;'>Main Category</span>"
                    : "<span style='color: red; font-weight: bold;'>Sub Category</span>";
            })
            ->filter(['Yes' => 'Main Category', 'No' => 'Sub Category'])
            ->sortable();
        
        $grid->column('show_in_banner', __('Show in Banner'))
            ->editable('select', ['Yes' => 'Yes', 'No' => 'No'])
            ->sortable();
        
        $grid->column('show_in_categories', __('Show in Categories'))
            ->editable('select', ['Yes' => 'Yes', 'No' => 'No'])
            ->sortable();
        
        $grid->column('is_first_banner', __('Is First Banner'))
            ->sortable();
        
        $grid->column('banner_image', __('Banner Image'))
            ->lightbox(['width' => 50, 'height' => 50])
            ->sortable();
        
        $grid->column('image', __('Main Photo'))
            ->lightbox(['width' => 50, 'height' => 50])
            ->sortable();

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
        $show = new Show(ProductCategory::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('category', __('Category'));
        $show->field('icon', __('Icon'));
        $show->field('is_parent', __('Is Main Category'));
        $show->field('parent_id', __('Parent Category ID'));
        $show->field('image', __('Main Photo'));
        $show->field('banner_image', __('Banner Image'));
        $show->field('show_in_banner', __('Show in Banner'));
        $show->field('show_in_categories', __('Show in Categories'));
        $show->field('is_first_banner', __('Is First Banner'));
        $show->field('first_banner_image', __('First Banner Image'));
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
        $form = new Form(new ProductCategory());

        $form->text('category', __('Category Name'))
            ->rules('required')
            ->placeholder('e.g., Seeds, Livestock, Equipment')
            ->help('Enter the agricultural product category name');
        
        $form->text('icon', __('Icon Class'))
            ->placeholder('e.g., bi-phone, bi-laptop, bi-headphones')
            ->help('Bootstrap Icons class name (optional)');

        $form->radio('is_parent', __('Is Main Category'))
            ->options(['Yes' => 'Yes', 'No' => 'No'])
            ->default('Yes')
            ->when('No', function (Form $form) {
                $parentCategories = ProductCategory::where('is_parent', 'Yes')
                    ->get()
                    ->pluck('category', 'id');
                $form->select('parent_id', __('Select Parent Category'))
                    ->options($parentCategories)
                    ->rules('required');
            })
            ->rules('required');

        $form->image('image', __('Main Photo'))
            ->rules('required')
            ->uniqueName()
            ->help('Upload the category icon/image');
        
        $form->image('banner_image', __('Banner Image'))
            ->uniqueName()
            ->help('Upload banner image (optional)');

        $form->radio('show_in_banner', __('Show in Banner'))
            ->options(['Yes' => 'Yes', 'No' => 'No'])
            ->default('Yes')
            ->rules('required');
        
        $form->radio('show_in_categories', __('Show in Categories'))
            ->options(['Yes' => 'Yes', 'No' => 'No'])
            ->default('Yes')
            ->rules('required');

        $form->radio('is_first_banner', __('Is First Banner'))
            ->options(['Yes' => 'Yes', 'No' => 'No'])
            ->default('No')
            ->when('Yes', function (Form $form) {
                $form->image('first_banner_image', __('First Banner Image'))
                    ->uniqueName()
                    ->help('Upload the first banner image');
            });

        return $form;
    }
}
