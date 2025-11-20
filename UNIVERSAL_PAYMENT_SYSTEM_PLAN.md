# Universal Payment System - Complete Implementation

## Overview
A comprehensive, dynamic payment system that handles payments for ANY module in the application (insurance, ecommerce, invoices, etc.) with full Pesapal integration.

## Architecture

### 1. Database Schema ✅ COMPLETE

**Table:** `universal_payments`

**Key Features:**
- Dynamic payment type support (`payment_type`, `payment_category`)
- Multi-item payment support via JSON (`payment_items`)
- Full Pesapal integration fields
- Item processing tracking
- Comprehensive audit trail
- Refund support

**Payment Items Structure:**
```json
[
  {
    "type": "insurance_subscription_payment",
    "id": 12,
    "amount": 50000,
    "description": "Month 5 Premium Payment",
    "metadata": {"period": "Month 5"}
  },
  {
    "type": "insurance_subscription_payment",
    "id": 13,
    "amount": 50000,
    "description": "Month 6 Premium Payment"
  }
]
```

### 2. Backend Implementation

#### A. UniversalPayment Model
Location: `/Applications/MAMP/htdocs/dtehm-insurance-api/app/Models/UniversalPayment.php`

**Core Features:**
1. **Auto-generation of unique payment reference** (UNI-PAY-{TIMESTAMP}-{RANDOM})
2. **Dynamic item processing** - processes any payment type
3. **Cascading updates** - marks paid items automatically
4. **Status management** - PENDING → PROCESSING → COMPLETED
5. **Pesapal integration** - stores tracking IDs, responses
6. **Error handling** - tracks retry attempts, error messages

**Key Methods:**
```php
// Create new payment
UniversalPayment::createPayment([
    'payment_type' => 'insurance_multiple_payments',
    'payment_category' => 'insurance',
    'user_id' => $userId,
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'customer_phone' => '+256700000000',
    'payment_items' => [
        ['type' => 'insurance_subscription_payment', 'id' => 12, 'amount' => 50000],
        ['type' => 'insurance_subscription_payment', 'id' => 13, 'amount' => 50000],
    ],
    'description' => 'Premium payments for Month 5 & 6',
    'payment_gateway' => 'pesapal',
])

// Process paid items (called after payment confirmation)
$payment->processPaymentItems()

// Check status
$payment->isCompleted()
$payment->isPending()
$payment->isFailed()
```

**Item Processing Logic:**
- Insurance Subscription Payment → Marks as Paid, cascades to subscription & program
- Insurance Transaction → Updates status to COMPLETED
- Order → Marks as PAID, sets to PROCESSING
- **Extensible:** Add new types easily

#### B. UniversalPaymentController
Location: `/Applications/MAMP/htdocs/dtehm-insurance-api/app/Http/Controllers/UniversalPaymentController.php`

**Endpoints:**
```php
POST   /api/universal-payments/initialize        - Initialize payment (creates payment + Pesapal)
POST   /api/universal-payments/create            - Create payment without gateway
GET    /api/universal-payments                   - List all payments
GET    /api/universal-payments/{id}              - Get payment details
POST   /api/universal-payments/{id}/process      - Manually process items
GET    /api/universal-payments/callback          - Pesapal callback
POST   /api/universal-payments/ipn               - Pesapal IPN
GET    /api/universal-payments/status/{id}       - Check status
POST   /api/universal-payments/{id}/refund       - Refund payment
```

**Initialize Payment Flow:**
1. Validate payment items
2. Calculate total amount
3. Create UniversalPayment record
4. If gateway = pesapal:
   - Submit to Pesapal via PesapalService
   - Store tracking ID & redirect URL
   - Return redirect URL to frontend
5. Return payment details

**IPN Callback Flow:**
1. Receive IPN from Pesapal
2. Get payment status from Pesapal API
3. Update UniversalPayment status
4. If COMPLETED:
   - Call `processPaymentItems()`
   - Mark all paid items
   - Send confirmation notifications
5. Log IPN for audit

#### C. PesapalService Integration
Uses existing `PesapalService.php` - NO CHANGES NEEDED!

The UniversalPayment system leverages your existing Pesapal service:
- `submitOrderRequest()` → Initialize payment
- `getTransactionStatus()` → Check status
- `processIpnCallback()` → Handle IPN

**Adapter Pattern:**
```php
// UniversalPaymentController
public function initializePesapalPayment($universalPayment)
{
    // Create adapter order object
    $adapterOrder = new \stdClass();
    $adapterOrder->id = $universalPayment->id;
    $adapterOrder->order_total = $universalPayment->amount;
    $adapterOrder->customer_name = $universalPayment->customer_name;
    $adapterOrder->mail = $universalPayment->customer_email;
    $adapterOrder->customer_phone_number_1 = $universalPayment->customer_phone;
    $adapterOrder->customer_address = '';
    
    // Use existing PesapalService
    $pesapalService = app(\App\Services\PesapalService::class);
    $response = $pesapalService->submitOrderRequest($adapterOrder);
    
    // Update UniversalPayment with Pesapal data
    $universalPayment->update([
        'pesapal_order_tracking_id' => $response['order_tracking_id'],
        'pesapal_merchant_reference' => $response['merchant_reference'],
        'pesapal_redirect_url' => $response['redirect_url'],
        'status' => 'PROCESSING',
    ]);
    
    return $response;
}
```

### 3. Frontend Implementation (Flutter)

#### A. Models

**UniversalPayment.dart**
Location: `/Users/mac/Desktop/github/dtehm-insurance/lib/models/UniversalPayment.dart`

```dart
class UniversalPayment {
  int id;
  String paymentReference;
  String paymentType;
  String paymentCategory;
  List<PaymentItem> paymentItems;
  double amount;
  String currency;
  String status;
  String? pesapalOrderTrackingId;
  String? pesapalRedirectUrl;
  DateTime? paymentDate;
  bool itemsProcessed;
  
  // API Methods
  static Future<UniversalPayment> initializePayment(Map<String, dynamic> data);
  static Future<UniversalPayment?> checkStatus(int id);
  
  // Computed properties
  bool get isCompleted;
  bool get isPending;
  String get formattedAmount;
  String get statusColor;
}

class PaymentItem {
  String type;
  int id;
  double amount;
  String? description;
  Map<String, dynamic>? metadata;
}
```

**PaymentItem.dart** - Helper class for building payment items

#### B. Payment Selection UI

**MultiPaymentSelectorScreen.dart**
Location: `/Users/mac/Desktop/github/dtehm-insurance/lib/screens/payment/MultiPaymentSelectorScreen.dart`

**Features:**
- Bottom sheet OR full screen
- Select multiple items (checkbox UI)
- Real-time total calculation
- Item details display
- "Proceed to Payment" button
- Validation (at least 1 item selected)

**Usage Example:**
```dart
// From Subscription Details
ElevatedButton(
  onPressed: () async {
    List<int> selectedPaymentIds = await Get.to(() => 
      MultiPaymentSelectorScreen(
        subscriptionId: subscription.id,
        availablePayments: pendingPayments,
      )
    );
    
    if (selectedPaymentIds.isNotEmpty) {
      // Initialize payment
      _initiatePayment(selectedPaymentIds);
    }
  },
  child: Text('Pay Multiple Months'),
)
```

**UI Structure:**
```
AppBar: "Select Payments to Pay"
├── Search/Filter
├── ListView (Checkboxes)
│   ├── Payment Card 1 [✓]
│   ├── Payment Card 2 [✓]
│   └── Payment Card 3 [ ]
├── Summary Card
│   ├── Selected: 2 items
│   └── Total: UGX 100,000
└── Floating Button: "Proceed to Payment (UGX 100,000)"
```

#### C. Universal Payment Screen

**UniversalPaymentScreen.dart**
Location: `/Users/mac/Desktop/github/dtehm-insurance/lib/screens/payment/UniversalPaymentScreen.dart`

**Reuses OrderPaymentScreen Pattern:**
- Payment method selection (Mobile Money, Visa/Mastercard, Bank Transfer)
- Payment summary display
- "Pay Now" button → Pesapal WebView
- Status checking timer
- Success/Failure handling

**Flow:**
1. Display selected items summary
2. Show total amount
3. Select payment method
4. Click "Pay Now"
5. Call initialize API
6. Open Pesapal WebView
7. Monitor status with timer
8. Show success/failure dialog
9. Process items if successful

#### D. Payment WebView Screen

**PaymentWebViewScreen.dart** (Reuse existing!)
Location: `/Users/mac/Desktop/github/dtehm-insurance/lib/screens/payment/PaymentWebViewScreen.dart`

NO CHANGES NEEDED - already handles Pesapal redirects and callbacks perfectly!

### 4. Integration with Insurance Module

#### A. Subscription Details Screen
**Add "Pay Multiple Months" Button:**

```dart
// In InsuranceSubscriptionDetails.dart
ElevatedButton.icon(
  onPressed: () async {
    // Get pending payments
    List<InsuranceSubscriptionPayment> pendingPayments = 
      payments.where((p) => p.payment_status != 'Paid').toList();
    
    if (pendingPayments.isEmpty) {
      Utils.toast('No pending payments');
      return;
    }
    
    // Open selector
    List<int> selectedIds = await Get.to(() => 
      MultiPaymentSelectorScreen(
        title: 'Select Months to Pay',
        availablePayments: pendingPayments.map((p) => PaymentItem(
          type: 'insurance_subscription_payment',
          id: p.id,
          amount: double.parse(p.total_amount),
          description: p.period_name,
          metadata: {
            'due_date': p.due_date,
            'period': p.period_name,
          },
        )).toList(),
      )
    );
    
    if (selectedIds.isNotEmpty) {
      _initiateUniversalPayment(selectedIds);
    }
  },
  icon: Icon(Icons.payment),
  label: Text('Pay Multiple Months'),
)

Future<void> _initiateUniversalPayment(List<int> paymentIds) async {
  // Build payment items
  List<Map<String, dynamic>> items = [];
  double total = 0;
  
  for (int id in paymentIds) {
    var payment = payments.firstWhere((p) => p.id == id);
    items.add({
      'type': 'insurance_subscription_payment',
      'id': payment.id,
      'amount': double.parse(payment.total_amount),
      'description': payment.period_name,
    });
    total += double.parse(payment.total_amount);
  }
  
  // Navigate to payment screen
  await Get.to(() => UniversalPaymentScreen(
    paymentType: 'insurance_multiple_payments',
    paymentCategory: 'insurance',
    paymentItems: items,
    totalAmount: total,
    customerName: subscription.insuranceUser?.name,
    customerPhone: subscription.insuranceUser?.phone_number_1,
    customerEmail: subscription.insuranceUser?.email,
  ));
  
  // Refresh after payment
  _loadPayments();
}
```

#### B. Payment List Row (Single Payment)
```dart
// Add "Pay Now" button to each payment card
if (payment.payment_status != 'Paid')
  ElevatedButton(
    onPressed: () => _paySinglePayment(payment),
    child: Text('Pay Now'),
  )

Future<void> _paySinglePayment(InsuranceSubscriptionPayment payment) async {
  await Get.to(() => UniversalPaymentScreen(
    paymentType: 'insurance_single_payment',
    paymentCategory: 'insurance',
    paymentItems: [{
      'type': 'insurance_subscription_payment',
      'id': payment.id,
      'amount': double.parse(payment.total_amount),
      'description': payment.period_name,
    }],
    totalAmount: double.parse(payment.total_amount),
    customerName: subscription.insuranceUser?.name,
    customerPhone: subscription.insuranceUser?.phone_number_1,
  ));
  
  _loadPayments();
}
```

### 5. Testing Plan

#### Backend Tests
```bash
# 1. Create payment with multiple items
curl -X POST http://localhost:8888/dtehm-insurance-api/public/api/universal-payments/initialize \
  -H "Content-Type: application/json" \
  -d '{
    "payment_type": "insurance_multiple_payments",
    "payment_category": "insurance",
    "user_id": 1,
    "customer_name": "John Doe",
    "customer_email": "john@test.com",
    "customer_phone": "+256700000000",
    "payment_items": [
      {"type": "insurance_subscription_payment", "id": 1, "amount": 50000},
      {"type": "insurance_subscription_payment", "id": 2, "amount": 50000}
    ],
    "payment_gateway": "pesapal"
  }'

# 2. Check payment status
curl http://localhost:8888/dtehm-insurance-api/public/api/universal-payments/status/1

# 3. Manually process items (after payment)
curl -X POST http://localhost:8888/dtehm-insurance-api/public/api/universal-payments/1/process
```

#### Frontend Tests
1. Select multiple payments → Total calculated correctly
2. Initialize payment → Pesapal redirect works
3. Complete payment → Items marked as paid
4. Check subscription → Balance updated correctly
5. Single payment → Same flow works
6. Failed payment → Error handling works

### 6. API Documentation

#### Initialize Payment
```
POST /api/universal-payments/initialize

Request:
{
  "payment_type": "insurance_multiple_payments",
  "payment_category": "insurance",
  "user_id": 1,
  "customer_name": "John Doe",
  "customer_email": "john@test.com",
  "customer_phone": "+256700000000",
  "payment_items": [
    {
      "type": "insurance_subscription_payment",
      "id": 1,
      "amount": 50000,
      "description": "Month 5 Premium"
    }
  ],
  "payment_gateway": "pesapal",
  "callback_url": "yourapp://payment-result"
}

Response:
{
  "success": true,
  "message": "Payment initialized successfully",
  "data": {
    "payment": {
      "id": 1,
      "payment_reference": "UNI-PAY-1698765432-ABC123",
      "amount": 50000,
      "status": "PROCESSING",
      "items_count": 1
    },
    "pesapal": {
      "order_tracking_id": "4a8a5956-1ae4-4b6f-8b0b-0e8d4e8e8e8e",
      "redirect_url": "https://pesapal.com/...",
      "merchant_reference": "UNI-PAY-1698765432-ABC123"
    }
  }
}
```

### 7. Security Considerations

1. **Payment Items Validation:**
   - Verify item ownership (user can only pay for their own items)
   - Validate amounts match database records
   - Prevent duplicate payments

2. **IPN Verification:**
   - Validate IPN signature (if available)
   - Verify payment status with Pesapal API
   - Log all IPN requests for audit

3. **Idempotency:**
   - Prevent duplicate processing with `items_processed` flag
   - Check payment reference uniqueness
   - Handle race conditions

4. **Error Handling:**
   - Comprehensive try-catch blocks
   - Detailed logging for debugging
   - User-friendly error messages
   - Automatic retry for failed processing

### 8. Benefits of This System

1. **Universal:** Works for ANY payment type (insurance, orders, invoices, etc.)
2. **Flexible:** Support multiple items in one payment
3. **Reliable:** Comprehensive error handling & retry logic
4. **Traceable:** Full audit trail with timestamps
5. **Extensible:** Add new payment types easily
6. **Reusable:** Same UI components for all modules
7. **Pesapal Ready:** Full integration with existing service
8. **Mobile Friendly:** Optimized for Flutter apps
9. **Admin Friendly:** Track all payments in one place
10. **Future Proof:** Ready for Stripe, M-Pesa, etc.

### 9. Future Enhancements

1. **Payment Methods:**
   - Stripe integration
   - M-Pesa/Airtel Money direct APIs
   - Bank transfer verification

2. **Features:**
   - Scheduled/recurring payments
   - Partial payment support
   - Payment plans
   - Multi-currency support
   - Payment reminders

3. **Admin:**
   - Payment analytics dashboard
   - Refund management UI
   - Payment dispute resolution
   - Bulk payment processing

4. **User:**
   - Payment history
   - Download receipts
   - Save payment methods
   - One-click repeat payments

### 10. Implementation Status

✅ **Phase 1: Database** (COMPLETE)
- Migration created and run
- Table structure comprehensive

⏳ **Phase 2: Backend Models** (IN PROGRESS)
- UniversalPayment model needs full implementation
- Processing logic needs completion

⏳ **Phase 3: Backend Controller** (PENDING)
- UniversalPaymentController needs creation
- API routes need registration

⏳ **Phase 4: Frontend Models** (PENDING)
- UniversalPayment.dart needs creation
- PaymentItem.dart needs creation

⏳ **Phase 5: Frontend UI** (PENDING)
- MultiPaymentSelectorScreen needs creation
- UniversalPaymentScreen needs creation
- Integration with insurance module

⏳ **Phase 6: Testing** (PENDING)
- Backend API testing
- Frontend UI testing
- End-to-end payment flow testing

### 11. Next Steps

1. Complete UniversalPayment model implementation
2. Create UniversalPaymentController
3. Register API routes
4. Create Flutter models
5. Create payment selection UI
6. Create universal payment screen
7. Integrate with insurance module
8. Test end-to-end flow
9. Deploy to production
10. Monitor and optimize

---

**This system is designed to be the ONLY payment system you'll ever need!**
