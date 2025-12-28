<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class ProductionProtocol extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'enterprise_id',
        'activity_name',
        'activity_description',
        'start_time',
        'end_time',
        'is_compulsory',
        'photo',
        'order',
        'weight',
        'is_active',
        'created_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'enterprise_id' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'is_compulsory' => 'boolean',
        'order' => 'integer',
        'weight' => 'integer',
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
        'duration_weeks',
        'duration_text',
        'start_time_text',
        'end_time_text',
        'compulsory_text',
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
                'enterprise_id' => 'required|exists:enterprises,id',
                'activity_name' => 'required|string|max:255',
                'activity_description' => 'nullable|string',
                'start_time' => 'required|integer|min:0',
                'end_time' => 'required|integer|min:0',
                'is_compulsory' => 'boolean',
                'photo' => 'nullable|string',
                'order' => 'integer|min:0',
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            // Validate that end_time is greater than or equal to start_time
            if ($model->end_time < $model->start_time) {
                throw new \Exception('End time must be greater than or equal to start time.');
            }

            // Validate against enterprise duration
            $enterprise = Enterprise::find($model->enterprise_id);
            if ($enterprise) {
                $maxWeeks = $enterprise->duration * 4; // Convert months to weeks
                if ($model->end_time > $maxWeeks) {
                    throw new \Exception('End time cannot exceed enterprise duration of ' . $maxWeeks . ' weeks.');
                }
            }

            // Set defaults
            if (!isset($model->is_compulsory)) {
                $model->is_compulsory = true;
            }
            if (!isset($model->is_active)) {
                $model->is_active = true;
            }
            if (!isset($model->order)) {
                $model->order = 0;
            }
        });

        // Validate before updating
        self::updating(function ($model) {
            $validator = Validator::make($model->toArray(), [
                'enterprise_id' => 'required|exists:enterprises,id',
                'activity_name' => 'required|string|max:255',
                'activity_description' => 'nullable|string',
                'start_time' => 'required|integer|min:0',
                'end_time' => 'required|integer|min:0',
                'is_compulsory' => 'boolean',
                'photo' => 'nullable|string',
                'order' => 'integer|min:0',
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            // Validate that end_time is greater than or equal to start_time
            if ($model->end_time < $model->start_time) {
                throw new \Exception('End time must be greater than or equal to start time.');
            }

            // Validate against enterprise duration
            $enterprise = Enterprise::find($model->enterprise_id);
            if ($enterprise) {
                $maxWeeks = $enterprise->duration * 4; // Convert months to weeks
                if ($model->end_time > $maxWeeks) {
                    throw new \Exception('End time cannot exceed enterprise duration of ' . $maxWeeks . ' weeks.');
                }
            }
        });
    }

    /**
     * Get the enterprise that owns the protocol.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    /**
     * Get user who created this protocol.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the duration in weeks.
     *
     * @return int
     */
    public function getDurationWeeksAttribute()
    {
        return $this->end_time - $this->start_time;
    }

    /**
     * Get the duration text.
     *
     * @return string
     */
    public function getDurationTextAttribute()
    {
        $weeks = $this->duration_weeks;
        return Enterprise::weeksToText($weeks);
    }

    /**
     * Get the start time text.
     *
     * @return string
     */
    public function getStartTimeTextAttribute()
    {
        return 'Week ' . $this->start_time;
    }

    /**
     * Get the end time text.
     *
     * @return string
     */
    public function getEndTimeTextAttribute()
    {
        return 'Week ' . $this->end_time;
    }

    /**
     * Get the compulsory text.
     *
     * @return string
     */
    public function getCompulsoryTextAttribute()
    {
        return $this->is_compulsory ? 'Mandatory' : 'Optional';
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
     * Scope to filter by enterprise.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $enterpriseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEnterprise($query, $enterpriseId)
    {
        return $query->where('enterprise_id', $enterpriseId);
    }

    /**
     * Scope to get only compulsory protocols.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompulsory($query)
    {
        return $query->where('is_compulsory', true);
    }

    /**
     * Scope to get only optional protocols.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOptional($query)
    {
        return $query->where('is_compulsory', false);
    }

    /**
     * Scope to get only active protocols.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by start time.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByTime($query, $direction = 'asc')
    {
        return $query->orderBy('start_time', $direction);
    }

    /**
     * Check if this protocol overlaps with another protocol.
     *
     * @param ProductionProtocol $other
     * @return bool
     */
    public function overlapsWith(ProductionProtocol $other)
    {
        return !($this->end_time < $other->start_time || $this->start_time > $other->end_time);
    }

    /**
     * Get protocols that overlap with this one.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverlappingProtocols()
    {
        return static::where('enterprise_id', $this->enterprise_id)
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->whereBetween('start_time', [$this->start_time, $this->end_time])
                    ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                    ->orWhere(function ($q) {
                        $q->where('start_time', '<=', $this->start_time)
                            ->where('end_time', '>=', $this->end_time);
                    });
            })
            ->get();
    }
}
