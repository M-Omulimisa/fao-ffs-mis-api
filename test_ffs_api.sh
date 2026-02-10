#!/bin/bash
# FFS Training Sessions API Test Script
# Tests all endpoints for the FFS Training module

BASE_URL="http://127.0.0.1:8899/api"
USER_ID=1
GROUP_ID=1

echo "============================================"
echo "  FFS TRAINING SESSIONS API TEST"
echo "============================================"

# ─────────────────────────────────────
# 1. Get Stats (empty initially)
# ─────────────────────────────────────
echo ""
echo ">>> TEST 1: GET /ffs-training-sessions/stats"
STATS=$(curl -s -X GET "${BASE_URL}/ffs-training-sessions/stats" \
  -H "User-Id: ${USER_ID}" \
  -H "Accept: application/json")
echo "$STATS" | python3 -m json.tool 2>/dev/null || echo "$STATS"

# ─────────────────────────────────────
# 2. Create a training session
# ─────────────────────────────────────
echo ""
echo ">>> TEST 2: POST /ffs-training-sessions (Create Session)"
CREATE_RESP=$(curl -s -X POST "${BASE_URL}/ffs-training-sessions" \
  -H "User-Id: ${USER_ID}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "group_id": '${GROUP_ID}',
    "title": "Soil Management Basics",
    "topic": "Soil preparation and composting",
    "description": "Training on basic soil management techniques for improved crop yields",
    "session_date": "2026-03-01",
    "start_time": "09:00",
    "end_time": "12:00",
    "venue": "Community Hall, Kitopoloi",
    "session_type": "classroom",
    "expected_participants": 25,
    "materials_used": "Soil samples, compost demonstration kit, handouts",
    "notes": "First session of the season"
  }')
echo "$CREATE_RESP" | python3 -m json.tool 2>/dev/null || echo "$CREATE_RESP"

# Extract session ID
SESSION_ID=$(echo "$CREATE_RESP" | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['id'])" 2>/dev/null)
echo ">>> Created Session ID: ${SESSION_ID}"

# ─────────────────────────────────────
# 3. Create a second session
# ─────────────────────────────────────
echo ""
echo ">>> TEST 3: POST /ffs-training-sessions (Create Session 2 - Field)"
CREATE2=$(curl -s -X POST "${BASE_URL}/ffs-training-sessions" \
  -H "User-Id: ${USER_ID}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "group_id": '${GROUP_ID}',
    "title": "Pest Identification Field Walk",
    "topic": "Common pests and natural pest control",
    "session_date": "2026-03-08",
    "start_time": "08:00",
    "end_time": "11:00",
    "venue": "FFS Demo Plot, Kitopoloi",
    "session_type": "field",
    "expected_participants": 20
  }')
echo "$CREATE2" | python3 -m json.tool 2>/dev/null || echo "$CREATE2"

# ─────────────────────────────────────
# 4. List all sessions
# ─────────────────────────────────────
echo ""
echo ">>> TEST 4: GET /ffs-training-sessions (List All)"
curl -s -X GET "${BASE_URL}/ffs-training-sessions" \
  -H "User-Id: ${USER_ID}" \
  -H "Accept: application/json" | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 5. Show single session
# ─────────────────────────────────────
echo ""
echo ">>> TEST 5: GET /ffs-training-sessions/${SESSION_ID} (Show)"
curl -s -X GET "${BASE_URL}/ffs-training-sessions/${SESSION_ID}" \
  -H "User-Id: ${USER_ID}" \
  -H "Accept: application/json" | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 6. Update session
# ─────────────────────────────────────
echo ""
echo ">>> TEST 6: PUT /ffs-training-sessions/${SESSION_ID} (Update)"
curl -s -X PUT "${BASE_URL}/ffs-training-sessions/${SESSION_ID}" \
  -H "User-Id: ${USER_ID}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "ongoing",
    "actual_participants": 22,
    "challenges": "Some farmers arrived late due to rain",
    "recommendations": "Schedule earlier during rainy season"
  }' | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 7. Add participants (bulk sync)
# ─────────────────────────────────────
echo ""
echo ">>> TEST 7: POST /ffs-training-sessions/${SESSION_ID}/participants (Sync)"
curl -s -X POST "${BASE_URL}/ffs-training-sessions/${SESSION_ID}/participants" \
  -H "User-Id: ${USER_ID}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "participants": [
      {"user_id": 1, "attendance_status": "present", "remarks": "Facilitator"},
      {"user_id": 1, "attendance_status": "present", "remarks": "Updated remark - arrived on time"}
    ]
  }' | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 8. List participants
# ─────────────────────────────────────
echo ""
echo ">>> TEST 8: GET /ffs-training-sessions/${SESSION_ID}/participants"
PARTS=$(curl -s -X GET "${BASE_URL}/ffs-training-sessions/${SESSION_ID}/participants" \
  -H "User-Id: ${USER_ID}" \
  -H "Accept: application/json")
echo "$PARTS" | python3 -m json.tool 2>/dev/null || echo "$PARTS"

PARTICIPANT_ID=$(echo "$PARTS" | python3 -c "import sys,json; print(json.load(sys.stdin)['data'][0]['id'])" 2>/dev/null)
echo ">>> Participant ID: ${PARTICIPANT_ID}"

# ─────────────────────────────────────
# 9. Create resolution (GAP)
# ─────────────────────────────────────
echo ""
echo ">>> TEST 9: POST /ffs-training-sessions/${SESSION_ID}/resolutions (Create)"
RES_RESP=$(curl -s -X POST "${BASE_URL}/ffs-training-sessions/${SESSION_ID}/resolutions" \
  -H "User-Id: ${USER_ID}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "resolution": "Adopt composting for soil improvement",
    "description": "All group members to start composting organic waste for use as fertilizer",
    "gap_category": "soil",
    "responsible_person_id": 1,
    "target_date": "2026-04-01",
    "follow_up_notes": "Check progress during next session"
  }')
echo "$RES_RESP" | python3 -m json.tool 2>/dev/null || echo "$RES_RESP"

RESOLUTION_ID=$(echo "$RES_RESP" | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['id'])" 2>/dev/null)
echo ">>> Resolution ID: ${RESOLUTION_ID}"

# ─────────────────────────────────────
# 10. Create second resolution
# ─────────────────────────────────────
echo ""
echo ">>> TEST 10: POST /ffs-training-sessions/${SESSION_ID}/resolutions (Create 2)"
curl -s -X POST "${BASE_URL}/ffs-training-sessions/${SESSION_ID}/resolutions" \
  -H "User-Id: ${USER_ID}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "resolution": "Use mulching to conserve soil moisture",
    "description": "Apply mulch around crops to retain moisture and suppress weeds",
    "gap_category": "water",
    "target_date": "2026-03-15"
  }' | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 11. List resolutions
# ─────────────────────────────────────
echo ""
echo ">>> TEST 11: GET /ffs-training-sessions/${SESSION_ID}/resolutions (List)"
curl -s -X GET "${BASE_URL}/ffs-training-sessions/${SESSION_ID}/resolutions" \
  -H "User-Id: ${USER_ID}" \
  -H "Accept: application/json" | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 12. Update resolution (mark in progress)
# ─────────────────────────────────────
echo ""
echo ">>> TEST 12: PUT /ffs-training-sessions/${SESSION_ID}/resolutions/${RESOLUTION_ID} (Update)"
curl -s -X PUT "${BASE_URL}/ffs-training-sessions/${SESSION_ID}/resolutions/${RESOLUTION_ID}" \
  -H "User-Id: ${USER_ID}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "in_progress",
    "follow_up_notes": "3 members have started composting"
  }' | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 13. Mark resolution completed
# ─────────────────────────────────────
echo ""
echo ">>> TEST 13: PUT resolution -> completed (auto sets completed_at)"
curl -s -X PUT "${BASE_URL}/ffs-training-sessions/${SESSION_ID}/resolutions/${RESOLUTION_ID}" \
  -H "User-Id: ${USER_ID}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "completed",
    "follow_up_notes": "All members now composting successfully"
  }' | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 14. View full session with nested data
# ─────────────────────────────────────
echo ""
echo ">>> TEST 14: GET /ffs-training-sessions/${SESSION_ID} (Full nested view)"
curl -s -X GET "${BASE_URL}/ffs-training-sessions/${SESSION_ID}" \
  -H "User-Id: ${USER_ID}" \
  -H "Accept: application/json" | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 15. Get stats again (should show data now)
# ─────────────────────────────────────
echo ""
echo ">>> TEST 15: GET /ffs-training-sessions/stats (With Data)"
curl -s -X GET "${BASE_URL}/ffs-training-sessions/stats" \
  -H "User-Id: ${USER_ID}" \
  -H "Accept: application/json" | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 16. Filter sessions
# ─────────────────────────────────────
echo ""
echo ">>> TEST 16: GET /ffs-training-sessions?status=ongoing (Filter)"
curl -s -X GET "${BASE_URL}/ffs-training-sessions?status=ongoing" \
  -H "User-Id: ${USER_ID}" \
  -H "Accept: application/json" | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 17. Validation test - missing required fields
# ─────────────────────────────────────
echo ""
echo ">>> TEST 17: POST /ffs-training-sessions (Validation Error)"
curl -s -X POST "${BASE_URL}/ffs-training-sessions" \
  -H "User-Id: ${USER_ID}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"title": "Missing group_id"}' | python3 -m json.tool 2>/dev/null

# ─────────────────────────────────────
# 18. 404 test
# ─────────────────────────────────────
echo ""
echo ">>> TEST 18: GET /ffs-training-sessions/99999 (Not Found)"
curl -s -X GET "${BASE_URL}/ffs-training-sessions/99999" \
  -H "User-Id: ${USER_ID}" \
  -H "Accept: application/json" | python3 -m json.tool 2>/dev/null

echo ""
echo "============================================"
echo "  ALL TESTS COMPLETED"
echo "============================================"
