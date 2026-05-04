# Meeting Duplication Incident Report

## Description
Facilitators reported duplicate VSLA meetings and partial/inconsistent financial totals after saving meeting data. Incidents are reported for KOMOJOJ FARMERS SAVINGS GROUP and ETATA OYARA.

## SN
- INC-VSLA-MEETING-DUP-2026-05-04

## Steps to Reproduce (Reported)
1. Facilitator starts a meeting and enters basic info.
2. Facilitator spends time reviewing savings, then taps Save.
3. App confirms success and exits flow before welfare/action plan sections are completed.
4. Later, additional meeting records appear for the same date/group with different totals.

## Observed Behaviour
- Duplicate meetings created for same group/date.
- Inconsistent totals between duplicates:
  - One record contains only cash savings.
  - Another includes cash savings + welfare.
- For ETATA OYARA, one date reportedly duplicated multiple times with varying totals.

## Expected Behaviour
- One meeting per intended event save action.
- No duplicate backend records for same group/date from retries/delays.
- Meeting data should only finalize once all required sections complete (or clearly save as draft/update same record idempotently).

## Developer Remarks
- Investigation completed with DB-backed evidence and verified fixes.
- Prevention was implemented first (backend + mobile), then historical duplicates were cleaned.

## Review
- Completed.

## Database Evidence

### Snapshot Before Cleanup

Affected groups and duplicate dates found:

- Group 445 (ETATA OYARA)
  - 2026-04-08 had 3 meeting rows: IDs 70, 71, 72
  - 2026-04-22 had 2 meeting rows: IDs 80, 81
- Group 643 (KOMOJOJ)
  - 2026-04-06 had 2 meeting rows: IDs 73, 74

Key forensic observations:

- Duplicate rows shared same group/date but had different meeting numbers and local IDs.
- Account transactions were posted for each duplicate meeting (e.g., repeated totals for same date).
- Richer copies were identifiable by social fund/action-plan presence:
  - Kept 72 over 70/71 (social fund + action plan present)
  - Kept 74 over 73 (social fund + action plans present)
  - Kept 81 over 80 (latest record for same date)

## Root Cause Analysis

### Root Cause 1: Backend Idempotency Gap

- `VslaMeetingController::submit` blocked duplicates only by `local_id`.
- A second submit with a new `local_id` but same `group_id + meeting_date` was accepted as a new meeting.

### Root Cause 2: Premature Submission UX Path

- Meeting hub provided a direct bottom “Submit Meeting Data” action, enabling early submission before full workflow review.
- This made it easy to submit partial data and later create another same-date meeting to continue work.

### Root Cause 3: Local Create Flow Messaging

- Local create logic could return no new record while UI still displayed success messaging.
- This increased facilitator confusion around whether they were continuing an existing meeting or creating a new one.

## Fixes Implemented

### Backend (API)

- Added same-day duplicate guard in submit flow:
  - Rejects new submissions when a meeting already exists for the same `group_id + meeting_date`.
  - Returns 409 with existing meeting identifiers for clear client handling.
- Added server-authoritative totals normalization from payload arrays before persistence:
  - members present/absent from attendance data
  - savings/welfare/fines/social fund from transaction payloads
  - loans disbursed/repaid from loan payloads
  - shares sold/share value from share purchases

### Mobile App

- Local meeting creation now reuses existing same-day/ongoing records instead of returning null.
- Basic info save now checks create result explicitly; no false “created successfully” path.
- Hub bottom action changed from direct submit to “Review Summary Before Submit” to force review path.
- Sync service now treats backend 409 duplicate response as idempotent success and clears local duplicate copy to avoid retry loops.

## Data Cleanup Actions

### Canonical Meetings Kept

- Group 445: kept IDs 69, 72, 79, 81
- Group 643: kept ID 74

### Duplicate Meetings Removed

- Deleted IDs: 70, 71, 73, 80

### Cleanup Method

- Executed transactional force-delete through Laravel model cleanup path to cascade related records.
- Post-cleanup checks confirmed zero remaining rows referencing deleted meeting IDs in:
  - `account_transactions`
  - `vsla_meeting_attendance`
  - `social_fund_transactions`
  - `vsla_action_plans`
  - `vsla_loans`

## Test Cases & Results

1. API syntax check
  - Command: `php -l app/Http/Controllers/Api/VslaMeetingController.php`
  - Result: pass (no syntax errors)

2. Mobile static analysis on touched files
  - Command: `dart analyze` on edited VSLA meeting files
  - Result: pass for errors (warnings only; no blocking issues)

3. Database duplicate verification after cleanup
  - Re-queried meetings for groups 445 and 643
  - Result: no duplicate rows remain for same group/date

4. Database orphan check after cleanup
  - Queried dependent tables for deleted meeting IDs [70,71,73,80]
  - Result: zero residual linked rows found
