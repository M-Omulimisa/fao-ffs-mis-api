<?php

namespace App\Models;

use Dflydev\DotAccessData\Util;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Import the new specification models
use App\Models\ProductHasSpecification;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name', 'price', 'description', 'tags', 'local_id', 'category', 'feature_photo',
        'colors', 'keywords', 'sizes', 'summary', 'quantity', 'min_quantity_alert',
        'review_count', 'average_rating', 'home_section_1', 'home_section_2', 'home_section_3',
        // Compression fields
        'is_compressed', 'compress_status', 'compress_status_message', 
        'original_size', 'compressed_size', 'compression_ratio',
        'compression_method', 'original_image_url', 'compressed_image_url',
        'tinify_model_id', 'compression_started_at', 'compression_completed_at'
    ];

    protected $casts = [
        'compression_started_at' => 'datetime',
        'compression_completed_at' => 'datetime',
        'original_size' => 'decimal:2',
        'compressed_size' => 'decimal:2',
        'compression_ratio' => 'decimal:4',
        'summary' => 'json',
    ];

    public static function boot()
    {
        parent::boot();
        //created
        self::created(function ($m) {});

        //cerating
        self::creating(function ($m) {
            $pro_with_same_vid = Product::where('local_id', $m->local_id)->first();
            if ($pro_with_same_vid != null) {
                throw new \Exception("Product with same local_id already exists", 1);
            }
        });

        //updating
        self::updating(function ($m) {

            return $m;
        });
        //updated
        self::updated(function ($m) {
            // $m->sync(Utils::get_stripe()); // Disabled to prevent memory issues
        });

        self::deleting(function ($m) {
            try {
                $imgs = Image::where('parent_id', $m->id)->orwhere('product_id', $m->id)->get();
                foreach ($imgs as $img) {
                    $img->delete();
                }
                
                // Delete all product specifications when product is deleted
                ProductHasSpecification::where('product_id', $m->id)->delete();
            } catch (\Throwable $th) {
                //throw $th;
            }
        });
    }

    //getter for feature_photo
    public function getFeaturePhotoAttribute($value)
    {

        //check if value contains images/
        if (str_contains($value, 'images/')) {
            return $value;
        }
        $value = 'images/' . $value;
        return $value;
    }



    public function update_stripe_price($new_price)
    {

        return; 
    }

    public function sync($stripe)
    {

        return;
    }
    
    // Memory-safe getRatesAttribute implementation
    public function getRatesAttribute()
    {
        try {
            // Limit to 10 images maximum to prevent memory issues
            $imgs = Image::where('product_id', $this->id)
                ->limit(10)
                ->select(['id', 'src', 'thumbnail', 'product_id'])
                ->get();
            
            if ($imgs->isEmpty()) {
                return json_encode([]);
            }
            
            return json_encode($imgs);
        } catch (\Throwable $th) {
            // Return empty array on error to prevent crashes
            return json_encode([]);
        }
    }


    protected $appends = ['category_text', 'tags_array', 'rates'];
    public function getCategoryTextAttribute()
    {
        $d = ProductCategory::find($this->category);
        if ($d == null) {
            return 'Not Category.';
        }
        return $d->category;
    }

    //getter for colors from json
    public function getColorsAttribute($value)
    {
        if ($value === null) {
            return '';
        }
        $resp = str_replace('\"', '"', $value);
        $resp = str_replace('[', '', $resp);
        $resp = str_replace(']', '', $resp);
        $resp = str_replace('"', '', $resp);
        return $resp;
    }

    //setter for colors to json
    public function setColorsAttribute($value)
    {
        if ($value != null) {
            if (strlen($value) > 2) {
                $value = json_encode($value);
                $this->attributes['colors'] = $value;
            }
        }
    }

    //sett keywords to json
    public function setKeywordsAttribute($value)
    {
        if ($value != null) {
            if (strlen($value) > 2) {
                $value = json_encode($value);
                $this->attributes['keywords'] = $value;
            }
        }
    }

    //getter for keywords from json
    public function getKeywordsAttribute($value)
    {
        if ($value == null) {
            return [];
        }

        try {
            $resp = json_decode($value);
            return $resp;
        } catch (\Throwable $th) {
            return [];
        }

        return $resp;
    }


    //getter for sizes
    public function getSizesAttribute($value)
    {
        if ($value === null) {
            return '';
        }
        $resp = str_replace('\"', '"', $value);
        $resp = str_replace('[', '', $resp);
        $resp = str_replace(']', '', $resp);
        $resp = str_replace('"', '', $resp);
        return $resp;
    }

    //setter for sizes
    public function setSizesAttribute($value)
    {
        if ($value != null) {
            if (strlen($value) > 2) {
                $value = json_encode($value);
                $this->attributes['sizes'] = $value;
            }
        }
    }

    //has many Image
    public function images()
    {
        return $this->hasMany(Image::class, 'product_id', 'id');
    }

    //has many ProductHasSpecification
    public function specifications()
    {
        return $this->hasMany(ProductHasSpecification::class, 'product_id', 'id');
    }

    //has many ProductHasAttribute  
    public function attributes()
    {
        return $this->hasMany(ProductHasAttribute::class, 'product_id', 'id');
    }

    //belongs to ProductCategory
    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'category', 'id');
    }

    /**
     * Get specification value by name - TEMPORARILY DISABLED
     */
    // public function getSpecificationValue($specificationName)
    // {
    //     $specification = $this->specifications()->where('name', $specificationName)->first();
    //     return $specification ? $specification->value : null;
    // }

    /**
     * Set specification value by name - TEMPORARILY DISABLED
     */
    // public function setSpecificationValue($specificationName, $value)
    // {
    //     return $this->specifications()->updateOrCreate(
    //         ['name' => $specificationName],
    //         ['value' => $value]
    //     );
    // }

    /**
     * Get all specifications as key-value pairs - TEMPORARILY DISABLED
     */
    // public function getSpecificationsArrayAttribute()
    // {
    //     try {
    //         return $this->productSpecifications()->get()->map(function ($spec) {
    //             return [
    //                 'name' => $spec->name,
    //                 'value' => $spec->value
    //             ];
    //         })->toArray();
    //     } catch (\Exception $e) {
    //         return [];
    //     }
    // }

    /**
     * Get tags as array
     */
    public function getTagsArrayAttribute()
    {
        if (empty($this->tags)) {
            return [];
        }
        return array_map('trim', explode(',', $this->tags));
    }

    /**
     * Set tags from array
     */
    public function setTagsFromArray(array $tags)
    {
        $this->tags = implode(',', array_map('trim', $tags));
        return $this;
    }

    /**
     * Check if product has a specific tag
     */
    public function hasTag($tag)
    {
        return in_array(trim($tag), $this->tags_array);
    }

    /**
     * Add a tag to the product
     */
    public function addTag($tag)
    {
        $tags = $this->tags_array;
        $tag = trim($tag);
        
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->setTagsFromArray($tags);
        }
        
        return $this;
    }

    /**
     * Remove a tag from the product
     */
    public function removeTag($tag)
    {
        $tags = $this->tags_array;
        $tag = trim($tag);
        
        $tags = array_filter($tags, function($t) use ($tag) {
            return $t !== $tag;
        });
        
        $this->setTagsFromArray($tags);
        return $this;
    }

    /**
     * Scope to filter products by tags
     */
    public function scopeWithTag($query, $tag)
    {
        return $query->where('tags', 'LIKE', '%' . trim($tag) . '%');
    }

    /**
     * Scope to filter products by multiple tags (OR condition)
     */
    public function scopeWithAnyTag($query, array $tags)
    {
        return $query->where(function($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhere('tags', 'LIKE', '%' . trim($tag) . '%');
            }
        });
    }

    /**
     * Scope to filter products by multiple tags (AND condition)
     */
    public function scopeWithAllTags($query, array $tags)
    {
        foreach ($tags as $tag) {
            $query->where('tags', 'LIKE', '%' . trim($tag) . '%');
        }
        return $query;
    }

    /**
     * Search products by name, description, or tags with enhanced scoring
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('name', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('description', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('tags', 'LIKE', '%' . $searchTerm . '%');
        });
    }

    /**
     * Enhanced search with tag prioritization and scoring
     */
    public function scopeEnhancedSearch($query, $searchTerm)
    {
        $searchWords = array_filter(explode(' ', trim($searchTerm)), function($word) {
            return strlen(trim($word)) > 2;
        });

        return $query->where(function($q) use ($searchTerm, $searchWords) {
            // Exact name match (highest priority)
            $q->where('name', 'LIKE', '%' . $searchTerm . '%');
            
            // Exact tag match (high priority)
            $q->orWhere('tags', 'LIKE', '%' . $searchTerm . '%');
            
            // Individual word matches in tags
            foreach ($searchWords as $word) {
                $q->orWhere('tags', 'LIKE', '%' . trim($word) . '%');
            }
            
            // Description match (lower priority)
            $q->orWhere('description', 'LIKE', '%' . $searchTerm . '%');
        })
        ->orderByRaw("
            CASE 
                WHEN name LIKE '%{$searchTerm}%' THEN 1
                WHEN tags LIKE '%{$searchTerm}%' THEN 2
                WHEN description LIKE '%{$searchTerm}%' THEN 3
                ELSE 4
            END
        ");
    }

    /**
     * Get search relevance score for a product
     */
    public function getSearchRelevanceScore($searchTerm)
    {
        $score = 0;
        $searchLower = strtolower($searchTerm);
        $nameLower = strtolower($this->name);
        $descriptionLower = strtolower($this->description ?? '');
        $tagsLower = strtolower($this->tags ?? '');

        // Exact matches
        if (stripos($nameLower, $searchLower) !== false) {
            $score += 100;
        }
        if (stripos($tagsLower, $searchLower) !== false) {
            $score += 80;
        }
        if (stripos($descriptionLower, $searchLower) !== false) {
            $score += 30;
        }

        // Word matches in tags
        $searchWords = explode(' ', $searchLower);
        $tags = array_map('trim', explode(',', $tagsLower));
        
        foreach ($searchWords as $word) {
            if (strlen(trim($word)) > 2) {
                foreach ($tags as $tag) {
                    if (stripos($tag, trim($word)) !== false) {
                        $score += 20;
                    }
                }
            }
        }

        return $score;
    }

    /**
     * Get product specifications relationship - TEMPORARILY DISABLED
     */
    // public function productSpecifications()
    // {
    //     return $this->hasMany(ProductHasSpecification::class, 'product_id');
    // }

    /**
     * Get all reviews for this product
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get recent reviews for this product
     */
    public function recentReviews($limit = 5)
    {
        return $this->hasMany(Review::class)->latest()->limit($limit);
    }

    /**
     * Get reviews with specific rating
     */
    public function reviewsWithRating($rating)
    {
        return $this->hasMany(Review::class)->where('rating', $rating);
    }

    /**
     * Get formatted average rating with stars
     */
    public function getFormattedRatingAttribute()
    {
        $rating = round($this->average_rating);
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }

    /**
     * Check if user has reviewed this product
     */
    public function hasUserReviewed($userId)
    {
        return $this->reviews()->where('user_id', $userId)->exists();
    }

    /**
     * Get user's review for this product
     */
    public function getUserReview($userId)
    {
        return $this->reviews()->where('user_id', $userId)->first();
    }

    /**
     * Calculate and update review statistics
     */
    public function updateReviewStats()
    {
        $reviews = $this->reviews();
        $this->review_count = $reviews->count();
        $this->average_rating = $reviews->avg('rating') ?: 0;
        $this->save();
    }

    /**
     * Relationship to TinifyModel used for compression
     */
    public function tinifyModel()
    {
        return $this->belongsTo(TinifyModel::class);
    }

    /**
     * Scope for uncompressed products
     */
    public function scopeUncompressed($query)
    {
        return $query->where(function($q) {
            $q->where('is_compressed', '!=', 'yes')
              ->orWhereNull('is_compressed');
        });
    }

    /**
     * Scope for compressed products
     */
    public function scopeCompressed($query)
    {
        return $query->where('is_compressed', 'yes');
    }

    /**
     * Get compression status display
     */
    public function getCompressionStatusDisplayAttribute()
    {
        if ($this->is_compressed === 'yes') {
            return '✅ Compressed';
        } elseif ($this->compress_status === 'pending') {
            return '⏳ Pending';
        } elseif ($this->compress_status === 'failed') {
            return '❌ Failed';
        }
        return '⭕ Not Compressed';
    }
}
