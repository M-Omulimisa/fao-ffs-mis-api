<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'amount',
        'transaction_date',
        'created_by_id',
        'description',
        'type',
        'source',
        'related_share_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $appends = [
        'type_label',
        'source_label',
        'formatted_amount',
    ];

    // Boot method - Model Events
    protected static function boot()
    {
        parent::boot();

        // After creating a transaction, update project computed fields
        static::created(function ($transaction) {
            if ($transaction->project_id) {
                $project = Project::find($transaction->project_id);
                if ($project) {
                    $project->recalculateFromTransactions();
                }
            }
        });

        // After updating a transaction, update project computed fields
        static::updated(function ($transaction) {
            if ($transaction->project_id) {
                $project = Project::find($transaction->project_id);
                if ($project) {
                    $project->recalculateFromTransactions();
                }
            }
        });

        // After deleting a transaction, update project computed fields
        static::deleted(function ($transaction) {
            if ($transaction->project_id) {
                $project = Project::find($transaction->project_id);
                if ($project) {
                    $project->recalculateFromTransactions();
                }
            }
        });

        // After restoring a soft-deleted transaction, update project
        static::restored(function ($transaction) {
            if ($transaction->project_id) {
                $project = Project::find($transaction->project_id);
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function relatedShare()
    {
        return $this->belongsTo(ProjectShare::class, 'related_share_id');
    }

    // Accessors
    public function getTypeLabelAttribute()
    {
        return ucfirst($this->type);
    }

    public function getSourceLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->source));
    }

    public function getFormattedAmountAttribute()
    {
        $prefix = $this->type === 'income' ? '+' : '-';
        return $prefix . number_format($this->amount, 2);
    }

    // Scopes
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
