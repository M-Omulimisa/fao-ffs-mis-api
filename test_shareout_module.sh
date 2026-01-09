#!/bin/bash

# VSLA Shareout Module - Integration Test Script
# Tests all endpoints and state transitions

echo "=================================="
echo "VSLA Shareout Module - Test Suite"
echo "=================================="
echo ""

# Configuration
API_URL="http://localhost:8888/fao-ffs-mis-api/api"
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." # Replace with valid token
USER_ID="168"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TESTS_PASSED=0
TESTS_FAILED=0

# Helper function to make API call
api_call() {
    local method=$1
    local endpoint=$2
    local data=$3
    
    if [ "$method" = "GET" ]; then
        curl -s -X GET "$API_URL/$endpoint" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -H "Authorization: Bearer $TOKEN" \
            -H "User-Id: $USER_ID"
    else
        curl -s -X POST "$API_URL/$endpoint" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -H "Authorization: Bearer $TOKEN" \
            -H "User-Id: $USER_ID" \
            -d "$data"
    fi
}

# Helper function to check response
check_response() {
    local response=$1
    local test_name=$2
    
    if echo "$response" | grep -q '"success":true'; then
        echo -e "${GREEN}✓ PASS${NC}: $test_name"
        ((TESTS_PASSED++))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC}: $test_name"
        echo "   Response: $response"
        ((TESTS_FAILED++))
        return 1
    fi
}

echo "Starting tests..."
echo ""

# TEST 1: Get Available Cycles
echo "Test 1: Get Available Cycles"
response=$(api_call GET "vsla/shareouts/available-cycles")
check_response "$response" "Get available cycles"
echo ""

# TEST 2: Get Shareout History
echo "Test 2: Get Shareout History"
response=$(api_call GET "vsla/shareouts/history")
check_response "$response" "Get shareout history"
echo ""

# TEST 3: Initiate Shareout
echo "Test 3: Initiate Shareout"
response=$(api_call POST "vsla/shareouts/initiate" '{"cycle_id":7}')
check_response "$response" "Initiate shareout"
SHAREOUT_ID=$(echo "$response" | grep -o '"shareout_id":[0-9]*' | grep -o '[0-9]*')
echo "   Shareout ID: $SHAREOUT_ID"
echo ""

# TEST 4: Calculate Distributions
if [ -n "$SHAREOUT_ID" ]; then
    echo "Test 4: Calculate Distributions"
    response=$(api_call POST "vsla/shareouts/$SHAREOUT_ID/calculate" '{}')
    check_response "$response" "Calculate distributions"
    echo ""
    
    # TEST 5: Get Distributions
    echo "Test 5: Get Member Distributions"
    response=$(api_call GET "vsla/shareouts/$SHAREOUT_ID/distributions")
    check_response "$response" "Get member distributions"
    echo ""
    
    # TEST 6: Get Summary
    echo "Test 6: Get Shareout Summary"
    response=$(api_call GET "vsla/shareouts/$SHAREOUT_ID/summary")
    check_response "$response" "Get shareout summary"
    echo ""
    
    # TEST 7: Approve Shareout
    echo "Test 7: Approve Shareout"
    response=$(api_call POST "vsla/shareouts/$SHAREOUT_ID/approve" '{}')
    check_response "$response" "Approve shareout"
    echo ""
    
    # TEST 8: Complete Shareout
    echo "Test 8: Complete Shareout"
    echo -e "${YELLOW}⚠ WARNING${NC}: This will close the cycle. Skipping in test mode."
    # Uncomment to actually test completion:
    # response=$(api_call POST "vsla/shareouts/$SHAREOUT_ID/complete" '{}')
    # check_response "$response" "Complete shareout"
    echo ""
    
    # TEST 9: Cancel Shareout (if not completed)
    echo "Test 9: Cancel Shareout"
    response=$(api_call POST "vsla/shareouts/$SHAREOUT_ID/cancel" '{}')
    check_response "$response" "Cancel shareout"
    echo ""
fi

# Summary
echo "=================================="
echo "Test Summary"
echo "=================================="
echo -e "Tests Passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Tests Failed: ${RED}$TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed${NC}"
    exit 1
fi
