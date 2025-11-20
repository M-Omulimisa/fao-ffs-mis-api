# Pesapal Payment Gateway API Endpoints

## Overview

This document describes the Pesapal payment gateway API endpoints integrated into the BlitXpress system. These endpoints handle payment initialization, callbacks, IPN notifications, and status checking.

**Base URL**: `/api/pesapal/`

## Authentication

All endpoints are accessible without authentication, but the `initialize` endpoint validates order ownership and the IPN endpoint validates Pesapal's signatures.

## Endpoints

### 1. Initialize Payment

**Endpoint**: `POST /api/pesapal/initialize`

**Description**: Initializes a Pesapal payment for an existing order.

**Request Body**:
```json
{
  "order_id": 123,
  "callback_url": "https://yoursite.com/payment-success", // Optional
  "notification_id": "pesapal_notification_id" // Optional
}
```

**Response Success (200)**:
```json
{
  "success": true,
  "message": "Payment initialized successfully",
  "data": {
    "order_tracking_id": "4a8a5956-1ae4-4b6f-8b0b-0e8d4e8e8e8e",
    "merchant_reference": "BLX-ORD-123-20250830",
    "redirect_url": "https://www.pesapal.com/pesapalv3/api/Auth/RequestId/...",
    "status": "200"
  }
}
```

**Response Error (400/500)**:
```json
{
  "success": false,
  "message": "Error message",
  "data": null
}
```

**Usage Example**:
```javascript
// Frontend payment initialization
const initializePayment = async (orderId) => {
  const response = await fetch('/api/pesapal/initialize', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      order_id: orderId,
      callback_url: window.location.origin + '/payment-result'
    })
  });
  
  const result = await response.json();
  if (result.success) {
    // Redirect user to Pesapal payment page
    window.location.href = result.data.redirect_url;
  }
};
```

### 2. Payment Callback

**Endpoint**: `GET|POST /api/pesapal/callback`

**Description**: Handles payment callback from Pesapal after payment completion.

**Query Parameters**:
- `OrderTrackingId`: Pesapal order tracking ID
- `OrderMerchantReference`: Your merchant reference
- `OrderNotificationType`: Type of notification (COMPLETED, FAILED, etc.)

**Response for JSON requests**:
```json
{
  "success": true,
  "message": "Callback processed successfully",
  "data": {
    "order_tracking_id": "4a8a5956-1ae4-4b6f-8b0b-0e8d4e8e8e8e",
    "merchant_reference": "BLX-ORD-123-20250830",
    "payment_status": "Completed",
    "payment_method": "Visa",
    "amount": 1000.00,
    "order_id": 123
  }
}
```

**Response for web requests**: Redirects to frontend with status parameters.

### 3. IPN (Instant Payment Notification)

**Endpoint**: `POST /api/pesapal/ipn`

**Description**: Receives instant payment notifications from Pesapal. This is called by Pesapal automatically.

**Request Body** (from Pesapal):
```json
{
  "OrderTrackingId": "4a8a5956-1ae4-4b6f-8b0b-0e8d4e8e8e8e",
  "OrderMerchantReference": "BLX-ORD-123-20250830",
  "OrderNotificationType": "IPNCHANGE"
}
```

**Response to Pesapal**:
```json
{
  "orderNotificationType": "IPNCHANGE",
  "orderTrackingId": "4a8a5956-1ae4-4b6f-8b0b-0e8d4e8e8e8e",
  "orderMerchantReference": "BLX-ORD-123-20250830",
  "status": 200
}
```

**Note**: This endpoint is called automatically by Pesapal and should not be called directly.

### 4. Check Payment Status

**Endpoint**: `GET /api/pesapal/status/{orderId}`

**Description**: Retrieves current payment status for an order.

**Path Parameters**:
- `orderId`: The BlitXpress order ID

**Response Success (200)**:
```json
{
  "success": true,
  "message": "Status retrieved successfully",
  "data": {
    "order_id": 123,
    "order_tracking_id": "4a8a5956-1ae4-4b6f-8b0b-0e8d4e8e8e8e",
    "merchant_reference": "BLX-ORD-123-20250830",
    "payment_status": "COMPLETED",
    "pesapal_status": "COMPLETED",
    "payment_method": "Visa",
    "amount": 1000.00,
    "currency": "UGX",
    "confirmation_code": "TR1234567890",
    "payment_account": "****1234",
    "order_state": "PAID",
    "is_paid": true,
    "created_at": "2025-08-30T08:30:00Z",
    "updated_at": "2025-08-30T08:35:00Z"
  }
}
```

**Usage Example**:
```javascript
// Check payment status
const checkPaymentStatus = async (orderId) => {
  const response = await fetch(`/api/pesapal/status/${orderId}`);
  const result = await response.json();
  
  if (result.success) {
    console.log('Payment status:', result.data.payment_status);
    console.log('Is paid:', result.data.is_paid);
  }
};
```

### 5. Register IPN URL (Utility)

**Endpoint**: `POST /api/pesapal/register-ipn`

**Description**: Registers an IPN URL with Pesapal (utility endpoint for setup).

**Request Body**:
```json
{
  "ipn_url": "https://yoursite.com/api/pesapal/ipn", // Optional
  "notification_type": "POST" // Optional: GET or POST
}
```

**Response Success (200)**:
```json
{
  "success": true,
  "message": "IPN URL registered successfully",
  "data": {
    "ipn_id": "pesapal_notification_id",
    "url": "https://yoursite.com/api/pesapal/ipn",
    "ipn_notification_type": "POST",
    "ipn_status": "1"
  }
}
```

## Error Handling

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "data": null
}
```

Common HTTP status codes:
- `400`: Bad Request (validation errors)
- `404`: Not Found (order not found)
- `500`: Internal Server Error (processing errors)

## Payment Flow Integration

### Frontend Integration Example

```javascript
class PesapalPayment {
  
  static async initializePayment(orderId, callbackUrl = null) {
    try {
      const response = await fetch('/api/pesapal/initialize', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          order_id: orderId,
          callback_url: callbackUrl || `${window.location.origin}/payment-result`
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        // Store tracking info for later reference
        localStorage.setItem('payment_tracking', JSON.stringify({
          order_id: orderId,
          order_tracking_id: result.data.order_tracking_id,
          merchant_reference: result.data.merchant_reference
        }));
        
        // Redirect to Pesapal
        window.location.href = result.data.redirect_url;
      } else {
        throw new Error(result.message);
      }
    } catch (error) {
      console.error('Payment initialization failed:', error);
      throw error;
    }
  }
  
  static async checkPaymentStatus(orderId) {
    try {
      const response = await fetch(`/api/pesapal/status/${orderId}`);
      const result = await response.json();
      
      if (result.success) {
        return result.data;
      } else {
        throw new Error(result.message);
      }
    } catch (error) {
      console.error('Status check failed:', error);
      throw error;
    }
  }
  
  static async pollPaymentStatus(orderId, maxAttempts = 30, intervalMs = 2000) {
    let attempts = 0;
    
    return new Promise((resolve, reject) => {
      const poll = async () => {
        try {
          attempts++;
          const status = await this.checkPaymentStatus(orderId);
          
          if (status.is_paid || status.payment_status === 'COMPLETED') {
            resolve(status);
          } else if (status.payment_status === 'FAILED') {
            reject(new Error('Payment failed'));
          } else if (attempts >= maxAttempts) {
            reject(new Error('Payment status polling timeout'));
          } else {
            setTimeout(poll, intervalMs);
          }
        } catch (error) {
          if (attempts >= maxAttempts) {
            reject(error);
          } else {
            setTimeout(poll, intervalMs);
          }
        }
      };
      
      poll();
    });
  }
}

// Usage examples:

// Initialize payment
PesapalPayment.initializePayment(123)
  .catch(error => {
    alert('Payment initialization failed: ' + error.message);
  });

// Check status
PesapalPayment.checkPaymentStatus(123)
  .then(status => {
    console.log('Payment status:', status.payment_status);
  });

// Poll for completion (useful after callback)
PesapalPayment.pollPaymentStatus(123)
  .then(status => {
    console.log('Payment completed!', status);
  })
  .catch(error => {
    console.log('Payment failed or timeout:', error.message);
  });
```

### Laravel Integration Example

```php
// In your Order controller or service
class OrderController extends Controller 
{
    public function processPayment(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);
        
        // Verify order belongs to user
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
        
        // Initialize Pesapal payment
        try {
            $response = Http::post(url('/api/pesapal/initialize'), [
                'order_id' => $order->id,
                'callback_url' => url("/orders/{$order->id}/payment-result")
            ]);
            
            $result = $response->json();
            
            if ($result['success']) {
                return response()->json([
                    'redirect_url' => $result['data']['redirect_url'],
                    'order_tracking_id' => $result['data']['order_tracking_id']
                ]);
            } else {
                throw new \Exception($result['message']);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Payment initialization failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function paymentResult(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);
        
        // Get latest payment status
        $response = Http::get(url("/api/pesapal/status/{$orderId}"));
        $result = $response->json();
        
        if ($result['success']) {
            $paymentData = $result['data'];
            
            return view('orders.payment-result', [
                'order' => $order,
                'payment_status' => $paymentData['payment_status'],
                'is_paid' => $paymentData['is_paid'],
                'payment_method' => $paymentData['payment_method'],
                'confirmation_code' => $paymentData['confirmation_code']
            ]);
        }
        
        return view('orders.payment-error', ['order' => $order]);
    }
}
```

## Testing

### Manual Testing Steps

1. **Test Payment Initialization**:
   ```bash
   curl -X POST http://localhost/api/pesapal/initialize \
     -H "Content-Type: application/json" \
     -d '{"order_id": 1}'
   ```

2. **Test Status Checking**:
   ```bash
   curl http://localhost/api/pesapal/status/1
   ```

3. **Test IPN Registration**:
   ```bash
   curl -X POST http://localhost/api/pesapal/register-ipn \
     -H "Content-Type: application/json" \
     -d '{"ipn_url": "http://localhost/api/pesapal/ipn"}'
   ```

### Integration with Frontend

The endpoints are designed to work seamlessly with mobile apps (Flutter) and web applications. All responses use consistent JSON format with success/error indicators.

## Security Considerations

1. **Callback Verification**: The callback endpoint validates incoming requests and updates payment status based on actual Pesapal API responses.

2. **IPN Logging**: All IPN requests are logged for audit purposes with full request/response data.

3. **Error Handling**: Comprehensive error handling prevents information leakage while providing useful debugging information.

4. **Transaction Integrity**: Payment status is always verified with Pesapal before marking orders as paid.

## Monitoring and Logging

All Pesapal operations are logged to Laravel's logging system:

- Payment initializations
- Callback processing  
- IPN notifications
- Status checks
- API errors

Check logs at: `storage/logs/laravel.log`

## Environment Configuration

Required `.env` variables:
```env
PESAPAL_CONSUMER_KEY=your_consumer_key
PESAPAL_CONSUMER_SECRET=your_consumer_secret
PESAPAL_ENVIRONMENT=sandbox # or live
PESAPAL_IPN_URL=https://yourdomain.com/api/pesapal/ipn
PESAPAL_CALLBACK_URL=https://yourdomain.com/api/pesapal/callback
```
