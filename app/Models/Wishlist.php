<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'product_name',
        'product_price',
        'product_sale_price',
        'product_photo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'product_price' => 'decimal:2',
        'product_sale_price' => 'decimal:2',
    ];

    /**
     * Get the user that owns the wishlist item.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product associated with the wishlist item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the effective price (sale price if available, otherwise regular price).
     */
    public function getEffectivePriceAttribute()
    {
        return $this->product_sale_price ?? $this->product_price;
    }

    /**
     * Check if the product is on sale.
     */
    public function getIsOnSaleAttribute()
    {
        return !is_null($this->product_sale_price) && $this->product_sale_price < $this->product_price;
    }
}
