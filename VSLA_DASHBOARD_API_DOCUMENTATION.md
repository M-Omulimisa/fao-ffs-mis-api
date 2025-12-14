# VSLA Dashboard API Documentation

## Overview
This document describes the VSLA Dashboard API endpoints designed for mobile app integration. The dashboard supports two types of users with different permissions and data access levels.

## User Roles

### 1. Admin Users (Chairman, Secretary, Treasurer)
**Permissions:**
- Full access to all VSLA group data
- Can create and manage meetings
- Can view all members' transactions
- Can configure group settings
- Can view comprehensive group reports

**Identification:**
- User has role: `chairman`, `secretary`, or `treasurer`
- Check via `user.vsla_position` field or `user_type` field

### 2. Regular Members
**Permissions:**
- View only their own contributions
- View only their own loans
- View only their own fines and transactions
- View group-level aggregated statistics (read-only)
- Cannot create meetings or configure settings

**Identification:**
- User has role: `member` or no special role
- Default user type in VSLA group

---

## Dashboard Menu Structure

### For Admin Users
1. **Create/Continue Meeting** - Navigate to meeting creation/continuation
2. **Meetings** - List of all submitted meetings
3. **Attendance** - View attendance records
4. **Shares** - Share purchases and ownership
5. **Loans** - Loan management (requests, disbursements, repayments)
6. **Loan Transactions** - Detailed loan transaction history
7. **Action Plans** - Group action plans from meetings
8. **Members** - List of all group members
9. **Group Report** - Comprehensive group financial report
10. **Configurations** - Group settings and preferences

### For Regular Members
1. ~~Create/Continue Meeting~~ (Hidden)
2. ~~Meetings~~ (Hidden)
3. **Attendance** - View their own attendance
4. **Shares** - View their own shares
5. **Loans** - View their own loans only
6. **Loan Transactions** - View their own loan transactions
7. **Action Plans** - View group action plans (read-only)
8. **Members** - View list of group members
9. **Group Report** - View group statistics (aggregated only)
10. ~~Configurations~~ (Hidden)

---

## API Endpoints

### 1. Get Dashboard Data
**Endpoint:** `GET /api/vsla/dashboard`

**Purpose:** Fetch personalized dashboard data based on user role

**Request Parameters:**
```json
{
  "group_id": "integer (required)",
  "cycle_id": "integer (optional)"
}
```

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response for Admin Users:**
```json
{
  "code": 1,
  "message": "Dashboard data retrieved successfully",
  "data": {
    "user_role": "admin",
    "user_position": "chairman",
    "user_info": {
      "id": 123,
      "name": "John Doe",
      "phone": "0700000000",
      "vsla_position": "chairman"
    },
    "group_info": {
      "id": 5,
      "name": "Kampala Women VSLA",
      "code": "KWV-001",
      "total_members": 25,
      "active_members": 23
    },
    "cycle_info": {
      "id": 10,
      "name": "Cycle 2025 Q1",
      "start_date": "2025-01-01",
      "end_date": "2025-03-31",
      "status": "active",
      "weeks_elapsed": 8,
      "total_weeks": 12,
      "progress_percentage": 67
    },
    "financial_summary": {
      "total_savings": 2450000,
      "total_shares_value": 5000000,
      "total_loans_disbursed": 3500000,
      "total_loans_outstanding": 2800000,
      "total_fines_collected": 150000,
      "total_welfare": 200000,
      "total_social_fund": 180000,
      "cash_at_hand": 1230000,
      "formatted": {
        "total_savings": "UGX 2,450,000",
        "total_shares_value": "UGX 5,000,000",
        "total_loans_disbursed": "UGX 3,500,000",
        "total_loans_outstanding": "UGX 2,800,000",
        "total_fines_collected": "UGX 150,000",
        "total_welfare": "UGX 200,000",
        "total_social_fund": "UGX 180,000",
        "cash_at_hand": "UGX 1,230,000"
      }
    },
    "meeting_stats": {
      "total_meetings": 15,
      "last_meeting_date": "2025-12-10",
      "next_meeting_date": "2025-12-17",
      "has_ongoing_meeting": false,
      "ongoing_meeting_id": null
    },
    "loan_stats": {
      "active_loans": 12,
      "pending_requests": 3,
      "loans_disbursed_this_cycle": 18,
      "total_disbursed_amount": 3500000,
      "total_repaid_amount": 700000
    },
    "member_stats": {
      "total_members": 25,
      "active_members": 23,
      "inactive_members": 2,
      "members_with_savings": 23,
      "members_with_loans": 12
    },
    "menu_items": [
      {
        "id": "create_meeting",
        "title": "Create/Continue Meeting",
        "icon": "calendar_today",
        "route": "/vsla/meetings/hub",
        "visible": true,
        "enabled": true,
        "badge": null
      },
      {
        "id": "meetings",
        "title": "Meetings",
        "icon": "event",
        "route": "/vsla/meetings",
        "visible": true,
        "enabled": true,
        "badge": "15"
      },
      {
        "id": "attendance",
        "title": "Attendance",
        "icon": "how_to_reg",
        "route": "/vsla/attendance",
        "visible": true,
        "enabled": true,
        "badge": null
      },
      {
        "id": "shares",
        "title": "Shares",
        "icon": "pie_chart",
        "route": "/vsla/shares",
        "visible": true,
        "enabled": true,
        "badge": null
      },
      {
        "id": "loans",
        "title": "Loans",
        "icon": "account_balance",
        "route": "/vsla/loans",
        "visible": true,
        "enabled": true,
        "badge": "12"
      },
      {
        "id": "loan_transactions",
        "title": "Loan Transactions",
        "icon": "receipt_long",
        "route": "/vsla/loan-transactions",
        "visible": true,
        "enabled": true,
        "badge": null
      },
      {
        "id": "action_plans",
        "title": "Action Plans",
        "icon": "assignment",
        "route": "/vsla/action-plans",
        "visible": true,
        "enabled": true,
        "badge": null
      },
      {
        "id": "members",
        "title": "Members",
        "icon": "people",
        "route": "/vsla/members",
        "visible": true,
        "enabled": true,
        "badge": "25"
      },
      {
        "id": "group_report",
        "title": "Group Report",
        "icon": "assessment",
        "route": "/vsla/reports",
        "visible": true,
        "enabled": true,
        "badge": null
      },
      {
        "id": "configurations",
        "title": "Configurations",
        "icon": "settings",
        "route": "/vsla/settings",
        "visible": true,
        "enabled": true,
        "badge": null
      }
    ]
  }
}
```

**Response for Regular Members:**
```json
{
  "code": 1,
  "message": "Dashboard data retrieved successfully",
  "data": {
    "user_role": "member",
    "user_position": "member",
    "user_info": {
      "id": 456,
      "name": "Jane Smith",
      "phone": "0701111111",
      "vsla_position": "member"
    },
    "group_info": {
      "id": 5,
      "name": "Kampala Women VSLA",
      "code": "KWV-001",
      "total_members": 25,
      "active_members": 23
    },
    "cycle_info": {
      "id": 10,
      "name": "Cycle 2025 Q1",
      "start_date": "2025-01-01",
      "end_date": "2025-03-31",
      "status": "active",
      "weeks_elapsed": 8,
      "total_weeks": 12,
      "progress_percentage": 67
    },
    "my_summary": {
      "my_savings": 120000,
      "my_shares_value": 250000,
      "my_shares_count": 5,
      "my_active_loans": 1,
      "my_loan_amount": 150000,
      "my_loan_balance": 120000,
      "my_fines_paid": 5000,
      "my_welfare": 10000,
      "my_social_fund": 8000,
      "my_attendance_rate": 93.3,
      "formatted": {
        "my_savings": "UGX 120,000",
        "my_shares_value": "UGX 250,000",
        "my_loan_amount": "UGX 150,000",
        "my_loan_balance": "UGX 120,000",
        "my_fines_paid": "UGX 5,000",
        "my_welfare": "UGX 10,000",
        "my_social_fund": "UGX 8,000"
      }
    },
    "group_summary": {
      "total_savings": 2450000,
      "total_members": 25,
      "cash_at_hand": 1230000,
      "formatted": {
        "total_savings": "UGX 2,450,000",
        "cash_at_hand": "UGX 1,230,000"
      }
    },
    "menu_items": [
      {
        "id": "attendance",
        "title": "My Attendance",
        "icon": "how_to_reg",
        "route": "/vsla/my-attendance",
        "visible": true,
        "enabled": true,
        "badge": null
      },
      {
        "id": "shares",
        "title": "My Shares",
        "icon": "pie_chart",
        "route": "/vsla/my-shares",
        "visible": true,
        "enabled": true,
        "badge": "5"
      },
      {
        "id": "loans",
        "title": "My Loans",
        "icon": "account_balance",
        "route": "/vsla/my-loans",
        "visible": true,
        "enabled": true,
        "badge": "1"
      },
      {
        "id": "loan_transactions",
        "title": "My Loan Transactions",
        "icon": "receipt_long",
        "route": "/vsla/my-loan-transactions",
        "visible": true,
        "enabled": true,
        "badge": null
      },
      {
        "id": "action_plans",
        "title": "Action Plans",
        "icon": "assignment",
        "route": "/vsla/action-plans",
        "visible": true,
        "enabled": true,
        "badge": null
      },
      {
        "id": "members",
        "title": "Members",
        "icon": "people",
        "route": "/vsla/members",
        "visible": true,
        "enabled": true,
        "badge": "25"
      },
      {
        "id": "group_report",
        "title": "Group Report",
        "icon": "assessment",
        "route": "/vsla/reports",
        "visible": true,
        "enabled": true,
        "badge": null
      }
    ]
  }
}
```

---

## Implementation Notes for Mobile Developers

### 1. User Role Detection
```dart
bool isAdmin(User user) {
  final adminPositions = ['chairman', 'secretary', 'treasurer'];
  return adminPositions.contains(user.vslaPosition?.toLowerCase());
}
```

### 2. Menu Rendering
```dart
// The API returns menu_items array - simply render it
Widget buildDashboardMenu(List<MenuItem> menuItems) {
  return ListView.builder(
    itemCount: menuItems.length,
    itemBuilder: (context, index) {
      final item = menuItems[index];
      if (!item.visible) return SizedBox.shrink();
      
      return ListTile(
        leading: Icon(getIconData(item.icon)),
        title: Text(item.title), // Full title provided by API
        trailing: item.badge != null 
          ? Badge(label: Text(item.badge))
          : null,
        enabled: item.enabled,
        onTap: item.enabled 
          ? () => navigateToRoute(item.route)
          : () => showToast('Coming Soon'),
      );
    },
  );
}
```

### 3. Data Access Pattern
- **Admin users**: Can access all endpoints without user_id filtering
- **Regular members**: Must include `user_id={current_user_id}` in requests
- The API will automatically filter data based on authenticated user

### 4. Dashboard Display
- Show user info at top
- For admin: Show group-level statistics
- For members: Show personal statistics + limited group stats
- Render menu items dynamically from API response

---

## Additional Endpoints

### 2. Get My Attendance (Regular Members)
**Endpoint:** `GET /api/vsla/my-attendance`

**Parameters:**
```json
{
  "group_id": "integer (required)",
  "cycle_id": "integer (optional)"
}
```

**Response:**
```json
{
  "code": 1,
  "message": "Your attendance records retrieved",
  "data": {
    "total_meetings": 15,
    "attended": 14,
    "absent": 1,
    "attendance_rate": 93.3,
    "records": [
      {
        "meeting_id": 1,
        "meeting_number": 1,
        "meeting_date": "2025-12-01",
        "status": "present"
      }
    ]
  }
}
```

### 3. Get My Shares (Regular Members)
**Endpoint:** `GET /api/vsla/my-shares`

**Parameters:**
```json
{
  "group_id": "integer (required)",
  "cycle_id": "integer (optional)"
}
```

**Response:**
```json
{
  "code": 1,
  "message": "Your shares retrieved",
  "data": {
    "total_shares": 5,
    "total_value": 250000,
    "share_price": 50000,
    "formatted_value": "UGX 250,000",
    "purchases": [
      {
        "id": 1,
        "meeting_id": 2,
        "meeting_number": 2,
        "purchase_date": "2025-12-05",
        "number_of_shares": 2,
        "amount_paid": 100000,
        "formatted_amount": "UGX 100,000"
      }
    ]
  }
}
```

### 4. Get My Loans (Regular Members)
**Endpoint:** `GET /api/vsla/my-loans`

**Parameters:**
```json
{
  "group_id": "integer (required)",
  "cycle_id": "integer (optional)"
}
```

**Response:**
```json
{
  "code": 1,
  "message": "Your loans retrieved",
  "data": {
    "active_loans": 1,
    "total_borrowed": 150000,
    "total_repaid": 30000,
    "total_balance": 120000,
    "loans": [
      {
        "id": 1,
        "loan_amount": 150000,
        "interest_rate": 10,
        "total_amount_due": 165000,
        "balance": 120000,
        "status": "active",
        "disbursement_date": "2025-11-15",
        "due_date": "2026-02-15",
        "formatted": {
          "loan_amount": "UGX 150,000",
          "total_due": "UGX 165,000",
          "balance": "UGX 120,000"
        }
      }
    ]
  }
}
```

---

## Security Considerations

1. **Authentication Required**: All endpoints require valid Bearer token
2. **Data Isolation**: Regular members can only access their own data
3. **Role Validation**: Admin actions require position verification
4. **Group Membership**: Users can only access data from groups they belong to

---

## Error Responses

### Unauthorized Access
```json
{
  "code": 0,
  "message": "Unauthorized access. Admin privileges required.",
  "data": null
}
```

### Invalid Parameters
```json
{
  "code": 0,
  "message": "Validation failed",
  "data": {
    "errors": {
      "group_id": ["The group_id field is required."]
    }
  }
}
```

### Not Found
```json
{
  "code": 0,
  "message": "VSLA group not found or you are not a member",
  "data": null
}
```

---

## Testing Recommendations

1. **Test with Admin User**: Verify all menu items appear
2. **Test with Regular Member**: Verify restricted menu and data
3. **Test Role Switching**: If user changes role, dashboard should update
4. **Test Empty States**: When no data exists, show appropriate messages
5. **Test Offline Mode**: Cache dashboard data for offline viewing

---

## Version History

- **v1.0** (2025-12-15): Initial dashboard API design
  - Support for admin and regular member roles
  - Dynamic menu generation
  - Personalized data based on role
  - Comprehensive financial summaries
