<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Location extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'parent',
        'photo',
        'detail',
        'order',
        'code',
        'locked_down',
        'type',
        'processed',
        'farm_count',
        'cattle_count',
        'goat_count',
        'sheep_count',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'locked_down' => 'boolean',
        'order' => 'integer',
        'parent' => 'integer',
        'farm_count' => 'integer',
        'cattle_count' => 'integer',
        'goat_count' => 'integer',
        'sheep_count' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['name_text'];

    /**
     * Get sub-counties with district names as associative array
     * Format: [id => "Sub-county name, District name"]
     */
    public static function get_sub_counties_array()
    {
        $subs = [];
        foreach (Location::get_sub_counties() as $key => $value) {
            $subs[$value->id] = ((string)($value->name)) . ", " . ((string)($value->district_name));
        }
        return $subs;
    }

    /**
     * Get the parent district relationship
     */
    public function district()
    {
        return $this->belongsTo(Location::class, 'parent');
    }

    /**
     * Get parent location relationship
     */
    public function parent_location()
    {
        return $this->belongsTo(Location::class, 'parent');
    }

    /**
     * Get child locations relationship
     */
    public function children()
    {
        return $this->hasMany(Location::class, 'parent');
    }

    /**
     * Get sub-counties with their district names
     * Returns sub-counties (locations with parent > 0 and type = 'Sub-county')
     */
    public static function get_sub_counties()
    {
        $sql = "SELECT 
                    locations.id as id, 
                    locations.name as name, 
                    parent_location.name as district_name 
                FROM locations
                LEFT JOIN locations as parent_location ON locations.parent = parent_location.id
                WHERE locations.parent > 0 
                AND locations.type IN ('Sub-county', 'Subcounty', 'Sub County')
                ORDER BY locations.name ASC";
        return DB::select($sql);
    }

    /**
     * Get all districts
     * Returns locations with parent = 0 or type = 'District'
     */
    public static function get_districts()
    {
        return Location::where('type', 'District')
            ->orWhere(function ($query) {
                $query->where('parent', 0)
                    ->whereNotIn('type', ['Country', 'Region']);
            })
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * Get all regions
     */
    public static function get_regions()
    {
        return Location::where('type', 'Region')
            ->orWhere(function ($query) {
                $query->where('parent', 0)
                    ->where('name', 'LIKE', '%Region%');
            })
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * Get parishes for a specific sub-county
     */
    public static function get_parishes($subCountyId = null)
    {
        $query = Location::where('type', 'Parish');

        if ($subCountyId !== null) {
            $query->where('parent', $subCountyId);
        }

        return $query->orderBy('name', 'ASC')->get();
    }

    /**
     * Get sub-counties for a specific district
     */
    public static function get_district_sub_counties($districtId)
    {
        return Location::where('parent', $districtId)
            ->whereIn('type', ['Sub-county', 'Subcounty', 'Sub County'])
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * Get full location hierarchy path
     * Example: "Central Region > Kampala District > Kawempe Division"
     */
    public function getFullPathAttribute()
    {
        $path = [$this->name];
        $current = $this;

        while ($current->parent > 0) {
            $current = Location::find($current->parent);
            if ($current) {
                array_unshift($path, $current->name);
            } else {
                break;
            }
        }

        return implode(' > ', $path);
    }

    /**
     * Get name with parent location (appended accessor)
     * Example: "Kampala District, Central Region"
     */
    public function getNameTextAttribute()
    {
        if (((int)($this->parent)) > 0) {
            $mother = Location::find($this->parent);

            if ($mother != null) {
                return $mother->name . ", " . $this->name;
            }
        }
        return $this->name;
    }

    /**
     * Prevent deletion of locations
     */
    public static function boot()
    {
        parent::boot();
        
        self::deleting(function ($m) {
            // Check if location has children
            $childrenCount = Location::where('parent', $m->id)->count();
            if ($childrenCount > 0) {
                throw new \Exception("Cannot delete location with {$childrenCount} child locations. Delete children first.");
            }

            // Check if location is used in other tables
            // Add checks for your other tables that reference locations
            $groupsCount = \App\Models\FfsGroup::where('location_id', $m->id)->count();
            if ($groupsCount > 0) {
                throw new \Exception("Cannot delete location. It is used by {$groupsCount} groups.");
            }
        });
    }

    /**
     * Scope: Get locations by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Get root locations (no parent)
     */
    public function scopeRoots($query)
    {
        return $query->where('parent', 0)->orWhereNull('parent');
    }

    /**
     * Scope: Get locations with parent
     */
    public function scopeWithParent($query)
    {
        return $query->where('parent', '>', 0);
    }
}

