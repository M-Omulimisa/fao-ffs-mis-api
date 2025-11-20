<?php

namespace App\Observers;

use App\Models\Review;

class ReviewObserver
{
    /**
     * Handle the Review "created" event.
     *
     * @param  \App\Models\Review  $review
     * @return void
     */
    public function created(Review $review)
    {
        $this->updateProductStats($review->product_id);
    }

    /**
     * Handle the Review "updated" event.
     *
     * @param  \App\Models\Review  $review
     * @return void
     */
    public function updated(Review $review)
    {
        $this->updateProductStats($review->product_id);
    }

    /**
     * Handle the Review "deleted" event.
     *
     * @param  \App\Models\Review  $review
     * @return void
     */
    public function deleted(Review $review)
    {
        $this->updateProductStats($review->product_id);
    }

    /**
     * Update product review statistics.
     *
     * @param  int  $productId
     * @return void
     */
    private function updateProductStats($productId)
    {
        $product = \App\Models\Product::find($productId);
        if ($product) {
            $reviews = Review::where('product_id', $productId);
            $product->review_count = $reviews->count();
            $product->average_rating = $reviews->count() > 0 ? $reviews->avg('rating') : 0;
            $product->save();
        }
    }
}
