#!/bin/bash

echo "🧪 Testing Pesapal API Integration - Debug Script"
echo "=================================================="

echo ""
echo "1️⃣ Testing Configuration..."
curl -s "http://localhost:8888/blitxpress/payment-test/config" | jq -r '.data.configuration_status'

echo ""
echo "2️⃣ Testing Payment without IPN notification_id..."
curl -s -X POST "http://localhost:8888/blitxpress/payment-test/initialize" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "amount=1000&currency=UGX&customer_name=Debug%20Test&customer_email=debug@test.com&customer_phone=%2B256700000000&description=Debug%20Test%20Payment&merchant_reference=BX-DEBUG-$(date +%Y%m%d%H%M%S)&callback_url=http://localhost:8888/blitxpress/payment-test/callback&debug_mode=1&validate_response=1&api_environment=sandbox" | jq .

echo ""
echo "3️⃣ Checking recent logs for errors..."
cd /Applications/MAMP/htdocs/blitxpress && tail -n 5 storage/logs/laravel.log | grep -E "(ERROR|Pesapal)"

echo ""
echo "✅ Debug test complete!"
