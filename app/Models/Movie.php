<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Movie extends Model
{
    use HasFactory;

    protected $table = 'movies';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'poster_image',
        'category',
        'genre',
        'year',
        'rating',
        'duration',
        'source_url',
        'quality',
        'language',
        'status',
        'fix_status',
        'fix_message',
        'last_fix_date',
    ];

    protected $casts = [
        'last_fix_date' => 'datetime',
        'year' => 'integer',
        'duration' => 'integer',
    ];

    protected $appends = ['fix_status_text', 'status_color', 'duration_text'];

    // ─── Fix Status Constants ────────────────────────────
    const FIX_PENDING = 'pending';
    const FIX_SUCCESS = 'success';
    const FIX_FAILED  = 'failed';

    public static function getFixStatuses(): array
    {
        return [
            self::FIX_PENDING => 'Pending',
            self::FIX_SUCCESS => 'Success',
            self::FIX_FAILED  => 'Failed',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'active'   => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    public static function getQualities(): array
    {
        return [
            '4K'    => '4K',
            '1080p' => '1080p',
            '720p'  => '720p',
            '480p'  => '480p',
            '360p'  => '360p',
        ];
    }

    // ─── Scopes ──────────────────────────────────────────
    public function scopeFixPending($query)
    {
        return $query->where('fix_status', self::FIX_PENDING);
    }

    public function scopeFixSuccess($query)
    {
        return $query->where('fix_status', self::FIX_SUCCESS);
    }

    public function scopeFixFailed($query)
    {
        return $query->where('fix_status', self::FIX_FAILED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ─── Accessors ───────────────────────────────────────
    public function getFixStatusTextAttribute(): string
    {
        return self::getFixStatuses()[$this->fix_status] ?? ucfirst($this->fix_status ?? 'Unknown');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->fix_status) {
            self::FIX_SUCCESS => 'success',
            self::FIX_FAILED  => 'danger',
            self::FIX_PENDING => 'warning',
            default           => 'default',
        };
    }

    public function getDurationTextAttribute(): string
    {
        if (!$this->duration) return '-';
        $hours = intdiv($this->duration, 60);
        $mins = $this->duration % 60;
        return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
    }

    // ─── Helpers ─────────────────────────────────────────
    public function markFixSuccess(?string $message = null): self
    {
        $this->update([
            'fix_status'    => self::FIX_SUCCESS,
            'fix_message'   => $message,
            'last_fix_date' => now(),
        ]);
        return $this;
    }

    public function markFixFailed(string $message): self
    {
        $this->update([
            'fix_status'    => self::FIX_FAILED,
            'fix_message'   => $message,
            'last_fix_date' => now(),
        ]);
        return $this;
    }

    public function markFixPending(?string $message = null): self
    {
        $this->update([
            'fix_status'    => self::FIX_PENDING,
            'fix_message'   => $message,
            'last_fix_date' => now(),
        ]);
        return $this;
    }

    // ─── Boot ────────────────────────────────────────────
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }
            if (is_null($model->fix_status)) {
                $model->fix_status = self::FIX_PENDING;
            }
        });
    }
}
