# VSLA Meeting Web Portal - Implementation Progress & Next Steps

**Date**: December 11, 2025  
**Status**: Phase 1-4 COMPLETED ✅ | Phase 5-6 Remaining  
**Completed**: Database, Models, Service Layer, API Endpoints  
**Remaining**: Mobile Integration, Admin Interface  

---

## ✅ PHASE 1-4 COMPLETED

### Phase 1: Database Setup ✅
**Status**: COMPLETE

**Migrations Created & Run Successfully**:
1. ✅ `2025_12_11_000001_create_vsla_meetings_table.php`
   - Main meeting storage with 33 fields
   - JSON columns for offline data preservation
   - Processing status tracking
   - Error/warning tracking
   
2. ✅ `2025_12_11_000002_create_vsla_meeting_attendance_table.php`
   - Individual attendance records
   - Unique constraint per member per meeting
   
3. ✅ `2025_12_11_000003_create_vsla_action_plans_table.php`
   - Action plan management
   - Priority and status tracking

**Database Tables Verified**:
```bash
✅ vsla_meetings - Created successfully
✅ vsla_meeting_attendance - Created successfully  
✅ vsla_action_plans - Created successfully
```

**Note**: Foreign key constraints disabled due to users table INT vs BIGINT compatibility. Tables use indexes for relationships.

---

### Phase 2: Models & Relationships ✅
**Status**: COMPLETE

**Models Created**:

1. ✅ **VslaMeeting** (`app/Models/VslaMeeting.php`)
   - Fillable fields: 33 attributes
   - Casts: JSON arrays, dates, decimals, booleans
   - Relationships:
     - `cycle()` → Project (VSLA cycle)
     - `group()` → FfsGroup
     - `creator()` → User
     - `processor()` → User
     - `attendance()` → hasMany VslaMeetingAttendance
     - `actionPlans()` → hasMany VslaActionPlan
   - Scopes: `pending()`, `completed()`, `failed()`, `needsReview()`, `hasErrors()`, `hasWarnings()`, `byCycle()`, `byGroup()`
   - Helper Methods:
     - `isPending()`, `isCompleted()`, `isFailed()`, `needsReview()`, `canBeProcessed()`
     - `markAsProcessing()`, `markAsCompleted()`, `markAsFailed()`, `markAsNeedsReview()`
     - `addError()`, `addWarning()`
   - Computed Attributes:
     - `total_members`, `attendance_rate`, `total_contributions`, `total_cash_collected`, `net_cash_flow`

2. ✅ **VslaMeetingAttendance** (`app/Models/VslaMeetingAttendance.php`)
   - Fillable: meeting_id, member_id, is_present, absent_reason
   - Relationships: `meeting()`, `member()`
   - Scopes: `present()`, `absent()`, `forMeeting()`, `forMember()`
   - Methods: `wasPresent()`, `wasAbsent()`

3. ✅ **VslaActionPlan** (`app/Models/VslaActionPlan.php`)
   - Fillable: 12 fields including priority, status, due_date
   - Relationships: `meeting()`, `cycle()`, `assignedTo()`, `creator()`
   - Scopes: `byStatus()`, `pending()`, `inProgress()`, `completed()`, `cancelled()`, `byPriority()`, `highPriority()`, `overdue()`, `forCycle()`, `assignedToMember()`, `active()`
   - Methods: `markAsInProgress()`, `complete()`, `cancel()`, `updateStatus()`, `isOverdue()`, `isActive()`
   - Computed: `days_until_due`, `status_display`, `priority_display`

**Models Tested**: All models instantiate correctly ✅

---

### Phase 3: Service Layer ✅
**Status**: COMPLETE

**MeetingProcessingService Created** (`app/Services/MeetingProcessingService.php`):

**Core Method**:
- `processMeeting(VslaMeeting $meeting)` - Main orchestrator with DB transaction management

**Processing Pipeline**:
1. ✅ `validateMeeting()` - Checks for duplicates, validates cycle, validates data consistency
2. ✅ `processAttendance()` - Extracts attendance_data JSON → creates VslaMeetingAttendance records
3. ✅ `processTransactions()` - Creates double-entry for savings/welfare/social fund/fines
4. ✅ `processSharePurchases()` - Creates share purchase transactions
5. ✅ `processLoans()` - Creates loan disbursement transactions
6. ✅ `processActionPlans()` - Updates previous plans, creates new upcoming plans

**Helper Methods**:
- ✅ `createDoubleEntryTransaction()` - Creates paired debit/credit ProjectTransaction records
- ✅ `createLoanDisbursement()` - Creates loan-specific double-entry transactions
- ✅ `calculateExpectedTotal()` - Validates transaction totals

**Error Handling**:
- Wrapped in DB transactions with rollback on error
- Detailed error tracking with type, message, field
- Warning system for non-critical issues
- Comprehensive logging

**Transaction Types Mapped**:
- `savings` → SAVINGS_CONTRIBUTION
- `welfare` → WELFARE_CONTRIBUTION  
- `social_fund` → SOCIAL_FUND_CONTRIBUTION
- `fine/penalty` → FINE
- Share purchases → SHARE_PURCHASE
- Loans → LOAN_DISBURSEMENT

---

### Phase 4: API Endpoints ✅
**Status**: COMPLETE

**VslaMeetingController Created** (`app/Http/Controllers/Api/VslaMeetingController.php`):

**Endpoints**:

1. ✅ **POST /api/vsla-meetings/submit** - Submit meeting from mobile
   - Validates 16 required/optional fields
   - Checks for duplicates by local_id
   - Creates meeting record
   - Processes immediately via MeetingProcessingService
   - Returns success/failure with errors/warnings
   - Response includes meeting stats (attendance_rate, cash_collected, etc.)

2. ✅ **GET /api/vsla-meetings** - List meetings with filters
   - Filters: cycle_id, group_id, status, date_from, date_to, has_errors, has_warnings
   - Includes relationships: cycle, group, creator
   - Paginated (default 20 per page)
   - Sorted by date desc, meeting_number desc

3. ✅ **GET /api/vsla-meetings/{id}** - Get single meeting
   - Full details with all relationships
   - Includes attendance.member, actionPlans.assignedTo

4. ✅ **PUT /api/vsla-meetings/{id}/reprocess** - Reprocess failed meeting
   - Only for status: failed or needs_review
   - Resets errors/warnings
   - Re-runs processing pipeline

5. ✅ **GET /api/vsla-meetings/stats** - Meeting statistics
   - Total meetings, by status counts
   - Total savings, loans, shares
   - Filterable by cycle_id

**Routes Registered** (`routes/api.php`):
```php
Route::prefix('vsla-meetings')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/submit', [VslaMeetingController::class, 'submit']);
        Route::get('/', [VslaMeetingController::class, 'index']);
        Route::get('/stats', [VslaMeetingController::class, 'stats']);
        Route::get('/{id}', [VslaMeetingController::class, 'show']);
        Route::put('/{id}/reprocess', [VslaMeetingController::class, 'reprocess']);
    });
});
```

**Route Verification**: All 5 routes registered successfully ✅

**Authentication**: All endpoints protected with `auth:sanctum` middleware

---

## 🚧 REMAINING WORK

### PHASE 5: Mobile App Integration (Next Priority)

**File to Create**: `lib/services/vsla_meeting_sync_service.dart`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VslaMeeting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'local_id', 'cycle_id', 'group_id', 'created_by_id',
        'meeting_date', 'meeting_number', 'notes',
        'members_present', 'members_absent',
        'total_savings_collected', 'total_welfare_collected',
        'total_social_fund_collected', 'total_fines_collected',
        'total_loans_disbursed', 'total_shares_sold', 'total_share_value',
        'attendance_data', 'transactions_data', 'loans_data',
        'share_purchases_data', 'previous_action_plans_data', 'upcoming_action_plans_data',
        'processing_status', 'processed_at', 'processed_by_id',
        'has_errors', 'has_warnings', 'errors', 'warnings',
        'submitted_from_app_at', 'received_at'
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'attendance_data' => 'array',
        'transactions_data' => 'array',
        'loans_data' => 'array',
        'share_purchases_data' => 'array',
        'previous_action_plans_data' => 'array',
        'upcoming_action_plans_data' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
        'has_errors' => 'boolean',
        'has_warnings' => 'boolean',
        'processed_at' => 'datetime',
        'submitted_from_app_at' => 'datetime',
        'received_at' => 'datetime',
        'total_savings_collected' => 'decimal:2',
        'total_welfare_collected' => 'decimal:2',
        'total_social_fund_collected' => 'decimal:2',
        'total_fines_collected' => 'decimal:2',
        'total_loans_disbursed' => 'decimal:2',
        'total_share_value' => 'decimal:2',
    ];

    // Relationships
    public function cycle()
    {
        return $this->belongsTo(Project::class, 'cycle_id');
    }

    public function group()
    {
        return $this->belongsTo(FfsGroup::class, 'group_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    public function attendance()
    {
        return $this->hasMany(VslaMeetingAttendance::class, 'meeting_id');
    }

    public function actionPlans()
    {
        return $this->hasMany(VslaActionPlan::class, 'meeting_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('processing_status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('processing_status', 'completed');
    }

    public function scopeHasErrors($query)
    {
        return $query->where('has_errors', true);
    }

    public function scopeByCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    // Methods
    public function isPending()
    {
        return $this->processing_status === 'pending';
    }

    public function isCompleted()
    {
        return $this->processing_status === 'completed';
    }

    public function canBeProcessed()
    {
        return $this->isPending() && !$this->has_errors;
    }
}
```

**File**: `app/Models/VslaMeetingAttendance.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VslaMeetingAttendance extends Model
{
    protected $table = 'vsla_meeting_attendance';

    protected $fillable = [
        'meeting_id', 'member_id', 'is_present', 'absent_reason'
    ];

    protected $casts = [
        'is_present' => 'boolean',
    ];

    public function meeting()
    {
        return $this->belongsTo(VslaMeeting::class, 'meeting_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function scopePresent($query)
    {
        return $query->where('is_present', true);
    }

    public function scopeAbsent($query)
    {
        return $query->where('is_present', false);
    }
}
```

**File**: `app/Models/VslaActionPlan.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VslaActionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'local_id', 'meeting_id', 'cycle_id',
        'action', 'description',
        'assigned_to_member_id', 'priority', 'due_date',
        'status', 'completion_notes', 'completed_at',
        'created_by_id'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(VslaMeeting::class, 'meeting_id');
    }

    public function cycle()
    {
        return $this->belongsTo(Project::class, 'cycle_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_member_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
            ->where('due_date', '<', now());
    }

    public function complete($notes = null)
    {
        $this->update([
            'status' => 'completed',
            'completion_notes' => $notes,
            'completed_at' => now()
        ]);
    }
}
```

---

### PHASE 3: Create Meeting Processing Service (Priority 1)

**File**: `app/Services/MeetingProcessingService.php`

This is the CORE business logic that:
1. Validates meeting data
2. Extracts attendance and creates records
3. Creates double-entry transactions for savings, welfare, social fund, fines
4. Creates share purchase records and transactions
5. Creates loan disbursement transactions
6. Processes action plans (status updates + new creations)
7. Handles errors and warnings

**See full implementation in**: `/Applications/MAMP/htdocs/fao-ffs-mis-api/VSLA_MEETING_WEB_PORTAL_IMPLEMENTATION_PLAN.md` (lines 300-500)

**Key Methods**:
- `processMeeting(VslaMeeting $meeting)` - Main entry point
- `validateMeeting()` - Pre-processing validation
- `processAttendance()` - Extract and save attendance
- `processTransactions()` - Create savings/welfare/social/fines transactions
- `processSharePurchases()` - Create shares + transactions
- `processLoans()` - Create loan disbursement transactions
- `processActionPlans()` - Update previous + create upcoming
- `createDoubleEntryTransaction()` - Double-entry helper
- `createLoanDisbursement()` - Loan disbursement helper

---

### PHASE 4: Create API Endpoints (Priority 1)

**File**: `app/Http/Controllers/Api/VslaMeetingController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VslaMeeting;
use App\Services\MeetingProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VslaMeetingController extends Controller
{
    protected $meetingProcessor;

    public function __construct(MeetingProcessingService $meetingProcessor)
    {
        $this->meetingProcessor = $meetingProcessor;
    }

    /**
     * Submit meeting from mobile app
     * POST /api/vsla-meetings/submit
     */
    public function submit(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'local_id' => 'required|string|unique:vsla_meetings,local_id',
            'cycle_id' => 'required|exists:projects,id',
            'group_id' => 'required|exists:ffs_groups,id',
            'meeting_date' => 'required|date',
            'meeting_number' => 'nullable|integer',
            'notes' => 'nullable|string',
            'attendance_data' => 'required|array',
            'transactions_data' => 'nullable|array',
            'loans_data' => 'nullable|array',
            'share_purchases_data' => 'nullable|array',
            'previous_action_plans_data' => 'nullable|array',
            'upcoming_action_plans_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate
        $existing = VslaMeeting::where('local_id', $request->local_id)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Meeting already submitted',
                'meeting_id' => $existing->id,
                'processing_status' => $existing->processing_status
            ], 409);
        }

        // Create meeting record
        $meeting = VslaMeeting::create([
            'local_id' => $request->local_id,
            'cycle_id' => $request->cycle_id,
            'group_id' => $request->group_id,
            'created_by_id' => auth()->id(),
            'meeting_date' => $request->meeting_date,
            'meeting_number' => $request->meeting_number,
            'notes' => $request->notes,
            'members_present' => $request->members_present ?? 0,
            'members_absent' => $request->members_absent ?? 0,
            'total_savings_collected' => $request->total_savings_collected ?? 0,
            'total_welfare_collected' => $request->total_welfare_collected ?? 0,
            'total_social_fund_collected' => $request->total_social_fund_collected ?? 0,
            'total_fines_collected' => $request->total_fines_collected ?? 0,
            'total_loans_disbursed' => $request->total_loans_disbursed ?? 0,
            'total_shares_sold' => $request->total_shares_sold ?? 0,
            'total_share_value' => $request->total_share_value ?? 0,
            'attendance_data' => $request->attendance_data,
            'transactions_data' => $request->transactions_data,
            'loans_data' => $request->loans_data,
            'share_purchases_data' => $request->share_purchases_data,
            'previous_action_plans_data' => $request->previous_action_plans_data,
            'upcoming_action_plans_data' => $request->upcoming_action_plans_data,
            'submitted_from_app_at' => now(),
            'received_at' => now(),
            'processing_status' => 'pending'
        ]);

        // Process meeting
        $result = $this->meetingProcessor->processMeeting($meeting);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] 
                ? 'Meeting processed successfully' 
                : 'Meeting processing failed',
            'meeting_id' => $meeting->id,
            'processing_status' => $meeting->fresh()->processing_status,
            'errors' => $result['errors'],
            'warnings' => $result['warnings']
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Get meetings list
     * GET /api/vsla-meetings
     */
    public function index(Request $request)
    {
        $query = VslaMeeting::with(['cycle', 'group', 'creator']);

        if ($request->has('cycle_id')) {
            $query->where('cycle_id', $request->cycle_id);
        }

        if ($request->has('status')) {
            $query->where('processing_status', $request->status);
        }

        $meetings = $query->orderBy('meeting_date', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($meetings);
    }

    /**
     * Get single meeting
     * GET /api/vsla-meetings/{id}
     */
    public function show($id)
    {
        $meeting = VslaMeeting::with(['cycle', 'group', 'creator', 'attendance', 'actionPlans'])
            ->findOrFail($id);

        return response()->json($meeting);
    }

    /**
     * Reprocess a failed meeting
     * PUT /api/vsla-meetings/{id}/reprocess
     */
    public function reprocess($id)
    {
        $meeting = VslaMeeting::findOrFail($id);

        if (!in_array($meeting->processing_status, ['failed', 'needs_review'])) {
            return response()->json([
                'success' => false,
                'message' => 'Meeting cannot be reprocessed in current status'
            ], 400);
        }

        // Reset status
        $meeting->update([
            'processing_status' => 'pending',
            'has_errors' => false,
            'has_warnings' => false,
            'errors' => null,
            'warnings' => null
        ]);

        // Reprocess
        $result = $this->meetingProcessor->processMeeting($meeting);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] 
                ? 'Meeting reprocessed successfully' 
                : 'Meeting reprocessing failed',
            'errors' => $result['errors'],
            'warnings' => $result['warnings']
        ]);
    }
}
```

**File**: `routes/api.php` - Add routes:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('vsla-meetings')->group(function () {
        Route::post('submit', [VslaMeetingController::class, 'submit']);
        Route::get('', [VslaMeetingController::class, 'index']);
        Route::get('{id}', [VslaMeetingController::class, 'show']);
        Route::put('{id}/reprocess', [VslaMeetingController::class, 'reprocess']);
    });
});
```

---

### PHASE 5: Mobile App Integration (Priority 2)

**File**: `lib/services/vsla_meeting_sync_service.dart` (Flutter)

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/VslaOfflineMeeting.dart';
import 'vsla_offline_meeting_service.dart';
import '../utils/Utils.dart';

class VslaMeetingSyncService {
  static Future<Map<String, dynamic>> submitMeeting(VslaOfflineMeeting meeting) async {
    try {
      // Get auth token
      final token = await Utils.getAuthToken();
      
      // Prepare payload matching API expectations
      final payload = {
        'local_id': meeting.localId,
        'cycle_id': meeting.cycleId,
        'group_id': await _getGroupId(meeting.cycleId), // Need to implement
        'meeting_date': meeting.meetingDate,
        'meeting_number': meeting.meetingNumber,
        'notes': meeting.notes,
        'members_present': meeting.membersPresent,
        'members_absent': meeting.membersAbsent,
        'total_savings_collected': meeting.totalSavingsCollected,
        'total_welfare_collected': _calculateWelfareTotal(meeting.transactions),
        'total_social_fund_collected': _calculateSocialFundTotal(meeting.transactions),
        'total_fines_collected': meeting.totalFinesCollected,
        'total_loans_disbursed': meeting.totalLoansDisbursed,
        'total_shares_sold': meeting.totalSharesSold,
        'total_share_value': _calculateShareValue(meeting.sharePurchases),
        'attendance_data': meeting.attendance.map((a) => a.toJson()).toList(),
        'transactions_data': meeting.transactions.map((t) => t.toJson()).toList(),
        'loans_data': meeting.loans.map((l) => l.toJson()).toList(),
        'share_purchases_data': meeting.sharePurchases.map((s) => s.toJson()).toList(),
        'previous_action_plans_data': meeting.previousActionPlans.map((p) => p.toJson()).toList(),
        'upcoming_action_plans_data': meeting.upcomingActionPlans.map((u) => u.toJson()).toList(),
      };
      
      // Submit to API
      final response = await http.post(
        Uri.parse('${Utils.API_BASE_URL}/vsla-meetings/submit'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode(payload),
      );
      
      final data = jsonDecode(response.body);
      
      if (response.statusCode == 200 && data['success'] == true) {
        // Success - delete local meeting
        await VslaOfflineMeetingService.deleteMeeting(meeting.localId);
        
        return {
          'success': true,
          'message': data['message'] ?? 'Meeting submitted successfully',
          'meeting_id': data['meeting_id'],
          'warnings': data['warnings'] ?? []
        };
      } else {
        // Failed - keep local copy
        return {
          'success': false,
          'message': data['message'] ?? 'Submission failed',
          'errors': data['errors'] ?? []
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error submitting meeting: $e',
        'errors': [e.toString()]
      };
    }
  }
  
  static double _calculateWelfareTotal(List<MeetingTransaction> transactions) {
    return transactions
        .where((t) => t.accountType == 'welfare')
        .fold(0.0, (sum, t) => sum + t.amount);
  }
  
  static double _calculateSocialFundTotal(List<MeetingTransaction> transactions) {
    return transactions
        .where((t) => t.accountType == 'social_fund')
        .fold(0.0, (sum, t) => sum + t.amount);
  }
  
  static double _calculateShareValue(List<MeetingSharePurchase> shares) {
    return shares.fold(0.0, (sum, s) => sum + s.totalAmountPaid);
  }
  
  static Future<int> _getGroupId(int cycleId) async {
    // TODO: Implement logic to get group_id from cycle_id
    // This might be stored locally or fetched from API
    return 1; // Placeholder
  }
}
```

**Update**: `lib/screens/vsla/MeetingSummaryScreen.dart` - Add sync button:

```dart
// Add sync button to meeting summary
ElevatedButton.icon(
  onPressed: _syncMeeting,
  icon: Icon(FeatherIcons.upload),
  label: Text('Sync to Server'),
  style: ElevatedButton.styleFrom(
    backgroundColor: ModernTheme.info,
  ),
)

Future<void> _syncMeeting() async {
  setState(() => _isSyncing = true);
  
  final result = await VslaMeetingSyncService.submitMeeting(widget.meeting);
  
  if (result['success']) {
    Utils.toast(result['message'], color: ModernTheme.success);
    
    if (result['warnings'].isNotEmpty) {
      // Show warnings dialog
      showWarningsDialog(result['warnings']);
    }
    
    // Navigate back to hub
    Get.back(result: true);
    Get.back(result: true);
  } else {
    Utils.toast(result['message'], color: ModernTheme.error);
    
    if (result['errors'].isNotEmpty) {
      // Show errors dialog
      showErrorsDialog(result['errors']);
    }
  }
  
  setState(() => _isSyncing = false);
}
```

---

### PHASE 6: Admin Interface (Priority 3)

**File**: `app/Admin/Controllers/VslaMeetingController.php`

```php
<?php

namespace App\Admin\Controllers;

use App\Models\VslaMeeting;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VslaMeetingController extends AdminController
{
    protected $title = 'VSLA Meetings';

    protected function grid()
    {
        $grid = new Grid(new VslaMeeting());

        $grid->column('id', 'ID')->sortable();
        $grid->column('meeting_number', 'Meeting #');
        $grid->column('meeting_date', 'Date')->display(function ($date) {
            return date('M d, Y', strtotime($date));
        });
        $grid->column('cycle.title', 'Cycle')->limit(30);
        $grid->column('group.name', 'Group')->limit(30);
        $grid->column('members_present', 'Present')->label('success');
        $grid->column('members_absent', 'Absent')->label('warning');
        $grid->column('processing_status', 'Status')->using([
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'needs_review' => 'Needs Review'
        ])->label([
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'needs_review' => 'warning'
        ]);
        $grid->column('has_errors', 'Errors')->bool();
        $grid->column('has_warnings', 'Warnings')->bool();
        $grid->column('created_at', 'Submitted')->display(function ($date) {
            return $date ? date('M d, Y H:i', strtotime($date)) : 'N/A';
        });

        $grid->filter(function ($filter) {
            $filter->equal('cycle_id', 'Cycle')->select(\App\Models\Project::pluck('title', 'id'));
            $filter->equal('processing_status', 'Status')->select([
                'pending' => 'Pending',
                'completed' => 'Completed',
                'failed' => 'Failed',
                'needs_review' => 'Needs Review'
            ]);
            $filter->between('meeting_date', 'Meeting Date')->date();
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(VslaMeeting::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('local_id', 'Local ID');
        $show->field('meeting_number', 'Meeting Number');
        $show->field('meeting_date', 'Meeting Date');
        $show->field('cycle.title', 'Cycle');
        $show->field('group.name', 'Group');
        $show->field('notes', 'Notes');
        
        $show->divider();
        $show->field('members_present', 'Members Present');
        $show->field('members_absent', 'Members Absent');
        
        $show->divider();
        $show->field('total_savings_collected', 'Total Savings')->as(function ($value) {
            return 'UGX ' . number_format($value, 2);
        });
        $show->field('total_welfare_collected', 'Total Welfare')->as(function ($value) {
            return 'UGX ' . number_format($value, 2);
        });
        $show->field('total_social_fund_collected', 'Total Social Fund')->as(function ($value) {
            return 'UGX ' . number_format($value, 2);
        });
        $show->field('total_fines_collected', 'Total Fines')->as(function ($value) {
            return 'UGX ' . number_format($value, 2);
        });
        $show->field('total_loans_disbursed', 'Total Loans Disbursed')->as(function ($value) {
            return 'UGX ' . number_format($value, 2);
        });
        $show->field('total_shares_sold', 'Total Shares Sold');
        
        $show->divider();
        $show->field('processing_status', 'Processing Status');
        $show->field('has_errors', 'Has Errors')->using(['0' => 'No', '1' => 'Yes']);
        $show->field('has_warnings', 'Has Warnings')->using(['0' => 'No', '1' => 'Yes']);
        
        if ($show->model()->has_errors && $show->model()->errors) {
            $show->field('errors', 'Errors')->json();
        }
        
        if ($show->model()->has_warnings && $show->model()->warnings) {
            $show->field('warnings', 'Warnings')->json();
        }
        
        $show->divider();
        $show->field('created_at', 'Created At');
        $show->field('submitted_from_app_at', 'Submitted From App');
        $show->field('processed_at', 'Processed At');
        
        return $show;
    }
}
```

**Add to**: `app/Admin/routes.php`

```php
$router->resource('vsla-meetings', VslaMeetingController::class);
```

---

## 🧪 TESTING GUIDE

### 1. Test Database Setup
```bash
php artisan migrate:status
# Verify vsla_meetings, vsla_meeting_attendance, vsla_action_plans exist
```

### 2. Test Model Creation
```bash
php artisan tinker
>>> $meeting = VslaMeeting::create([
  'local_id' => 'test-uuid-123',
  'cycle_id' => 1,
  'group_id' => 1,
  'created_by_id' => 1,
  'meeting_date' => '2025-12-11',
  'attendance_data' => json_encode([])
]);
>>> $meeting->id; // Should return ID
```

### 3. Test API Endpoint
```bash
# Create test meeting JSON
curl -X POST http://localhost/fao-ffs-mis-api/public/api/vsla-meetings/submit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "local_id": "test-meeting-001",
    "cycle_id": 1,
    "group_id": 1,
    "meeting_date": "2025-12-11",
    "attendance_data": [
      {"memberId": 1, "memberName": "John Doe", "isPresent": true}
    ],
    "transactions_data": [],
    "loans_data": [],
    "share_purchases_data": []
  }'
```

### 4. Test Mobile Sync
- Create a test meeting in mobile app
- Mark as ready to submit
- Trigger sync
- Verify meeting appears in web portal
- Verify local meeting is deleted

---

## 📊 SUCCESS METRICS

**Implementation Complete When**:
- [ ] All 3 database tables created
- [ ] All 3 models created with relationships
- [ ] MeetingProcessingService fully implemented
- [ ] API endpoints working (submit, index, show, reprocess)
- [ ] Mobile sync service implemented
- [ ] Admin interface functional
- [ ] End-to-end test passes:
  - Create meeting in mobile
  - Submit to server
  - Process successfully
  - View in admin panel
  - Verify all transactions created
  - Verify local meeting deleted

**Data Integrity Verified When**:
- [ ] Double-entry transactions balance
- [ ] All JSON data preserved
- [ ] No data loss during processing
- [ ] Error handling prevents partial saves
- [ ] Warnings tracked but don't block processing

---

## 🚀 QUICK START TO RESUME

1. **Run Migrations**:
```bash
cd /Applications/MAMP/htdocs/fao-ffs-mis-api
php artisan migrate --path=database/migrations/2025_12_11_000001_create_vsla_meetings_table.php
php artisan migrate --path=database/migrations/2025_12_11_000002_create_vsla_meeting_attendance_table.php
php artisan migrate --path=database/migrations/2025_12_11_000003_create_vsla_action_plans_table.php
```

2. **Create Models** (copy code from Phase 2 above)

3. **Create Processing Service** (see VSLA_MEETING_WEB_PORTAL_IMPLEMENTATION_PLAN.md)

4. **Create API Controller** (copy code from Phase 4 above)

5. **Test API** (use curl or Postman)

6. **Implement Mobile Sync** (copy code from Phase 5 above)

7. **Create Admin Interface** (copy code from Phase 6 above)

---

## 📁 FILES TO CREATE

### Backend (Laravel)
- [x] `database/migrations/2025_12_11_000001_create_vsla_meetings_table.php`
- [x] `database/migrations/2025_12_11_000002_create_vsla_meeting_attendance_table.php`
- [x] `database/migrations/2025_12_11_000003_create_vsla_action_plans_table.php`
- [ ] `app/Models/VslaMeeting.php`
- [ ] `app/Models/VslaMeetingAttendance.php`
- [ ] `app/Models/VslaActionPlan.php`
- [ ] `app/Services/MeetingProcessingService.php`
- [ ] `app/Http/Controllers/Api/VslaMeetingController.php`
- [ ] `app/Admin/Controllers/VslaMeetingController.php`

### Mobile (Flutter)
- [ ] `lib/services/vsla_meeting_sync_service.dart`
- [ ] Update: `lib/screens/vsla/MeetingSummaryScreen.dart` (add sync button)

### Documentation
- [x] `VSLA_MEETING_WEB_PORTAL_IMPLEMENTATION_PLAN.md`
- [ ] `VSLA_MEETING_WEB_PORTAL_TESTING_GUIDE.md` (create when testing)

---

## ⚠️ IMPORTANT NOTES

1. **Foreign Key Issue**: User table uses INT not BIGINT. All user foreign keys use `integer()->unsigned()`.

2. **Group ID Retrieval**: Mobile app needs to include `group_id` in meeting submission. Add logic to get group_id from cycle_id.

3. **Transaction Types**: Ensure offline uses exact same types as online:
   - `savings`
   - `welfare`
   - `social_fund`
   - `fine` / `penalty`

4. **JSON Structure**: Maintain exact offline structure in JSON fields for 100% data preservation.

5. **Error Handling**: All processing must be wrapped in DB transactions with rollback on error.

6. **Testing**: Test with real data before production deployment.

---

**Status**: Ready to implement Phase 2 (Models & Relationships)  
**Estimated Time**: 2-3 days for full implementation  
**Priority**: HIGH - Core VSLA functionality
