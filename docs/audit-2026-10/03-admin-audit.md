# FAO FFS-MIS — Admin Web Portal Audit (laravel-admin 1.8.19 / Laravel 8.83)

Audit date: 2026-10-31. Read-only. No project files or database rows were changed.
Live schema: `fao_prod_copy`, which has the same 128 tables and 2,076 columns as `fao_ffs_mis`. Row counts below come from `fao_prod_copy`.
How it was checked:
- `artisan route:list --path=admin` (486 actions). A reflection script confirmed that every routed class and method exists.
- A static script compared every `$grid->column/filter/form/show` field in the routed controllers against the live schema.
- SQL SELECTs were run against `admin_menu`, `admin_role_*`, `admin_operation_log`, `users`, `vsla_loans` and other tables.
- `OperationsDashboardController::computeMetrics()` was run by reflection with the query log on, against `fao_prod_copy`.

Severity scale: **Critical** means exploitable now, or leaks credentials. **High** means a broken core function, a cross-tenant leak, or data loss. **Medium** means wrong data shown, slow, or a latent risk. **Low** means polish.

---

## Summary table

| ID | Sev | Area | Title |
|----|-----|------|-------|
| ADM-01 | Critical | Security | Every role, including *Farmer Member* (5,193 users), holds the `*` permission, so mobile-app users can log into /admin |
| ADM-02 | Critical | Security | Plaintext passwords are stored in `admin_operation_log` (352 rows) and shown at `/admin/auth/logs` |
| ADM-03 | Critical | Security | IP managers can grant the **Super Admin** role because code filters a slug (`super_admin`) that does not exist |
| ADM-04 | Critical | Security | Default passwords are phone digits or `4321`, sent by SMS and written to logs. Combined with ADM-01, anyone can log in |
| ADM-05 | High | Security | IDOR: edit, update and delete (and many show pages) have no IP-ownership check |
| ADM-06 | High | Security | `system-health-check/batch-assign-ip` has no scope. Any role can move any user to any IP |
| ADM-07 | High | Security | AESA observations and crop observations grids are not IP-scoped |
| ADM-08 | High | Security | The Loan Transactions "Loan" filter lists every loan system-wide (borrower and amount) to IP users |
| ADM-09 | High | Broken | VSLA Loans and Loan Transactions are scoped through `meeting`, but 99% of loans have no meeting. IP users see 15 of 1,437 loans |
| ADM-10 | High | Broken | Creating a group from "Group Associations" fails (`type='Other'` is not in the enum, and strict mode is on) |
| ADM-11 | High | Data | The ledger (`account_transactions`) can be created by hand without double entry and deleted row by row |
| ADM-12 | High | Data | Users are hard-deleted from the "Admin Users" grid and health check. `users` has no soft delete, so financial records are left orphaned |
| ADM-13 | Medium | Broken | Menu "Reports Export Centre" returns 404 (route is `xexport-centre`) |
| ADM-14 | Medium | Broken | `deliveries` and `product-orders` routes point at tables that do not exist (500) |
| ADM-15 | Medium | Broken | VSLA Loans "Principal" column is always UGX 0, and sorting by it throws a SQL error |
| ADM-16 | Medium | Broken | The super-admin "Implementing Partner" filter on Account Transactions and VSLA Loans throws a SQL error |
| ADM-17 | Medium | Broken | Sorting Loan Transactions by "Borrower" throws a SQL error |
| ADM-18 | Medium | Broken | The IP "KPI Targets" tab is never shown, even to super admins (`isRole('super_admin')`) |
| ADM-19 | Medium | Broken | Dashboard "Quick period" selector has no effect after the first load |
| ADM-20 | Medium | Broken | The IP-denied branch of `detail()` prints raw "HTTP/1.0 302 Found…" text instead of redirecting |
| ADM-21 | Medium | Broken | Navbar "CREATE NEW → Invoice" goes to a route that does not exist. HELP links are empty leftovers from another project |
| ADM-22 | Medium | Security | `IpScopeable` fails open: a non-super-admin with no `ip_id` sees all IPs' data |
| ADM-23 | Medium | Security | State-changing actions run on GET (activate, deactivate, reset password, approve, confirm, reprocess), so they are open to CSRF |
| ADM-24 | Medium | Security | Deactivated accounts (`status=0`) can still log into the portal |
| ADM-25 | Medium | Security | Legacy and unrelated modules (movies, insurance, withdraw, medical, e-commerce) are still routed and open to every role |
| ADM-26 | Medium | Perf | Members grid makes 3 extra queries per row, and export runs them for all 8.6k users |
| ADM-27 | Medium | Perf | Loan Transactions grid makes about 8 queries per row. Cycles grid makes 3 per row |
| ADM-28 | Medium | Perf | Operations dashboard runs 77 uncached queries per load (9 per IP in a loop) |
| ADM-29 | Medium | Perf | Selects load whole tables (all users or loans) instead of using ajax |
| ADM-30 | Medium | Data | `users.status` mixes `'1'` and `'Active'`. 6,688 active users show as "Inactive" in three grids and are missed by the status filter |
| ADM-31 | Medium | Data | 750 members store their member code in `phone_number`. Phone numbers have no format rule or normalisation |
| ADM-32 | Medium | Data | Member form saves `district_name` (free list) but grids and filters use `district_id`. 1,235 members have no `district_id` |
| ADM-33 | Medium | Data | `ffs_groups.total_members` is stale for 303 of 322 groups, and the dashboard leaderboard and recent-groups table display it |
| ADM-34 | Medium | Data | Savings totals differ by screen (project_shares 272.1M vs account_transactions 270.3M) |
| ADM-35 | Medium | Data | Group, IP, KPI-benchmark and system-config forms have almost no validation |
| ADM-36 | Medium | UI | CSV export writes HTML markup into cells (display callbacks are exported) |
| ADM-37 | Medium | UI | Menu structure: duplicate or overlapping items, raw slugs as labels, a hard-coded localhost link, missing modules |
| ADM-38 | Medium | UI | Dashboard design: no FFS training, AESA or gender data, no drill-down, opaque scoring (details in the Dashboard section) |
| ADM-39 | Low | Perf/UI | Bootstrap 4.6 CSS is loaded on top of AdminLTE (Bootstrap 3). `styles.css` uses a root-relative path |
| ADM-40 | Low | Broken | Orphan-member count on the dashboard is always 0 for IP-scoped users (contradictory query) |
| ADM-41 | Low | Code health | 13 backup or dead controllers declare live class names. HomeController has about 950 lines of unreachable code |
| ADM-42 | Low | UI | Inconsistent labels ("SACCO", "FFS Group" used for all group types). Members grid includes staff accounts |
| ADM-43 | Low | Data | Duplicate IP record (KADP id 1 and "Developent" id 6). Wrong "Subcounty" filter source |
| ADM-44 | Low | Perf | Operation log records every GET (12.3k rows) and is never pruned |

---

## 1. Broken

### ADM-09 — High — VSLA Loans and Loan Transactions are IP-scoped through `meeting`, which almost never exists
- **Where:** `app/Admin/Controllers/VslaLoanController.php:36-43` (`whereHas('meeting', ip_id)`); the detail guard at `:203-211` (`$record->meeting->ip_id`); `LoanTransactionController.php:35-40` (`whereHas('loan.meeting', …)`).
- **Evidence:**
  - `vsla_loans` has 1,437 rows; 1,422 have `meeting_id IS NULL`. They come from the opening-balance import.
  - 2,010 of 2,043 `loan_transactions` belong to such loans.
- **Impact:**
  - Every IP Manager or Facilitator sees only about 1% of their partner's loans, and opening a loan's detail page is denied.
  - The dashboard (which goes through `cycle → group`) shows UGX 140M outstanding, but the Loans grid shows almost nothing, so users lose trust in the numbers.
- **Fix:** Scope through the cycle's group: `whereHas('cycle.group', fn($q) => $q->where('ip_id', $ipId))`. Use the same relation in `detail()` (`$record->cycle?->group?->ip_id`). Do the same for LoanTransaction (`loan.cycle.group`).

### ADM-10 — High — Creating a group under "Group Associations" fails
- **Where:** `FfsGroupController.php:537` sets `$groupType = 'Other'`. The grid (`:74`) and `FfsGroup::TYPE_ASSOCIATION` use `'Association'`.
- **Evidence:**
  - `ffs_groups.type` is `enum('FFS','FBS','VSLA','Association')`.
  - `config/database.php:59` sets `'strict' => true`, and the server's sql_mode includes STRICT_TRANS_TABLES.
- **Impact:** POST `/admin/ffs-group-associations` fails with "Data truncated for column 'type'", so associations cannot be created from their own screen.
- **Fix:** Change `'Other'` to `FfsGroup::TYPE_ASSOCIATION`. Better, derive the type in one helper that the grid, form and title all share.

### ADM-13 — Medium — Menu "Reports Export Centre" returns 404
- **Where:** `admin_menu.id=83` has `uri='export-centre'`. `app/Admin/routes.php:332` registers `xexport-centre`.
- **Impact:** The only entry point to the export hub (meeting PDFs, cycle reports, AESA and training CSVs) is broken.
- **Fix:** Rename the route to `export-centre` (it comes after the specific `export-centre/*` routes, so there is no conflict). Or update the menu URI.

### ADM-14 — Medium — Routes point at tables that do not exist
- **Where:**
  - `routes.php:25,31` `deliveries` → `DeliveryController` (model `Delivery`, table `deliveries` is missing).
  - `routes.php:34` `product-orders` → `ProductOrderController` (table `product_orders` is missing).
  - Also duplicate registrations: `deliveries` (:25 and :31), `product-categories` (:26 and :32), `products` (:33 and :86).
- **Impact:** Opening these URLs throws a 500 "Base table or view not found".
- **Fix:** Remove the routes and controllers (legacy e-commerce), and remove the duplicate lines.

### ADM-15 — Medium — VSLA Loans "Principal" column always shows UGX 0, and sorting by it crashes
- **Where:**
  - `VslaLoanController.php:112-117`: `column('principal_amount')->sortable()`.
  - Show `:229`, form `:285`.
  - Show also lists non-existent `local_id` (:216) and `guarantor_1_id`/`guarantor_2_id` (:258-259).
- **Evidence:** `vsla_loans` has `loan_amount` and no `principal_amount`, `local_id` or guarantor columns.
- **Impact:** All 1,437 loans show Principal = "UGX 0". Clicking sort gives SQL error 1054. The detail page shows empty fields.
- **Fix:** Use `loan_amount`, and remove the fields that do not exist.

### ADM-16 — Medium — Super-admin "Implementing Partner" filter throws a SQL error
- **Where:** `AccountTransactionController.php:82-85` and `VslaLoanController.php:56-59`: `$filter->equal('ip_id', …)`.
- **Evidence:** Neither `account_transactions` nor `vsla_loans` has an `ip_id` column.
- **Impact:** Using the filter throws "Unknown column 'ip_id'".
- **Fix:** Use `$filter->where(function($q){ $q->whereHas('group'|'cycle.group', fn($g)=>$g->where('ip_id',$this->input)); }, 'Implementing Partner')->select(...)`.

### ADM-17 — Medium — Loan Transactions "Borrower" column is sortable but virtual
- **Where:** `LoanTransactionController.php:72-78`: `column('borrower')->…->sortable()`.
- **Impact:** Sorting adds `ORDER BY borrower` and throws a SQL error.
- **Fix:** Remove `->sortable()`, or join `vsla_loans`/`users` and sort on `users.name`.

### ADM-18 — Medium — IP KPI Targets can never be edited from the UI
- **Where:** `ImplementingPartnerController.php:191`: `Admin::user()->isRole('super_admin')`.
- **Evidence:** `admin_roles` slugs are `administrator, ip_manager, field_facilitator, vsla_treasurer, farmer_member, me_officer, content_manager`. There is no `super_admin`.
- **Impact:** The "KPI Targets" tab (`kpi_target_groups`, etc.) is hidden from everyone, yet the dashboard IP comparison relies on `kpi_target_groups` for its progress bars.
- **Fix:** Use the trait's `isSuperAdmin()`. Then grep and replace every `'super_admin'` slug check (see ADM-03).

### ADM-19 — Medium — Dashboard "Quick period" selector stops working after the first load
- **Where:** `OperationsDashboardController.php:63-67` and `:525-541`.
- **Evidence:**
  - The form always posts `date_from`/`date_to`, pre-filled from the current range.
  - `$dateFrom = request('date_from') ? parse(date_from) : now()->subDays(period)`.
  - So when the period select changes, `period=7&date_from=<30 days ago>` is submitted and the explicit date wins.
- **Impact:** Picking "Last 7 days" still shows 30 days, so the numbers look wrong.
- **Fix:** On period change, clear the date inputs with JS before submitting. Or apply dates only when `period=custom`. Or ignore `date_from`/`date_to` when they equal the previous default.

### ADM-20 — Medium — The IP-denied branch of `detail()` prints a raw HTTP response
- **Where:** `IpScopeable::denyIpAccess()` (`app/Admin/Traits/IpScopeable.php:236-240`) returns a `RedirectResponse` from `detail()`. That happens in `MemberController:286`, `FfsGroupController:332`, `CycleController:191`, `VslaLoanController:209`, and others.
- **Evidence:** `AdminController::show()` passes the return value to `$content->body()`. `Layout/Column::build()` echoes `(string) $content`, so the user sees "HTTP/1.0 302 Found Cache-Control: no-cache…".
- **Impact:** A confusing page instead of a redirect. (No data leaks.)
- **Fix:** In `detail()`, `abort(403)`. Or override `show()`/`edit()` and return the redirect before building Content.

### ADM-21 — Medium — Navbar shortcuts are broken or left over from other projects
- **Where:** `app/Admin/bootstrap.php:60-76`.
- **Evidence:**
  - "CREATE NEW → Invoice" goes to `invoices/create`, which has no route.
  - "News product" goes to `products/create` (legacy).
  - "HELP → How to register a new candidate / change candidate's status" have empty URLs.
  - `resources/views/admin/search-bar.blade.php` is a search input with no action or JS, so it does nothing.
- **Fix:** Replace with FAO shortcuts (New Member, New VSLA, Schedule Session, Export Centre) and a Help link to the user-guide videos. Remove the dead search box, or make it a global member/group search.

### ADM-40 — Low — Dashboard orphan-member count is always 0 for IP users
- **Where:** `OperationsDashboardController.php:276-279`: `->when($ipId, whereHas('group', ip))->whereDoesntHave('group')`.
- **Impact:** The two conditions cancel each other, so IP managers always see 0. The super-admin view shows 7.
- **Fix:** For IP scope, filter on `users.ip_id = $ipId` together with `whereDoesntHave('group')`.

---

## 2. Performance

### ADM-26 — Medium — Members grid is N+1, and its export multiplies the cost
- **Where:** `MemberController.php:209-223` (`ImplementingPartner::find()` up to twice per row), `:247-257` and `:259-273` (one `DB::selectOne` aggregate over `account_transactions` per row for savings, another for loan balance).
- **Evidence:** About 3 extra queries per row, so 60 per default page and 300 at 100 rows per page. Export is enabled: it chunks all 8.6k users through the same callbacks, about 26k queries in one request. "Savings Balance" is `sortable()` but sorts on `users.balance`, not on the value shown.
- **Fix:**
  - Eager-load `implementingPartner` and `group.implementingPartner`.
  - Compute both balances with `selectSub(...)` (as `FfsGroupController:78-100` already does), which also makes sorting correct.
  - Disable export or add a custom exporter.

### ADM-27 — Medium — Loan Transactions and Cycles grids query per row
- **Where:**
  - `LoanTransactionController.php:31-57`: three separate `VslaLoan::with(...)->find($this->loan_id)` calls per row. `:93` `User::find()` per row.
  - `CycleController.php:123-131` (`FfsGroup::find` per row) and `:170-176` (two count queries per row).
- **Evidence:** About 8 queries per row for loan transactions (2,043 rows). The table has `disableExport` absent, so export is on.
- **Fix:** `$grid->model()->with(['loan.borrower','loan.cycle.group','creator'])`, then read `$this->loan`. For cycles: `->with('group')->withCount(['meetings', 'loans as active_loans_count' => fn($q)=>$q->where('status','active')])`.

### ADM-28 — Medium — Operations dashboard: 77 uncached queries per load
- **Where:**
  - `OperationsDashboardController::computeMetrics()` `:144-501`.
  - The IP loop `:432-475` runs 9 aggregate queries per IP, most with nested `whereHas` (correlated EXISTS).
  - The leaderboard `:340-424` uses four correlated subqueries per facilitator.
- **Evidence:**
  - Measured: 77 queries, 0.25s DB time for super admin; 29 for an IP user. That is at today's size (1.4k loans, 8.6k users, 18k ledger rows).
  - It grows linearly with IPs and data, and runs on every login because `/admin` redirects here (`HomeController.php:32`).
  - There is no `Cache::` anywhere in the dashboard. Only `SystemHealthCheckController` uses Cache.
- **Fix:**
  - Wrap `computeMetrics()` in `Cache::remember("ops:{$ipId}:{$from}:{$to}", 300, …)` and show a "Last updated hh:mm · Refresh" link.
  - Replace the per-IP loop with grouped queries, e.g. `FfsGroup::selectRaw('ip_id, count(*), sum(created_at>=?)…')->groupBy('ip_id')`, and the same for joins `vsla_loans→projects→ffs_groups`.
  - Replace the `whereHas` chains with joins.

### ADM-29 — Medium — Dropdowns load whole tables
- **Where:**
  - `FfsGroupController.php:617-630`: Facilitator select = every user in the IP, all 8.6k for super admin, farmers included.
  - `AccountTransactionController.php:428-431`: user select = all users.
  - `LoanTransactionController.php:139-148`: every loan with its borrower.
  - `MemberController.php:81-93`: all groups with `withCount('members')`.
- **Impact:** Slow form loads, unusable 8k-option lists, and cross-IP exposure (ADM-08).
- **Fix:** Use `->ajax(admin_url('api/users?role=field_facilitator'))` endpoints with IP scoping, and restrict facilitator lists to the `field_facilitator` role (as the FfsGroup filter at `:156-171` already does).

### ADM-44 — Low — Operation log grows without limit and stores everything
- **Where:** `config/admin.php:210-228`: `allowed_methods` includes GET/HEAD and `except` only covers `auth/logs`.
- **Evidence:** `admin_operation_log` has 12,325 rows, mostly page views.
- **Fix:** Log only POST/PUT/PATCH/DELETE. Prune rows older than 90 days. Redact secrets (see ADM-02).

---

## 3. Security / multi-tenancy

### ADM-01 — Critical — Every role, including Farmer Member, has the `*` permission
- **Evidence (DB):**
  - `admin_role_permissions` maps roles 1–6 → permission 1 (`slug='*'`, `http_path='*'`). That includes `vsla_treasurer` (60 users) and `farmer_member` (5,193 users).
  - All 5,193 farmer-role users have a password, and 4,894 have a username.
  - `config/admin.php:235` sets `check_route_permission => true`, but with `*` it allows everything.
  - Menu restriction (`admin_role_menu`) only hides AESA, Auth Management, IP Managers, Marketplace and Export Centre. Everything else is visible to every role.
  - Code comments say `farmer_member` users are "app-only" (`AdminUserController.php:227`).
- **Impact:** Any farmer who logs into `/admin` with their mobile credentials can:
  - browse every member's personal data in their IP;
  - open and change the ledger, loans and cycles;
  - use the System Health Check batch delete and merge;
  - open the built-in `/admin/auth/users`, `/auth/roles`, `/auth/permissions`, `/auth/menu` (and change role permissions);
  - read `/admin/auth/logs` (ADM-02).
- **Fix:**
  1. Delete the `admin_role_permissions` rows for roles 4 and 5, and create scoped permissions for roles 2, 3 and 6 (e.g. deny `auth/*`, `system-health-check*`, `implementing-partners*`).
  2. Add a login gate: override `AuthController::postLogin` (or add middleware on the `admin` group) that rejects users whose only roles are `farmer_member`/`vsla_treasurer`, and rejects `status` not in (`1`,`Active`).
  3. Restrict `auth/*` routes to `administrator`.

### ADM-02 — Critical — Plaintext passwords in the operation log, shown at /admin/auth/logs
- **Where:** `config/admin.php:210-228`. The vendor `LogOperation.php:28` stores `json_encode($request->input())`.
- **Evidence:**
  - 352 `admin_operation_log` rows contain a non-empty `"password":"…"`, e.g. POST `admin/ffs-members` (150), POST `admin/facilitators` (12), PUT `admin/facilitators/731`, POST `admin/ip-users`. Values were redacted during the audit.
  - `MemberController.php:345-350` also writes `request()->all()`, password included, to `laravel.log` on every member update.
  - `MemberController.php:387-392` logs the full credentials SMS text.
- **Impact:** Anyone who can open `auth/logs` (every role, see ADM-01) or the log files can read facilitator and member passwords.
- **Fix:**
  - Extend `LogOperation` (bind your own middleware as `admin.log`) to strip `password`, `password_confirmation` and `_token`.
  - Purge the existing rows with `UPDATE … SET input = JSON_REMOVE(...)`, or delete them.
  - Remove the `Log::info` of request input and SMS bodies.
  - Force a password reset for the affected accounts.

### ADM-03 — Critical — IP managers can assign the Super Admin role
- **Where:** `AdminUserController.php:550-553`, `IPUserController.php:219-222`, `UserController.php:280-283`, `MemberController.php:622-625`. Each runs `$rolesQuery->where('slug', '!=', 'super_admin')` for non-super-admins. The same wrong slug appears at `AdminUserController.php:710,799,865,896` (the "cannot modify a Super Admin" guards) and `OperationsDashboardController.php:57`.
- **Evidence:** The real Super Admin slug is `administrator` (role id 1), so the filter removes nothing. The role id is not validated server-side either.
- **Impact:** An IP Manager (or any role, see ADM-01) can create or edit a user, tick "Super Admin", and get unscoped access to every partner. The guards against changing super-admin accounts are also ineffective.
- **Fix:**
  - Filter `whereNotIn('slug', ['administrator','super_admin'])`, or better, filter by `id != 1`.
  - Add a `saving` hook that rejects role id 1 unless `isSuperAdmin()`.
  - Centralise this in one `IpScopeable::assignableRoles()` helper.

### ADM-04 — Critical — Predictable default credentials
- **Where:**
  - `MemberController.php:654-658`, `IPUserController.php:265-270`, `UserController.php:334-336`, `FacilitatorController.php:409-413`: password = phone digits, or `'123456'`.
  - `VslaProfileController.php:537`: chairperson password `bcrypt('4321')`.
  - `AdminUserController.php:902-908` and `IPManagerController.php:500-506`: "reset password" sets phone digits and shows them in a toastr.
  - `MemberController::sendCredentials` (:373-394) texts "Password: <phone digits>", even if the user changed it.
- **Impact:** Combined with ADM-01, anyone who knows a member's or facilitator's phone number can log into the portal. All VSLA chairpersons created through profiling share the password `4321`.
- **Fix:**
  - Generate random 8-character passwords (`Str::password(10)`) and send them once.
  - Set a `must_change_password` flag and enforce it on first login (mobile and web).
  - Never log or display the plaintext beyond one-time delivery.

### ADM-05 — High — IDOR: no IP-ownership check on edit, update and delete (and several show pages)
- **Evidence:**
  - laravel-admin's `edit/update/destroy` call `$this->form()->edit|update|destroy($id)`, which runs a plain `findOrFail` (no global scopes exist in `app/Models`).
  - None of the scoped controllers check ownership in `form()` or override `edit/update/destroy`. Only `MemberController::update` (:345) overrides, and it has no check.
  - `detail()` has no guard in: `FarmController:103`, `AesaSessionController:159`, `VslaOpeningBalanceController:576`, `FfsKpiIpController:216`, `FfsKpiFacilitatorEntryController`, `VslaProfileController:89`, `VslaActionPlanController`, `VslaMeetingAttendanceController`, `LoanTransactionController`, `AdvisoryPostController`, `FarmerQuestion(Answer)Controller`, `ProjectShareController`, `ProjectController`.
  - Example: an IP Manager of ECO opens `/admin/ffs-vslas/{GARD group id}/edit`, sees the form, and saves it. `addIpFieldToForm` (`IpScopeable.php:176-180`) then **forces `ip_id` to ECO**, which takes the group from GARD. `DELETE /admin/ffs-vslas/{id}` works for any id.
  - `ImplementingPartnerController` has no scoping at all, so IP managers can edit other partners.
- **Fix:**
  - Add to the trait:
    ```php
    protected function guardRecord(Model $m){ if(!$this->verifyIpAccess($m)) abort(403); }
    ```
  - Call it from overridden `show/edit/update/destroy`, or from `$form->editing()`/`$form->deleting()` callbacks (`$form->model()`).
  - For group-owned records, use `verifyIpAccessViaGroup`.
  - Restrict `implementing-partners` to super admin.

### ADM-06 — High — Health-check batch actions are not scoped or role-gated
- **Where:**
  - `SystemHealthCheckController.php:442-470` (`batchAssignIp`): `User::whereIn('id',$ids)->update(['ip_id'=>…])` with no `hasIpScope()` check.
  - `:401-436` (`batchAssignFacilitator`) accepts any facilitator id and copies that facilitator's `ip_id` onto the groups.
  - No action checks for super admin. Routes `routes.php:102-119`.
- **Impact:** Any logged-in role can move any user, or move groups, into any IP (cross-tenant data move), or batch-delete or merge users.
- **Fix:** Gate the whole controller with `if(!$this->isSuperAdmin()) abort(403)` (or allow IP managers read-only). Apply `hasIpScope()` to `batchAssignIp`, and validate that the facilitator belongs to the same IP.

### ADM-07 — High — AESA observations are not IP-scoped
- **Where:** `AesaObservationController.php:29-32` and `AesaCropObservationController.php:26-30`. Both `use IpScopeable` but never call `applyIpScope`. Both tables have an `ip_id` column. `AesaSessionController::detail` (:159) has no guard.
- **Impact:** Every IP's field observations are visible to every partner and facilitator.
- **Fix:** `$this->applyIpScope($grid);` plus a detail guard.

### ADM-08 — High — Loan filter leaks every borrower across IPs
- **Where:** `LoanTransactionController.php:139-148`: `VslaLoan::with('borrower')->get()` builds the "Loan" filter options with no IP filter.
- **Impact:** An IP user can see the name and loan amount of all 1,437 borrowers in the system.
- **Fix:** Scope the options (`whereHas('cycle.group', ip)`), or use an ajax select.

### ADM-22 — Medium — `IpScopeable` fails open
- **Where:** `IpScopeable.php:28-36` returns `null` when the user is not a super admin but has `ip_id = NULL`. `applyIpScope()` (:112-118) then applies **no filter**.
- **Evidence:** No roled non-admin user has a null `ip_id` today (checked). The operations dashboard already guards this case with `-1` (`OperationsDashboardController.php:150`), but the trait does not.
- **Fix:** In `applyIpScope`/`scopeQuery`, if `!isSuperAdmin() && !$user->ip_id`, add `whereRaw('1=0')`. Also cache `isSuperAdmin()` per request: it runs up to 3 role queries per call and is called up to 18 times per request.

### ADM-23 — Medium — State changes over GET (CSRF)
- **Where:** `routes.php:49-50` (cycles activate/deactivate), `:63-65` (withdraw approve/reject), `:93` (membership confirm), `:128-131` (admin-users activate/deactivate/reset-password/send-credentials), `:139,145-146,152,168-169` (send-credentials, reset), `:187-188` (answers approve/accept), `:216` (opening-balance reprocess).
- **Impact:** An `<img src=".../admin/admin-users/5/reset-password">` on any page an admin visits resets that password to phone digits.
- **Fix:** Change these to POST/PUT with a CSRF token (laravel-admin `RowAction` or a form with `@csrf`), plus a confirm dialog.

### ADM-24 — Medium — Deactivated accounts can still log in
- **Where:** `AdminUserController::setStatus` (:854-879) only updates `users.status`. `AuthController` (`app/Admin/Controllers/AuthController.php`) is empty and inherits vendor login, which ignores status.
- **Fix:** Override `postLogin` / `credentials()` to require `status IN ('1','Active')`. Also invalidate existing sessions.

### ADM-25 — Medium — Legacy and unrelated modules are still routed
- **Where:** `routes.php:25-34, 55-59, 61-99, 265-277`.
- **Detail:** Products, deliveries, orders, ordered-items, withdraw-requests, insurance (4 resources), medical-service-requests, membership-payments, pesapal-payments, disbursements, financial-accounts, movies and series-movies (8 resources), and `projects` (investment).
- **Evidence:** Tables are empty (withdraw, movies, series_movies, medical, membership_payments = 0) or legacy. All are reachable by every role and enlarge the attack surface. The menu still shows "Marketplace".
- **Fix:** Remove these routes, controllers and menu items, or put them behind `administrator` only.

---

## 4. Data entry quality

### ADM-11 — High — The ledger can be edited by hand
- **Where:** `AccountTransactionController.php:424-460` and grid actions `:331-334`.
- **Evidence:**
  - The create button is enabled. The form sets only `user_id, amount, source, description, transaction_date`; there is no `owner_type`, `group_id`, `account_type` or `cycle_id`, and no contra entry.
  - Row delete and batch delete stay enabled; only edit and view are disabled.
  - The ledger is double-entry: every `share/loan/loan_repayment/social_fund/fine` row exists once for `member` and once for `group`.
- **Impact:** Manual rows are invisible to IP-scoped users (no `group_id`) and break per-member and per-group balances. Deleting one side of a pair unbalances the books.
- **Fix:** `disableCreateButton()`, `disableBatchActions()`, `$actions->disableDelete()`. Do corrections through a dedicated "adjustment" action that writes both legs in a DB transaction.

### ADM-12 — High — Users are hard-deleted and their financial records orphaned
- **Where:**
  - `UserController` grid (the "Admin Users" menu → `/admin/users`, all 8.6k users) has no `actions`/`disableBatchActions` restriction, so row delete and batch delete are on.
  - `SystemHealthCheckController::batchDeleteUsers` (:306), `deleteAllOrphanedMembers` (:360) and `mergeDuplicateUsers` (:536) call `->delete()`.
- **Evidence:** The `users` table has no `deleted_at` column, so these are hard deletes. `mergeDuplicateUsers` deletes the duplicates without moving their `account_transactions`, `project_shares`, `vsla_loans` or `ffs_session_participants`.
- **Impact:** Permanent loss of members, and their savings and loan history is orphaned.
- **Fix:**
  - Add `deleted_at` to `users` and use SoftDeletes.
  - Disable delete in `UserController` and `MemberController`.
  - In merge, reassign the foreign keys (`user_id`, `borrower_id`, `investor_id`, …) to `keep_id` inside the transaction before deleting.

### ADM-30 — Medium — `users.status` has mixed values
- **Evidence:** `users.status` holds `'Active'` 6,688, `'1'` 1,983, `'0'` 10, NULL 31.
  - `UserController.php:138-142`, `IPUserController.php:116-120` and `IPManagerController.php:110-114` test `$v == 1`. In PHP 8, `'Active' == 1` is false, so those users show "Inactive".
  - The `UserController` status filter (`:56-59`) offers only `'1'`/`'0'`.
  - The member form `status` select (`MemberController.php:557-559`) has options `'1'`/`'0'`, so editing an `'Active'` member shows a blank status.
  - `VslaProfileController.php:530` writes `'Active'`.
- **Fix:** Normalise with a migration to `'1'`/`'0'` (or `Active`/`Inactive`), add a model cast or constant, and use one display helper (`AdminUserController.php:320-325` already handles both values).

### ADM-31 — Medium — Phone numbers are not validated
- **Evidence:**
  - 750 users have `phone_number = member_code` (e.g. `NAB-MEM-26-0434`) used as a placeholder.
  - Uniqueness is checked on the raw string (`MemberController.php:510-512`), so `0771234567` and `+256771234567` both pass. There are 2 duplicate phones today.
  - There is no format regex on the member, facilitator, IP-user or group contact phones.
  - The grid "Contact" column shows the member code with a phone icon, and send-credentials or SMS goes to an invalid number.
- **Fix:** Normalise to E.164 (`+2567XXXXXXXX`) in a mutator. Add a `regex:/^\+2567\d{8}$/` rule. Store "no phone" as NULL, not the member code. Make uniqueness check the normalised value.

### ADM-32 — Medium — Member district captured in the wrong field
- **Where:**
  - `MemberController.php:571-572` saves `district_name` from `northernUgandaDistricts()`, a hard-coded list of 35 districts across Acholi, Lango and West Nile.
  - The grid filter (`:109-112`) and the rest of the system use `district_id`, linked to `locations`.
- **Evidence:** 1,235 members have no `district_id` (1,024 of them have `district_name`).
- **Fix:** Use `select('district_id')->options(Location::where('type','District')…)` (Karamoja only), followed by a cascading subcounty select. Backfill `district_id` from `district_name`.

### ADM-33 — Medium — Stale `ffs_groups.total_members`
- **Evidence:** `total_members` differs from the actual member count in 303 of 322 live groups. The column sums to 4,190; real members number 8,622.
  - The group form asks for male, female and total counts by hand (`FfsGroupController.php:596-607`).
  - The dashboard leaderboard `SUM(g.total_members)` (`OperationsDashboardController.php:357`) and the "Recent Group Registrations" Members column (`:485`) show this stale field. The KPI strip and groups grid use the real count (`withCount('members')`).
- **Fix:** Drop the manual inputs, or make them read-only. Show `withCount` / `COUNT(users)` everywhere. Recompute `total_members`, `male_members` and `female_members` from `users` in an observer.

### ADM-34 — Medium — Two definitions of "savings"
- **Evidence:** Dashboard and groups grid use `SUM(project_shares.total_amount_paid)` = UGX 272,113,020. The member grid uses `SUM(account_transactions WHERE account_type='share' AND owner_type='member')` = UGX 270,307,020, a gap of 1.8M.
- **Fix:** Pick one source of truth (the ledger), expose it through a model scope or service, and use it in every screen. Reconcile the 1.8M difference.

### ADM-35 — Medium — Core forms have almost no validation
- **Evidence (rule count vs field count):**
  - `FfsGroupController` form: 26 fields, 1 rule. Only `name` is required. `district_id`, `facilitator_id`, `establishment_date` and type are not; latitude and longitude have no range; there is no duplicate-name check, even though the health check later reports "similar names".
  - `ImplementingPartnerController`: 21 fields, 1 rule (no email or phone format, no unique `short_name`, which already caused the duplicate KADP record).
  - `KpiFacilitatorController` form: 6 fields, 0 rules. `SystemConfigurationController`: 10 fields, 0 rules.
  - Member form: `group_id` is optional (creates orphans), `dob` has no `before:today`, NIN has no format or uniqueness check (8 duplicate NINs exist), nothing stops two chairpersons in one group, and the status values are inconsistent.
  - Training session form: no check that `end_time` is after `start_time`, and no GPS range.
- **Fix:** Add `->rules()` for required, numeric, `between:-90,90` and similar ranges, `unique:` with soft-delete awareness, and `after:start_time`. Add a `saving` check for one chair, secretary and treasurer per group.

---

## 5. UI / UX

### ADM-36 — Medium — CSV export contains HTML
- **Evidence:** The vendor `Grid/Exporters/CsvExporter.php:164-182` runs `$column->fill()`, which applies `display()` callbacks. About 38 routed grids have export enabled with HTML display callbacks, e.g. Members (`<strong>Name</strong><br><span class="label…">Chairperson</span>`), Facilitators, Loan Transactions, IP Users, VSLA Opening Balances, Cycles. Only `FfsGroupController` checks `request()->has('_export_')`.
- **Fix:** Add a shared exporter that runs `strip_tags`, or define `$grid->export(fn($e)=>$e->originalValue([...])->column(...))` per grid. Disable export where it is not needed.

### ADM-37 — Medium — Menu structure (`admin_menu`)
- **Evidence:**
  - Two KPI menus: "KPIs" (id 57, order 20) and "FFS KPIs" (id 70, order 56).
  - "KPI Settings" (id 62) and the parent "KPIs" both point to `kpi-benchmarks`, an alias of `KpiFacilitatorController` ("Facilitator KPIs"). It duplicates "Facilitators KPIs" (id 59). The real settings controller, `KpiBenchmarkController`, is not routed.
  - Four overlapping user-management entries: "All Admin Users" (`admin-users`), "Admin Users" (`users`, which actually lists all 8.6k users including farmers), "User Management → All Users / Add New User" (`ip-users`), and "Admin User Management → Add Admin User".
  - Marketplace children are titled with raw slugs `product-categories` and `products`.
  - "Main Website" (id 69) points to `http://localhost:8888/fao-ffs-mis-api`, which breaks in production.
  - "Savings & Shares" points to `account-transactions`, the whole ledger including loans and fines.
  - Icons: `fa-user-md` (a doctor) for members, `fa-paw` for AESA (crops too), `fa-shield` used twice, `fa-users`/`fa-line-chart` repeated.
  - Missing from the menu although routed: VSLA Profiles, Farms, Farm Activities, AESA Crop Observations, Operations Dashboard (reachable only through the "/" redirect), and per-type group lists (FFS/FBS/VSLA/Associations).
  - "System health check" (lower-case, order 55) is visible to every role.
- **Suggested structure:**
  1. Dashboard
  2. Groups (All, FFS, FBS, VSLA, Associations, VSLA Profiles)
  3. Members
  4. VSLA Finance (Cycles, Opening Balances, Meetings, Attendance, Loans, Loan Repayments, Ledger, Action Plans)
  5. Training & Field (Sessions, Attendance, Resolutions, AESA)
  6. Farms & Enterprises
  7. Advisory
  8. Market Prices
  9. KPIs & Reports (KPI Dashboard, IP entries, Facilitator entries, Export Centre)
  10. Administration (IPs, Users & Roles, Facilitators, IP Managers, Health Check), bound to the administrator role in `admin_role_menu`.

### ADM-38 — Medium — Dashboard information design
See the Dashboard section below.

### ADM-39 — Low — Bootstrap 4 loaded on top of AdminLTE (Bootstrap 3)
- **Where:** `app/Admin/bootstrap.php:95` loads `public/assets/css/bootstrap.css`, which is Bootstrap **v4.6.1**. AdminLTE in laravel-admin 1.8 uses Bootstrap 3.
- **Impact:** Bootstrap 4 redefines `.row` (flex), `.col-*`, `.badge`, `.btn`, `.form-control` and removes `.label` styling, which causes layout and style drift. That likely explains the heavy inline styles throughout the controllers. `:96` uses the root-relative `/assets/css/styles.css`, which 404s when the app is served from a sub-folder (e.g. local `localhost:8888/fao-ffs-mis-api`). That file only defines misspelled `.bagde` classes.
- **Fix:** Remove the Bootstrap 4 include. Move the dashboard and grid inline styles into one `public/css/admin-custom.css` loaded with `Admin::css(asset(...))`.

### ADM-41 — Low — Backup and dead code
- **Where:** In `app/Admin/Controllers`:
  - `HomeController_NEW.php`, `HomeController_OLD.php` and `HomeController_OLD_BACKUP.php` (all `class HomeController`).
  - `FfsGroupController_OLD.php`, `MemberController_OLD.php`, `UserController_DTEHM_BACKUP.php`, `UserControllerOld.php`, `CourseControllerOld.php`, `OrderControllerOld.php` (each declares the live class name).
  - `*_REMOVED.php`, `*.bak`, `dummy.php`, `ExampleController.php`.
  - Recruitment-era controllers: Candidate, Enjaz, Interpol, Musaned, Embassy, Ministry, Training, Failed, and others.
  - `HomeController.php:32` redirects first, so lines 34-989 never run.
- **Risk:** `composer dump-autoload -o` may report "Ambiguous class resolution" and could map a live class to a backup file. (The classmap is correct today.)
- **Fix:** Delete them (git keeps the history), and reduce `HomeController::index` to the redirect.

### ADM-42 — Low — Labels and terminology
- **Evidence:**
  - "Group / SACCO" (`CycleController.php:95,123,204`) and "SACCO Role" (`MemberController.php:229`). The programme runs VSLAs, not SACCOs.
  - The member and user filter "FFS Group" covers every group type.
  - The Members grid repeats the role badge in both "Full Name" and "SACCO Role".
  - Members has no Member Code, Gender or Age columns and no Gender filter, although FAO reporting is sex-disaggregated.
  - "Group Members" (`MemberController`) lists **all** users, including admins, facilitators and IP managers, with no `group_id`/`user_type` filter.
- **Fix:** Rename to "VSLA / Group" and "Group Role". Add `member_code`, `sex` and age columns with filters. Default the members grid to `whereNotNull('group_id')`.

### ADM-43 — Low — Reference data issues
- **Evidence:**
  - `implementing_partners` has id 1 "Karamoja Agro-Pastoral Development Programme (KADP)" with 0 groups, and id 6 "Karamoja Agro-pastoral **Developent** Program (KADP)" with 65 groups.
  - The member "Subcounty" filter (`MemberController.php:115-119`) uses `Location::where('parent','>',0)`. That returns the 11 districts plus one parish and one subcounty; the `locations` table has only 16 rows.
- **Fix:** Merge the IP rows (move any references to id 6, fix the name) and add a unique index on `short_name`. Load the full subcounty and parish hierarchy (the 1,797-row `locations_backup_20260101_225251` looks like the original) and filter on `type='Sub-county'`.

---

## Dashboard (HomeController → OperationsDashboardController)

**Current behaviour.** `/admin` (`HomeController@index`) immediately redirects to `/admin/operations-dashboard`. All roles land there. The page renders, top to bottom:
1. **Filter bar:** IP select (super admin only), quick period (7–90 days; broken, see ADM-19), date from/to, Apply and Reset, and a red "User Guide Videos" YouTube link.
2. **KPI strip** (8 cards): Active IPs, Total Groups, Groups (period), Total Members, Facilitators, Active Cycles, Meetings (period), Total Savings.
3. **Momentum strip:** groups, meetings, savings and loans compared with the previous period (percentage change).
4. **Data Quality Monitor:** groups without facilitator, groups without IP, orphan members, facilitator/IP mismatches. Next to it, an **Insights** panel of 4 generated sentences.
5. **Facilitator leaderboard** (top 10 by an "activity score" = meetings×3 + transactions + groups this week×2 + groups this month). Next to it, a **daily group registrations** line chart.
6. **IP comparison table:** groups (week, month), members, facilitators, cycles, savings, loans, outstanding, and progress against `kpi_target_groups`. The target cannot be set; see ADM-18.
7. **Financial health:** 3 cards (savings, loans, social fund).
8. **Loan portfolio:** a doughnut by status and a status table.
9. **Recent 20 group registrations.**

**Problems found:**
- **Speed:** 77 uncached queries per load (ADM-28), on every login.
- **Correctness:**
  - Quick period does not work (ADM-19).
  - Orphan count is always 0 for IP users (ADM-40).
  - Leaderboard members and recent-groups Members use the stale `total_members` (ADM-33).
  - Savings uses a different source from the member screens (ADM-34).
  - PAR shows **100%** because all 1,036 active loans are past `due_date` (3-month loans disbursed April–July 2026). The page gives no context, and repayments are not reflected in `vsla_loans.status`.
- **Coverage:**
  - It is entirely VSLA and group centric. There is nothing on **FFS training sessions, attendance or GAP resolutions, AESA, farms, or FFS KPI achievement against targets**, which are core FOSTER outputs.
  - There is no **sex, youth or PWD disaggregation** (no `Female`, `sex` or `youth` anywhere in the controller).
  - Facilitators get the same IP-wide page instead of "my groups / my sessions this week".
- **Design:**
  - Eight cards of equal weight, all-caps micro labels (10–11px), and every colour inlined in PHP strings.
  - No drill-down: no card, data-quality row or table row links anywhere (the only `href`s are Reset and YouTube).
  - The activity score is opaque. There is no "last updated" timestamp.
  - The YouTube link is styled as a primary action inside the filter bar.

**Recommended redesign (clearer and faster):**
1. **Cache:** wrap metrics in a 5–10 minute `Cache::remember` keyed by IP and period. Replace the per-IP loops with grouped SQL. Load secondary sections (leaderboard, IP table, loan portfolio) lazily through ajax endpoints so the first paint shows only the KPI row.
2. **Role-specific landing:**
   - Super admin / M&E: programme view.
   - IP manager: their IP against targets.
   - Facilitator: "My groups, sessions due this week, meetings not submitted, loans overdue".
3. **Top row, at most 5 headline KPIs aligned with the FOSTER logframe:** groups (FFS / VSLA split), members (with % female and % youth), training sessions this period (with average attendance), VSLA savings (UGX, one source), and portfolio at risk. Each shows a delta and **links to the filtered grid**.
4. **Second row: "Needs attention" list:** overdue loans by group, groups with no meeting in 30 days, sessions without reports, data-quality issues. Each item links to the Health Check or a filtered grid.
5. **Third row:** IP-versus-target table, with targets editable after the ADM-18 fix and progress bars per KPI from `ffs_kpi_ip_entries`. Add a trend chart (groups, sessions and meetings per week, stacked).
6. Move the leaderboard to the KPI module and document the score formula in a tooltip. Move "User Guide Videos" to the navbar Help menu. Add "Data as of hh:mm".
7. Put styles in one CSS file with design tokens, instead of 1,000 lines of inline styles.

---

## Verified non-issues (checked, nothing found)
- Every routed controller class and method exists (486 actions checked by reflection). No `*_OLD`/`*_BACKUP` controller is routed.
- Local `fao_ffs_mis` and `fao_prod_copy` have identical schemas.
- `ExportCentreController` downloads are IP-scoped, and the PDF endpoints check ownership (`abort(403)`).
- `FfsGroupController` grid is well optimised (`selectSub` + `withCount` + eager load). The field-facilitator scoping there is correct.
- `addIpFieldToForm` forces `ip_id` on save for non-super-admins, so the read-only select cannot be tampered with in controllers that use it. The caveat is IDOR re-assignment (ADM-05).
- Laravel 8.83 logs PHP 8 deprecations (`number_format(null)`) instead of throwing them.
