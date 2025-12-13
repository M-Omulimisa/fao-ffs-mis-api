# VSLA Controllers Complete Enhancement Guide

**Date:** December 13, 2025  
**Purpose:** 360° Admin View of VSLA System

---

## ✅ COMPLETED ENHANCEMENTS

### 1. LoanTransactionController
- ✅ Loan filter with dropdown
- ✅ Borrower column
- ✅ Group column  
- ✅ Type filter (principal, interest, payment, penalty)
- ✅ Amount color-coding
- ✅ Balance calculation display

### 2. FfsGroupController - Partial
- ✅ Added Project, AccountTransaction, VslaMeeting models
- ✅ Added VSLA-specific columns (cycles, balance, meetings)
- ⏳ Need to test and verify calculations

---

## 🔧 REQUIRED ENHANCEMENTS

### Priority 1: Core VSLA Controllers

#### A. ProjectController (Cycles)
**File:** `app/Admin/Controllers/ProjectController.php`

**Required Changes:**

```php
// In grid() method - Add filters
$grid->filter(function($filter){
    $filter->disableIdFilter();
    
    // Group filter
    $filter->equal('group_id', 'VSLA Group')->select(function() {
        return FfsGroup::where('type', 'VSLA')
            ->pluck('name', 'id');
    });
    
    // Status filter
    $filter->equal('status', 'Status')->select([
        'planning' => 'Planning',
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ]);
    
    // Date range
    $filter->between('start_date', 'Start Date')->date();
    $filter->between('end_date', 'End Date')->date();
});

// Add columns
$grid->column('group.name', 'VSLA Group')->display(function() {
    if (!$this->group) return 'N/A';
    return "<a href='/admin/ffs-vslas/{$this->group_id}'>{$this->group->name}</a>";
});

$grid->column('share_value', 'Share Value')->display(function($value) {
    return 'UGX ' . number_format($value, 0);
});

$grid->column('total_shares_sold', 'Shares Sold')->display(function() {
    $shares = ProjectShare::where('project_id', $this->id)->count();
    return "<a href='/admin/project-shares?project_id={$this->id}'>{$shares}</a>";
});

$grid->column('group_balance', 'Group Balance')->display(function() {
    $balance = AccountTransaction::where('user_id', null)
        ->whereHas('cycle', function($q) {
            $q->where('id', $this->id);
        })
        ->sum('amount');
    $formatted = number_format($balance, 0);
    $color = $balance >= 0 ? 'green' : 'red';
    return "<strong style='color: {$color};'>UGX {$formatted}</strong>";
});

$grid->column('active_loans', 'Active Loans')->display(function() {
    $loans = VslaLoan::where('cycle_id', $this->id)
        ->where('status', 'active')
        ->count();
    return "<a href='/admin/vsla-loans?cycle_id={$this->id}&status=active'>{$loans}</a>";
});

$grid->column('meetings_count', 'Meetings')->display(function() {
    $meetings = VslaMeeting::where('cycle_id', $this->id)->count();
    return "<a href='/admin/vsla-meetings?cycle_id={$this->id}'>{$meetings}</a>";
});
```

---

#### B. VslaMeetingController
**File:** `app/Admin/Controllers/VslaMeetingController.php`

**Required Changes:**

```php
// Add models
use App\Models\FfsGroup;
use App\Models\Project;
use App\Models\VslaMeetingAttendance;

// In grid() - Add filters
$grid->filter(function($filter){
    $filter->disableIdFilter();
    
    // Group filter
    $filter->equal('group_id', 'VSLA Group')->select(function() {
        return FfsGroup::where('type', 'VSLA')->pluck('name', 'id');
    });
    
    // Cycle filter
    $filter->equal('cycle_id', 'Cycle')->select(function() {
        return Project::where('is_vsla_cycle', 'Yes')->pluck('name', 'id');
    });
    
    // Date range
    $filter->between('meeting_date', 'Meeting Date')->date();
    
    // Status filter
    $filter->equal('status', 'Status')->select([
        'pending' => 'Pending',
        'processed' => 'Processed',
        'failed' => 'Failed',
    ]);
});

// Add columns
$grid->column('meeting_number', 'Meeting #')->sortable();

$grid->column('group.name', 'VSLA Group')->display(function() {
    if (!$this->group) return 'N/A';
    return "<a href='/admin/ffs-vslas/{$this->group_id}'>{$this->group->name}</a>";
});

$grid->column('cycle.name', 'Cycle')->display(function() {
    if (!$this->cycle) return 'N/A';
    return "<a href='/admin/cycles/{$this->cycle_id}'>{$this->cycle->name}</a>";
});

$grid->column('meeting_date', 'Date')->display(function($date) {
    return date('d M Y', strtotime($date));
})->sortable();

$grid->column('attendance_rate', 'Attendance')->display(function() {
    $total = VslaMeetingAttendance::where('meeting_id', $this->id)->count();
    $present = VslaMeetingAttendance::where('meeting_id', $this->id)
        ->where('is_present', 1)->count();
    
    if ($total == 0) return 'N/A';
    
    $percentage = round(($present / $total) * 100);
    $color = $percentage >= 80 ? 'green' : ($percentage >= 50 ? 'orange' : 'red');
    
    return "<span style='color: {$color}; font-weight: bold;'>{$present}/{$total} ({$percentage}%)</span>";
});

$grid->column('total_savings_collected', 'Savings')->display(function($amount) {
    return 'UGX ' . number_format($amount, 0);
});

$grid->column('total_loans_disbursed', 'Loans')->display(function($amount) {
    return 'UGX ' . number_format($amount, 0);
});

$grid->column('status', 'Status')->label([
    'pending' => 'warning',
    'processed' => 'success',
    'failed' => 'danger',
])->sortable();
```

---

#### C. VslaLoanController
**File:** `app/Admin/Controllers/VslaLoanController.php`

**Required Changes:**

```php
// Add models
use App\Models\FfsGroup;
use App\Models\Project;
use App\Models\User;
use App\Models\LoanTransaction;

// In grid() - Add filters
$grid->filter(function($filter){
    $filter->disableIdFilter();
    
    // Group filter
    $filter->equal('cycle.group_id', 'VSLA Group')->select(function() {
        return FfsGroup::where('type', 'VSLA')->pluck('name', 'id');
    });
    
    // Cycle filter
    $filter->equal('cycle_id', 'Cycle')->select(function() {
        return Project::where('is_vsla_cycle', 'Yes')->pluck('name', 'id');
    });
    
    // Borrower filter
    $filter->equal('borrower_id', 'Borrower')->select(function() {
        return User::pluck('name', 'id');
    });
    
    // Status filter
    $filter->equal('status', 'Status')->select([
        'active' => 'Active',
        'paid' => 'Paid',
        'defaulted' => 'Defaulted',
    ]);
});

// Add columns
$grid->column('borrower.name', 'Borrower')->display(function() {
    if (!$this->borrower) return 'Unknown';
    return "<a href='/admin/users/{$this->borrower_id}'>{$this->borrower->name}</a>";
})->sortable();

$grid->column('cycle.group.name', 'VSLA Group')->display(function() {
    if (!$this->cycle || !$this->cycle->group) return 'N/A';
    $group = $this->cycle->group;
    return "<a href='/admin/ffs-vslas/{$group->id}'>{$group->name}</a>";
});

$grid->column('cycle.name', 'Cycle')->display(function() {
    if (!$this->cycle) return 'N/A';
    return "<a href='/admin/cycles/{$this->cycle_id}'>{$this->cycle->name}</a>";
});

$grid->column('loan_amount', 'Amount')->display(function($amount) {
    return 'UGX ' . number_format($amount, 0);
})->sortable();

$grid->column('interest_rate', 'Interest')->display(function($rate) {
    return $rate . '%';
});

$grid->column('balance', 'Balance')->display(function() {
    $balance = LoanTransaction::calculateLoanBalance($this->id);
    $formatted = number_format(abs($balance), 0);
    
    if ($balance < 0) {
        return "<span style='color: red; font-weight: bold;'>UGX {$formatted}</span>";
    } elseif ($balance == 0) {
        return "<span style='color: green; font-weight: bold;'>✓ PAID</span>";
    } else {
        return "<span style='color: blue;'>UGX {$formatted}</span>";
    }
});

$grid->column('payment_progress', 'Progress')->display(function() {
    $totalDue = $this->total_amount_due;
    if ($totalDue == 0) return 'N/A';
    
    $balance = LoanTransaction::calculateLoanBalance($this->id);
    $paid = $totalDue + $balance; // balance is negative
    $percentage = round(($paid / $totalDue) * 100);
    $percentage = max(0, min(100, $percentage));
    
    $color = $percentage >= 80 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
    
    return "
        <div class='progress' style='height: 20px;'>
            <div class='progress-bar progress-bar-{$color}' 
                 style='width: {$percentage}%'>
                {$percentage}%
            </div>
        </div>
    ";
});

$grid->column('status', 'Status')->label([
    'active' => 'warning',
    'paid' => 'success',
    'defaulted' => 'danger',
])->sortable();

$grid->column('disbursement_date', 'Disbursed')->display(function($date) {
    return date('d M Y', strtotime($date));
})->sortable();

$grid->column('due_date', 'Due')->display(function($date) {
    return date('d M Y', strtotime($date));
})->sortable();
```

---

#### D. AccountTransactionController
**File:** `app/Admin/Controllers/AccountTransactionController.php`

**Required Changes:**

```php
// Add models
use App\Models\FfsGroup;
use App\Models\Project;
use App\Models\User;

// In grid() - Add filters
$grid->filter(function($filter){
    $filter->disableIdFilter();
    
    // User filter (for member transactions)
    $filter->equal('user_id', 'Member')->select(function() {
        $users = User::pluck('name', 'id')->toArray();
        return ['' => 'Group (NULL)'] + $users;
    });
    
    // Group filter (via cycle)
    $filter->where(function ($query) {
        $groupId = $this->input;
        $query->whereHas('cycle', function($q) use ($groupId) {
            $q->where('group_id', $groupId);
        });
    }, 'Group')->select(function() {
        return FfsGroup::where('type', 'VSLA')->pluck('name', 'id');
    });
    
    // Cycle filter
    $filter->where(function ($query) {
        $cycleId = $this->input;
        $query->where(function($q) use ($cycleId) {
            $q->where('related_disbursement_id', $cycleId)
              ->orWhereHas('cycle', function($q2) use ($cycleId) {
                  $q2->where('id', $cycleId);
              });
        });
    }, 'Cycle')->select(function() {
        return Project::where('is_vsla_cycle', 'Yes')->pluck('name', 'id');
    });
    
    // Source filter
    $filter->equal('source', 'Source')->select([
        'share_purchase' => 'Share Purchase',
        'loan_disbursement' => 'Loan Disbursement',
        'loan_repayment' => 'Loan Repayment',
        'savings' => 'Savings',
        'welfare' => 'Welfare',
        'fine' => 'Fine',
        'dividend' => 'Dividend',
    ]);
    
    // Date range
    $filter->between('transaction_date', 'Transaction Date')->date();
});

// Add columns
$grid->column('transaction_date', 'Date')->display(function($date) {
    return date('d M Y', strtotime($date));
})->sortable();

$grid->column('user.name', 'User/Group')->display(function() {
    if ($this->user_id === null) {
        return "<strong style='color: #2196f3;'>GROUP</strong>";
    }
    if ($this->user) {
        return "<a href='/admin/users/{$this->user_id}'>{$this->user->name}</a>";
    }
    return 'Unknown';
});

$grid->column('amount', 'Amount')->display(function($amount) {
    $formatted = number_format(abs($amount), 2);
    $sign = $amount < 0 ? '-' : '+';
    $color = $amount < 0 ? 'red' : 'green';
    return "<strong style='color: {$color};'>{$sign} UGX {$formatted}</strong>";
})->sortable();

$grid->column('source', 'Source')->label([
    'share_purchase' => 'primary',
    'loan_disbursement' => 'danger',
    'loan_repayment' => 'success',
    'savings' => 'info',
    'welfare' => 'warning',
    'fine' => 'danger',
    'dividend' => 'success',
])->display(function($source) {
    return str_replace('_', ' ', ucwords($source));
});

$grid->column('description', 'Description')->limit(50);

$grid->column('created_at', 'Created')->display(function($date) {
    return date('d M Y H:i', strtotime($date));
})->hide();
```

---

### Priority 2: Supporting Controllers

#### E. VslaActionPlanController
**Similar pattern - add filters for cycle, group, assigned member, status**

#### F. VslaMeetingAttendanceController
**Similar pattern - add filters for meeting, cycle, group, member**

---

## 🎯 IMPLEMENTATION CHECKLIST

### Phase 1: Core Enhancements (Priority)
- [ ] Complete FfsGroupController VSLA metrics
- [ ] Enhance ProjectController for cycles
- [ ] Enhance VslaMeetingController
- [ ] Enhance VslaLoanController
- [ ] Enhance AccountTransactionController

### Phase 2: Supporting Enhancements
- [ ] Enhance VslaActionPlanController
- [ ] Enhance VslaMeetingAttendanceController

### Phase 3: Testing & Validation
- [ ] Test all filters and dropdowns
- [ ] Verify all links work correctly
- [ ] Validate balance calculations
- [ ] Check query performance
- [ ] Test with real data

### Phase 4: Documentation
- [ ] Document filter usage
- [ ] Document relationship navigation
- [ ] Create admin user guide
- [ ] Document balance calculation formulas

---

## 📋 COMMON PATTERNS

### Filter Pattern
```php
$filter->equal('field_id', 'Label')->select(function() {
    return Model::pluck('name', 'id');
});
```

### Link Pattern
```php
->display(function() {
    return "<a href='/admin/resource/{$this->id}'>{$this->name}</a>";
});
```

### Balance Calculation Pattern
```php
->display(function() {
    $balance = AccountTransaction::where('condition')->sum('amount');
    $formatted = number_format($balance, 0);
    $color = $balance >= 0 ? 'green' : 'red';
    return "<strong style='color: {$color};'>UGX {$formatted}</strong>";
});
```

### Progress Bar Pattern
```php
->display(function() {
    $percentage = 75; // calculate
    $color = $percentage >= 80 ? 'success' : 'warning';
    return "
        <div class='progress'>
            <div class='progress-bar progress-bar-{$color}' 
                 style='width: {$percentage}%'>{$percentage}%</div>
        </div>
    ";
});
```

---

## 🔍 TESTING GUIDELINES

### Balance Calculation Test
```sql
-- Test group balance
SELECT 
    user_id,
    SUM(amount) as balance 
FROM account_transactions 
WHERE user_id IS NULL 
GROUP BY user_id;

-- Test member balance
SELECT 
    user_id,
    u.name,
    SUM(at.amount) as balance 
FROM account_transactions at
JOIN users u ON at.user_id = u.id
GROUP BY user_id, u.name;
```

### Filter Test
1. Apply each filter individually
2. Apply multiple filters together
3. Test with empty results
4. Test with large datasets

### Link Test
1. Click each link type
2. Verify correct page opens
3. Check filter pre-applied
4. Verify data consistency

---

## 📝 NOTES

- All balance calculations use `SUM(amount)` from AccountTransactions
- Negative amounts = debits (money out)
- Positive amounts = credits (money in)
- Group transactions have `user_id = NULL`
- Member transactions have `user_id = member_id`
- Each loan transaction also creates AccountTransactions (double-entry)

