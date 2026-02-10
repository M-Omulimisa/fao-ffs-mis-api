# Training Session System — Comprehensive Analysis

> **Generated:** 2026-02-10  
> **Scope:** API (`fao-ffs-mis-api`) + Mobile (`fao-ffs-mis-mobo`)

---

## TABLE OF CONTENTS

1. [Current System — Database](#1-current-system--database)
2. [Current System — API Endpoints](#2-current-system--api-endpoints)
3. [Current System — Mobile App](#3-current-system--mobile-app)
4. [Required Changes Overview](#4-required-changes-overview)
5. [Database Changes (Migrations)](#5-database-changes-migrations)
6. [API Changes](#6-api-changes)
7. [Mobile App Changes](#7-mobile-app-changes)
8. [Implementation Plan & Complexity](#8-implementation-plan--complexity)
9. [Risks & Considerations](#9-risks--considerations)

---

## 1. Current System — Database

### 1.1 `ffs_training_sessions` table

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | bigint (PK) | NO | auto | — |
| `group_id` | bigint unsigned | YES | NULL | FK → `ffs_groups.id` (**single** group) |
| `facilitator_id` | bigint unsigned | YES | NULL | FK → `users.id` |
| `title` | varchar(255) | NO | — | Required |
| `description` | text | YES | NULL | — |
| `topic` | varchar(255) | YES | NULL | — |
| `session_date` | date | NO | — | Required |
| `start_time` | time | YES | NULL | — |
| `end_time` | time | YES | NULL | — |
| `venue` | varchar(255) | YES | NULL | — |
| `session_type` | varchar | NO | `'classroom'` | classroom / field / demonstration / workshop |
| `status` | varchar | NO | `'scheduled'` | scheduled / ongoing / completed / cancelled |
| `expected_participants` | int | NO | 0 | Manual count |
| `actual_participants` | int | NO | 0 | Auto-calculated from participants table |
| `materials_used` | text | YES | NULL | — |
| `notes` | text | YES | NULL | — |
| `challenges` | text | YES | NULL | — |
| `recommendations` | text | YES | NULL | — |
| `photo` | varchar | YES | NULL | Single photo path |
| `created_by_id` | bigint unsigned | YES | NULL | FK → `users.id` |
| `created_at` | timestamp | YES | — | — |
| `updated_at` | timestamp | YES | — | — |
| `deleted_at` | timestamp | YES | NULL | Soft delete |

**Indexes:** `group_id`, `facilitator_id`, `session_date`, `status`

**Status transitions (enforced in model):**
- `scheduled` → `ongoing`, `cancelled`
- `ongoing` → `completed`, `cancelled`
- `completed` → *(terminal)*
- `cancelled` → `scheduled` (reschedule)

### 1.2 `ffs_session_participants` table

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | bigint (PK) | NO | auto | — |
| `session_id` | bigint unsigned | NO | — | FK → `ffs_training_sessions.id` |
| `user_id` | bigint unsigned | NO | — | FK → `users.id` |
| `attendance_status` | varchar | NO | `'present'` | present / absent / excused / late |
| `remarks` | text | YES | NULL | — |
| `created_at` | timestamp | YES | — | — |
| `updated_at` | timestamp | YES | — | — |

**Indexes:** `session_id`, `user_id`  
**Unique constraint:** `(session_id, user_id)`

> **Note:** There is NO `'pending'` attendance status currently. Participants are only added manually via the sync endpoint, and the default status is `'present'`.

### 1.3 `ffs_session_resolutions` table (GAP)

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | bigint (PK) | NO | auto | — |
| `session_id` | bigint unsigned | NO | — | FK → `ffs_training_sessions.id` |
| `resolution` | varchar(255) | NO | — | Title/summary |
| `description` | text | YES | NULL | Details |
| `gap_category` | varchar | YES | NULL | soil / water / seeds / pest / harvest / storage / marketing / livestock / other |
| `responsible_person_id` | bigint unsigned | YES | NULL | FK → `users.id` |
| `target_date` | date | YES | NULL | — |
| `status` | varchar | NO | `'pending'` | pending / in_progress / completed / cancelled |
| `follow_up_notes` | text | YES | NULL | — |
| `completed_at` | datetime | YES | NULL | Auto-set when status → completed |
| `created_by_id` | bigint unsigned | YES | NULL | FK → `users.id` |
| `created_at` | timestamp | YES | — | — |
| `updated_at` | timestamp | YES | — | — |
| `deleted_at` | timestamp | YES | NULL | Soft delete |

**Indexes:** `session_id`, `status`, `gap_category`

### 1.4 User–Group Relationship

- `users.group_id` → `ffs_groups.id` (belongs-to, single group per user)
- `User::group()` returns `belongsTo(FfsGroup::class, 'group_id')`
- `FfsGroup` has a commented-out `members()` relationship (`hasMany(User::class, 'group_id')` not active)
- The session's role-based filtering uses `user->group_id` to match `session->group_id`

---

## 2. Current System — API Endpoints

**Route prefix:** `api/ffs-training-sessions`  
**Middleware:** `EnsureTokenIsValid`  
**Controller:** `App\Http\Controllers\Api\FfsTrainingSessionController` (816 lines)

### 2.1 Session CRUD

| Method | URI | Controller Method | Description |
|--------|-----|-------------------|-------------|
| GET | `/` | `index()` | List sessions (role-filtered, with search/filter/sort) |
| GET | `/stats` | `stats()` | Aggregate stats (total, by status, participant counts) |
| GET | `/{id}` | `show()` | Single session with participants + resolutions eager-loaded |
| POST | `/` | `store()` | Create session. Requires: `group_id`, `title`, `session_date`, `session_type` |
| PUT | `/{id}` | `update()` | Update session fields. Validates status transitions. |
| DELETE | `/{id}` | `destroy()` | Soft-delete. Only allowed for scheduled/cancelled sessions. Cascades to participants & resolutions. |

**Key behaviors in `store()`:**
- Accepts a **single** `group_id` (required, validated against `ffs_groups`)
- `facilitator_id` defaults to the authenticated user
- `session_date` must be `after_or_equal:today`
- **Does NOT** auto-create participant records
- **Does NOT** calculate `expected_participants` from group membership

**Key behaviors in `index()`:**
- Non-admin users see only: sessions for their `group_id`, sessions they facilitate, or sessions they created
- Filters: `group_id`, `facilitator_id`, `status`, `session_type`, `date_from`, `date_to`, `search`
- Returns all (no pagination)

**Key behaviors in `show()`:**
- Eager-loads: `group`, `facilitator`, `createdBy`, `participants.user`, `resolutions.responsiblePerson`
- Permission check: same group, facilitator, or creator

### 2.2 Participant Endpoints

| Method | URI | Controller Method | Description |
|--------|-----|-------------------|-------------|
| GET | `/{sessionId}/participants` | `participants()` | List participants for session |
| POST | `/{sessionId}/participants` | `syncParticipants()` | Bulk add/update via `updateOrCreate`. Expects `participants[]` array. |
| DELETE | `/{sessionId}/participants/{participantId}` | `removeParticipant()` | Remove single participant |

**Key behaviors in `syncParticipants()`:**
- Uses `updateOrCreate` on `(session_id, user_id)` composite key
- Required fields per participant: `user_id`, `attendance_status`
- Calls `session->refreshParticipantCount()` after sync (counts present + late)
- Blocked on cancelled sessions

### 2.3 Resolution (GAP) Endpoints

| Method | URI | Controller Method | Description |
|--------|-----|-------------------|-------------|
| GET | `/{sessionId}/resolutions` | `resolutions()` | List resolutions for session |
| POST | `/{sessionId}/resolutions` | `storeResolution()` | Create resolution. Validates `gap_category` enum. |
| PUT | `/{sessionId}/resolutions/{resolutionId}` | `updateResolution()` | Update. Auto-manages `completed_at` timestamp. |
| DELETE | `/{sessionId}/resolutions/{resolutionId}` | `destroyResolution()` | Soft-delete resolution. |

---

## 3. Current System — Mobile App

### 3.1 File Structure

```
lib/
├── models/
│   └── ffs_training_session_model.dart       # FfsTrainingSession, FfsSessionParticipant, FfsSessionResolution
├── services/
│   └── ffs_training_session_service.dart      # API service + response wrappers
└── screens/ffs_activities/
    ├── ffs_sessions_list_screen.dart          # List with search, filter, stats
    ├── ffs_session_detail_screen.dart         # 3-tab detail view (Details, Participants, Resolutions)
    ├── ffs_session_form_screen.dart           # Create/Edit session form
    └── ffs_resolution_form_screen.dart        # Create/Edit resolution form
```

### 3.2 Model: `FfsTrainingSession`

- Maps 1:1 to API response JSON
- Has `groupId` (single int, required)
- No `coFacilitatorId` field
- No report status / submission concept
- Embeds nested `List<FfsSessionParticipant>` and `List<FfsSessionResolution>`
- `toJson()` always sends `group_id` as a string (hardcoded `'1'` in form — **BUG**)

### 3.3 Model: `FfsSessionParticipant`

- Fields: `id`, `sessionId`, `userId`, `attendanceStatus`, `remarks`, `userName`
- No `'pending'` status concept
- `displayName` falls back to `"User #$userId"`

### 3.4 Model: `FfsSessionResolution`

- Full GAP model with categories, responsible person, target date, overdue calculation
- Status: pending / in_progress / completed / cancelled

### 3.5 Service: `FfsTrainingSessionService`

- Static methods for all CRUD operations
- Sessions: `getSessions()`, `getSession()`, `createSession()`, `updateSession()`, `deleteSession()`, `getStats()`
- Participants: `getParticipants()`, `syncParticipants()`, `removeParticipant()`
- Resolutions: `getResolutions()`, `createResolution()`, `updateResolution()`, `deleteResolution()`
- Response wrappers: `FfsBaseResponse`, `FfsSessionListResponse`, `FfsSessionResponse`, `FfsParticipantListResponse`, `FfsResolutionListResponse`, `FfsResolutionResponse`

### 3.6 Screens

**FfsSessionsListScreen** — List page with:
- Search bar, status filter chips, session type filter
- Stats summary cards (total, scheduled, completed, etc.)
- Session cards with status badge, date, group name, participant count
- FAB to create new session

**FfsSessionDetailScreen** — 3-tab layout:
- **Tab 1 — Details:** Session info, status, venue, facilitator, dates, materials, notes
- **Tab 2 — Participants:** List of participants with attendance status badges
- **Tab 3 — Resolutions:** GAP resolutions with category, status, responsible person, overdue indicator
- AppBar actions: Edit, Start, Complete, Cancel, Reschedule, Delete (context-dependent)

**FfsSessionFormScreen** — Simple single-page form:
- Fields: Title, Topic, Description, Session Type, Date, Start/End Time, Venue, Expected Participants, Materials, Notes
- **No group selection** (hardcoded `group_id: '1'`) — **BUG**
- **No facilitator selection**
- **No co-facilitator selection**
- No multi-step wizard workflow

**FfsResolutionFormScreen** — Resolution create/edit form (separate screen)

---

## 4. Required Changes Overview

| # | Requirement | Type | Complexity |
|---|-------------|------|-----------|
| 1 | **Multiple Target Groups** | DB schema change + API + Mobile | **HIGH** |
| 2 | **Auto-populate expected members** | API logic + Mobile display | **MEDIUM** |
| 3 | **Attendance pre-creation** | API logic change | **MEDIUM** |
| 4 | **Co-Facilitator** | DB + API + Mobile (simple field add) | **LOW** |
| 5 | **Report Submission workflow** | DB + API + Mobile | **MEDIUM** |
| 6 | **Report Wizard (multi-step form)** | Mobile UI overhaul | **HIGH** |

---

## 5. Database Changes (Migrations)

### 5.1 New Table: `ffs_session_target_groups` (pivot)

**Purpose:** Replace single `group_id` on `ffs_training_sessions` with a many-to-many relationship.

```
ffs_session_target_groups
├── id                  bigint PK
├── session_id          bigint unsigned FK → ffs_training_sessions.id
├── group_id            bigint unsigned FK → ffs_groups.id
├── created_at          timestamp
│
├── INDEX (session_id)
├── INDEX (group_id)
└── UNIQUE (session_id, group_id)
```

### 5.2 Alter `ffs_training_sessions`

| Change | Column | Type | Notes |
|--------|--------|------|-------|
| **ADD** | `co_facilitator_id` | bigint unsigned, nullable | FK → `users.id` |
| **ADD** | `report_status` | varchar, default `'draft'` | Values: `draft`, `submitted` |
| **ADD** | `submitted_at` | datetime, nullable | Timestamp of report submission |
| **ADD** | `submitted_by_id` | bigint unsigned, nullable | FK → `users.id` |
| **DEPRECATE** | `group_id` | — | Keep for backward compat during migration, then drop. Or: keep as `primary_group_id` shortcut. |

> **Decision needed:** Whether to keep `group_id` as a denormalized "primary group" or fully remove it. Recommendation: **keep it nullable but populate from the pivot** for backward compatibility during the transition period, then remove in a future migration.

### 5.3 Alter `ffs_session_participants`

| Change | Column | Type | Notes |
|--------|--------|------|-------|
| **ADD** | `attendance_status` default change | — | Default should change from `'present'` → `'pending'` to support pre-creation |

**New valid values for `attendance_status`:** `pending`, `present`, `absent`, `excused`, `late`

### 5.4 Migration Sequence

1. `create_ffs_session_target_groups_table` — new pivot table
2. `add_co_facilitator_and_report_status_to_ffs_training_sessions` — new columns
3. `change_participant_default_status` — alter default from `present` → `pending`
4. `migrate_existing_sessions_to_pivot` — data migration: copy each session's `group_id` into the new pivot table
5. *(Future)* `drop_group_id_from_ffs_training_sessions` — once mobile app is updated

---

## 6. API Changes

### 6.1 Modified Endpoints

#### `POST /ffs-training-sessions` (store)

**Current:** Accepts `group_id` (single, required)  
**New:** Accepts `group_ids[]` (array, required, min:1), each validated against `ffs_groups`

**New logic after session creation:**
1. Insert rows into `ffs_session_target_groups` pivot for each group ID
2. Query all users where `users.group_id IN (selected group_ids)` → these are the "expected members"
3. Auto-create `ffs_session_participants` records for each expected member with `attendance_status = 'pending'`
4. Set `expected_participants` = count of auto-created records
5. Accept optional `co_facilitator_id` (validated against `users`)
6. Set `report_status = 'draft'` by default

#### `PUT /ffs-training-sessions/{id}` (update)

**New fields accepted:**
- `group_ids[]` — if changed, re-sync pivot table + optionally re-sync pending participants
- `co_facilitator_id` — nullable
- `report_status` — only allow `draft` → `submitted` transition (not reverse, unless admin)

**New logic for group change:**
- When `group_ids` changes: update pivot, add new pending participants for newly-added groups, optionally remove pending participants from removed groups (only if still `pending`)

#### `GET /ffs-training-sessions` (index)

**Changes:**
- Role-based filter needs to check `ffs_session_target_groups` pivot instead of direct `group_id`
- Response should return `group_ids` array + `group_names` array (or nested `groups` array) instead of single `group_id`/`group_name`
- Add filter: `report_status`

#### `GET /ffs-training-sessions/{id}` (show)

**Changes:**
- Include `groups[]` array in response (from pivot)
- Include `co_facilitator` relationship data
- Include `report_status`, `submitted_at`, `submitted_by` data
- Permission check: user's `group_id` IN session's target group IDs

#### `GET /ffs-training-sessions/stats` (stats)

**Changes:**
- Filter by pivot table instead of direct `group_id`
- Add: `draft_count`, `submitted_count` to stats

#### `POST /{sessionId}/participants` (syncParticipants)

**Changes:**
- Validate `attendance_status` now includes `'pending'` as a valid value
- Still uses `updateOrCreate`, so existing pre-created pending records just get updated

#### `DELETE /ffs-training-sessions/{id}` (destroy)

**Changes:**
- Also delete pivot records from `ffs_session_target_groups`

### 6.2 New Endpoints

| Method | URI | Purpose |
|--------|-----|---------|
| GET | `/{sessionId}/expected-members` | Return list of users from target groups (useful for mobile to display the expected member list before creating participants) |
| POST | `/{id}/submit-report` | Transition `report_status` from `draft` → `submitted`. Set `submitted_at` and `submitted_by_id`. Could validate that attendance has been recorded. |
| POST | `/{id}/unsubmit-report` | (Admin only) Revert submitted → draft |

### 6.3 Model Changes

#### `FfsTrainingSession` model

- Add `co_facilitator_id`, `report_status`, `submitted_at`, `submitted_by_id` to `$fillable`
- Add relationships: `coFacilitator()`, `submittedBy()`, `targetGroups()` (belongsToMany via pivot)
- Add appended attributes: `co_facilitator_name`, `report_status_text`, `group_names` (array)
- Update `getAllowedTransitions()` if report submission gates completing a session
- Uncomment / create `FfsGroup::members()` or add `FfsGroup::users()` relationship: `hasMany(User::class, 'group_id')`

#### `FfsSessionParticipant` model

- Add `STATUS_PENDING = 'pending'` constant
- Update `getAttendanceStatuses()` to include `'pending' => 'Pending'`

---

## 7. Mobile App Changes

### 7.1 Model Changes

#### `FfsTrainingSession` model

| Change | Field | Type |
|--------|-------|------|
| **REPLACE** | `groupId` → `groupIds` | `List<int>` |
| **ADD** | `groupNames` | `List<String>` |
| **ADD** | `coFacilitatorId` | `int?` |
| **ADD** | `coFacilitatorName` | `String?` |
| **ADD** | `reportStatus` | `String` (default `'draft'`) |
| **ADD** | `submittedAt` | `DateTime?` |
| **ADD** | `submittedById` | `int?` |
| **UPDATE** | `fromJson()` | Parse new fields; `group_ids` as List, fallback to `[group_id]` for backward compat |
| **UPDATE** | `toJson()` | Send `group_ids[]` instead of `group_id` |
| **ADD** | Computed props | `isSubmitted`, `isDraft`, `displayGroupNames` (comma-joined) |

#### `FfsSessionParticipant` model

| Change | Notes |
|--------|-------|
| Add `'pending'` to recognized statuses | Treat as not-yet-marked |
| Update `isPresent` | Should remain `present` or `late` only |
| Add `isPending` computed | `attendanceStatus == 'pending'` |

### 7.2 Service Changes

#### `FfsTrainingSessionService`

| Method | Change |
|--------|--------|
| `createSession()` | Send `group_ids[]` array instead of `group_id` |
| `updateSession()` | Support `group_ids[]` |
| **NEW** `getExpectedMembers(int sessionId)` | `GET /{sessionId}/expected-members` |
| **NEW** `submitReport(int sessionId)` | `POST /{id}/submit-report` |
| `syncParticipants()` | Include `'pending'` as valid status |

### 7.3 Screen Changes

#### **FfsSessionFormScreen → Complete Rewrite as Report Wizard**

The current single-page form needs to become a multi-step wizard:

**Step 1 — Session Details**
- Title, Topic, Description
- Session Type (dropdown)
- Date, Start Time, End Time
- Venue
- **Target Groups** (multi-select picker from `ffs_groups` list) — **NEW**
- **Facilitator** (auto-filled, editable) — **NEW**
- **Co-Facilitator** (user picker) — **NEW**
- Materials Used, Notes

**Step 2 — Attendance**
- Shows auto-populated member list from selected target groups
- Each member has a status toggle: Pending → Present / Absent / Excused / Late
- Summary: X/Y members marked, Z present
- Ability to add walk-in participants not in target groups

**Step 3 — GAP / Resolutions**
- Inline resolution creation (currently a separate screen)
- List existing resolutions with edit/delete
- Add new resolution with category, responsible person, target date
- Summary: X resolutions created

**Step 4 — Review & Submit**
- Summary of all steps
- "Save as Draft" button
- "Submit Report" button (transitions `report_status` to `submitted`)

**Navigation:** Stepper or PageView with step indicators. Allow jumping between steps. Auto-save each step as user progresses.

**Implementation options:**
1. Flutter `Stepper` widget — simple but limited customization
2. `PageView` with custom step indicator — more flexible, recommended
3. Separate screens with shared state via Provider/Bloc — cleanest but most work

#### **FfsSessionDetailScreen**

- **Tab 1 (Details):** Show multiple group names, co-facilitator name, report status badge
- **Tab 2 (Participants):** Show pending participants distinctly (greyed out or different icon). Add attendance marking capability directly in this tab. Show "X of Y marked" progress.
- **Tab 3 (Resolutions):** No major changes
- **NEW**: "Submit Report" action button (visible when all attendance is marked and status is `completed`)

#### **FfsSessionsListScreen**

- Session cards should show: multiple group names (or count), report status indicator (draft/submitted)
- Add `report_status` filter chip
- Stats: add draft/submitted counts

#### **New: Group Multi-Select Picker**

A reusable widget/dialog that:
- Loads available groups (from existing groups API)
- Allows multi-selection with checkboxes
- Shows selected count
- Returns `List<int>` of selected group IDs

---

## 8. Implementation Plan & Complexity

### Phase 1 — Database & Backend Foundation (Effort: ~2-3 days)

1. Create migrations (pivot table, new columns, default change)
2. Run data migration for existing sessions
3. Update `FfsTrainingSession` model (relationships, fillable, appends)
4. Update `FfsSessionParticipant` model (add pending status)
5. Enable `FfsGroup::users()` relationship

### Phase 2 — API Endpoints (Effort: ~2-3 days)

1. Refactor `store()` — accept `group_ids[]`, auto-create pivot + pending participants
2. Refactor `update()` — handle group changes, new fields
3. Refactor `index()`, `show()`, `stats()` — pivot-based filtering & responses
4. Add new endpoints: `expected-members`, `submit-report`
5. Update validation rules throughout
6. Backward-compatibility: if `group_id` sent (old app), wrap it in array

### Phase 3 — Mobile Models & Services (Effort: ~1-2 days)

1. Update `FfsTrainingSession` model (new fields, updated JSON parsing)
2. Update `FfsSessionParticipant` model (pending status)
3. Update service methods (group_ids, new endpoints)
4. Add group multi-select data fetching

### Phase 4 — Mobile UI — Report Wizard (Effort: ~4-5 days)

1. Build group multi-select picker widget
2. Build wizard/stepper container
3. Step 1: Session details form (refactor existing)
4. Step 2: Attendance marking UI (new)
5. Step 3: Resolutions inline management (new)
6. Step 4: Review & submit (new)
7. Wire up auto-save / draft persistence

### Phase 5 — Mobile UI — Updates to Existing Screens (Effort: ~1-2 days)

1. Update list screen (multi-group display, report status filter)
2. Update detail screen (new fields, attendance marking, submit button)
3. Update navigation flow (wizard replaces old form)

### Total Estimated Effort: ~10-15 developer days

---

## 9. Risks & Considerations

### 9.1 Backward Compatibility

- **Existing sessions** have a single `group_id`. The data migration must copy these into the pivot table.
- **Old mobile app versions** may still send `group_id` (singular). API must gracefully handle both `group_id` and `group_ids[]` during the transition.
- The `group_id` column should NOT be dropped until all mobile clients have updated.

### 9.2 Performance

- Auto-creating participants on session creation could involve many INSERT queries if target groups are large. Use bulk insert (`insert()` with array) instead of individual `create()` calls.
- Pivot-based filtering (`whereHas('targetGroups', ...)`) is slower than direct column filter. Add proper indexes on the pivot table.
- The `index()` endpoint returns ALL sessions (no pagination). With more data, this will become a problem. Consider adding pagination.

### 9.3 Data Integrity

- When target groups change on an existing session, what happens to participants from removed groups who already have `present`/`absent` status? **Recommendation:** Only remove participants that are still `'pending'`. Already-marked participants from removed groups should be kept.
- The `expected_participants` count should be auto-calculated from the pivot, not manually entered. Remove or make read-only in the form.
- Walk-in participants (users not in target groups) need to be supported — the `syncParticipants` endpoint already handles this since it accepts any valid `user_id`.

### 9.4 Report Submission Workflow

- **Decision needed:** Can a session be "submitted" before it's "completed"? Recommendation: require `status = 'completed'` before allowing `report_status = 'submitted'`.
- **Decision needed:** Can a submitted report be edited? Recommendation: no (except by admin unsubmitting). Lock all edits once submitted.
- **Decision needed:** Does submitting a report notify anyone? (Push notification to admin, etc.)

### 9.5 Offline Support

- The current mobile app does NOT appear to have offline support for training sessions (all calls go through `Utils.http_get/post`).
- The wizard form is multi-step and complex. If network drops mid-wizard, user could lose data. **Recommendation:** Save wizard state locally (SharedPreferences or SQLite) and sync when online.
- Auto-created pending participants are server-side. The mobile app needs to fetch them after creation. If offline, the wizard Step 2 cannot show expected members until reconnected.

### 9.6 Mobile Form Bug

- The current form hardcodes `'group_id': '1'` — this must be fixed regardless of the multi-group change. The selected group(s) should come from user selection.

### 9.7 Co-Facilitator

- Simple field addition. Low risk. Needs a user-picker in the form (similar to facilitator). The co-facilitator should probably be scoped to users who are facilitators or admins, not all users.

### 9.8 Photo/Media

- Current system supports only a single `photo` field (string path). The multi-step report wizard might benefit from supporting multiple photos (gallery). This is NOT in the current requirements but worth considering for the future.

---

## Appendix: Current File Inventory

### API Files

| File | Lines | Purpose |
|------|-------|---------|
| `database/migrations/2026_02_07_000001_create_ffs_training_sessions_table.php` | ~50 | Sessions table migration |
| `database/migrations/2026_02_07_000002_create_ffs_session_participants_table.php` | ~35 | Participants table migration |
| `database/migrations/2026_02_07_000003_create_ffs_session_resolutions_table.php` | ~40 | Resolutions table migration |
| `app/Models/FfsTrainingSession.php` | 181 | Session model with relationships & status machine |
| `app/Models/FfsSessionParticipant.php` | ~55 | Participant model |
| `app/Models/FfsSessionResolution.php` | ~115 | Resolution model with GAP categories |
| `app/Http/Controllers/Api/FfsTrainingSessionController.php` | 816 | All API logic (sessions + participants + resolutions) |
| `routes/api.php` (lines 214-235) | ~21 | Route definitions |
| `app/Admin/Controllers/FfsTrainingSessionController.php` | — | Admin panel controller (not analyzed) |

### Mobile Files

| File | Lines | Purpose |
|------|-------|---------|
| `lib/models/ffs_training_session_model.dart` | 432 | 3 models: Session, Participant, Resolution |
| `lib/services/ffs_training_session_service.dart` | 427 | API service + 6 response wrappers |
| `lib/screens/ffs_activities/ffs_sessions_list_screen.dart` | ~700 | List screen with stats, search, filter |
| `lib/screens/ffs_activities/ffs_session_detail_screen.dart` | 885 | 3-tab detail screen |
| `lib/screens/ffs_activities/ffs_session_form_screen.dart` | 526 | Create/edit form (single page) |
| `lib/screens/ffs_activities/ffs_resolution_form_screen.dart` | — | Resolution form (not fully analyzed) |
