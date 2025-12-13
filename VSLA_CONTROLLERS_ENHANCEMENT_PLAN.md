# VSLA Admin Controllers Enhancement Plan

**Date:** December 13, 2025  
**Status:** In Progress

---

## Controller Enhancement Hierarchy

### 1. FfsGroupController (VSLA Groups) ✅ IN PROGRESS
**URL:** `/admin/ffs-vslas`

**Enhancements:**
- ✅ Add VSLA-specific metrics (active cycles, total savings, group balance)
- ✅ Show links to cycles and meetings
- ✅ Filter by district, status, facilitator
- ✅ Display member statistics with gender breakdown
- ⏳ Add group balance calculation from AccountTransactions
- ⏳ Show count of active cycles and meetings

### 2. ProjectController (VSLA Cycles)
**URL:** `/admin/cycles` or `/admin/projects`

**Enhancements:**
- Filter by group (dropdown)
- Filter by status, date range
- Show group name with link
- Display share value, total shares sold
- Show total savings (from AccountTransactions)
- Show active loans count
- Display group balance
- Add links to meetings, loans, transactions

### 3. VslaMeetingController
**URL:** `/admin/vsla-meetings`

**Enhancements:**
- Filter by cycle (dropdown)
- Filter by group (dropdown)
- Filter by date range, status
- Show meeting number, cycle name, group name
- Display total savings collected
- Show total loans disbursed
- Display attendance rate (present/total)
- Add links to cycle, group, attendance records
- Show transaction summary

### 4. VslaLoanController  
**URL:** `/admin/vsla-loans`

**Enhancements:**
- Filter by cycle (dropdown)
- Filter by group (dropdown)
- Filter by borrower (dropdown)
- Filter by status
- Show borrower name with link
- Display group name with link
- Show cycle name with link
- Display loan amount, interest, balance
- Show payment progress bar
- Add links to loan transactions
- Color-code by status

### 5. AccountTransactionController
**URL:** `/admin/account-transactions`

**Enhancements:**
- Filter by user (dropdown - members)
- Filter by cycle (dropdown)
- Filter by group (dropdown)
- Filter by type, source
- Filter by date range
- Show user/group name
- Display cycle name
- Show amount with color-coding (debit/credit)
- Display transaction type and source
- Show running balance
- Add links to related records

### 6. LoanTransactionController ✅ COMPLETED
**URL:** `/admin/loan-transactions`

**Already Enhanced:**
- Loan filter (dropdown)
- Borrower column
- Group column
- Type filter
- Amount color-coding
- Date filters

### 7. VslaActionPlanController
**URL:** `/admin/vsla-action-plans`

**Enhancements:**
- Filter by cycle (dropdown)
- Filter by group (dropdown)
- Filter by assigned member (dropdown)
- Filter by status, due date
- Show cycle name with link
- Display group name with link
- Show assigned member with link
- Display due date with overdue indicator
- Color-code by status

### 8. VslaMeetingAttendanceController
**URL:** `/admin/vsla-meeting-attendance`

**Enhancements:**
- Filter by meeting (dropdown)
- Filter by cycle (dropdown)
- Filter by group (dropdown)
- Filter by member (dropdown)
- Show meeting details with link
- Display member name with link
- Show group name
- Display attendance status
- Show arrival time

---

## Implementation Status

| Controller | Filters | Metrics | Links | Status |
|-----------|---------|---------|-------|--------|
| FfsGroup | ✅ | ⏳ | ⏳ | In Progress |
| Project (Cycles) | ❌ | ❌ | ❌ | Pending |
| VslaMeeting | ❌ | ❌ | ❌ | Pending |
| VslaLoan | ❌ | ❌ | ❌ | Pending |
| AccountTransaction | ❌ | ❌ | ❌ | Pending |
| LoanTransaction | ✅ | ✅ | ✅ | Complete |
| VslaActionPlan | ❌ | ❌ | ❌ | Pending |
| VslaMeetingAttendance | ❌ | ❌ | ❌ | Pending |

---

## Next Steps

1. Complete FfsGroupController VSLA metrics
2. Enhance ProjectController for cycles
3. Enhance VslaMeetingController
4. Continue systematically through all controllers
5. Test each enhancement
6. Document changes
