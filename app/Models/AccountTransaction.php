<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'transaction_date',
        'description',
        'source',
        'related_disbursement_id',
        'created_by_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_date',
        'source_label',
        'type',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function relatedDisbursement()
    {
        return $this->belongsTo(Disbursement::class, 'related_disbursement_id');
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        $prefix = $this->amount >= 0 ? '+' : '';
        return $prefix . 'UGX ' . number_format(abs($this->amount), 2);
    }

    public function getFormattedDateAttribute()
    {
        return $this->transaction_date->format('d M Y');
    }

    public function getSourceLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->source));
    }

    public function getTypeAttribute()
    {
        return $this->amount >= 0 ? 'credit' : 'debit';
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeCredit($query)
    {
        return $query->where('amount', '>=', 0);
    }

    public function scopeDebit($query)
    {
        return $query->where('amount', '<', 0);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
