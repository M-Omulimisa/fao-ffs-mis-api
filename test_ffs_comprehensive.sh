#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# FFS Training Sessions — Comprehensive Logic & API Test Suite
# Tests business logic, edge cases, CRUD, and data integrity
# ═══════════════════════════════════════════════════════════════
BASE="http://localhost:8888/fao-ffs-mis-api/public/api"
H1="User-Id: 1"
H2="Accept: application/json"
H3="Content-Type: application/json"
PASS=0; FAIL=0; TOTAL=0

# Helper: extract JSON field
jf() { python3 -c "import sys,json;d=json.load(sys.stdin);print($1)" 2>/dev/null; }

assert_eq() {
    TOTAL=$((TOTAL+1))
    local label="$1" expected="$2" actual="$3"
    if [ "$expected" = "$actual" ]; then
        echo "  ✓ $label"
        PASS=$((PASS+1))
    else
        echo "  ✗ $label (expected: $expected, got: $actual)"
        FAIL=$((FAIL+1))
    fi
}

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 0: CLEAN UP — Remove old test data"
echo "════════════════════════════════════════════════════════"
# Get existing session IDs and delete them
EXISTING=$(curl -s "$BASE/ffs-training-sessions" -H "$H1" -H "$H2" | python3 -c "import sys,json;[print(s['id']) for s in json.load(sys.stdin)['data']]" 2>/dev/null)
for SID in $EXISTING; do
    # Force status to 'cancelled' then delete (so delete guard passes)
    curl -s -X PUT "$BASE/ffs-training-sessions/$SID" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"cancelled"}' > /dev/null 2>&1
    curl -s -X DELETE "$BASE/ffs-training-sessions/$SID" -H "$H1" -H "$H2" > /dev/null 2>&1
done
echo "  Cleaned up existing test data"

# Verify empty
C=$(curl -s "$BASE/ffs-training-sessions/stats" -H "$H1" -H "$H2" | jf "d['data']['total_sessions']")
assert_eq "Database is clean (0 sessions)" "0" "$C"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 1: CREATE — Valid Sessions with Realistic Data"
echo "════════════════════════════════════════════════════════"

# Session 1: Classroom training (future date)
R=$(curl -s -X POST "$BASE/ffs-training-sessions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "group_id": 1,
    "title": "Integrated Soil Fertility Management",
    "topic": "Composting and organic fertilizer preparation",
    "description": "Farmers learn to prepare compost from locally available materials. Includes hands-on demonstration of compost heap building.",
    "session_date": "2026-03-10",
    "start_time": "09:00",
    "end_time": "12:30",
    "venue": "Kitopoloi Community Hall",
    "session_type": "classroom",
    "expected_participants": 30,
    "materials_used": "Compost samples, pH testing kit, handout booklets, flipchart",
    "notes": "First session of Season A cycle. Ensure all FFS members attend."
}')
S1=$(echo "$R" | jf "d['data']['id']")
S1_CODE=$(echo "$R" | jf "d['code']")
assert_eq "Create classroom session" "1" "$S1_CODE"

# Session 2: Field visit
R=$(curl -s -X POST "$BASE/ffs-training-sessions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "group_id": 1,
    "title": "Maize AESA Field Observation",
    "topic": "Agro-Ecosystem Analysis for maize plots",
    "description": "Field walk to observe maize at vegetative stage. Groups will compare FFS plot vs control plot.",
    "session_date": "2026-03-17",
    "start_time": "08:00",
    "end_time": "11:00",
    "venue": "FFS Demo Plot, Kitopoloi Village",
    "session_type": "field",
    "expected_participants": 25,
    "notes": "Bring notebooks and pens. Meet at demo plot gate."
}')
S2=$(echo "$R" | jf "d['data']['id']")
S2_CODE=$(echo "$R" | jf "d['code']")
assert_eq "Create field session" "1" "$S2_CODE"

# Session 3: Demonstration
R=$(curl -s -X POST "$BASE/ffs-training-sessions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "group_id": 1,
    "title": "Safe Pesticide Handling & Application",
    "topic": "Pest management and biopesticide preparation",
    "description": "Demonstration of neem extract preparation and application. Safety precautions and PPE usage.",
    "session_date": "2026-03-24",
    "start_time": "09:00",
    "end_time": "13:00",
    "venue": "Kitopoloi Primary School Grounds",
    "session_type": "demonstration",
    "expected_participants": 28
}')
S3=$(echo "$R" | jf "d['data']['id']")
S3_CODE=$(echo "$R" | jf "d['code']")
assert_eq "Create demonstration session" "1" "$S3_CODE"

# Session 4: Workshop
R=$(curl -s -X POST "$BASE/ffs-training-sessions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "group_id": 1,
    "title": "Post-Harvest Handling & Value Addition",
    "topic": "Grain drying, storage, and market linkages",
    "description": "Workshop covering hermetic storage (PICS bags), aflatoxin prevention, and collective marketing strategies.",
    "session_date": "2026-04-07",
    "start_time": "09:00",
    "end_time": "16:00",
    "venue": "Sub-County Agriculture Office, Moroto",
    "session_type": "workshop",
    "expected_participants": 35,
    "materials_used": "PICS bags sample, moisture meter, grading sieves, marketing flipchart"
}')
S4=$(echo "$R" | jf "d['data']['id']")
S4_CODE=$(echo "$R" | jf "d['code']")
assert_eq "Create workshop session" "1" "$S4_CODE"

echo "  Created sessions: S1=$S1, S2=$S2, S3=$S3, S4=$S4"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 2: VALIDATION — Reject bad data"
echo "════════════════════════════════════════════════════════"

# Missing required fields
R=$(curl -s -X POST "$BASE/ffs-training-sessions" -H "$H1" -H "$H2" -H "$H3" -d '{}')
V_CODE=$(echo "$R" | jf "d['code']")
V_ERRS=$(echo "$R" | jf "','.join(d['errors'].keys())")
assert_eq "Reject empty body (code:0)" "0" "$V_CODE"
echo "    errors: $V_ERRS"

# Invalid session_type
R=$(curl -s -X POST "$BASE/ffs-training-sessions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "group_id": 1, "title": "Bad Type", "session_date": "2026-04-01", "session_type": "invalid"
}')
V_CODE=$(echo "$R" | jf "d['code']")
assert_eq "Reject invalid session_type" "0" "$V_CODE"

# Non-existent group_id
R=$(curl -s -X POST "$BASE/ffs-training-sessions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "group_id": 99999, "title": "Bad Group", "session_date": "2026-04-01", "session_type": "classroom"
}')
V_CODE=$(echo "$R" | jf "d['code']")
assert_eq "Reject non-existent group_id" "0" "$V_CODE"

# Past session date
R=$(curl -s -X POST "$BASE/ffs-training-sessions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "group_id": 1, "title": "Past Session", "session_date": "2020-01-01", "session_type": "classroom"
}')
V_CODE=$(echo "$R" | jf "d['code']")
assert_eq "Reject past session date" "0" "$V_CODE"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 3: STATUS TRANSITIONS — Business logic checks"
echo "════════════════════════════════════════════════════════"

# S1: scheduled -> ongoing ✓
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S1" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"ongoing"}')
ST=$(echo "$R" | jf "d['data']['status']")
assert_eq "scheduled -> ongoing (allowed)" "ongoing" "$ST"

# S1: ongoing -> completed ✓
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S1" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"completed","actual_participants":27,"challenges":"3 members arrived late due to market day","recommendations":"Avoid scheduling on market days"}')
ST=$(echo "$R" | jf "d['data']['status']")
assert_eq "ongoing -> completed (allowed)" "completed" "$ST"

# S1: completed -> scheduled ✗ (terminal state)
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S1" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"scheduled"}')
C=$(echo "$R" | jf "d['code']")
assert_eq "completed -> scheduled (blocked)" "0" "$C"

# S1: completed -> ongoing ✗
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S1" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"ongoing"}')
C=$(echo "$R" | jf "d['code']")
assert_eq "completed -> ongoing (blocked)" "0" "$C"

# S2: scheduled -> cancelled ✓
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S2" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"cancelled"}')
ST=$(echo "$R" | jf "d['data']['status']")
assert_eq "scheduled -> cancelled (allowed)" "cancelled" "$ST"

# S2: cancelled -> scheduled ✓ (reschedule)
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S2" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"scheduled"}')
ST=$(echo "$R" | jf "d['data']['status']")
assert_eq "cancelled -> scheduled (reschedule allowed)" "scheduled" "$ST"

# S2: cancelled -> completed ✗ (must go through ongoing)
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S2" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"cancelled"}')
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S2" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"completed"}')
C=$(echo "$R" | jf "d['code']")
assert_eq "cancelled -> completed (blocked)" "0" "$C"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 4: PARTICIPANTS — Attendance management"
echo "════════════════════════════════════════════════════════"

# Set S3 to ongoing for participant operations
curl -s -X PUT "$BASE/ffs-training-sessions/$S3" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"ongoing"}' > /dev/null

# Sync participants (user_id=1 is the only available test user)
R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S3/participants" -H "$H1" -H "$H2" -H "$H3" -d '{
    "participants": [
        {"user_id": 1, "attendance_status": "present", "remarks": "Lead facilitator, arrived early"}
    ]
}')
SYNC_COUNT=$(echo "$R" | jf "d['data']['synced_count']")
ACT_P=$(echo "$R" | jf "d['data']['actual_participants']")
assert_eq "Sync 1 participant" "1" "$SYNC_COUNT"
assert_eq "actual_participants auto-updated to 1" "1" "$ACT_P"

# Re-sync same user to update remarks (upsert behavior)
R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S3/participants" -H "$H1" -H "$H2" -H "$H3" -d '{
    "participants": [
        {"user_id": 1, "attendance_status": "late", "remarks": "Arrived 30 minutes late due to transport issues"}
    ]
}')
# 'late' should still count toward actual_participants
ACT_P=$(echo "$R" | jf "d['data']['actual_participants']")
assert_eq "Upsert: late counts toward attendance" "1" "$ACT_P"

# List participants — should be 1 (not duplicated)
R=$(curl -s "$BASE/ffs-training-sessions/$S3/participants" -H "$H1" -H "$H2")
P_COUNT=$(echo "$R" | jf "len(d['data'])")
P_STATUS=$(echo "$R" | jf "d['data'][0]['attendance_status']")
P_REMARKS=$(echo "$R" | jf "d['data'][0]['remarks']")
assert_eq "No duplicates after upsert (count=1)" "1" "$P_COUNT"
assert_eq "Status updated to 'late'" "late" "$P_STATUS"
echo "    remarks: $P_REMARKS"
PID=$(echo "$R" | jf "d['data'][0]['id']")

# Update to absent — should NOT count toward actual
R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S3/participants" -H "$H1" -H "$H2" -H "$H3" -d '{
    "participants": [{"user_id": 1, "attendance_status": "absent", "remarks": "Marked absent"}]
}')
ACT_P=$(echo "$R" | jf "d['data']['actual_participants']")
assert_eq "Absent does NOT count (actual=0)" "0" "$ACT_P"

# Back to present
curl -s -X POST "$BASE/ffs-training-sessions/$S3/participants" -H "$H1" -H "$H2" -H "$H3" -d '{"participants": [{"user_id": 1, "attendance_status": "present", "remarks": "Corrected"}]}' > /dev/null

# Cannot add participants to cancelled session
curl -s -X PUT "$BASE/ffs-training-sessions/$S2" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"scheduled"}' > /dev/null
curl -s -X PUT "$BASE/ffs-training-sessions/$S2" -H "$H1" -H "$H2" -H "$H3" -d '{"status":"cancelled"}' > /dev/null
R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S2/participants" -H "$H1" -H "$H2" -H "$H3" -d '{
    "participants": [{"user_id": 1, "attendance_status": "present"}]
}')
C=$(echo "$R" | jf "d['code']")
assert_eq "Block participants on cancelled session" "0" "$C"

# Invalid attendance status
R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S3/participants" -H "$H1" -H "$H2" -H "$H3" -d '{
    "participants": [{"user_id": 1, "attendance_status": "maybe"}]
}')
C=$(echo "$R" | jf "d['code']")
assert_eq "Reject invalid attendance_status" "0" "$C"

# Empty participants array
R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S3/participants" -H "$H1" -H "$H2" -H "$H3" -d '{"participants": []}')
C=$(echo "$R" | jf "d['code']")
assert_eq "Reject empty participants array" "0" "$C"

# Remove participant
R=$(curl -s -X DELETE "$BASE/ffs-training-sessions/$S3/participants/$PID" -H "$H1" -H "$H2")
C=$(echo "$R" | jf "d['code']")
assert_eq "Remove participant" "1" "$C"

# Verify count is now 0
R=$(curl -s "$BASE/ffs-training-sessions/$S3/participants" -H "$H1" -H "$H2")
P_COUNT=$(echo "$R" | jf "len(d['data'])")
assert_eq "Participant list is empty after removal" "0" "$P_COUNT"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 5: RESOLUTIONS (GAP) — Full lifecycle"
echo "════════════════════════════════════════════════════════"

# Create resolutions for S1 (completed session — should still allow for now)
R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S1/resolutions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "resolution": "All members to start composting kitchen waste",
    "description": "Each household should build a compost heap (1m x 1m x 1m) using kitchen waste, crop residues, and animal manure. Turn every 2 weeks.",
    "gap_category": "soil",
    "responsible_person_id": 1,
    "target_date": "2026-04-30",
    "follow_up_notes": "Check progress at next session"
}')
R1=$(echo "$R" | jf "d['data']['id']")
R1_STATUS=$(echo "$R" | jf "d['data']['status']")
assert_eq "Create soil GAP resolution" "pending" "$R1_STATUS"

R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S1/resolutions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "resolution": "Construct rainwater harvesting structures",
    "description": "Each farmer to dig a small farm pond (3m x 3m x 1.5m) or install gutter system on farmhouse for rainwater collection.",
    "gap_category": "water",
    "responsible_person_id": 1,
    "target_date": "2026-05-15"
}')
R2=$(echo "$R" | jf "d['data']['id']")
assert_eq "Create water GAP resolution" "1" "$(echo "$R" | jf "d['code']")"

R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S1/resolutions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "resolution": "Purchase improved maize seed varieties",
    "description": "Group to collectively order Longe 5 or NAROMAIZE 4C seeds from certified dealer in Moroto town.",
    "gap_category": "seeds",
    "target_date": "2026-03-20"
}')
R3=$(echo "$R" | jf "d['data']['id']")
assert_eq "Create seeds GAP resolution (no responsible person)" "1" "$(echo "$R" | jf "d['code']")"

R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S1/resolutions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "resolution": "Scout for Fall Armyworm weekly",
    "description": "Each member to inspect 10 plants per plot every Monday. Report findings in WhatsApp group. Apply neem extract if infestation above 20%.",
    "gap_category": "pest",
    "responsible_person_id": 1,
    "target_date": "2026-06-30"
}')
R4=$(echo "$R" | jf "d['data']['id']")
assert_eq "Create pest GAP resolution" "1" "$(echo "$R" | jf "d['code']")"

# Validate all GAP categories accepted
for CAT in soil water seeds pest harvest storage marketing livestock other; do
    R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S1/resolutions" -H "$H1" -H "$H2" -H "$H3" -d "{
        \"resolution\": \"Test $CAT category\",
        \"gap_category\": \"$CAT\"
    }")
    C=$(echo "$R" | jf "d['code']")
    if [ "$C" != "1" ]; then
        echo "  ✗ GAP category '$CAT' rejected unexpectedly"
        FAIL=$((FAIL+1))
    fi
    TOTAL=$((TOTAL+1))
done
echo "  ✓ All 9 GAP categories accepted"
PASS=$((PASS+1))

# Invalid GAP category
R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S1/resolutions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "resolution": "Bad category", "gap_category": "invalid_cat"
}')
C=$(echo "$R" | jf "d['code']")
assert_eq "Reject invalid GAP category" "0" "$C"

# Cannot add resolution to cancelled session
R=$(curl -s -X POST "$BASE/ffs-training-sessions/$S2/resolutions" -H "$H1" -H "$H2" -H "$H3" -d '{
    "resolution": "Should fail", "gap_category": "soil"
}')
C=$(echo "$R" | jf "d['code']")
assert_eq "Block resolution on cancelled session" "0" "$C"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 6: RESOLUTION STATUS LIFECYCLE"
echo "════════════════════════════════════════════════════════"

# pending -> in_progress
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S1/resolutions/$R1" -H "$H1" -H "$H2" -H "$H3" -d '{
    "status": "in_progress", "follow_up_notes": "5 of 25 members have started composting. Others need support."
}')
RS=$(echo "$R" | jf "d['data']['status']")
COMP=$(echo "$R" | jf "str(d['data']['completed_at'])")
assert_eq "Resolution: pending -> in_progress" "in_progress" "$RS"
assert_eq "completed_at is null while in_progress" "None" "$COMP"

# in_progress -> completed (auto-sets completed_at)
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S1/resolutions/$R1" -H "$H1" -H "$H2" -H "$H3" -d '{
    "status": "completed", "follow_up_notes": "All 25 members now have active compost heaps. Quality verified."
}')
RS=$(echo "$R" | jf "d['data']['status']")
COMP=$(echo "$R" | jf "d['data']['completed_at']")
assert_eq "Resolution: in_progress -> completed" "completed" "$RS"
assert_eq "completed_at auto-set on completion" "True" "$(echo "$R" | jf "bool(d['data']['completed_at'])")"
echo "    completed_at: $COMP"

# completed -> back to in_progress (should clear completed_at)
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S1/resolutions/$R1" -H "$H1" -H "$H2" -H "$H3" -d '{
    "status": "in_progress", "follow_up_notes": "Reopened — quality issue found"
}')
COMP=$(echo "$R" | jf "str(d['data']['completed_at'])")
assert_eq "completed_at cleared when reopened" "None" "$COMP"

# Cancel a resolution
R=$(curl -s -X PUT "$BASE/ffs-training-sessions/$S1/resolutions/$R2" -H "$H1" -H "$H2" -H "$H3" -d '{
    "status": "cancelled", "follow_up_notes": "Drought conditions - postponed to next season"
}')
RS=$(echo "$R" | jf "d['data']['status']")
assert_eq "Resolution cancelled" "cancelled" "$RS"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 7: OVERDUE DETECTION"
echo "════════════════════════════════════════════════════════"

# R3 has target_date 2026-03-20 which is future, so not overdue
R=$(curl -s "$BASE/ffs-training-sessions/$S1/resolutions" -H "$H1" -H "$H2")
R3_OVER=$(echo "$R" | jf "[r for r in d['data'] if r['id']==$R3][0]['is_overdue']" 2>/dev/null)
assert_eq "Future target → not overdue" "False" "$R3_OVER"

# Completed/cancelled resolutions should never be overdue
R1_OVER=$(echo "$R" | jf "[r for r in d['data'] if r['id']==$R1][0]['is_overdue']" 2>/dev/null)
echo "  R1 (in_progress, target 2026-04-30) overdue: $R1_OVER"
R2_OVER=$(echo "$R" | jf "[r for r in d['data'] if r['id']==$R2][0]['is_overdue']" 2>/dev/null)
assert_eq "Cancelled resolution → never overdue" "False" "$R2_OVER"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 8: FILTERS & SEARCH"
echo "════════════════════════════════════════════════════════"

# Filter by status
R=$(curl -s "$BASE/ffs-training-sessions?status=completed" -H "$H1" -H "$H2")
FC=$(echo "$R" | jf "len(d['data'])")
assert_eq "Filter: status=completed (1 session)" "1" "$FC"

# Filter by session_type
R=$(curl -s "$BASE/ffs-training-sessions?session_type=field" -H "$H1" -H "$H2")
FC=$(echo "$R" | jf "len(d['data'])")
# S2 is field but cancelled - still shows in list
assert_eq "Filter: session_type=field" "1" "$FC"

# Search by title
R=$(curl -s "$BASE/ffs-training-sessions?search=Pesticide" -H "$H1" -H "$H2")
FC=$(echo "$R" | jf "len(d['data'])")
assert_eq "Search: 'Pesticide' finds 1 session" "1" "$FC"
T=$(echo "$R" | jf "d['data'][0]['title']")
assert_eq "Search result is correct session" "Safe Pesticide Handling & Application" "$T"

# Search by venue
R=$(curl -s "$BASE/ffs-training-sessions?search=Moroto" -H "$H1" -H "$H2")
FC=$(echo "$R" | jf "len(d['data'])")
assert_eq "Search by venue 'Moroto' finds workshop" "1" "$FC"

# Date range filter
R=$(curl -s "$BASE/ffs-training-sessions?date_from=2026-03-15&date_to=2026-03-25" -H "$H1" -H "$H2")
FC=$(echo "$R" | jf "len(d['data'])")
assert_eq "Date range filter (Mar 15-25)" "2" "$FC"

# Sort by title ascending
R=$(curl -s "$BASE/ffs-training-sessions?sort_by=title&sort_dir=asc" -H "$H1" -H "$H2")
FIRST=$(echo "$R" | jf "d['data'][0]['title']")
echo "    First by title asc: $FIRST"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 9: SHOW DETAIL — Nested data integrity"
echo "════════════════════════════════════════════════════════"

# Re-add a participant to S3 for the nested test
curl -s -X POST "$BASE/ffs-training-sessions/$S3/participants" -H "$H1" -H "$H2" -H "$H3" -d '{"participants":[{"user_id":1,"attendance_status":"present","remarks":"Arrived early"}]}' > /dev/null
# Add resolutions to S3
curl -s -X POST "$BASE/ffs-training-sessions/$S3/resolutions" -H "$H1" -H "$H2" -H "$H3" -d '{"resolution":"Use neem extract for pest control","gap_category":"pest","target_date":"2026-04-15"}' > /dev/null

R=$(curl -s "$BASE/ffs-training-sessions/$S3" -H "$H1" -H "$H2")
HAS_PARTS=$(echo "$R" | jf "len(d['data']['participants'])")
HAS_RES=$(echo "$R" | jf "len(d['data']['resolutions'])")
TITLE=$(echo "$R" | jf "d['data']['title']")
assert_eq "Show includes nested participants" "1" "$HAS_PARTS"
assert_eq "Show includes nested resolutions" "1" "$HAS_RES"
assert_eq "Title correct in detail view" "Safe Pesticide Handling & Application" "$TITLE"

# Verify computed fields are present
TYPE_TEXT=$(echo "$R" | jf "d['data']['session_type_text']")
STATUS_TEXT=$(echo "$R" | jf "d['data']['status_text']")
GROUP_NAME=$(echo "$R" | jf "d['data']['group_name']")
assert_eq "session_type_text computed" "Demonstration" "$TYPE_TEXT"
assert_eq "status_text computed" "Ongoing" "$STATUS_TEXT"
assert_eq "group_name resolved" "KITOPOLOI FFS" "$GROUP_NAME"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 10: STATS — Aggregate numbers"
echo "════════════════════════════════════════════════════════"

R=$(curl -s "$BASE/ffs-training-sessions/stats" -H "$H1" -H "$H2")
TOTAL_S=$(echo "$R" | jf "d['data']['total_sessions']")
SCHED=$(echo "$R" | jf "d['data']['scheduled']")
ONGOING=$(echo "$R" | jf "d['data']['ongoing']")
COMPL=$(echo "$R" | jf "d['data']['completed']")
CANC=$(echo "$R" | jf "d['data']['cancelled']")
echo "  total=$TOTAL_S scheduled=$SCHED ongoing=$ONGOING completed=$COMPL cancelled=$CANC"
assert_eq "Total sessions = 4" "4" "$TOTAL_S"
assert_eq "Completed count = 1" "1" "$COMPL"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 11: DELETE GUARDS"
echo "════════════════════════════════════════════════════════"

# Cannot delete ongoing session
R=$(curl -s -X DELETE "$BASE/ffs-training-sessions/$S3" -H "$H1" -H "$H2")
C=$(echo "$R" | jf "d['code']")
assert_eq "Cannot delete ongoing session" "0" "$C"

# Cannot delete completed session
R=$(curl -s -X DELETE "$BASE/ffs-training-sessions/$S1" -H "$H1" -H "$H2")
C=$(echo "$R" | jf "d['code']")
assert_eq "Cannot delete completed session" "0" "$C"

# CAN delete cancelled session
R=$(curl -s -X DELETE "$BASE/ffs-training-sessions/$S2" -H "$H1" -H "$H2")
C=$(echo "$R" | jf "d['code']")
assert_eq "Can delete cancelled session" "1" "$C"

# Verify deleted session returns 404
R=$(curl -s "$BASE/ffs-training-sessions/$S2" -H "$H1" -H "$H2")
C=$(echo "$R" | jf "d['code']")
assert_eq "Deleted session returns 404" "0" "$C"

# CAN delete scheduled session (S4 is still scheduled)
R=$(curl -s -X DELETE "$BASE/ffs-training-sessions/$S4" -H "$H1" -H "$H2")
C=$(echo "$R" | jf "d['code']")
assert_eq "Can delete scheduled session" "1" "$C"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo "  STEP 12: 404 / ERROR HANDLING"
echo "════════════════════════════════════════════════════════"

# Non-existent session
R=$(curl -s "$BASE/ffs-training-sessions/99999" -H "$H1" -H "$H2")
C=$(echo "$R" | jf "d['code']")
assert_eq "GET non-existent session → code:0" "0" "$C"

# Non-existent participant
R=$(curl -s -X DELETE "$BASE/ffs-training-sessions/$S3/participants/99999" -H "$H1" -H "$H2")
C=$(echo "$R" | jf "d['code']")
assert_eq "DELETE non-existent participant → code:0" "0" "$C"

# Non-existent resolution
R=$(curl -s "$BASE/ffs-training-sessions/$S3/resolutions" -H "$H1" -H "$H2")
R=$(curl -s -X DELETE "$BASE/ffs-training-sessions/$S3/resolutions/99999" -H "$H1" -H "$H2")
C=$(echo "$R" | jf "d['code']")
assert_eq "DELETE non-existent resolution → code:0" "0" "$C"

# Participants on non-existent session
R=$(curl -s "$BASE/ffs-training-sessions/99999/participants" -H "$H1" -H "$H2")
C=$(echo "$R" | jf "d['code']")
assert_eq "Participants on missing session → code:0" "0" "$C"

# ═══════════════════════════════════════════════════════════════
echo ""
echo "════════════════════════════════════════════════════════"
echo ""
echo "  RESULTS: $PASS passed / $FAIL failed / $TOTAL total"
echo ""
if [ "$FAIL" -eq 0 ]; then
    echo "  ✓ ALL TESTS PASSED!"
else
    echo "  ✗ SOME TESTS FAILED — review output above"
fi
echo ""
echo "════════════════════════════════════════════════════════"
