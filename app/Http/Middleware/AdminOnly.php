<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;

/**
 * Admin Only Middleware
 * 
 * This middleware ensures that only users with 'admin' role
 * can access certain routes. Managers and other roles will be denied.
 */
class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Admin::user();

        // Check if user is logged in and has admin role
        if (!$user || !$user->isRole('admin')) {
            // Return error response based on request type
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied. Admin privileges required.'
                ], 403);
            }

            // For web requests, redirect back with error message
            admin_toastr('Access denied. Admin privileges required.', 'error');
            return back();
        }

        return $next($request);
    }
}
