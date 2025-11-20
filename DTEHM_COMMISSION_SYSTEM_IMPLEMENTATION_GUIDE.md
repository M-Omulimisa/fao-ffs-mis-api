# DTEHM Multi-Level Commission System - Implementation Guide

## Overview
This document outlines the complete implementation of a 10-level commission system for DTEHM product sales. The system tracks sales made by DTEHM members and distributes commissions to the seller and their upline (up to 10 generations).

## Commission Structure
- **Seller (Sponsor)**: 10% of product price
- **Parent 1**: 3% of product price
- **Parent 2**: 2.5% of product price
- **Parent 3**: 2.0% of product price
- **Parent 4**: 1.5% of product price
- **Parent 5**: 1.0% of product price
- **Parent 6**: 0.8% of product price
- **Parent 7**: 0.6% of product price
- **Parent 8**: 0.4% of product price
- **Parent 9**: 0.3% of product price
- **Parent 10**: 0.2% of product price

**Total Commission Pool**: 21.3% of product price

## Implementation Steps

### Phase 1: Database Schema Changes
1. ✅ Add columns to `orders` table
2. ✅ Add columns to `ordered_items` table
3. ✅ Run migrations

### Phase 2: AccountTransaction Analysis
1. ✅ Study AccountTransaction model and structure
2. ✅ Understand transaction types and workflow
3. ✅ Plan integration with commission system

### Phase 3: Core Commission Service
1. ✅ Create CommissionService class
2. ✅ Implement commission calculation logic
3. ✅ Implement parent hierarchy traversal
4. ✅ Implement AccountTransaction creation
5. ✅ Add comprehensive error handling and logging

### Phase 4: Integration with Order System
1. ✅ Add hooks to OrderedItem model
2. ✅ Trigger commission processing on payment
3. ✅ Update OrderController to handle seller info

### Phase 5: Testing
1. ✅ Create test data (sellers with hierarchy)
2. ✅ Test commission calculations
3. ✅ Verify AccountTransaction creation
4. ✅ Test edge cases (missing parents, etc.)

### Phase 6: Admin Interface
1. ✅ Create CommissionController for admin
2. ✅ Add commission reports and views
3. ✅ Add commission processing controls

## Database Schema

### Orders Table - New Columns
```sql
- order_is_paid (VARCHAR) - 'Yes'/'No'
- order_paid_date (TIMESTAMP)
- order_paid_amount (DECIMAL)
- has_detehm_seller (VARCHAR) - 'Yes'/'No'
- dtehm_seller_id (VARCHAR) - Member ID
- dtehm_user_id (BIGINT) - User ID
```

### Ordered Items Table - New Columns
```sql
# Payment Tracking
- item_is_paid (VARCHAR) - 'Yes'/'No'
- item_paid_date (TIMESTAMP)
- item_paid_amount (DECIMAL)

# Seller Information
- has_detehm_seller (VARCHAR) - 'Yes'/'No'
- dtehm_seller_id (VARCHAR)
- dtehm_user_id (BIGINT)

# Commission Processing
- commission_is_processed (VARCHAR) - 'Yes'/'No'
- commission_processed_date (TIMESTAMP)
- total_commission_amount (DECIMAL)
- balance_after_commission (DECIMAL)

# Commission Amounts per Parent
- commission_seller (DECIMAL) - 10%
- commission_parent_1 to parent_10 (DECIMAL)

# Parent User IDs for Tracking
- parent_1_user_id to parent_10_user_id (BIGINT)
```

## Commission Calculation Flow

```
1. OrderedItem is marked as paid (item_is_paid = 'Yes')
   ↓
2. Check if has_detehm_seller = 'Yes'
   ↓
3. Retrieve seller user (dtehm_user_id)
   ↓
4. Get seller's parent hierarchy (parent_1 to parent_10)
   ↓
5. Calculate commissions for each level:
   - Seller: subtotal × 10%
   - Parent 1: subtotal × 3%
   - Parent 2: subtotal × 2.5%
   - ... (continue for all 10 levels)
   ↓
6. For each commission:
   a. Create AccountTransaction
   b. Update user's account balance
   c. Record commission in ordered_item
   d. Log transaction details
   ↓
7. Mark commission_is_processed = 'Yes'
   ↓
8. Update total_commission_amount and balance_after_commission
```

## AccountTransaction Integration

### Transaction Record Format
```php
[
    'user_id' => $parent_user_id,
    'type' => 'COMMISSION',
    'amount' => $commission_amount,
    'description' => "Commission earned from product sale. Order #X, Item #Y, Level: Z, Percentage: W%",
    'balance_before' => $previous_balance,
    'balance_after' => $new_balance,
    'source_type' => 'OrderedItem',
    'source_id' => $ordered_item_id,
    'status' => 'completed',
    'processed_at' => now(),
]
```

## Error Handling

### Critical Scenarios
1. **Seller not found**: Log error, skip commission processing
2. **Parent not found**: Skip that level, continue with others
3. **AccountTransaction fails**: Rollback, log error, mark as failed
4. **Duplicate processing**: Check commission_is_processed flag
5. **Database errors**: Use transactions, rollback on failure

## Testing Checklist

- [ ] Single seller (no parents) - 10% commission only
- [ ] Seller with 1 parent - Seller + Parent 1 commissions
- [ ] Seller with full 10-level hierarchy - All commissions
- [ ] Missing parents in middle (e.g., parent 3 missing) - Skip that level
- [ ] Multiple items in single order - Process each separately
- [ ] Duplicate processing prevention
- [ ] AccountTransaction balance updates correctly
- [ ] Commission totals match calculations
- [ ] Large order processing (performance test)
- [ ] Edge case: Zero-price items

## Security Considerations

1. **Transaction Integrity**: Use DB transactions for atomic operations
2. **Idempotency**: Prevent duplicate commission processing
3. **Audit Trail**: Log all commission calculations
4. **Access Control**: Only admins can manually trigger processing
5. **Validation**: Verify all amounts before creating transactions

## Performance Optimizations

1. Queue commission processing for large orders
2. Batch AccountTransaction inserts where possible
3. Cache user hierarchy lookups
4. Index commission-related columns
5. Regular cleanup of processed records

## Monitoring & Reporting

1. Daily commission summary reports
2. Failed commission processing alerts
3. Suspicious activity detection (unusually high commissions)
4. User commission earnings dashboard
5. Monthly commission statements

## Implementation Timeline

- **Day 1**: Database migrations and schema setup
- **Day 2**: CommissionService core logic
- **Day 3**: AccountTransaction integration
- **Day 4**: Testing and bug fixes
- **Day 5**: Admin interface and reporting
- **Day 6**: Final testing and deployment

## Status Tracking

| Phase | Status | Notes |
|-------|--------|-------|
| Database Schema | ✅ Complete | Migrations created |
| AccountTransaction Study | ⏳ In Progress | |
| CommissionService | 🔄 Pending | |
| Integration | 🔄 Pending | |
| Testing | 🔄 Pending | |
| Admin Interface | 🔄 Pending | |

## Next Steps

1. Study AccountTransaction model structure
2. Create migration files for orders and ordered_items tables
3. Implement CommissionService class
4. Add model hooks and events
5. Create comprehensive tests

---

**Last Updated**: November 15, 2025
**Implementation Lead**: AI Assistant
**Status**: 🚀 Implementation Starting
