<?php

namespace App\Http\Middleware;

use App\Models\Utils;
use App\Models\User;
use Closure;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Http\Request;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // UPDATED VERSION - v2.0
        // Get user ID from headers (like the frontend sends)
        $user_id = 0;
        
        // Check multiple header names to match frontend implementation
        if ($request->header('User-Id')) {
            $user_id = (int) $request->header('User-Id');
        } elseif ($request->header('HTTP_USER_ID')) {
            $user_id = (int) $request->header('HTTP_USER_ID');
        } elseif ($request->header('user_id')) {
            $user_id = (int) $request->header('user_id');
        }

        // Debug logging
        \Log::info('EnsureTokenIsValid - User ID from header: ' . $user_id);
        
        // Check if user_id is provided
        if ($user_id < 1) {
            \Log::error('EnsureTokenIsValid - No user ID provided');
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'User ID is required in headers',
                'data' => null
            ], 401);
        }

        // Find the user in the users table (not administrators)
        $u = User::find($user_id);
        \Log::info('EnsureTokenIsValid - User lookup result: ' . ($u ? 'Found' : 'NOT FOUND'));
        \Log::info('EnsureTokenIsValid - About to check if user is null');
        
        if ($u == null) {
            \Log::error('INSIDE NULL CHECK - This should NOT appear if user exists!');
            \Log::error('EnsureTokenIsValid - User ' . $user_id . ' not found in users table');
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'User not found. [MW v2.0]',
                'data' => null,
                'debug' => [
                    'user_id_from_header' => $user_id,
                    'middleware_version' => 'v2.0'
                ]
            ], 401);
        }

        // Add user to request for controller access
        $request->user = $user_id;
        $request->userModel = $u;

        \Log::info('EnsureTokenIsValid - Passing to next middleware/controller');
        $response = $next($request);
        \Log::info('EnsureTokenIsValid - Response received: ' . $response->getStatusCode());
        
        return $response;
    }
}
