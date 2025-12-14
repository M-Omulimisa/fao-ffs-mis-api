# VSLA Dashboard - Mobile App Implementation Guide

**For**: Flutter Mobile Developers  
**Date**: December 15, 2024  
**Backend Status**: ✅ Complete and Ready

---

## Quick Start

The backend API for the new VSLA dashboard is ready. Follow these steps to integrate it into the mobile app.

---

## 1. API Endpoint

**New Endpoint**: `GET /api/vsla/dashboard`

**Old Endpoint** (to be replaced): `GET /api/vsla/dashboard-summary`

### Request
```
GET /api/vsla/dashboard?group_id={groupId}&cycle_id={cycleId}
Authorization: Bearer {token}
```

**Parameters**:
- `group_id` (required) - The VSLA group ID
- `cycle_id` (optional) - Filter data by specific cycle

### Response Structure

The response differs based on user role:

**Admin Users** (chairman, secretary, treasurer):
```json
{
  "user_role": "admin",
  "financial_summary": { /* complete group stats */ },
  "meeting_stats": { /* all meetings */ },
  "loan_stats": { /* all loans */ },
  "member_stats": { /* all members */ },
  "menu_items": [ /* 10 items */ ]
}
```

**Regular Members**:
```json
{
  "user_role": "member",
  "my_summary": { /* personal stats only */ },
  "group_summary": { /* limited aggregates */ },
  "menu_items": [ /* 7 items */ ]
}
```

---

## 2. File to Update

**Primary File**: `lib/screens/main_app/tabs/vsla_tab.dart`

### Current Structure (Lines 735-1100)
```dart
_buildVslaDashboard() {
  return Column(
    children: [
      _buildGroupOverviewCards(),  // Keep this (update data source)
      _buildVslaQuickActions(),     // DELETE THIS SECTION
      _buildRecentActivities(),     // Optional: Keep or update
    ],
  );
}
```

### New Structure
```dart
_buildVslaDashboard() {
  return Column(
    children: [
      _buildUserInfo(),              // Keep user info at top
      _buildOverviewCards(),         // Personalized cards based on role
      _buildDynamicMenu(),           // NEW: Menu from API
      _buildRecentActivities(),      // Optional
    ],
  );
}
```

---

## 3. Step-by-Step Implementation

### Step 1: Create Data Models

Create `lib/models/vsla_dashboard_models.dart`:

```dart
class VslaDashboardResponse {
  final String userRole;
  final String userPosition;
  final UserInfo userInfo;
  final GroupInfo groupInfo;
  final CycleInfo? cycleInfo;
  
  // Admin only
  final FinancialSummary? financialSummary;
  final MeetingStats? meetingStats;
  final LoanStats? loanStats;
  final MemberStats? memberStats;
  
  // Member only
  final MemberSummary? mySummary;
  final GroupSummary? groupSummary;
  
  // Both
  final List<MenuItem> menuItems;

  VslaDashboardResponse.fromJson(Map<String, dynamic> json)
      : userRole = json['user_role'],
        userPosition = json['user_position'],
        userInfo = UserInfo.fromJson(json['user_info']),
        groupInfo = GroupInfo.fromJson(json['group_info']),
        cycleInfo = json['cycle_info'] != null 
            ? CycleInfo.fromJson(json['cycle_info']) 
            : null,
        financialSummary = json['financial_summary'] != null 
            ? FinancialSummary.fromJson(json['financial_summary']) 
            : null,
        mySummary = json['my_summary'] != null 
            ? MemberSummary.fromJson(json['my_summary']) 
            : null,
        groupSummary = json['group_summary'] != null 
            ? GroupSummary.fromJson(json['group_summary']) 
            : null,
        menuItems = (json['menu_items'] as List)
            .map((item) => MenuItem.fromJson(item))
            .toList();
}

class MenuItem {
  final String id;
  final String title;
  final String icon;
  final String route;
  final bool visible;
  final bool enabled;
  final String? badge;

  MenuItem.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        title = json['title'],
        icon = json['icon'],
        route = json['route'],
        visible = json['visible'],
        enabled = json['enabled'],
        badge = json['badge'];
}

class UserInfo {
  final int id;
  final String name;
  final String phone;
  final String vslaPosition;

  UserInfo.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        name = json['name'],
        phone = json['phone'],
        vslaPosition = json['vsla_position'];
}

class GroupInfo {
  final int id;
  final String name;
  final String code;
  final int totalMembers;
  final int activeMembers;

  GroupInfo.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        name = json['name'],
        code = json['code'],
        totalMembers = json['total_members'],
        activeMembers = json['active_members'];
}

class CycleInfo {
  final int id;
  final String name;
  final String? startDate;
  final String? endDate;
  final String status;
  final int weeksElapsed;
  final int totalWeeks;
  final int progressPercentage;

  CycleInfo.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        name = json['name'],
        startDate = json['start_date'],
        endDate = json['end_date'],
        status = json['status'],
        weeksElapsed = json['weeks_elapsed'],
        totalWeeks = json['total_weeks'],
        progressPercentage = json['progress_percentage'];
}

// Add more models as needed...
```

### Step 2: Update API Service

In `lib/services/vsla_transaction_api.dart`, add:

```dart
Future<VslaDashboardResponse> getDashboard({
  required int groupId,
  int? cycleId,
}) async {
  try {
    final queryParams = {
      'group_id': groupId.toString(),
      if (cycleId != null) 'cycle_id': cycleId.toString(),
    };
    
    final response = await get(
      '/vsla/dashboard',
      queryParameters: queryParams,
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return VslaDashboardResponse.fromJson(data['data']);
    } else {
      throw Exception('Failed to load dashboard');
    }
  } catch (e) {
    print('Error fetching dashboard: $e');
    rethrow;
  }
}
```

### Step 3: Update Dashboard Widget

In `lib/screens/main_app/tabs/vsla_tab.dart`:

```dart
class _VslaTabState extends State<VslaTab> {
  VslaDashboardResponse? _dashboardData;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    setState(() => _isLoading = true);
    
    try {
      final dashboard = await VslaTransactionApi().getDashboard(
        groupId: widget.groupId,
        cycleId: widget.cycleId,
      );
      
      setState(() {
        _dashboardData = dashboard;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      _showErrorSnackbar('Failed to load dashboard: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Center(child: CircularProgressIndicator());
    }

    if (_dashboardData == null) {
      return Center(child: Text('No data available'));
    }

    return RefreshIndicator(
      onRefresh: _loadDashboard,
      child: SingleChildScrollView(
        child: Column(
          children: [
            _buildUserInfo(),
            _buildOverviewCards(),
            _buildDynamicMenu(),
          ],
        ),
      ),
    );
  }

  Widget _buildUserInfo() {
    final user = _dashboardData!.userInfo;
    final group = _dashboardData!.groupInfo;
    
    return Card(
      child: ListTile(
        leading: CircleAvatar(
          child: Text(user.name[0].toUpperCase()),
        ),
        title: Text(user.name),
        subtitle: Text('${user.vslaPosition} - ${group.name}'),
        trailing: Text(user.phone),
      ),
    );
  }

  Widget _buildOverviewCards() {
    final isAdmin = _dashboardData!.userRole == 'admin';
    
    if (isAdmin) {
      // Show group stats for admin
      final financial = _dashboardData!.financialSummary!;
      return _buildAdminOverview(financial);
    } else {
      // Show personal stats for member
      final mySummary = _dashboardData!.mySummary!;
      return _buildMemberOverview(mySummary);
    }
  }

  Widget _buildDynamicMenu() {
    return Padding(
      padding: EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Menu',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 12),
          GridView.builder(
            shrinkWrap: true,
            physics: NeverScrollableScrollPhysics(),
            gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              childAspectRatio: 1.3,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
            ),
            itemCount: _dashboardData!.menuItems.length,
            itemBuilder: (context, index) {
              final item = _dashboardData!.menuItems[index];
              return _buildMenuItem(item);
            },
          ),
        ],
      ),
    );
  }

  Widget _buildMenuItem(MenuItem item) {
    return InkWell(
      onTap: () {
        if (item.enabled) {
          Navigator.pushNamed(context, item.route);
        } else {
          _showComingSoonToast();
        }
      },
      child: Card(
        color: item.enabled ? Colors.white : Colors.grey[200],
        child: Stack(
          children: [
            Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    _getIconData(item.icon),
                    size: 32,
                    color: item.enabled ? Colors.blue : Colors.grey,
                  ),
                  SizedBox(height: 8),
                  Text(
                    item.title,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                      color: item.enabled ? Colors.black87 : Colors.grey,
                    ),
                  ),
                ],
              ),
            ),
            if (item.badge != null)
              Positioned(
                top: 8,
                right: 8,
                child: Container(
                  padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: Colors.red,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    item.badge!,
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  IconData _getIconData(String iconName) {
    // Map icon names to Flutter Icons
    switch (iconName) {
      case 'calendar_today':
        return Icons.calendar_today;
      case 'event':
        return Icons.event;
      case 'how_to_reg':
        return Icons.how_to_reg;
      case 'pie_chart':
        return Icons.pie_chart;
      case 'account_balance':
        return Icons.account_balance;
      case 'receipt_long':
        return Icons.receipt_long;
      case 'assignment':
        return Icons.assignment;
      case 'people':
        return Icons.people;
      case 'assessment':
        return Icons.assessment;
      case 'settings':
        return Icons.settings;
      default:
        return Icons.dashboard;
    }
  }

  void _showComingSoonToast() {
    Get.snackbar(
      'Coming Soon',
      'This feature is under development',
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: Colors.orange,
      colorText: Colors.white,
      duration: Duration(seconds: 2),
    );
  }

  void _showErrorSnackbar(String message) {
    Get.snackbar(
      'Error',
      message,
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: Colors.red,
      colorText: Colors.white,
      duration: Duration(seconds: 3),
    );
  }
}
```

### Step 4: Remove Old Code

**DELETE** these sections from `vsla_tab.dart`:

```dart
// DELETE: The entire _buildVslaQuickActions() method (around lines 900-1100)
// DELETE: Hardcoded menu items
// DELETE: Quick links section
```

**KEEP** only:
- User info display
- Overview cards (update data source)
- Dynamic menu (new implementation)

---

## 4. Menu Items Reference

### Admin Users See (10 Items)

1. **Create Meeting** / **Continue Meeting** - `/vsla/meetings/hub` ✅ Enabled
2. **Meetings** - `/vsla/meetings` ✅ Enabled
3. **Attendance** - `/vsla/attendance` ⏳ Coming Soon
4. **Shares** - `/vsla/shares` ⏳ Coming Soon
5. **Loans** - `/vsla/loans` ✅ Enabled
6. **Loan Transactions** - `/vsla/loan-transactions` ⏳ Coming Soon
7. **Action Plans** - `/vsla/action-plans` ⏳ Coming Soon
8. **Members** - `/vsla/members` ✅ Enabled
9. **Group Report** - `/vsla/reports` ⏳ Coming Soon
10. **Configurations** - `/vsla/settings` ⏳ Coming Soon

### Regular Members See (7 Items)

1. **My Attendance** - `/vsla/my-attendance` ⏳ Coming Soon
2. **My Shares** - `/vsla/my-shares` ⏳ Coming Soon
3. **My Loans** - `/vsla/my-loans` ✅ Enabled
4. **My Loan Transactions** - `/vsla/my-loan-transactions` ⏳ Coming Soon
5. **Action Plans** - `/vsla/action-plans` ⏳ Coming Soon
6. **Members** - `/vsla/members` ✅ Enabled
7. **Group Report** - `/vsla/reports` ⏳ Coming Soon

---

## 5. Testing Checklist

### Before Deployment
- [ ] Test with admin user (chairman)
- [ ] Test with secretary user
- [ ] Test with treasurer user
- [ ] Test with regular member
- [ ] Verify correct menu items for each role
- [ ] Verify admin sees group stats
- [ ] Verify member sees only personal stats
- [ ] Test "Coming Soon" toast for disabled items
- [ ] Test enabled routes navigate correctly
- [ ] Test pull-to-refresh functionality
- [ ] Test error handling (no internet, invalid token)
- [ ] Test with different group IDs
- [ ] Test with and without cycle_id parameter

---

## 6. Key Changes Summary

### What's Different

**Old Dashboard**:
- Hardcoded quick actions
- Same view for all users
- No role-based filtering
- Static menu items

**New Dashboard**:
- Dynamic menu from API
- Different views for admin vs members
- Role-based data filtering
- Menu items have enable/disable state
- "Coming Soon" toasts for future features
- Badge indicators on menu items

### What to Remove

❌ DELETE:
- `_buildVslaQuickActions()` method
- Hardcoded menu arrays
- Quick links section
- Static route definitions

✅ KEEP:
- User info display
- Overview cards (update data source)
- Recent activities (optional)

---

## 7. Common Issues & Solutions

### Issue: Menu items not showing
**Solution**: Check that `menu_items` array is being parsed correctly from JSON

### Issue: Wrong menu items for user
**Solution**: Verify user's `vsla_position` field in database is set correctly

### Issue: "Coming Soon" not showing
**Solution**: Check that `item.enabled` is false and toast method is called

### Issue: Icons not displaying
**Solution**: Verify `_getIconData()` method has mapping for all icon names

---

## 8. Next Steps After Integration

1. Test with real users
2. Gather feedback on UI/UX
3. Implement disabled features progressively
4. Add offline support for dashboard data
5. Implement push notifications for meetings

---

## 9. Support & Documentation

**Full API Documentation**: See `VSLA_DASHBOARD_API_DOCUMENTATION.md`

**Backend Implementation**: See `VSLA_DASHBOARD_IMPLEMENTATION_COMPLETE.md`

**API Endpoint**: `GET /api/vsla/dashboard`

**Controller**: `app/Http/Controllers/Api/VslaDashboardController.php`

---

## 10. Quick Reference

### API Call Example

```dart
// Call the dashboard API
final response = await VslaTransactionApi().getDashboard(
  groupId: currentGroup.id,
  cycleId: currentCycle?.id,
);

// Check user role
if (response.userRole == 'admin') {
  // Show admin view
  final financial = response.financialSummary!;
  _displayAdminDashboard(financial);
} else {
  // Show member view
  final personal = response.mySummary!;
  _displayMemberDashboard(personal);
}

// Render menu dynamically
for (var item in response.menuItems) {
  _addMenuItem(
    title: item.title,
    icon: item.icon,
    enabled: item.enabled,
    onTap: () => item.enabled 
      ? navigateTo(item.route) 
      : showComingSoon(),
  );
}
```

---

**Ready to implement?** Start with Step 1 (Create Models) and work your way through!

**Questions?** Review the full API documentation or contact the backend team.

---

**Document Version**: 1.0  
**Last Updated**: December 15, 2024  
**Status**: ✅ Backend Ready | ⏳ Awaiting Mobile Integration
