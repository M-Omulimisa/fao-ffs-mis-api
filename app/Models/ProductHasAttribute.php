<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductHasAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'value'
    ];

    /**
     * Get the product that owns this attribute value.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get attributes for a specific product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get a specific attribute by name for a product
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Get attribute value as key-value pair
     */
    public function getKeyValueAttribute()
    {
        return [$this->name => $this->value];
    }
}
