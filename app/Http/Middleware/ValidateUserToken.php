<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ValidateUserToken
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
        // SIMPLE TEST - Just pass through without validation
        // This proves the middleware is being called
        return response()->json([
            'success' => true,
            'message' => 'ValidateUserToken middleware is working!',
            'middleware' => 'NEW_VALIDATE_USER_TOKEN_v3',
            'data' => []
        ], 200);
    }
}
