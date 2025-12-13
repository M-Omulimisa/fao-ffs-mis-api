# VSLA Double-Entry Accounting System

## Core Principles

### Foundation
The VSLA (Village Savings and Loan Association) E-Ledger uses a **double-entry accounting system** for all financial transactions. Every transaction must maintain balance between debits and credits.

### Core Model
**`AccountTransaction`** is the primary model for all VSLA transactions.

**Database Table:** `account_transactions`

**Key Fields:**
- `user_id` - The member ID (NULL for group-level transactions)
- `amount` - Positive (+) for credits, Negative (-) for debits
- `transaction_date` - Date of transaction
- `description` - Human-readable description
- `source` - Type/source of transaction (use constants)
- `related_disbursement_id` - Link to loan disbursement if applicable
- `created_by_id` - User who created the transaction

### Transaction Rules

#### Rule 1: Dual Recording
Every member transaction creates TWO `AccountTransaction` records:
1. **Group Record** - Shows impact on group's account
2. **Member Record** - Shows impact on member's account

#### Rule 2: Amount Signs
- **Positive (+)** amount = Money coming IN (Credit)
- **Negative (-)** amount = Money going OUT (Debit)

#### Rule 3: Balance Calculation
```
Balance = SUM(positive amounts) - SUM(ABS(negative amounts))
```

For a member or group:
```sql
SELECT 
    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as credits,
    SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as debits,
    SUM(amount) as balance
FROM account_transactions
WHERE user_id = [member_id OR NULL for group]
```

---

## Transaction Types & Examples

### Example 1: Member Makes a Saving
**Scenario:** Member saves UGX 10,000

**Result:** 2 transactions created

| Record | user_id | amount | source | description |
|--------|---------|--------|--------|-------------|
| Group  | NULL    | +10000 | savings | Group received savings from Member X |
| Member | 123     | +10000 | savings | Member X saved to group |

**Logic:**
- Group gains money (+) → Credit to group
- Member contributed (+) → Shows member's contribution

---

### Example 2: Member Takes a Loan
**Scenario:** Member receives loan of UGX 50,000

**Result:** 2 transactions created

| Record | user_id | amount | source | description |
|--------|---------|--------|--------|-------------|
| Group  | NULL    | -50000 | loan_disbursement | Group disbursed loan to Member X |
| Member | 123     | -50000 | loan_disbursement | Member X received loan from group |

**Logic:**
- Group loses money (-) → Debit to group
- Member received money (-) → Shows member's debt

---

### Example 3: Member Purchases Shares
**Scenario:** Member buys 5 shares @ UGX 5,000 = UGX 25,000

**Result:** 2 transactions created

| Record | user_id | amount | source | description |
|--------|---------|--------|--------|-------------|
| Group  | NULL    | +25000 | share_purchase | Group received share payment from Member X |
| Member | 123     | +25000 | share_purchase | Member X purchased 5 shares |

**Logic:**
- Group gains money (+) → Credit to group
- Member contributed (+) → Shows member's equity contribution

**Related Records:**
- `project_shares` table records the share ownership
- `AccountTransaction` records the financial flow

---

### Example 4: Member Receives Share Dividend
**Scenario:** Member receives dividend of UGX 15,000

**Result:** 2 transactions created

| Record | user_id | amount | source | description |
|--------|---------|--------|--------|-------------|
| Group  | NULL    | -15000 | share_dividend | Group paid dividend to Member X |
| Member | 123     | -15000 | share_dividend | Member X received dividend |

**Logic:**
- Group loses money (-) → Debit to group
- Member received money (-) → Decreases member's net contribution

---

### Example 5: Member Contributes to Welfare Fund
**Scenario:** Member contributes UGX 2,000 to welfare

**Result:** 2 transactions created

| Record | user_id | amount | source | description |
|--------|---------|--------|--------|-------------|
| Group  | NULL    | +2000  | welfare_contribution | Group received welfare contribution from Member X |
| Member | 123     | +2000  | welfare_contribution | Member X contributed to welfare fund |

**Logic:**
- Group gains money (+) → Credit to group
- Member contributed (+) → Shows member's welfare contribution

---

### Example 6: Member Receives Welfare Assistance
**Scenario:** Member receives welfare support of UGX 8,000

**Result:** 2 transactions created

| Record | user_id | amount | source | description |
|--------|---------|--------|--------|-------------|
| Group  | NULL    | -8000  | welfare_distribution | Group paid welfare assistance to Member X |
| Member | 123     | -8000  | welfare_distribution | Member X received welfare assistance |

**Logic:**
- Group loses money (-) → Debit to group
- Member received money (-) → Shows member's benefit

---

### Example 7: Member Repays a Loan
**Scenario:** Member repays UGX 20,000 toward loan

**Result:** 2 transactions created

| Record | user_id | amount | source | description |
|--------|---------|--------|--------|-------------|
| Group  | NULL    | +20000 | loan_repayment | Group received loan repayment from Member X |
| Member | 123     | +20000 | loan_repayment | Member X repaid loan installment |

**Logic:**
- Group gains money (+) → Credit to group
- Member paid back (+) → Reduces member's debt

---

### Example 8: Member Pays a Fine
**Scenario:** Member fined UGX 5,000 for late attendance

**Result:** 2 transactions created

| Record | user_id | amount | source | description |
|--------|---------|--------|--------|-------------|
| Group  | NULL    | +5000  | fine_payment | Group received fine from Member X |
| Member | 123     | +5000  | fine_payment | Member X paid fine for late attendance |

**Logic:**
- Group gains money (+) → Credit to group
- Member paid (+) → Shows member's penalty payment

---

## Single-Entry Transactions

Some transactions involve only the GROUP (no member):

### Example 9: Administrative Expense
**Scenario:** Group spends UGX 3,000 on stationery

**Result:** 1 transaction created

| Record | user_id | amount | source | description |
|--------|---------|--------|--------|-------------|
| Group  | NULL    | -3000  | administrative_expense | Purchased stationery for group |

**Logic:**
- Money goes to external entity (not a member)
- Only group record needed

---

### Example 10: External Income
**Scenario:** Group receives UGX 50,000 grant

**Result:** 1 transaction created

| Record | user_id | amount | source | description |
|--------|---------|--------|--------|-------------|
| Group  | NULL    | +50000 | external_income | Received grant from NGO |

**Logic:**
- Money comes from external entity (not a member)
- Only group record needed

---

## Balance Calculation Examples

### Member Balance Example
**Member X Transactions:**
```
+10,000  (savings)
-50,000  (loan taken)
+25,000  (share purchase)
-15,000  (dividend received)
+20,000  (loan repayment)
+5,000   (fine paid)
```

**Balance Calculation:**
```
Credits:  10,000 + 25,000 + 20,000 + 5,000 = 60,000
Debits:   50,000 + 15,000 = 65,000
Balance:  60,000 - 65,000 = -5,000
```

**Interpretation:** Member owes group UGX 5,000 (net debt position)

---

### Group Balance Example
**Group Transactions:**
```
+10,000  (savings from Member 1)
+25,000  (share purchase from Member 1)
-50,000  (loan to Member 1)
-15,000  (dividend to Member 1)
+20,000  (loan repayment from Member 1)
+5,000   (fine from Member 1)
-3,000   (administrative expense)
+50,000  (external grant)
```

**Balance Calculation:**
```
Credits:  10,000 + 25,000 + 20,000 + 5,000 + 50,000 = 110,000
Debits:   50,000 + 15,000 + 3,000 = 68,000
Balance:  110,000 - 68,000 = 42,000
```

**Interpretation:** Group has UGX 42,000 in cash

---

## Transaction Source Constants

Define constants for transaction sources to avoid typos:

```php
class TransactionSource
{
    // Member contributions (credits to group)
    const SAVINGS = 'savings';
    const SHARE_PURCHASE = 'share_purchase';
    const WELFARE_CONTRIBUTION = 'welfare_contribution';
    const LOAN_REPAYMENT = 'loan_repayment';
    const FINE_PAYMENT = 'fine_payment';
    
    // Member receipts (debits from group)
    const LOAN_DISBURSEMENT = 'loan_disbursement';
    const SHARE_DIVIDEND = 'share_dividend';
    const WELFARE_DISTRIBUTION = 'welfare_distribution';
    
    // Group-only transactions
    const ADMINISTRATIVE_EXPENSE = 'administrative_expense';
    const EXTERNAL_INCOME = 'external_income';
    const BANK_CHARGES = 'bank_charges';
    
    // Other
    const MANUAL_ADJUSTMENT = 'manual_adjustment';
}
```

---

## Implementation Checklist

### For Every Member Transaction:
- [ ] Create group `AccountTransaction` with `user_id = NULL`
- [ ] Create member `AccountTransaction` with `user_id = member_id`
- [ ] Use same `amount` (positive or negative) for both
- [ ] Use same `transaction_date` for both
- [ ] Use same `source` constant for both
- [ ] Set descriptive `description` for both
- [ ] Set `created_by_id` to authenticated user

### For Automated Transactions:
- [ ] Shares → Create 2 `AccountTransaction` records (group + member)
- [ ] Loans → Create 2 `AccountTransaction` records (group + member)
- [ ] Savings → Create 2 `AccountTransaction` records (group + member)
- [ ] Welfare → Create 2 `AccountTransaction` records (group + member)
- [ ] Fines → Create 2 `AccountTransaction` records (group + member)

### For Group-Only Transactions:
- [ ] Administrative costs → Create 1 `AccountTransaction` (group only)
- [ ] External income → Create 1 `AccountTransaction` (group only)

---

## Code Examples

### Creating Share Purchase Transactions

```php
// Member purchases shares
$memberId = 123;
$shareAmount = 25000;
$meetingDate = '2025-01-20';
$createdBy = auth()->id();
$memberName = $member->name;

// Transaction 1: Group receives money
AccountTransaction::create([
    'user_id' => null, // Group transaction
    'amount' => $shareAmount, // Positive = credit
    'transaction_date' => $meetingDate,
    'description' => "Group received share payment from {$memberName}",
    'source' => 'share_purchase',
    'created_by_id' => $createdBy,
]);

// Transaction 2: Member contributes money
AccountTransaction::create([
    'user_id' => $memberId, // Member transaction
    'amount' => $shareAmount, // Positive = member contributed
    'transaction_date' => $meetingDate,
    'description' => "Member {$memberName} purchased shares",
    'source' => 'share_purchase',
    'created_by_id' => $createdBy,
]);
```

### Creating Loan Disbursement Transactions

```php
// Member receives loan
$memberId = 123;
$loanAmount = 50000;
$disbursementDate = '2025-01-20';
$createdBy = auth()->id();
$memberName = $member->name;

// Transaction 1: Group loses money
AccountTransaction::create([
    'user_id' => null, // Group transaction
    'amount' => -$loanAmount, // Negative = debit
    'transaction_date' => $disbursementDate,
    'description' => "Group disbursed loan to {$memberName}",
    'source' => 'loan_disbursement',
    'related_disbursement_id' => $disbursement->id,
    'created_by_id' => $createdBy,
]);

// Transaction 2: Member receives money (debt)
AccountTransaction::create([
    'user_id' => $memberId, // Member transaction
    'amount' => -$loanAmount, // Negative = member owes
    'transaction_date' => $disbursementDate,
    'description' => "Member {$memberName} received loan",
    'source' => 'loan_disbursement',
    'related_disbursement_id' => $disbursement->id,
    'created_by_id' => $createdBy,
]);
```

---

## Testing & Validation

### Test Balance Calculation

```php
// Test member balance
$memberId = 123;
$balance = AccountTransaction::where('user_id', $memberId)->sum('amount');
echo "Member {$memberId} balance: UGX " . number_format($balance, 2);

// Test group balance
$groupBalance = AccountTransaction::whereNull('user_id')->sum('amount');
echo "Group balance: UGX " . number_format($groupBalance, 2);
```

### Verify Double-Entry Integrity

```php
// For a specific date/meeting
$meetingDate = '2025-01-20';
$source = 'share_purchase';

$groupTotal = AccountTransaction::whereNull('user_id')
    ->where('transaction_date', $meetingDate)
    ->where('source', $source)
    ->sum('amount');

$memberTotal = AccountTransaction::whereNotNull('user_id')
    ->where('transaction_date', $meetingDate)
    ->where('source', $source)
    ->sum('amount');

if ($groupTotal == $memberTotal) {
    echo "✅ Double-entry balanced for {$source} on {$meetingDate}";
} else {
    echo "❌ Double-entry MISMATCH: Group={$groupTotal}, Members={$memberTotal}";
}
```

---

## Migration Requirements

### Required Columns in `account_transactions` table:
- `id` (primary key)
- `user_id` (nullable, foreign key to users)
- `amount` (decimal, can be positive or negative)
- `transaction_date` (date)
- `description` (text)
- `source` (varchar, indexed)
- `related_disbursement_id` (nullable, foreign key to disbursements)
- `created_by_id` (foreign key to users)
- `created_at`, `updated_at`, `deleted_at` (timestamps)

---

## Critical Rules Summary

1. **Every member transaction = 2 records** (group + member)
2. **Same amount, same date, same source** for both records
3. **Positive (+) = money IN**, **Negative (-) = money OUT**
4. **Balance = SUM(all amounts)** for member or group
5. **Use constants** for transaction sources
6. **AccountTransaction is the single source of truth** for all VSLA finances
7. **Group transactions have user_id = NULL**
8. **Member transactions have user_id = member_id**

---

## Related Models Integration

### Shares (project_shares)
- Records share ownership details
- Must create 2 `AccountTransaction` records when share purchased
- Share price stored in `project_shares.share_price_at_purchase`
- Transaction amount = number_of_shares × share_price_at_purchase

### Loans (disbursements)
- Records loan details
- Must create 2 `AccountTransaction` records when loan disbursed
- Link via `related_disbursement_id`
- Loan repayments also create 2 `AccountTransaction` records

### Savings (savings_accounts)
- May have separate tracking
- Must create 2 `AccountTransaction` records for each saving
- All savings flow through `AccountTransaction`

---

**Last Updated:** December 13, 2025
**Version:** 1.0
**Status:** Active Implementation Guide
