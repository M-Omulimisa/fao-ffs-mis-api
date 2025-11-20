<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'share_price',
        'total_shares',
        'shares_sold',
        'image',
        'total_investment',
        'total_returns',
        'total_expenses',
        'total_profits',
        'created_by_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'share_price' => 'decimal:2',
        'total_investment' => 'decimal:2',
        'total_returns' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'total_profits' => 'decimal:2',
    ];

    protected $appends = [
        'status_label',
        'net_profit',
        'roi_percentage',
        'available_for_purchase',
        'available_for_disbursement',
        'formatted_total_investment',
        'formatted_total_profits',
        'formatted_total_expenses',
        'formatted_total_returns',
    ];

    // Boot method - Model Events
    protected static function boot()
    {
        parent::boot();

        // When creating a new project, initialize computed fields
        static::creating(function ($project) {
            $project->shares_sold = $project->shares_sold ?? 0;
            $project->total_investment = $project->total_investment ?? 0;
            $project->total_returns = $project->total_returns ?? 0;
            $project->total_expenses = $project->total_expenses ?? 0;
            $project->total_profits = $project->total_profits ?? 0;
        });
    }

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function shares()
    {
        return $this->hasMany(ProjectShare::class);
    }

    public function transactions()
    {
        return $this->hasMany(ProjectTransaction::class);
    }

    public function payments()
    {
        return $this->hasMany(UniversalPayment::class);
    }

    public function disbursements()
    {
        return $this->hasMany(Disbursement::class);
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getNetProfitAttribute()
    {
        return $this->total_profits - $this->total_expenses;
    }

    public function getRoiPercentageAttribute()
    {
        if ($this->total_investment == 0) {
            return 0;
        }
        return ($this->net_profit / $this->total_investment) * 100;
    }

    public function getAvailableForPurchaseAttribute()
    {
        return $this->status === 'ongoing';
    }

    public function getAvailableForDisbursementAttribute()
    {
        return $this->total_profits - $this->total_expenses - $this->total_returns;
    }

    public function getFormattedTotalInvestmentAttribute()
    {
        return number_format($this->total_investment, 0);
    }

    public function getFormattedTotalProfitsAttribute()
    {
        return number_format($this->total_profits, 0);
    }

    public function getFormattedTotalExpensesAttribute()
    {
        return number_format($this->total_expenses, 0);
    }

    public function getFormattedTotalReturnsAttribute()
    {
        return number_format($this->total_returns, 0);
    }

    // Helper Methods
    public function isOngoing()
    {
        return $this->status === 'ongoing';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isOnHold()
    {
        return $this->status === 'on_hold';
    }

    // Update computed fields
    public function updateComputedFields()
    {
        // Total shares sold
        $this->shares_sold = $this->shares()->sum('number_of_shares');

        // Total investment (from share purchases)
        $this->total_investment = $this->transactions()
            ->where('source', 'share_purchase')
            ->sum('amount');

        // Total returns distributed
        $this->total_returns = abs($this->transactions()
            ->where('source', 'returns_distribution')
            ->sum('amount'));

        // Total expenses
        $this->total_expenses = abs($this->transactions()
            ->where('type', 'expense')
            ->whereIn('source', ['project_expense'])
            ->sum('amount'));

        // Total profits
        $this->total_profits = $this->transactions()
            ->where('type', 'income')
            ->whereIn('source', ['project_profit'])
            ->sum('amount');

        $this->saveQuietly(); // Save without triggering events
    }

    /**
     * Recalculate all project computed fields from transactions and disbursements
     * This is the authoritative method that should be called after any transaction changes
     */
    public function recalculateFromTransactions()
    {
        DB::transaction(function () {
            // Get all transactions for this project
            $transactions = $this->transactions()->get();
            
            // Reset all computed fields
            $shares_sold = 0;
            $total_investment = 0;
            $total_returns = 0;
            $total_expenses = 0;
            $total_profits = 0;
            
            // Calculate from transactions
            foreach ($transactions as $transaction) {
                switch ($transaction->source) {
                    case 'share_purchase':
                        $total_investment += abs($transaction->amount);
                        break;
                    case 'returns_distribution':
                        $total_returns += abs($transaction->amount);
                        break;
                    case 'project_expense':
                        $total_expenses += abs($transaction->amount);
                        break;
                    case 'project_profit':
                        $total_profits += abs($transaction->amount);
                        break;
                }
            }
            
            // Add disbursements to total returns
            // Disbursements are stored separately and create AccountTransactions for investors
            $total_disbursements = Disbursement::where('project_id', $this->id)
                ->whereNull('deleted_at')
                ->sum('amount');
            $total_returns += abs($total_disbursements);
            
            // Get shares sold from project_shares
            $shares_sold = $this->shares()->sum('number_of_shares');
            
            // Update fields
            $this->shares_sold = $shares_sold;
            $this->total_investment = $total_investment;
            $this->total_returns = $total_returns;
            $this->total_expenses = $total_expenses;
            $this->total_profits = $total_profits;
            
            $this->saveQuietly();
        });
    }

    // Scopes
    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOnHold($query)
    {
        return $query->where('status', 'on_hold');
    }

    public function scopeAvailableForInvestment($query)
    {
        return $query->where('status', 'ongoing');
    }
}
