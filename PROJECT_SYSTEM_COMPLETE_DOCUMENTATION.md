# PROJECT SYSTEM COMPLETE DOCUMENTATION

## Overview
The Project Investment System allows users to invest in projects by purchasing shares and receive proportional returns through disbursements. The system includes comprehensive validation, automatic calculations, and proper financial tracking.

## System Components

### 1. **Projects** (`Project` Model)
Core entity representing investment opportunities.

**Key Fields:**
- `title` - Project name
- `description` - Project details
- `share_price` - Price per share (UGX)
- `total_shares` - Maximum shares available
- `shares_sold` - Number of shares sold (auto-calculated)
- `total_investment` - Total money invested (auto-calculated)
- `total_profits` - Total profits earned (auto-calculated)
- `total_expenses` - Total expenses incurred (auto-calculated)
- `total_returns` - Total disbursed to investors (auto-calculated)
- `status` - `ongoing`, `completed`, `on_hold`

**Computed Fields:**
- `net_profit` = `total_profits` - `total_expenses`
- `available_for_disbursement` = `total_profits` - `total_expenses` - `total_returns`
- `roi_percentage` = (`net_profit` / `total_investment`) × 100

**Key Methods:**
- `recalculateFromTransactions()` - Recalculates all totals from transactions and disbursements

### 2. **Project Shares** (`ProjectShare` Model)
Represents investor ownership in a project.

**Key Fields:**
- `project_id` - Which project
- `investor_id` - Which user
- `number_of_shares` - Number of shares owned
- `total_amount_paid` - Total investment amount
- `share_price_at_purchase` - Price at time of purchase
- `purchase_date` - When shares were purchased

**Automatic Actions:**
- On create/update/delete: Triggers `Project::recalculateFromTransactions()`

### 3. **Project Transactions** (`ProjectTransaction` Model)
Records all financial movements within a project.

**Transaction Types:**
- `income` - Money coming in
- `expense` - Money going out

**Transaction Sources:**
- `share_purchase` - Investor purchases shares
- `project_profit` - Project earns money
- `project_expense` - Project spends money
- `returns_distribution` - Manual returns to investors (not commonly used with disbursements)

**Automatic Actions:**
- On create/update/delete/restore: Triggers `Project::recalculateFromTransactions()`

### 4. **Disbursements** (`Disbursement` Model)
Distributes project returns to investors proportionally.

**Key Fields:**
- `project_id` - Which project
- `amount` - Total amount to distribute
- `disbursement_date` - When distribution happens
- `description` - Reason for disbursement

**Validation (on create):**
- ✅ Project must exist
- ✅ Project must have net profit (profits > expenses)
- ✅ Must have available funds: `available_for_disbursement` ≥ `amount`
- ✅ Project must have investors (shares sold > 0)

**Automatic Actions (on create):**
1. **Creates `AccountTransaction` for each investor:**
   - Amount = `disbursement_amount` × (investor_shares / total_shares)
   - Each investor gets proportional share
2. **Updates project totals:**
   - Calls `Project::recalculateFromTransactions()`
   - Updates `total_returns`

**Example:**
```
Project has 3 investors:
- Investor A: 100 shares (22.22%)
- Investor B: 200 shares (44.44%)
- Investor C: 150 shares (33.33%)
- Total: 450 shares

Disbursement of UGX 900,000:
- Investor A receives: 900,000 × 22.22% = UGX 200,000
- Investor B receives: 900,000 × 44.44% = UGX 400,000
- Investor C receives: 900,000 × 33.33% = UGX 300,000
```

### 5. **Account Transactions** (`AccountTransaction` Model)
Individual investor account movements.

**Created by Disbursements:**
- `user_id` - Which investor
- `amount` - Amount received
- `source` - `'disbursement'`
- `related_disbursement_id` - Links back to disbursement
- `description` - Details about the return

---

## Admin Panel Features

### **DisbursementController**

#### Grid View
- Shows all disbursements ordered by date (newest first)
- Displays project, amount, date, description, creator
- Shows number of investors who received the disbursement
- Quick search by description
- Filter by project and date range
- Edit and Delete disabled (disbursements are immutable once created)

#### Create Form (Enhanced with Validations)
1. **Project Selection Dropdown:**
   - Shows only ongoing/completed projects
   - Displays available funds in parentheses
   - Example: "Project ABC (Available: UGX 1,500,000)"

2. **Financial Summary Box (when project selected):**
   ```
   Project Financial Summary
   ========================================
   Total Profits:              UGX 2,250,000
   Total Expenses:             UGX 450,000
   Already Disbursed:          UGX 800,000
   Available for Disbursement: UGX 1,000,000
   Total Investors:            3
   Total Shares:               450
   ========================================
   ```

3. **Amount Field:**
   - Validates amount > 0
   - Must not exceed available funds
   - Shows error if insufficient funds

4. **Date and Description Fields**

5. **Validation on Save:**
   - Backend validation prevents creating disbursement with insufficient funds
   - Shows error message: "Insufficient funds! Available: UGX X"

6. **Success Message:**
   - "Disbursement created successfully! Amount distributed to N investor(s)."

#### Detail View
- Shows disbursement details
- Lists all AccountTransactions created (investor distributions)
- Shows each investor's name and amount received

---

## Financial Calculations Flow

### When Investor Purchases Shares:
1. `ProjectShare` created
2. `ProjectTransaction` created (type=income, source=share_purchase)
3. Project `recalculateFromTransactions()` called
4. `total_investment` increases
5. `shares_sold` increases

### When Project Earns Profit:
1. `ProjectTransaction` created (type=income, source=project_profit)
2. Project `recalculateFromTransactions()` called
3. `total_profits` increases

### When Project Has Expense:
1. `ProjectTransaction` created (type=expense, source=project_expense)
2. Project `recalculateFromTransactions()` called
3. `total_expenses` increases

### When Disbursement is Created:
1. **Validation:**
   - Check available funds
   - Check project has investors
2. **Distribution:**
   - Create `AccountTransaction` for each investor proportionally
3. **Update Totals:**
   - `Disbursement` saved
   - Project `recalculateFromTransactions()` called
   - `total_returns` increases
   - `available_for_disbursement` decreases

---

## Testing

### Automated Test Command
Run comprehensive system test:
```bash
php artisan project:test-system
```

**What it tests:**
1. ✅ Create project
2. ✅ Create shares for 3 investors (100, 200, 150 shares)
3. ✅ Add profits (3 transactions totaling UGX 2,250,000)
4. ✅ Add expenses (3 transactions totaling UGX 450,000)
5. ✅ Create valid disbursement (UGX 800,000)
6. ✅ Verify proportional distribution to investors
7. ✅ Try invalid disbursement with insufficient funds (should fail)
8. ✅ Verify all calculations are correct
9. ✅ Rollback test data (no database changes)

**Expected Results:**
```
Total Investment:    UGX 4,500,000
Total Profits:       UGX 2,250,000
Total Expenses:      UGX 450,000
Total Returns:       UGX 800,000
Net Profit:          UGX 1,800,000
Available Funds:     UGX 1,000,000
ROI:                 40.00%

✓ All calculations are CORRECT!
```

---

## Database Relationships

```
Project
├── hasMany ProjectShares
├── hasMany ProjectTransactions
├── hasMany Disbursements
└── hasMany UniversalPayments

ProjectShare
├── belongsTo Project
├── belongsTo User (investor)
└── belongsTo UniversalPayment

ProjectTransaction
├── belongsTo Project
├── belongsTo User (created_by)
└── belongsTo ProjectShare (related_share)

Disbursement
├── belongsTo Project
├── belongsTo User (created_by)
└── hasMany AccountTransactions (related_disbursement_id)

AccountTransaction
├── belongsTo User
├── belongsTo Disbursement (related_disbursement)
└── belongsTo InsuranceSubscription
```

---

## Important Business Rules

1. **Disbursement Validation:**
   - Cannot create disbursement if `available_for_disbursement` ≤ 0
   - Cannot disburse more than available funds
   - Cannot disburse to project with no investors

2. **Automatic Calculations:**
   - All project totals are auto-calculated from transactions
   - Never manually update computed fields
   - Always use `recalculateFromTransactions()` to ensure accuracy

3. **Proportional Distribution:**
   - Each investor receives returns proportional to their share percentage
   - Formula: `investor_amount = disbursement_amount × (investor_shares / total_shares)`

4. **Immutability:**
   - Disbursements cannot be edited once created
   - Disbursements cannot be deleted (or related AccountTransactions must be cleaned up)
   - This ensures financial integrity

5. **Transaction Tracking:**
   - All investor returns are tracked via AccountTransactions
   - Each AccountTransaction links back to its Disbursement
   - Easy audit trail for all distributions

---

## Files Modified/Created

### Models:
- ✅ `app/Models/Project.php` - Enhanced calculations, added disbursements relationship
- ✅ `app/Models/ProjectTransaction.php` - Already had proper events
- ✅ `app/Models/ProjectShare.php` - Already had proper events
- ✅ `app/Models/Disbursement.php` - Added validation and distribution logic

### Controllers:
- ✅ `app/Admin/Controllers/DisbursementController.php` - Enhanced with validations and financial summary

### Commands:
- ✅ `app/Console/Commands/TestProjectSystem.php` - Comprehensive test command

### Documentation:
- ✅ `PROJECT_SYSTEM_COMPLETE_DOCUMENTATION.md` - This file

---

## Usage Guide for Admins

### To Disburse Project Returns:

1. Navigate to `/admin/disbursements`
2. Click "New"
3. Select the project
4. Review the financial summary box (shows available funds)
5. Enter amount to disburse (must not exceed available)
6. Enter disbursement date
7. Enter description (e.g., "Q1 2025 Profit Distribution")
8. Click Save

**What Happens Automatically:**
- System validates sufficient funds
- Creates AccountTransaction for each investor
- Updates project `total_returns`
- Updates project `available_for_disbursement`
- Shows success message with investor count

### To View Disbursement Details:

1. Click "View" on any disbursement
2. See disbursement information
3. See list of all investors who received returns
4. See exact amount each investor received

---

## Error Messages

### "Insufficient funds for disbursement"
**Cause:** Trying to disburse more than available  
**Solution:** Check available funds in summary box, enter smaller amount

### "No funds available for disbursement"
**Cause:** Project has no net profit (expenses ≥ profits)  
**Solution:** Add more project profits or reduce expenses first

### "Cannot disburse funds. Project has no investors"
**Cause:** No shares have been sold  
**Solution:** Wait for investors to purchase shares first

---

## Performance Notes

- All calculations use efficient SQL queries
- `recalculateFromTransactions()` wrapped in database transaction
- Eager loading used in grids to prevent N+1 queries
- Computed fields cached on model (accessed via attributes)

---

## Future Enhancements (Recommendations)

1. **Email Notifications:** Notify investors when disbursement created
2. **PDF Statements:** Generate disbursement statements for investors
3. **Disbursement Schedule:** Allow scheduling future disbursements
4. **Partial Disbursements:** Disburse to specific investors only
5. **Currency Support:** Support multiple currencies
6. **Tax Calculations:** Automatic withholding tax calculations
7. **Investor Dashboard:** Show investors their total returns
8. **ROI Tracking:** Track ROI per investor over time

---

## Support

For issues or questions:
- Run test command: `php artisan project:test-system`
- Check calculations match expected results
- Verify project `available_for_disbursement` before creating disbursement
- Review AccountTransactions to verify proper distribution

---

**Last Updated:** November 11, 2025  
**System Status:** ✅ Fully Tested and Operational
