# FAO FFS MIS: Data-Layer Audit

- **Scope:** `app/Models` (103 files), `database/migrations` (90), `app/Services` (15), and the live schema.
- **Mode:** read-only. Nothing in the project or the database was modified.
- **Database:** `fao_prod_copy`, the fresher production copy (latest ledger row 2026-07-27), queried with SELECT, SHOW and EXPLAIN only. Some evidence blocks marked "older copy" come from `fao_ffs_mis` (about 3 months older, roughly half the rows). Wherever an item says "Re-verified on fao_prod_copy", the prod-copy figure is the current one.
- **Server settings:** MySQL 5.7, `sql_mode` includes `STRICT_TRANS_TABLES`, and Laravel `database.strict=true`.

**Table sizes (fao_prod_copy):**

| Table | Rows |
|---|---|
| account_transactions | 18,146 |
| admin_operation_log | 12,242 |
| users | 8,712 |
| admin_role_users | 5,890 |
| project_shares | 4,548 |
| social_fund_transactions | 4,472 |
| vsla_meeting_attendance | 3,602 |
| vsla_opening_balance_members | 3,151 |
| loan_transactions | 2,043 |
| ffs_session_participants | 2,006 |
| vsla_loans | 1,437 |
| ffs_groups | 751 (429 soft-deleted) |
| vsla_shareout_distributions | 390 |
| projects | 262 |
| vsla_meetings | 164 |
| **project_transactions** | **0** (second ledger, unused) |

**Severity count:** 5 Critical, 14 High, 22 Medium, 13 Low (54 items).

---

## Top 15 issues

| # | ID | Sev | Issue |
|---|---|---|---|
| 1 | DATA-38 | Critical | `PUT/DELETE /api/insurance-users/{id}` has no auth. Anyone can change any user's `user_type`, phone or status, or hard-delete the user. |
| 2 | DATA-39 | Critical | Public `POST users/register` accepts `role=Admin` and stores it as `user_type`. That unlocks admin-only ledger writes and cross-group meeting reads. |
| 3 | DATA-17 | Critical | A same-day meeting re-submission re-posts every saving, share, social fund and repayment (double posting). `local_id` is not stored, so retries also double-post. |
| 4 | DATA-18 | Critical | Re-processing an opening balance duplicates shares, social fund and loans; only the ledger rows are cleared. |
| 5 | DATA-19 | Critical | Opening-balance shares are priced at UGX 1 when `share_value` is NULL (481 rows, 18 cycles). Shareout weights by share count, so meeting buyers are under-paid about 5,000x. |
| 6 | DATA-05 / DATA-22 | High | `account_transactions.user_id` is NOT NULL, so group rows carry the submitter's id (9,028 rows). `calculateAccountBalance()`, which gates withdrawals, overstates 103 users by up to UGX 17.1M. |
| 7 | DATA-20 | High | Live ledger rows (128) still belong to soft-deleted duplicate meetings, so members' savings are double-counted. |
| 8 | DATA-21 | High | Loan transaction signs are inconsistent: the computed balance is wrong for 756 of 1,437 loans. The penalty and waiver endpoints overwrite `vsla_loans.balance` with the wrong value, and `createPayment` always fails. |
| 9 | DATA-42 | High | Soft-deleting an FfsGroup hard-deletes its ledger, loans, meetings, cycles and **member user rows** through raw SQL, with no transaction. |
| 10 | DATA-41 | High | The User `updating` hook rewrites `phone_number` on every save. 1,972 numbers are corrupted to `+256<letters>` and the count grows with every ledger write. |
| 11 | DATA-24 / DATA-23 | High | Opening-balance loans keep interest in `balance` but not in `total_amount_due` (173 loans, UGX 2.76M). Repayment writes the interest off, and the cached `users.loan_balance` is wrong for 185 users. |
| 12 | DATA-25 / DATA-26 | High | The group cash balance drops UGX 121M of group rows flagged `is_contra_entry`. The manifest compares UPPERCASE account types, so shares, repayments and fines read as 0. |
| 13 | DATA-40 | High | `POST /api/members/sync` writes arbitrary request keys (balance, user_type, group_id, ip_id) onto any User. |
| 14 | DATA-06 | High | Admin `cycles/{id}`, `projects/{id}` and the project edit page crash (SQL 1054 on `total_savings` / `amount`). |
| 15 | DATA-01 | High | `users.phone_number`, `email` and `username` have no indexes. They are full-scanned on every login and OTP, and twice on every User save (about 180 scans per 30-member meeting). Duplicate usernames make login ambiguous. |

The other High items are DATA-43 (meeting delete does not reverse repayments) and DATA-36 (39 "completed" meetings with no postings, UGX 4.62M).

---

## Indexes to add, ordered by impact

```sql
-- 1. Identity lookups (login, OTP, registration, validateUniqueFields on every User save)
ALTER TABLE users ADD INDEX users_phone_number_index (phone_number);
ALTER TABLE users ADD INDEX users_email_index (email);
ALTER TABLE users ADD INDEX users_username_index (username);
--    later, after de-duplicating (DATA-01 / DATA-41): make these three UNIQUE
ALTER TABLE users DROP INDEX users_member_code_index;          -- duplicate of users_member_code_unique

-- 2. Ledger aggregates (per-row member balance recompute; cycle summaries / shareout / manifest)
ALTER TABLE account_transactions ADD INDEX at_user_owner_type (user_id, owner_type, account_type);
ALTER TABLE account_transactions ADD INDEX at_cycle_owner_type (cycle_id, owner_type, account_type);
--    then optional: DROP INDEX idx_owner_type, DROP INDEX idx_account_type (cardinality 2 and 5)

-- 3. Role lookups and integrity (after deleting the 550 orphan rows)
ALTER TABLE admin_role_users ADD UNIQUE INDEX aru_user_role (user_id, role_id);

-- 4. Loans / shares / social fund composites
ALTER TABLE vsla_loans ADD INDEX vl_cycle_status (cycle_id, status);
ALTER TABLE vsla_loans ADD INDEX vl_borrower_status (borrower_id, status);
ALTER TABLE loan_transactions ADD INDEX lt_loan_type (loan_id, type);
ALTER TABLE project_shares ADD INDEX ps_project_investor (project_id, investor_id);
ALTER TABLE social_fund_transactions ADD INDEX sft_cycle_member (cycle_id, member_id);

-- 5. Location and creator FKs
ALTER TABLE users ADD INDEX users_subcounty_id_index (subcounty_id);
ALTER TABLE users ADD INDEX users_parish_id_index (parish_id);
ALTER TABLE users ADD INDEX users_registered_by_id_index (registered_by_id);
ALTER TABLE users ADD INDEX users_created_by_id_index (created_by_id);
ALTER TABLE ffs_groups ADD INDEX fg_subcounty_id (subcounty_id);
ALTER TABLE ffs_groups ADD INDEX fg_parish_id (parish_id);
ALTER TABLE ffs_groups ADD INDEX fg_created_by_id (created_by_id);

-- 6. Hygiene (small tables)
ALTER TABLE ffs_training_sessions ADD INDEX fts_created_by_id (created_by_id), ADD INDEX fts_submitted_by_id (submitted_by_id);
ALTER TABLE vsla_shareouts ADD INDEX vs_initiated_by_id (initiated_by_id);
ALTER TABLE vsla_shareout_distributions ADD INDEX vsd_paid_by_id (paid_by_id);
ALTER TABLE vsla_opening_balances ADD INDEX vob_is_processed (is_processed);
ALTER TABLE vsla_action_plans ADD INDEX vap_created_by_id (created_by_id);
ALTER TABLE pesapal_logs ADD INDEX pl_order_id (order_id);

-- Schema additions tied to findings (not pure indexes): DATA-10 / DATA-11
-- ALTER TABLE loan_transactions ADD meeting_id BIGINT UNSIGNED NULL, ADD INDEX (meeting_id);
-- ALTER TABLE project_shares ADD meeting_id BIGINT UNSIGNED NULL, ADD opening_balance_id BIGINT UNSIGNED NULL, ADD INDEX (meeting_id);
-- ALTER TABLE social_fund_transactions ADD deleted_at TIMESTAMP NULL;
-- ALTER TABLE loan_transactions DROP FOREIGN KEY loan_transactions_loan_id_fk;   -- duplicate FK
```

As `table(column)` (same order): users(phone_number), users(email), users(username), account_transactions(user_id, owner_type, account_type), account_transactions(cycle_id, owner_type, account_type), admin_role_users(user_id, role_id) UNIQUE, vsla_loans(cycle_id, status), vsla_loans(borrower_id, status), loan_transactions(loan_id, type), project_shares(project_id, investor_id), social_fund_transactions(cycle_id, member_id), users(subcounty_id), users(parish_id), users(registered_by_id), users(created_by_id), ffs_groups(subcounty_id), ffs_groups(parish_id), ffs_groups(created_by_id), ffs_training_sessions(created_by_id), ffs_training_sessions(submitted_by_id), vsla_shareouts(initiated_by_id), vsla_shareout_distributions(paid_by_id), vsla_opening_balances(is_processed), vsla_action_plans(created_by_id), pesapal_logs(order_id).

---

## 1. Missing indexes

Method: I dumped `information_schema.statistics` and `information_schema.columns` for `fao_prod_copy`. For every `*_id` column and every commonly filtered column (status, type, phone_number, username, email, user_type, created_at, deleted_at, etc.) I checked whether it is the **leading** column of any index. I then grepped the code for the actual filter patterns and ran EXPLAIN on the hot queries.

Overall the VSLA tables are reasonably indexed. Migration `2026_05_04` added most of `account_transactions`, and `projects`, `vsla_meetings`, `vsla_loans`, `social_fund_transactions` and `vsla_meeting_attendance` all have their FK indexes. The real gap is the identity columns on `users`.

### DATA-01 `users.phone_number`, `users.email` and `users.username` are not indexed, yet they are queried on every login, OTP and registration, and twice on every User save (High)
- **Table:** `users` (8,712 rows; it grew 16% between the two DB copies). Existing indexes: PRIMARY, member_code (two indexes, one of them a duplicate unique), group_id, ip_id, district_id, onboarding_step, is_group_admin.
- **Evidence:**
  ```
  EXPLAIN SELECT * FROM users WHERE phone_number='+256700000000' LIMIT 1
    -> type=ALL key=NULL rows=8416
  EXPLAIN SELECT * FROM users WHERE email='x' OR username='x' LIMIT 1
    -> type=ALL key=NULL rows=8416
  ```
  Callers:
  - `app/Models/User.php:465` and `:484` (`validateUniqueFields`) run `where('email')` and `where('phone_number')` on **every create and update**. Every member ledger row triggers a User save (see DATA-44), so a 30-member meeting does about 180 full scans of `users`.
  - `ApiAuthController.php:106,145,182`, `AuthController.php:52`, `ApiResurceController.php:204-376` (registration uniqueness), `VslaOnboardingController.php:146,1037`, `Api/VslaFacilitatorController.php:488,496,631`.
- **Data that blocks UNIQUE indexes today:**
  ```
  duplicate username: '256' x5, '07' x3, '0777533990' x2   (3 values, 10 rows)
  duplicate phone_number: '+2567' x3, '+256Al' x2           (junk produced by DATA-41)
  1,972 phone numbers match '^\+256[A-Za-z]' (corrupted placeholders, DATA-41)
  email: 8,040 NULL, 0 empty-string, 0 duplicates
  ```
  Login with `username='0777533990'` (ApiAuthController:182, `->first()`) resolves to an arbitrary one of two accounts.
- **Fix:** add non-unique `users(phone_number)`, `users(email)` and `users(username)` now. After cleaning up the duplicates and the DATA-41 junk, convert them to UNIQUE (NULLs are allowed). Drop the redundant `users_member_code_index`, because `users_member_code_unique` already covers it.

### DATA-02 `account_transactions` needs composite indexes for the per-member and per-cycle aggregates (Medium)
- **Table:** `account_transactions` (18,146 rows, doubling roughly every 3 months: 8,798 in the older copy).
- **Evidence:**
  - `AccountTransaction::updateMemberBalance` (AccountTransaction.php:73-85) runs `WHERE owner_type='member' AND user_id=? AND deleted_at IS NULL` after **every** ledger insert. EXPLAIN shows an `index_merge` intersect of `user_id` and `owner_type`. The heaviest user_ids carry 418, 258 and 222 rows, mostly group rows (DATA-05).
  - Cycle summaries (manifest, shareout, group stats) filter on `cycle_id + owner_type + account_type`. EXPLAIN uses `idx_cycle_id` only: rows=400, filtered=2.5%.
- **Fix:** `account_transactions(user_id, owner_type, account_type)` and `account_transactions(cycle_id, owner_type, account_type)`. After that, the single-column `idx_owner_type` and `idx_account_type` (cardinality 2 and 5) can be dropped.

### DATA-03 `admin_role_users` has no primary key and no index led by `user_id`; 550 rows point to deleted users (Low)
- **Table:** `admin_role_users` (5,890 rows). Its only index is `(role_id, user_id)`.
- **Evidence:** 550 orphan rows (`user_id` not in users, see DATA-08). There are no duplicate (role_id, user_id) pairs today, but nothing prevents them. EXPLAIN on the Laravel-Admin role join is fine today only because `admin_roles` has 7 rows.
- **Fix:** delete the orphans, then add `UNIQUE admin_role_users(user_id, role_id)`.

### DATA-04 Secondary FK and filter columns without indexes (Low)
These are small tables today, but the columns are used in joins and filters:
- `users(subcounty_id)`, `users(parish_id)`, `users(registered_by_id)`, `users(created_by_id)`
- `ffs_groups(subcounty_id)`, `ffs_groups(parish_id)`, `ffs_groups(created_by_id)`
- `loan_transactions(loan_id, type)`, replacing `loan_id`, for repayment sums by type
- `vsla_loans(cycle_id, status)` and `vsla_loans(borrower_id, status)` (1,437 rows)
- `project_shares(project_id, investor_id)` (4,548 rows; today it relies on an index_merge intersect)
- `social_fund_transactions(cycle_id, member_id)` (4,472 rows)
- `ffs_training_sessions(created_by_id)`, `ffs_training_sessions(submitted_by_id)`, `vsla_shareouts(initiated_by_id)`, `vsla_shareout_distributions(paid_by_id)`, `vsla_opening_balances(is_processed)`, `vsla_action_plans(created_by_id)`, `pesapal_logs(order_id)`, `images(parent_id, type)`

Not recommended: `created_at` / `deleted_at` alone on these tables (low selectivity: 429 of 751 ffs_groups are soft-deleted), and `users.user_type` (3 values: Customer 8,648, member 59, Admin 5).

---

## 2. Schema, integrity, relationships, dead code

### DATA-05 `account_transactions.user_id` is NOT NULL, so group-side ledger rows must carry a person's id. This is the root cause of the inflated balances (High)
- **Table:** `account_transactions`
- **Evidence:**
  ```
  user_id        bigint unsigned NOT NULL
  owner_type     enum('group','member') NULL        <- nullable discriminator
  source         enum('disbursement','withdrawal','deposit') NOT NULL
  account_type   varchar(50) NULL                    <- free text; values in use: share, loan, loan_repayment, fine, social_fund
  live group-side rows carrying a user_id: 9,028 rows over 126 distinct users
  ```
  The writers put the meeting submitter in `user_id` for the group row (MeetingProcessingService.php:425, 516, 709, 931; OpeningBalanceService.php:122, 207, 242, 308). Every reader that does `User->accountTransactions()` therefore picks up the group's money. On the prod copy, 103 users have a `SUM(amount)` above their own member share total, with a maximum overstatement of UGX 17,116,000 (see DATA-22).
- Other problems in the same table:
  - **Narrow enum:** `LoanTransactionController.php:133-152` inserts `source='loan_repayment'` with `user_id=null`. With `STRICT_TRANS_TABLES` (verified in `@@GLOBAL.sql_mode`) and `'strict'=>true` (config/database.php:59), that is an SQL error, so `POST /api/loan-transactions/payment` always fails.
  - **`owner_type` is nullable:** rows created by `WithdrawRequest.php:154`, `CommissionService.php:244`, `AccountTransactionController.php:166` and `Disbursement.php:159` have no `owner_type`, so `updateMemberBalance` ignores them.
  - **Spoofable creator:** `AccountTransactionController.php:172` takes `created_by_id` straight from the request.
- **Fix:**
  - Make `user_id` NULLable and stop writing the submitter into group rows (keep them in `created_by_id`); backfill `user_id=NULL WHERE owner_type='group'`.
  - Make `owner_type` NOT NULL.
  - Convert `account_type` to an enum or a lookup table.
  - Widen `source`, or stop using it.

### DATA-06 Admin cycle and project detail pages crash: they query `vsla_meetings.total_savings` and `vsla_loans.amount`, which do not exist (High)
- **Files:**
  - `app/Admin/Controllers/CycleController.php:244` (`VslaMeeting::where('cycle_id',..)->sum('total_savings')`) and `:251` (`VslaLoan::...->sum('amount')`), both in `detail()`.
  - `app/Admin/Controllers/ProjectController.php:321` and `:333` (`detail()`), and `:525` and `:541` (`form()` edit page).
- **Evidence:**
  ```
  SELECT SUM(total_savings) FROM vsla_meetings WHERE cycle_id=1 -> ERROR 1054 Unknown column 'total_savings'
  SELECT SUM(amount) FROM vsla_loans WHERE cycle_id=1           -> ERROR 1054 Unknown column 'amount'
  ```
  The real columns are `total_savings_collected` (or `total_share_value`) and `loan_amount`.
- **Failure scenario:** opening any VSLA cycle under `admin/cycles/{id}` returns HTTP 500. So does `admin/projects/{id}` and the project edit form.
- **Fix:** rename the columns in the four queries. Ideally, compute totals from `account_transactions` (member share rows) rather than meeting header totals, which are not maintained for opening balances.

### DATA-07 Other queries on non-existent columns or enum values: silent zeros and blocked deletes (Medium)
| File:line | Problem | Effect |
|---|---|---|
| `app/Http/Controllers/Api/VslaGroupManifestController.php:855-857` | `VslaActionPlan::where('deadline', ...)`; the column is `due_date` | Throws inside `try`, so the "action plans due soon" list in the manifest is always empty |
| `app/Models/Location.php:244` (deleting hook) | `FfsGroup::where('location_id', ...)`; ffs_groups has no `location_id` (verified: ERROR 1054) | Every location delete throws a raw SQL error. The real references (`district_id`, `subcounty_id`, `parish_id` on ffs_groups and users) are never checked |
| `app/Admin/Controllers/HomeController.php:189` | `FarmActivity::where('status','completed')`; the enum is `pending, done, skipped, overdue` | Farm "completion rate" on the admin dashboard is always 0% |
| `app/Http/Controllers/LoanTransactionController.php:138,149` | `source='loan_repayment'` (not in enum) plus `user_id=null` (NOT NULL) | `createPayment` always rolls back (DATA-05) |
| `app/Services/MeetingProcessingService.php` loan repayment and `Api/VslaLoansController.php` repayment | Never set `loan_transactions.type`, which is NOT NULL with no default | Strict mode stores the first enum value, so repayments are recorded as `type='principal'` (18 rows `principal/repayment` on the prod copy). See DATA-28 and DATA-32 |

- **Fix:** correct the column names and enum values. For `Location`, check `district_id`, `subcounty_id` and `parish_id` on `ffs_groups` and `users`, and throw a friendly error.

### DATA-08 Orphaned rows left by hard-deleted users. `users` has no SoftDeletes, but 20+ tables reference it (Medium)
- **Evidence (fao_prod_copy, `LEFT JOIN parent ... WHERE parent.id IS NULL`):**
  | child.column | orphan rows (distinct ids) |
  |---|---|
  | admin_role_users.user_id | 550 (550) |
  | ffs_groups.facilitator_id | 107 (28) |
  | ffs_groups.treasurer_id | 81 (77) |
  | ffs_groups.admin_id | 82 (76) |
  | ffs_groups.secretary_id | 74 (68) |
  | ffs_session_participants.user_id | 100 (69) |
  | account_transactions.user_id | 16 (8) |
  | vsla_opening_balance_members.member_id | 16 (16) |
  | project_shares.investor_id | 9 (8) |
  | social_fund_transactions.member_id | 7 (5) |
  | vsla_meeting_attendance.member_id | 6 (6) |
  | vsla_shareout_distributions.member_id | 3 (3) |
  | vsla_loans.borrower_id | 2 (2) |
  | ffs_training_sessions.group_id -> ffs_groups | 2 (1) |

  All other FK-like columns checked (users.group_id/district_id/ip_id, projects.group_id, vsla_meetings.*, account_transactions.group_id/cycle_id/meeting_id, loan_transactions.loan_id, vsla_shareouts.*, farms.*, vsla_action_plans.*) have 0 orphans.
- **Cause:** User hard deletes, through the unauthenticated `DELETE insurance-users/{id}` (DATA-38), the FfsGroup deleting hook's raw `DB::table('users')->delete()` (DATA-42), and admin deletes. Only `account_transactions`, `social_fund_transactions` and a few others have real FKs, and none of those point to `users`.
- **Failure scenario:**
  - Ledger rows, loans and shares whose owner no longer exists are still counted in group totals and shareouts, but `->borrower->name` and `->investor->name` crash with "property on null".
  - Groups whose `facilitator_id` points to a deleted user drop out of facilitator-scoped lists.
- **Fix:** add `SoftDeletes` to `User` (migration: `deleted_at` on users). Block deletion of users who have ledger, loan or share rows. Repair: null the officer ids on ffs_groups, delete the admin_role_users orphans, and re-link or archive the 16 ledger, 9 share and 2 loan rows.

### DATA-09 Live children of soft-deleted parents, and VSLA groups missing cycle configuration (Medium)
- **Evidence (fao_prod_copy):**
  ```
  users whose group is soft-deleted                         : 7
  live projects (cycles) whose group is soft-deleted        : 9
  live account_transactions on soft-deleted meetings        : 128   (DATA-20)
  live VSLA groups with no active cycle                     : 24
  live VSLA cycles with share_value NULL or 0               : 27    (feeds DATA-19: shares priced at UGX 1)
  users with group_id NULL                                  : 83
  groups with >1 active cycle                               : 0     (constraint works)
  ```
- **Failure scenario:**
  - `$user->group` returns null for those 7 users (SoftDeletes scope), so any code doing `$user->group->name` crashes.
  - Meeting submission for the 24 groups fails with `missing_active_cycle` (MeetingProcessingService.php:196).
  - Opening balances for the 27 cycles without a share value create shares priced at UGX 1.
- **Fix:**
  - Require `share_value > 0` on VSLA cycles (validation plus a NOT NULL default in the migration).
  - When a group is soft-deleted, soft-delete or close its cycles.
  - Re-home or unlink the 7 users.

### DATA-10 Foreign keys cascade group deletes into financial history (Medium)
- **Evidence** (`information_schema.referential_constraints`):
  ```
  account_transactions.group_id     -> ffs_groups  ON DELETE CASCADE
  social_fund_transactions.group_id -> ffs_groups  ON DELETE CASCADE
  vsla_shareouts.group_id           -> ffs_groups  ON DELETE CASCADE
  loan_transactions.loan_id         -> vsla_loans  ON DELETE CASCADE   (defined twice: loan_transactions_loan_id_fk AND loan_transactions_loan_id_foreign)
  account_transactions.meeting_id   -> (no FK)     -> rows can point to purged meetings
  ```
- **Failure scenario:** any `forceDelete()` or raw SQL delete of an `ffs_groups` row (for example a cleanup script, or a future forceDelete path next to DATA-42) silently erases the group's whole ledger, social fund and shareout history, with no audit trail.
- **Fix:** change the financial FKs to `ON DELETE RESTRICT`. Drop the duplicate `loan_transactions_loan_id_fk`. Add `account_transactions.meeting_id -> vsla_meetings ON DELETE RESTRICT` (meetings are soft-deleted anyway).

### DATA-11 Missing traceability columns make meeting reversal and idempotency impossible (Medium)
- **Evidence:** column lists from the live schema:
  - `loan_transactions` has no `meeting_id`, `cycle_id`, `balance_before` or `balance_after` (yet MeetingProcessingService.php:604-704 passes them, and they are silently dropped).
  - `project_shares` has no `meeting_id` and no `opening_balance_id`.
  - `social_fund_transactions` has no `deleted_at` (SocialFundTransaction cannot be soft-deleted alongside its meeting).
  - `account_transactions` has no idempotency key; uniqueness of (meeting_id, user_id, account_type, owner_type) is not enforced.
- **Failure scenario:** this is why deleting or re-processing a meeting or opening balance cannot find and reverse the rows it created (DATA-17, DATA-18, DATA-20, DATA-43).
- **Fix:**
  - Add `meeting_id` and `opening_balance_id` to `loan_transactions`, `project_shares` and `social_fund_transactions`.
  - Add `deleted_at` to `social_fund_transactions`.
  - Add `UNIQUE account_transactions(meeting_id, user_id, owner_type, account_type, deleted_at)` or a `source_ref` column.

### DATA-12 Location references point at the wrong level, and the subcounty/parish hierarchy is effectively absent (Medium)
- **Evidence:**
  - `locations` has only 16 rows: 1 country, 2 regions, 11 districts, 1 sub-county (Kawempe) and 1 parish. `locations_backup_20260101_225251` holds 1,797 rows.
  - The FK targets are the wrong type:
    ```
    users.district_id   -> Region row: 684 users, Country row: 2 users  (6,720 -> District)
    users.subcounty_id  -> District row: 22 users
    ffs_groups: 0 live groups have subcounty_id; 298 carry only subcounty_text
    ```
- **Failure scenario:** district-level KPIs and filters (`User::where('district_id')`, IP dashboards, ExportCentre) miss 686 users, or bucket them under "Karamoja Region". Sub-county and parish filtering by id returns nothing.
- **Fix:** restore the subcounty and parish levels from the backup (or the UBOS list). Remap users with Region or Country district ids using their group's `district_id`. Add a check that `district_id` refers to `type='District'`.

### DATA-13 `ffs_groups.total_members` is a stale manual counter served to the mobile app (Low)
- **Evidence:** 221 of the live VSLA groups have `total_members` different from `COUNT(users WHERE group_id=g.id)`. Groups 449, 313, 643, 341 and 312 show 0 against 40, 36, 36, 35 and 35 members. Only the admin form recomputes it (`FfsGroupController.php:661`). `Api/VslaConfigurationController.php:80` returns it to the app, and `FfsGroup::getGenderDistribution` divides by it.
- **Fix:** compute it with `withCount('users')` when reading, or update it from a User saved/deleted hook.

### DATA-14 Broken relationship definitions (Low; currently unused)
I verified these by instantiating each model and resolving every relation against `information_schema.columns`:
- `FfsGroup::vslaActionPlans()` (FfsGroup.php:208-211): `hasMany(VslaActionPlan, 'group_id')`, but `vsla_action_plans` has no `group_id`. It should be `hasManyThrough(VslaActionPlan, Project, 'group_id', 'cycle_id')`.
- `User::vslaActionPlans()` (User.php:1157-1160): FK `assigned_to_id`; the column is `assigned_to_member_id` (VslaActionPlan::assignedTo already uses the right one).
- `User::campus()` refers to the missing class `App\Models\Campus`; `User::programs()` refers to the missing table `user_has_program`.
- `Person::association()` refers to the missing class `App\Models\Association`.
- `Product::attributes()` refers to the missing table `product_has_attributes`.
- `NewsPost::category()` refers to the missing table `post_categories`; `Disability::people()` and `Person::disability()` to missing tables.
- `ProjectTransaction::owner()` is a MorphTo, but no `owner_type`/`owner_id` morph pair is ever written (and the table has 0 rows).

None of these is called today: a grep for `vslaActionPlans` finds only the definitions. Any future `->with('vslaActionPlans')` will throw SQL 1054.
- **Fix:** correct the FKs or delete the methods.

### DATA-15 Dead models and dead tables (Low)
- **Models whose table does not exist (19):** CounsellingCentre, Delivery, DeliveryItem, Disability, Institution, Invoice, InvoiceItem, NewsPost, Participant, Person, PostCategory, ProductCategoryAttribute, ProductHasAttribute, ProductOrder, Quotation, QuotationItem, ServiceProvider, TrafficRecord, UserHasProgram.
  - Some are still routed: `app/Admin/routes.php:25,31` `deliveries` (registered twice) and `:34` `product-orders`, so those admin pages return HTTP 500.
  - `ApiResurceController::people()` (:2329) queries the missing `people` table, but no route points at it.
- **File problems in app/Models:**
  - `app/Models/JobApplication.php` is an empty 0-byte file, but `Admin/Controllers/JobApplicationController.php` references the class.
  - `app/Models/QuotationItem copy.php` declares a second `class QuotationItem`, a duplicate-class risk under `composer dump-autoload -o`.
  - `User.php.bak` and `Product.php.backup` should not be in app/Models.
- **Tables with no model:** 47 in total. All are empty legacy e-commerce or affiliate tables (`product_*`, `affiliate_*`, `payments`, `districts`, `countries`, ...), except for:
  - `locations_backup_20260101_225251` (1,797 rows, see DATA-12);
  - `health_check_resolutions` (23 rows);
  - `ffs_session_target_groups` (67 rows, used as a pivot, which is fine).

  `project_transactions` has a model but 0 rows (the second, unused ledger: DATA-31).
- **Fix:** delete the dead models, controllers and routes, and drop the empty legacy tables after a backup.

### DATA-16 Minor type issues (Low)
- `users.reg_date` is `varchar(45)` (0 non-empty rows; drop it).
- `orders.amount` and `orders.order_total` are `mediumtext`; `orders.payment_completed_at` is `text`; `pesapal_transactions.amount` is `text`; `users.dtehm_membership_is_paid` is `varchar(255)` (legacy e-commerce).
- `projects.net_profit` is `bigint`, while every other money column is `decimal(15,2)`.
- Mixed id widths: `vsla_meetings.created_by_id` and `processed_by_id` are `int unsigned`, and `admin_role_users.user_id` is `int`, while `users.id` is `bigint`.
- `fcm_tokens` is the only MyISAM table.
- The core VSLA money columns are all `decimal(15,2)`, which is correct. No money is stored in float or varchar in the VSLA tables.

---

## 3. Financial logic (models and services)

**Ledger sign conventions, fao_prod_copy (live rows):**

| owner_type | account_type | positive rows | negative rows | total UGX |
|---|---|---|---|---|
| group | share | 4,523 | 0 | 270,307,020 |
| member | share | 4,523 | 0 | 270,307,020 |
| group | loan | 0 | 1,437 | -227,699,200 |
| member | loan | 0 | 1,437 | -227,699,200 |
| group | loan_repayment | 591 | 0 | 90,010,800 |
| member | loan_repayment | 591 | 0 | 90,010,800 |
| group | social_fund | 2,428 | 0 | 31,209,050 |
| member | social_fund | 0 | 2,428 | **-31,209,050** |
| group | fine | 49 | 0 | 95,000 |
| member | fine | 0 | 49 | **-95,000** |

Shares, loans and repayments are mirrored with the same sign on both sides. Social fund and fines are mirrored with opposite signs. So any reader that sums `amount` without filtering `owner_type` and `account_type` produces a meaningless number (DATA-22, DATA-25, DATA-26).

### DATA-17 Same-day meeting "upsert" re-posts every financial entry (double posting) — Critical
- **Where:** `app/Http/Controllers/Api/VslaMeetingController.php:209-258` together with `app/Services/MeetingProcessingService.php:41-127`.
- **Code:** If a meeting already exists for the same group and date, the controller calls `fill([... new payload ..., 'processing_status' => 'pending'])`, then `save()`, then `processMeeting($sameDayExisting)`. `processMeeting` never removes or reverses what the earlier run posted:
  - `processTransactions` (l.324) creates new `AccountTransaction` pairs.
  - `processSharePurchases` (l.469) creates new `ProjectShare` rows and new AccountTransaction pairs.
  - `processLoanRepayments` (l.570) reduces `vsla_loans.balance` again and adds another LoanTransaction and AccountTransaction pair.
  - `processSocialFundContributions` (l.1132) creates new `SocialFundTransaction` rows.
  - Only loan disbursements (`existingLoan` check, l.870) and attendance (`updateOrCreate`) are idempotent.
- **Also:** the new `local_id` is never stored. The `fill()` call leaves out `local_id`, although the log line says "updated with new local_id". A client that retries the same payload after a timeout therefore passes the local_id duplicate check at l.84 and is re-posted every time.
- **Evidence:** this path has not yet run against the current data; the finding is verified from the code. The earlier duplicate-meeting problem that this path was written to fix is real: meetings 70/71/72 (group 445, 2026-04-08) and 73/74, 80/81 and 85/86/87 were the same meeting re-sent with new local_ids, and each copy posted its own ledger rows (see DATA-20).
- **Failure scenario:** the chairperson re-submits a meeting with one corrected entry. Every saving, share purchase, social fund contribution and loan repayment from the first submission is counted twice. Loans get repaid twice, so a loan can show as `paid` while the money was only received once.
- **Fix:** in the upsert branch, inside the same DB transaction, before re-processing:
  - soft-delete the `account_transactions`, `social_fund_transactions` and `loan_transactions` rows with this `meeting_id`;
  - delete the `project_shares` rows this meeting created (add a `meeting_id` column to `project_shares` so they can be found);
  - reverse loan repayments, i.e. restore `amount_paid` and `balance` from the deleted LoanTransactions.

  Alternatively, reject a same-day re-submission when the payload hash differs. Also store `local_id` (or keep a list of accepted local_ids) so retries are idempotent.

### DATA-18 Re-processing an opening balance duplicates shares, social fund and loans — Critical
- **Where:** `app/Services/OpeningBalanceService.php:55-60` (cleanup step) and `fanOut()` l.111, 164, 181, 280. Called from `Api/VslaOpeningBalanceController::reprocess` (l.462-500), `Admin/VslaOpeningBalanceController` (l.87-97) and the `VslaOpeningBalance::saved` hook (`app/Models/VslaOpeningBalance.php:55-90`).
- **Code:** the docblock says "Idempotent: clears previous Opening balance account_transactions first". The cleanup only soft-deletes `account_transactions WHERE description LIKE 'Opening balance%'`. It does not touch `project_shares`, `vsla_loans`, `loan_transactions` or `social_fund_transactions`, which `fanOut()` creates again on every run.
- **Evidence (cycle 173, opening balance reprocessed on 2026-04-15):**
  ```
  project_shares  2026-04-10: 20 rows 1,770,000 | 2026-04-15: 24 rows 2,038,000  (both live)
  social_fund_tx  2026-04-10: 20 rows   252,000 | 2026-04-15: 24 rows   291,000
  account_tx (group share, live) = 2,038,000    (old rows correctly soft-deleted: 20 share + 20 social_fund pairs)
  ```
  Reconciling project_shares against the share ledger, per cycle, finds two cycles that disagree: 173 (+1,770,000) and 243 (+36,000, see DATA-20).
- **Failure scenario:** the shareout reads `ProjectShare` (ShareoutCalculationService l.185, 235). For cycle 173 it would distribute against 3.8M of share value when only 2.04M was contributed, and the 20 members entered twice get double weight. The social fund balance is overstated by 252,000.
- **Fix:** make `process()` truly idempotent. Tag every row it creates with `opening_balance_id` (or `source='opening_balance'`), and delete or soft-delete the earlier rows in all five tables before running `fanOut`, inside the caller's transaction. Then repair cycle 173 by deleting the 2026-04-10 `project_shares` and `social_fund_transactions` rows.

### DATA-19 Opening-balance shares priced at UGX 1 when the cycle's share_value is NULL, which breaks shareout weighting — Critical
- **Re-verified on fao_prod_copy:** 481 live share rows priced at UGX 1 across 18 cycles. 4 cycles mix price 1 with price >1. 27 live VSLA cycles have share_value NULL or 0.
- **Where:** `app/Services/OpeningBalanceService.php:52` sets `$shareValue = (float) ($cycle->share_value ?? 1);`. Lines 106-118 then set `number_of_shares = floor(total/1)` and `share_price_at_purchase = 1`.
- **Evidence:**
  ```
  project_shares with share_price_at_purchase=1: 421 rows in 16 cycles
  e.g. id 1595 cycle 178: number_of_shares=610000, total_amount_paid=610000, price=1
  cycles mixing prices: 319 (1.00 & 5000.00 -> 1,569,040 shares for 1,769,000 UGX)
                        179 (1.00 & 5000.00 ->   795,055 shares for 1,070,000 UGX)
  projects 178/196/197 share_value = NULL
  ```
  `projects.shares_sold` totals 41,295,961 across 85 VSLA cycles because of this.
- **Failure scenario:** the shareout splits the fund by share count (`ShareoutCalculationService.php:279-286`). In cycle 319, a member who bought one share for 5,000 at a meeting holds 1/1,569,040 of the fund instead of 5,000/1,769,000. That is about 5,000 times less than their contribution entitles them to. Opening-balance members take almost the whole pot.
- **Fix:** refuse to process an opening balance when `share_value` is NULL or 0. Better, have the shareout weight members by `total_amount_paid` (money) rather than `number_of_shares`. To repair data, recompute `number_of_shares = total_amount_paid / cycle.share_value` for the 421 rows.

### DATA-20 Deleting or de-duplicating meetings leaves their ledger, shares and social fund rows live — High
- **Re-verified on fao_prod_copy:** 128 live account_transactions still belong to soft-deleted meetings. 2 VSLA cycles have SUM(project_shares) != SUM(member share ledger).
- **Where:** migration `2026_05_04_000001_add_data_integrity_constraints.php` step 1a soft-deletes duplicate meetings only. `VslaMeetingController::destroy` (l.720-770) allows deleting only `pending` meetings, but meetings were also removed outside the app (meeting 88 is gone and its ledger rows remain).
- **Evidence:**
  ```
  soft-deleted meetings with LIVE account_transactions:
    70: 18 rows (group share 50,000)   71: 18 (50,000)   73: 36 (100,000)
    81: 10 (25,000)                     85: 2 (4,000)     86: 44 (44,000)
    => 128 live rows, 273,000 UGX of group share receipts that belong to deleted duplicate meetings
  meeting 66 (soft-deleted 2026-04-23): ledger soft-deleted, but 5 project_shares (36,000) still live -> cycle 243 mismatch
  meeting_id=88: account_transactions 9109/9110 (10,000 share) reference a meeting row that no longer exists;
                 group 146 is itself soft-deleted (2026-04-02) while cycle 27 stays is_active_cycle=Yes
  ```
  `social_fund_transactions` has no `deleted_at` column, so its rows cannot be soft-deleted together with their meeting.
- **Failure scenario:** group share totals, member balances (`users.balance` comes from member share rows) and shareout amounts include the duplicate meetings' money.
- **Fix:**
  - Add a `VslaMeeting::deleting`/`deleted` hook, or a service method, that soft-deletes every row in the five tables with this meeting_id.
  - Add `deleted_at` to `social_fund_transactions`.
  - Add `meeting_id` to `project_shares`.
  - Run a one-off cleanup for meetings 70, 71, 73, 81, 85, 86, 66 and 88.
- **Additional evidence (merged from the model audit): the cause is a raw UPDATE in a migration**
  - **Cause:** Migration database/migrations/2026_05_04_000001_add_data_integrity_constraints.php:24-38 soft-deleted duplicate meetings with a raw `UPDATE vsla_meetings ... SET deleted_at = NOW()`. That bypasses the VslaMeeting `deleting` hook (DATA-43), so the duplicates' ledger rows stayed live.
  - **Evidence:**
  ```
  deleted meeting -> kept meeting (same group/date), live member rows / amount
  70 -> 72 (g445, 2026-04-08)   9 / 50,000   kept: 9 / 50,000
  71 -> 72 (g445, 2026-04-08)   9 / 50,000   kept: 9 / 50,000
  73 -> 74 (g643, 2026-04-06)  18 /100,000   kept: 18/100,000
  81 -> 80 (g445, 2026-04-22)   5 / 25,000   kept: 5 / 25,000
  85 -> 87 (g505, 2026-05-04)   1 /  4,000   kept: 32/ 66,000
  86 -> 87 (g505, 2026-05-04)  22 / 44,000   kept: 32/ 66,000
  Total live rows on soft-deleted meetings: 64 member + 64 group share rows, UGX 273,000 each side, 51 members
  ```
    `users.balance` is recomputed from all live rows, so these 51 members' savings are inflated by the duplicate amounts. Group share totals are inflated by the same amount.
  - **Fix:** Soft-delete (or contra-reverse) `account_transactions`, `project_shares` and `social_fund_transactions` where `meeting_id IN (70,71,73,81,85,86)`. Then call `AccountTransaction::updateMemberBalance()` for each affected user. In future, do data fixes through the model (`->each->delete()`) or a reversal service, never raw UPDATE.

### DATA-21 LoanTransaction sign convention is broken, so calculateLoanBalance is wrong for 756 of 1,437 loans (older copy: 519 of 822), and penalty/waiver endpoints corrupt loan balances — High
- **Re-verified on fao_prod_copy:** for 756 of 1,437 live loans, |SUM(loan_transactions.amount)| != vsla_loans.balance.
- **Where:**
  - `app/Models/LoanTransaction.php:129-132` computes the balance as `SUM(amount)`.
  - `app/Http/Controllers/LoanTransactionController.php`:
    - `balance()` l.75
    - `createPayment()` l.120-170
    - `addPenalty()` l.215-232: `$loan->balance = abs($newBalance)`
    - `addWaiver()`
  - Routes: `routes/api.php:377-382`.
- **Code:** the column comment and MeetingProcessingService (l.909, 920) store principal and interest as **negative** amounts. OpeningBalanceService l.181-202 stores principal **positive** and payments positive.
- **Evidence:**
  ```
  loan_transactions: principal/opening_balance 822 rows SUM=+122,479,900 ; payment/opening_balance 361 rows SUM=+38,269,800
  loans where |SUM(loan_transactions.amount)| != vsla_loans.balance: 519 / 822
  loan 1: loan_amount 200,000, amount_paid 200,000, balance 0.00, SUM(lt)=400,000
  ```
- **Failure scenarios:**
  - `GET /loan-transactions/1/balance` returns 400,000 and `is_paid=false` for a loan that is fully paid.
  - `POST /loan-transactions/penalty` with 1,000 on loan 1 sets `vsla_loans.balance = abs(400,000 − 1,000) = 399,000` and re-opens a closed loan.
  - A waiver on such a loan *increases* the balance.
  - `createPayment()` always fails and rolls back. It inserts `AccountTransaction(user_id => null, source => 'loan_repayment')`, but `user_id` is NOT NULL and `source` is `enum('disbursement','withdrawal','deposit')`, with strict mode on (`config/database.php:59`).
  - None of these endpoints checks that the loan belongs to the caller's group. Any valid token can penalise or waive any loan by ID.
- **Fix:** pick one sign convention (principal and interest negative) and migrate the 822 opening-balance principal rows to negative. Do not overwrite `vsla_loans.balance` from `calculateLoanBalance`; derive it from `total_amount_due − amount_paid`. Add a group-ownership check. Fix `createPayment` to set `owner_type`, `group_id`, `cycle_id`, a valid `source` and the borrower/creator user ids.

### DATA-22 `User::calculateAccountBalance()` (used for withdrawals) sums every row with that user_id, including group-side rows — High
- **Re-verified on fao_prod_copy:** 9,028 live group-side rows carry a user_id (126 users). 103 users have SUM(amount) above their own member share total; the largest overstatement is UGX 17,116,000.
- **Where:**
  - `app/Models/User.php:1073-1084`: `return $this->accountTransactions()->sum('amount');`
  - Used by `app/Http/Controllers/WithdrawRequestController.php:79,217`, `app/Models/WithdrawRequest.php:113` (`validateBalance`, which gates `approve`), `Admin/WithdrawRequestController.php:77,257` and `CommissionService.php:231`.
- **Root cause:** group-side ledger rows are written with `user_id = $meeting->created_by_id` (the person who submitted the meeting), e.g. MeetingProcessingService l.425, 516, 709, 931 and OpeningBalanceService l.122, 207, 242, 308. The sum also mixes the sign conventions shown in the context table.
- **Evidence:**
  ```
  group-side rows whose user_id is a member of that group: 4,354 rows, 61 users, 65,865,920 UGX
  user 4331: calculateAccountBalance = 2,356,000 ; own member share rows = 65,000
  user 7478: 679,000 vs own shares 5,000
  users whose calculateAccountBalance exceeds their own share total: 51
  ```
- **Failure scenario:** the secretary who submits meetings (user 4331) can create a withdraw request for up to UGX 2.36M while owning 65,000 in shares. `validateBalance()` lets the admin approve it.
- **Fix:** filter with `owner_type='member'`, and define the withdrawable balance per account type, e.g. shares minus outstanding loans for the current cycle. Store group rows with `user_id` NULL or a dedicated group account id, not the submitter's id.
- **Additional evidence (merged from the model audit):**
  - **Files:** app/Models/User.php:1049-1084; used in app/Http/Controllers/WithdrawRequestController.php:79 and :217, app/Admin/Controllers/WithdrawRequestController.php:77 and :257, app/Services/CommissionService.php:231, DashboardController.php:87 and :620. The group-side rows are written with the creator's id at app/Services/MeetingProcessingService.php:515-516 (`'user_id' => $meeting->created_by_id, 'owner_type' => 'group'`).
  - **Evidence:**
  ```php
  public function calculateAccountBalance() { return $this->accountTransactions()->sum('amount'); } // hasMany(user_id), no owner_type filter
  ```
  ```
  group-owned rows carrying a user_id: 61 distinct users, 4,354 rows, UGX 65,865,920
  user 1647: member-only sum 180,000   -> calculateAccountBalance() = 6,127,000
  user 4303: 42,000 -> 4,710,000 ; user 8208: 140,000 -> 4,162,032
  ```
    Even the member-only sum mixes shares (+), loans (−), repayments (+), fines (−) and social fund (−). It is not a withdrawable balance.
  - **Failure scenario:** A group secretary who records meetings sees a balance of many millions and can file a withdraw request that passes the "sufficient balance" check.
  - **Fix:** Filter by `owner_type = 'member'` and by the account types that make up withdrawable funds, or read the maintained `users.balance`. Stop putting the creator's id in `user_id` on group rows: use NULL and keep `created_by_id`.

### DATA-23 Cached `users.balance` / `users.loan_balance` are wrong: loan balance leaves out interest, and both ignore cycles — High
- **Re-verified on fao_prod_copy:** for 185 users, users.loan_balance != SUM(active vsla_loans.balance).
- **Where:** `app/Models/AccountTransaction.php:47-95` (`updateMemberBalance`, which runs on every created/updated/deleted/restored row).
- **Code:**
  - `balance = SUM(share)` over all cycles; it is never reduced by a shareout or withdrawal.
  - `loan_balance = |SUM(loan)| − SUM(loan_repayment)`, which is principal minus all repayments, so interest is never included.
  - `ProjectTransaction::updateUserAccountBalance` (`app/Models/ProjectTransaction.php:369-395`) is a second writer that overwrites the same columns with project_transactions-based values.
- **Evidence:**
  ```
  users whose loan_balance != SUM(active vsla_loans.balance): 157 ; total understatement 2,177,580 UGX
  ```
  For meeting-disbursed loans with interest, `loan_balance` reaches 0 once the principal is repaid, although the interest is still owed (`max(0, ...)` hides it).
- **Fix:** compute `loan_balance` from `vsla_loans` (SUM(balance) WHERE status='active'), and scope both values to the active cycle. Remove the second writer in ProjectTransaction. Make the recompute run once per meeting, not once per row: today each pair triggers 2–3 recomputes plus a `User::save()` with its own hooks.

### DATA-24 Opening-balance loans store interest in `balance` but not in `total_amount_due`, and the repayment endpoint silently writes the interest off — High
- **Re-verified on fao_prod_copy:** 173 active loans have balance != total_amount_due - amount_paid, a gap of UGX 2,763,680.
- **Where:**
  - `app/Services/OpeningBalanceService.php:164-179` sets `total_amount_due => $totalLoanAmount`, `balance => $loanBalance` and `interest_rate => cycle rate`.
  - `app/Http/Controllers/Api/VslaLoansController.php:307-309` recomputes `$loan->balance = $loan->total_amount_due - $loan->amount_paid`.
- **Evidence:**
  ```
  157 active loans where balance != total_amount_due - amount_paid (all opening-balance, amount_paid=0)
  e.g. loan 31: loan_amount 100,000, rate 10, total_amount_due 100,000, balance 106,000
  total gap = 2,177,580 UGX
  ```
- **Failure scenarios:**
  - Paying 6,000 on loan 31 through `POST vsla-loans/{id}/repayment` sets the balance to 94,000 instead of 100,000. 6,000 of interest disappears with no record.
  - The meeting repayment path (`MeetingProcessingService.php:676-681`) uses `balance − amount` instead, so the two paths disagree.
  - The shareout treats interest as `total_amount_due − loan_amount = 0` for these loans (DATA-30b).
- **Fix:** in OpeningBalanceService set `total_amount_due = loanBalance + amountPaid` (or principal + interest). Use one repayment routine for both paths, `balance = max(0, balance − amount)` with `amount_paid += amount`. Repair the 157 rows.

### DATA-25 Group cash balance in getGroupSavings leaves out all group rows flagged `is_contra_entry=1` — High
- **Re-verified on fao_prod_copy:** live group rows with is_contra_entry=1, which the filter drops, total UGX 121,314,850.
- **Where:** `app/Http/Controllers/Api/VslaTransactionController.php:866-886`.
- **Code:** `owner_type='group' AND (is_contra_entry=0 OR NULL)`.
- **Root cause:** the flag is set inconsistently on group rows. MeetingProcessingService l.435 (savings/fines group row = true), l.526 (shares group = false), l.719 (loan repayment group = true), l.943 (loan group = false); OpeningBalanceService l.252 and 318 (= true).
- **Evidence (live group rows):**
  ```
  fine            is_contra=1    5 rows      6,000
  loan            is_contra=0  822 rows -122,479,900
  loan_repayment  is_contra=1  362 rows  38,569,800   <- excluded
  share           is_contra=0 1836 rows 132,247,800
  social_fund     is_contra=1 1329 rows  17,522,220   <- excluded
  ```
- **Failure scenario:** `cash_balance` reported to the app equals shares − loans. It leaves out 38.6M of loan repayments, 17.5M of social fund and all fines and savings, so groups appear to have far less cash than they do.
- **Fix:** do not use `is_contra_entry` to tell sides apart; `owner_type` already does that. Remove the filter, or normalise the flag (group row = primary).

### DATA-26 Manifest financial summary uses UPPERCASE account types against lowercase data (shares/repayments/fines read as 0) and double counts in SQL — High
- **Where:** `app/Http/Controllers/Api/VslaGroupManifestController.php`:
  - l.254-270: `$transactions->where('account_type','SHARE')`, 'LOAN_REPAYMENT', 'FINE', 'WELFARE', ['DEPOSIT','SAVING'] on a PHP Collection, where comparison is case-sensitive.
  - l.403-412: member summary, same problem, and no owner_type filter.
  - l.716-718: SQL `account_type='LOAN_REPAYMENT'` matches case-insensitively under the `_ci` collation but has no owner_type filter.
  - l.735: highest-saver loop, one query per member.
- **Evidence (read-only run of `getCycleFinancialSummary` for cycle 198):**
  ```
  total_shares_sold 0, total_share_value 0, total_loans_repaid 0, total_fines_collected 0,
  group_cash_balance -2,400,000      (actual member share ledger for cycle 198 = 1,801,000)
  ```
- **Failure scenario:** `GET groups/{id}/manifest`, which the mobile app syncs, shows zero share value and a negative cash balance for every group. Fixing only the case would expose the next bug: with no `owner_type='member'` filter, each amount is counted twice (group + member), and the submitter's member summary picks up all group rows (user 4331: 1,866,000 instead of 65,000).
- **Fix:** use lowercase values and filter `owner_type`. For members, also filter `owner_type='member'`. Compute the totals in SQL with GROUP BY instead of loading every row.

### DATA-27 Social fund contributions submitted in meetings are never written to account_transactions — Medium
- **Where:** `app/Services/MeetingProcessingService.php:1132-1191` creates only a `SocialFundTransaction`. Opening balances (OpeningBalanceService l.280-322) write both tables.
- **Evidence:** `social_fund_transactions` vs the group social_fund ledger, per cycle:
  ```
  27: 1,000 vs NULL | 173: 543,000 vs 291,000 | 179: 172,000 vs 109,000 | 198: 699,500 vs 632,000
  243: 303,000 vs 268,000 | 319: 566,000 vs 515,000 | 332: 60,000 vs NULL | 334: 1,021,000 vs 937,000
  ```
- **Failure scenario:** reports built on account_transactions (dashboard l.282, getGroupSavings l.886) understate the social fund. If the app also sends `accountType='social_fund'` in `transactions_data`, the contribution is posted once in each table, with inconsistent totals.
- **Fix:** post a ledger pair for each meeting social-fund contribution, or make `social_fund_transactions` the only source of truth and stop writing social_fund rows to the ledger.

### DATA-28 Meeting loan repayment: no ownership check, no lock, and LoanTransaction fields silently dropped or mislabelled — Medium
- **Where:** `app/Services/MeetingProcessingService.php:604-704`.
- **Code:**
  - `VslaLoan::find($loanId)` has no check that `loan.cycle_id` equals the meeting's cycle or group. A loan from another group can be repaid, and the cash is posted to this group's ledger.
  - There is no `lockForUpdate()` on the loan row, so two concurrent submissions can both reduce the balance.
  - `LoanTransaction::create([... 'cycle_id','meeting_id','borrower_id','transaction_type'=>'repayment','balance_before','balance_after' ...])` sets fields that are neither fillable nor columns (only `transaction_type` exists), and it never sets `type`.
- **Evidence:** `loan_transactions` has no cycle_id, meeting_id, borrower_id, balance_before or balance_after columns. The only meeting repayment row is stored as `type='principal'` (MySQL's implicit first enum value) with `amount=+300,000` and `transaction_type='repayment'`, so the repayment is recorded as a principal entry.
- **Fix:** validate that the loan belongs to the meeting's cycle, lock the loan row, set `type => 'payment'`, and either add the missing columns or remove them from the payload.

### DATA-29 VslaGroupStatsController counts soft-deleted ledger rows and derives repayments incorrectly — Medium
- **Where:** `app/Http/Controllers/Api/VslaGroupStatsController.php:93-150`.
- **Code:**
  - It uses `DB::table('account_transactions')` and `DB::table('vsla_loans')`, which do not apply the SoftDeletes scope.
  - `totalRepaid = totalDisbursed − activeLoans`, but `activeLoans` includes unpaid interest.
  - `totalInterestEarned = 0` is hard-coded.
  - `totalCash` subtracts the interest-inclusive loan balances.
- **Evidence:**
  ```
  group share, cycle 173: incl. deleted 3,808,000 vs live 2,038,000 ; cycle 243: 3,092,000 vs 3,056,000
  soft-deleted group rows overall: 45 rows, 2,058,000 UGX
  ```
- **Fix:** add `whereNull('deleted_at')` (or use the Eloquent models). Compute repayments from `loan_repayment` rows and interest from loans.

### DATA-30 ShareoutCalculationService: sign error on savings, misleading interest formula, open transactions, no ledger on completion — Medium
- **Where:** `app/Services/ShareoutCalculationService.php`.
  - **(a) l.179-182, 299-317:** sums member `savings`/`welfare` rows as positive, but MeetingProcessingService l.416 stores member contributions as `-$amount`. `total_distributable_fund` would shrink by the savings instead of growing. This is latent today: there are 0 `savings` rows because the app sends savings as shares.
  - **(b) l.199-210, 328-339:** the "only paid interest" calculation reduces algebraically to `interestPaid = total_amount_due − loan_amount`, because balance = due − paid. So it always counts the full interest, paid or not. It is 0 for the 157 opening-balance loans (DATA-24), whose interest sits only in `balance` and is still deducted from borrowers. The fund therefore understates assets for those cycles.
  - **(c) l.44-66 and l.375-393:** `return` after `DB::beginTransaction()` without `rollBack()`, which leaves the connection inside an open transaction for the rest of the request.
  - **(d) l.373-421:** `completeShareout` marks distributions paid and closes the cycle but posts no payout ledger rows. `users.balance` keeps showing the shares after the shareout, and the withdraw balance (DATA-22) still includes them.
  - **(e)** Float division with no rounding: stored `final_payout` values (decimal 2) do not add up to the fund, and the remainder is not allocated.
  - **Also:** shares come from `ProjectShare`, which carries the duplicates from DATA-18, DATA-19 and DATA-20.
- **Fix:** use `abs()` or member-side sign rules consistently. Compute interest earned from actual repayment allocations. Roll back before every early return (or use `DB::transaction()` closures). Post a shareout payout ledger pair per member. Round per member and give the remainder to the largest holder.

### DATA-31 Live `/vsla-transactions/*` endpoints write to a second, empty ledger (project_transactions) that no reader uses — Medium
- **Where:**
  - `app/Services/VslaTransactionService.php` (all methods), injected into `Api/VslaTransactionController` (routes `routes/api.php:224-234`: /create, /saving, /loan-disbursement, /loan-repayment, /fine, /member-balance, /group-balance).
  - `ProjectTransaction::createWithContra` / `calculateUserBalances` (`app/Models/ProjectTransaction.php:219-330`).
- **Code:**
  - `$groupId = $project->ffs_group_id ?? $user->group_id`, but `projects` has no `ffs_group_id` column (only `group_id`). The fallback makes the membership check `$user->group_id != $groupId` always pass, so a member can post to any group's cycle.
  - `loan_max_multiplier` is not a column either; the real one is `maximum_loan_multiple`.
  - `createTransaction` maps welfare/social_fund/charge/share_out/other to `recordFine`, so they are stored as `account_type='fine'`.
  - `getMemberBalance` and `getDashboardSummary` read project_transactions, which has 0 rows, so they always return 0.
- **Failure scenario:** anything posted through these endpoints never appears in the dashboard, manifest, shareout or `users.balance` (all of which read account_transactions). It also clobbers `users.balance` through the second writer (DATA-23).
- **Fix:** route these endpoints through the account_transactions writer (or retire them), use `projects.group_id`, and map account types correctly.

### DATA-32 `POST vsla-loans/{id}/repayment` is not atomic, posts no ledger entry, and is unlocked — Medium
- **Where:** `app/Http/Controllers/Api/VslaLoansController.php:258-340`.
- **Code:**
  - `$loan->save()` and then `LoanTransaction::create(...)` run with no DB transaction.
  - No `type` is set, so it is stored as 'principal'.
  - No AccountTransaction is created, so the group cash and `users.loan_balance` never change.
  - There is no `lockForUpdate`.
  - Validation is `min:0`, so zero-amount payments are accepted.
  - The ownership check is skipped when `$user->group_id` is empty.
- **Fix:** wrap the work in `DB::transaction` with `lockForUpdate`, post the ledger pair, set `type='payment'`, require `amount > 0`, and always enforce group ownership.

### DATA-33 Loan interest is charged once, flat, whatever the cycle's interest frequency or the loan duration — Medium (business-rule check)
- **Where:** `app/Models/VslaLoan.php:43-57` computes `interest = principal × rate/100`, and `MeetingProcessingService.php:902` does the same. Neither uses `projects.interest_frequency`, `monthly_loan_interest_rate`, `weekly_loan_interest_rate` or `duration_months`.
- **Evidence:** 216 VSLA cycles have `interest_frequency='Monthly'` (average rate 9.69%) and 8 are 'Weekly'. All 822 loans have `duration_months=3`, but each was charged a single 10%.
- **Fix:** confirm the rule with FAO. If rates are per period, compute `rate × periods`. Store the interest amount on the loan explicitly.

### DATA-34 Withdrawal approval: no transaction, no lock, and the ledger row has no owner_type/group/cycle — Medium
- **Where:** `app/Models/WithdrawRequest.php:122-180`.
- **Code:** `AccountTransaction::create([... no owner_type, group_id, cycle_id ...])` runs, then `$this->save()`. These are not in a DB transaction and the request row is not locked. Two concurrent approvals can both pass the `status==='pending'` check. Because `owner_type` is NULL, `updateMemberBalance` ignores the row, so `users.balance` is never reduced.
- **Fix:** `DB::transaction` with `WithdrawRequest::lockForUpdate()`, and set `owner_type='member'` plus the group and cycle.

### DATA-35 Social-fund withdrawal check is race-prone and spans all cycles when cycle_id is omitted — Low
- **Where:** `app/Http/Controllers/Api/SocialFundTransactionController.php:179-195` and `SocialFundTransaction::getGroupBalance` (`app/Models/SocialFundTransaction.php:120-130`).
- **Code:** `cycle_id` is nullable. Without it, the balance check sums every cycle's fund. The check runs without a transaction or lock. Balances are already inflated by the DATA-18 duplicates (cycle 173 +252,000).
- **Fix:** require `cycle_id`, and lock inside a transaction.

### DATA-36 39 "completed" meetings carry financial payloads but have no postings at all — Medium (data)
- **Re-verified on fao_prod_copy:** the same 39 meetings and UGX 4,620,000, so this is still unresolved.
- **Evidence:**
  ```
  completed, not deleted, no account_transactions, but totals > 0: 39 meetings (created 2026-03-19..2026-03-29)
  sum total_share_value 4,620,000 ; total_loans_repaid 39,000 ; fines 400
  e.g. meeting 41: share_purchases_data has 23 entries; no project_shares, attendance, loans or social fund rows for meetings <= 50
  ```
- **Cause:** not determined; possibly a group reset or a manual purge that left the meeting rows behind. These cycles' shareouts would leave out 4.62M of member contributions.
- **Fix:** decide whether to re-process these meetings (with the DATA-17 fix in place) or mark them `processing_status='failed'`.

### DATA-37 Per-row balance recompute hooks: heavy, and errors are swallowed — Low
- **Re-verified on fao_prod_copy:** SUM(projects.total_investment) for VSLA cycles = 0, against UGX 272,113,020 of live project_shares.
- **Where:**
  - `AccountTransaction::boot` (l.47-60) runs `updateMemberBalance` after every create and also after the follow-up `update(['contra_entry_id'=>...])`.
  - `ProjectShare::boot` (`app/Models/ProjectShare.php:35-78`) calls `Project::recalculateFromTransactions()` for every share row. That method loads every project_transaction and the disbursements and saves the project.
- **Impact:** a 30-member meeting runs about 90 user recomputes and 30 project recomputes inside the request transaction. `updateMemberBalance` catches every exception and only logs it, so a failure leaves the cached balance stale with no error. For VSLA cycles, `recalculateFromTransactions` writes `total_investment=0` (85 cycles with shares, all with total_investment 0), because it reads the empty project_transactions table.
- **Fix:** recompute once per user per meeting after processing, from a queued job or explicitly at the end of `processMeeting`.

---

## 4. Model hooks, accessors and mass assignment

### DATA-38 Unauthenticated endpoints can change any user's type/status/phone/email and hard-delete any user (Critical)
- **Files:** routes/api.php:754-760 (no middleware; the `api` group in app/Http/Kernel.php:42-46 has only throttle and bindings); app/Http/Controllers/ApiResurceController.php:3823 `insurance_user_update`, :3944 `insurance_user_delete`
- **Evidence:**
```php
// ApiResurceController.php ~3845-3862
$basicFields = ['name','first_name','last_name','email','phone_number','avatar','sex','dob','address','status','user_type','country'];
foreach ($basicFields as $field) { if ($request->has($field)) { $user->$field = $request->input($field); } }
// :3954
// Soft delete the user
$user->delete();
```
  The User model has no `SoftDeletes` trait and `users` has no `deleted_at` column (`SHOW COLUMNS FROM users LIKE 'deleted_at'` returns nothing). So `delete()` is a **hard delete**.
- **Failure scenario:** An anonymous caller sends `PUT /api/insurance-users/123 {"user_type":"Admin"}` to escalate themselves. Or they change a victim's `phone_number` and take over the account through OTP or password reset. Or they send `DELETE /api/insurance-users/{id}` for every id and erase the 7,538 users. That leaves their account_transactions, loans and shares orphaned. Only id 1 is protected (User.php:159).
- **Fix:** Put `EnsureTokenIsValid` on these routes and add an authorization check (the caller is the same user or an admin). Remove `user_type` and `status` from self-editable fields. If deletes must be recoverable, add `SoftDeletes` to User with a `deleted_at` migration.

### DATA-39 Public registration lets the caller choose `user_type` (role=Admin), which unlocks admin-only ledger writes (Critical)
- **Files:** app/Http/Controllers/Api/UserRegistrationController.php:23 and :154 (route `POST users/register`, routes/api.php:37, unauthenticated); app/Http/Controllers/AccountTransactionController.php:117
- **Evidence:**
```php
$role = $request->input('role', 'Farmer');          // only 'required|string'
...
'user_type' => $role,                                // mass-assigned via User::create($userData)
// AccountTransactionController::store
if (!$currentUser || $currentUser->user_type !== 'Admin') { return Utils::error('Only administrators ...'); }
```
  `User::isAdmin()` (User.php:1185) is also only `strtolower($this->user_type) === 'admin'`. It gates cross-group meeting access in VslaMeetingController.php:397, 418, 539, 616, 641, 726.
- **Failure scenario:** Anyone registers with `role=Admin`. They can then POST deposits or withdrawals to `/api/account-transactions` for any user, and read meetings of every group.
- **Fix:** Whitelist the roles allowed at self-registration (`in:Farmer,Service Provider,...`) and never allow Admin. Set `user_type` server-side. Remove `user_type`, `balance`, `loan_balance`, `ip_id`, `is_group_*`, `member_code` and `password` from `User::$fillable`, and remove `protected $guarded = []` (User.php:27-91).

### DATA-40 MemberController::sync writes arbitrary request keys straight onto User, bypassing $fillable (High)
- **File:** app/Http/Controllers/MemberController.php:793-815 (route `POST /api/members/sync`, routes/api.php:418)
- **Evidence:**
```php
$member = User::find($memberId);
foreach ($data as $key => $value) {
    if ($key !== 'id' && $key !== 'member_code' && $key !== 'password') {
        $member->$key = $value;          // direct attribute set, $fillable not consulted
    }
}
$member->save();
```
  There is no check that `$memberId` belongs to the caller's group or IP.
- **Failure scenario:** Any token holder can send `{"action":"update","data":{"id":<any>,"balance":9999999,"loan_balance":0,"user_type":"Admin","group_id":X,"ip_id":Y,"is_group_admin":"Yes"}}`. This edits any user's balance or role. It can also move members across groups and IPs. An unknown key throws a SQL 1054 error, which rolls back the whole sync batch.
- **Fix:** Use an explicit whitelist: `$member->fill(Arr::only($data, [...profile fields...]))`. Authorize with `$member->group_id === $caller->group_id` (or a facilitator/IP scope check).

### DATA-41 User `updating` hook rewrites phone_number on every save, corrupting 1,972 phone numbers (older copy: 1,035) (High)
- **Re-verified on fao_prod_copy:** 1,660 phone numbers match '^\+256[A-Z]{3}MEM' and 1,972 match '^\+256[A-Za-z]'. The number grows with every ledger write.
- **File:** app/Models/User.php:141-145 (updating → `sanitizeData`), :219-222, :356-389 `normalizePhoneNumber`
- **Evidence:**
```php
// sanitizeData runs on EVERY create/update, not only when phone_number is dirty
if (!empty($user->phone_number)) {
    $user->phone_number = self::normalizePhoneNumber($user->phone_number);
}
// normalizePhoneNumber: strips "-", then "Otherwise, assume it needs +256 prefix"
return '+256' . $phone;
```
  Members without phones get `phone_number = member_code` (for example `NAK-MEM-26-0353`). The first later save turns that into `+256NAKMEM260353`.
```
SELECT COUNT(*) FROM users WHERE phone_number REGEXP '^\\+256[A-Z]{3}MEM'      -> 723
SELECT COUNT(*) FROM users WHERE phone_number REGEXP '^\\+256[A-Z]'            -> 1035   (incl. '+256Nonumber', '+256Hasnophone', ...)
intact placeholders  phone_number REGEXP '^[A-Z]{3}-MEM-'                      -> 865, of which with transactions -> 0
corrupted users that have member account_transactions                           -> 459
```
  The split matches the trigger. Every member ledger write calls `AccountTransaction::updateMemberBalance()`, which runs `$user->save()`, which runs `updating`, which runs `sanitizeData`. So every member who ever transacted has had the placeholder rewritten.
- **Failure scenario:** SMS sends go to junk numbers and phone-based lookup or login fails. The `validateUniqueFields` skip (`/^[A-Z]{3}-MEM-/`) no longer matches, so junk values take part in uniqueness checks. Free-text entries such as "No phone number" are also stored as `+256Nophonenumber`.
- **Fix:** Normalize only when `isDirty('phone_number')`. Reject or null values that are not digits after cleaning instead of prefixing `+256`. Use NULL instead of copying the member_code into phone_number. Data repair: `UPDATE users SET phone_number=NULL WHERE phone_number REGEXP '^\\+256[A-Za-z]'`, or restore from member_code if the app needs a placeholder.

### DATA-42 FfsGroup `deleting` hook hard-deletes all financial history and all member users on a soft delete, with no transaction (High)
- **File:** app/Models/FfsGroup.php:421-517 (the model uses `SoftDeletes`, line 11)
- **Evidence:**
```php
static::deleting(function ($group) {
    ... \DB::table('vsla_loans')->whereIn('cycle_id', $cycleIds)->delete();
    ... \DB::table('account_transactions')->where('group_id', $id)->delete();
    ... \DB::table('projects')->whereIn('id', $cycleIds)->delete();
    \DB::table('users')->where('group_id', $id)->delete();   // line 516, hard delete of members
});
```
  `ffs_groups` has 358 soft-deleted rows out of 649. 7 users still point at soft-deleted groups.
- **Failure scenario:** An admin clicks "delete" on a group. The group itself is only soft-deleted and looks restorable, but its ledger, loans, meetings, cycles and **member accounts** are permanently gone. The raw `DB::table('users')` delete skips the User `deleting` guard (protected id 1) and all model events. Anything that fails halfway leaves a half-deleted group, because nothing is wrapped in `DB::transaction`. The hook also skips `farms`, `ffs_session_participants`, `ffs_training_sessions.group_id`, `vsla_meeting_attendance` linked by member, and others, so it is not a complete cascade either.
- **Fix:** On soft delete, only soft-delete the group (and optionally soft-delete its children). Do the hard cascade only in `forceDeleting`, inside `DB::transaction`. Never delete users: null their `group_id` instead, which the comment "Users/members are intentionally preserved (only unlinked)" already promises.

### DATA-43 VslaMeeting `deleting` hook force-deletes the ledger but does not reverse repayments on older loans (High)
- **File:** app/Models/VslaMeeting.php:37-94
- **Evidence:** On a **soft** delete of a meeting the hook calls `AccountTransaction::withTrashed()->where('meeting_id',..)->forceDelete()`, `VslaLoan::withTrashed()->where('meeting_id',..)->forceDelete()`, and `LoanTransaction` forceDelete for loans *created* in that meeting. `loan_transactions` has no `meeting_id` column (`SHOW COLUMNS FROM loan_transactions`), so repayment rows (`type='payment'`, 361 rows) made in this meeting against loans from earlier meetings are not touched. Those loans' `vsla_loans.amount_paid` and `balance` are not reverted either. Meanwhile the matching `account_transactions` `loan_repayment` rows **are** force-deleted. Nothing is wrapped in a transaction.
- **Failure scenario:** A meeting that recorded repayments is deleted. The member ledger shows the loan as outstanding again (loan_balance goes up), but `vsla_loans.balance` still shows it as repaid. The loan screens and the ledger disagree, and the shareout computes on whichever source it reads. Soft-deleting and then restoring the meeting cannot bring the rows back.
- **Fix:** Implement a proper meeting reversal service. It should write contra entries (or soft-delete in step with the meeting), revert `amount_paid` and `balance` on every loan touched by the meeting's repayments, and record `meeting_id` on `loan_transactions`. Run it in `DB::transaction`, and do the hard cascade only on `forceDeleting`.

### DATA-44 AccountTransaction boot hook: full recompute plus a User save on every row; errors swallowed; bulk deletes bypass it (Medium)
- **File:** app/Models/AccountTransaction.php:46-99; User.php:141-156, :457-495
- **Evidence:** Each `created`/`updated`/`deleted`/`restored` event for a member row runs `User::find` and a `SUM(...)` over account_transactions, then `$user->save()`. That save fires the User `updating` hook: `sanitizeData`, then `validateUniqueFields`, which runs `SELECT ... WHERE email=?` and `SELECT ... WHERE phone_number=?`. Neither `users.email` nor `users.phone_number` is indexed (`information_schema.statistics` shows only `member_code` among the identity columns), so both are full scans of 7,538 rows. That is about 5 queries plus 1 UPDATE per ledger row. A 30-member meeting with savings, social fund and repayment rows means about 90 member rows, or 450+ queries inside the meeting transaction.
  - `catch (\Exception $e) { Log::error(...) }`: if the user save throws (for example a uniqueness exception), the meeting commits and `users.balance` silently stays stale.
  - On `updated`, if `user_id` changed, only the new user is recomputed.
  - Query-builder deletes bypass the hook completely: Disbursement.php:71, DisbursementController.php:270, OpeningBalanceService.php:56-60, FfsGroup.php:487, VslaGroupManagementController.php:556.
  - Current state: no drift (0 users where `balance` differs from the share SUM).
- **Fix:** Recompute once per affected user at the end of meeting processing (collect user ids, then run one grouped UPDATE...JOIN), not per row. Update balances with `DB::table('users')->where('id',..)->update([...])` (or `saveQuietly`) so the user hooks don't run. Rethrow inside transactions. Index `users.phone_number` and `users.email`.

### DATA-45 ProjectShare and ProjectTransaction hooks run Project::recalculateFromTransactions on every share; VSLA cycles get zeroed totals and drifting shares_sold (Medium)
- **Re-verified on fao_prod_copy:** VSLA cycles have total_investment = 0 against UGX 272.1M of shares.
- **Files:** app/Models/ProjectShare.php:36-77; app/Models/ProjectTransaction.php:47-110; app/Models/Project.php:238-288; triggered per share at MeetingProcessingService.php:504 and OpeningBalanceService.php:111
- **Evidence:** Each share insert loads every ProjectTransaction of the cycle, then runs `SUM(disbursements)`, then `SUM(project_shares)`, then saves the project (4 queries). For VSLA cycles `project_transactions` is empty (0 rows), so `total_investment`, `total_returns` and `total_profits` are always overwritten with 0:
```
projects is_vsla_cycle=Yes: 224 rows, SUM(total_investment)=0, while project_shares on those cycles total UGX 134,053,800
cycles where shares_sold != SUM(live project_shares.number_of_shares): 24   (shares deleted via DB::table bypass the hook)
```
  The Project `$appends` (`roi_percentage`, `net_profit`, `available_for_disbursement`) are therefore meaningless for cycles.
  Side note for the financial section: `project_shares.number_of_shares` has a max of 610,000 and a sum of 41.29M against UGX 134M paid. That looks like amounts stored as share counts.
- **Fix:** Skip the recalculation when `is_vsla_cycle = 'Yes'`, or compute cycle totals from `account_transactions`. Recompute once per meeting rather than per share.

### DATA-46 Disbursement hooks double-count returns, have no transaction, and delete by a heuristic match (Medium; latent, disbursements = 0 rows)
- **File:** app/Models/Disbursement.php:37-90; Project.php:257-274
- **Evidence:**
```php
// created: writes ProjectTransaction source='returns_distribution' amount=X, then recalc:
case 'returns_distribution': $total_returns += abs($transaction->amount);   // X
$total_returns += abs(Disbursement::where('project_id',..)->sum('amount')); // X again
// deleting: deletes ProjectTransaction matched by project_id+source+date+amount+created_by_id
```
  The disbursement insert, the ProjectTransaction insert, N investor AccountTransactions and two recalcs run without `DB::transaction`. The investor AccountTransactions are created without `owner_type` (enum, NULL default), so they never update `users.balance`.
- **Failure scenario:** `total_returns` is twice the real figure and `available_for_disbursement` is too low. Two identical disbursements on the same day: deleting one removes both expense rows. A failure halfway leaves a disbursement with partial investor credits.
- **Fix:** Count returns from one source only. Link the ProjectTransaction by `related_disbursement_id`. Wrap the created-hook work in a transaction (or move it to a service). Set `owner_type='member'`.

### DATA-47 VslaOpeningBalance `saved` hook runs the whole opening-balance fan-out from inside a model event (Medium)
- **File:** app/Models/VslaOpeningBalance.php:55-90; app/Services/OpeningBalanceService.php:36-63
- **Evidence:** Any save of a record with `status='submitted'` and `is_processed=false` (except the first insert) calls `OpeningBalanceService::process()`. That method bulk-deletes the old opening-balance ledger rows with a query builder (`AccountTransaction::where(...)->delete()`, so member balances are not recomputed for members dropped from the sheet) and recreates shares, loans and social fund rows. It passes `$ob->submitted_by_id ?? $ob->group_id` as `$processedById`, so a **group id** is used as a user id in `created_by_id` when the submitter is null. Failures are swallowed by the `catch (\Throwable)` that only logs. Current data: all 66 rows are `processed/1`, so nothing is pending.
- **Fix:** Move processing to an explicit job or command. Do not fall back to `group_id`. After the bulk delete, recompute balances for every member who previously had opening-balance rows.

### DATA-48 `$appends` accessors that query the database cause N+1 on every serialization (Medium)
Every `toArray()` or JSON response runs these accessors, unless the relation is eager loaded (accessors that call `->count()` or `::find` cannot be eager loaded at all).

| Model (file:line) | Appended attrs that query the DB | Queries/row | Worst listing seen |
|---|---|---|---|
| FfsGroup (FfsGroup.php:263, :268) | district_name, facilitator_name | 2 | `ApiResurceController::ffs_groups` (:1512-1545) selects 291 active groups with no `with()`, about 582 extra queries |
| FfsTrainingSession (FfsTrainingSession.php:208-250) | group_name, group_ids/group_names, facilitator_name, co_facilitator_name, participants_count, resolutions_count, attending_facilitator_names | up to 7 | `FfsTrainingSessionController@index` (:174-224) eager loads relations but is unpaginated. `participants_count`/`resolutions_count` accessors **override** the `withCount()` used at :660, so 2 COUNT queries per row always run |
| AesaSession (AesaSession.php:260-315) | group/facilitator/district/subCounty/village names, observations_count, crop_observations_count | 7 (5 after the controller's eager load) | `AesaController@index` (:117-152) does `withCount('observations')`, but the accessor at :308 ignores it and re-counts. district/subCounty/village are not eager loaded. Unpaginated unless `per_page` is given |
| ProjectShare (ProjectShare.php:97, :102) | investor_name, project_title | 2 | 1,861 rows. `ProjectShareController@index` (:34) eager loads `project` but not `investor` |
| VslaShareout (VslaShareout.php:109, :114) | cycle_name, group_name | 2 | small (4 rows) |
| VslaShareoutDistribution (VslaShareoutDistribution.php:79, :95) | member_name, is_payable (loads shareout) | 2 | per member per shareout (VslaShareoutController.php:230, :290) |
| Farm (Farm.php:105, :110) | enterprise_text, farmer_text | 2 | small |
| FarmActivity (FarmActivity.php:133) / FfsSessionResolution (:105) / AesaObservation (:278) / AesaCropObservation (:319) | protocol_text / responsible_person_name / owner_display / farmer_display | 1 | small |
| Location (Location.php:216) | name_text calls `Location::find($this->parent)` | 1 | 16 rows locally |
| Product (Product.php:105, :127), ProductCategory (:36-55), ChatHead (:12-24) | rates (Image query), category_text; 3 identical `Product::where('category')->count()`; 2 unread counts | 2-3 | legacy e-commerce, small |

- **Fix:** Remove relation-based attributes from `$appends` and add them in the API resource when the relation is loaded (`$this->relationLoaded('district') ? ... : null`). For counts, use `withCount` and make the accessor read `$this->attributes['participants_count'] ?? $this->participants()->count()`. Eager load `district,facilitator` in `ffs_groups`.

### DATA-49 AESA observation create/update mass-assigns request data, including `ip_id` and `created_by_id` (Medium)
- **Files:** app/Http/Controllers/Api/AesaController.php:541-553 (`AesaObservation::create($request->all())`), :591 (`update($request->except([...]))`), :1074-1081 and :1140 (same for AesaCropObservation); `$fillable` contains `ip_id` and `created_by_id` (AesaObservation.php:113-114, AesaCropObservation.php:109-110)
- **Failure scenario:** The `creating` hook only fills `ip_id` and `created_by_id` when they are empty (AesaObservation.php:198-212). A client posting `ip_id=<other IP>` or `created_by_id=<someone>` plants records in another IP's scope (IP scoping relies on `ip_id`) and spoofs the author.
- **Fix:** Use `$request->only([...observation fields...])` or `$validated`. Always set `ip_id` from the session and `created_by_id` from the authenticated user, and remove both from `$fillable`.

### DATA-50 Farm `created` hook dereferences a possibly-null enterprise after the insert; FarmActivity `saved` saves the farm twice (Low)
- **Files:** app/Models/Farm.php:232-235 → :192-194 (`$protocols = $this->enterprise->productionProtocols;`); FarmActivity.php:277-283 → Farm.php:136-177
- **Evidence:** `creating` guards with `if ($farm->enterprise && ...)`, but `created` does not. Enterprise uses SoftDeletes, so a farm created against a soft-deleted enterprise (`farms.enterprise_id` is NOT NULL but has no FK) throws "Attempt to read property on null" **after** the farm row is inserted. Nothing wraps it in a transaction, so a farm is left with no activities. Every FarmActivity status change runs `updateActivityCounts()` (2 COUNTs + save) and `updateScore()` (SUM, load all activities, save).
- **Fix:** Use `optional($this->enterprise)->productionProtocols ?? collect()` and wrap creation in a transaction. Combine the two farm updates into one `saveQuietly`.

### DATA-51 Insurance payment hooks recompute totals before the delete and cascade on every row (Low; tables empty)
- **Files:** app/Models/InsuranceSubscriptionPayment.php:98-104 (`deleting` → `updateBalances()` runs while the row still exists, so totals still include it); InsuranceSubscription.php:85-101, :366-396 (each payment created by `prepare()` triggers 4 aggregates, a subscription save, then `InsuranceProgram::updateStatistics()`)
- **Fix:** Use `deleted` instead of `deleting`. Recompute once after `prepare()`.

### DATA-52 `$fillable` and scopes reference columns that do not exist (Low)
- `OneSignalDevice` (app/Models/OneSignalDevice.php): 17 fillable columns are missing from `onesignal_devices`, which has only `id,user_id,player_id,device_type,app_version,last_active,created_at,updated_at`. `scopeActive` (`is_active`, :63-66), `scopeRecentlyActive` (`last_seen_at`, :71-74) and `getStatistics()` (:153-163) all reference missing columns. As a result the admin page `NotificationController::devices` (:712) throws SQL 1054. `syncWithOneSignal()` (:169) would also fail.
- `OtpCode`: `$fillable` is `phone_number, otp_code, expires_at, is_verified, attempts`, but the `otp_codes` table has only `id, created_at, updated_at`. Any `OtpCode::create()` fails. No live callers were found, so this is effectively dead.
- `Project`: 8 fillable columns are missing (`cycle_start_date, cycle_end_date, loan_interest_rate_weekly, loan_interest_rate_monthly, loan_repayment_frequency, max_loan_amount, penalty_percentage, is_active`). No current write path passes them to Project, but any future `Project::create($request->all())` would 1054.
- **Fix:** Remove the dead fillables and scopes, or add migrations.

### DATA-53 Two writers with different formulas update `users.balance` (Low; legacy path currently idle)
- **Files:** AccountTransaction.php:66-99 (`balance` = member shares; `loan_balance` = |loans| − repayments) vs ProjectTransaction.php:369-395 (`balance` = savings − |fines|; `loan_balance` = |loans|, **scoped to one project**). The ProjectTransaction hook fires for `owner_type='user'`. `project_transactions` currently has 0 rows, so it is idle.
- **Failure scenario:** If any legacy path writes a ProjectTransaction for a user, `users.balance` and `loan_balance` flip to a different formula and a single-cycle scope until the next AccountTransaction write.
- **Fix:** Delete `ProjectTransaction::updateUserAccountBalance` and its hook calls.

### DATA-54 ChatMessage `created` hook sends a push notification synchronously (Low; legacy)
- **File:** app/Models/ChatMessage.php:15-35. It runs `ChatHead::find` and an HTTP push (`Utils::sendNotification`) inside the insert request.
- **Fix:** Queue it.
---

**Checked and clean:** no model defines `$with`. The `$guarded = []` KPI models (FfsKpiIndicator, FfsKpiIpEntry, FfsKpiFacilitatorEntry) are written only through Laravel-Admin forms, with no API mass assignment. Recursive-save loops: none found (Project uses `saveQuietly`, VslaOpeningBalance uses `updateQuietly`, and FarmActivity→Farm does not loop back). Date accessors that call `->format()` without a null check (Disbursement.php:193, LoanTransaction.php:87) are safe because both columns are NOT NULL with 0 NULL rows.
