# Membership Payment System - Quick Testing Guide

## Prerequisites
- ✅ Backend server running (MAMP)
- ✅ Database migrations completed
- ✅ Mobile app compiled and running
- ✅ Test user accounts created

---

## Quick Test Scenarios

### Scenario 1: Test API Endpoints (Backend)

#### 1.1 Get Membership Benefits
```bash
curl -X GET "http://localhost/dtehm-insurance-api/public/api/membership-benefits"
```

**Expected Response:**
```json
{
  "code": 1,
  "message": "Membership benefits retrieved successfully",
  "data": {
    "membership_fee": 20000,
    "currency": "UGX",
    "benefits": [...]
  }
}
```

#### 1.2 Create Membership Payment
```bash
curl -X POST "http://localhost/dtehm-insurance-api/public/api/membership-payment" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "amount": 20000,
    "payment_method": "MOBILE_MONEY",
    "payment_phone_number": "0771234567"
  }'
```

**Expected Response:**
```json
{
  "code": 1,
  "message": "Membership payment initiated successfully. Awaiting confirmation.",
  "data": {
    "id": 1,
    "payment_reference": "MEM-...",
    "status": "PENDING",
    ...
  }
}
```

#### 1.3 Get Membership Status
```bash
curl -X GET "http://localhost/dtehm-insurance-api/public/api/membership-status?user_id=1"
```

**Expected Response:**
```json
{
  "code": 1,
  "data": {
    "has_valid_membership": false,
    "requires_payment": true,
    ...
  }
}
```

#### 1.4 Confirm Payment
```bash
curl -X POST "http://localhost/dtehm-insurance-api/public/api/membership-payment/confirm" \
  -H "Content-Type: application/json" \
  -d '{
    "payment_reference": "MEM-ABC123-1"
  }'
```

**Expected Response:**
```json
{
  "code": 1,
  "message": "Membership payment confirmed successfully",
  "data": {
    "user": {
      "is_membership_paid": true,
      ...
    }
  }
}
```

---

### Scenario 2: Test Admin Panel

1. **Login to Admin Panel**
   - URL: `http://localhost/dtehm-insurance-api/public/admin`
   - Login with admin credentials

2. **Navigate to Membership Payments**
   - Click "Membership Payments" in sidebar
   - Should see list of all membership payments

3. **Test Grid Features**
   - ✅ Search by payment reference
   - ✅ Filter by user
   - ✅ Filter by status
   - ✅ Filter by payment method
   - ✅ Sort columns

4. **Create New Payment**
   - Click "New" button
   - Select user
   - Amount auto-filled (20000)
   - Select payment method
   - Save

5. **Confirm Pending Payment**
   - Find pending payment in grid
   - Click "Confirm" button
   - Verify status changes to CONFIRMED
   - Verify user's membership status updates

---

### Scenario 3: Test Mobile App (Non-Admin User)

#### 3.1 First Time Login (No Membership)
```
1. Open app
2. Login with non-admin user
3. ✅ Should see MembershipPaymentScreen
4. ✅ Cannot go back (WillPopScope blocks)
5. ✅ See benefits, UGX 20,000, payment methods
6. Select payment method (e.g., Mobile Money)
7. Enter phone number
8. Click "Proceed to Payment"
9. ✅ See success dialog with payment reference
10. ✅ Dialog shows "Wait for confirmation"
```

**Expected UI:**
- Beautiful gradient welcome section
- List of 10 benefits
- Prominent "UGX 20,000" display
- Payment method dropdown
- Conditional input fields
- Large payment button
- Help text at bottom

#### 3.2 Admin Confirms Payment
```
1. Admin logs into admin panel
2. Goes to Membership Payments
3. Finds user's pending payment
4. Clicks "Confirm" button
5. Status changes to CONFIRMED
```

#### 3.3 User Reopens App (After Confirmation)
```
1. Close and reopen app
2. Login with same non-admin user
3. ✅ Should NOT see MembershipPaymentScreen
4. ✅ Should go directly to MainScreen
5. ✅ Has full access to all features
```

---

### Scenario 4: Test Admin User Bypass

```
1. Open app
2. Login with admin user (user_type = 'admin')
3. ✅ Should NOT see MembershipPaymentScreen
4. ✅ Should go directly to MainScreen
5. ✅ Has full access immediately (no payment required)
```

---

### Scenario 5: Test Different Payment Methods

#### Mobile Money
```
1. Select "Mobile Money" from dropdown
2. ✅ Phone number field appears
3. Enter 0771234567
4. Submit payment
5. ✅ Payment created with payment_phone_number
```

#### Bank Transfer
```
1. Select "Bank Transfer" from dropdown
2. ✅ Account number field appears
3. Enter account number
4. Submit payment
5. ✅ Payment created with payment_account_number
```

#### Cash Payment
```
1. Select "Cash Payment" from dropdown
2. ✅ No additional fields appear
3. ✅ See help text about visiting office
4. Submit payment
5. ✅ Payment created with method CASH
```

---

### Scenario 6: Test Error Handling

#### 6.1 Duplicate Payment Prevention (Backend)
```
1. Create payment for user A
2. Try to create another payment for user A (while first is PENDING)
3. ✅ Should return error: "User already has an active membership"
```

#### 6.2 Missing Required Fields
```
1. Try to create payment without user_id
2. ✅ Should return error: "User ID is required"
```

#### 6.3 Network Error (Mobile)
```
1. Turn off WiFi/Mobile Data
2. Try to open app
3. ✅ Should fail gracefully
4. ✅ Should allow access (fail-open behavior)
```

#### 6.4 Invalid Payment Reference
```
1. Try to confirm payment with non-existent reference
2. ✅ Should return error: "Membership payment not found"
```

---

## Automated Test Script (Optional)

Create file: `test_membership_api.sh`

```bash
#!/bin/bash

BASE_URL="http://localhost/dtehm-insurance-api/public/api"

echo "Testing Membership Payment API..."

# Test 1: Get Benefits
echo "\n1. Testing GET /membership-benefits"
curl -X GET "$BASE_URL/membership-benefits"

# Test 2: Create Payment
echo "\n\n2. Testing POST /membership-payment"
PAYMENT_RESPONSE=$(curl -X POST "$BASE_URL/membership-payment" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "amount": 20000,
    "payment_method": "MOBILE_MONEY",
    "payment_phone_number": "0771234567"
  }')

echo $PAYMENT_RESPONSE

# Extract payment_reference from response
PAYMENT_REF=$(echo $PAYMENT_RESPONSE | grep -o '"payment_reference":"[^"]*"' | cut -d'"' -f4)

# Test 3: Get Status
echo "\n\n3. Testing GET /membership-status"
curl -X GET "$BASE_URL/membership-status?user_id=1"

# Test 4: Confirm Payment
if [ ! -z "$PAYMENT_REF" ]; then
  echo "\n\n4. Testing POST /membership-payment/confirm"
  curl -X POST "$BASE_URL/membership-payment/confirm" \
    -H "Content-Type: application/json" \
    -d "{\"payment_reference\": \"$PAYMENT_REF\"}"
fi

# Test 5: Check Status Again
echo "\n\n5. Testing GET /membership-status (after confirmation)"
curl -X GET "$BASE_URL/membership-status?user_id=1"

echo "\n\nTests Complete!"
```

Run with: `bash test_membership_api.sh`

---

## Success Criteria

### Backend Tests ✅
- [ ] All API endpoints return correct HTTP status codes
- [ ] Payment creation generates unique reference
- [ ] Duplicate payment prevention works
- [ ] Payment confirmation updates user model
- [ ] Expiry date calculation correct
- [ ] Admin panel displays correctly
- [ ] Confirm button works
- [ ] Filters and search work

### Mobile App Tests ✅
- [ ] Non-admin user sees payment screen
- [ ] Admin user bypasses payment screen
- [ ] Paid user bypasses payment screen
- [ ] Payment screen UI renders correctly
- [ ] All payment methods work
- [ ] Form validation works
- [ ] Success dialog displays
- [ ] Cannot go back when unpaid
- [ ] Can access app after payment confirmed

### Integration Tests ✅
- [ ] End-to-end flow works (create → confirm → access)
- [ ] Multiple users can pay simultaneously
- [ ] App handles network errors gracefully
- [ ] Database constraints enforced at app level
- [ ] No security vulnerabilities

---

## Troubleshooting

### Issue: "Column not found" error
**Solution:** Run migrations
```bash
php artisan migrate
```

### Issue: "Route not found"
**Solution:** Clear route cache
```bash
php artisan route:clear
php artisan route:cache
```

### Issue: "Class not found: MembershipPayment"
**Solution:** Clear application cache
```bash
php artisan cache:clear
composer dump-autoload
```

### Issue: Mobile app won't compile
**Solution:** Check imports in OnBoardingScreen.dart
```dart
import 'membership/MembershipPaymentScreen.dart';
```

### Issue: Admin sees payment screen
**Solution:** Check user_type in database
```sql
UPDATE users SET user_type = 'admin' WHERE id = 1;
```

### Issue: Payment confirmed but user still sees payment screen
**Solution:** Check user record
```sql
SELECT is_membership_paid, membership_paid_at FROM users WHERE id = 1;
```

If false, manually update:
```sql
UPDATE users 
SET is_membership_paid = 1, 
    membership_paid_at = NOW(),
    membership_amount = 20000
WHERE id = 1;
```

---

## Database Queries for Testing

### Check All Membership Payments
```sql
SELECT id, user_id, payment_reference, amount, status, membership_type
FROM membership_payments
ORDER BY created_at DESC;
```

### Check User Membership Status
```sql
SELECT id, name, user_type, is_membership_paid, membership_paid_at, membership_amount
FROM users
WHERE id = 1;
```

### Manually Confirm Payment
```sql
UPDATE membership_payments 
SET status = 'CONFIRMED', 
    confirmed_at = NOW()
WHERE id = 1;

UPDATE users
SET is_membership_paid = 1,
    membership_paid_at = NOW(),
    membership_amount = 20000,
    membership_payment_id = 1
WHERE id = 1;
```

### Reset User Membership (for testing)
```sql
UPDATE users 
SET is_membership_paid = 0,
    membership_paid_at = NULL,
    membership_amount = NULL,
    membership_payment_id = NULL
WHERE id = 1;

DELETE FROM membership_payments WHERE user_id = 1;
```

---

## Test Results Template

```
Date: ___________
Tester: ___________

Backend API Tests:
[ ] GET /membership-benefits - PASS/FAIL
[ ] POST /membership-payment - PASS/FAIL
[ ] GET /membership-status - PASS/FAIL
[ ] POST /membership-payment/confirm - PASS/FAIL
[ ] GET /membership-payments - PASS/FAIL

Admin Panel Tests:
[ ] Login - PASS/FAIL
[ ] Grid display - PASS/FAIL
[ ] Search/Filter - PASS/FAIL
[ ] Create payment - PASS/FAIL
[ ] Confirm payment - PASS/FAIL

Mobile App Tests:
[ ] Non-admin sees payment screen - PASS/FAIL
[ ] Admin bypasses payment screen - PASS/FAIL
[ ] Paid user bypasses payment screen - PASS/FAIL
[ ] Payment submission works - PASS/FAIL
[ ] Success dialog shows - PASS/FAIL

Notes:
_______________________________
_______________________________
```

---

**Ready to test! Good luck! 🚀**
