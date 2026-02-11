<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketPrice extends Model
{
    protected $table = 'market_prices';

    protected $fillable = [
        'product_id',
        'district_id',
        'sub_county_id',
        'market_name',
        'price',
        'price_min',
        'price_max',
        'currency',
        'unit',
        'quantity',
        'date',
        'source',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'district_id' => 'integer',
        'sub_county_id' => 'integer',
        'price' => 'decimal:2',
        'price_min' => 'decimal:2',
        'price_max' => 'decimal:2',
        'date' => 'date',
        'created_by' => 'integer',
    ];

    /**
     * Get the product for this price
     */
    public function product()
    {
        return $this->belongsTo(MarketPriceProduct::class, 'product_id');
    }

    /**
     * Get the district for this price
     */
    public function district()
    {
        return $this->belongsTo(Location::class, 'district_id');
    }

    /**
     * Get the sub county for this price
     */
    public function sub_county()
    {
        return $this->belongsTo(Location::class, 'sub_county_id');
    }

    /**
     * Get the district name (accessor only, no relationship)
     */
    public function getDistrictNameAttribute()
    {
        if ($this->district_id) {
            $district = \DB::table('districts')->where('id', $this->district_id)->first();
            return $district ? $district->district : null;
        }
        return null;
    }

    /**
     * Get the sub county name (accessor only, no relationship)
     */
    public function getSubCountyNameAttribute()
    {
        if ($this->sub_county_id) {
            $location = \DB::table('locations')->where('id', $this->sub_county_id)->first();
            return $location ? $location->name : null;
        }
        return null;
    }

    /**
     * Get the user who created this price
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only active prices
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope to filter by product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to filter by district
     */
    public function scopeInDistrict($query, $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    /**
     * Scope to filter by sub county
     */
    public function scopeInSubCounty($query, $subCountyId)
    {
        return $query->where('sub_county_id', $subCountyId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Get product name
     */
    public function getProductNameAttribute()
    {
        return $this->product ? $this->product->name : null;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return $this->currency . ' ' . number_format($this->price, 2);
    }

    /**
     * Get price range text
     */
    public function getPriceRangeAttribute()
    {
        if ($this->price_min && $this->price_max) {
            return $this->currency . ' ' . number_format($this->price_min, 2) . ' - ' . number_format($this->price_max, 2);
        }
        return $this->formatted_price;
    }
}
