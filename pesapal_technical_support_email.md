Subject: Pesapal Production API Integration - Technical Support Request

Dear Pesapal Technical Team,

I am writing to request assistance with our Pesapal production environment integration for BlitXpress (https://blitxpress.com). We are experiencing issues when attempting to process payments and would appreciate your guidance.

## MERCHANT DETAILS
- Merchant Name: BlitXpress
- Environment: Production
- Integration: Pesapal API v3.0
- Phone Number for Testing: +256783204665
- Integration Date: September 2025

## CURRENT CONFIGURATION
We have configured our system with the following production credentials:

**API Endpoints:**
- Base URL: https://pay.pesapal.com/v3
- Auth Endpoint: /api/Auth/RequestToken
- Submit Order Endpoint: /api/Transactions/SubmitOrderRequest
- Status Check Endpoint: /api/Transactions/GetTransactionStatus

**Environment Variables:**
```
PESAPAL_CONSUMER_KEY=qkio1BGGYAXTu2JOfm7XSXNruoZsrqEW
PESAPAL_CONSUMER_SECRET=osGQ364R49cXKeOYSpaOnT++rHs=
PESAPAL_ENVIRONMENT=production
PESAPAL_PRODUCTION_URL=https://pay.pesapal.com/v3
PESAPAL_CURRENCY=UGX
PESAPAL_IPN_URL=http://localhost:8888/blitxpress/api/pesapal/ipn
PESAPAL_CALLBACK_URL=http://localhost:8888/blitxpress/payment-callback
```

## HTTP REQUEST WE ARE SENDING

**Authentication Request:**
```
POST https://pay.pesapal.com/v3/api/Auth/RequestToken
Content-Type: application/json
Accept: application/json

{
    "consumer_key": "qkio1BGGYAXTu2JOfm7XSXNruoZsrqEW",
    "consumer_secret": "osGQ364R49cXKeOYSpaOnT++rHs="
}
```

**Payment Initialization Request:**
```
POST https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest
Content-Type: application/json
Accept: application/json
Authorization: Bearer [TOKEN_FROM_AUTH_RESPONSE]

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

## EXPECTED RESPONSE VS ACTUAL BEHAVIOR

**Expected Response:**
According to your API documentation, we expect to receive:
```json
{
    "order_tracking_id": "some-tracking-id",
    "redirect_url": "https://pay.pesapal.com/iframe/PesapalIframe3/Index/?OrderTrackingId=...",
    "status": "200",
    "message": "Order submitted successfully"
}
```

**Actual Issue:**
We are experiencing one of the following issues:
1. Authentication failures with production credentials
2. Missing required fields in API response (order_tracking_id or redirect_url)
3. Error responses that are not documented
4. Timeout or connection issues

## TECHNICAL IMPLEMENTATION DETAILS

**Our Integration Architecture:**
- Framework: Laravel 9+
- PHP Version: 8.2.20
- HTTP Client: Guzzle (via Laravel HTTP facade)
- Database: MySQL 8.0
- Server: Apache/2.4.58 on macOS (development)

**Request Headers We Send:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer [VALID_TOKEN]
User-Agent: Guzzle/7.0 (Laravel HTTP Client)
```

**Error Handling Implementation:**
```php
try {
    $response = Http::withHeaders([
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ])->post($this->baseUrl . '/Transactions/SubmitOrderRequest', $payload);

    if ($response->successful()) {
        $data = $response->json();
        
        // Validate required fields
        if (!isset($data['order_tracking_id']) || !isset($data['redirect_url'])) {
            throw new \Exception('Missing required fields in Pesapal response');
        }
        
        return $data;
    } else {
        throw new \Exception('HTTP ' . $response->status() . ': ' . $response->body());
    }
} catch (\Exception $e) {
    // Log and handle error
}
```

## QUESTIONS FOR PESAPAL TECHNICAL TEAM

1. **Credentials Verification**: Can you confirm that our production credentials (Consumer Key: qkio1BGGYAXTu2JOfm7XSXNruoZsrqEW) are properly activated for the Uganda market?

2. **API Endpoint Validation**: Are we using the correct production endpoints? Should we be using different URLs for Uganda-specific transactions?

3. **Phone Number Format**: Is the phone number format "+256783204665" correct for Uganda mobile numbers, or should we use a different format?

4. **Currency Support**: Can you confirm that UGX currency is supported in the production environment?

5. **IPN Configuration**: Do we need to register our IPN URL separately, or is it handled automatically in production?

6. **Minimum Transaction Amount**: Is there a minimum transaction amount for production? We're testing with UGX 50,000.

7. **Error Response Format**: Can you provide the exact error response format we should expect when transactions fail?

## DEBUGGING INFORMATION REQUESTED

Could you please help us verify:

1. **API Access**: Can you check if our Consumer Key has proper access to the production environment?

2. **Rate Limiting**: Are there any rate limits or IP restrictions that might be affecting our requests?

3. **Response Format**: Can you confirm the exact JSON structure of successful and failed responses?

4. **Testing Process**: What is the recommended testing process for production environment before going live?

## NEXT STEPS

We would appreciate:

1. Verification of our credentials and configuration
2. Sample successful request/response for our specific setup
3. Common error scenarios and their solutions
4. Confirmation of production environment access
5. Any additional configuration required for Uganda market

## CONTACT INFORMATION

**Technical Contact:**
- Developer: [Your Name]
- Email: [Your Email]
- Phone: +256783204665
- Company: BlitXpress
- Website: https://blitxpress.com

**Business Contact:**
- Business Owner: [Business Contact]
- Email: sales@blitxpress.com

We are ready to provide any additional information or logs that would help diagnose this issue. Please let us know if you need access to our test environment or additional technical details.

Thank you for your assistance.

Best regards,
[Your Name]
BlitXpress Development Team

---

**Note**: This email contains our current production configuration. Please advise if any changes are needed for proper integration with Pesapal's production environment.
