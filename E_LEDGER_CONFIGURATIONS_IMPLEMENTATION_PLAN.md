# E-LEDGER CONFIGURATIONS MODULE - IMPLEMENTATION PLAN

**Date:** 3 January 2026  
**Module:** E-Ledger > Configurations  
**Priority:** HIGH  
**Status:** PLANNING PHASE

---

## 📋 MODULE OVERVIEW

The Configurations module allows VSLA group administrators to view and manage their group's core settings after onboarding is complete. This is the central hub for group management.

### Sub-Modules:
1. **Group Basic Info** - View/Edit group details, location, meeting info
2. **Cycles** - Manage savings cycles (view, create, activate, close, shareout)
3. **Shareouts** - View shareout history and manage distributions

---

## 🎯 LEARNING FROM ONBOARDING

### Data Already Collected During Onboarding:

#### Step 4: Group Creation (`VslaGroupCreationScreen.dart`)
```dart
Fields Collected:
- name (group_name)
- establishment_date
- district_id
- subcounty_text
- parish_text
- village
- meeting_venue
- meeting_day (Monday-Sunday)
- meeting_frequency (Weekly/Bi-weekly/Monthly)
- estimated_members
```

**Backend Table:** `ffs_groups`
**API Endpoint:** `POST /api/vsla-onboarding/create-group`

---

#### Step 5: Main Members (`VslaMainMembersScreen.dart`)
```dart
Secretary & Treasurer Registration:
- name
- phone_number  
- email (optional)
- password

Relationships Established:
- admin_id (chairperson)
- secretary_id
- treasurer_id
```

**Backend Table:** `ffs_groups` (role IDs) + `users` (member data)
**API Endpoint:** `POST /api/vsla-onboarding/register-main-members`

---

#### Step 6: Savings Cycle (`VslaSavingsCycleScreen.dart`)
```dart
Cycle Configuration:
- cycle_name (auto-generated: "2025 Cycle 1")
- start_date
- end_date
- share_value (share_price)
- meeting_frequency (inherited from group)
- loan_interest_rate
- interest_frequency (Weekly/Monthly)
- minimum_loan_amount
- maximum_loan_multiple (5x, 10x, 20x, etc.)
- late_payment_penalty

Computed Values:
- status: 'ongoing'
- is_vsla_cycle: 'Yes'
- is_active_cycle: 'Yes'
- group_id: (linked to group)
```

**Backend Table:** `projects` (cycles stored as projects)
**API Endpoint:** `POST /api/vsla-onboarding/setup-cycle`

---

## 📊 DATABASE SCHEMA ANALYSIS

### Table: `ffs_groups`
```sql
Core Fields:
- id
- name
- type (VSLA)
- code (unique)
- establishment_date
- district_id, subcounty_id, parish_id
- subcounty_text, parish_text
- village
- meeting_venue
- meeting_day
- meeting_frequency
- total_members, male_members, female_members
- admin_id (chairperson)
- secretary_id
- treasurer_id
- status (Active/Inactive/Suspended/Graduated)
- created_at, updated_at
```

### Table: `projects` (Savings Cycles)
```sql
VSLA-Specific Fields:
- is_vsla_cycle ('Yes'/'No')
- group_id (FK to ffs_groups)
- cycle_name
- start_date, end_date
- share_value
- meeting_frequency
- loan_interest_rate
- interest_frequency
- weekly_loan_interest_rate
- monthly_loan_interest_rate
- minimum_loan_amount
- maximum_loan_multiple
- late_payment_penalty
- is_active_cycle ('Yes'/'No')
- status (ongoing/completed/archived)
```

### Table: `shareouts` (To be created)
```sql
Shareout Records:
- id
- cycle_id (FK to projects)
- group_id (FK to ffs_groups)
- shareout_date
- total_amount
- total_shares
- value_per_share
- members_count
- status (pending/completed/cancelled)
- notes
- created_by_id
- created_at, updated_at
```

---

## 🎨 UI/UX DESIGN

### Navigation Structure:
```
E-Ledger Menu
├── Dashboard
├── Configurations ⭐ (Focus)
│   ├── Group Basic Info
│   ├── Cycles
│   └── Shareouts
├── Members
├── Savings
├── Loans
└── Reports
```

### Design Patterns:
- ✅ Consistent with onboarding screens (VslaTheme)
- ✅ Clean cards with icons
- ✅ Edit/View modes
- ✅ Validation with clear error messages
- ✅ Loading states
- ✅ Confirmation dialogs for critical actions
- ✅ Success/error snackbars

---

## 📱 MOBILE APP IMPLEMENTATION

### 1. GROUP BASIC INFO SCREEN

**File:** `lib/screens/vsla/configurations/GroupBasicInfoScreen.dart`

#### Features:
- **View Mode:** Display current group information
- **Edit Mode:** Update editable fields
- **Sections:**
  1. Group Identity (name, establishment date, type)
  2. Location (district, subcounty, parish, village)
  3. Meeting Details (venue, day, frequency)
  4. Core Members (chairperson, secretary, treasurer)
  5. Statistics (total members, status)

#### API Endpoints:
```dart
GET  /api/vsla/groups/{group_id}              // Fetch group details
PUT  /api/vsla/groups/{group_id}/basic-info   // Update group info
```

#### Validation Rules:
- Group name: Required, min 3 chars
- Establishment date: Required, not future
- Meeting venue: Required
- Meeting day: Required, valid day name
- Meeting frequency: Required dropdown

#### UI Components:
```dart
- AppBar with Edit/Save button
- ScrollView with sections
- ReadOnlyField (view mode)
- TextFormField (edit mode)
- DropdownButton (district, frequency, day)
- DatePicker (establishment date)
- MemberCard (show admin/secretary/treasurer)
```

---

### 2. CYCLES MANAGEMENT SCREEN

**File:** `lib/screens/vsla/configurations/CyclesScreen.dart`

#### Features:
- **List View:** Show all cycles (active, completed, archived)
- **Active Cycle Card:** Highlight with special styling
- **Cycle Actions:**
  - View Details
  - Edit (only active cycle)
  - Close Cycle (trigger shareout process)
  - Create New Cycle
  - Archive Old Cycles

#### API Endpoints:
```dart
GET  /api/vsla/cycles?group_id={id}            // List all cycles
GET  /api/vsla/cycles/{cycle_id}               // Get cycle details
POST /api/vsla/cycles                          // Create new cycle
PUT  /api/vsla/cycles/{cycle_id}               // Update cycle
POST /api/vsla/cycles/{cycle_id}/close         // Close & shareout
DELETE /api/vsla/cycles/{cycle_id}             // Archive cycle
```

#### Cycle States:
1. **Active** (is_active_cycle = 'Yes', status = 'ongoing')
   - Only one active cycle per group
   - Can record savings, loans, meetings
   - Can be edited
   
2. **Completed** (is_active_cycle = 'No', status = 'completed')
   - Shareout has been done
   - Read-only
   - Shows final statistics
   
3. **Archived** (status = 'archived')
   - Old cycles for historical reference
   - Hidden by default

#### Cycle Card UI:
```dart
CycleCard(
  title: "2025 Cycle 1",
  status: Active/Completed,
  dateRange: "Jan 15 - Jul 15, 2025",
  shareValue: "UGX 10,000",
  totalSavings: "UGX 2,450,000",
  totalLoans: "UGX 1,200,000",
  memberCount: 25,
  meetingsHeld: 24,
  actions: [Edit, Close, View],
)
```

---

### 3. SHAREOUTS SCREEN

**File:** `lib/screens/vsla/configurations/ShareoutsScreen.dart`

#### Features:
- **Shareout History:** List all past shareouts
- **Create Shareout:** Wizard for new shareout
- **Shareout Details:** View distributions per member
- **Export:** Generate PDF/Excel reports

#### API Endpoints:
```dart
GET  /api/vsla/shareouts?group_id={id}         // List shareouts
GET  /api/vsla/shareouts/{id}                  // Get shareout details
POST /api/vsla/shareouts                       // Create shareout
GET  /api/vsla/shareouts/{id}/report           // Download report
```

#### Shareout Process:
1. **Initiate:** Close active cycle
2. **Calculate:** 
   - Total savings per member
   - Share of profits
   - Welfare fund distribution
   - Loan interest earned
3. **Review:** Show distribution preview
4. **Confirm:** Lock calculations
5. **Distribute:** Record payments
6. **Complete:** Mark cycle as completed

#### Shareout Card UI:
```dart
ShareoutCard(
  cycleTitle: "2024 Cycle 2",
  shareoutDate: "Dec 31, 2024",
  totalDistributed: "UGX 5,230,000",
  membersCount: 28,
  valuePerShare: "UGX 11,500",
  profitPercentage: "15%",
  actions: [ViewDetails, DownloadReport],
)
```

---

## 🔧 BACKEND API IMPLEMENTATION

### Controller: `VslaConfigurationController.php`

**Location:** `app/Http/Controllers/Api/VslaConfigurationController.php`

#### Methods to Implement:

```php
// GROUP BASIC INFO
getGroupInfo(Request $request, $groupId)
updateGroupBasicInfo(Request $request, $groupId)

// CYCLES MANAGEMENT
getCycles(Request $request)  // Filter by group_id
getCycleDetails(Request $request, $cycleId)
createCycle(Request $request)
updateCycle(Request $request, $cycleId)
closeCycle(Request $request, $cycleId)  // Initiates shareout
archiveCycle(Request $request, $cycleId)
getActiveCycle(Request $request, $groupId)

// SHAREOUTS
getShareouts(Request $request)  // Filter by group_id
getShareoutDetails(Request $request, $shareoutId)
createShareout(Request $request)
calculateShareoutDistribution(Request $request, $cycleId)
generateShareoutReport(Request $request, $shareoutId)
```

---

### Routes: `routes/api.php`

```php
// VSLA Configurations
Route::prefix('vsla')->group(function () {
    
    // Group Basic Info
    Route::get('groups/{group_id}', [VslaConfigurationController::class, 'getGroupInfo']);
    Route::put('groups/{group_id}/basic-info', [VslaConfigurationController::class, 'updateGroupBasicInfo']);
    
    // Cycles Management
    Route::get('cycles', [VslaConfigurationController::class, 'getCycles']);
    Route::get('cycles/{cycle_id}', [VslaConfigurationController::class, 'getCycleDetails']);
    Route::post('cycles', [VslaConfigurationController::class, 'createCycle']);
    Route::put('cycles/{cycle_id}', [VslaConfigurationController::class, 'updateCycle']);
    Route::post('cycles/{cycle_id}/close', [VslaConfigurationController::class, 'closeCycle']);
    Route::delete('cycles/{cycle_id}', [VslaConfigurationController::class, 'archiveCycle']);
    Route::get('cycles/active/{group_id}', [VslaConfigurationController::class, 'getActiveCycle']);
    
    // Shareouts
    Route::get('shareouts', [VslaConfigurationController::class, 'getShareouts']);
    Route::get('shareouts/{shareout_id}', [VslaConfigurationController::class, 'getShareoutDetails']);
    Route::post('shareouts', [VslaConfigurationController::class, 'createShareout']);
    Route::post('shareouts/calculate/{cycle_id}', [VslaConfigurationController::class, 'calculateShareoutDistribution']);
    Route::get('shareouts/{shareout_id}/report', [VslaConfigurationController::class, 'generateShareoutReport']);
});
```

---

### Database Migration: Shareouts Table

**File:** `database/migrations/2026_01_03_create_shareouts_table.php`

```php
Schema::create('shareouts', function (Blueprint $table) {
    $table->id();
    $table->bigInteger('cycle_id')->unsigned();
    $table->bigInteger('group_id')->unsigned();
    $table->date('shareout_date');
    $table->decimal('total_amount', 15, 2)->default(0);
    $table->integer('total_shares')->default(0);
    $table->decimal('value_per_share', 15, 2)->default(0);
    $table->integer('members_count')->default(0);
    $table->decimal('welfare_fund_amount', 15, 2)->default(0);
    $table->decimal('loan_fund_amount', 15, 2)->default(0);
    $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
    $table->text('notes')->nullable();
    $table->bigInteger('created_by_id')->unsigned();
    $table->timestamps();
    
    $table->index('cycle_id');
    $table->index('group_id');
    $table->index('shareout_date');
    $table->index('status');
});
```

---

## 🔐 SECURITY & VALIDATION

### Access Control:
- ✅ User must be logged in
- ✅ User must belong to the group
- ✅ Only admin/chairperson can edit group info
- ✅ Only admin/chairperson can create/close cycles
- ✅ Only admin/treasurer can initiate shareouts

### Validation Rules:

#### Group Update:
```php
'name' => 'required|string|min:3|max:200',
'establishment_date' => 'required|date|before_or_equal:today',
'meeting_venue' => 'required|string',
'meeting_day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
'meeting_frequency' => 'required|in:Weekly,Bi-weekly,Monthly',
```

#### Cycle Creation:
```php
'cycle_name' => 'required|string|max:200',
'start_date' => 'required|date',
'end_date' => 'required|date|after:start_date',
'share_value' => 'required|numeric|min:1000',
'loan_interest_rate' => 'required|numeric|min:0|max:100',
'minimum_loan_amount' => 'required|numeric|min:0',
'maximum_loan_multiple' => 'required|integer|min:1|max:50',
```

#### Business Rules:
- ✅ Only ONE active cycle per group at a time
- ✅ Cannot edit completed cycles
- ✅ Cannot delete cycle with transactions
- ✅ Must complete shareout before starting new cycle
- ✅ Cycle dates cannot overlap

---

## 📝 IMPLEMENTATION PHASES

### ✅ PHASE 1: GROUP BASIC INFO (Days 1-2)
1. Create `GroupBasicInfoScreen.dart`
2. Implement view/edit mode toggle
3. Create backend endpoints
4. Add validation
5. Test update flow
6. Add to navigation menu

### ✅ PHASE 2: CYCLES MANAGEMENT (Days 3-5)
1. Create `CyclesScreen.dart`
2. Implement cycles list
3. Create `CycleDetailsScreen.dart`
4. Implement create/edit cycle
5. Add cycle status badges
6. Implement close cycle flow
7. Create backend endpoints
8. Add business logic validation
9. Test complete workflow

### ✅ PHASE 3: SHAREOUTS (Days 6-8)
1. Create shareouts migration
2. Create `ShareoutsScreen.dart`
3. Implement shareout wizard
4. Create calculation logic
5. Implement distribution preview
6. Add PDF export
7. Create backend endpoints
8. Test shareout process
9. Integration testing

---

## 🧪 TESTING CHECKLIST

### Group Basic Info:
- [ ] View group info loads correctly
- [ ] Edit mode enables all fields
- [ ] Validation works for all fields
- [ ] Update saves to database
- [ ] Changes reflect immediately
- [ ] Error handling works

### Cycles:
- [ ] List shows all cycles correctly
- [ ] Active cycle is highlighted
- [ ] Can create new cycle
- [ ] Validation prevents overlapping dates
- [ ] Cannot have 2 active cycles
- [ ] Close cycle initiates shareout
- [ ] Completed cycles are read-only

### Shareouts:
- [ ] Calculation logic is accurate
- [ ] Distribution preview is correct
- [ ] Members receive correct amounts
- [ ] PDF export works
- [ ] Shareout completes cycle
- [ ] New cycle can be started after

---

## 📊 SUCCESS METRICS

- ✅ All CRUD operations work error-free
- ✅ UI is responsive and intuitive
- ✅ Data syncs properly with backend
- ✅ Validation prevents invalid data
- ✅ Error messages are clear
- ✅ Loading states provide feedback
- ✅ No crashes or exceptions
- ✅ Follows existing design patterns

---

## 🎯 NEXT STEPS

**Immediate Action:**
1. Create VslaConfigurationController
2. Add routes to api.php
3. Create GroupBasicInfoScreen
4. Implement first API endpoint
5. Test basic flow

**Ready to begin implementation!** 🚀
