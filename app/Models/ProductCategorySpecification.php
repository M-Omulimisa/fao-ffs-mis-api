<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategorySpecification extends Model
{
    use HasFactory;

    protected $table = 'product_category_specifications';

    protected $fillable = [
        'product_category_id',
        'name',
        'is_required',
        'attribute_type'
    ];

    /**
     * Get the product category that owns this specification.
     */
    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Check if the specification is required.
     */
    public function getIsRequiredBoolAttribute()
    {
        return $this->is_required === 'Yes';
    }

    /**
     * Scope to get only required specifications.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', 'Yes');
    }

    /**
     * Scope to get only optional specifications.
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', 'No');
    }
}
