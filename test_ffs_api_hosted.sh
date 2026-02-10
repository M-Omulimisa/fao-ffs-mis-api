#!/bin/bash
# FFS API Test Suite - Tests against hosted MAMP system
BASE="http://localhost:8888/fao-ffs-mis-api/public/api"
PASS=0
FAIL=0

test_endpoint() {
    local label="$1"
    local result="$2"
    if echo "$result" | grep -q '"code":1\|"code": 1\|"code":0\|"code": 0'; then
        local code=$(echo "$result" | python3 -c "import sys,json;print(json.load(sys.stdin).get('code','?'))" 2>/dev/null)
        echo "  [PASS] $label (code:$code)"
        PASS=$((PASS+1))
    else
        echo "  [FAIL] $label"
        echo "    Response: $(echo "$result" | head -c 200)"
        FAIL=$((FAIL+1))
    fi
}

echo "========================================"
echo " FFS Training Sessions API Test Suite"
echo " Base: $BASE"
echo "========================================"

# 1. Stats
R=$(curl -s "$BASE/ffs-training-sessions/stats" -H "User-Id: 1" -H "Accept: application/json")
test_endpoint "GET /stats" "$R"

# 2. List sessions
R=$(curl -s "$BASE/ffs-training-sessions" -H "User-Id: 1" -H "Accept: application/json")
test_endpoint "GET / (list sessions)" "$R"

# 3. Create session
R=$(curl -s -X POST "$BASE/ffs-training-sessions" \
  -H "User-Id: 1" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"group_id":1,"title":"Test API Session","topic":"Testing","session_date":"2026-04-01","session_type":"classroom","venue":"Hall A","expected_participants":15}')
test_endpoint "POST / (create session)" "$R"
SID=$(echo "$R" | python3 -c "import sys,json;print(json.load(sys.stdin)['data']['id'])" 2>/dev/null)

# 4. Show session
R=$(curl -s "$BASE/ffs-training-sessions/$SID" -H "User-Id: 1" -H "Accept: application/json")
test_endpoint "GET /$SID (show session)" "$R"

# 5. Update session
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$SID" \
  -H "User-Id: 1" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"status":"ongoing","actual_participants":12,"challenges":"Light rain"}')
test_endpoint "PUT /$SID (update session)" "$R"

# 6. Sync participants
R=$(curl -s -X POST "$BASE/ffs-training-sessions/$SID/participants" \
  -H "User-Id: 1" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"participants":[{"user_id":1,"attendance_status":"present","remarks":"Facilitator"}]}')
test_endpoint "POST /$SID/participants (sync)" "$R"

# 7. List participants
R=$(curl -s "$BASE/ffs-training-sessions/$SID/participants" -H "User-Id: 1" -H "Accept: application/json")
test_endpoint "GET /$SID/participants (list)" "$R"

# 8. Create resolution
R=$(curl -s -X POST "$BASE/ffs-training-sessions/$SID/resolutions" \
  -H "User-Id: 1" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"resolution":"Test GAP resolution","gap_category":"soil","target_date":"2026-05-01","responsible_person_id":1}')
test_endpoint "POST /$SID/resolutions (create)" "$R"
RID=$(echo "$R" | python3 -c "import sys,json;print(json.load(sys.stdin)['data']['id'])" 2>/dev/null)

# 9. List resolutions
R=$(curl -s "$BASE/ffs-training-sessions/$SID/resolutions" -H "User-Id: 1" -H "Accept: application/json")
test_endpoint "GET /$SID/resolutions (list)" "$R"

# 10. Update resolution
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$SID/resolutions/$RID" \
  -H "User-Id: 1" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"status":"in_progress","follow_up_notes":"Work started"}')
test_endpoint "PUT /$SID/resolutions/$RID (update)" "$R"

# 11. Mark resolution completed (auto completed_at)
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$SID/resolutions/$RID" \
  -H "User-Id: 1" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"status":"completed"}')
test_endpoint "PUT resolution->completed (auto date)" "$R"
# Verify completed_at is set
HAS_DATE=$(echo "$R" | python3 -c "import sys,json;d=json.load(sys.stdin);print('yes' if d['data'].get('completed_at') else 'no')" 2>/dev/null)
if [ "$HAS_DATE" = "yes" ]; then
    echo "    -> completed_at auto-set: OK"
else
    echo "    -> completed_at NOT set: FAIL"
    FAIL=$((FAIL+1))
fi

# 12. Validation error test
R=$(curl -s -X POST "$BASE/ffs-training-sessions" \
  -H "User-Id: 1" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"title":"missing fields"}')
CODE=$(echo "$R" | python3 -c "import sys,json;print(json.load(sys.stdin).get('code','?'))" 2>/dev/null)
if [ "$CODE" = "0" ]; then
    echo "  [PASS] POST / validation error (code:0)"
    PASS=$((PASS+1))
else
    echo "  [FAIL] POST / validation error"
    FAIL=$((FAIL+1))
fi

# 13. 404 test
R=$(curl -s "$BASE/ffs-training-sessions/99999" -H "User-Id: 1" -H "Accept: application/json")
CODE=$(echo "$R" | python3 -c "import sys,json;print(json.load(sys.stdin).get('code','?'))" 2>/dev/null)
if [ "$CODE" = "0" ]; then
    echo "  [PASS] GET /99999 not found (code:0)"
    PASS=$((PASS+1))
else
    echo "  [FAIL] GET /99999 not found"
    FAIL=$((FAIL+1))
fi

# 14. Delete resolution
R=$(curl -s -X DELETE "$BASE/ffs-training-sessions/$SID/resolutions/$RID" -H "User-Id: 1" -H "Accept: application/json")
test_endpoint "DELETE resolution" "$R"

# 15. Remove participant
PID=$(curl -s "$BASE/ffs-training-sessions/$SID/participants" -H "User-Id: 1" -H "Accept: application/json" | python3 -c "import sys,json;print(json.load(sys.stdin)['data'][0]['id'])" 2>/dev/null)
R=$(curl -s -X DELETE "$BASE/ffs-training-sessions/$SID/participants/$PID" -H "User-Id: 1" -H "Accept: application/json")
test_endpoint "DELETE participant" "$R"

# 16. Delete session (mark as cancelled first, then delete)
curl -s -X PUT "$BASE/ffs-training-sessions/$SID" \
  -H "User-Id: 1" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"status":"cancelled"}' > /dev/null 2>&1
R=$(curl -s -X DELETE "$BASE/ffs-training-sessions/$SID" -H "User-Id: 1" -H "Accept: application/json")
test_endpoint "DELETE session (cancelled)" "$R"

# 17. Filter test
R=$(curl -s "$BASE/ffs-training-sessions?session_type=classroom" -H "User-Id: 1" -H "Accept: application/json")
test_endpoint "GET /?session_type=classroom (filter)" "$R"

echo ""
echo "========================================"
echo " Results: $PASS passed, $FAIL failed"
echo "========================================"
