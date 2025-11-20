<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductHasSpecification extends Model
{
    use HasFactory;

    protected $table = 'product_has_specifications';

    protected $fillable = [
        'product_id',
        'name',
        'value'
    ];

    /**
     * Get the product that owns this specification value.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get specifications for a specific product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get a specific specification by name for a product
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Get specification value as key-value pair
     */
    public function getKeyValueAttribute()
    {
        return [$this->name => $this->value];
    }
}
