<?php

namespace App\Config;

/**
 * Production Configuration Helper for Pesapal
 * 
 * This class helps manage the transition from sandbox to production
 */
class PesapalProductionConfig
{
    /**
     * Check if we're in production mode
     */
    public static function isProduction(): bool
    {
        return config('services.pesapal.environment', env('PESAPAL_ENVIRONMENT', 'sandbox')) === 'production';
    }

    /**
     * Check if we're in sandbox mode
     */
    public static function isSandbox(): bool
    {
        return !self::isProduction();
    }

    /**
     * Get transaction limit based on environment
     * Returns null for production (no limits)
     */
    public static function getTransactionLimit(): ?float
    {
        if (self::isSandbox()) {
            // Convert KES limit to UGX (approximate rate: 1 KES = 30 UGX)
            return 30000.0; // UGX 30,000 limit for sandbox (equivalent to KES 1000)
        }
        
        return null; // No limit for production
    }

    /**
     * Validate transaction amount based on environment
     */
    public static function validateTransactionAmount(float $amount): void
    {
        $limit = self::getTransactionLimit();
        
        if ($limit && $amount > $limit) {
            $environment = self::isSandbox() ? 'Test' : 'Production';
            $currency = self::getCurrency();
            throw new \Exception(
                "{$environment} account transaction limit is {$currency} " . number_format($limit, 2) . 
                ". Current amount: {$currency} " . number_format($amount, 2)
            );
        }
    }

    /**
     * Get appropriate currency based on environment and configuration
     */
    public static function getCurrency(): string
    {
        // Default to UGX for Uganda
        return config('services.pesapal.currency', env('PESAPAL_CURRENCY', 'UGX'));
    }

    /**
     * Get country code based on currency
     */
    public static function getCountryCode(): string
    {
        $currency = self::getCurrency();
        
        return match($currency) {
            'KES' => 'KE',
            'UGX' => 'UG',
            'USD' => 'US',
            default => 'UG' // Default to Uganda
        };
    }

    /**
     * Get production readiness status
     */
    public static function getProductionReadiness(): array
    {
        $checks = [];
        
        // Check if production credentials are set
        $checks['credentials'] = [
            'status' => !empty(env('PESAPAL_CONSUMER_KEY')) && !empty(env('PESAPAL_CONSUMER_SECRET')),
            'message' => 'Production credentials configured'
        ];
        
        // Check if URLs are HTTPS for production
        $ipnUrl = env('PESAPAL_IPN_URL');
        $callbackUrl = env('PESAPAL_CALLBACK_URL');
        
        $checks['https_urls'] = [
            'status' => (self::isSandbox() || (str_starts_with($ipnUrl, 'https://') && str_starts_with($callbackUrl, 'https://'))),
            'message' => 'Production URLs must use HTTPS'
        ];
        
        // Check environment setting
        $checks['environment'] = [
            'status' => !empty(env('PESAPAL_ENVIRONMENT')),
            'message' => 'Environment properly configured'
        ];
        
        return $checks;
    }

    /**
     * Get production migration commands
     */
    public static function getProductionMigrationSteps(): array
    {
        return [
            '1. Update Environment' => [
                'PESAPAL_ENVIRONMENT=production',
                'PESAPAL_CONSUMER_KEY=your_production_key',
                'PESAPAL_CONSUMER_SECRET=your_production_secret'
            ],
            '2. Update URLs' => [
                'PESAPAL_IPN_URL=https://yourdomain.com/api/pesapal/ipn',
                'PESAPAL_CALLBACK_URL=https://yourdomain.com/payment-callback'
            ],
            '3. Clear Caches' => [
                'php artisan config:cache',
                'php artisan cache:clear'
            ],
            '4. Test Production' => [
                'php artisan pesapal:test',
                'Test small transaction first'
            ]
        ];
    }

    /**
     * Get API credentials for centralized client
     * Compatible with PesapalApiClient requirements
     */
    public static function getApiCredentials(): array
    {
        return [
            'consumer_key' => config('services.pesapal.consumer_key', env('PESAPAL_CONSUMER_KEY', '')),
            'consumer_secret' => config('services.pesapal.consumer_secret', env('PESAPAL_CONSUMER_SECRET', '')),
            'environment' => self::isProduction() ? 'production' : 'sandbox',
            'base_url' => self::isProduction() 
                ? 'https://pay.pesapal.com/v3/api'
                : 'https://cybqa.pesapal.com/pesapalv3/api',
            'ipn_url' => config('services.pesapal.ipn_url', env('PESAPAL_IPN_URL', url('/api/pesapal/ipn'))),
            'callback_url' => config('services.pesapal.callback_url', env('PESAPAL_CALLBACK_URL', url('/payment-callback')))
        ];
    }

    /**
     * Validate configuration for centralized API client
     */
    public static function validateConfiguration(): array
    {
        $credentials = self::getApiCredentials();
        $issues = [];

        if (empty($credentials['consumer_key'])) {
            $issues[] = 'Missing PESAPAL_CONSUMER_KEY';
        }

        if (empty($credentials['consumer_secret'])) {
            $issues[] = 'Missing PESAPAL_CONSUMER_SECRET';
        }

        if (empty($credentials['ipn_url'])) {
            $issues[] = 'Missing PESAPAL_IPN_URL';
        }

        if (empty($credentials['callback_url'])) {
            $issues[] = 'Missing PESAPAL_CALLBACK_URL';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'credentials' => $credentials
        ];
    }
}
