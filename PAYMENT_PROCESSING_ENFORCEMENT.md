# Payment Processing Enforcement - Complete Documentation

## Overview
This document details the comprehensive payment processing system that handles both **Insurance Subscription Payments** and **Project Share Purchases** through a unified Universal Payment system.

---

## Payment Flow Architecture

### 1. Payment Initialization
**Endpoint:** `POST /api/universal-payments/initialize`

**Process:**
1. Client creates payment request with payment items
2. System creates `UniversalPayment` record with status: `PENDING`
3. Payment gateway (Pesapal) is initialized
4. Client receives redirect URL to complete payment

### 2. Payment Confirmation
**Endpoint:** `GET /api/universal-payments/status/{id}`

**Process:**
1. System queries Pesapal API for transaction status
2. Status is updated in `UniversalPayment` record
3. **CRITICAL TRIGGER:** If status is `COMPLETED` and `items_processed = false`, automatic processing begins

### 3. Item Processing
**Triggered Automatically When:**
- ✅ Payment status is `COMPLETED` (status_code = 1)
- ✅ Items have not been processed yet (`items_processed = false`)

---

## Payment Status Codes

### Pesapal Status Codes
| Code | Status | Action |
|------|--------|--------|
| `1` | COMPLETED | ✅ Process items and mark records as PAID |
| `0` | PENDING | ⏳ Wait for completion |
| `2` | FAILED | ❌ Mark payment as failed |

### System Enforcement
```php
// CRITICAL: Only status code 1 triggers processing
if ($statusCode == '1' || $statusCode == 1 || 
    ($pesapalStatus['status'] ?? '') === 'COMPLETED') {
    $status = 'COMPLETED';
    // Triggers automatic item processing
}
```

---

## Processing Safeguards

### Multi-Layer Protection System

#### Layer 1: Pre-Processing Check
```php
if ($this->items_processed) {
    return ['success' => true, 'message' => 'Items already processed'];
}
```

#### Layer 2: Database Transaction
```php
return \DB::transaction(function () {
    // All processing happens atomically
    // Rollback on any failure
});
```

#### Layer 3: In-Transaction Double-Check
```php
$this->refresh();
if ($this->items_processed) {
    return ['success' => true, 'message' => 'Items already processed'];
}
```

#### Layer 4: Payment Reference Matching
```php
if ($payment->payment_reference === $this->payment_reference && 
    $payment->payment_status === 'Paid') {
    return ['success' => true, 'message' => 'Payment already marked as Paid'];
}
```

---

## Insurance Payment Processing

### Process Flow
1. **Locate Insurance Payment Record**
   ```php
   $payment = \App\Models\InsuranceSubscriptionPayment::find($paymentId);
   ```

2. **Verify Not Already Processed**
   - Check payment_reference matches
   - Check payment_status is not already 'Paid'

3. **Calculate Payment Status**
   ```php
   $newPaidAmount = $payment->paid_amount + $item['amount'];
   
   if ($newPaidAmount >= $totalAmount) {
       $paymentStatus = 'Paid';    // ✅ FULLY PAID
   } else {
       $paymentStatus = 'Partial';  // ⚠️ PARTIALLY PAID
   }
   ```

4. **Update Payment Record**
   ```php
   $payment->update([
       'paid_amount' => $newPaidAmount,
       'payment_status' => $paymentStatus,      // 'Paid' or 'Partial'
       'payment_date' => now(),
       'payment_method' => 'Online',
       'payment_reference' => $this->payment_reference,
       'transaction_id' => $this->pesapal_order_tracking_id,
   ]);
   ```

5. **Cascade Updates**
   - Update `InsuranceSubscription` computed fields
   - Update `InsuranceProgram` computed fields

### Overpayment Protection
```php
if ($newPaidAmount > $totalAmount) {
    $newPaidAmount = $totalAmount; // Cap at total
}
```

---

## Project Share Processing

### Process Flow
1. **Locate Project**
   ```php
   $project = Project::find($projectId);
   ```

2. **Check for Duplicate Shares**
   ```php
   $existingShare = ProjectShare::where('payment_id', $this->id)->first();
   if ($existingShare) {
       return ['success' => true, 'message' => 'Share already created'];
   }
   ```

3. **Validate Share Price & Availability**
   ```php
   // Validate share price exists
   if (!$project->share_price || $project->share_price <= 0) {
       return ['success' => false, 'message' => 'Invalid share price'];
   }
   
   // Calculate shares
   $numberOfShares = round($amount / $share_price);
   
   // Check availability
   $availableShares = $project->total_shares - $project->shares_sold;
   if ($numberOfShares > $availableShares) {
       return ['success' => false, 'message' => 'Not enough shares'];
   }
   ```

4. **Create Share Record**
   ```php
   $share = ProjectShare::create([
       'project_id' => $projectId,
       'investor_id' => $this->user_id,
       'number_of_shares' => $numberOfShares,
       'total_amount_paid' => $amount,
       'share_price_at_purchase' => $project->share_price,
       'payment_id' => $this->id,  // CRITICAL: Links to payment
   ]);
   ```

5. **Create Transaction Record**
   ```php
   $transaction = ProjectTransaction::create([
       'project_id' => $projectId,
       'amount' => $amount,
       'type' => 'income',
       'source' => 'share_purchase',
       'related_share_id' => $share->id,
   ]);
   ```

6. **Update Project Fields**
   ```php
   $project->updateComputedFields(); // Updates shares_sold, etc.
   ```

---

## Comprehensive Logging

### Payment Status Check Logs
```
🎯 Payment is COMPLETED, processing items now
✅ Items processed successfully
❌ Failed to process items
⏳ Payment not yet completed
ℹ️ Items already processed
```

### Insurance Payment Logs
```
✅ Insurance payment now FULLY PAID
⚠️ Insurance payment PARTIALLY paid
💾 Insurance payment record updated
🔄 Updating subscription computed fields
🔄 Updating program computed fields
```

### Share Purchase Logs
```
💾 Creating share record
✅ Share record created
💾 Creating transaction record
✅ Transaction record created
🔄 Updating project computed fields
```

### Success Summary
```
🎉 ============================================
🎉 ALL PAYMENT ITEMS PROCESSED SUCCESSFULLY
🎉 ============================================
```

---

## Error Handling

### Transaction Rollback
If **ANY** item fails to process:
1. All changes are rolled back
2. `items_processed` remains `false`
3. Processing can be retried
4. Detailed error logged with stack trace

### Partial Failure Handling
```php
if (!empty($failedItems)) {
    $this->update([
        'processing_notes' => 'Processed X items, Y failed',
    ]);
    throw new \Exception('Some items failed to process');
    // Triggers transaction rollback
}
```

---

## API Response Structure

### Check Status Response
```json
{
    "success": true,
    "data": {
        "payment": {
            "id": 123,
            "status": "COMPLETED",
            "payment_reference": "REF-12345",
            "total_amount": 50000,
            "items_processed": true,
            "items_processed_at": "2025-11-03 12:34:56"
        },
        "is_completed": true,
        "is_pending": false,
        "is_failed": false,
        "items_processed": true,
        "processing_result": {
            "success": true,
            "message": "All items processed successfully",
            "processed": 2,
            "failed": 0
        }
    }
}
```

---

## Testing Checklist

### Insurance Payment Testing
- [ ] Single month payment
- [ ] Multiple months payment
- [ ] Partial payment (amount < total)
- [ ] Full payment (amount = total)
- [ ] Overpayment attempt (amount > total)
- [ ] Duplicate payment attempt (same reference)
- [ ] Payment to non-existent subscription
- [ ] Status check after Pesapal confirmation

### Share Purchase Testing
- [ ] Single share purchase
- [ ] Multiple shares purchase
- [ ] Purchase with insufficient shares available
- [ ] Duplicate purchase attempt (same payment_id)
- [ ] Purchase with invalid share price
- [ ] Purchase with zero amount
- [ ] Transaction record creation
- [ ] Project fields update

### System Testing
- [ ] Race condition prevention (concurrent requests)
- [ ] Transaction rollback on partial failure
- [ ] Pesapal IPN handling
- [ ] Pesapal callback handling
- [ ] Manual status check
- [ ] Idempotent processing (multiple status checks)

---

## Key Database Fields

### UniversalPayment Table
| Field | Purpose |
|-------|---------|
| `status` | Payment status (PENDING, COMPLETED, FAILED) |
| `payment_status_code` | Pesapal status code (0, 1, 2) |
| `items_processed` | Boolean - have items been processed? |
| `items_processed_at` | Timestamp of processing |
| `payment_reference` | Unique reference for idempotency |
| `payment_items` | JSON array of items to process |

### InsuranceSubscriptionPayment Table
| Field | Purpose |
|-------|---------|
| `payment_status` | Status: 'Pending', 'Partial', 'Paid' |
| `paid_amount` | Amount paid so far |
| `total_amount` | Total amount due |
| `payment_reference` | Links to UniversalPayment |
| `payment_date` | When marked as paid |

### ProjectShare Table
| Field | Purpose |
|-------|---------|
| `payment_id` | Links to UniversalPayment (prevents duplicates) |
| `number_of_shares` | Shares purchased |
| `total_amount_paid` | Amount paid |
| `purchase_date` | When shares were purchased |

---

## Enforcement Rules Summary

### ✅ CRITICAL RULES ENFORCED

1. **Only COMPLETED payments trigger processing**
   - Status code must be `1` or status must be `COMPLETED`
   
2. **Processing happens only once**
   - Multiple safeguards prevent duplicate processing
   
3. **All items must succeed or all rollback**
   - Database transaction ensures atomicity
   
4. **Insurance payments marked as 'Paid' when full**
   - Partial payments marked as 'Partial'
   - Overpayments capped at total amount
   
5. **Shares cannot be duplicated**
   - `payment_id` uniqueness prevents duplicate shares
   
6. **Comprehensive audit trail**
   - Every step logged with emojis for easy scanning
   
7. **Cascade updates computed fields**
   - Subscriptions, programs, and projects stay in sync

---

## Monitoring Commands

### Check Payment Status
```bash
php artisan tinker
$payment = \App\Models\UniversalPayment::find(123);
$payment->status;
$payment->items_processed;
$payment->payment_items;
```

### Check Insurance Payment
```bash
php artisan tinker
$payment = \App\Models\InsuranceSubscriptionPayment::find(456);
$payment->payment_status;
$payment->paid_amount;
$payment->total_amount;
```

### Check Project Shares
```bash
php artisan tinker
$shares = \App\Models\ProjectShare::where('payment_id', 123)->get();
$shares->count();
```

### View Logs
```bash
tail -f storage/logs/laravel.log | grep "🎉\|✅\|❌\|⚠️"
```

---

## Version History

| Date | Version | Changes |
|------|---------|---------|
| 2025-11-03 | 2.0 | Enhanced logging, added emoji indicators, strengthened safeguards |
| 2025-10-XX | 1.0 | Initial implementation |

---

## Support Contacts

For issues with payment processing:
1. Check logs: `storage/logs/laravel.log`
2. Review this documentation
3. Contact system administrator

**Last Updated:** November 3, 2025
**Status:** ✅ Production Ready - Fully Enforced
