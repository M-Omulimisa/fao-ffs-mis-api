# FAO FFS MIS – API Layer Audit

Date: 2026-10-31. Scope: `routes/api.php`, `app/Http/Controllers/Api/*`, the `app/Http/Controllers/*` controllers that `api.php` routes to, `EnsureTokenIsValid`, and `ApiResponser`. The audit was read-only and no project files were changed.

**How I verified**
- `artisan route:list` and programmatic route matching on MAMP PHP 8.3.9.
- Read-only HTTP-kernel requests against the local copy (GET only, or calls that fail before any write).
- `SELECT`/`DESCRIBE` queries against the local `fao_ffs_mis` MySQL 5.7.
- Greps of the Flutter app (`/Users/mac/Desktop/github/fao-ffs-mis-mobo/lib`) to check what each response shape is used for.

Schema facts come from the **local** DB. Confirm items that depend on a missing column (marked "schema-dependent") against production before acting.

**Shape-safety convention**
- "shape-safe: yes" means the fix does not rename or remove a response key, change a type, or change the structure the app parses.
- Removing `password` / `intro` from serialised User objects counts as shape-safe. The app reads `password` with an empty default (`LoggedInUserModel.dart:134`, `Utils.to_str(m['password'], '')`) and never needs `intro`.
- Do **not** hide `remember_token` globally. Login and registration return the JWT in it, and the app reads it.

The sections run as follows:
- **Section 1 (API-01 … API-40):** findings in the core scope: routing, auth, `ApiResurceController`, `ApiAuthController`, registration and profiling, and the VSLA meeting, shareout, loan and transaction controllers.
- **Section 2 (API-41 … API-71):** VSLA secondary controllers (facilitator, onboarding, configuration, manifest, opening balance, social fund, loan transactions).
- **Section 3 (API-72 … API-106):** FFS training, AESA, farms, advisory, members, groups, locations, KPI, OneSignal.
- **Section 4 (API-107 … API-138):** payments, ledger, investments, insurance, withdrawals and dashboard (the legacy routes with no auth).

Sections 2–4 were audited the same way, by reading each file and checking the DB and app; their IDs were renumbered into one sequence.

**Fix first:** API-01, 02, 03, 04, 05, 41, 06, 09, 107, 108, 109, 110, 72, 74, 07.

Most fixes are shape-safe route middleware, `$hidden` and authorization checks. Doing API-01 and API-03 first removes the leverage behind most of the others.

---

## Section 1 – Core: routing, auth, generic resource controller, VSLA financial controllers

### Critical

- **API-01 — critical — `app/Http/Middleware/EnsureTokenIsValid.php:27-54`**
  - **What:** Every route "protected" by `EnsureTokenIsValid` takes the caller's identity from a plain `User-Id` header (also `HTTP_USER_ID` and `user_id`). The header wins over the Bearer token. When there is no header, the JWT payload is base64-decoded and `sub` is trusted **without checking the signature or expiry**. The JWT config (`HS256`, TTL) is never enforced.
  - **Failure scenario:** Anyone who knows or guesses a numeric user id (ids are sequential, about 7.5k users) can call any authenticated endpoint as that user, including admins and chairpersons. Examples: submitting meetings, completing shareouts, resetting a group, setting member passwords. Every per-endpoint IDOR finding below becomes exploitable without any credentials.
  - **Fix:**
    - When a Bearer token is present, validate it (`JWTAuth::setToken($t)->getPayload()` or `auth('api')->setToken($t)->user()`) and take the user from the verified `sub`.
    - If `User-Id` is also present, require it to equal `sub`; otherwise reject with the same 401 JSON.
    - Phase out header-only auth: log header-only requests for one release, then reject them.
    - The app always sends both `Authorization: Bearer` and `User-Id` (`Utils.dart:193-201`, `:320-324`), so current builds keep working.
    - Risk: some app builds have very old or expired tokens, because login sets a 1-year TTL (`ApiAuthController.php:195`). Measure first.
  - **Shape-safe:** yes (the 401 body stays the same).

- **API-02 — critical — `routes/api.php:963-964` → `ApiResurceController::index` (`:2893-2959`) and `::update` (`:3017-3120`)**
  - **What:** `GET/POST /api/api/{model}` is **unauthenticated** and generic over any `App\Models\*` class.
    - `index` with `?is_not_private=1` drops the owner filter and runs `Model::where($q_*)->get()` with arbitrary `q_<column>` filters.
    - `update` (POST) creates or updates **any row of any model**: it takes the id from the URL, `online_id` or `id`, then assigns every `$_POST` key to the model. There is no ownership check. The "user" is just `Utils::get_user_id()` from the `User-Id` header or `?user_id`.
  - **Verified:** a local `GET /api/api/users?is_not_private=1&q_id=1` with no auth returned the full user row, including `password` (`$2y$10$…`), `remember_token`, `intro` and `national_id_number`. Without `q_id`, it dumps all 7,538 users.
  - **Failure scenario:**
    - Full credential and PII dump.
    - `POST /api/api/users` with `User-Id: 1`, `id=<victim>` and `password=x` writes a raw password, user type or group onto any account.
    - `POST /api/api/account-transactions` forges ledger rows.
  - **Fix:**
    - Put the route behind `EnsureTokenIsValid`.
    - Add an explicit allowlist of read-only, non-sensitive models (the app uses `api/Disability`, `api/groups` and `ProductCategory` via `is_not_private`).
    - Return `Model not found` for anything else.
    - Disable `update` for anything outside a per-model writable-field allowlist, and enforce ownership (`administrator_id`/`user_id` = auth user).
  - **Shape-safe:** yes for allowlisted models; the response wrapper doesn't change.

- **API-03 — critical — `app/Models/User.php:17-89` (no `$hidden`) combined with the public endpoints in API-02, API-05 and API-07, and Section 2/3/4 items**
  - **What:** `User` defines no `$hidden`, so any serialised User exposes:
    - `password` (bcrypt);
    - `remember_token`;
    - `intro`, which **stores the live password-reset / email-verification OTP** (`ApiAuthController.php:121`, `User::send_verification_code` `User.php:913`).
  - **Failure scenario (account takeover chain):**
    1. Call `POST /api/users/login` with `task=request_password_reset` for the victim. This sets `intro` to a 6-digit code.
    2. Read the victim's `intro` from any endpoint that serialises the User (API-02, `profiling/users/{id}`, the `ffs-groups` facilitator relation, social-fund `member`, and so on).
    3. Call `login` with `task=reset_password` and that code. The victim's password is changed.
  - **Fix:**
    - Add `protected $hidden = ['password', 'intro', 'otp_expires_at'];` to `User`.
    - Move OTPs out of `intro` into the existing `otp_codes`/`otp_verifications` tables, storing them hashed.
    - Keep `remember_token` visible for now (the login screen reads the JWT from it), or replace it with `token` once the app is updated.
  - **Shape-safe:** yes. The app defaults `password` to `''` and never reads `intro` from the login payload. Grep `intro` in the app before shipping.

- **API-04 — critical — `ApiResurceController.php:974-1037` (`account_verification`), route `api.php:55` (no auth)**
  - **What:** The user is taken from the request body (`$request->user`, which the app also sends as a form field). `task=request_verification_code` stores a new code in `intro` and emails it to **the email address in the request**. `task=verify_code` then sets that user's `email` **and** `username` to the requested address.
  - **Failure scenario:** Without credentials, an attacker can set any user's email and username to an address the attacker controls, using only the user's id. They then use `login` with `task=request_password_reset`; the reset code goes to the attacker's mailbox, and the account is taken over. No data leak is needed.
  - **Fix:**
    - Add `->middleware(EnsureTokenIsValid::class)` to the route. The middleware sets `$request->user`, which overrides the body field.
    - Bind codes to the target email with an expiry and an attempt counter.
  - **Shape-safe:** yes.

- **API-05 — critical — `ApiResurceController.php:3670-3963`, routes `api.php:755-760` (no auth): `insurance-users` CRUD**
  - **What:**
    - `GET insurance-users` returns name, phone, email, DOB and address for every non-admin user, with no pagination.
    - `PUT/PATCH/POST insurance-users/{id}` updates any user's `status`, `user_type`, `phone_number`, `email` **and `password`** (`:3891-3893`).
    - `DELETE insurance-users/{id}` **hard-deletes** any user except id 1. `User` has no SoftDeletes (`User.php:159`).
  - **Failure scenario:** An anonymous `PUT /api/insurance-users/123` with `password=…` takes over account 123. An anonymous DELETE wipes members, leaving orphaned ledger rows.
  - **Data bug:** the list query `whereNotIn(...)->orWhereNull(...)->orWhere('user_type','')->where(search)` has no grouping, so `search` only applies to the last OR branch and effectively doesn't filter. The `get([...])` column list omits `country`, `tribe`, `father_name`, `child_*` and `sponsor_id`, so those keys are always `null` in the list.
  - **Fix:**
    - Add `EnsureTokenIsValid`.
    - Restrict update and delete to self or admin; drop `password`, `user_type` and `status` from the self-update path.
    - Wrap the OR conditions in a closure and add the missing columns to the select.
  - **Shape-safe:** yes.

- **API-06 — critical — `app/Http/Controllers/Api/VslaLoansController.php:296-328` (`POST /api/vsla/loans/{id}/repayments`, used by the app in `services/vsla_loan_api.dart:170`)**
  - **What:** The loan's `amount_paid` and `balance` are saved (`:317`) **before** the `LoanTransaction::create` call (`:320`). That insert never sets `type`, but `loan_transactions.type` is `enum NOT NULL` with no default (verified with `DESCRIBE`). Laravel runs MySQL in strict mode (`config/database.php:59`), so the insert throws. There is no DB transaction.
  - **Failure scenario:** Every repayment recorded through this endpoint does three things:
    1. It reduces the loan balance, or marks the loan `paid`.
    2. It fails to write the repayment row.
    3. It returns HTTP 500 `Error recording payment: SQLSTATE[HY000]… Field 'type' doesn't have a default value`.
    The treasurer retries and the balance drops again. No group ledger (`AccountTransaction`) entry is ever created either, so group cash doesn't include the repayment.
  - **Fix:**
    - Wrap the method in `DB::transaction` with `VslaLoan::lockForUpdate()->find($id)`.
    - Set `'type' => LoanTransaction::TYPE_PAYMENT`.
    - Write the member and group `AccountTransaction` pair the same way `MeetingProcessingService::createLoanRepayment` does.
    - Reject `amount <= 0` (validation currently allows `min:0`).
  - **Shape-safe:** yes.

- **API-07 — critical — `ApiResurceController.php:799-809` (`delete_profile`), `:928-971` (`password_change`), `:46-150` (`become_vendor`); routes `api.php:56, 59, 60` (no auth)**
  - **What:** The user is taken from the body field `user`, so anyone can `POST delete-account user=<id>`. That sets `status='Deleted'`, which blocks login ("User account not found").
  - **Also a crash:** all three methods call `$this->success(..., ..., 1)` (lines 143, 809, 965), which passes `1` as the **HTTP status**. Verified: `InvalidArgumentException: The HTTP status code "1" is not valid`. The write is committed and the client then gets a 500, so password change, account deletion and become-vendor **always report failure to the app** (`AccountChangePassword.dart:197`, `AccountSection.dart:347`, `BecomeVendorFormScreen.dart:517`).
  - **Fix:**
    - Add `EnsureTokenIsValid` to the three routes.
    - Change the third argument to `200`.
  - **Shape-safe:** yes. Success currently never reaches the app, and the fixed body is the standard success envelope.

- **API-08 — critical — `ApiResurceController.php:3975-4020` (`membership_payment_create`) and `:4166-4217` (`membership_payment_confirm`); routes `api.php:763, 766` (no auth)**
  - **What:**
    - `create` takes `user_id` from the body when unauthenticated and takes **`amount` from the client**.
    - `confirm` marks any payment, found by `payment_id` or `payment_reference`, as CONFIRMED with **no gateway verification and no auth**. `MembershipPayment::confirm()` then flips `users.is_membership_paid`.
  - **Failure scenario:** Anyone can create a 1 UGX "LIFE" membership for any user and confirm it immediately.
  - **Fix:**
    - Require auth.
    - Compute the amount server-side from `membership_type`.
    - Allow `confirm` only for admins, or after `PesapalService` returns a COMPLETED status for that reference.
  - **Shape-safe:** yes.

### High

- **API-09 — high — `app/Http/Controllers/Api/VslaMeetingController.php:211-286` (same-day upsert path of `POST /vsla-meetings/submit`) + `app/Services/MeetingProcessingService.php:324-466`**
  - **What:** When a meeting for the same group and date exists, the controller `fill()`s the new payload into it and **re-runs `processMeeting()` without reversing the ledger rows from the first run**. The processors only append:
    - `createDoubleEntryTransaction` always `AccountTransaction::create`s;
    - share purchases, loan repayments and social-fund contributions append;
    - only loan disbursement has a per-meeting duplicate guard (`:869-878`).
  - Also, the new `local_id` is **not** saved (it is missing from `fill()`), so the 409 duplicate check at `:84` never catches a retry of the second submission.
  - **Failure scenario:** A chairperson re-runs the meeting flow on the same day, or the app re-sends the second `local_id` after a timeout. Each submission re-posts every saving, share, social-fund contribution and loan repayment, inflating member balances and group cash. The request returns 200 each time.
  - **Fix:** Inside the existing transaction, before re-processing:
    1. Delete or reverse the `AccountTransaction`, `ProjectShare`, `SocialFundTransaction` and `LoanTransaction` rows (and the loan balance effects) for `meeting_id = $sameDayExisting->id`, reusing the cascade logic already in the `VslaMeeting::deleting` hook (`VslaMeeting.php:37-90`).
    2. Store the new `local_id`, or keep a `local_ids` alias list, so retries return 409.
    Alternatively, make same-day resubmission return 409 with the existing `meeting_id`.
  - **Shape-safe:** yes. The response keys stay the same. If you switch to 409, the app already handles 409 for duplicate `local_id`.

- **API-10 — high — `VslaMeetingController.php:106-121, 156-172` (`submit` group/cycle resolution)**
  - **What:**
    - If the caller has no `group_id` (facilitators, staff, or unassigned users), the client's `group_id` is trusted with no check that the caller is that group's facilitator or a member.
    - If no group resolves, any `cycle_id` is accepted.
  - **Failure scenario:** Any authenticated user without a group can post meetings, money and loans into any group's active cycle.
  - **Fix:** When `user.group_id` is empty, require `FfsGroup::where('id',$gid)->where(fn($q)=>$q->where('facilitator_id',$uid)->orWhere('admin_id',$uid)...)` (or the IP scope for admins) before accepting the group.
  - **Shape-safe:** yes.

- **API-11 — high — `MeetingProcessingService.php:128-147` → returned by `VslaMeetingController.php:249-255, 332-338, 706`**
  - **What:** On any exception, `processMeeting` puts `'trace' => $e->getTraceAsString()` into the `errors[]` entry, and the controller returns `errors` to the client. Full stack traces, including file paths and SQL from the `SQLSTATE` message, reach the app. `processMeeting` also calls `DB::rollBack()` itself, then the controller calls it again.
  - **Fix:** Drop `trace` from the returned error (keep it in `Log::error`) and let the controller own the rollback.
  - **Shape-safe:** yes. `errors[].type` and `errors[].message` stay; check that the app doesn't read `errors[].trace` (grep found no use).

- **API-12 — high — `VslaShareoutController.php:311-399` (`approve`, `complete`) and `:95-149` (`initiate`)**
  - **What:**
    - Any member of the group, not just the chairperson, secretary or treasurer, can approve and **complete** a shareout. `completeShareout` marks every distribution paid and closes the cycle (`is_active_cycle='No'`, `status='completed'`) (`ShareoutCalculationService.php:373-420`).
    - `initiateShareout` has **no group check at all**: any user can open a draft shareout on any group's active cycle.
  - **Failure scenario:** An ordinary member taps "complete". The cycle closes, and every later `vsla-meetings/submit` for the group fails with "No active cycle found".
  - **Fix:**
    - Require `$user->isVslaGroupAdmin()` (or the facilitator of `group_id`) for initiate, calculate, approve, complete and cancel.
    - In `initiate`, check `$cycle->group_id == $user->group_id`.
    - Lock the shareout row (`lockForUpdate`) in `completeShareout`.
  - **Shape-safe:** yes.

- **API-13 — high — `VslaShareoutController.php:43-87` (`getAvailableCycles`)**
  - **What:** `->when($userGroupId, ...)` skips the filter when the user has no group, so **every active cycle in the system** is returned with group names. There is also an N+1: one `VslaShareout` query per cycle.
  - **Fix:** Return an empty list when there is no group (or apply the facilitator/IP scope), and eager-load or `whereIn` the shareouts.
  - **Shape-safe:** yes.

- **API-14 — high — `VslaTransactionController.php:36-199, 661-738` (`/vsla/transactions/saving|loan-disbursement|loan-repayment|fine|create`)**
  - **What:** `user_id` and `project_id` come from the body with only an `exists:` check. There is no check that the caller belongs to, or leads, that project's group, or that the member belongs to it. The whole `$request->all()` is passed to the service.
  - **Failure scenario:** Any authenticated user can record savings, fines or loans for any member of any cycle.
  - **Fix:** Load the project, then require `project.group_id == auth.group_id` (or facilitator) and `member.group_id == project.group_id`, and pass only validated fields (`$validator->validated()`).
  - **Shape-safe:** yes.

- **API-15 — high — `VslaTransactionController.php:751-925` (`GET /vsla/transactions/group-savings`) – wrong totals**
  - **What:** Group rows are selected with `is_contra_entry = false OR NULL OR account_type='fine'`, and the cash balance with `is_contra_entry = false OR NULL`. But the meeting processor writes group-side **loan_repayment** and **social_fund** rows with `is_contra_entry = 1`.
  - **Verified totals (local DB):**

    | Group-side rows | Count | Total (UGX) |
    |---|---|---|
    | `loan_repayment` | 362 | 38,569,800 |
    | `social_fund` | 1,349 | 17,774,220 |

  - **Failure scenario:** The app's group savings screen leaves out every loan repayment and social-fund contribution, from both inflow and `balances.cash`. Group cash is understated by tens of millions of UGX.
  - **Fix:** Select group rows by `owner_type='group'` only, with no `is_contra_entry` filter, or explicitly include `loan_repayment` and `social_fund`. Do the same for `$balancesQuery`.
  - **Also:** cap `limit` (currently unbounded), and replace the per-row `AccountTransaction::find` + `User::find` (2 queries per row, up to 400) with an eager-loaded contra relation.
  - **IDOR:** `group_id` is not checked against the caller.
  - **Shape-safe:** yes. The values become correct and the keys don't change.

- **API-16 — high — `VslaTransactionController.php:210-410, 420-488, 498-588, 598-650` (balance, statement, recent, dashboard-summary, group-members)**
  - **What:** Each endpoint takes `user_id`, `group_id` or `project_id` from the URL or query with no ownership check (IDOR).
    - `getGroupMembers` returns names, phones and emails of any group's members by `project_id`.
    - `getRecentTransactions` requires `group_id` but **never filters by it**: with only `group_id`, it returns the latest transactions across all groups.
    - `limit` is unbounded, and there is an N+1 `User::find` per row (`:456`).
  - **Fix:**
    - Scope every query to a group the caller belongs to or facilitates.
    - Add `where('group_id', $groupId)` (or the cycle's group) in `getRecentTransactions`.
    - Clamp `limit` to 200 or less.
  - **Shape-safe:** yes.

- **API-17 — high — `app/Http/Controllers/Api/UserRegistrationController.php:441-609` (`profiling/users`, `/{id}`, `/{id}/status`)**
  - **What:**
    - **(a) index:** `summary.verified` queries `phone_number_verified_at`, which is not a column in the local `users` table. The whole endpoint throws and returns 500 `Error: SQLSTATE[42S22]…`. The app uses this list (`profiling_users_list_screen.dart:74`). Schema-dependent.
    - **(b)** `orderBy($request->order_by, $request->order_dir)` and `paginate($request->per_page)` are unvalidated: any column, an invalid direction gives a 500, and `per_page` is unbounded.
    - **(c)** Full User models with password hashes are returned in `data` (index and show).
    - **(d)** `show` and `updateStatus` have no authorization: any authenticated user can read any user, or activate or deactivate anyone.
    - **(e)** `updateStatus` `verify` writes `phone_number_verified_at`, which isn't in `$fillable`, so it is silently discarded but reports success.
  - **Fix:**
    - Remove or guard the `verified` count (`Schema::hasColumn`).
    - Allowlist `order_by`, `order_dir` and `per_page` (100 or less).
    - Hide sensitive fields (API-03).
    - Require admin, IP manager or facilitator for show and status.
  - **Shape-safe:** yes.

- **API-18 — high — `routes/api.php:869-937` (`GET /api/ajax` closure, no auth)**
  - **What:** The model class (`App\Models\` + `?model=`), the search columns (`search_by_1`, `search_by_2`) and arbitrary `query_*` equality conditions all come from the request.
  - **Failure scenario:** `?model=User&search_by_1=password&q=$2y$10$a` returns ids whose hash matches a LIKE prefix, a boolean oracle that extracts hashes, `intro` OTPs or tokens one character at a time. It also enumerates any table's ids and names.
  - **Fix:** Move the route to `web.php` behind `admin` middleware (it serves Laravel-Admin selects). Allowlist models and searchable columns.
  - **Shape-safe:** yes. The app does not call `/api/ajax`.

- **API-19 — high — `ApiResurceController.php:153-308` (`update_profile`), `:316-558` (`profile_update_comprehensive`)**
  - **What:** Both end with `$this->success($user, "...", 1)` (lines 304, 549). That throws `InvalidArgumentException` (HTTP status 1). The exception is caught by the method's own `catch (\Throwable)`, which returns `code:0 "Failed to update profile: The HTTP status code "1" is not valid."` **after `$u->save()` has committed**. Every profile edit from the app is reported as a failure even though it was saved.
  - **Also:**
    - `update_profile` lets the client set `intro` directly (`:283-285`), which overwrites a pending OTP.
    - `profile_update_comprehensive` puts children data in `remember_token` (read back at `:500-512`), overloading the field that also carries the JWT.
  - **Fix:**
    - Change `1` to `200`.
    - Stop accepting `intro` from the client.
    - Store children data in its own columns (`child_1..4` exist).
  - **Shape-safe:** yes.

- **API-20 — high — `ApiResurceController.php:1176-1190, 1230-1237, 1267-1286, 2401-2497`; routes `api.php:62-91` (legacy e-commerce, no auth)**
  - **What:**
    - `order`, `orders/{id}`: read any order.
    - `cancel-order`: **hard-delete** any order by id.
    - `products-update`: edit any product's price.
    - `products-delete`, `images-delete`: delete any product or image.
    None of these check auth or ownership.
  - **Fix:** Add `EnsureTokenIsValid` and check `order.user == auth id` / `product.user == auth id`, or admin. Alternatively, disable the routes if e-commerce is retired.
  - **Shape-safe:** yes.

### Medium

- **API-21 — medium — `routes/api.php:37` vs `:99`: duplicate `POST users/register`**
  - **What:** Both are registered; the later `ApiAuthController::register` wins (verified via `Router::match`). `UserRegistrationController::register` (role-based profiling, `:20-225`) is dead code.
  - **Fix:** Delete one definition, or move the profiling registration to a distinct URI.
  - **Shape-safe:** yes (behaviour is unchanged).

- **API-22 — medium — `routes/api.php:94` vs `:690`: duplicate `GET manifest`**
  - **What:** The public `ApiResurceController::manifest` is overwritten by the authenticated `ManifestController::getManifest`. The first definition is dead.
  - **Fix:** Remove line 94.
  - **Shape-safe:** yes.

- **API-23 — medium — `routes/api.php:87-91`: routes to missing methods**
  - **What:** `chat-start`, `chat-send`, `chat-mark-as-read`, `chat-heads` and `chat-messages` point at `ApiResurceController` methods that don't exist (verified with `method_exists`). Every call returns 500 `BadMethodCallException`.
  - **Fix:** Remove the routes, or restore the methods (`ChatHead`/`ChatMessage` are still imported at `:6-7`).
  - **Shape-safe:** yes.

- **API-24 — medium — shadowed routes (verified with `Router::match`)**
  - `GET vsla/attendance/export-pdf` is caught by `vsla/attendance/{id}` (`api.php:368-369`), so the PDF export is unreachable. Also reported as API-65.
  - `GET projects/transactions` is caught by `projects/{id}` (`api.php:944` vs `:950`). Also reported as API-123.
  - `GET locations/parishes/{subcountyId}` (`LocationController`, `:429`) is unreachable behind `ApiResurceController::locations_parishes` (`:79`). `GET locations/districts` (`:75`) is overwritten by `:427`.
  - **Fix:** Declare static segments before `{id}`, or add `->where('id','[0-9]+')`. Remove the dead duplicates.
  - **Shape-safe:** yes for the attendance and projects routes. For locations, keep whichever handler the app currently gets.

- **API-25 — medium — `VslaMeetingController.php:826-937` (`GET vsla-meetings/export`)**
  - **What:** Non-admins are scoped to their group only `if ($user->group_id)`. A user without a group gets **all meetings system-wide**, and can also pick any `group_id`. There is no IP scope.
  - **Fix:** Apply the same scoping as `index` (no group means empty), plus the IP and facilitator scope.
  - **Shape-safe:** yes (it's a CSV).

- **API-26 — medium — `VslaMeetingController.php:888, 991`: wrong savings key in the CSV export and cycle report PDF**
  - **What:** Savings are counted only when `accountType === 'saving'`. The app sends `'savings'` (`VslaMeetingsHubScreen.dart:188`; `VslaOfflineMeeting.dart:361`), and the server's own `calculateSubmittedTotals` uses `'savings'` (`:1148`). So the "Total Savings" column and the cycle report's savings and leaderboard are always 0.
  - **Fix:** Accept `in_array($type, ['saving','savings'])`.
  - **Shape-safe:** yes.

- **API-27 — medium — `VslaMeetingController.php:671-714` (`PUT vsla-meetings/{id}/reprocess`)**
  - **What:** There is no authorization: any authenticated user can reprocess any group's meeting. It also has the same non-reversal problem as API-09, although it is limited to `failed`/`needs_review` statuses (and `needs_review` isn't a valid enum value, per the service comment at `:117`).
  - **Fix:** Require admin or group leader of `meeting.group_id`, and reverse previous postings first.
  - **Shape-safe:** yes.

- **API-28 — medium — `VslaLoansController.php:142-252` (`GET vsla/loans/{id}`)**
  - **What:**
    - The group check is skipped when the caller has no `group_id`, so anyone without a group can read any loan.
    - Ordinary members can read other members' loans, which `index` deliberately prevents (`:78-81`).
    - `borrower_phone` reads `$loan->borrower->phone`, which isn't a column (`phone_number` is), so it is always null.
    - `cycle_name` reads `$loan->cycle->name`, which isn't a column (`cycle_name`/`title` are), so it is always null.
    - The interest split (`:190-192`) uses the loan's **current** total `amount_paid` for every historic payment, so `principal_paid`/`interest_paid` are wrong for loans with more than one repayment.
  - **Fix:**
    - Deny when the cycle's group isn't the caller's (unless facilitator or admin), and limit non-admins to their own loans.
    - Use `phone_number` and `cycle_name ?? title`.
    - Compute the allocation with a running total.
  - **Shape-safe:** yes.

- **API-29 — medium — `VslaLoansController.php:279-287` (`recordRepayment` authorization)**
  - **What:** The group check is skipped for callers without a group. There is no role check, so an ordinary member can record a repayment against **their own** loan and mark it paid without paying cash. The write is a read-modify-write with no row lock, so concurrent submissions double-apply.
  - **Fix:** Require a group leader (`isVslaGroupAdmin()`) of the loan's group, and add `lockForUpdate` (with API-06).
  - **Shape-safe:** yes.

- **API-30 — medium — `app/Http/Controllers/Api/PhoneVerificationController.php:104-111, 158-161`**
  - **What:** OTPs are created with `expires_at = now()->addYears(10)`, and `verifyOtp` never checks expiry or counts attempts. The code comes from `rand()`, which isn't a CSPRNG. `checkPhone` and `sendOtp` are public and confirm whether a phone number belongs to a chairperson (enumeration), and repeated `send-otp` calls cost SMS money with no throttle beyond `throttle:api`.
  - **Fix:**
    - Use a 10–15-minute expiry checked in `verifyOtp`.
    - Use `random_int`.
    - Add a per-phone attempt counter and an SMS rate limit.
  - **Shape-safe:** yes.

- **API-31 — medium — `ApiAuthController.php:102-164` (`login` with `task=…`)**
  - **What:**
    - The reset OTP is compared from `users.intro`, which leaks (API-03).
    - There is no attempt counter; with `throttle:api` at 60/min per IP, a distributed brute force of a 6-digit code within the 15-minute window is feasible.
    - `request_password_reset` reveals account existence ("No account found…") and returns mailer exception text (`:155`).
    - `login` itself distinguishes "User account not found" from "Wrong credentials" (enumeration).
  - **Fix:** Store a hashed OTP with an attempt counter (lock after 5), give a generic response for unknown accounts, and log mailer errors instead of returning them.
  - **Shape-safe:** yes (messages only).

- **API-32 — medium — `ApiAuthController.php:206-219, 343-349`; `ApiResurceController.php:1416-1444` (`users/me`)**
  - **What:** Login, register and `my_profile` return the full User model, including `password` hash and `intro`. This one is limited to the caller's own record, so it's lower risk than API-03, but the hash and OTP still end up in app storage and logs: the app persists `password` into SQLite (`LoggedInUserModel.dart:352, 507`).
  - **Fix:** Covered by `$hidden` (API-03).
  - **Shape-safe:** yes.

- **API-33 — medium — `VslaMeetingController.php:388, 519-527` (performance)**
  - **What:**
    - `show` eager-loads `processor`, `attendance.member`, `actionPlans.assignedTo` and `loans.borrower`, then never uses them: about 4 wasted queries and full User hydration per call.
    - `index` returns every completed meeting of the group with no pagination ("keep it simple").
  - **Fix:** Load only `cycle`, `group` and `creator` in `show`. Keep `index` unpaginated for the app, but add an optional `limit`.
  - **Shape-safe:** yes.

### Low

- **API-34 — low — `VslaMeetingController.php:447-458`, `UserRegistrationController.php:488-490`: unvalidated sort direction passed to `orderBy`**
  - **What:** Laravel 8 throws `InvalidArgumentException` for anything other than `asc`/`desc`, giving a 500 with the message.
  - **Fix:** `in_array(strtolower($dir), ['asc','desc']) ? $dir : 'desc'`.
  - **Shape-safe:** yes.

- **API-35 — low — `VslaMeetingController.php:84`**
  - **What:** The duplicate check runs `VslaMeeting::where('local_id')` with SoftDeletes, so it excludes trashed rows. The unique index `vsla_meetings_local_id_unique` includes trashed rows (e.g. rows 70, 71, 81 and 85 are soft-deleted). Re-submitting a `local_id` whose meeting was soft-deleted gets a raw 500 unique-violation instead of 409.
  - **Fix:** Use `withTrashed()` in the duplicate check.
  - **Shape-safe:** yes.

- **API-36 — low — `ShareoutCalculationService.php:47-66, 380-393`: transactions left open on early return**
  - **What:** `DB::beginTransaction()` is followed by early `return` statements without `rollBack()`. There are no writes in between, but the open transaction level persists for the rest of the request.
  - **Fix:** Call `rollBack()` before each return, or use `DB::transaction()`.
  - **Shape-safe:** yes.

- **API-37 — low — `VslaShareoutController.php:276-287`**
  - **What:** `$shareout->cycle->cycle_name` and `$shareout->group->name` are dereferenced without null checks. If the cycle or group was deleted (group delete exists, see API-54), the summary returns 500.
  - **Fix:** Use `?->` with defaults.
  - **Shape-safe:** yes.

- **API-38 — low — the 500-body logging in `EnsureTokenIsValid.php:94-98` and `ApiResponser::error` (`:62-74`)**
  - **What:** The first 2,000 characters of every 5xx body are logged, and those bodies often hold PII or SQL (see API-11). A large share of controllers return `'…: ' . $e->getMessage()` to the client, which leaks SQL, table and column names (throughout `VslaMeetingController`, `VslaShareoutController`, `VslaLoansController`, `VslaTransactionController` and `ApiResurceController`).
  - **Fix:** Return a generic message plus a correlation id; log the detail server-side.
  - **Shape-safe:** yes (the `message` text changes, not the keys).

- **API-39 — low — `routes/api.php:444-450`, `:865-867` (`auth:sanctum`)**
  - **What:** Review create/update/delete and `/api/user` use the Sanctum guard, but the app authenticates with JWT and `User-Id`, and no Sanctum tokens are ever issued (no `createToken` call). These routes always return 401 to the app. Also reported as API-106.
  - **Fix:** Switch them to `EnsureTokenIsValid`.
  - **Shape-safe:** yes.

- **API-40 — low — `app/Http/Kernel.php:42-46` / middleware design**
  - **What:** `$request->user = $user_id` in the middleware relies on a dynamic property on `Request` (`EnsureTokenIsValid.php:81-82`). On routes **without** the middleware, `$request->user` silently falls back to the request input field `user`. That fallback is the root cause of API-04 and API-07: controllers can't tell an authenticated id from a client-supplied one.
  - **Fix:** Have controllers read `auth()->id()` (set by the middleware) instead of `$request->user`, so a missing middleware fails closed (null) instead of trusting the body.
  - **Shape-safe:** yes.

---

## Section 2 – VSLA secondary controllers (facilitator, onboarding, configuration, manifest, opening balance, social fund, attendance, loans)

Scope: Api/VslaFacilitatorController, VslaGroupManagementController, VslaGroupResetController, VslaAgentOnboardingController, VslaOnboardingDataController, VslaConfigurationController, VslaGroupManifestController, VslaOpeningBalanceController, SocialFundTransactionController, VslaDashboardController, VslaGroupStatsController, AttendanceController, UserLoanController, plus app/Http/Controllers/VslaOnboardingController.php and LoanTransactionController.php.

How I checked: I read every file in full and checked each column against `information_schema` and live data in the local `fao_ffs_mis` DB (SELECT only). I confirmed two behaviours with read-only `php artisan tinker` runs (MAMP PHP 8.3.9).

**Systemic fact behind several items (verified by tinker).** `FfsGroup::$appends` includes `facilitator_name` and `district_name`. Their accessors (`app/Models/FfsGroup.php:263-271`) lazy-load the `facilitator` and `district` relations while `attributesToArray()` runs. `relationsToArray()` runs after that, so it also serialises the full `facilitator` User. Result: **every raw `FfsGroup` in a JSON response includes the facilitator's `password` hash, `remember_token` and `intro`.** Tinker output for a sample group: keys `...,district,facilitator`, and facilitator keys include `password,remember_token,intro`.

**Shape note on fixing User leaks.** The mobile parsers (`LoggedInUserModel.dart`, `UserModel.dart`, `VendorModel.dart`) read `password` and `intro` through `Utils.to_str(m['password'], '')`, so a missing key falls back to `''`. That means hiding `password` and `intro` is tolerated by the client. Do **not** hide `remember_token` globally. Login and registration screens read `user.remember_token` as the JWT (`login_screen.dart:73`, `VslaRegistrationScreen.dart:279`), and the backend injects the token into that key. Strip `remember_token` only from *other users'* objects.

---

### CRITICAL

- **API-41 — critical — `app/Http/Controllers/VslaOnboardingController.php:104-272` (`POST /api/vsla-onboarding/register-admin`, public route at `routes/api.php:110`)**
  - **What's wrong:** This is an unauthenticated route. It looks up *any* existing user by phone or username (lines 145-152) and overwrites that user's password with the caller's value (lines 168 and 233). It then issues a 1-year JWT (lines 186-187 and 247-248). There is no OTP and no ownership proof. For users not at `step_7_complete` it also sets `user_type='Customer'` and `is_group_admin='Yes'` (lines 235 and 239). The response also returns `$user->toArray()`, which includes the new password hash and `intro` (lines 193 and 254).
  - **Failure scenario:** An attacker who knows the phone number of any account, including the two `user_type='Admin'` users in the DB (one is `not_started`, one is `step_7_complete`), POSTs `{name, phone_number, password, password_confirmation}`. They get a valid token and the victim is locked out. For the `not_started` admin, the account is also demoted to Customer.
  - **Fix:** Require a verified phone OTP (the `otp_verifications` flow) before changing the password. Refuse users that already have a password set, `user_type='Admin'`, or a role other than `farmer_member`. Never reset the password on the `step_7_complete` path; send them to the normal login instead. Drop the 1-year TTL override. `makeHidden(['password','intro'])` before `toArray()`.
  - **Shape-safe:** yes. Same keys are returned; only `password` and `intro` disappear from `user`, and the client defaults those.

- **API-42 — critical — `VslaOnboardingController.php:931-941` (`GET vsla-onboarding/status`), also `:875-893` (`complete`) and `:610-619` (`register-main-members`)**
  - **What's wrong:** `getOnboardingStatus` returns `'user' => $user`, `'group' => $group` (which leaks the facilitator, per the systemic note), and `'secretary'`/`'treasurer' => User::find(...)` as raw models. Any user whose `group_id` is set gets the secretary's and treasurer's `password` hash, `remember_token` and `intro`. `intro` holds the password-reset OTP. `completeOnboarding` (lines 878-893) and `registerMainMembers` (lines 610-619) do the same through `toArray()` with only the location relations unset.
  - **Failure scenario:** Any ordinary member calls `/vsla-onboarding/status` and receives the officers' bcrypt hashes for offline cracking. If an officer has a pending reset OTP in `intro`, the member can take over that officer's account. Six users currently hold an OTP-like `intro`.
  - **Fix:** `makeHidden(['password','intro','remember_token'])` on the secretary, treasurer and group-facilitator objects. For `group`, use `$group->setAppends([])->makeHidden(['facilitator','district'])`, or keep the appends and unset the relations after `toArray()`.
  - **Shape-safe:** yes for the officer and group objects (the client defaults missing `password` and `intro`). Keep `remember_token` on the caller's own `user` object.

- **API-43 — critical — `SocialFundTransactionController.php:57, 211, 223, 240` (`/api/social-fund/transactions`, `/transactions/{id}`)**
  - **What's wrong:** None of `index`, `show` or `store` checks that the caller belongs to `group_id`. `index` eager-loads `member` and `creator` (raw Users), up to 100 per page. `show` also loads `group`, which pulls in the facilitator through the appends.
  - **Failure scenario:** `GET /api/social-fund/transactions?group_id=N&per_page=100` for every N dumps the password hash, `intro`, NIN and phone of every contributing member in every group. `show/{id}` enumerates the same data one record at a time.
  - **Fix:** Resolve the group from `auth()->user()->group_id`, or require the caller to be a member, officer or facilitator of that group. Load the relations with column lists (`member:id,name,first_name,last_name,phone_number,member_code`, `creator:id,name`), or `makeHidden` on the User relations.
  - **Shape-safe:** yes if the same relation keys are kept and only the sensitive attributes are removed (the client tolerates missing `password` and `intro`).

- **API-44 — critical — `Api/VslaAgentOnboardingController.php:456-538` (`createOrUpdateOfficer`) via `POST agent-vsla/register-officers`; `createGroup` at `:50-127` has no role check**
  - **What's wrong:** `findUserByPhone` (`app/Traits/PhoneNumberNormalization.php:87-103`) matches *any* user by phone, username, `business_phone_number`, or a `LIKE '%<last 9 digits>'` suffix. The controller then overwrites that user's name, email, `password` (`Hash::make($password)`, where the chairperson password is caller-chosen at line 201), `user_type='Customer'`, `group_id`, `ip_id` and role flags (lines 476-524). It never checks that the matched user is unassigned or is an ordinary member. Any authenticated user can call `createGroup`, which makes them `facilitator_id`, so they pass the facilitator check at line 194.
  - **Failure scenario:** User X creates a dummy group. X then calls `register-officers` with `chairperson_phone` set to an admin's or another group's chairperson's phone and `chairperson_password=1234`. That account's password is replaced and the account is moved into X's group, so X can log in as the victim. The suffix LIKE match can also hit the wrong person.
  - **Fix:** If an existing user is matched, do not change the password or user_type. Reject matches that have another `group_id`, `user_type='Admin'`, or admin roles. Use an exact normalised phone match. Gate `createGroup` and `registerOfficers` on a facilitator or IP role.
  - **Shape-safe:** yes.

- **API-45 — critical — `Api/VslaOpeningBalanceController.php:462-518` (`POST vsla/opening-balance/reprocess`) together with `app/Services/OpeningBalanceService.php:36-64`**
  - **What's wrong:** There is no group or role check; any `cycle_id` in `projects` is accepted. `process()` deletes only the `account_transactions` rows matching `description LIKE 'Opening balance%'` (lines 56-60). It then **re-creates** `ProjectShare`, `VslaLoan`, `LoanTransaction` and `SocialFundTransaction` rows (`fanOut`, `ProjectShare::create` / `VslaLoan::create` / `SocialFundTransaction::create`) without deleting the earlier ones. Reprocess also runs on records already in `processed` status (line 481).
  - **Failure scenario:** Calling reprocess once on a processed cycle doubles every member's opening loans (new active `vsla_loans` rows with balances), shares and social-fund contributions. Any authenticated user can do this to any of the 66 processed opening balances.
  - **Fix:**
    - Restrict to the group's chairperson or facilitator, and check `$ob->group_id` against the caller.
    - Only allow `status='submitted' AND is_processed=0`.
    - Make `process()` idempotent: delete the rows it created earlier, e.g. `vsla_loans` with `purpose='Opening balance carry-over' AND meeting_id IS NULL`, `social_fund_transactions` with `reason='opening_balance'`, the matching `project_shares` and `loan_transactions`. Better, link them to an `opening_balance_id`.
    - Lock the OB row with `lockForUpdate()`.
  - **Shape-safe:** yes.

- **API-46 — critical — `app/Http/Controllers/LoanTransactionController.php:195-325` (`/api/loan-transactions/penalty`, `/waiver`) plus `app/Models/LoanTransaction.php:129-132`**
  - **What's wrong (no authorization):** Any authenticated user can add a penalty or a waiver to *any* loan, including their own.
  - **What's wrong (sign error):** `calculateLoanBalance` is `SUM(amount)` under the convention "principal negative, payment positive". Every loan in the DB was created by `OpeningBalanceService` with a **positive** principal and a positive payment (822 `opening_balance/principal` rows, all positive). Example: loan #7 has `loan_amount=60000`, `balance=10000`, and `SUM(loan_transactions.amount)=110000`. Lines 230 and 297 then set `balance = abs(newBalance)`.
  - **Failure scenario:** A borrower calls `/waiver` with `amount=1` on loan #7. The balance jumps from 10,000 to 110,001. A penalty instead *reduces* the balance. If the sum ever reaches about 0, the loan is set to `paid`. With no authorization, a borrower can also waive their own loan.
  - **Fix:** Require the caller to be an officer (chair or treasurer) of the loan's cycle group, never the borrower. Compute the new balance from the stored `vsla_loans.balance` plus or minus the amount (penalty adds, waiver and payment subtract, floor at 0) instead of summing transactions. Alternatively, normalise principal signs in the service. Wrap in `lockForUpdate()` on the loan.
  - **Shape-safe:** yes.

### HIGH

- **API-47 — high — `LoanTransactionController.php:133-152` (`POST /api/loan-transactions/payment`)**
  - **What's wrong:** It creates an `AccountTransaction` with `user_id => null`, but the column is `NOT NULL`. It also sets `source => 'loan_repayment'`, but the column is `enum('disbursement','withdrawal','deposit')`. With `'strict' => true` (`config/database.php:59`) every call throws a `QueryException`. The catch returns HTTP 500 with the raw SQL in `message` (line 181).
  - **Further defects:** `group_id`, `cycle_id`, `owner_type` and `account_type` are never set, so even a fixed version would be invisible to every group or cycle ledger. There is no authorization and no overpayment guard: `abs()` turns an overpayment into a positive balance with status still `active`. The sign problem from API-46 also applies.
  - **Failure scenario:** The endpoint always fails and exposes the SQL text.
  - **Fix:** Set `user_id` (the group side to the chairperson, as `OpeningBalanceService` does), `owner_type`, `group_id` (from `loan->cycle->group_id`), `cycle_id`, `account_type='loan_repayment'` and `source='deposit'`. Cap the amount at the balance. Add authorization.
  - **Shape-safe:** yes.

- **API-48 — high — `Api/VslaFacilitatorController.php:496-512` (`addMember`) together with `Api/VslaGroupManagementController.php:346-374` (`setMemberPassword`)**
  - **What's wrong:** `addMember` takes any existing user whose phone matches and who has `group_id` null, including staff and Admin accounts (two Admins have no group). It sets that user's `group_id` to the facilitator's group without consent. `setMemberPassword` lets the facilitator set the password of anyone in their group.
  - **Failure scenario:** A facilitator, or any user who first calls `agent-vsla/create-group`, adds an admin's phone as a member and then calls `set-password`. They now have the admin's credentials.
  - **Fix:** In `addMember`, only attach existing users that are ordinary members (`user_type` Customer or member, no admin roles); otherwise return 422. In `setMemberPassword`, refuse targets that have admin roles or `user_type='Admin'`.
  - **Shape-safe:** yes.

- **API-49 — high — `SocialFundTransactionController.php:159-231` (`store`)**
  - **What's wrong (authorization and validation):** Any authenticated user can write contributions or withdrawals into any `group_id`. `cycle_id` and `member_id` are only checked with `exists`, not checked to belong to that group.
  - **What's wrong (race):** The withdrawal balance check (lines 185-195) is a read-then-insert with no transaction or lock.
  - **What's wrong (ledger):** If `cycle_id` is omitted, the balance is computed across all cycles but the row is stored with `cycle_id` NULL. No matching `account_transactions` pair is written, so the social-fund ledger and the double-entry ledger drift apart. The response embeds raw `member` and `creator` Users (see API-43).
  - **Failure scenario:** A member of group A drains group B's social fund on paper. Two concurrent withdrawals both pass the balance check and push the balance below zero.
  - **Fix:** Authorize as an officer of the group. Validate that the cycle belongs to the group and the member's `group_id` matches. Wrap in `DB::transaction` with `SocialFundTransaction::where(group,cycle)->lockForUpdate()` before summing. Require `cycle_id` (or default it to the active cycle).
  - **Shape-safe:** yes.

- **API-50 — high — `Api/VslaOpeningBalanceController.php:269-287` and `:336-353` (`store`)**
  - **What's wrong (any group):** When the caller has no `group_id`, the controller falls back to the client-supplied `group_id` (lines 278-279). A facilitator, admin or any group-less account can therefore write opening balances, loans and shares into any group.
  - **What's wrong (no role check):** Any group member, not only the chairperson, can submit.
  - **What's wrong (race):** The dedupe check (lines 336-351) runs *before* `DB::beginTransaction()`, without a lock, and there is no unique index on `vsla_opening_balances(cycle_id)` (only non-unique indexes exist).
  - **Failure scenario:** A double-tap or retry on a slow network produces two processed opening balances, so every loan and share is duplicated. The same happens for a group-less user posting any group.
  - **Fix:** Require the caller to be the group's chairperson or treasurer, or its facilitator; never fall back to the request `group_id` without that check. Move the dedupe inside the transaction with `lockForUpdate()` on the cycle's `projects` row, and add a unique key, e.g. on `(cycle_id, status)` or a generated `active_cycle_key`.
  - **Shape-safe:** yes. The existing 409 response shape is kept.

- **API-51 — high — `Api/VslaGroupManagementController.php:603-676` (`performMemberDelete`), specifically `:638-639`**
  - **What's wrong:** It deletes `account_transactions WHERE group_id=? AND user_id=member`. But the *group-side* leg of each double-entry pair is stored with `user_id` = the chairperson. That covers 4,138 of the 4,399 group rows (3,935 of them opening-balance rows), as written by `OpeningBalanceService` (`user_id => $submittedById`). Deleting a member therefore removes only the member legs and the member's `vsla_loans`. The group legs (share deposits and loan disbursements credited to the group) remain.
  - **Failure scenario:** After a member is deleted, group totals (`owner_type='group'` sums used by the dashboard and stats) still include that member's shares and loan outflows. Cash at hand and share value stop reconciling with the sum of member balances.
  - **Fix:** Delete pairs through `contra_entry_id`, or delete the group-side rows whose `contra_entry_id` points at the member rows being deleted. Alternatively soft-delete or archive the member instead of hard-deleting financial history.
  - **Shape-safe:** yes.

- **API-52 — high — `Api/VslaGroupManifestController.php:254-270, 407-412, 563-574` (case mismatch) and `:716-718` (double count)**
  - **What's wrong:** The real `account_type` values are lowercase (`share`, `loan`, `loan_repayment`, `fine`, `social_fund`). The manifest filters *in PHP collections* on `'SHARE'`, `'DEPOSIT'`, `'SAVING'`, `'LOAN_REPAYMENT'`, `'FINE'` and `'WELFARE'`, which is a case-sensitive match. Tinker on cycle 123: `where('account_type','SHARE')->sum()` gives `0`, while `'share'` gives `1242000`.
  - **Effect:** Every `financial_summary` (cycle, member and meeting) reports 0 shares, 0 savings, 0 repayments and 0 fines. `group_cash_balance` comes out as minus total loans.
  - **The opposite bug:** `getDashboardData` line 716 filters in SQL, where the `utf8mb4_unicode_ci` collation is case-insensitive. It sums both the group and member legs, so `total_repaid` is roughly doubled (1,240,000 shown for cycle 123).
  - **Failure scenario:** The offline manifest that drives the meeting hub shows members with zero shares and the group with negative cash.
  - **Fix:** Use the real lowercase types, and filter `owner_type='member'` for member figures and `'group'` for group figures. Push the sums into SQL (`SUM(CASE ...)` grouped by `user_id`), as in `AccountTransaction.php:76-79`.
  - **Shape-safe:** yes. Values change; keys and types do not.

- **API-53 — high — `Api/VslaConfigurationController.php:139-203` (`PUT vsla/groups/{id}/basic-info`)**
  - **What's wrong (authorization):** Any group member passes (`$isMember`, line 151), despite the "chairperson or facilitator" message.
  - **What's wrong (leak):** The response is `$group->fresh(['admin','secretary','treasurer','district'])` plus the auto-loaded `facilitator`, which is four raw Users with password hashes and `intro`.
  - **Minor:** `$user->id` is dereferenced (line 150) before the `!$user` check (line 154).
  - **Failure scenario:** Any member renames the group or moves its district, and receives the chair's, secretary's, treasurer's and facilitator's hashes.
  - **Fix:** Allow only `admin_id` or `facilitator_id`. `makeHidden` on the relation Users (`password`, `intro`, `remember_token`).
  - **Shape-safe:** yes, with the same keys minus secrets.

- **API-54 — high — `Api/VslaGroupManagementController.php:512-598` (`performGroupDelete`) and `VslaOnboardingController.php:408`**
  - **What's wrong (orphans):** Group delete hard-deletes every `users` row with that `group_id` (line 592). It does **not** delete their `admin_role_users` rows (`performMemberDelete` does, at line 670) or their `ffs_session_participants` rows. The DB already holds 506 `admin_role_users` rows pointing at non-existent users.
  - **What's wrong (who can delete):** `canDeleteGroup` relies on `facilitator_id`. The self-onboarding flow sets `facilitator_id = chairperson` (`VslaOnboardingController.php:408`), so a self-registered chairperson can permanently delete the whole group and every member account. One live group is in this state.
  - **Failure scenario:** A chairperson wipes all member accounts and leaves orphaned role rows and training participations.
  - **Fix:** In `performGroupDelete`, delete `admin_role_users` and `ffs_session_participants` for the member IDs. Stop setting `facilitator_id = chairperson` during self-onboarding, or require a real facilitator or super admin in `canDeleteGroup`.
  - **Shape-safe:** yes.

### MEDIUM

- **API-55 — medium — `Api/VslaConfigurationController.php:381-386, 582-587, 706-711, 805-822, 847-876`**
  - **What's wrong:** Cycle creation, update, close or reopen, and auto-activate are all allowed for *any* group member through the `$isMember` fallback. `updateCycle` also lets a member change `share_value`, `saving_type` or interest rates on a closed cycle, or mid-cycle; there is no `is_active_cycle` check despite the docblock. `autoActivateCycle` falls back to the most recent **completed** cycle and reopens it (lines 854-858, 869-871).
  - **Failure scenario:** An ordinary member closes the active cycle, or changes the interest rate from 10 to 0. Or the app's auto-activate silently reopens a shared-out cycle.
  - **Fix:** Require `admin_id` (or the facilitator) for writes. Reject updates to completed cycles. Never auto-reopen `status='completed'`.
  - **Shape-safe:** yes. These are error responses with existing codes.

- **API-56 — medium — `VslaConfigurationController.php:735-752` (`updateCycleStatus`), `:829-876` (`autoActivateCycle`), `Api/VslaAgentOnboardingController.php:338-394` (`createCycle`)**
  - **What's wrong:** The single-active-cycle rule is check-then-write with no transaction or lock in the first two. The agent `createCycle` deactivates and then inserts without locking. There is no DB constraint.
  - **Also:** Agent `createCycle` returns early (lines 357-359) after already running the deactivate `UPDATE` inside an open transaction, with no rollback. It relies on the connection closing to roll back.
  - **Failure scenario:** Two concurrent activations (two officers' phones, or a retry) leave two `is_active_cycle='Yes'` rows. Downstream code calls `->first()` and posts meetings to an arbitrary cycle.
  - **Fix:** Wrap each path in `DB::transaction` with `Project::where('group_id',..)->where('is_vsla_cycle','Yes')->lockForUpdate()->get()` first. Validate the dates before the deactivate. Add `DB::rollBack()` before the early return.
  - **Shape-safe:** yes.

- **API-57 — medium — `Api/VslaGroupResetController.php:340-360` (reset) vs `:88-132` (preview)**
  - **What's wrong:** A VSLA chairperson's "reset" hard-deletes data that IP and facilitator staff own: `ffs_kpi_ip_entries` (IP annual targets and monthly values), `ffs_kpi_facilitator_entries`, `aesa_sessions` with their observations, and `ffs_session_target_groups`. The preview the chairperson confirms against does not list either KPI table, so `total_records_to_delete` under-reports.
  - **Failure scenario:** The chairperson types "delete all" and erases the IP's KPI reporting for that group, which cannot be recovered.
  - **Fix:** Limit reset to the VSLA ledger tables. If KPI, AESA or training data must go, require a facilitator or super admin and include those counts in the preview.
  - **Shape-safe:** yes. Removing keys from `deleted_counts` is safe (it is an open map), or keep the keys with 0.

- **API-58 — medium — `VslaOnboardingController.php:979` (`POST vsla-onboarding/update-step`)**
  - **What's wrong:** `return $this->error('Validation failed', $validator->errors());` passes a `MessageBag` as the HTTP status code. `ApiResponser::error()` then calls `response()->json($response, $code)`, and `JsonResponse::__construct(..., int $status)` throws a `TypeError` on PHP 8.
  - **Failure scenario:** Any invalid or missing `onboarding_step` returns an unhandled 500 instead of a validation error.
  - **Fix:** `return $this->error('Validation failed', 422, $validator->errors());`
  - **Shape-safe:** yes. This is the standard error envelope.

- **API-59 — medium — `Api/VslaGroupStatsController.php:17-76` (`GET vsla-groups/stats`)**
  - **What's wrong:** There is no authorization at all; any `group_id` and `cycle_id` pair works. It returns full financials and top saver / top borrower names. It also never checks that the cycle belongs to the group, and loans are filtered only by `cycle_id` (lines 114-117, 231-237), so mismatched pairs mix two groups' data.
  - **Failure scenario:** Any user enumerates every group's cash position and its largest borrowers.
  - **Fix:** Require membership of, or facilitation of, the group. Require `projects.group_id = group_id`.
  - **Shape-safe:** yes.

- **API-60 — medium — `Api/VslaOpeningBalanceController.php:421-442` (`GET vsla/opening-balance/{id}`) and `:165-215` (`status`)**
  - **What's wrong:** Neither checks group ownership. `show` returns every member's opening shares, loans and social fund for any group's record ID.
  - **Failure scenario:** Iterating IDs 1..N leaks the opening financial position of all 66 groups.
  - **Fix:** Check `$ob->group_id` against the caller's group or facilitation.
  - **Shape-safe:** yes.

- **API-61 — medium — `VslaOnboardingController.php:1027-1095` (`createOrUpdateMember`) and `:547-604`**
  - **What's wrong (hijack):** An existing user is matched only by `phone_number`. Their `group_id` is silently moved to the caller's group, with no check that they already belong to another group. Their financial history stays behind in the old group.
  - **What's wrong (wrong credentials):** For existing users the generated password is never saved, but it is still emailed as their credentials (lines 589-604).
  - **What's wrong (role flags):** Old role flags are not cleared. A user moved as Treasurer keeps `is_group_secretary='Yes'` from a previous group.
  - **Failure scenario:** A chairperson enters a member of another group's phone as Secretary. The member is pulled out of their group and receives an email with a password that does not work.
  - **Fix:** Reject users with a different `group_id`. Either set the password for existing users or don't email one. Reset all three role flags before setting the new one.
  - **Shape-safe:** yes.

- **API-62 — medium — `Api/VslaGroupManifestController.php:319-370, 436-484, 489-554, 700-750` (performance)**
  - **What's wrong:** Heavy N+1 work on the offline manifest endpoint:
    - Per member: `getMemberFinancialSummary` (2 queries) and `getMemberStatistics` (5 queries).
    - Per member again: `getDashboardData` runs one savings query each (lines 734-744).
    - Per meeting: `getMeetingAttendance` and `getMeetingFinancialSummary` (4 queries each, 10 meetings).
    - The active-cycle query is repeated about 8 times.
  - **Failure scenario:** A 30-member group costs roughly 30×8 + 40 + 20 ≈ 300 queries per manifest call. The mobile app calls this on sync.
  - **Fix:** Resolve the cycle once. Pre-aggregate with `GROUP BY user_id` for transactions, loans and attendance, and look values up from maps.
  - **Shape-safe:** yes.

- **API-63 — medium — `Api/VslaFacilitatorController.php:58-87, 124-161, 722-778, 798-837, 906-963` (performance and payload)**
  - **What's wrong:**
    - `myGroups` runs 2 queries plus 3 lazy relation loads per group.
    - `meetingGroups` runs 4 queries per group.
    - `dashboard` runs 1 query per group.
    - `allMembers` lazy-loads `$m->group` per member and is unpaginated.
    - `allMeetings` loads *every* meeting of every group with `SELECT *`, which includes all the JSON blobs (`attendance_data`, `transactions_data`, ...), and is unpaginated.
  - **Failure scenario:** A facilitator with 20 groups and about 2,000 meetings gets a multi-MB response and hundreds of queries.
  - **Fix:** Use `withCount('members')`, eager-load `admin`, `secretary`, `treasurer` and `group`, and fetch active cycles in one `whereIn`. `select()` only the needed columns in `allMeetings`. Optional `limit` or paging params defaulting to the current behaviour.
  - **Shape-safe:** yes, if paging is opt-in.

- **API-64 — medium — `Api/VslaDashboardController.php:92-98, 291-295, 365, 122-123, 256/421/496`**
  - **What's wrong:**
    - `isAdminUser` reads `$user->vsla_position`, which is not a column on `users`. Every officer therefore gets the *member* dashboard, and the admin branch is dead code.
    - If that branch is ever enabled: `getLoanStats` uses `DB::raw('loan_amount + interest_amount - balance')`, but `interest_amount` is not a column, so it would 500. Also `getGroupFinancialSummary` sums `VslaLoan::where('status','active')` with **no group filter** when `cycle_id` is absent (lines 291-295), which is the system-wide loan book.
    - `account_type 'savings'` and `'welfare'` never occur in the data, so `my_savings` and `total_savings` are always 0.
    - `getCycleInfo` reads the non-existent `vsla_cycle_start_date`, so dates are always null. It also accepts a `cycle_id` from another group.
  - **Failure scenario:** Chairpersons see member menus. Fixing only the role check would expose other groups' loan totals and crash.
  - **Fix:** Derive the role from `is_group_admin/secretary/treasurer` (or `admin_id`). Add `whereIn('cycle_id', group cycles)`. Drop `interest_amount` (use `total_amount_due - balance`). Use `start_date`/`end_date`. Check the cycle against the group. Map savings to `share`.
  - **Shape-safe:** yes. Note that the admin payload's keys differ from the member payload's; the client already handles both `user_role` values.

- **API-65 — medium — `Api/AttendanceController.php:148-159`, `:72-87`, `routes/api.php:368-369`**
  - **What's wrong:**
    - `show` lets any officer (anyone with `is_group_*='Yes'`) read any attendance record of any group, including member phone numbers. There is no `meeting.group_id` check.
    - `sort_by` and `sort_order` are unvalidated, and there is no try/catch. An invalid direction throws `InvalidArgumentException`, and an unknown column throws a `QueryException`; both surface as 500, with SQL text when debug is on.
    - `index` is unpaginated.
    - `/{id}` is registered before `/export-pdf`, so `export-pdf` is unreachable: `show('export-pdf')` runs and returns 404.
  - **Fix:** Add a group check in `show`. Whitelist the sort fields and directions. Move the `export-pdf` route above `/{id}`.
  - **Shape-safe:** yes.

### LOW

- **API-66 — low — wrong or non-existent columns that always produce null or empty values (silent data bugs):**
  - `VslaFacilitatorController.php:153-154, 222-223`: `cycle_start_date` and `cycle_end_date` are not `projects` columns. Use `start_date`/`end_date`.
  - `VslaFacilitatorController.php:828, 1061`: `$activeCycle->name` does not exist. Use `cycle_name`.
  - `VslaOnboardingDataController.php:315-316, 480-481`: `'cycle_name' => $cycle->name` is always null. Use `cycle_name`. Also `expected_end_date`, `max_shares_per_member`, `registration_fee` and the group's `meeting_time`/`share_price` are not columns.
  - `VslaConfigurationController.php:91, 98, 105`: `phone_number_1` is not a column, so the officer `phone` is always null. Use `phone_number`.
  - `VslaGroupStatsController.php:83`: `$cycle->name ?? $cycle->project_name` is always `'Cycle'`. Use `cycle_name`.
  - `VslaGroupManifestController.php:845`: `User::find($loan->user_id)` — `vsla_loans` has no `user_id`, so the borrower name is always `'N/A'`. Use `borrower_id`.
  - `VslaGroupManifestController.php:857-866`: the `deadline` and `responsible_user_id` columns don't exist. The SQL error is swallowed by the catch at line 870, so `action_plans_due` is always `[]`. Use `due_date` and `assigned_to_member_id`.
  - `VslaGroupManifestController.php:983`: same `deadline` problem.
  - `VslaGroupManifestController.php:1107`: `user_type === 'admin'`, but the stored value is `'Admin'`.
  - **Fix:** Correct the attribute names.
  - **Shape-safe:** yes. Keys are unchanged; values become populated with the same types (string or date).

- **API-67 — low — `Api/UserLoanController.php:22, 31-33, 71`**
  - **What's wrong:**
    - `show` eager-loads `borrower`, a raw User, so it returns the caller's own password hash and `intro`.
    - Both `index` and `show` eager-load `meeting`, the whole `VslaMeeting` including `attendance_data`, `loans_data` and `transactions_data`. That exposes every other member's attendance and loan figures to a borrower, and bloats the payload.
    - `sort_by` and `sort_order` are passed straight to `orderBy`: a bad direction throws `InvalidArgumentException`, and an unknown column returns the SQL error message.
  - **Fix:** `makeHidden(['password','intro'])` on `borrower`. Use `meeting:id,meeting_number,meeting_date`, or `makeHidden` on the JSON columns. Whitelist the sort fields.
  - **Shape-safe:** mostly. Hiding the meeting JSON columns removes keys from the nested `meeting` object. Check that the app does not read `loan.meeting.loans_data`; the client models I sampled default missing keys.

- **API-68 — low — exception text returned to clients, and catch blocks that hide failures:**
  - **Raw `$e->getMessage()`** (SQL, file paths, column names) appears in responses at:
    - `VslaFacilitatorController` 91, 166, 278, 354, 440, 574, 658, 714, 776, 842, 898, 961, 1076
    - `VslaGroupManagementController` 82, 146, 194, 263, 332, 385
    - `VslaGroupResetController` 164, 222
    - `VslaAgentOnboardingController` 125, 270, 415, 547 (`sms_results.error`)
    - `VslaOnboardingDataController` 66, 160, 249, 342, 514
    - `VslaConfigurationController` 125, 206, 346, 548, 671, 781, 892
    - `VslaGroupManifestController` 80, 134
    - `VslaOpeningBalanceController` 159, 213, 414, 447, 516
    - `SocialFundTransactionController` 95, 148, 229
    - `VslaDashboardController` 85
    - `VslaGroupStatsController` 73
    - `UserLoanController` 59, 108, 166, 210
    - `VslaOnboardingController` 74, 276, 462, 626, 824, 907, 1018, 1130
    - `LoanTransactionController` 181, 248, 322
  - **Silent swallowing** that returns success-shaped empty data hides real SQL errors: `VslaGroupManifestController.php:682-694` and `:870-872`, `VslaFacilitatorController.php:1044-1046`.
  - **Fix:** Log `$e` and return a generic message, keeping the same `message` key. Log inside the swallowing catches.
  - **Shape-safe:** yes. Only message text changes.

- **API-69 — low — transactions opened and then abandoned on early `return`**
  - **Where:** `VslaConfigurationController.php:357-386` (four returns after `DB::beginTransaction()` with no rollback); `VslaOnboardingController.php:543, 715, 722`; `VslaAgentOnboardingController.php:357-359` (after a write).
  - **Effect:** Today the uncommitted work is rolled back only because PHP-FPM closes the connection. Under queue or Octane-style reuse, the open transaction would leak into later work, and the line-357 case would also carry the deactivate `UPDATE` along with it.
  - **Fix:** Validate before `beginTransaction`, or `DB::rollBack()` before each return, or use the `DB::transaction()` closure.
  - **Shape-safe:** yes.

- **API-70 — low — weak duplicate detection on phone numbers**
  - **Where:** `VslaAgentOnboardingController.php:175-186` and `VslaFacilitatorController.php:629-639`.
  - **What's wrong:** The agent controller compares *raw* phone strings, so `+256771234567` and `0771234567` pass the "different officers" check. Both then resolve to the same user, who ends up as both secretary and treasurer (`admin_id`, `secretary_id` and `treasurer_id` can point to one person, holding only the last role's flags). The facilitator's `updateMember` enforces phone uniqueness only within the group and then also sets `username=phone`. That allows system-wide duplicates, and since login and `findUserByPhone` take `first()`, the wrong account can be picked.
  - **Fix:** Compare normalised numbers, and check uniqueness globally.
  - **Shape-safe:** yes.

- **API-71 — low — `VslaAgentOnboardingController.php:117-121`, `:406-411`, `:561-566`; `VslaOnboardingController.php:453-458`, `:814-817`, `:888-893` — raw `FfsGroup` (and own `User`) objects in responses**
  - **What's wrong:** Per the systemic note, each raw `group` object carries the facilitator's password hash and `intro`. In the agent flow the facilitator is the caller; in self-onboarding the facilitator equals the chairperson. `cleanUser()` (lines 561-566) returns officer `toArray()` including hashes, and that goes to the facilitator. This is lower severity than API-42 only because the recipient is usually the account owner or the creator.
  - **Fix:** Same as API-42 (hide `password` and `intro`; strip the `facilitator` relation).
  - **Shape-safe:** yes, with the client defaulting missing keys.

---

## Section 3 – FFS training, AESA, farms, advisory, members, groups, locations, KPI, OneSignal

Files read in full: the Api controllers FfsTrainingSession, Aesa, Farm, FarmActivity, Enterprise, ProductionProtocol, Advisory, FarmerQuestion, Kpi, Manifest, Weather, Review, OneSignalMobile and OneSignalTest, plus MemberController, FfsGroupController and LocationController. Route protection was checked in routes/api.php. Database facts come from the local `fao_ffs_mis` database (read-only queries).

Context:
- Admin-panel accounts are rows in the same `users` table (`config/admin.php:179`).
- The User model has no `$hidden`, so any raw User in a response includes `password`, `remember_token` and `intro`.
- In the "Fix" lines, "hide secret fields" means `makeHidden(['password','remember_token','intro'])`, or adding `$hidden` to User.

---

### Critical

- **API-72 – critical – `app/Http/Controllers/MemberController.php:558-661` (`PUT /api/members/{id}`)**
  - **Problem:** The update runs on any user id. There is no ownership, group or role check. It can change password, phone/username and status. The response returns the raw User model (line 660).
  - **Impact:** Any logged-in user can take over another user's account, including admin-panel accounts. The response also leaks credential fields.
  - **Fix:** Only allow updates by the member themselves, an officer of the member's group, the group's facilitator, or an admin. Only the member themselves or an admin may change a password. Refuse to edit Admin-type users. Hide secret fields in the response.
  - **Shape-safe:** yes. Only the secret fields disappear from `data`.

- **API-73 – critical – `MemberController.php:643-648`; validator at 570-584**
  - **Problem:** The avatar upload has no validation rule. The file is saved under its client-supplied name and extension, in a public folder that the web server serves directly.
  - **Impact:** Any file type can be placed in the public web root. That can lead to server-side code execution.
  - **Fix:** Add `nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120` and generate the filename on the server. `Utils::uploadMedia` with an extension whitelist already does this. Also turn off script execution under `public/storage`.
  - **Shape-safe:** yes.

- **API-74 – critical – `MemberController.php:792-821` (`POST /api/members/sync`, action=update)**
  - **Problem:** It loads any user by id and copies every key in `data` onto the model, with only a three-key blacklist. Each result also returns the full User model (lines 790 and 820).
  - **Impact:** Any logged-in user can change another user's role, group, implementing partner and other sensitive columns, including reset/credential fields. Password hashes leak in the response.
  - **Fix:** Use a whitelist of profile fields. Apply the same authorization as API-72. Hide secret fields in `member`.
  - **Shape-safe:** yes.

- **API-75 – critical – `FfsGroupController.php:21` (index) and `:160` (show); routes api.php:401 and 405 are public**
  - **Problem:** Both endpoints eager-load `facilitator` (a User) and return raw models. They need no authentication. The index is unscoped when there is no token: all 649 groups, 510 of them with a facilitator.
  - **Impact:** Facilitators' password hashes, remember tokens and reset codes are exposed to anyone.
  - **Fix:** Hide secret fields on User, or limit the eager load to safe columns (`facilitator:id,name,phone_number`). Consider requiring auth on the index.
  - **Shape-safe:** yes. Keys inside `facilitator` that the app uses (id/name) stay.

- **API-76 – critical – `AesaController.php:34-57` (resolveUserTier) and `:84-97` (canAccessSession)**
  - **Problem:**
    - Any user with an `ip_id` who is not a facilitator gets the "ip_manager" tier; on the local database that is 7,458 ordinary members.
    - Users with no IP get the "super_admin" tier.
    - No role check is done.
  - **Impact:** Ordinary members can list, view, edit, delete, submit and export every AESA session in their IP. The 6 users with no IP can do this for all sessions.
  - **Fix:** Decide the IP-manager and super-admin tiers from an explicit role (admin roles or `user_type`). Anyone else should fall back to "own sessions only".
  - **Shape-safe:** yes.

### High

- **API-77 – high – `Api/FfsTrainingSessionController.php`: 694 (expectedMembers), 993 (participants), 1033 (syncParticipants), 1101 (removeParticipant), 1139 (resolutions), 1185 (storeResolution), 1244 (updateResolution), 1309 (destroyResolution)**
  - **Problem:** None of these call `userCanAccessSession`.
  - **Impact:** Any logged-in user can:
    - read and overwrite attendance on any session, which feeds actual_participants and the KPIs
    - delete participants
    - create, edit or delete GAP resolutions
    - read members' phone numbers
  - **Fix:** Load the session, call `userCanAccessSession` and return 403 on failure, as `show`/`update` already do.
  - **Shape-safe:** yes.

- **API-78 – high – `FfsTrainingSessionController.php:1233`, `:1298`**
  - **Problem:** `storeResolution`/`updateResolution` return `$resolution->load('responsiblePerson')`, which serialises a raw User.
  - **Impact:** The responsible person's credential fields leak.
  - **Fix:** Hide secret fields on User, or load `responsiblePerson:id,name`.
  - **Shape-safe:** yes.

- **API-79 – high – `FfsTrainingSessionController.php:33-53`; used at 415, 574, 742, 788, 826**
  - **Problem:** `userCanAccessSession` gives full write access (update, delete, submit, unsubmit, wizard) to every member of a target group, not just facilitators and the creator.
  - **Impact:** Any group member can edit, reassign, delete, or submit/unsubmit a session report.
  - **Fix:** Split the checks. Read access can stay as it is. Write access should be limited to the facilitator, co-facilitator, creator or an admin.
  - **Shape-safe:** yes.

- **API-80 – high – `AesaController.php`: 117, 184-195, 289, 362, 434, 465, 483, 557, 592, 678, 1026, 1081, 1098, 1141**
  - **Problem:** AESA responses serialise raw User relations (`facilitator`, `createdBy`, `observations.owner`, `cropObservations.farmer`). The `owner_display` accessor also lazy-loads `owner`.
  - **Impact:** Credential fields of facilitators, creators and farmers leak to the mobile client.
  - **Fix:** Hide secret fields on User, or select only safe columns in the eager loads.
  - **Shape-safe:** yes.

- **API-81 – high – `MemberController.php:381-445` (show), `:678-708` (destroy), `:452-499` (sendCredentials)**
  - **Problem:** None of these check whether the caller may act on the target user.
  - **Impact:** Any logged-in user can read any user's personal data (national ID, phone, DOB, balances) and deactivate any account. `sendCredentials` returns code 1 ("sent") even though the SMS call is commented out, and it writes the default password into the log.
  - **Fix:** Apply the same group/officer/facilitator/admin check as API-72. Stop logging credentials. Return code 0 or an honest message while SMS is disabled.
  - **Shape-safe:** yes.

- **API-82 – high – `MemberController.php:222-277` (index)**
  - **Problem:**
    - If the caller has no group and facilitates no groups, no filter is applied, so every user in the system is returned (about 7.4k).
    - A caller who is not a facilitator can pass any `group_id`.
    - The GET also writes `group_id` back to the caller's user row (lines 242 and 251).
  - **Impact:** Bulk leak of personal data and balances; a read request with side effects.
  - **Fix:** If no group can be resolved, return an empty list. Only allow `group_id` values in the caller's own group or facilitated groups (admins excepted). Move the self-repair write out of the GET.
  - **Shape-safe:** yes.

- **API-83 – high – `FfsGroupController.php:186-189` (getFormOptions; route api.php:402 is public)**
  - **Problem:** It returns `id`, `name` and `phone_number` for every user with `status=1` or `user_type=Admin` (about 1,854 rows), with no authentication.
  - **Impact:** Public bulk leak of names and phone numbers.
  - **Fix:** Require auth, and limit the list to actual facilitators in the caller's IP.
  - **Shape-safe:** yes. The keys stay; fewer rows come back.

- **API-84 – high – `LocationController.php:45-51`, `:75-81`; `FfsGroupController.php:241-244`, `:265-268`**
  - **Problem:** These queries filter and select `locations.parent_id`, which does not exist on the database. The local DB returns MySQL error 1054 "Unknown column 'parent_id'". The create migration is skipped when the table already exists. The type literals `'Subcounty'` and `'Parish'` also don't match the stored `'Sub-county'`.
  - **Impact:** These endpoints always return HTTP 500. The app calls them from `ffs_group_registration_screen.dart:103/125` and `location_service.dart:83`.
  - **Fix:** Filter on the existing `parent` column. If `parent_id` is still selected, alias it as `parent as parent_id` so the response keys stay the same.
  - **Shape-safe:** yes, if the `parent_id` key is kept via the alias.

- **API-85 – high – `EnterpriseController.php:104-223`; `ProductionProtocolController.php:155-322`**
  - **Problem:** Create, update and delete only require the token middleware. There is no admin or role check.
  - **Impact:** Any logged-in member can change or delete global reference data. That affects how every new farm generates its activities (Farm::created → generateActivitiesFromProtocols).
  - **Fix:** Limit writes to admin users.
  - **Shape-safe:** yes.

- **API-86 – high – `MemberController.php:715-846` (sync); app side `member_offline_service.dart:403-407`**
  - **Problem:**
    - Failures are caught per item (823-828), but the endpoint still returns `code: 1` "Sync completed".
    - The app treats code 1 as success and deletes its entire offline queue.
    - Members created through sync get no `group_id`/`ip_id`, unlike `store`, which inherits them from the registrar.
    - The app reads `data.synced`/`data.results`, which the server never returns.
  - **Impact:** Members registered offline are silently lost when any item fails. Members that do sync are left without a group and don't appear in group lists.
  - **Fix:**
    - Set `group_id`/`ip_id` from the registrar, as `store` does.
    - Make creation idempotent per `temp_id`.
    - Return code 0 when any item failed.
    - Add a `synced` count and a `results` array alongside the existing keys.
  - **Shape-safe:** yes. Only keys are added.

### Medium

- **API-87 – medium – `KpiController.php:75` (ipScorecard), `:109` (facilitatorTrend), `:36-46` (facilitatorScorecard)**
  - **Problem:**
    - `ipScorecard` accepts any `ip_id`, although its comment says super admin only.
    - `facilitatorTrend` has no check at all.
    - In `facilitatorScorecard`, any user whose `ip_id` matches the facilitator's groups passes the "IP manager" test. A user with a null `ip_id` matches groups with a null `ip_id`.
    - `Admin::user()` is always null in API context.
  - **Impact:** Any member can read KPI scorecards for other IPs and facilitators.
  - **Fix:** Check role before honouring `ip_id`/`facilitator_id`. Treat a null `ip_id` as no access.
  - **Shape-safe:** yes.

- **API-88 – medium – `AesaController.php:541-553`, `:591`, `:1074-1077`, `:1140`, `:283`**
  - **Problem:** Observations are created and updated from `$request->all()`/`$request->except()`. The models' `$fillable` includes `ip_id`, `created_by_id` and `owner_id`.
  - **Impact:** Clients can reassign an observation's IP or creator, which breaks IP scoping and attribution.
  - **Fix:** Use an explicit `only([...])` list of observation fields. Set `ip_id`/`created_by_id` on the server.
  - **Shape-safe:** yes.

- **API-89 – medium – `AesaController.php:330`, `:337-348` (update)**
  - **Problem:** PUT accepts `status` = `submitted` or `reviewed`.
  - **Impact:** It skips `submit()`'s "must have observations" rule and lets a facilitator mark their own session as reviewed.
  - **Fix:** Remove `status` from the update input; only allow transitions through submit or an admin review.
  - **Shape-safe:** yes.

- **API-90 – medium – `FfsTrainingSessionController.php:817-983` (submitWizardReport)**
  - **Problem:**
    - It doesn't check whether the report is already submitted.
    - Resolutions are inserted on every call, so a retry or double tap duplicates them.
    - Resolution text allows `max:500`, but the column is `varchar(255)` and strict mode is on, so long text throws.
  - **Impact:** A submitted report can be changed afterwards, GAP resolutions get duplicated, and one long resolution makes the whole wizard (attendance included) fail with a 500.
  - **Fix:** Reject the call when `report_status=submitted`. Deduplicate resolutions (e.g. by session + text, or a client id). Change the rule to `max:255`.
  - **Shape-safe:** yes.

- **API-91 – medium – `FfsTrainingSessionController.php:420`**
  - **Problem:** The "submitted report is locked" check is skipped whenever the request contains a `report_status` key, even though `update` never processes that key.
  - **Impact:** A submitted report can be edited without unsubmitting it.
  - **Fix:** Remove the `!$request->has('report_status')` condition.
  - **Shape-safe:** yes.

- **API-92 – medium – performance and pagination**
  - **Problem:**
    - `FfsTrainingSessionController.php:223` (index) and `:660-674` (pendingReports): the `participants_count`/`resolutions_count` accessors (`FfsTrainingSession.php:243-251`) override `withCount` and run 2 queries per row. pendingReports also lazy-loads group, facilitator and co-facilitator.
    - `AesaController.php:117-168` (index): the appends lazy-load district, subCounty and village and run 2 counts, about 5 queries per row.
    - `AesaController.php:788-886` (export): lazy-loads owner and farmer per observation.
    - None of these paginate by default. The public `FfsGroupController` index is also unpaginated.
  - **Impact:** Slow responses and heavy database load as data grows.
  - **Fix:** Have the count accessors use `$this->attributes['x_count']` when it is present, and eager-load the relations the appends use. Keep the default (unpaginated) behaviour so the app's parsing doesn't break.
  - **Shape-safe:** yes.

- **API-93 – medium – `FarmController.php:37`, `:95`, `:115`, `:184`; `Farm.php:110-113`; `FarmActivityController` responses that include `farm`**
  - **Problem:** The appended `farmer_text` lazy-loads `user`. That relation is then serialised, so every farm JSON contains a full `user` object.
  - **Impact:** The caller's own credential fields are returned on every farm and activity response.
  - **Fix:** Hide secret fields on User. Optionally `makeHidden('user')` on Farm.
  - **Shape-safe:** mostly. `user` is currently present; removing it entirely would change the shape, so prefer hiding the secret fields only.

- **API-94 – medium – `FarmController.php:82-93` together with `Farm.php:220-238`**
  - **Problem:** The farm insert and the activity generation in the `created` hook are not in a transaction. `exists:enterprises,id` also accepts soft-deleted enterprises, and then `$this->enterprise->productionProtocols` dereferences null.
  - **Impact:** The farm row is saved, the request returns a 500, the user retries and gets duplicate farms with partial or no activities.
  - **Fix:** Wrap the work in `DB::transaction`. Use a rule like `Rule::exists('enterprises','id')->whereNull('deleted_at')`. Generate activities only from active protocols.
  - **Shape-safe:** yes.

- **API-95 – medium – the app calls `api/onesignal/register-device` (`OneSignalService.dart:389`, `ApiService.dart:522-524`), but no route exists**
  - **Problem:** `OneSignalMobileController` and `OneSignalTestController` are not routed anywhere. The public web page `/onesignal-test` (web.php:785) calls `/api/onesignal/*` endpoints that don't exist.
  - **Impact:** Device registration always gets a 404, so targeted pushes can't reach users.
  - **Fix:** Route the mobile controller under `EnsureTokenIsValid`. Take `user_id` from the authenticated user, not the body (currently `OneSignalMobileController.php:19-37`). Remove the test page or protect it.
  - **Shape-safe:** yes.

- **API-96 – medium – `AdvisoryController.php:248` (searchPosts)**
  - **Problem:** `$request->query` is Symfony's query-string object, not the `query` input. Turning it into a string in `scopeSearch` throws an `\Error`, which `catch (\Exception)` doesn't catch.
  - **Impact:** `/advisory/posts/search` always returns a 500.
  - **Fix:** Use `$request->input('query')`.
  - **Shape-safe:** yes.

- **API-97 – medium – `AdvisoryController.php:210-227` (route api.php:489 is public); `FarmerQuestionController.php:322-361`**
  - **Problem:** Liking a post needs no authentication and has no per-user limit. Question and answer likes are authenticated but also unlimited. The increment itself is atomic.
  - **Impact:** Like counts can be inflated without limit.
  - **Fix:** Require auth and record likes per user in a pivot table (unique user + target); only increment on the first like.
  - **Shape-safe:** yes.

- **API-98 – medium – `WeatherController.php:37-38`, `:51-80` (route api.php:53 is public)**
  - **Problem:**
    - It is an unauthenticated, uncached proxy to the paid Tomorrow.io API.
    - On failure it returns the upstream body in an `error` key and reuses the upstream HTTP status (401 or 429 would reach the app).
    - It reads its key with `env()`, which returns null once config is cached.
  - **Impact:** API quota can be exhausted, upstream details leak, and the endpoint breaks under `config:cache`.
  - **Fix:** Move the key into `config/services.php`. Cache per rounded lat/lon for about 30 minutes. Add throttle middleware. Return a generic 502 without the upstream body.
  - **Shape-safe:** yes. The `error` key can stay but should be generic.

### Low

- **API-99 – low – `FarmActivityController.php:179-184` together with `FarmActivity::markAsDone`**
  - **Problem:** Completing an activity without a photo sets `photo = null`.
  - **Impact:** A photo uploaded earlier through `/upload-photo` is wiped.
  - **Fix:** Only overwrite `photo` when a new one is uploaded.
  - **Shape-safe:** yes.

- **API-100 – low – `FarmActivityController.php:92-102`**
  - **Problem:** Creating a custom activity doesn't update the farm's activity counts. The `saved` hook checks `wasChanged('status')`, which is false on insert.
  - **Impact:** `total_activities_count` is stale and `progress_percentage` is overstated.
  - **Fix:** Call `$farm->updateActivityCounts()` after saving.
  - **Shape-safe:** yes.

- **API-101 – low – exception messages and unvalidated sorting**
  - **Problem:**
    - Most catch blocks return `$e->getMessage()`; examples: `FfsTrainingSessionController` (all), `AesaController` (all), Enterprise/Protocol, Member store/update/sync (including per-item errors), Advisory, Manifest.
    - `AesaController.php:162-164` passes the user-supplied sort column and direction to `orderBy`.
    - `EnterpriseController.php:44-46` and `ProductionProtocolController.php:48-50` do the same with the column.
    - `MemberController.php:330-340` doesn't validate direction and has no try/catch.
  - **Impact:** SQL and internal details leak; a bad sort parameter causes a 500.
  - **Fix:** Log the detail and return a generic message. Whitelist sort columns and map direction to asc/desc.
  - **Shape-safe:** yes. Only message text changes.

- **API-102 – low – `AesaController.php:967-975` (exportSessionPdf)**
  - **Problem:** It uses a different access rule from `canAccessSession`: facilitators are checked by ffs_groups.facilitator_id, and IP managers are skipped when the session's `ip_id` is null.
  - **Impact:** A facilitator can be denied the PDF of their own session, and the rules are inconsistent.
  - **Fix:** Use `canAccessSession`.
  - **Shape-safe:** yes.

- **API-103 – low – `LocationController.php:18-21` (getDistricts)**
  - **Problem:** `orWhere('parent','<',1)` also returns Region and Country rows (Central Region, Karamoja Region, and a Country row named "Kampla, Uganda").
  - **Impact:** Non-districts appear in district dropdowns.
  - **Fix:** Filter on `type='District'` only.
  - **Shape-safe:** yes.

- **API-104 – low – `FarmerQuestionController.php:153-191`, `:230-291`**
  - **Problem:** The controller accepts audio extensions mp4/mpeg and video flv, but `Utils::uploadMedia`'s whitelist rejects them and silently returns null. The success message says the question needs approval, but `farmer_questions` has no approval column and the list shows everything immediately.
  - **Impact:** Uploads are silently dropped, and the moderation message is misleading.
  - **Fix:** Use the same extension lists in both places, and return an error when the upload returns null.
  - **Shape-safe:** yes.

- **API-105 – low – N+1 in advisory and Q&A formatting (`AdvisoryController.php:282` category lazy-load per post; `FarmerQuestionController.php:392` `User::find` per answer; `FarmerQuestion::getAnswersCountAttribute` per question)**
  - **Problem:** One extra query per row; `per_page` is also not validated in `getPosts`/`getQuestions`/`getMyQuestions`.
  - **Impact:** Slow lists, and a client can request very large pages.
  - **Fix:** Eager-load or use withCount, and cap `per_page` at 100.
  - **Shape-safe:** yes.

- **API-106 – low – `ReviewController` (routes api.php:444-450 use `auth:sanctum`)**
  - **Problem:** The app authenticates with JWT or `User-Id`, so the sanctum guard never authenticates these routes. This is a legacy e-commerce feature.
  - **Impact:** The review write endpoints are effectively unusable.
  - **Fix:** Switch to `EnsureTokenIsValid`, or remove the routes if they are unused.
  - **Shape-safe:** yes.

---

## Section 4 – Payments, ledger, investments, insurance, withdrawals, dashboard (unauthenticated legacy routes)

Scope: the 18 controllers listed in the brief, plus `VerifyPesapalWebhook`, the closures in `routes/api.php`, and the model hooks they trigger (`Disbursement`, `AccountTransaction`, `UniversalPayment`, `Transaction`, `WithdrawRequest`, `Project`, `PesapalService`).
The audit was read-only. Data figures come from SELECT/DESCRIBE queries against the local `fao_ffs_mis` DB: 7,538 users, 8,798 account_transactions, 229 projects (224 of them VSLA cycles), 1 universal_payment, and the insurance, medical and transactions tables are empty.

Background facts these findings rely on:
- None of the route groups below runs `EnsureTokenIsValid`. Where a controller does any identity check, it uses `Utils::get_user_id()` (`app/Models/Utils.php:463-474`). That function returns `$_SERVER['HTTP_USER_ID']`, then `$_GET['user_id']`, then `$_POST['user_id']`, with no token check at all.
- Under PHP 8, "Undefined array key" is an E_WARNING, which Laravel 8.83 turns into an `ErrorException`. Deprecations are only logged.
- The `account_transactions` table holds the VSLA ledger: owner_type `group`/`member`; account_type `share`, `loan`, `loan_repayment`, `social_fund`, `fine`; source `deposit`/`disbursement`/`withdrawal`.

---

### CRITICAL

- **API-107 | critical | `app/Http/Controllers/InvestmentTransactionController.php:151-227` + `app/Http/Controllers/DisbursementController.php:87-197`: anonymous chain that creates withdrawable money**
  - **What is wrong:** Neither controller checks auth. `investment-transactions` POST accepts any `amount` (`min:0`) with `type=income`. `disbursements` POST takes `user_id` from the body (lines 94 and 140), uses it as `created_by_id`, and credits AccountTransactions to every investor.
  - **Failure scenario:**
    1. `POST /api/investment-transactions {project_id:1, amount:100000000, type:"income", description:"x", transaction_date:"2026-10-01", user_id:1}` creates a `project_profit` income row, so the project now has 100M "available funds".
    2. `POST /api/disbursements {project_id:1, amount:100000000, disbursement_date:"2026-10-01", user_id:1}` credits each investor's `account_transactions` (2× the amount, see API-109).
    3. The investor's withdrawable balance (`User::calculateAccountBalance`) passes the check in `POST /api/withdraw-requests`.
    4. Bonus: `DELETE`/`PUT /api/investment-transactions/{id}` and `DELETE /api/disbursements/{id}` let the same caller erase the trail.
  - **Fix:** Put both groups behind `EnsureTokenIsValid`. Add an explicit admin check (`$u->user_type === 'Admin'` or an admin role) in store/update/destroy. Take `created_by_id` from the authenticated user, never from the body.
  - **Mobile app:** It uses these from its admin screens (`disbursement_api.dart`, `investment_transaction_api.dart`), always via `http_get`/`http_post` with auth headers.
  - **shape-safe: yes**

- **API-108 | critical | `app/Http/Controllers/AccountTransactionController.php:213-229` (destroy), `:17-108` (index), `:195-208` (show): anonymous deletion and listing of the VSLA ledger**
  - **What is wrong:** `destroy` has no auth. Its only guard is `source !== 'disbursement'`. VSLA share, social-fund, fine and loan-repayment rows all have `source='deposit'` (about 4,800 rows, both `group` and `member` mirrors), so every one of them can be deleted.
  - **Failure scenario:**
    - `POST /api/account-transactions/123 {_method:"DELETE"}` (or `DELETE /api/account-transactions/123`) soft-deletes a member's share deposit.
    - The `deleted` hook (`AccountTransaction.php:56-58`) recomputes `users.balance`, so the member's savings drop.
    - The contra entry (`contra_entry_id`) is left orphaned, which unbalances the double-entry ledger.
    - Looping over IDs wipes the savings history of every group.
    - `GET /api/account-transactions?per_page=10000` dumps all 8,798 ledger rows with member names. `user_id`, `source` and `search` filters work without auth.
  - **Fix:**
    - Add auth and an admin check.
    - In `destroy`, refuse any row where `owner_type IS NOT NULL` (VSLA ledger rows) or where `meeting_id`/`cycle_id` is set.
    - Scope `index`/`show` to the caller unless admin.
  - **shape-safe: yes**

- **API-109 | critical | `app/Http/Controllers/DisbursementController.php:143-178` vs `app/Models/Disbursement.php:43-66`: every disbursement is credited twice**
  - **What is wrong:** `Disbursement::create()` (line 135) already fires the model's `created` hook. That hook creates the `returns_distribution` expense ProjectTransaction (Disbursement.php:45-53), runs `distributeToInvestors()` (one AccountTransaction per investor, weighted by share count, lines 134-170), and calls `recalculateFromTransactions()`. The controller then does all of it again: a second expense ProjectTransaction at lines 144-152, and a second AccountTransaction per investor weighted by `total_amount_paid` at lines 156-170.
  - **Failure scenario:**
    - A 1,000,000 UGX disbursement credits investors about 2,000,000 in total and books 2,000,000 of expenses.
    - `Project::recalculateFromTransactions` (Project.php:259-274) adds the `returns_distribution` rows **and** `SUM(disbursements.amount)` to `total_returns`, so it reports 3,000,000.
    - The response's `account_transactions` array lists only the second set, so the admin never sees the duplication.
    - Separately, the balance check at lines 105-111 runs before `DB::beginTransaction()` (line 131) and without a lock, so two concurrent POSTs can both pass and over-disburse.
  - **Fix:**
    - Delete the manual creation at lines 143-178.
    - After the commit, build `account_transactions` from `$disbursement->accountTransactions`, keeping the same keys (`user_id`, `amount`, `formatted_amount`, `proportion`).
    - Run the availability check inside the transaction after `Project::lockForUpdate()->find()`.
    - In `recalculateFromTransactions`, drop the extra `Disbursement::sum` (or stop creating the `returns_distribution` ProjectTransaction).
  - **shape-safe: yes** (same keys)

- **API-110 | critical | `app/Http/Controllers/UniversalPaymentController.php:27-40, 62-84` + `app/Models/UniversalPayment.php:117-119, 599-602, 719-800, 473-505`: the client chooses the amount and quantity paid for shares, membership and orders**
  - **What is wrong:**
    - `payment_items.*.amount` is client-supplied (`min:0`), and the payment total is just their sum (UniversalPayment.php:117-119).
    - For shares, the share count is `$this->number_of_shares ?? $item['quantity'] ?? round(amount/share_price)` (line 600). The client's `quantity` wins, and it is never checked against the amount.
    - Membership grants a LIFE membership for any `item['amount']`.
    - `processOrder` marks any order id `PAID` and `PROCESSING`, with no check on the amount or on who owns the order.
    - `user_id` also comes from the body (line 30), so a payment can be created "for" anyone.
  - **Failure scenario:**
    1. `POST /api/universal-payments/initialize {user_id:<me>, payment_type:"project_share_purchase", payment_gateway:"pesapal", payment_items:[{type:"project_share", id:1, amount:500, quantity:39}]}` creates a Pesapal order for 500 UGX.
    2. The attacker pays 500 UGX.
    3. `GET /api/universal-payments/status/{id}` sees COMPLETED from Pesapal and issues 39 shares (39 × 15,000 = 585,000 UGX).
    4. The same trick with `type:"membership", amount:1` gives a lifetime membership, and `type:"order", id:<any order>` marks someone else's order paid.
  - **Fix:** Compute every item's amount on the server:
    - project share: `share_price × quantity`, then check the quantity is still available;
    - membership: the configured fee;
    - order: `order.total`, and the order must belong to `user_id`.
    - Reject the request with the existing 422 shape when the client's amount differs.
    - Take `user_id` from the authenticated user.
  - **shape-safe: yes**

- **API-111 | critical | raw `User` model serialised (password hash, `remember_token`, `intro` = reset OTP) to anonymous or IDOR callers**
  - **What is wrong:** Eager-loaded user relations have no column list. Anonymous routes:
    - `UniversalPaymentController.php:580` (index, `with('user')`, `per_page` unbounded) and `:622` (show)
    - `InvestmentController.php:372-376, 400` (`GET /api/investments/shares/{id}` → `share.investor`; IDs are sequential)
    - `TransactionController.php:20, 113, 130, 221` (`user`, `creator`, `updater`)
    - `InsuranceSubscriptionController.php:18, 143, 168, 231`
    - `InsuranceSubscriptionPaymentController.php:19, 100, 162, 213, 230`
    - `MedicalServiceRequestController.php:21, 124, 141, 164, 227, 289` (`user` + `reviewer`)

    Authenticated, so reachable by spoofing `User-Id`, and IDOR:
    - `ProjectShareController.php:172` (`investor`)
    - `ProjectTransactionController.php:98, 119, 176` (`creator`)
    - `ProjectController.php:197` (`$project->load('createdBy')` with no select, unlike its other loads)
  - **Failure scenario:**
    - `GET /api/universal-payments?per_page=100000` returns every paying user's bcrypt hash and current `intro` OTP. That allows offline cracking, or a password reset by replaying the OTP.
    - `GET /api/investments/shares/1..N` returns each investor's full row.
    - The same index endpoint, filtered with `?user_id=X`, also lists X's `customer_phone`, `customer_email` and payment history.
  - **Fix:** Add `protected $hidden = ['password','remember_token','intro']` to `User`, or call `->makeHidden([...])` on these relations. The mobile app does not depend on these fields: `VendorModel.dart:101-118` reads them with `''` defaults. Also add auth to the anonymous routes.
  - **shape-safe: yes** (only secret keys disappear; every other key stays)

### HIGH

- **API-112 | high | `app/Http/Controllers/UniversalPaymentController.php:164-183` + `app/Services/PesapalService.php:151-197` + `app/Http/Controllers/PesapalController.php:180-246`: universal-payment IPNs go to the wrong handler and mark an unrelated e-commerce Order paid**
  - **What is wrong:**
    - `initializePesapalPayment` calls `PesapalApiClient::initializePayment`. That registers the IPN at `env('PESAPAL_IPN_URL')` (PesapalApiClient.php:29, 226), which defaults to `/api/pesapal/ipn`, i.e. `PesapalController::ipn`, not `/api/universal-payments/ipn`.
    - It also stores a `PesapalTransaction` with `'order_id' => $payment->id`, which is the UniversalPayment id.
    - On IPN, `PesapalService::updateTransactionStatus` runs `Order::find($transaction->order_id)` (line 182) and sets `payment_status=COMPLETED` and `order_state=confirmed` (186-190) on the e-commerce **Order** whose id happens to equal the UniversalPayment id.
    - The UniversalPayment itself is never updated by the IPN.
  - **Failure scenario:**
    - A user pays for shares or membership, then closes the app before it polls `/universal-payments/status/{id}`. The money is captured, but `items_processed` stays false and no shares or membership are granted.
    - Meanwhile Order #<same id> flips to COMPLETED.
    - Separately, the local `.env` sets `PESAPAL_IPN_URL` to a different domain (`blitxpress.com`). Check the production value.
  - **Fix:**
    - In `updateTransactionStatus`, branch on `merchant_reference` prefix `PAYMENT_`, or on a type column: look up `UniversalPayment::where('pesapal_order_tracking_id', …)`, call `updatePaymentFromPesapal` and `processPaymentItems`, and skip the Order update.
    - Or pass the universal IPN URL when initialising universal payments.
  - **shape-safe: yes**

- **API-113 | high | `app/Models/UniversalPayment.php:151-175, 575, 730`: processing payment items is not safe under concurrency (duplicate shares or memberships)**
  - **What is wrong:** The "third safeguard" is `$this->refresh()` inside `DB::transaction`. Under InnoDB REPEATABLE READ that is a plain non-locking read. The per-item guards (`ProjectShare::where('payment_id')`, `MembershipPayment::where('payment_reference')`) are also non-locking.
  - **Failure scenario:**
    - The mobile app polls `GET /universal-payments/status/{id}` and also calls `POST /universal-payments/{id}/process` (`UniversalPayment.dart:628`, `UniversalPaymentDetailsScreen.dart:99`). A callback or IPN can land at the same moment.
    - Two requests both read `items_processed=0` and both create a ProjectShare plus a `share_purchase` ProjectTransaction, so shares are doubled and `shares_sold` is inflated.
    - `/{id}/process` is anonymous, so anyone can fire parallel requests.
  - **Fix:**
    - Inside the transaction, use `$p = self::whereKey($this->id)->lockForUpdate()->first(); if ($p->items_processed) return …`.
    - Add a UNIQUE index on `project_shares.payment_id` and on `membership_payments.payment_reference`.
  - **shape-safe: yes**

- **API-114 | high | `app/Http/Controllers/ProjectShareController.php:130-139` vs `app/Models/UniversalPayment.php:258-276`: shares bought via `initiate-purchase` are never issued**
  - **What is wrong:** `initiatePurchase` builds `payment_items` with keys `item_type`/`item_id`. `processIndividualItem` reads `$item['type']` and `$item['id']`, which throws "Undefined array key 'type'" as an `ErrorException`. The item is recorded as failed, and `processPaymentItems` throws "Some items failed to process". The payment ends up COMPLETED with `items_processed=0`, forever.
  - **Failure scenario:** The mobile app calls `POST /api/projects/shares/initiate-purchase` (`ProjectService.dart:157`). The user pays and gets no shares. Every status poll returns 500 "Failed to check payment status".
  - **Secondary issue:** `available_for_purchase` is just `status==='ongoing'`, which is true for VSLA cycles (`share_price` NULL). That yields a 0-UGX payment.
  - **Fix:** Add `'type' => 'project_share', 'id' => $projectId` to the item, keeping the existing keys. Reject projects with `is_vsla_cycle='Yes'` or `share_price<=0`.
  - **shape-safe: yes** (additive)

- **API-115 | high | `app/Models/User.php:1081-1084` (used by `WithdrawRequestController.php:79, 217`, `WithdrawRequest.php:111-115`, `UserAccountController.php:29, 100, 158`, `DashboardController.php:75, 491`, `AccountTransactionController.php:240`): the withdrawable "account balance" counts the whole VSLA ledger**
  - **What is wrong:** `calculateAccountBalance()` is `SUM(amount)` over **all** of a user's `account_transactions`. That includes `owner_type='group'` mirror rows (4,399 group rows carry a member's `user_id`), plus loans, loan repayments and social fund.
  - **Verified example:** user 1647 has `calculateAccountBalance` = 6,127,000, while `users.balance` (member share) = 240,000. The group rows alone contribute 5,947,000.
  - **Failure scenario:** `POST /api/withdraw-requests {amount:6000000, payment_method:"mobile_money", payment_phone_number:"07…"}` as user 1647 passes the balance check (WithdrawRequestController.php:82). Admin approval re-checks with the same function (`WithdrawRequest::validateBalance`) and passes too. `dashboard/my-account-overview`, `withdraw-requests/balance` and `user-accounts/dashboard`, which the mobile app shows, all display the inflated figure.
  - **Fix:** Scope the wallet balance to wallet rows only: `whereNull('owner_type')` with `source IN (disbursement, deposit, withdrawal)` from Disbursement, withdraw and admin flows. Alternatively keep investment-wallet entries in a separate account_type. Apply the same scope in every place listed above.
  - **shape-safe: yes** (values change, keys and types do not)

- **API-116 | high | `app/Http/Controllers/WithdrawRequestController.php:47-125` (+ `Utils.php:463-474`): withdrawal requests for any user, a payout number the requester picks, and a double-request race**
  - **What is wrong:**
    - The user is `Utils::get_user_id()`. Besides the header (already reported), it also accepts `?user_id=` or a POST `user_id`, so not even a header is needed.
    - The requester sets the payout `payment_phone_number`.
    - The "one pending request" check (93-102) and the insert (105-113) are separate and unlocked.
  - **Failure scenario:**
    - `POST /api/withdraw-requests` with `user_id=<victim>` in the form body, `amount` up to the victim's (inflated, API-115) balance, and the attacker's MoMo number. An admin approving it pays the attacker.
    - Two parallel POSTs create two pending requests, each for the full balance.
    - `WithdrawRequest::approve()` (WithdrawRequest.php:124-185) has no row lock either, so two admins approving at once both pass `validateBalance()`.
  - **Fix:**
    - Resolve the user via `auth('api')->user()` (JWT) and ignore the query or body `user_id`.
    - Wrap check and insert in a transaction with `User::lockForUpdate()->find($id)`.
    - In `approve()`, re-select the request `FOR UPDATE` and re-check `status`.
  - **shape-safe: yes**

- **API-117 | high | `app/Http/Controllers/TransactionController.php:73-122, 158-230, 235-262` + `app/Models/Transaction.php:102-113`: anonymous savings transactions overwrite `users.balance`**
  - **What is wrong:**
    - `store` passes `$request->all()` (line 101) to `Transaction::createTransaction`. The client controls `status:"COMPLETED"`, `transaction_date` and `user_id`.
    - Every create, update or delete then runs `Transaction::updateUserBalance()`, which **sets `users.balance = SUM(transactions.amount)`**. That is the same column `AccountTransaction::updateMemberBalance` maintains for VSLA share savings.
  - **Failure scenario:** `POST /api/transactions {user_id:X, amount:1, type:"DEPOSIT", status:"COMPLETED"}` sets X's VSLA savings balance (`users.balance`) to 1. `DELETE /api/transactions/{id}` sets it to 0. `GET /api/transactions/user/{id}/balance` and `/stats?user_id=` reveal names and balances.
  - **Mobile app:** It does not use these endpoints; no `transactions` root calls exist in the app.
  - **Fix:** Remove the route group, or put it behind auth plus admin. In any case, stop `Transaction` from writing `users.balance`.
  - **shape-safe: yes** (unused by mobile app)

- **API-118 | high | `app/Http/Controllers/ProjectController.php:157-237`: any authenticated user can edit or delete any project, including the 224 VSLA cycles**
  - **What is wrong:** There is no role or ownership check. `projects` holds both investment projects and VSLA cycles (`is_vsla_cycle='Yes'`).
  - **Failure scenario:** With `User-Id: <any id>`, `DELETE /api/projects/<another group's active cycle id>` soft-deletes it, and the group's meetings, loans and shareout lose their cycle. `PUT /api/projects/{id} {share_price:0, status:"completed"}` corrupts a cycle. `store` lets any user create investment projects.
  - **Fix:** Require admin for store/update/destroy. Refuse rows where `is_vsla_cycle='Yes'`, since cycles are managed via the VSLA endpoints and `CycleController`.
  - **shape-safe: yes**

- **API-119 | high | `app/Http/Controllers/MedicalServiceRequestController.php:18-78, 138-180, 185-236, 241-298, 337-354`: anonymous health data and a review bypass**
  - **What is wrong:**
    - Index, show and getByReference expose `symptoms_description`, contact phone and address, and the raw user (API-111) to anyone.
    - `update` (line 222) calls `$serviceRequest->update($request->all())`. `status`, `reviewed_by`, `estimated_cost`, `insurance_coverage` and `patient_payment` are all fillable.
    - `review` and `destroy` are anonymous.
  - **Failure scenario:** `PUT /api/medical-service-requests/5 {status:"approved", insurance_coverage:5000000, reviewed_by:1}` self-approves a request with arbitrary coverage. `GET /api/medical-service-requests?per_page=1000` dumps all medical requests. The table is currently empty, but the mobile app creates and lists these (`MedicalServiceRequestService.dart:25, 73, 131`).
  - **Fix:**
    - Add auth.
    - Scope index and show to the owner, or to admins.
    - In `update`, use `$request->only([...validated user fields...])`.
    - Make `review` and `destroy` admin-only.
  - **shape-safe: yes**

- **API-120 | high | `app/Http/Controllers/UserAccountController.php:93-147, 152-226`: anonymous dump of every user's PII and balance, and a 15k-query N+1**
  - **What is wrong:** `getAllUsersWithBalances` runs `User::select(...)->get()` (all 7,538 users), then 2 queries per user (lines 100-101), about 15,000 queries per call. Search and sort happen in PHP afterwards. `getUserDashboard/{userId}` returns any user's ledger, phone and email.
  - **Failure scenario:** `GET /api/user-accounts/users-list` returns the name, email, phone and balance of every user to an anonymous caller. A few parallel calls saturate MySQL.
  - **Fix:**
    - Make it admin-only.
    - Use a single query: `User::leftJoinSub(AccountTransaction::selectRaw('user_id, SUM(amount) bal, COUNT(*) cnt')->groupBy('user_id'), 'a', …)` with SQL `LIKE` search and pagination.
    - Keep the `users` and `summary` keys.
  - **Mobile app:** used from an admin screen (`account_transaction_api.dart:280`).
  - **shape-safe: yes** (if pagination is added, keep returning the `users` array)

### MEDIUM

- **API-121 | medium | `app/Http/Controllers/InvestmentController.php:23, 199, 282`: anonymous IDOR on any user's portfolio**
  - **What is wrong:** These read `$userId = $request->user_id ?? Auth::id()` on an unauthenticated route group, so `Auth::id()` is null and the client's `user_id` is always used.
  - **Failure scenario:** `GET /api/investments/dashboard?user_id=X` (also `/shares`, `/transactions`) returns X's holdings, amounts and ROI. The mobile app itself sends `investments/transactions?user_id=$userId`.
  - **Fix:** Add `EnsureTokenIsValid` and use the authenticated id. Honour `user_id` only for admins.
  - **shape-safe: yes**

- **API-122 | medium | `app/Http/Controllers/PesapalAdminController.php:71-74, 100-103, 113, 142-145, 184-195, 272`: queries columns that don't exist, so most actions return 500; no admin check**
  - **What is wrong:** DESCRIBE shows `pesapal_transactions` has `order_tracking_id` and `status_code` but no `tracking_id`, `status_reason` or `retry_count`. `pesapal_ipn_logs` has no `response_code`, `response_message` or `ipn_data`.
    - `analytics` filters on `response_code` → SQL error.
    - `transactionDetails` uses `orWhere('tracking_id')` → SQL error.
    - `failedTransactions` uses `groupBy('status_reason')` → SQL error.
    - `retryTransaction` sets `retry_count` → SQL error caught, message leaked. Its `PesapalIpnLog::create` keys are not fillable and are silently dropped.
    - `export` writes an empty "Tracking ID" column.
    - `analytics`, `transactionDetails` and `failedTransactions` have no try/catch, so APP_DEBUG pages are exposed.
    - There is no admin check: any user, or anyone spoofing User-Id, can `GET /api/admin/pesapal/export?period=36500` and download every transaction.
  - **Fix:**
    - Use `order_tracking_id`, `status_code`/`error_message`, and `pesapal_ipn_logs.status`.
    - Drop `retry_count`, or add a migration.
    - Add a `user_type==='Admin'` guard.
  - **Mobile app:** does not use these endpoints.
  - **shape-safe: yes**

- **API-123 | medium | `routes/api.php:944` vs `:950`: `GET /api/projects/transactions` is caught by `GET /projects/{id}`**
  - **What is wrong:** `/{id}` is registered before `/transactions`, so the request reaches `ProjectController::show('transactions')`, which returns 404 "Project not found". The mobile app calls `projects/transactions` (`ProjectService.dart:193`), so the project transactions list is always empty or errors.
  - **Once fixed:** `ProjectTransactionController::index` (lines 19-53) is an unpaginated `->get()` with a raw `creator` user (API-111).
  - **Fix:** Add `->where('id', '[0-9]+')` to the `/{id}` routes, or move `/transactions*` and `/shares*` above them. Add a select or makeHidden on `creator`.
  - **shape-safe: yes** (restores the intended response)

- **API-124 | medium | `app/Http/Middleware/VerifyPesapalWebhook.php:61-100`: the `verify.pesapal.webhook` check can be spoofed and may also drop real IPNs**
  - **What is wrong:** It accepts any request whose User-Agent contains `pesapal`, `curl` or `postman`. The IP allowlist contains only localhost. In production (`PESAPAL_ENVIRONMENT=production`, so `isSandbox()` is false), a genuine Pesapal IPN whose User-Agent contains none of those strings gets 403 and is never logged or processed.
  - **Good news:** `PesapalController::callback`/`ipn` (lines 123 and 215) and `UniversalPaymentController::handleCallback`/`handleIPN` (lines 363 and 427) do **not** trust status from the request. They re-query `GetTransactionStatus` by `OrderTrackingId`, so spoofing only gives log spam and outbound API calls. `/universal-payments/ipn` has no middleware at all, which is the same situation.
  - **Fix:** Drop the User-Agent heuristic. Rely on the re-query plus `throttle`, and add idempotency (API-113).
  - **shape-safe: yes**

- **API-125 | medium | `app/Http/Controllers/PesapalController.php:26-99, 352-391, 397-510`: anonymous merchant-account operations**
  - **What is wrong:**
    - `POST /api/pesapal/register-ipn {ipn_url:"https://attacker/x"}` registers any URL on the production merchant account and returns its `ipn_id`.
    - `initialize` accepts a client `notification_id` (line 57), so an attacker can route a victim order's IPNs to their URL.
    - Without one, every `initialize` call, and every `POST /pesapal/test` (line 474), registers a brand-new IPN, which an attacker can use to flood the account.
    - `GET /pesapal/config` returns the environment and the last 4 characters of the consumer key and secret (`PesapalConfig.php:145-146`).
    - `UniversalPaymentController.php:39, 88`: `callback_url` comes from the client, so Pesapal redirects the payer to any site (phishing).
  - **Fix:**
    - Make `register-ipn` and `test` admin-only.
    - Ignore the client `notification_id`, and cache the registered IPN id.
    - Return `null` for the key fragments in `/config` (the mobile app reads this endpoint; keep the keys present).
    - Allow-list `callback_url` hosts.
  - **shape-safe: yes**

- **API-126 | medium | `app/Http/Controllers/AccountTransactionController.php:116`: `store` always crashes**
  - **What is wrong:** `Utils::get_user_from_request()` does not exist anywhere in `app/`. Because `Utils extends Model`, `__callStatic` forwards the call to the Eloquent Builder, which throws `BadMethodCallException`. That happens at line 116, outside the `try` that starts at line 141, so the response is HTTP 500 with a stack trace (APP_DEBUG=true locally).
  - **Failure scenario:** The mobile app's "add account transaction" (`account_transaction_api.dart:142`) never works.
  - **Latent issues once fixed:** `created_by_id` comes from the client (line 133), and the withdrawal balance check-then-insert (156-173) is unlocked and uses the inflated API-115 balance.
  - **Fix:** Use `Utils::get_user($request)` or `auth('api')->user()`. Set `created_by_id` from that user. Lock the user row.
  - **shape-safe: yes**

- **API-127 | medium | unbounded `per_page` and whole-table reads on anonymous routes**
  - **What is wrong:** `per_page` is read from the request with no cap in:
    - `TransactionController.php:52`
    - `AccountTransactionController.php:87`
    - `DisbursementController.php:55`
    - `InvestmentTransactionController.php:85`
    - `UniversalPaymentController.php:599`
    - `InsuranceProgramController.php:57`
    - `InsuranceSubscriptionController.php:56`
    - `InsuranceSubscriptionPaymentController.php:70, 247, 291`
    - `MedicalServiceRequestController.php:63`

    No pagination at all in: `InvestmentController.php:296-309`, `ProjectController.php:38`, `ProjectTransactionController.php:40`, `WithdrawRequestController.php:29-32`.
  - **Failure scenario:** `GET /api/account-transactions?per_page=1000000` loads all 8,798 rows plus `user`, `creator` and `relatedDisbursement`. Repeating it ties up PHP-FPM workers. The same call on `universal-payments` combines the dump with the API-111 leak.
  - **Fix:** `$perPage = min(max((int)$request->input('per_page', 20), 1), 100);`
  - **shape-safe: yes**

- **API-128 | medium | `app/Http/Controllers/DashboardController.php:99-123` (`getMainDashboard`): other users' medical requests and phone numbers on every home screen**
  - **What is wrong:** `getRecentMedicalServices()` returns the latest 20 requests from **all** users, with `user_name` and `user_phone`, to whoever calls `dashboard/main`. The mobile app calls this (`dashboard_api.dart:7`). It also reads nonexistent attributes (`service_name`, `cost`, `description`), which are always null.
  - **Fix:** Add `->where('user_id', $userId)`, and map `service_type_label` and `estimated_cost` into the existing keys.
  - **shape-safe: yes**

- **API-129 | medium | `routes/api.php:676-682, 697-747`: anonymous debug and one-off closures still live**
  - **What is wrong:**
    - `POST /api/fix-project-shares` sets project 1 ("Medicine Distribution Partnership") `total_shares=20`. The DB shows `shares_sold=61`, so available shares would be −41. `processProjectSharePurchase` safeguard 4 (UniversalPayment.php:615-628) would then reject every purchase of that project after a successful payment, leaving it paid but unprocessed.
    - It also dumps all 229 projects.
    - `GET /api/dashboard/debug-projects` exposes all projects and cycles.
  - **Mobile app:** uses neither.
  - **Fix:** Delete both closures.
  - **shape-safe: yes** (unused by mobile app)

- **API-130 | medium | insurance controllers: anonymous mark-paid and mass assignment**
  - **Locations:** `app/Http/Controllers/InsuranceSubscriptionPaymentController.php:146-156, 176-222`, `InsuranceSubscriptionController.php:115-137, 215-225`, `InsuranceProgramController.php:114-121, 196-206, 226-244`.
  - **What is wrong:**
    - `POST /api/insurance-subscription-payments/{id}/pay {paid_amount:0, payment_method:"x"}` sets `payment_status='Paid'` unconditionally (`InsuranceSubscriptionPayment::markAsPaid`, model lines 191-208).
    - `update` passes `$request->all()`, so `amount`, `total_amount`, `paid_by` and `user_id` can be written.
    - Subscription store and update pass `$request->all()`, so `total_paid`, `total_balance`, `prepared` and `policy_number` can be written.
    - Programs can be created, edited or deleted anonymously.
  - **Data and mobile app:** the tables hold 1 program and 0 subscriptions or payments, and the mobile app uses none of this (only a `.insurance_backup` file references it).
  - **Fix:** Remove the routes, or add auth plus admin with `$validator->validated()` instead of `$request->all()`. In `markAsPaid`, require `paid_amount >= total_amount`, otherwise mark `Partial`.
  - **shape-safe: yes** (unused by mobile app)

- **API-131 | medium | `app/Http/Controllers/UniversalPaymentController.php:460-525`: no terminal-state guard; a COMPLETED payment can be downgraded**
  - **What is wrong:** Every status check overwrites `status`. Pesapal `status_code` 3 (reversed), 0 or empty falls into the PENDING branch and replaces COMPLETED, after items may already be processed. Reversals never revoke shares or membership. `payment_status_code` is also nulled.
  - **Fix:** If already COMPLETED, update only on an explicit reversal (`status_code==3`): set a REVERSED status and flag the payment for manual handling. Otherwise leave it alone.
  - **shape-safe: yes**

### LOW

- **API-132 | low | `app/Http/Controllers/DashboardController.php:555`: `$project->profits` doesn't exist, so returns are always 0**
  - **What is wrong:** No such column or accessor exists; `getInvestmentsOverview` (line 356) uses `total_profits`.
  - **Effect:** In `dashboard/my-account-overview` (used by the mobile app), `investments.total_returns`, `portfolio_value` and `roi` are always 0.
  - **Fix:** Use `total_profits`.
  - **shape-safe: yes**

- **API-133 | low | `app/Http/Controllers/DashboardController.php:163-164`: `getPendingInsuranceTransactions` eager-loads relations that don't exist**
  - **What is wrong:** It loads `subscription.program`, but `InsuranceSubscriptionPayment` defines only `insuranceSubscription()` and `insuranceProgram()` (model lines 237 and 247). This throws `RelationNotFoundException`, so the endpoint always returns `code:0` with the exception text.
  - **Mobile app:** does not use it.
  - **Fix:** `with(['insuranceSubscription.insuranceProgram'])` and update the accessors at lines 172-174.
  - **shape-safe: yes**

- **API-134 | low | `app/Http/Controllers/TransactionController.php:196-215` + `app/Models/Transaction.php:149-151`: sign and owner bugs on update**
  - **What is wrong:** The amount sign is normalised only when **both** `type` and `amount` are sent, and the `creating` hook doesn't run on update. So `PUT /transactions/{id} {amount:5000}` on a WITHDRAWAL stores +5000, a 10,000 swing. Changing `user_id` (allowed at line 172) recomputes only the new owner's balance; the old owner's stays stale.
  - **Mobile app:** does not use these endpoints.
  - **Fix:** Normalise using `$data['type'] ?? $transaction->type`, and recompute `getOriginal('user_id')` as well.
  - **shape-safe: yes**

- **API-135 | low | `app/Http/Controllers/InvestmentTransactionController.php:265-273, 424-449`: silent no-op write and inconsistent source/type**
  - **What is wrong:**
    - `updateProjectNetProfit` writes `net_profit`, which is not in `Project::$fillable`, so it is silently dropped; `getNetProfitAttribute` overrides it anyway. Success is reported but nothing is persisted.
    - `update` lets `type` flip between income and expense while `source` stays `project_profit`/`project_expense`. `Project::updateComputedFields` filters on type+source while `recalculateFromTransactions` uses source only, so the two disagree.
    - Anonymous `store` with no `user_id` hits a NOT NULL error on `created_by_id`, and the SQL message is returned.
  - **Fix:** Drop `updateProjectNetProfit` and call `$project->recalculateFromTransactions()`. Re-derive `source` from `type` on update.
  - **shape-safe: yes**

- **API-136 | low | `app/Models/UniversalPayment.php:442`: the `InsuranceTransaction` class does not exist**
  - **What is wrong:** An item with `type:"insurance_transaction"` throws a `\Error` (class not found). The `catch (\Exception)` blocks (UniversalPayment.php:460, 187, 243, and controller lines 316 and 559) do not catch it, so the response is an uncaught 500 with a stack trace in debug mode. Anyone can trigger it via `initialize`.
  - **Fix:** Remove that case, or catch `\Throwable`.
  - **shape-safe: yes**

- **API-137 | low | unvalidated `sort_by`/`sort_order` passed to `orderBy`**
  - **Locations:** `AccountTransactionController.php:61-63`, `DisbursementController.php:50-52`, `InvestmentTransactionController.php:64-66`, `InsuranceProgramController.php:52-54`, `InsuranceSubscriptionController.php:51-53`, `InsuranceSubscriptionPaymentController.php:65-67, 286-288`.
  - **What is wrong:** Laravel 8.83 wraps identifiers and escapes JSON paths, so this is not SQL-injectable. But `sort_order=x` throws `InvalidArgumentException`, and an unknown `sort_by` gives a QueryException. Both are echoed through `getMessage()`, which reveals table and column names and the full SQL.
  - **Fix:** Whitelist the columns and use `in_array(strtolower($o), ['asc','desc'])`.
  - **shape-safe: yes**

- **API-138 | low | exception messages returned to clients across all 18 controllers**
  - **Examples:** `UniversalPaymentController.php:124, 326, 384, 452, 568, 610`; `TransactionController.php:65, 119, 150, 227`; `AccountTransactionController.php:106, 188`; `DisbursementController.php:80, 195`; `InvestmentController.php:187, 270, 360`; `ProjectController.php:53, 116`; and others.
  - **What is wrong:** These return `$e->getMessage()`, which includes QueryException SQL text and file paths. Combined with `APP_DEBUG=true` in `.env`, uncaught paths (API-122, API-126, API-136) return full stack traces.
  - **Fix:** Log the detail and return a generic message in the same key. Make sure `APP_DEBUG=false` in production.
  - **shape-safe: yes** (same keys, different text)
