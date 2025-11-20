<?php

namespace App\Admin\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ReviewController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Product Reviews';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Review());

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableExport();

        $grid->quickSearch('comment')->placeholder('Search by comment');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            // Product filter
            $products = Product::all();
            $filter->equal('product_id', 'Product')->select(
                $products->pluck('name', 'id')
            );
            
            // User filter
            $users = User::all();
            $filter->equal('user_id', 'User')->select(
                $users->pluck('name', 'id')
            );
            
            $filter->equal('rating', 'Rating')->select([
                1 => '1 Star',
                2 => '2 Stars', 
                3 => '3 Stars',
                4 => '4 Stars',
                5 => '5 Stars'
            ]);
            
            $filter->like('comment', 'Comment');
            $filter->between('created_at', 'Created Date')->datetime();
        });

        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('product.name', __('Product'))
            ->display(function($name) {
                return $name ?: 'N/A';
            });
            
        $grid->column('user.name', __('User'))
            ->display(function($name) {
                return $name ?: 'N/A';
            });
            
        $grid->column('rating', __('Rating'))
            ->display(function ($rating) {
                $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                return "<span style='color: #ffa500; font-size: 16px;'>$stars</span> ($rating/5)";
            });
            
        $grid->column('comment', __('Comment'))
            ->limit(50);
            
        $grid->column('created_at', __('Created At'))
            ->display(function ($created_at) {
                return date('Y-m-d H:i', strtotime($created_at));
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
        $show = new Show(Review::findOrFail($id));

        $show->field('id', __('ID'));
        
        $show->field('product.name', __('Product'));
        $show->field('user.name', __('User'));
        $show->field('user.email', __('User Email'));
        
        $show->field('rating', __('Rating'))
            ->as(function ($rating) {
                $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                return "$stars ($rating/5)";
            });
            
        $show->field('comment', __('Comment'));
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
        $form = new Form(new Review());

        // Product selection
        $products = Product::all();
        $form->select('product_id', __('Product'))
            ->options($products->pluck('name', 'id'))
            ->rules('required');

        // User selection  
        $users = User::all();
        $form->select('user_id', __('User'))
            ->options($users->pluck('name', 'id'))
            ->rules('required');

        // Rating selection
        $form->select('rating', __('Rating'))
            ->options([
                1 => '1 Star - Poor',
                2 => '2 Stars - Fair',
                3 => '3 Stars - Good', 
                4 => '4 Stars - Very Good',
                5 => '5 Stars - Excellent'
            ])
            ->rules('required|integer|min:1|max:5');

        // Comment textarea
        $form->textarea('comment', __('Comment'))
            ->rows(4)
            ->rules('required|string|max:1000');

        // Validation for unique product-user combination
        $form->saving(function (Form $form) {
            $productId = $form->product_id;
            $userId = $form->user_id;
            $reviewId = $form->model()->id;

            // Check if user already has a review for this product (excluding current review)
            $existingReview = Review::where('product_id', $productId)
                ->where('user_id', $userId)
                ->when($reviewId, function($query) use ($reviewId) {
                    return $query->where('id', '!=', $reviewId);
                })
                ->first();

            if ($existingReview) {
                throw new \Exception('This user has already reviewed this product.');
            }
        });

        return $form;
    }
}
