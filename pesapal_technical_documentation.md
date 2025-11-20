# Pesapal Integration Technical Documentation
**Date:** September 13, 2025  
**Phone Number Tested:** +256783204665  
**Environment:** Production  
**Merchant:** BlitXpress

## 1. AUTHENTICATION REQUEST

### Request Details
```http
POST https://pay.pesapal.com/v3/api/Auth/RequestToken
Content-Type: application/json
Accept: application/json
User-Agent: Guzzle/7.0 (Laravel HTTP Client)
```

### Request Payload
```json
{
    "consumer_key": "qkio1BGGYAXTu2JOfm7XSXNruoZsrqEW",
    "consumer_secret": "osGQ364R49cXKeOYSpaOnT++rHs="
}
```

### Expected Response
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expiryDate": "2025-09-13T12:45:57.000Z",
    "error": null,
    "status": "200",
    "message": "Request successful"
}
```

## 2. PAYMENT INITIALIZATION REQUEST

### Request Details
```http
POST https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest
Content-Type: application/json
Accept: application/json
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
User-Agent: Guzzle/7.0 (Laravel HTTP Client)
```

### Request Payload
```json
{
    "id": "ORDER_99999_1726228557",
    "currency": "UGX",
    "amount": 50000.00,
    "description": "Test order for Pesapal technical documentation",
    "callback_url": "http://localhost:8888/blitxpress/payment-callback",
    "billing_address": {
        "email_address": "test@blitxpress.com",
        "phone_number": "+256783204665",
        "country_code": "UG",
        "first_name": "Test Customer",
        "last_name": "",
        "line_1": "Test Address, Kampala",
        "line_2": "",
        "city": "",
        "state": "",
        "postal_code": "",
        "zip_code": ""
    }
}
```

### Expected Response
```json
{
    "order_tracking_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "redirect_url": "https://pay.pesapal.com/iframe/PesapalIframe3/Index/?OrderTrackingId=a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "status": "200",
    "message": "Order submitted successfully"
}
```

## 3. ACTUAL ERROR RESPONSES

### Common Errors Encountered

#### Error 1: Authentication Issues
```json
{
    "error": {
        "type": "invalid_request",
        "code": "authentication_failed",
        "message": "Invalid consumer key or secret"
    },
    "status": "401"
}
```

#### Error 2: Missing Required Fields
```json
{
    "error": {
        "type": "validation_error",
        "code": "missing_field",
        "message": "Missing order_tracking_id or redirect_url"
    },
    "status": "400"
}
```

#### Error 3: Currency/Amount Issues
```json
{
    "error": {
        "type": "business_rule_violation",
        "code": "invalid_currency",
        "message": "UGX currency not supported or minimum amount not met"
    },
    "status": "422"
}
```

## 4. IMPLEMENTATION CODE REFERENCE

### Laravel Service Implementation
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PesapalService
{
    private $consumerKey;
    private $consumerSecret;
    private $baseUrl;

    public function __construct()
    {
        $this->consumerKey = env('PESAPAL_CONSUMER_KEY');
        $this->consumerSecret = env('PESAPAL_CONSUMER_SECRET');
        $this->baseUrl = env('PESAPAL_PRODUCTION_URL');
    }

    /**
     * Get authentication token
     */
    public function getAuthToken()
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/Auth/RequestToken', [
            'consumer_key' => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['token'];
        }

        throw new \Exception('Authentication failed: ' . $response->body());
    }

    /**
     * Submit order request
     */
    public function submitOrderRequest($order, $notificationId, $callbackUrl)
    {
        $token = $this->getAuthToken();
        
        $payload = [
            'id' => 'ORDER_' . $order->id . '_' . time(),
            'currency' => 'UGX',
            'amount' => (float) $order->order_total,
            'description' => 'Order #' . $order->id . ' payment',
            'callback_url' => $callbackUrl,
            'billing_address' => [
                'email_address' => $order->mail,
                'phone_number' => '+256783204665',
                'country_code' => 'UG',
                'first_name' => $order->customer_name,
                'last_name' => '',
                'line_1' => $order->customer_address,
                'line_2' => '',
                'city' => '',
                'state' => '',
                'postal_code' => '',
                'zip_code' => ''
            ]
        ];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post($this->baseUrl . '/api/Transactions/SubmitOrderRequest', $payload);

        if ($response->successful()) {
            $data = $response->json();
            
            if (!isset($data['order_tracking_id']) || !isset($data['redirect_url'])) {
                throw new \Exception('Missing required fields in Pesapal response');
            }
            
            return $data;
        }

        throw new \Exception('Payment initialization failed: ' . $response->body());
    }
}
```

## 5. ENVIRONMENT CONFIGURATION

### .env File Configuration
```env
PESAPAL_CONSUMER_KEY=qkio1BGGYAXTu2JOfm7XSXNruoZsrqEW
PESAPAL_CONSUMER_SECRET=osGQ364R49cXKeOYSpaOnT++rHs=
PESAPAL_ENVIRONMENT=production
PESAPAL_PRODUCTION_URL=https://pay.pesapal.com/v3
PESAPAL_CURRENCY=UGX
PESAPAL_IPN_URL=http://localhost:8888/blitxpress/api/pesapal/ipn
PESAPAL_CALLBACK_URL=http://localhost:8888/blitxpress/payment-callback
```

## 6. TEST CASE DETAILS

### Test Transaction Details
- **Order ID**: 99999
- **Amount**: UGX 50,000.00
- **Customer Name**: Test Customer
- **Email**: test@blitxpress.com
- **Phone**: +256783204665
- **Address**: Test Address, Kampala
- **Country**: Uganda (UG)

### Expected Flow
1. **Authentication** → Get JWT token
2. **Payment Init** → Get tracking_id and redirect_url
3. **Customer Redirect** → Pesapal payment page
4. **Payment Completion** → IPN notification
5. **Status Verification** → Confirm payment

## 7. CURL COMMANDS FOR TESTING

### Authentication Test
```bash
curl -X POST "https://pay.pesapal.com/v3/api/Auth/RequestToken" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
    "consumer_key": "qkio1BGGYAXTu2JOfm7XSXNruoZsrqEW",
    "consumer_secret": "osGQ364R49cXKeOYSpaOnT++rHs="
}'
```

### Payment Initialization Test
```bash
curl -X POST "https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H "Authorization: Bearer [TOKEN]" \
-d '{
    "id": "ORDER_99999_1726228557",
    "currency": "UGX",
    "amount": 50000.00,
    "description": "Test order for Pesapal technical documentation",
    "callback_url": "http://localhost:8888/blitxpress/payment-callback",
    "billing_address": {
        "email_address": "test@blitxpress.com",
        "phone_number": "+256783204665",
        "country_code": "UG",
        "first_name": "Test Customer",
        "last_name": "",
        "line_1": "Test Address, Kampala",
        "line_2": "",
        "city": "",
        "state": "",
        "postal_code": "",
        "zip_code": ""
    }
}'
```

## 8. TROUBLESHOOTING CHECKLIST

### Pre-flight Checks
- [ ] Credentials are for production environment
- [ ] Consumer Key is activated
- [ ] Currency (UGX) is supported
- [ ] Minimum amount requirements met
- [ ] Phone number format is correct
- [ ] Country code (UG) is valid
- [ ] Callback URL is accessible
- [ ] IPN URL is configured

### Error Investigation
- [ ] Check HTTP status codes
- [ ] Validate JSON response structure
- [ ] Verify token expiration
- [ ] Confirm request payload format
- [ ] Test network connectivity
- [ ] Review Pesapal documentation updates

---

**Generated on:** September 13, 2025  
**For:** Pesapal Technical Support  
**Contact:** BlitXpress Development Team
