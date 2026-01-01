<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketPriceProduct extends Model
{
    protected $table = 'market_price_products';

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'photo',
        'unit',
        'status',
        'created_by',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * Get the category for this product
     */
    public function category()
    {
        return $this->belongsTo(MarketPriceCategory::class, 'category_id');
    }

    /**
     * Get the prices for this product
     */
    public function prices()
    {
        return $this->hasMany(MarketPrice::class, 'product_id');
    }

    /**
     * Get active prices for this product
     */
    public function activePrices()
    {
        return $this->hasMany(MarketPrice::class, 'product_id')
            ->where('status', 'Active')
            ->orderBy('date', 'desc');
    }

    /**
     * Get the latest price for this product
     */
    public function latestPrice()
    {
        return $this->hasOne(MarketPrice::class, 'product_id')
            ->where('status', 'Active')
            ->latest('date');
    }

    /**
     * Get the user who created this product
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only active products
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
     * Get category name
     */
    public function getCategoryNameAttribute()
    {
        return $this->category ? $this->category->name : null;
    }
}
