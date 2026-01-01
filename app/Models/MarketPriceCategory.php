<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketPriceCategory extends Model
{
    protected $table = 'market_price_categories';

    protected $fillable = [
        'name',
        'description',
        'photo',
        'icon',
        'status',
        'order',
        'created_by',
    ];

    protected $casts = [
        'order' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * Get the products for this category
     */
    public function products()
    {
        return $this->hasMany(MarketPriceProduct::class, 'category_id');
    }

    /**
     * Get active products for this category
     */
    public function activeProducts()
    {
        return $this->hasMany(MarketPriceProduct::class, 'category_id')
            ->where('status', 'Active');
    }

    /**
     * Get the user who created this category
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Get full photo URL
     */
    public function getPhotoUrlAttribute()
    {
        if (!$this->photo) {
            return null;
        }

        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }

        return url('storage/' . $this->photo);
    }

    /**
     * Get products count
     */
    public function getProductsCountAttribute()
    {
        return $this->products()->count();
    }
}
