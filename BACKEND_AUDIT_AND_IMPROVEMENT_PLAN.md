# FAO FFS MIS Backend: Audit Report and Improvement Plan

**Date:** 31 October 2026
**Scope:** Laravel API (`routes/api.php`, 33 API controllers, 34 legacy controllers), data layer (103 models, 90 migrations, live schema) and the Laravel-Admin portal (129 controllers).
**Data used:** a local copy of production (`fao_prod_copy`, from the 28 Jul 2026 dump: 8,712 users, 751 groups, 128 tables).
**Detailed evidence:** [`docs/audit-2026-10/`](docs/audit-2026-10/), 270+ verified findings, each with file:line, evidence and fix.

| Appendix | Content |
|---|---|
| [01-api-audit.md](docs/audit-2026-10/01-api-audit.md) | 138 API findings (API-01 … API-138) |
| [02-data-audit.md](docs/audit-2026-10/02-data-audit.md) | 54 data/schema findings (DATA-01 … DATA-54) plus index list |
| [03-admin-audit.md](docs/audit-2026-10/03-admin-audit.md) | 44 admin portal findings (ADM-01 … ADM-44) plus dashboard review |
| [04-mobile-api-contract.md](docs/audit-2026-10/04-mobile-api-contract.md) | What the production Flutter app sends and reads, for about 330 endpoints |

---

## 1. Summary

The backend works for day-to-day VSLA and FFS operations, but it has four classes of problems:

1. **Security.**
   - The API identifies the caller from a plain `User-Id` header, so anyone can act as any user.
   - Dozens of legacy endpoints need no identity at all, including endpoints that change passwords, delete users or write ledger rows.
   - Password hashes and live password-reset codes are returned in ordinary API responses.
   - Every admin role, including the 5,729 Farmer Member accounts, held the `*` permission on the admin portal.
   - Uploaded files could be executed as PHP.
2. **Financial integrity.** Some flows post money twice or compute balances wrongly:
   - meeting re-submission;
   - opening-balance reprocessing;
   - disbursements credited twice;
   - mixed loan-transaction signs;
   - case-mismatched manifest totals;
   - group cash leaving out loan repayments and social fund.
3. **Broken endpoints.** About 15 endpoints the app uses return 500 or report failure after a successful save.
4. **Performance.**
   - N+1 query patterns: one endpoint ran 17,425 queries per request; the group list and manifest run 300+.
   - No indexes on `users.phone_number`, `email` or `username`, which every login and save scans.
   - An uncached admin dashboard running 77 queries per page.

**Hard constraint: the mobile app is in production.** Every backend change must keep response keys, types and envelopes exactly as the app parses them (appendix 04). In particular:
- `code` must stay the integer `1`.
- List endpoints must keep returning a bare array.
- `data.remember_token` carries the login JWT.
- The group manifest is parsed all-or-nothing.

---

## 2. How this was analysed and how every change is verified

1. **Production copy.** The 28 Jul 2026 production dump was imported locally as `fao_prod_copy`. All tests run against real data.
2. **Five independent audits:** API controllers, data layer, admin portal, security/infrastructure, and the mobile app's API contract. Each finding was verified by reading code and querying the live schema.
3. **API regression harness.** Every GET API route (237) is called in-process for 4 real users (2 group chairs, a member, a facilitator), the way the app calls it (`User-Id` header plus Bearer JWT). It records status, full JSON structure, response body, query count and latency. A baseline of 948 responses was captured **before any change**.
4. **Compare after every change.** A comparator flags any status regression, removed key or changed type as a **BREAK**, and lists value differences and query-count changes. Any BREAK means the change is not accepted.
5. **No real-world side effects during tests.** Outbound HTTP (SMS, push, Pesapal) is routed to a dead proxy and mail goes to the log, so no farmer receives anything.
6. **Admin portal tests.** The real login form and portal pages are exercised over HTTP, as Super Admin, IP Manager, Facilitator and Farmer accounts on the local copy.

---

## 3. What cannot be fixed on the server alone

**API authentication (API-01).** The app sends `Authorization: Bearer <jwt>` and `User-Id`, but:
- it never refreshes the JWT, which expires after 60 minutes;
- users who signed up by OTP have no token at all.

If the server rejected requests without a valid token, most of the production app would stop working. Real authentication therefore needs a coordinated app release:
- **Backend (this plan):** verify JWT signatures and log every request whose header identity does not match a valid token, so the risk can be measured.
- **Next app release:**
  - store and refresh the token;
  - receive a token after OTP sign-up;
  - send the token on every call.
- **Then:** the server switches to enforcing tokens. This is a configuration flag, with no code change.

Until then, the mitigations in Phase 1 (hiding secrets, closing anonymous endpoints, per-endpoint authorization checks) reduce the damage.

---

## 4. Work plan

Status key: ✅ done and verified · 🔧 in progress · ⏳ planned · 🟡 needs a decision from the project owner.

### Phase 0: Safety net
| # | Task | Status |
|---|---|---|
| 0.1 | Production copy `fao_prod_copy` imported (128 tables, 90 migrations, 0 pending) | ✅ |
| 0.2 | API snapshot harness, 948-response baseline, comparator with BREAK detection | ✅ |
| 0.3 | Outbound network and mail neutralised for tests | ✅ |

### Phase 1: Critical security (shape-safe)
| # | Task | Findings | Status |
|---|---|---|---|
| 1.1 | Close remote code execution through uploads: one extension allow-list for every upload path, plus `public/storage/.htaccess` that refuses to run scripts. Verified on Apache: planted `.php` returns 403 and images still return 200 | API-73, security audit | ✅ |
| 1.2 | Admin portal staff gate: only Super Admin, IP Manager, Facilitator, M&E Officer and Content Manager can sign in. Others are refused at login and ejected mid-session. Built-in user, role, permission, menu and log management is super-admin only. Health-check batch actions and partner edits are super-admin only | ADM-01, ADM-06 | ✅ |
| 1.3 | Operation log no longer stores passwords (redacting middleware). Member-update debug log redacted | ADM-02 | ✅ |
| 1.4 | Remove the 352 plaintext passwords already in `admin_operation_log` (migration that redacts in place) | ADM-02 (352 → 0 on the production copy; new entries redacted; page views are no longer logged) | ✅ |
| 1.5 | Super Admin role can no longer be granted by non-super-admins: picker filters the real slug, and a server-side guard strips it on save. "Cannot modify Super Admin" guards fixed | ADM-03, ADM-18 | ✅ |
| 1.6 | Hide `password`, `intro` (holds live OTPs) and `otp_expires_at` from every API response. `remember_token` is kept because the app reads the JWT from it | API-03, 42, 43, 75, 80, 111 (verified on all 237 GET routes: the only change is the removal of these three keys, from 15+ endpoints) | ✅ |
| 1.7 | Generic model endpoints `GET/POST api/{model}` and `GET api/ajax`: allow-list of read-only, non-sensitive models; writes disabled | API-02, API-18 (reads allow-listed to Disability/Location/ProductCategory/Enterprise; generic writes disabled; `api/ajax` removed: no live caller) | ✅ |
| 1.8 | Anonymous legacy endpoints that write or delete (insurance-users, account-transactions delete, transactions, investment-transactions, disbursements, medical review/update, membership confirm, order cancel, product delete): require identity plus an admin role, or disable | API-05, 08, 20, 107, 108, 117, 119 (auth middleware on every legacy group; admin-only on writes, `transactions`, `insurance-users`, `users-list`; Pesapal callback and IPN stay public; `debug-projects` removed) | ✅ |
| 1.9 | Account-takeover paths: `account-verification` bound to the caller; `register-admin` and agent officer registration must not overwrite existing accounts' passwords; `PUT members/{id}` and `members/sync` limited to self, officer, facilitator or admin, with field allow-lists | API-04, 41, 44, 48, 72, 74, DATA-39, DATA-40 | ⏳ |
| 1.10 | Middleware: verify JWT signature when present and log identity mismatches (monitor mode, `API_AUTH_MODE` flag) | API-01 | ⏳ |

### Phase 2: Crash bugs and broken endpoints
| # | Task | Findings | Status |
|---|---|---|---|
| 2.1 | Profile update, comprehensive profile, password change, delete account and become-vendor saved the change, then returned 500 (HTTP status `1`). Response helpers now normalise invalid status codes | API-07, API-19 | ✅ |
| 2.2 | Location dropdowns (`ffs-groups/subcounties|parishes`, `locations/subcounties|parishes`) returned 500 on a non-existent `parent_id` column. They now use `parent`, and `parent_id` is kept as an alias. 500 → 200 for all 4 users | API-84 | ✅ |
| 2.3 | `profiling/users` returned 500 on a missing `phone_number_verified_at` column. Column added by migration and made fillable, so "verify" now persists. 500 → 200 | API-17 | ✅ |
| 2.4 | `chat-*` routes pointed at deleted methods (500). They now answer explicitly | API-23 | ✅ |
| 2.5 | Loan repayment `POST vsla/loans/{id}/repayments` lowered the balance, then failed (missing `type`) with no transaction, so retries lowered it again. Make it transactional and locked, set the type, write ledger legs | API-06, DATA-21, DATA-24 | ⏳ |
| 2.6 | `loan-transactions/payment` always fails (NULL `user_id`, invalid enum) | API-47 | ⏳ |
| 2.7 | App calls `api/vsla-onboarding/update-step`, which resolves to `/api/api/...` (404). Add a backend alias | contract #1 | ⏳ |
| 2.8 | Duplicate or shadowed routes (`locations/districts` returns regions as districts; attendance `export-pdf` shadowed by `{id}`) | API-21, 22, 24 | ⏳ |
| 2.9 | Admin pages that error: cycle and project detail (SQL 1054), loan Principal column, partner filters, borrower sort, Export Centre menu 404, routes to missing tables, group-association create | DATA-06, ADM-10, 13–17 | ⏳ |

### Phase 3: Financial integrity
| # | Task | Findings | Status |
|---|---|---|---|
| 3.1 | Same-day meeting re-submission re-posts every saving, share, social fund and repayment. Reverse the earlier run's rows before re-processing and store the new `local_id` | API-09, DATA-17 | ⏳ |
| 3.2 | Group savings screen leaves out loan repayments (UGX 38.6M) and social fund (UGX 17.8M) from cash and inflow | API-15, DATA-25 | ⏳ |
| 3.3 | Group manifest compares uppercase types with lowercase data, so shares, repayments and fines show 0. Dashboard `total_repaid` is double-counted | API-52, DATA-26 | ⏳ |
| 3.4 | Disbursements credited twice (model hook plus controller) | API-109 | ⏳ |
| 3.5 | Opening-balance reprocess duplicates shares, loans and social fund. Make it idempotent and restrict who can run it | API-45, DATA-18 | ⏳ |
| 3.6 | Loan penalty and waiver use a summed balance with inconsistent signs (wrong for 756 loans). Use the stored balance | API-46, DATA-21 | ⏳ |
| 3.7 | Withdrawable balance sums the whole VSLA ledger, including group mirror rows (103 users overstated, up to UGX 17.1M) | API-115, DATA-05, DATA-22 | ⏳ |
| 3.8 | Shareout initiate, approve and complete: limit to group officers or facilitator, with a row lock | API-12, API-13 | ⏳ |
| 3.9 | Data repairs that change historical balances: 128 ledger rows from deleted duplicate meetings; UGX 1 opening-balance shares; corrupted phone numbers | DATA-19, DATA-20, DATA-41 | 🟡 |

### Phase 4: Performance
| # | Task | Findings | Status |
|---|---|---|---|
| 4.1 | `user-accounts/users-list`: 17,425 → 2 queries, 4.4 s → 0.75 s, byte-identical output | API-120 | ✅ |
| 4.2 | Indexes: `users(phone_number)`, `users(email)`, `users(username)`, ledger and loan composite indexes (non-unique first; unique after cleanup) | DATA-01 + index list | ⏳ |
| 4.3 | `ffs-groups` (326 queries), group manifest (333), `group-savings` (609): remove N+1 | API-15, API-52 | ⏳ |
| 4.4 | Admin dashboard: 77 uncached queries per load, so cache 5 minutes; members, loan-transactions and cycles grids are N+1 | ADM-26, 27, 28 | ⏳ |
| 4.5 | Admin operation log records every page view (12k rows): log only changes | ADM-44 | ✅ |

### Phase 5: Admin portal correctness and UI/UX
| # | Task | Findings | Status |
|---|---|---|---|
| 5.1 | IP users see about 1% of loans (scoped through `meeting`, which 99% of loans lack). Scope through cycle → group | ADM-09 | ⏳ |
| 5.2 | Denied detail pages print raw "HTTP/1.0 302" text. Abort with 403 instead | ADM-20 | ⏳ |
| 5.3 | Dashboard "Quick period" ignored; orphan count always 0 for IP users; stale member counts | ADM-19, 33, 40 | ⏳ |
| 5.4 | Ledger cannot be hand-edited (create and delete disabled); hard user delete disabled in grids | ADM-11, ADM-12 | ⏳ |
| 5.5 | `users.status` mixes `'1'` and `'Active'`, so 6,688 active users show "Inactive". Use one display helper | ADM-30 | ⏳ |
| 5.6 | CSV exports contain HTML. Strip tags | ADM-36 | ⏳ |
| 5.7 | Navbar shortcuts point to non-existent routes and other projects; dead search box | ADM-21 | ⏳ |
| 5.8 | Menu restructure (duplicates, raw slugs, localhost link, wrong icons) | ADM-37 | ⏳ |
| 5.9 | Labels: "SACCO" becomes "VSLA / Group" | ADM-42 | ⏳ |

### Phase 6: Decisions for the project owner (🟡)
| # | Decision | Why it needs you |
|---|---|---|
| 6.1 | Mobile release for real token authentication (see section 3) | Requires app changes and a release |
| 6.2 | Restore Karamoja sub-counties and parishes into `locations` (currently 1 sub-county; the backup has 1,654) | Master-data change. Dropdowns are empty without it |
| 6.3 | Historical balance repairs (3.9) | Changes members' recorded savings and loans, and needs sign-off |
| 6.4 | Merge duplicate partner record (KADP id 1 and id 6) | Reporting impact |
| 6.5 | Replace predictable default passwords (phone digits, `123456`, `4321`) and force a change on first login | Affects how field staff onboard users |
| 6.6 | Soft deletes for users (`deleted_at`), and stop hard-deleting financial history | Schema change with behaviour impact |
| 6.7 | Rotate credentials: cPanel/SSH password (stored in plain text locally) and the Pesapal keys in `.env` | Operational |
| 6.8 | Point the domain's document root at `/fao/public` in cPanel (defence in depth) | Hosting configuration |

---

## 5. Deployment and rollback

1. Every change is made locally and verified with the harness: 0 BREAKs required.
2. Before deploying, back up the server (code state plus `mysqldump` of `schooics_fao`) to `~/fao_backups/<date>`.
3. Deploy with `git pull`, then `php artisan migrate --force`, then `php artisan optimize:clear`. Migrations are additive and guarded (`hasColumn`/`hasIndex`).
4. After deploying, run a smoke test against the live server (key API endpoints plus admin login per role).
5. **Rollback:** `git reset --hard <previous sha>`. The migrations only add columns or indexes and are safe to leave in place.

---

## 6. Progress log

- **31 Oct 2026, live domain:** `fao-ffs-mis.org` showed a certificate warning after the nameserver move to HostGator. DNS was already correct worldwide (NS `hgns1/2.hostgator.com`, A `162.241.194.81`). The server was presenting a `*.schooldynamics.ug` certificate because AutoSSL's 00:51 UTC run had still seen the old Namecheap IP. A fresh AutoSSL run issued a Let's Encrypt certificate for `fao-ffs-mis.org`, `www`, `mail` and `webmail` (valid to 29 Jan 2027, auto-renewing). HTTPS verified end to end: admin login, `/api/locations/districts`, `/api/manifest`.
- **31 Oct 2026, harness verification:** after each batch, the full 237-route × 5-role run shows no unintended BREAKs. The only differences are the intended ones listed in the tasks above (secret fields removed, admin-only 403s for non-admins, removed dangerous routes).
- Phase 1 items 1.9 and 1.10, and Phases 2.5 onward, are next.
