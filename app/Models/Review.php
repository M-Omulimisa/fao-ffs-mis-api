<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        // Update product stats when review is created
        static::created(function ($review) {
            $review->product->updateReviewStats();
        });

        // Update product stats when review is updated
        static::updated(function ($review) {
            $review->product->updateReviewStats();
        });

        // Update product stats when review is deleted
        static::deleted(function ($review) {
            $review->product->updateReviewStats();
        });
    }

    /**
     * Get the product that owns the review.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user that owns the review.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include reviews with a specific rating.
     */
    public function scopeWithRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope a query to only include reviews for a specific product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query to only include reviews by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to get recent reviews (last 30 days).
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    /**
     * Get formatted rating with stars.
     */
    public function getFormattedRatingAttribute()
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Get short comment for display.
     */
    public function getShortCommentAttribute()
    {
        return strlen($this->comment) > 100 ? substr($this->comment, 0, 100) . '...' : $this->comment;
    }
}
