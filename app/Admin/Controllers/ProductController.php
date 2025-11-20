<?php

namespace App\Admin\Controllers;

use App\Models\Image;
use App\Models\Product;
use App\Models\User;
use App\Models\Utils;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;

class ProductController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Products';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Product());

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableExport();

        $grid->quickSearch('name')->placeholder('Search by name');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', 'Product Name');
            $cats = \App\Models\ProductCategory::all();
            $filter->equal('category', 'Category')->select(
                $cats->pluck('category', 'id')
            );
            $filter->between('price_1', 'Selling Price (UGX)');
            $filter->between('created_at', 'Created Date')->datetime();
        });
        $grid->model()->orderBy('id', 'desc');

        $grid->column('feature_photo', __('Photo'))
            ->image('', 50, 50)
            ->sortable();

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Product Name'))->sortable()
            ->editable();
        $grid->column('description', __('Description'))
            ->hide();

        $grid->column('price_2', __('Original Price (UGX)'))
            ->sortable()
            ->editable();
        $grid->column('price_1', __('Selling Price (UGX)'))
            ->sortable()
            ->editable();

        $grid->column('date_updated', __('Last Updated'));
        $grid->column('user', __('User'))
            ->display(function ($user) {
                $u = \App\Models\User::find($user);
                return $u ? $u->name : 'Deleted';
            })
            ->sortable();
        $grid->column('category', __('Category'))
            ->display(function ($category) {
                $c = \App\Models\ProductCategory::find($category);
                return $c ? $c->category : 'Deleted';
            })
            ->sortable();
        //home_section_1 editable col
        $grid->column('home_section_1', __('Home Section 1'))
            ->editable('select', ['Yes' => 'Yes', 'No' => 'No'])
        
            ->filter([
                'Yes' => 'Yes',
                'No' => 'No',
            ])->sortable();

        $grid->column('home_section_2', __('Home Section 2'))
            ->editable('select', ['Yes' => 'Yes', 'No' => 'No'])
             
            ->filter([
                'Yes' => 'Yes',
                'No' => 'No',
            ])->sortable();

        $grid->column('home_section_3', __('Home Section 3'))
            ->editable('select', ['Yes' => 'Yes', 'No' => 'No'])
 
            ->filter([
                'Yes' => 'Yes',
                'No' => 'No',
            ])->sortable();

        $grid->column('created_at', __('Created'))
            ->display(function ($created_at) {
                return date('Y-m-d H:i:s', strtotime($created_at));
            })->sortable();


        $grid->column('is_compressed', __('Is Compressed'))
            ->sortable()
            ->filter([
                'yes' => 'Yes',
                'no' => 'No',
            ])->sortable()
            ->editable('select', ['yes' => 'Yes', 'no' => 'No']);
        $grid->column('compress_status', __('Compress Status'))
            ->sortable()
            ->editable('select', ['completed' => 'Success', 'failed' => 'Failed'])
            ->filter([
                'pending' => 'Pending',
                'in_progress' => 'In Progress',
                'completed' => 'Success',
                'failed' => 'Failed',
            ]);
        $grid->column('compress_status_message', __('Compress Status Message'))->hide();
        $grid->column('original_size', __('Original Size'))->sortable();
        $grid->column('compressed_size', __('Compressed Size'))->sortable();
        $grid->column('compression_ratio', __('Compression Ratio'))->sortable();
        $grid->column('compression_method', __('Compression Method'));
        $grid->column('original_image_url', __('Original Image URL'))->lightbox(['width' => 100, 'height' => 100])->sortable()->hide();
        $grid->column('compressed_image_url', __('Compressed Image URL'))->lightbox(['width' => 100, 'height' => 100])->sortable()->hide();
        $grid->column('tinify_model_id', __('Tinify Model ID'))->hide()->sortable();
        $grid->column('compression_started_at', __('Compression Started At'))->sortable();
        $grid->column('compression_completed_at', __('Compression Completed At'))->sortable();
        // add this as has many specifications

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
        $show = new Show(Product::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Product Name'));
        $show->field('metric', __('Metric'));
        // The currency is fixed to UGX.
        $show->field('currency', __('Currency'))->as(function () {
            return 'UGX';
        });
        $show->field('description', __('Description'));
        $show->field('summary', __('Summary'));
        $show->field('price_1', __('Selling Price (UGX)'));
        $show->field('price_2', __('Original Price (UGX)'));
        $show->field('feature_photo', __('Feature Photo'));
        $show->field('rates', __('Rates'));
        $show->field('date_added', __('Date Added'));
        $show->field('date_updated', __('Last Updated'));
        $show->field('user', __('User'));
        $show->field('category', __('Category'));
        $show->field('sub_category', __('Sub Category'));
        $show->field('supplier', __('Supplier'));
        $show->field('url', __('URL'));
        $show->field('status', __('Status'));
        $show->field('in_stock', __('In Stock'));
        $show->field('keywords', __('Keywords'));
        $show->field('p_type', __('Product Type'));
        $show->field('local_id', __('Local ID'));
        $show->field('updated_at', __('Updated At'));
        $show->field('created_at', __('Created At'));
        $show->field('data', __('Data'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $last = Image::where([])->get()->last();
        $last->create_thumbail();
        $form = new Form(new Product());
        $form->hidden('local_id')->value(Utils::get_unique_text());

        $form->text('name', __('Product Name'))
            ->rules('required');

        //tags
        $form->tags('tags', __('Tags'))
            ->placeholder('e.g., electronics, smartphone, android, mobile, communication, technology')
            ->rules('required');

        // Ensure the currency is set to UGX only.
        $form->hidden('currency')->default('UGX');

        $form->text('price_2', __('Original Price (UGX)'))
            ->rules('required');
        $form->text('price_1', __('Selling Price (UGX)'))
            ->rules('required');

        $form->radio('has_colors', __('Does it have color options?'))
            ->options([
                'Yes' => 'Yes',
                'No' => 'No'
            ])->when('Yes', function (Form $form) {
                // List of common colors.
                $colors = [
                    'Red' => 'Red',
                    'Blue' => 'Blue',
                    'Yellow' => 'Yellow',
                    'Green' => 'Green',
                    'Orange' => 'Orange',
                    'Purple' => 'Purple',
                    'Brown' => 'Brown',
                    'Pink' => 'Pink',
                    'Black' => 'Black',
                    'White' => 'White',
                    'Gray' => 'Gray',
                    'Cyan' => 'Cyan',
                    'Magenta' => 'Magenta',
                    'Lime' => 'Lime',
                    'Teal' => 'Teal',
                    'Lavender' => 'Lavender',
                    'Maroon' => 'Maroon',
                    'Navy' => 'Navy',
                    'Olive' => 'Olive',
                    'Silver' => 'Silver',
                    'Dark' => 'Dark',
                    'DarkBlue' => 'DarkBlue',
                    'DarkCyan' => 'DarkCyan',
                    'DarkGray' => 'DarkGray',
                    'DarkGreen' => 'DarkGreen',
                ];
                $form->tags('colors', 'Select Colors')
                    ->options($colors)
                    ->rules('required');
            })->default('No');

        $form->radio('has_sizes', __('Does it have size options?'))
            ->options([
                'Yes' => 'Yes',
                'No' => 'No'
            ])->when('Yes', function (Form $form) {
                // List of common sizes.
                $sizes = [
                    'XS' => 'XS',
                    'S' => 'S',
                    'M' => 'M',
                    'L' => 'L',
                    'XL' => 'XL',
                    'XXL' => 'XXL',
                    'XXXL' => 'XXXL',
                    'XXXXL' => 'XXXXL',
                    'XXXXXL' => 'XXXXXL',
                ];
                $form->tags('sizes', 'Select Sizes')
                    ->options($sizes)
                    ->rules('required');
            })->default('No');

        $form->quill('description', __('Description'))
            ->rules('required');

        $form->image('feature_photo', __('Feature Photo'))
            ->rules('required');
        $cats = \App\Models\ProductCategory::all();
        $form->select('category', __('Category'))
            ->options($cats->pluck('category', 'id'))
            ->rules('required');

        // Uncomment and adjust if needed:
        // $form->url('url', __('URL'));
        // $form->decimal('rates', __('Rates'));

        $form->divider('Product Details',);
        // has many images
        $form->hasMany('images', 'Images', function (Form\NestedForm $form) {
            $u = Auth::user();
            $form->image('src', 'Image')->uniqueName();
            $form->hidden('administrator_id')->value($u->id);
        });

        $form->divider('Product Specifications');

        //has many specifications
        $form->hasMany('specifications', 'Specifications', function (Form\NestedForm $form) {
            $form->text('name', __('Specification Name'))
                ->placeholder('e.g., Size, Color, Material')
                ->rules('required|max:255');
            $form->text('value', __('Value'))
                ->placeholder('e.g., Large, Red, Cotton');
        });

        $form->divider('Homepage Sections');

        $form->radio('home_section_1', __('Flash Sales Section'))
            ->options([
                'Yes' => 'Yes - Show in Flash Sales',
                'No' => 'No - Don\'t show in Flash Sales'
            ])
            ->default('No')
            ->help('Select "Yes" to display this product in the Flash Sales section on homepage');

        $form->radio('home_section_2', __('Super Buyer Section'))
            ->options([
                'Yes' => 'Yes - Show in Super Buyer',
                'No' => 'No - Don\'t show in Super Buyer'
            ])
            ->default('No')
            ->help('Select "Yes" to display this product in the Super Buyer section on homepage');

        $form->radio('home_section_3', __('Top Products Section'))
            ->options([
                'Yes' => 'Yes - Show in Top Products',
                'No' => 'No - Don\'t show in Top Products'
            ])
            ->default('No')
            ->help('Select "Yes" to display this product in the Top Products section on homepage');

        return $form;
    }
}
