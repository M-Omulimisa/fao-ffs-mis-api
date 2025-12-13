#!/bin/bash

# VSLA API Endpoints Test Script
# Tests all restored VSLA meeting endpoints

BASE_URL="http://localhost:8888/fao-ffs-mis-api/public/api"
TOKEN="your-auth-token-here"

echo "============================================"
echo "VSLA API Endpoints - Connectivity Test"
echo "============================================"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Get Meeting Statistics
echo -e "${YELLOW}1. Testing GET /api/vsla-meetings/stats${NC}"
STATS_RESPONSE=$(curl -s -X GET "$BASE_URL/vsla-meetings/stats" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

if echo "$STATS_RESPONSE" | grep -q "success"; then
    echo -e "${GREEN}✅ Stats endpoint accessible${NC}"
    echo "Response: $STATS_RESPONSE"
else
    echo -e "${RED}❌ Stats endpoint failed${NC}"
    echo "Response: $STATS_RESPONSE"
fi
echo ""

# Test 2: List Meetings
echo -e "${YELLOW}2. Testing GET /api/vsla-meetings${NC}"
LIST_RESPONSE=$(curl -s -X GET "$BASE_URL/vsla-meetings?per_page=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

if echo "$LIST_RESPONSE" | grep -q "success"; then
    echo -e "${GREEN}✅ List endpoint accessible${NC}"
    echo "Response: $LIST_RESPONSE"
else
    echo -e "${RED}❌ List endpoint failed${NC}"
    echo "Response: $LIST_RESPONSE"
fi
echo ""

# Test 3: Submit Meeting (with test data)
echo -e "${YELLOW}3. Testing POST /api/vsla-meetings/submit${NC}"
echo "Note: This will create a test meeting. Update the payload with valid IDs."
SUBMIT_RESPONSE=$(curl -s -X POST "$BASE_URL/vsla-meetings/submit" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "local_id": "test-'$(date +%s)'",
    "cycle_id": 1,
    "group_id": 1,
    "meeting_date": "2025-01-30",
    "members_present": 10,
    "attendance_data": [
      {"member_id": 1, "status": "present"}
    ]
  }')

if echo "$SUBMIT_RESPONSE" | grep -q "meeting_id\|success"; then
    echo -e "${GREEN}✅ Submit endpoint accessible${NC}"
    echo "Response: $SUBMIT_RESPONSE"
else
    echo -e "${RED}❌ Submit endpoint failed (expected - might be validation error)${NC}"
    echo "Response: $SUBMIT_RESPONSE"
fi
echo ""

echo "============================================"
echo "Test Complete!"
echo "============================================"
echo ""
echo "Note: If you see 401 Unauthorized, update the TOKEN variable"
echo "Note: If you see 404 Not Found, check that routes are registered"
echo "Note: If you see 422 Validation Error, update test data with valid IDs"
echo ""
