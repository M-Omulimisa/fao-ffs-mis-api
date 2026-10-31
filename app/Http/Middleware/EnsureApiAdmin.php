<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * API Admin Middleware
 *
 * For administrative API endpoints (ledger edits, disbursements, user
 * management). Must run after EnsureTokenIsValid. "Admin" matches the mobile
 * app's own rule (user_type admin) plus the portal's administrator role.
 */
class EnsureApiAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->userModel ?? auth()->user();

        if (!$user || !(strtolower((string) $user->user_type) === 'admin' || $user->isRole('administrator'))) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Only administrators can perform this action.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
