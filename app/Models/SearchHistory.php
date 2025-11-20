<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'search_text',
        'product_ids',
        'results_count',
        'session_id'
    ];

    protected $casts = [
        'product_ids' => 'array',
        'results_count' => 'integer'
    ];

    /**
     * Get the user that owns this search history
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to get recent searches for a user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get recent searches for a session
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to get recent searches (for user or session)
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Get recent unique search terms for user or session
     */
    public static function getRecentSearches($userId = null, $sessionId = null, $limit = 10)
    {
        $query = self::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return [];
        }

        return $query->select('search_text', DB::raw('MAX(created_at) as last_searched_at'))
            ->groupBy('search_text')
            ->orderBy('last_searched_at', 'desc')
            ->limit($limit)
            ->pluck('search_text')
            ->toArray();
    }

    /**
     * Record a search with deduplication within session
     */
    public static function recordSearch($searchText, $productIds = [], $resultsCount = 0, $userId = null, $sessionId = null)
    {
        // Don't record empty searches
        if (empty(trim($searchText))) {
            return null;
        }

        // Check if this exact search was already made in this session (last 30 minutes)
        $recentDuplicate = self::query()
            ->where('search_text', $searchText)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } elseif ($sessionId) {
                    $query->where('session_id', $sessionId);
                }
            })
            ->where('created_at', '>=', now()->subMinutes(30))
            ->first();

        // If duplicate found within 30 minutes, update it instead of creating new
        if ($recentDuplicate) {
            $recentDuplicate->update([
                'product_ids' => $productIds,
                'results_count' => $resultsCount,
                'updated_at' => now()
            ]);
            return $recentDuplicate;
        }

        // Create new search history record
        return self::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'search_text' => trim($searchText),
            'product_ids' => $productIds,
            'results_count' => $resultsCount
        ]);
    }

    /**
     * Clean up old search history (keep last 50 per user/session)
     */
    public static function cleanup($userId = null, $sessionId = null)
    {
        $query = self::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return;
        }

        $totalCount = $query->count();
        
        // Keep only the most recent 50 searches
        if ($totalCount > 50) {
            $oldSearches = $query->orderBy('created_at', 'desc')
                ->skip(50)
                ->pluck('id');
            
            self::whereIn('id', $oldSearches)->delete();
        }
    }
}
