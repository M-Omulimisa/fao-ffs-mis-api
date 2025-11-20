<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'pesapal' => [
        'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
        'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
        'environment' => env('PESAPAL_ENVIRONMENT', 'sandbox'),
        'currency' => env('PESAPAL_CURRENCY', 'UGX'),
        'sandbox_url' => env('PESAPAL_SANDBOX_URL', 'https://cybqa.pesapal.com/pesapalv3'),
        'production_url' => env('PESAPAL_PRODUCTION_URL', 'https://pay.pesapal.com/v3'),
        'ipn_url' => env('PESAPAL_IPN_URL'),
        'callback_url' => env('PESAPAL_CALLBACK_URL'),
    ],

    'onesignal' => [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),
        'android_channel_id' => env('ONESIGNAL_ANDROID_CHANNEL_ID'),
    ],

];
