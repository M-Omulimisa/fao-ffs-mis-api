# VSLA Shareout Module - Complete Documentation

## Overview
The VSLA Shareout module implements a secure, state-driven workflow for closing savings cycles and distributing funds to members. It enforces a strict state machine with validation at every step.

## State Machine

### States
1. **draft** - Shareout initiated, awaiting calculation
2. **calculated** - Distributions calculated, ready for review
3. **approved** - Approved by admin, ready to complete
4. **completed** - Finalized, cycle closed, no further actions
5. **cancelled** - Cancelled, no further actions

### State Transitions
```
draft → calculated (via calculate API)
calculated → approved (via approve API)
calculated → cancelled (via cancel API)
approved → completed (via complete API)
approved → cancelled (via cancel API)
```

### Permissions Matrix
| State | Can Recalculate | Can Approve | Can Complete | Can Cancel |
|-------|----------------|-------------|--------------|------------|
| draft | ✅ | ❌ | ❌ | ✅ |
| calculated | ✅ | ✅ | ❌ | ✅ |
| approved | ❌ | ❌ | ✅ | ✅ |
| completed | ❌ | ❌ | ❌ | ❌ |
| cancelled | ❌ | ❌ | ❌ | ❌ |

## API Endpoints

### 1. Get Available Cycles
**GET** `/api/vsla/shareouts/available-cycles`

Returns cycles that can be shared out (active, not already closed).

**Response:**
```json
{
  "success": true,
  "code": 1,
  "data": [
    {
      "cycle_id": 7,
      "cycle_name": "Cycle 2024-2025",
      "group_id": 10,
      "share_value": 1000,
      "has_existing_shareout": false
    }
  ]
}
```

### 2. Get Shareout History
**GET** `/api/vsla/shareouts/history`

Returns all shareouts for the user's group.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "shareout_id": 3,
      "cycle_name": "TEST Cycle 1",
      "status": "calculated",
      "total_members": 4,
      "total_actual_payout": 39000
    }
  ]
}
```

### 3. Initiate Shareout
**POST** `/api/vsla/shareouts/initiate`

**Body:**
```json
{
  "cycle_id": 7
}
```

**Validation:**
- Cycle must exist and be active
- No existing non-cancelled shareout for this cycle
- User must belong to the cycle's group

### 4. Calculate Distributions
**POST** `/api/vsla/shareouts/{shareout_id}/calculate`

**Validation:**
- Shareout must exist
- Status must be 'draft' or 'calculated'
- Cycle must have members with share purchases
- Deletes existing distributions before recalculating

**Process:**
1. Validates cycle has members with shares
2. Calculates financial totals
3. Distributes funds proportionally
4. Accounts for outstanding loans
5. Updates shareout status to 'calculated'

### 5. Get Member Distributions
**GET** `/api/vsla/shareouts/{shareout_id}/distributions`

Returns detailed breakdown for each member.

### 6. Get Shareout Summary
**GET** `/api/vsla/shareouts/{shareout_id}/summary`

Returns complete shareout information including financial summary.

### 7. Approve Shareout
**POST** `/api/vsla/shareouts/{shareout_id}/approve`

**Validation:**
- Status must be 'calculated'
- User must be authenticated

**Process:**
1. Updates status to 'approved'
2. Records approval timestamp and user ID
3. Enables completion action

### 8. Complete Shareout
**POST** `/api/vsla/shareouts/{shareout_id}/complete`

**Validation:**
- Status must be 'approved' (or 'calculated' with auto-approve)
- User must be authenticated

**Process:**
1. Auto-approves if status is 'calculated'
2. Marks all distributions as paid
3. Updates shareout status to 'completed'
4. Closes the cycle (is_active_cycle = 'No', status = 'completed')
5. Records completion timestamp and user ID

**CRITICAL:** This action:
- ✅ Closes the cycle permanently
- ✅ Marks distributions as paid
- ✅ Cannot be undone

### 9. Cancel Shareout
**POST** `/api/vsla/shareouts/{shareout_id}/cancel`

**Validation:**
- Status must NOT be 'completed' or 'cancelled'

**Process:**
1. Updates status to 'cancelled'
2. Does NOT delete distributions (audit trail)
3. Does NOT close the cycle

## Mobile App Implementation

### Services Layer
**File:** `lib/services/vsla_shareout_service.dart`

All API calls go through this service with:
- ✅ Type-safe response parsing
- ✅ Error handling with stack traces
- ✅ Consistent return format
- ✅ Debug logging

### Screens

#### ShareoutHistoryScreen
**File:** `lib/screens/vsla/configurations/ShareoutHistoryScreen.dart`

- Lists all shareouts for user's group
- Color-coded status badges
- Pull-to-refresh
- FAB to create new shareout
- Navigates to details on tap

#### ShareoutWizardScreen
**File:** `lib/screens/vsla/configurations/ShareoutWizardScreen.dart`

6-step wizard:
1. Select Cycle
2. Initiate Shareout
3. Calculate Distributions
4. View Member Distributions
5. Review Summary
6. Complete Shareout

#### ShareoutDetailsScreen
**File:** `lib/screens/vsla/configurations/ShareoutDetailsScreen.dart`

- Complete financial summary
- Member-by-member breakdown
- Action buttons based on permissions
- Confirmation dialogs for all actions
- Loading indicators
- Success/error feedback

### Models
**File:** `lib/models/vsla_shareout_models.dart`

- `AvailableCycle` - Cycles available for shareout
- `VslaShareoutSummary` - Complete shareout information
- `MemberDistribution` - Individual member payout details

## Error Handling

### Backend Validation
All endpoints validate:
1. Authentication (EnsureTokenIsValid middleware)
2. User group membership
3. Shareout state transitions
4. Data integrity

### Mobile Error Handling
- Network errors caught and displayed
- Validation errors shown to user
- Stack traces logged for debugging
- User-friendly error messages

## Security

### Authentication
- JWT token required for all endpoints
- Token validated by EnsureTokenIsValid middleware
- User ID extracted from token

### Authorization
- Users can only access shareouts for their group
- State machine prevents invalid transitions
- Completed shareouts cannot be modified

### Data Integrity
- Foreign key constraints
- Transaction wrapping for multi-step operations
- Soft deletes for audit trail
- Timestamps for all state changes

## Testing Checklist

### Backend Tests
- [ ] Can create shareout for valid cycle
- [ ] Cannot create duplicate shareout
- [ ] Calculation requires members with shares
- [ ] State transitions follow rules
- [ ] Completed shareout closes cycle
- [ ] Cancelled shareout does not close cycle
- [ ] Group isolation works

### Frontend Tests
- [ ] History loads correctly
- [ ] Can initiate new shareout
- [ ] Wizard progresses through all steps
- [ ] Buttons show based on permissions
- [ ] Confirmation dialogs appear
- [ ] Success messages show
- [ ] Error messages show
- [ ] Completed shareouts hide buttons
- [ ] Data refreshes after actions

## Common Issues & Solutions

### Issue: "No members with shares found"
**Solution:** Ensure members have purchased shares in the cycle using the shares module.

### Issue: "Shareout must be approved before completion"
**Solution:** Click "Approve Shareout" button first. Status must be 'approved'.

### Issue: State contradictions
**Solution:** Backend enforces state machine. Check `canApprove()`, `canComplete()` methods in VslaShareout model.

### Issue: 401 Unauthorized
**Solution:** Ensure token is valid and user belongs to the group.

## Database Schema

### vsla_shareouts table
- `id` - Primary key
- `cycle_id` - Foreign key to projects
- `group_id` - Foreign key to groups
- `status` - enum('draft', 'calculated', 'approved', 'completed', 'cancelled')
- `total_*` - Financial totals
- `*_at` - Timestamps for state changes
- `*_by_id` - User IDs for audit trail

### vsla_shareout_distributions table
- `id` - Primary key
- `shareout_id` - Foreign key
- `member_id` - Foreign key to users
- `member_*` - Member contributions
- `final_payout` - Amount to be paid
- `payment_status` - enum('pending', 'paid')
- Unique constraint: (shareout_id, member_id)

## Maintenance

### Adding New States
1. Update enum in database migration
2. Add state to VslaShareout model
3. Update `can*()` methods
4. Update frontend permissions
5. Update documentation

### Modifying Calculations
1. Update ShareoutCalculationService
2. Test with various scenarios
3. Verify totals match expectations
4. Check edge cases (no loans, no fines, etc.)

## Support

For issues or questions:
1. Check debug logs in console
2. Verify state machine rules
3. Check API response format
4. Review error messages
5. Consult this documentation

---

**Last Updated:** January 10, 2026
**Version:** 1.0.0
**Status:** Production Ready ✅
