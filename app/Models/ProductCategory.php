<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;
    protected $table = 'product_categories';
    
    protected $fillable = [
        'category',
        'image',
        'banner_image',
        'show_in_banner',
        'show_in_categories',
        'is_parent',
        'parent_id',
        'icon',
        'is_first_banner',
        'first_banner_image',
    ];
    
    protected $casts = [
        'attributes' => 'json',
    ];

    //getter for updated_at
    /* public function getUpdatedAtAttribute($value)
    {
        return Product::where('category_id', $this->id)->count();
    } */

    public function getCategoryTextAttribute($value)
    {
        return Product::where('category', $this->id)->count();
    }

    /**
     * Get the actual product count for this category
     */
    public function getProductCountAttribute()
    {
        return Product::where('category', $this->id)->count();
    }

    /**
     * Get the display count for mega menu (actual count + 10)
     */
    public function getDisplayCountAttribute()
    {
        $actualCount = Product::where('category', $this->id)->count();
        return $actualCount + 10;
    }

    //appends category_text and new attributes
    protected $appends = ['category_text', 'product_count', 'display_count'];

    /**
     * Get the parent category if this is a subcategory
     */
    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    /**
     * Get all subcategories if this is a main category
     */
    public function subcategories()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    /**
     * Get all specifications for this category
     */
    public function specifications()
    {
        return $this->hasMany(\App\Models\ProductCategorySpecification::class, 'product_category_id');
    }

    /**
     * Get all products in this category
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category');
    }

    /**
     * Get only required specifications for this category
     */
    public function requiredSpecifications()
    {
        return $this->hasMany(\App\Models\ProductCategorySpecification::class)->where('is_required', 'Yes');
    }

    /**
     * Get only optional specifications for this category
     */
    public function optionalSpecifications()
    {
        return $this->hasMany(\App\Models\ProductCategorySpecification::class)->where('is_required', 'No');
    }

    /**
     * Scope to get only main categories
     */
    public function scopeMainCategories($query)
    {
        return $query->where('is_parent', 'Yes');
    }

    /**
     * Scope to get only subcategories
     */
    public function scopeSubCategories($query)
    {
        return $query->where('is_parent', 'No');
    }

    /**
     * Scope to get categories visible in mega menu
     */
    public function scopeVisibleInCategories($query)
    {
        return $query->where('show_in_categories', 'Yes');
    }
}
