# Order Management System - Quick Testing Guide

## 🚀 Quick Start

### 1. Access Order Management
Navigate to: **Admin Panel → Orders**

---

## ✅ Test Scenario 1: Create Simple Order

### Steps:
1. Click **"New"** button
2. Fill in:
   - **Order Date:** Today's date
   - **Customer Name:** John Doe
   - **Customer Phone:** 0700 123 456
   - **Customer Email:** john@example.com (optional)
   - **Customer Address:** Kampala, Uganda
3. **Delivery Information:**
   - **Delivery Method:** Home Delivery
   - **Delivery Location:** Select from dropdown
   - **Delivery District:** Kampala
   - **Delivery Fee:** 5000
4. **Add Order Item:**
   - Click "New" under Items section
   - **Product:** Select "Standard Wheelchair" (UGX 850,000)
   - **Quantity:** 1
   - **Unit Price:** Leave empty (will use product price)
   - **Color:** Black (if applicable)
   - **Size:** Medium (if applicable)
5. **Payment Information:**
   - **Payment Gateway:** Manual Payment
   - **Payment Status:** Pending Payment
6. **Order Status:** Pending
7. Click **"Submit"**

### Expected Result:
✅ Order created successfully  
✅ Receipt number generated: ORD-20251115-000001  
✅ Invoice number generated: INV-20251115-000001  
✅ Subtotal: UGX 850,000  
✅ Delivery: UGX 5,000  
✅ Total: UGX 855,000  
✅ Success message shows order details  
✅ Email sent to customer (check logs)

---

## ✅ Test Scenario 2: Create Multi-Item Order

### Steps:
1. Click **"New"** button
2. Fill customer info as above
3. **Add Multiple Items:**
   - **Item 1:** Electric Mobility Scooter (UGX 2,500,000) × 1
   - **Item 2:** Adjustable Crutches (UGX 150,000) × 2
   - **Item 3:** Medical Alert Device (UGX 300,000) × 1
4. **Delivery Fee:** 10,000
5. **Tax:** 50,000
6. **Discount:** 25,000
7. Click **"Submit"**

### Expected Result:
✅ Order created with 3 items  
✅ Subtotal: UGX 3,100,000 (2,500,000 + 300,000 + 300,000)  
✅ Delivery: UGX 10,000  
✅ Tax: UGX 50,000  
✅ Discount: UGX 25,000  
✅ Total: UGX 3,135,000  
✅ All items saved with correct unit prices and subtotals

---

## ✅ Test Scenario 3: Edit Order

### Steps:
1. Click **"Edit"** on any existing order
2. **Observe:**
   - Existing items shown in read-only table
   - Cannot modify items
3. **Update:**
   - Change **Order Status** to "Processing"
   - Add **Admin Notes:** "Customer called to confirm delivery address"
   - Update **Payment Status** to "Paid"
   - Enter **Payment Confirmation:** "REF123456"
4. Click **"Submit"**

### Expected Result:
✅ Order updated successfully  
✅ Items unchanged (read-only)  
✅ Status changed to Processing  
✅ Notes saved  
✅ Payment status updated  
✅ Email sent for status change

---

## ✅ Test Scenario 4: Grid Filters

### Test Each Filter:
1. **Filter by Customer Name:** Enter "John"
2. **Filter by Phone:** Enter "0700"
3. **Filter by Receipt Number:** Enter "ORD-"
4. **Filter by Order Status:** Select "Pending"
5. **Filter by Payment Status:** Select "Pending Payment"
6. **Filter by Date Range:** Select this week
7. **Quick Search:** Try customer name, phone, email

### Expected Result:
✅ Each filter returns correct results  
✅ Quick search works across all fields  
✅ Results update immediately

---

## ✅ Test Scenario 5: Grid Actions

### Test Grid Features:
1. **Edit Status Inline:**
   - Click on order status in grid
   - Change from "Pending" to "Processing"
   - Press Enter
2. **Export:**
   - Click "Export" dropdown
   - Select "All" → CSV
   - Verify downloaded file
3. **Enhanced View:**
   - Click star icon on any order
   - Verify enhanced detail view opens
4. **Pagination:**
   - Change per page to 50
   - Navigate between pages

### Expected Result:
✅ Inline editing works  
✅ CSV export downloads with all data  
✅ Enhanced view shows full order details  
✅ Pagination works correctly

---

## ✅ Test Scenario 6: Validation

### Test Validation Rules:
1. **Try to create order without items:**
   - Fill customer info
   - Don't add any items
   - Click Submit
   - **Expected:** Error "Please add at least one item"

2. **Try to create order with invalid product:**
   - Manually enter invalid product ID in browser dev tools
   - Click Submit
   - **Expected:** Error "Invalid product selected"

3. **Try to create order with zero quantity:**
   - Add item with quantity 0
   - Click Submit
   - **Expected:** Error "Quantity must be greater than zero"

4. **Try to create order with missing customer name:**
   - Leave customer name empty
   - Click Submit
   - **Expected:** Required field validation

### Expected Result:
✅ All validations prevent invalid data  
✅ Clear error messages shown  
✅ Order not saved

---

## ✅ Test Scenario 7: Receipt/Invoice Numbers

### Test Auto-Generation:
1. Create 3 orders in sequence
2. Check receipt numbers:
   - ORD-20251115-000001
   - ORD-20251115-000002
   - ORD-20251115-000003
3. Check invoice numbers:
   - INV-20251115-000001
   - INV-20251115-000002
   - INV-20251115-000003

### Expected Result:
✅ Numbers increment sequentially  
✅ Date portion matches current date  
✅ Numbers are unique  
✅ Padded to 6 digits

---

## ✅ Test Scenario 8: Email Notifications

### Test Email Triggers:
1. **Create new order:**
   - Check logs: "Sending pending email for order X"
   - Customer should receive order confirmation
   - Admin should receive new order notification

2. **Change status to Processing:**
   - Check logs: "Sending processing email for order X"
   - Customer should receive processing notification

3. **Change status to Completed:**
   - Check logs: "Sending completed email for order X"
   - Customer should receive delivery confirmation

4. **Change status to Cancelled:**
   - Check logs: "Sending canceled email for order X"
   - Customer should receive cancellation notice

### Expected Result:
✅ Email sent for each status change  
✅ Only one email per status (not duplicated)  
✅ Logs show email activity  
✅ Email fields updated in database

---

## ✅ Test Scenario 9: Price Capture

### Test Price at Time of Order:
1. **Before creating order:**
   - Note product price: "Standard Wheelchair" = UGX 850,000
2. **Create order with that product**
3. **After order created:**
   - Change product price to UGX 900,000
4. **View created order:**
   - Order item should still show UGX 850,000
5. **Create new order:**
   - New order should use UGX 900,000

### Expected Result:
✅ Order captures price at time of creation  
✅ Past orders unaffected by price changes  
✅ New orders use current prices

---

## ✅ Test Scenario 10: Mobile App Integration

### Test API Compatibility:
1. **Use mobile app to create order** (if available)
2. **Check admin panel:**
   - Order appears in grid
   - All fields populated correctly
   - Items calculated correctly
3. **Update order in admin panel:**
   - Change status to Processing
4. **Check mobile app:**
   - Status updated
   - Customer receives notification

### Expected Result:
✅ API orders create successfully  
✅ Admin can view/edit API orders  
✅ Status changes sync to app  
✅ No errors in logs

---

## 🐛 Known Issues to Watch For

### ⚠️ Potential Issues:
1. **Receipt number collision:** If multiple orders created simultaneously
2. **Email queue:** If email service is slow
3. **Product not found:** If product deleted after order created
4. **Currency rounding:** Large order totals with many decimal places
5. **Browser timeout:** Very large orders (100+ items)

### Workarounds:
1. Use unique timestamps in receipt numbers
2. Queue emails with background jobs
3. Soft delete products instead of hard delete
4. Round to 2 decimal places consistently
5. Paginate items in edit view

---

## 📊 Success Metrics

### After Testing, Verify:
- ✅ All 10 test scenarios pass
- ✅ No errors in Laravel logs
- ✅ No SQL errors
- ✅ Emails sending correctly
- ✅ Grid performance acceptable (< 2 seconds)
- ✅ Form saves in < 5 seconds
- ✅ Mobile app still works
- ✅ Existing orders display correctly
- ✅ No data corruption

---

## 🔧 Troubleshooting

### If Order Creation Fails:

1. **Check Laravel Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Check Database:**
   ```sql
   SELECT * FROM orders ORDER BY id DESC LIMIT 10;
   SELECT * FROM ordered_items ORDER BY id DESC LIMIT 10;
   ```

3. **Check Migrations:**
   ```bash
   php artisan migrate:status
   ```

4. **Clear Cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

### If Emails Not Sending:

1. **Check Queue:**
   ```bash
   php artisan queue:work
   ```

2. **Check Email Configuration:**
   ```bash
   php artisan config:cache
   ```

3. **Check Logs:**
   ```bash
   grep "email" storage/logs/laravel.log
   ```

### If Grid Slow:

1. **Check Query Log:**
   - Enable query logging
   - Look for N+1 queries
   - Verify eager loading working

2. **Check Database Indexes:**
   ```sql
   SHOW INDEX FROM orders;
   SHOW INDEX FROM ordered_items;
   ```

---

## 📝 Testing Checklist

### Before Going Live:
- [ ] Create 10 test orders with different scenarios
- [ ] Edit 5 existing orders
- [ ] Filter orders by all available filters
- [ ] Export orders to CSV/Excel
- [ ] Change order statuses and verify emails
- [ ] Test with mobile app (if available)
- [ ] Test with large orders (20+ items)
- [ ] Test concurrent order creation
- [ ] Test all validation rules
- [ ] Verify receipt/invoice numbers unique
- [ ] Check performance with 1000+ orders
- [ ] Test on different browsers
- [ ] Test on mobile browser
- [ ] Review all logs for errors
- [ ] Backup database before go-live

### After Go-Live:
- [ ] Monitor error logs for 24 hours
- [ ] Check email delivery rates
- [ ] Monitor database performance
- [ ] Collect user feedback
- [ ] Watch for edge cases
- [ ] Document any issues found
- [ ] Plan fixes for any bugs
- [ ] Celebrate success! 🎉

---

**Testing Started:** _______________  
**Testing Completed:** _______________  
**Issues Found:** _______________  
**Status:** [ ] Pass [ ] Fail [ ] Needs Review  
**Tested By:** _______________  
**Approved By:** _______________
