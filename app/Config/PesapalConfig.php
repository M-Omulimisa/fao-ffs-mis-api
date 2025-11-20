<?php

namespace App\Config;

class PesapalConfig
{
    /**
     * Get Pesapal environment (production or sandbox)
     * DEFAULT: production (live environment)
     */
    public static function getEnvironment(): string
    {
        return config('services.pesapal.environment', env('PESAPAL_ENVIRONMENT', 'production'));
    }

    /**
     * Check if running in sandbox mode
     */
    public static function isSandbox(): bool
    {
        return self::getEnvironment() === 'sandbox';
    }

    /**
     * Check if running in production mode
     */
    public static function isProduction(): bool
    {
        return self::getEnvironment() === 'production';
    }

    /**
     * Get base API URL based on environment
     */
    public static function getBaseUrl(): string
    {
        return self::isSandbox() 
            ? 'https://cybqa.pesapal.com/pesapalv3/api'
            : 'https://pay.pesapal.com/v3/api';
    }

    /**
     * Get consumer key
     */
    public static function getConsumerKey(): string
    {
        return config('services.pesapal.consumer_key', env('PESAPAL_CONSUMER_KEY', ''));
    }

    /**
     * Get consumer secret
     */
    public static function getConsumerSecret(): string
    {
        return config('services.pesapal.consumer_secret', env('PESAPAL_CONSUMER_SECRET', ''));
    }

    /**
     * Get default IPN URL
     */
    public static function getIpnUrl(): string
    {
        return config('services.pesapal.ipn_url', env('PESAPAL_IPN_URL', url('/api/pesapal/ipn')));
    }

    /**
     * Get default callback URL
     */
    public static function getCallbackUrl(): string
    {
        return config('services.pesapal.callback_url', env('PESAPAL_CALLBACK_URL', url('/api/pesapal/callback')));
    }

    /**
     * Get supported currencies
     */
    public static function getSupportedCurrencies(): array
    {
        return [
            'UGX' => 'Ugandan Shilling',
            'KES' => 'Kenyan Shilling',
            'TZS' => 'Tanzanian Shilling',
            'RWF' => 'Rwandan Franc',
            'BIF' => 'Burundian Franc',
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound'
        ];
    }

    /**
     * Get default currency
     */
    public static function getDefaultCurrency(): string
    {
        return config('services.pesapal.default_currency', env('PESAPAL_DEFAULT_CURRENCY', 'UGX'));
    }

    /**
     * Get supported payment methods
     */
    public static function getSupportedPaymentMethods(): array
    {
        return [
            'card' => 'Credit/Debit Card',
            'mobile' => 'Mobile Money',
            'bank' => 'Bank Transfer',
            'wallet' => 'Digital Wallet'
        ];
    }

    /**
     * Get timeout settings
     */
    public static function getTimeoutSettings(): array
    {
        return [
            'connection_timeout' => config('services.pesapal.connection_timeout', 30),
            'request_timeout' => config('services.pesapal.request_timeout', 60),
            'token_cache_duration' => config('services.pesapal.token_cache_duration', 240) // 4 minutes
        ];
    }

    /**
     * Get retry settings
     */
    public static function getRetrySettings(): array
    {
        return [
            'max_retries' => config('services.pesapal.max_retries', 3),
            'retry_delay' => config('services.pesapal.retry_delay', 1000), // milliseconds
            'exponential_backoff' => config('services.pesapal.exponential_backoff', true)
        ];
    }

    /**
     * Get all configuration as array
     */
    public static function toArray(): array
    {
        return [
            'environment' => self::getEnvironment(),
            'is_sandbox' => self::isSandbox(),
            'base_url' => self::getBaseUrl(),
            'consumer_key' => self::getConsumerKey() ? '***' . substr(self::getConsumerKey(), -4) : null,
            'consumer_secret' => self::getConsumerSecret() ? '***' . substr(self::getConsumerSecret(), -4) : null,
            'ipn_url' => self::getIpnUrl(),
            'callback_url' => self::getCallbackUrl(),
            'default_currency' => self::getDefaultCurrency(),
            'supported_currencies' => self::getSupportedCurrencies(),
            'supported_payment_methods' => self::getSupportedPaymentMethods(),
            'timeout_settings' => self::getTimeoutSettings(),
            'retry_settings' => self::getRetrySettings()
        ];
    }
}
