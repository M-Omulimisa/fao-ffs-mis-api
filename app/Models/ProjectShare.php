<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectShare extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'investor_id',
        'purchase_date',
        'number_of_shares',
        'total_amount_paid',
        'share_price_at_purchase',
        'payment_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'share_price_at_purchase' => 'decimal:2',
        'total_amount_paid' => 'decimal:2',
    ];

    protected $appends = [
        'investor_name',
        'project_title',
    ];

    // Boot method - Model Events
    protected static function boot()
    {
        parent::boot();

        // After creating a share, update project's shares_sold
        static::created(function ($share) {
            if ($share->project_id) {
                $project = Project::find($share->project_id);
                if ($project) {
                    $project->recalculateFromTransactions();
                }
            }
        });

        // After updating a share, update project's shares_sold
        static::updated(function ($share) {
            if ($share->project_id) {
                $project = Project::find($share->project_id);
                if ($project) {
                    $project->recalculateFromTransactions();
                }
            }
        });

        // After deleting a share, update project's shares_sold
        static::deleted(function ($share) {
            if ($share->project_id) {
                $project = Project::find($share->project_id);
                if ($project) {
                    $project->recalculateFromTransactions();
                }
            }
        });

        // After restoring a soft-deleted share, update project
        static::restored(function ($share) {
            if ($share->project_id) {
                $project = Project::find($share->project_id);
                if ($project) {
                    $project->recalculateFromTransactions();
                }
            }
        });
    }

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function investor()
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function payment()
    {
        return $this->belongsTo(UniversalPayment::class, 'payment_id');
    }

    // Accessors
    public function getInvestorNameAttribute()
    {
        return $this->investor ? $this->investor->name : 'N/A';
    }

    public function getProjectTitleAttribute()
    {
        return $this->project ? $this->project->title : 'N/A';
    }

    // Scopes
    public function scopeForInvestor($query, $investorId)
    {
        return $query->where('investor_id', $investorId);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
