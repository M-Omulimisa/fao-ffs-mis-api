#!/bin/bash

echo "=== PESAPAL PAYMENT INTEGRATION TEST ==="
echo "Testing with phone number: +256783204665"
echo "Date: $(date)"
echo "========================================"
echo

# Get CSRF token first
echo "Step 1: Getting CSRF token..."
CSRF_TOKEN=$(curl -s "http://localhost:8888/blitxpress/payment-test" | grep -oP 'csrf-token" content="\K[^"]+')
COOKIE=$(curl -s -D - "http://localhost:8888/blitxpress/payment-test" | grep 'Set-Cookie:' | awk '{print $2}' | tr -d '\r')

echo "CSRF Token: $CSRF_TOKEN"
echo "Cookie: $COOKIE"
echo

# Make payment initialization request
echo "Step 2: Making payment initialization request..."
echo "Request URL: http://localhost:8888/blitxpress/payment-test/initialize"
echo "Method: POST"
echo "Headers:"
echo "  - Content-Type: application/json"
echo "  - Accept: application/json"
echo "  - X-CSRF-TOKEN: $CSRF_TOKEN"
echo "  - Cookie: $COOKIE"
echo
echo "Request Payload:"
echo '{
    "order_id": "99999",
    "amount": "50000",
    "currency": "UGX",
    "customer_name": "Test Customer",
    "customer_email": "test@blitxpress.com",
    "customer_phone": "+256783204665",
    "description": "Test order for Pesapal technical documentation",
    "test_type": "production_test"
}'
echo
echo "========================================"
echo "PESAPAL API RESPONSE:"
echo "========================================"

curl -X POST "http://localhost:8888/blitxpress/payment-test/initialize" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H "X-CSRF-TOKEN: $CSRF_TOKEN" \
-H "Cookie: $COOKIE" \
-H "X-Requested-With: XMLHttpRequest" \
-d '{
    "order_id": "99999",
    "amount": "50000",
    "currency": "UGX",
    "customer_name": "Test Customer",
    "customer_email": "test@blitxpress.com",
    "customer_phone": "+256783204665",
    "description": "Test order for Pesapal technical documentation",
    "test_type": "production_test"
}' \
-w "\n\nHTTP Status Code: %{http_code}\nResponse Time: %{time_total}s\nTotal Time: %{time_starttransfer}s\n" \
-s

echo
echo "========================================"
echo "TEST COMPLETED"
echo "========================================"
