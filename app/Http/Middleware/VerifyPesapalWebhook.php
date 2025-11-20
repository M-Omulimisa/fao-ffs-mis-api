<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Config\PesapalConfig;

class VerifyPesapalWebhook
{
    /**
     * Handle an incoming request.
     * Verify that webhook requests are coming from Pesapal
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Log all webhook attempts for security monitoring
        Log::info('Pesapal webhook attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'payload' => $request->all()
        ]);

        // Verify the request is coming from Pesapal
        if (!$this->isPesapalRequest($request)) {
            Log::warning('Pesapal webhook verification failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'reason' => 'IP/User-Agent verification failed'
            ]);

            return response()->json([
                'error' => 'Unauthorized webhook request'
            ], 403);
        }

        // Verify required parameters are present
        if (!$this->hasRequiredParameters($request)) {
            Log::warning('Pesapal webhook missing required parameters', [
                'provided_params' => array_keys($request->all())
            ]);

            return response()->json([
                'error' => 'Missing required parameters'
            ], 400);
        }

        return $next($request);
    }

    /**
     * Verify if request is coming from Pesapal
     */
    private function isPesapalRequest(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        $ipAddress = $request->ip();

        // Allow requests from Pesapal user agents
        $pesapalUserAgents = [
            'pesapal',
            'curl', // Pesapal sometimes uses curl
            'postman', // For testing
        ];

        foreach ($pesapalUserAgents as $agent) {
            if (strpos($userAgent, $agent) !== false) {
                return true;
            }
        }

        // Allow requests from known Pesapal IP ranges (you should update these)
        $pesapalIpRanges = [
            '127.0.0.1', // Localhost for testing
            '::1', // IPv6 localhost
            // Add actual Pesapal IP ranges here when available
        ];

        if (in_array($ipAddress, $pesapalIpRanges)) {
            return true;
        }

        // In sandbox mode, be more lenient
        if (PesapalConfig::isSandbox()) {
            Log::info('Pesapal webhook: Sandbox mode - allowing request', [
                'ip' => $ipAddress,
                'user_agent' => $userAgent
            ]);
            return true;
        }

        return false;
    }

    /**
     * Verify that required parameters are present
     */
    private function hasRequiredParameters(Request $request): bool
    {
        $requiredParams = ['OrderTrackingId'];

        foreach ($requiredParams as $param) {
            if (!$request->has($param) || empty($request->get($param))) {
                return false;
            }
        }

        return true;
    }
}
