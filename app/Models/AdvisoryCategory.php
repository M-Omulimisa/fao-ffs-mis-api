<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvisoryCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'icon',
        'order',
        'status',
        'created_by_id',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the user who created this category
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get all posts in this category
     */
    public function posts()
    {
        return $this->hasMany(AdvisoryPost::class, 'category_id');
    }

    /**
     * Get published posts count
     */
    public function getPublishedPostsCountAttribute()
    {
        return $this->posts()->published()->count();
    }

    /**
     * Scope to get only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope to order by order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    use \App\Traits\TitleCase;

    // ── Title Case accessors & mutators ──────────────────────────────────────

    public function getNameAttribute($value): ?string
    {
        return $value !== null ? $this->toTitleCase($value) : null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value !== null ? $this->toTitleCase($value) : null;
    }
}
