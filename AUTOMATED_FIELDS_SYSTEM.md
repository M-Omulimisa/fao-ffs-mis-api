# Automated Fields System - Complete Documentation

## Overview
This system uses Laravel Model Events to automatically update computed fields across related models. All updates happen in database transactions to ensure data integrity.

---

## 1. Project Model - Automated Fields

### Fields That Auto-Update:
- ✅ `shares_sold` - Total shares purchased by investors
- ✅ `total_investment` - Sum of all share purchase transactions
- ✅ `total_returns` - Sum of all returns distributed to investors
- ✅ `total_expenses` - Sum of all project expenses
- ✅ `total_profits` - Sum of all project profits

### Triggers:
These fields update automatically when:
1. **ProjectTransaction** is created/updated/deleted
2. **ProjectShare** is created/updated/deleted
3. **Disbursement** is created/deleted

### How It Works:

```php
// When a ProjectTransaction is created
ProjectTransaction::create([...]) 
// → triggers ProjectTransaction::created event
// → calls $project->recalculateFromTransactions()
// → updates all computed fields atomically

// When a ProjectShare is created
ProjectShare::create([...])
// → triggers ProjectShare::created event
// → calls $project->recalculateFromTransactions()
// → updates shares_sold from sum of all shares
```

### Manual Recalculation:
```php
$project = Project::find($id);
$project->recalculateFromTransactions(); // Recalculates all fields from scratch
```

### Implementation Details:

**Project.php** has:
- `boot()` method with `creating` event to initialize fields
- `recalculateFromTransactions()` method that:
  - Runs in DB transaction for atomicity
  - Queries all transactions grouped by source
  - Calculates each field based on transaction source:
    - `share_purchase` → `total_investment`
    - `returns_distribution` → `total_returns`
    - `project_expense` → `total_expenses`
    - `project_profit` → `total_profits`
  - Queries all shares to calculate `shares_sold`
  - Uses `saveQuietly()` to avoid triggering update events

**ProjectTransaction.php** has:
- `boot()` method with events:
  - `created` → updates project
  - `updated` → updates project
  - `deleted` → updates project
  - `restored` → updates project

**ProjectShare.php** has:
- `boot()` method with events:
  - `created` → updates project
  - `updated` → updates project
  - `deleted` → updates project
  - `restored` → updates project

---

## 2. User Model - Account Balance

### Fields That Auto-Update:
- ✅ `account_balance` (computed attribute) - Sum of all account transactions

### Triggers:
Balance updates automatically when:
1. **AccountTransaction** is created/updated/deleted

### How It Works:

```php
// When an AccountTransaction is created
AccountTransaction::create([
    'user_id' => $userId,
    'amount' => 50000, // positive = credit
    'source' => 'disbursement',
])
// → User balance increases by 50,000

AccountTransaction::create([
    'user_id' => $userId,
    'amount' => -10000, // negative = debit
    'source' => 'withdrawal',
])
// → User balance decreases by 10,000
```

### Getting Balance:
```php
// Via attribute accessor
$balance = $user->account_balance;

// Via method (same result)
$balance = $user->calculateAccountBalance();

// Both return: SUM of all account_transactions.amount
```

### Implementation Details:

**User.php** has:
- `accountTransactions()` relationship
- `getAccountBalanceAttribute()` accessor
- `calculateAccountBalance()` method

Balance is always computed on-the-fly from transactions table. No stored field = no sync issues.

---

## 3. Insurance Program - Statistics

### Fields That Auto-Update:
- ✅ `total_subscribers` - Count of active/suspended subscriptions
- ✅ `total_premiums_expected` - Sum of all expected payments
- ✅ `total_premiums_collected` - Sum of all paid payments
- ✅ `total_premiums_balance` - Expected minus collected

### Triggers:
Statistics update automatically when:
1. **InsuranceSubscription** is created/updated/deleted
2. **InsuranceSubscriptionPayment** is created/updated/deleted

### How It Works:

```php
// When a subscription is created
InsuranceSubscription::create([...])
// → triggers InsuranceSubscription::created event
// → calls $program->updateStatistics()
// → recounts subscribers, recalculates totals

// When a payment is marked as paid
$payment->payment_status = 'Paid';
$payment->save();
// → triggers InsuranceSubscriptionPayment::updated event
// → calls $subscription->updateBalances()
// → calls $program->updateStatistics()
```

### Manual Update:
```php
$program = InsuranceProgram::find($id);
$program->updateStatistics(); // Recalculates all stats
```

### Implementation Details:

**InsuranceProgram.php** has:
- `boot()` method initializing fields on create
- `updateStatistics()` method that:
  - Runs in DB transaction with row locking
  - Counts active/suspended subscriptions
  - Sums expected premiums from payment records
  - Sums collected premiums (status = 'Paid')
  - Calculates balance
  - Saves atomically

**InsuranceSubscription.php** has:
- `boot()` method with events:
  - `created` → updates program statistics
  - `updated` → updates program statistics
  - `deleting` → deletes all payment records
  - `deleted` → updates program statistics

---

## 4. Insurance Subscription - Payment Tracking

### Fields That Auto-Update:
- ✅ `total_expected` - Sum of all payment amounts
- ✅ `total_paid` - Sum of all paid amounts
- ✅ `total_balance` - Expected minus paid
- ✅ `payments_completed` - Count of paid payments
- ✅ `payments_pending` - Count of pending/overdue payments

### Triggers:
These fields update automatically when:
1. **InsuranceSubscriptionPayment** is created/updated/deleted

### How It Works:

```php
// When a payment is created (during subscription preparation)
InsuranceSubscriptionPayment::create([...])
// → triggers InsuranceSubscriptionPayment::created event
// → calls $subscription->updateBalances()
// → recalculates all totals

// When a payment is marked as paid
$payment->payment_status = 'Paid';
$payment->paid_amount = $payment->amount;
$payment->save();
// → triggers InsuranceSubscriptionPayment::updated event
// → calls $subscription->updateBalances()
// → increases total_paid, decreases balance
```

### Manual Update:
```php
$subscription = InsuranceSubscription::find($id);
$subscription->updateBalances(); // Recalculates all balances
```

### Implementation Details:

**InsuranceSubscription.php** has:
- `boot()` method initializing fields on create
- `updateBalances()` method that:
  - Runs in DB transaction with row locking
  - Sums all payment amounts (expected)
  - Sums paid amounts (status = 'Paid')
  - Calculates balance
  - Counts completed payments
  - Counts pending/overdue payments
  - Saves atomically

**InsuranceSubscriptionPayment.php** has:
- `boot()` method with events:
  - `created` → updates subscription balances
  - `updated` → updates subscription balances
  - `deleting` → updates subscription balances

---

## 5. Disbursement - Project Integration

### Automated Actions:
When a disbursement is created:
1. ✅ Creates proportional **AccountTransaction** records for all investors
2. ✅ Creates a **ProjectTransaction** record (source: returns_distribution)
3. ✅ Updates project's `total_returns` field

When a disbursement is deleted:
1. ✅ Deletes all related **AccountTransaction** records
2. ✅ Deletes the **ProjectTransaction** record
3. ✅ Updates project's `total_returns` field

### Implementation Details:

**Disbursement.php** has:
- `boot()` method with events:
  - `created` → updates project totals
  - `deleting` → deletes account transactions
  - `deleted` → updates project totals

**DisbursementController.php** handles:
- Creating account transactions proportionally
- Creating project transaction
- All in single DB transaction

---

## Transaction Safety & Atomicity

All automated updates use:
1. **Database Transactions** - All or nothing execution
2. **Row Locking** - Prevents race conditions (`lockForUpdate()`)
3. **Quiet Saves** - Prevents infinite event loops (`saveQuietly()`)
4. **Event Hooks** - Automatic, no manual calls needed

### Example Flow:

```php
DB::transaction(function () {
    // Create a project transaction
    $transaction = ProjectTransaction::create([
        'project_id' => 1,
        'amount' => 100000,
        'type' => 'income',
        'source' => 'project_profit',
        'description' => 'Profit from sale',
    ]);
    
    // Automatically:
    // 1. ProjectTransaction::created event fires
    // 2. Finds project with ID 1
    // 3. Calls $project->recalculateFromTransactions()
    // 4. Inside recalculate:
    //    - Starts DB::transaction
    //    - Queries all transactions
    //    - Calculates total_profits += 100000
    //    - Saves project atomically
    // 5. All done automatically!
});
```

---

## Error Handling

All automated methods are wrapped in try-catch blocks within transactions:

```php
protected static function boot()
{
    parent::boot();
    
    static::created(function ($transaction) {
        try {
            if ($transaction->project_id) {
                $project = Project::find($transaction->project_id);
                if ($project) {
                    $project->recalculateFromTransactions();
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to update project: ' . $e->getMessage());
            throw $e; // Re-throw to rollback transaction
        }
    });
}
```

---

## Testing Automated Fields

### Test 1: Project Transaction → Project Totals
```php
// Create a project
$project = Project::create([...]);
// Initially: all totals = 0

// Create a share purchase transaction
ProjectTransaction::create([
    'project_id' => $project->id,
    'amount' => 100000,
    'source' => 'share_purchase',
]);

// Refresh project
$project->refresh();
// Assert: total_investment = 100000 ✅
```

### Test 2: Account Transaction → User Balance
```php
$user = User::find(1);
$initialBalance = $user->account_balance;

AccountTransaction::create([
    'user_id' => $user->id,
    'amount' => 50000,
    'source' => 'disbursement',
]);

$user->refresh();
// Assert: new balance = initialBalance + 50000 ✅
```

### Test 3: Insurance Payment → Subscription Balance
```php
$subscription = InsuranceSubscription::find(1);
$initialPaid = $subscription->total_paid;

$payment = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subscription->id)
    ->where('payment_status', 'Pending')
    ->first();

$payment->payment_status = 'Paid';
$payment->paid_amount = $payment->amount;
$payment->save();

$subscription->refresh();
// Assert: total_paid = initialPaid + payment.amount ✅
// Assert: payments_completed increased by 1 ✅
```

### Test 4: Disbursement → Multiple Updates
```php
$project = Project::find(1);
$initialReturns = $project->total_returns;

$disbursement = Disbursement::create([
    'project_id' => $project->id,
    'amount' => 100000,
    // ... account transactions created automatically
]);

$project->refresh();
// Assert: total_returns = initialReturns + 100000 ✅

// Check investor balances increased
$investors = ProjectShare::where('project_id', $project->id)->get();
foreach ($investors as $share) {
    $investor = User::find($share->investor_id);
    // Assert: balance increased proportionally ✅
}
```

---

## Performance Considerations

### Optimizations:
1. **Batch Updates** - Recalculate uses aggregation queries, not loops
2. **Selective Updates** - Only affected fields are recalculated
3. **Index Usage** - All foreign keys and status fields are indexed
4. **Query Optimization** - Uses `sum()`, `count()` instead of loading all records

### Avoiding N+1 Queries:
```php
// ❌ Bad - N+1 queries
$projects = Project::all();
foreach ($projects as $project) {
    echo $project->total_investment; // Queries on each iteration
}

// ✅ Good - Fields already stored
$projects = Project::all(); // Single query
foreach ($projects as $project) {
    echo $project->total_investment; // No additional query
}
```

---

## Maintenance Commands

### Recalculate All Projects:
```php
// Create Artisan command: app/Console/Commands/RecalculateProjects.php
php artisan projects:recalculate

// Implementation:
Project::chunk(100, function ($projects) {
    foreach ($projects as $project) {
        $project->recalculateFromTransactions();
    }
});
```

### Recalculate All Insurance Programs:
```php
php artisan insurance:recalculate-programs

// Implementation:
InsuranceProgram::chunk(100, function ($programs) {
    foreach ($programs as $program) {
        $program->updateStatistics();
    }
});
```

### Recalculate All Insurance Subscriptions:
```php
php artisan insurance:recalculate-subscriptions

// Implementation:
InsuranceSubscription::chunk(100, function ($subscriptions) {
    foreach ($subscriptions as $subscription) {
        $subscription->updateBalances();
    }
});
```

---

## Summary

### ✅ Automated Fields Working:

**Projects:**
- shares_sold (via ProjectShare events)
- total_investment (via ProjectTransaction events)
- total_returns (via ProjectTransaction & Disbursement events)
- total_expenses (via ProjectTransaction events)
- total_profits (via ProjectTransaction events)

**Users:**
- account_balance (computed from AccountTransaction)

**Insurance Programs:**
- total_subscribers (via InsuranceSubscription events)
- total_premiums_expected (via InsuranceSubscriptionPayment events)
- total_premiums_collected (via InsuranceSubscriptionPayment events)
- total_premiums_balance (computed)

**Insurance Subscriptions:**
- total_expected (via InsuranceSubscriptionPayment events)
- total_paid (via InsuranceSubscriptionPayment events)
- total_balance (computed)
- payments_completed (via InsuranceSubscriptionPayment events)
- payments_pending (via InsuranceSubscriptionPayment events)

### 🔒 Data Integrity Guaranteed:
- All updates in DB transactions
- Row locking prevents race conditions
- Quiet saves prevent infinite loops
- Automatic rollback on errors

### 🚀 Performance Optimized:
- Aggregate queries (sum, count)
- Indexed fields
- Batch processing support
- No N+1 query issues

**System is production-ready and fully automated!** ✅
