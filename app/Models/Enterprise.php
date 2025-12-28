<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class Enterprise extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'duration',
        'photo',
        'is_active',
        'created_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'duration' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be appended to the model.
     *
     * @var array
     */
    protected $appends = [
        'type_text',
        'duration_text',
        'photo_url',
    ];

    /**
     * Boot method for model events.
     */
    public static function boot()
    {
        parent::boot();

        // Validate before creating
        self::creating(function ($model) {
            $validator = Validator::make($model->toArray(), [
                'name' => 'required|string|max:255|unique:enterprises,name',
                'type' => 'required|in:livestock,crop',
                'duration' => 'required|integer|min:1|max:120',
                'description' => 'nullable|string',
                'photo' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            // Set defaults
            if (!isset($model->is_active)) {
                $model->is_active = true;
            }
        });

        // Validate before updating
        self::updating(function ($model) {
            $validator = Validator::make($model->toArray(), [
                'name' => 'required|string|max:255|unique:enterprises,name,' . $model->id,
                'type' => 'required|in:livestock,crop',
                'duration' => 'required|integer|min:1|max:120',
                'description' => 'nullable|string',
                'photo' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }
        });

        // Clean up related protocols when deleting
        self::deleting(function ($model) {
            // Soft delete all related production protocols
            $model->productionProtocols()->delete();
        });
    }

    /**
     * Get the production protocols for the enterprise.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productionProtocols()
    {
        return $this->hasMany(ProductionProtocol::class)->orderBy('start_time', 'asc');
    }

    /**
     * Get only active production protocols.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeProtocols()
    {
        return $this->hasMany(ProductionProtocol::class)
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('start_time', 'asc');
    }

    /**
     * Get only compulsory production protocols.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function compulsoryProtocols()
    {
        return $this->hasMany(ProductionProtocol::class)
            ->where('is_compulsory', true)
            ->where('is_active', true)
            ->orderBy('start_time', 'asc');
    }

    /**
     * Get user who created this enterprise.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the type text attribute.
     *
     * @return string
     */
    public function getTypeTextAttribute()
    {
        return ucfirst($this->type);
    }

    /**
     * Get the duration text attribute.
     *
     * @return string
     */
    public function getDurationTextAttribute()
    {
        $months = $this->duration;
        if ($months == 1) {
            return '1 month';
        } elseif ($months < 12) {
            return $months . ' months';
        } else {
            $years = floor($months / 12);
            $remainingMonths = $months % 12;
            $text = $years == 1 ? '1 year' : $years . ' years';
            if ($remainingMonths > 0) {
                $text .= ' ' . $remainingMonths . ' month' . ($remainingMonths > 1 ? 's' : '');
            }
            return $text;
        }
    }

    /**
     * Get the photo URL attribute.
     *
     * @return string|null
     */
    public function getPhotoUrlAttribute()
    {
        if (empty($this->photo)) {
            return null;
        }

        // If photo is already a full URL, return as is
        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }

        // Otherwise, prepend the base URL
        return url('storage/' . $this->photo);
    }

    /**
     * Scope to filter by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get only active enterprises.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the total number of protocols for this enterprise.
     *
     * @return int
     */
    public function getTotalProtocolsAttribute()
    {
        return $this->productionProtocols()->count();
    }

    /**
     * Get the total number of compulsory protocols.
     *
     * @return int
     */
    public function getCompulsoryProtocolsCountAttribute()
    {
        return $this->productionProtocols()->where('is_compulsory', true)->count();
    }

    /**
     * Convert weeks to human-readable duration.
     *
     * @param int $weeks
     * @return string
     */
    public static function weeksToText($weeks)
    {
        if ($weeks < 4) {
            return $weeks . ' week' . ($weeks > 1 ? 's' : '');
        } else {
            $months = floor($weeks / 4);
            $remainingWeeks = $weeks % 4;
            $text = $months == 1 ? '1 month' : $months . ' months';
            if ($remainingWeeks > 0) {
                $text .= ' ' . $remainingWeeks . ' week' . ($remainingWeeks > 1 ? 's' : '');
            }
            return $text;
        }
    }
}
