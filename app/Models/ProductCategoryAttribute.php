<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategoryAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_category_id',
        'name',
        'is_required'
    ];

    /**
     * Get the product category that owns this attribute.
     */
    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Check if the attribute is required.
     */
    public function getIsRequiredBoolAttribute()
    {
        return $this->is_required === 'Yes';
    }

    /**
     * Scope to get only required attributes.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', 'Yes');
    }

    /**
     * Scope to get only optional attributes.
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', 'No');
    }
}
