# FAO FFS-MIS: Mobile API Contract (Flutter app → Laravel API)

- **Mobile source:** `/Users/mac/Desktop/github/fao-ffs-mis-mobo/lib` (Dio 5.8.0+1, GetX)
- **Backend source:** `/Applications/MAMP/htdocs/fao-ffs-mis-api/routes/api.php` (Laravel 8), plus the controllers it points to
- **Generated:** 2026-10-31. Read-only analysis; no files in either repo were changed.
- **Purpose:** before you merge a backend change, check every endpoint it touches against the "Response keys read" and "Type assumptions" columns below. Section 1 explains what the app does with the HTTP status and the envelope.

How to read the tables:
- **RM** means the call site wraps the response in `RespondModel`, which is safe (see 1.3). **direct** means the code does `response['code'] == 1` itself.
- **Live/Dead** (sections D and E, and partly A and C) says whether a reachable screen actually calls the endpoint. Broken calls in dead code are low priority.
- **Backend route** gives `Controller@method` and follows Laravel's matching rules. A GET `prefix/{id}` declared before `prefix/literal` captures the literal path ("SHADOWED"). When the same METHOD+URI is declared twice, the **later** declaration wins.
- Some module tables carry extra columns (row #, call site, Live/Dead). Every table has at least: method | path | request keys | response keys read | type assumptions | fragility | backend route.

---

## 0. Executive summary

| Metric | Value |
|---|---|
| Backend call sites in the app | **~426**: 381 via `Utils.http_*`, plus ~45 via farm_service (package:http), the advisory Dio clients, ApiService, phone_verification_api and weather_service |
| Distinct method + path pairs | **~330** |
| Call sites in live, reachable code | Roughly half. Most e-commerce, payments, investments, medical and legacy school/NGO models are dead code. |
| Backend routes in api.php | 426 |
| Backend routes never called by mobile | ~105 (section 8) |
| Broken routes in **live** code | 15+ (section 7.1) |
| Broken routes in dead code | ~70 (section 7.2) |

---

## 1. Transport and envelope handling

### 1.1 Base URL and clients

| Client | Where | URL construction | Headers sent | Body encoding |
|---|---|---|---|---|
| `Utils.http_get/post/put/delete` (≈90% of calls) | `lib/utils/Utils.dart:133-488` | `"${AppConfig.API_BASE_URL}/$path"`, where `API_BASE_URL = "https://fao-ffs-mis.org/api"` (`lib/utils/AppConfig.dart:2-7`). A new `Dio()` per call, no baseUrl, **no timeouts set**, bad certificates accepted. | `Authorization: Bearer <SharedPreferences 'token'>`, `User-Id: <logged-in user id>`, `Accept: application/json`, `Content-Type: application/json` (Dio overrides it to multipart for FormData) | **GET:** query params only (no user injected). **POST:** `FormData.fromMap(body)` (multipart) plus injected fields `user` and `User-Id`. **PUT:** JSON body plus `user`, `User-Id`, and `user_id` if missing. **DELETE:** FormData with `user`, `User-Id`, `user_id` **forced to the logged-in user** |
| `FarmService` | `lib/services/farm_service.dart` | `package:http`, `'$baseUrl/farms…'` | Bearer, User-Id, JSON | `MultipartRequest` for create/update (update uses `_method=PUT`), JSON for skip/custom activity |
| `AdvisoryApiService` | `lib/services/advisory_api.dart` | Dio `baseUrl: API_BASE_URL`; paths start with `/` (Dio 5 concatenates the strings, so the result is correct) | **Bearer only, no User-Id** | JSON / FormData |
| `AdvisoryOfflineService` | `lib/services/advisory_offline_service.dart` | Dio `baseUrl: API_BASE_URL` plus `'advisory/questions'` with **no leading slash**, giving `…/apiadvisory/questions` (BROKEN, :440) | Bearer only | FormData |
| `PhoneVerificationApi` | `lib/services/phone_verification_api.dart` | `'/api/check-phone'` etc., giving `/api/api/...` | none | JSON. **Dead code** (no callers) |
| `ApiService` (GetX) | `lib/services/ApiService.dart` | Dio baseUrl plus `'api/…'`, giving `/apiapi/…` | Bearer; adds user/User-Id/user_id | **Dead code** (its only consumers, ModernCartController, ProductService and AppStateController, are unreachable) |
| `WeatherService` | `lib/services/weather_service.dart` | `API_BASE_URL/weather/forecast` | – | Dead per section E |

**Identity:** the backend's `EnsureTokenIsValid` (`app/Http/Middleware/EnsureTokenIsValid.php`) trusts the `User-Id` header **first**. Only when that is absent does it decode the JWT `sub`, and it never verifies the signature or expiry. Many unprotected routes, such as `withdraw-requests`, `user-accounts/dashboard`, `membership-status` and ApiResurceController methods, identify the user from `$_SERVER['HTTP_USER_ID']` or the injected FormData `user` field.

**The app depends on this.** It never refreshes the JWT (60-minute TTL), and OTP sign-up has no token at all until `register-admin`. **Moving to JWT-only or signature-verified auth would break most of the app.**

**Token source:** login stores `data.remember_token` into prefs `'token'` (`login_screen.dart:73-75`, `switch_account_sheet.dart:113`, `VslaRegistrationScreen.dart:279`). No other token field is read. `OnBoardingScreen.dart:74` logs the user out on a cold start if the stored token is shorter than 10 characters.

### 1.2 What the helpers return

Dio 5 treats any non-2xx status as a `DioError`. Utils catches it and returns:

| Situation | `http_get` returns | `http_post` / `http_put` / `http_delete` return |
|---|---|---|
| Offline (ConnectivityController) | `{code:0, message:'You are not connected to internet.', data:null}` | same |
| 2xx with JSON object body | the decoded Map | the decoded Map |
| 2xx with a non-JSON or `text/html` body, or JSON preceded by PHP warning output | a `String` | a `String` |
| **4xx/5xx with a JSON object body** (e.g. `{code:0,message:"…"}` with 400/401/404/409/422/500) | **the decoded Map, exactly as if it were a 200** | **the decoded Map, exactly as if it were a 200** |
| 4xx/5xx with an HTML or empty body | `{status:0, code:0, message:<raw body or null>, data:null}` (Map) | **a `String`**: `jsonEncode({'status':0,'message':'Failed because …'})` |
| Timeout or DNS failure | `{status:0, code:0, message:null, data:null}` | String as above |

How the "is it a Map?" check works: the helpers test `e.response?.data.runtimeType.toString() == '_Map<String, dynamic>'`. That works with standard builds. A release build made with `--obfuscate` could change the type name, and every error body would then fall through to the generic branch.

### 1.3 How call sites read the envelope

**Pattern 1: `RespondModel(raw)`** (`lib/models/RespondModel.dart`, used at ~87 call sites):
- If `raw` is not a `_Map`, it runs `jsonDecode(raw)`. If decoding fails, it becomes `{code:0, message: raw.toString()}`.
- `code = Utils.int_parse(resp['code'].toString())`. As a result, `"1"` is treated as 1. `true` becomes 0, which counts as **failure**. A missing `code` also gives 0.
- `message = resp['message'].toString()`. A missing message shows as the string `"null"`.
- `data = resp['data']`, with no type coercion.
- `message == 'Unauthenticated'` (exact match, **no trailing period**) calls `Utils.logout()`.
  - Nothing in the backend sends that string. EnsureTokenIsValid sends `'User ID is required in headers'` or `'Your account was not found…'`, and sanctum sends `'Unauthenticated.'` with a period.
  - So **the auto-logout path is never triggered today**. A 401 just shows its message.

**Pattern 2: direct `response['code'] == 1`** (most services: vsla_*, aesa, enterprise, ffs, location, kpi, shareout, loan, cycle, farm and others):
- This requires `code` to be the JSON **number 1**. `"1"` or `true` fails it.
- On a POST/PUT/DELETE error the helper returns a String, so `response['code']` throws a TypeError. Most call sites catch it and show a generic "Error: …" toast.
- Some services instead assign `json['code'] ?? 0` to an `int` field (the FFS wrappers, attendance), which throws on `"1"`.

**Pattern 3: `success` key.**
- `vsla-meetings/submit` checks `response['success'] == true` at the top level, and checks the body `code == 409` with `meeting_id` for duplicates.
- Universal payments, projects and medical use `{success, message, data}`. Medical checks `code`, which is never sent.
- Phone verification (`phone-verification/*`) checks `status == 1`, not `code`.

### 1.4 What this means for HTTP status codes

| Change | Safe for the app? |
|---|---|
| Change status 200 → 4xx/5xx while **keeping a JSON object body** with `code`/`message` | **Safe** for every `Utils.http_*` and RespondModel call site. The app ignores the status and reads the body. |
| Same change on `farm_service.dart` endpoints (`farms*`, `farm-activities*`) | **Breaks.** It requires `statusCode == 200` (201 is allowed only for the farm create and custom activity create at :161 and :588). A 422 message becomes "Failed to …: 422". |
| Same change on advisory endpoints (AdvisoryApiService Dio) | Degrades. Any non-2xx becomes `{code:0, message:'Request failed: DioException…'}` and the server message is lost. |
| Return 201 instead of 200 | Safe everywhere except farm_service (only the two create endpoints accept 201). |
| Return 204 or an empty body | **Breaks.** The body becomes `""`, so `response['code']` throws in direct callers and RespondModel gives code 0. |
| Laravel default validation 422 `{message, errors:{field:[..]}}` with no `code` | Treated as a failure. RespondModel shows `message`. Direct callers see `code == null`, which is not 1, so they fail gracefully. **But** `vsla-meetings/submit` expects `errors` to be a **List** and crashes on a Map (`MeetingSummaryScreen.dart:284`, `VslaMeetingsHubScreen.dart:997`). |
| A non-JSON 500 page (debug HTML) | GET: the raw HTML is shown as the toast message. POST: "Failed because …". No crash for RM callers; direct callers throw, and the throw is usually caught. |
| PHP notices or deprecations printed before the JSON | **Breaks everything.** The body becomes a String that can't be decoded. Keep `display_errors=Off` in production. |
| `code` returned as `"1"` (string) or `true` | `"1"` is fine for RM but **fails every direct `== 1` check**. `true` fails everywhere. **Keep `code` as an integer.** |
| `data` changed from a List to a paginated object `{data:[…], …}` | **Breaks** list screens. Most do `data as List`, or check `is List` and otherwise show nothing, and several wipe the local cache first. |
| A nested object returned as PHP empty array `[]` instead of `{}`/`null` | **Breaks** `Map`-typed parsing (manifest, onboarding prefill, market `latest_price`, withdraw-request relations). |

---

## 2. Auth, Onboarding & Profile

Scope: login / switch account / forgot password, phone OTP, chairperson self-onboarding (VSLA steps 3-7), user profile, `users/me`, `users/my-roles`, profiling (universal registration, users list, groups list), manifest / app-config, OneSignal device registration, plus the `SystemUser` model.

Mobile paths are relative to `lib/`. Unless stated otherwise, every call goes through `Utils.http_get` / `Utils.http_post`. POST bodies are multipart **FormData** with `user` and `User-Id` injected, and every request carries the headers `Authorization: Bearer <prefs 'token'>` and `User-Id`.

**How the session is stored.** The app persists the whole login response `data` object, parsed by `LoggedInUserModel.fromJson`, as a single row in the SQLite table `logged_in_user_2`. The bearer token goes into SharedPreferences under the key **`'token'`**, and its value is taken **only from `data.remember_token`**. The app never reads `data.token`. On startup, `OnBoardingScreen.dart:74` treats `token.length < 10` as "not logged in" and sends the user to `BoardingWelcomeScreen`.

**Fields `LoggedInUserModel.fromJson` parses** (`models/LoggedInUserModel.dart:126-244`). Every one goes through `Utils.to_str(x, default)` or `Utils.int_parse`, so none of them can throw:
- `id` (int_parse), `household_size` (int_parse)
- String fields: `username, password, first_name, last_name, reg_date, last_seen, email, approved, profile_photo, user_type, sex, reg_number, country, occupation, profile_photo_large, phone_number, location_lat, location_long, facebook, twitter, whatsapp, linkedin, website, other_link, cv, language, about, address, created_at, updated_at, remember_token, avatar, name, campus_id, campus_text, complete_profile, title, dob, is_admin, intro, business_name, business_license_number, business_license_issue_authority, business_license_issue_date, business_license_validity, business_address, business_phone_number, business_whatsapp, business_email, business_logo, business_cover_photo, business_cover_details, status, group_id, group_name, group_code, district_id, district_name, subcounty_id, subcounty_name, parish_id, parish_name, village, member_code, marital_status, education_level, emergency_contact_name, emergency_contact_phone, national_id_number, onboarding_completed_at, last_onboarding_step_at, ip_id, ip_name, ip_short_name`
- With defaults: `is_group_admin` / `is_group_secretary` / `is_group_treasurer` default to `'No'`; the app compares them with `== 'Yes'`. `onboarding_step` defaults to `'not_started'`; the app compares it with the literal enum strings `step_N_*`.
- `group_text` comes from `group.name` when `group` is a Map, otherwise from `group_text ?? group_name`.
- `district_text` comes from `district` when `district` is a String, otherwise from `district_text ?? district_name`.
- `phone_number_2` is never parsed.

Legend for the "Backend route" column: **Yes** = exact match; **NO (missing)**; **DUP** = declared twice, the later declaration wins; **BROKEN-500** = the route exists but currently throws.

---

### A1. Login, switch account, forgot password

| # | Method | Path | Call site | Request (FormData) | Response keys read | Type assumptions | Fragility | Backend route |
|---|---|---|---|---|---|---|---|---|
| 1 | POST | `users/login` | `screens/account/login_screen.dart:59` | `username` (String: phone, email or username), `password` (String) | `code`, `message` (via RespondModel); `data` → `LoggedInUserModel.fromJson` (all fields above); then `data.remember_token`, `data.id`, `first_name`, `last_name`, `avatar`, `ip_name`, `phone_number`, `group_name`, `group_id` | `data` must be a Map. `remember_token.length > 4` is required to store the token. `id >= 1` is required, otherwise "Failed to retrieve you in." | **The token is read from `remember_token` only.** If the backend hides `remember_token` (Laravel `$hidden`) or renames it, the token is never saved and every app start bounces to the welcome screen (`OnBoardingScreen.dart:74`). Errors come back as HTTP 400 with a JSON body (handled). | Yes: `ApiAuthController@login` (api.php:98) |
| 2 | POST | `users/login` | `screens/account/switch_account_sheet.dart:92` | `username`, `password` (from saved-account store) | Same as #1, plus `data.username` | Same as #1 | Same as #1. The local DB is wiped and re-created before `user.save()`. | Yes: same |
| 3 | POST | `users/login` | `screens/account/login_screen_old.dart:39` | `phone_number`, `password` (**no `username`**) | `code`, `message` only | none | **Dead code**: `LoginScreenOld` is never referenced. Would also fail, because the backend requires `username`. | Yes, but would return "Username is required." |
| 4 | POST | `users/login` (task) | `services/forgot_password_service.dart:15` (from `PasswordRequestScreen`, linked from `LoginScreen`) | `username`, `email`, `task='request_password_reset'` | `code`, `message`. The app regex-extracts `\S+@\S+` from `message` to show the masked email. | none | Cosmetic only: if the message stops containing an email, the raw input is shown instead. | Yes: `ApiAuthController@login` task branch, `success(msg, [])` |
| 5 | POST | `users/login` (task) | `services/forgot_password_service.dart:31` | `username`, `email`, `code` (6-digit String), `password`, `task='reset_password'` | `code`, `message` | none | Safe | Yes: same |
| 6 | POST | `/auth/login` (via `ApiService`) | `controllers/AppStateController.dart:191` | JSON `email`, `password` | `isSuccess`, `data` → `LoggedInUserModel.fromJson` | — | **Dead code**: `signIn()` is only reachable through `StateManager.signIn`, which has no callers. | **NO (missing)** |

**Navigation.** `main.dart` starts at `OnBoardingScreen`, which goes either to `MainScreen` or to `BoardingWelcomeScreen`. The welcome screen offers "Login" (`LoginScreen`) and "Register as Group Admin" (`PhoneVerificationScreen` → `OTPVerificationScreen` → `/VslaPrivacyTermsScreen` → `VslaRegistrationScreen` → `VslaGroupCreationScreen` → `VslaMainMembersScreen` → `VslaSavingsCycleScreen` → `VslaCompleteScreen`). `VslaWelcomeScreen` (reached from the vsla tab and menu) also leads to `VslaPrivacyTermsScreen`. `LoginScreen` is also reached from `sections/widgets.dart:1333` and `WishlistScreen`. `login_screen_old.dart` and `LoggedInUserModelOld.dart` are dead; the latter is 100% commented out.

### A2. Phone OTP (group-admin self-registration)

The responses for these endpoints use **`status`**, not `code`, and the app checks `response['status'] == 1` **directly on the raw http_post result** (no RespondModel).

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend route |
|---|---|---|---|---|---|---|---|---|
| 7 | POST | `phone-verification/check-phone` | `screens/account/PhoneVerificationScreen.dart:47` | `phone_number` (String) | `status` (==1), `message`, `data` (kept whole as `_chairpersonData` and passed forward) | `status` compared with int `1` | Every backend response here is HTTP 200, so a String body would only come from a 5xx HTML page. In that case `String['status']` throws inside the try and the user sees a toast "Error: ...". If the backend moves to an envelope with only `code` (no `status`), **every OTP step silently fails**. | Yes: `PhoneVerificationController@checkPhone` |
| 8 | POST | `phone-verification/send-otp` | `PhoneVerificationScreen.dart:81` | `phone_number` | `status`, `message` | same | same | Yes: `@sendOTP` |
| 9 | POST | `phone-verification/send-otp` (resend) | `screens/account/OTPVerificationScreen.dart:123` | `phone_number` | `status`, `message` | same | same | Yes |
| 10 | POST | `phone-verification/verify-otp` | `OTPVerificationScreen.dart:75` | `phone_number`, `otp_code` (6-char String) | `status`, `message`, `data.user` → `LoggedInUserModel.fromJson` (backend sends only `id, name, phone_number, business_phone_number, email, is_group_admin, is_group_secretary, is_group_treasurer, group_id`), `data.user.remember_token` | `data.user` is expected to be a Map | **The backend returns no token.** The user is saved locally without one, and later calls authenticate only through the `User-Id` header, which `EnsureTokenIsValid` trusts. If the middleware ever stops trusting `User-Id`, onboarding steps 3-7 break until `register-admin` returns a token. `data.group`, `data.secretary` and `data.treasurer` are **ignored**: only the check-phone `data` is forwarded as `chairperson_data`. | Yes: `@verifyOTP` |
| 11 | — | `/api/check-phone`, `/api/send-otp`, `/api/verify-otp` (own Dio client) | `services/phone_verification_api.dart:22,55,91` | — | — | — | **Dead code** (never imported). The URL would be `/api/api/check-phone`, which hits the catch-all `api/{model}` POST → `ApiResurceController@update`. | catch-all (wrong) |

### A3. VSLA chairperson onboarding (steps 3-7)

| # | Method | Path | Call site | Request (FormData) | Response keys read | Type assumptions | Fragility | Backend route |
|---|---|---|---|---|---|---|---|---|
| 12 | GET | `vsla-onboarding/data/chairperson` | `models/OnboardingDataModel.dart:61` (used by `VslaRegistrationScreen.dart:73`) | — | `code` (==1), `message`, `data` (returned as a Map) → prefill keys `name, phone_number, email, national_id_number, sex, address, village` | `responseData['code'] == 1` checked directly (int). The return type is `Map<String,dynamic>?`. | A List `data` throws, but the try catches it and returns null. **Mismatch:** the backend sends `first_name`/`last_name`, not `name` or `village`, so the name is never prefilled. 401/403 come back as JSON with `code` 0, so the app falls back to the navigation arguments. | Yes: `VslaOnboardingDataController@getChairpersonData` |
| 13 | GET | `vsla-onboarding/data/group` | `OnboardingDataModel.dart:82` (used by `VslaGroupCreationScreen.dart:70`) | — | `data.name, phone_number, email, address, village, meeting_frequency, district_id` | same | The backend has no `phone_number`, `email` or `address` for groups (harmless). The prefill patches the form key `'district'`, but the field is named `district_id`, so **the district is never prefilled** (app bug). `data: null` (no group yet) is handled. | Yes: `@getGroupData` |
| 14 | GET | `vsla-onboarding/data/main-members` | `OnboardingDataModel.dart:103` (used by `VslaMainMembersScreen.dart:59`) | — | `data.secretary` / `data.treasurer` → `name, phone_number, email, national_id_number, sex` | `_secretaryData = apiData['secretary']` is assigned to a `Map<String,dynamic>?` **inside `setState`, outside the try** | If `secretary`/`treasurer` ever becomes `[]` (empty PHP array) instead of `null`, this throws a TypeError, uncaught. Backend secretary/treasurer objects have no `name` (only first_name/last_name), so names are never prefilled. | Yes: `@getMainMembersData` |
| 15 | GET | `vsla-onboarding/data/savings-cycle` | `OnboardingDataModel.dart:124` (used by `VslaSavingsCycleScreen.dart:111`) | — | **`data.cycle`** → `start_date, end_date ?? expected_end_date` (DateTime.parse), `interest_frequency`, `saving_type`, `share_price, share_value, max_shares_per_member, min_shares_per_member, loan_interest_rate, meeting_frequency, registration_fee, ...` | `DateTime.parse(String)` inside a try | **Mismatch:** the backend returns cycle fields **flat** in `data` (there is no `cycle` key), so the prefill **never runs**. The backend also sends `cycle_name` = `$cycle->name`, which is always null (the baseline confirms it). | Yes: `@getSavingsCycleData` |
| 16 | GET | `vsla-onboarding/data/all` | `OnboardingDataModel.dart:145` | — | `data.chairperson, group, main_members, savings_cycle, completion_status`, `completion_percentage` (int, `?? 0`), `current_step` (int, `?? 1`) | `json['completion_percentage'] ?? 0` is assigned to an `int` | **Unused** (`fetchAllOnboardingData` has no callers). If it were used, a String number would throw. | Yes: `@getAllOnboardingData` |
| 17 | POST | `vsla-onboarding/register-admin` | `screens/vsla/VslaRegistrationScreen.dart:251` | `name`, `phone_number` (may be `''`; the app labels it optional), `email` (always `''`: the form has no email field), `national_id_number`, `password`, `password_confirmation` (4-digit PIN; **auto-generated as millis%10000 when coming from OTP**), `country='Uganda'`, `from_phone_verification` ('1'/'0') | `code`, `message`, **`data.user`** → `LoggedInUserModel.fromJson`, `data.user.remember_token` → prefs `'token'`, `data.user.id` | `resp.data['user']` (Map) | The backend **requires** `phone_number` matching `^(\+256\|0)7\d{8}$`, so a blank phone fails with a validation message. The OTP path overwrites the user's password with a random PIN the user never sees (they can only log in again through forgot-password or OTP). `data.already_onboarded` is ignored. If `data.user` is missing, fromJson(null) gives id 0 and an exception: "Registration failed". | Yes: `VslaOnboardingController@registerGroupAdmin` (public) |
| 18 | GET | `locations/districts` | `screens/vsla/VslaGroupCreationScreen.dart:99` | — | `code`, `data[]` → `e['id'].toString()`, `e['name'].toString()` | **`resp.data as List`** | If `data` becomes paginated or wrapped in a Map, this throws; the try catches it and shows "Error loading districts". `null` id or name becomes the string "null". | Yes: `ApiResurceController@locations_districts` |
| 19 | POST | `vsla-onboarding/create-group` | `VslaGroupCreationScreen.dart:256` | `name`, `description`, `meeting_frequency` (Weekly/Bi-weekly/Monthly), `establishment_date` (yyyy-MM-dd), `year_of_establishment`, `district_id`, `estimated_members` (String), `subcounty_text`, `parish_text`, `village` | `code`, `message` only | none | The response's `data.group` and `data.user` are **ignored**, so the local user's `group_id` stays empty. Only `vsla_group_manifest_service.dart:93` repairs it later, through `users/me`. The backend requires subcounty_text, parish_text and village (min 2 characters) and estimated_members between 10 and 50. | Yes: `@createVslaGroup` (EnsureTokenIsValid) |
| 20 | POST | `vsla-onboarding/register-main-members` | `screens/vsla/VslaMainMembersScreen.dart:226` | `secretary_name`, `secretary_phone`, `secretary_email`, `secretary_nin`, `treasurer_name`, `treasurer_phone`, `treasurer_email`, `treasurer_nin`, optional `send_sms='1'` | `code`, `message`, `data.sms_sent` (`== true`) | `sms_sent` must be a real bool | If `sms_sent` becomes `1` or `"1"`, the SMS note simply isn't shown (silent). | Yes: `@registerMainMembers` |
| 21 | POST | `vsla-onboarding/create-cycle` | `screens/vsla/VslaSavingsCycleScreen.dart:309` | `cycle_name`, `start_date`, `end_date` (yyyy-MM-dd), `saving_type` (shares/any_amount), `meeting_frequency`, `loan_interest_rate`, `interest_frequency` (Weekly/Monthly), `minimum_loan_amount`, `maximum_loan_multiple`, `late_payment_penalty` (all `.toString()`), `share_value` (only when saving_type is shares), `weekly_loan_interest_rate` or `monthly_loan_interest_rate` | `code`, `message` only | none | `.toString()` on a null form value sends `"null"`, which fails the backend `numeric` rule. The form fields are required, so the risk is low. | Yes: `@createSavingsCycle` |
| 22 | POST | `vsla-onboarding/complete` | `screens/vsla/VslaCompleteScreen.dart:74` | (empty body + injected user / User-Id) | `code`, `message`, `data` → stored as `Map<String,dynamic>?`. Reads `summary.group_name`, `summary.total_members`, `summary.meeting_frequency`, `summary.cycle_duration`, `group.code`, `secretary.name`, `secretary.phone_number`, `treasurer.name`, `treasurer.phone_number`, `cycle.cycle_name`, `cycle.saving_type` (`== 'any_amount'`), `cycle.share_value` (passed to `_formatNumber`) | Values are passed to `_buildInfoRow(String,String)` with `?? 'N/A'` | A non-String value (for example a numeric `group.code`) passed into a String parameter would throw a TypeError at render time. The backend returns "Onboarding already completed" (`code` 0) on a retry, so the screen shows an error with no summary. | Yes: `@completeOnboarding` (requires server step == `step_6_cycle`) |
| 23 | POST | `api/vsla-onboarding/update-step` | `models/LoggedInUserModel.dart:819` (`updateOnboardingStepWithSync`, called from steps 2-7) | `onboarding_step` (enum String) | none (only printed) | — | The URL becomes `/api/api/vsla-onboarding/update-step`, which has 2 segments and therefore does not even match the catch-all, so it **always 404s**. The server step therefore advances only through the step endpoints themselves; `step_2_terms` is never synced. Silent. | **NO (missing)**: the real route is `vsla-onboarding/update-step` (`@updateOnboardingStep`) |

### A4. User profile, roles and account management

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend route |
|---|---|---|---|---|---|---|---|---|
| 24 | GET | `users/me` | `models/LoggedInUserModel.dart:286` (`getOnlineItems`, called at startup in `OnBoardingScreen.dart:64`, `MainController.dart:40`, `AccountEdit.dart:453`, vendor screens) | — | `code`, `data`, **but only when `data` is a List** (`runtimeType.toString().contains('List')`). It then deletes all local rows and inserts each element through `fromJson`. | `data` expected to be a List | **This is effectively a no-op today:** the backend returns `data` as a single user object (Map), so nothing is refreshed. The local user only ever comes from the login / register-admin / verify-otp responses. If the backend changes `data` to `[user]`, the app starts wiping and replacing the session row. | Yes: `ApiResurceController@my_profile` (EnsureTokenIsValid) |
| 25 | GET | `users/my-roles` | `models/UserRoleModel.dart:179` (`home_tab.dart:64,127`) | — | `code`, `data.roles[]` → `slug` (or `role_slug`), `name` (or `role_name`), `description`, `id`; `data.slugs` (logging only) | `data['roles'] as List? ?? []`; `data` must be a Map | Everything is inside a try. **Access control depends on the slugs** `super_admin, ip_manager, field_facilitator, me_officer` (the profiling menu gate) and the `vsla_menu_screen.dart:55` facilitator checks. Renaming admin role slugs silently removes access. | Yes: `@my_roles` |
| 26 | POST | `users/update-profile` | `screens/account/AccountProfileScreen.dart:206` (reached from more_tab and vsla_tab) | `id`, `first_name`, `last_name`, `name`, `sex` (Male/Female), `dob` (DateTime.toString()), `national_id`, `country`, `district`, `subcounty`, `parish` (names), `village`, `address`, `occupation`, `marital_status`, `education_level`, `household_size` (String), optional `photo` (MultipartFile) | `code`, `message`, `data.first_name, last_name, name, sex, dob, national_id_number, country, address, occupation, marital_status, education_level, household_size, district_id, subcounty_id, parish_id, village, profile_photo, avatar` | `to_str` / `int_parse`, but **`_user.village = userData['village']`** is a direct assignment to a String | A non-String `village` would throw (caught, generic error). | Yes: `@users_update_profile` (EnsureTokenIsValid) |
| 27 | POST | `update-profile` | `screens/account/AccountEdit.dart:451` (from legacy `AccountSection`) | `first_name`, `last_name`, `phone_number_1`, `email`, `address` (**sends `item.name`**), optional `file` | `code`, `message` | — | **BROKEN-500:** the backend saves, then calls `success($u, msg, 1)`. HTTP status 1 throws `InvalidArgumentException` (verified), so the app shows "Failed" even though the save happened. The app also re-reads the user from the DB before submitting, discarding the user's edits. | Yes, but **BROKEN-500**: `ApiResurceController@update_profile` |
| 28 | POST | `password-change` | `screens/account/AccountChangePassword.dart:197` (legacy AccountSection) | `current_password`, `password` (+ injected `user`, which the backend uses as the user id) | `code`, `message` | — | **BROKEN-500:** the password changes, then `success($u,$msg,1)` raises HTTP 500 and the app shows "Failed". | Yes, but **BROKEN-500**: `@password_change` (no middleware; relies on the FormData `user` field) |
| 29 | POST | `account-verification` (request code) | `screens/account/account_vefication_screen.dart:45` | `username`, `email`, `task='request_verification_code'` (+ `user`) | `code`, `message` | — | Relies on the injected `user` field. | Yes: `@account_verification` |
| 30 | POST | `account-verification` (verify) | `account_vefication_screen.dart:86` | `email`, `username`, `code`, `task='verify_code'` | `code`, `message` (sets local `complete_profile='Yes'`) | — | same | Yes |
| 31 | POST | `delete-account` | `screens/full_app/section/AccountSection.dart:347` (legacy FullApp, reached only from cart flows) | `id` (int) (+ `user`) | `code`, `message` | — | **BROKEN-500:** the status is set to `Deleted`, then `success(null,msg,1)` raises HTTP 500. The app shows an error toast and **does not log out**, but the account is already deleted. | Yes, but **BROKEN-500**: `@delete_profile` |
| 32 | POST | `${AppConfig.API_BASE_URL}/user/update-profile` | `controllers/AccountController.dart:194` | `user_id`, `first_name`, `last_name`, `email`, `phone_number`, `avatar` | `response['code'] == 1` checked directly | a String response would throw (caught) | **Dead** (`updateProfile` has no callers), and the URL is malformed (`/api/https://...`). | **NO (malformed URL)** |
| 33 | GET | `insurance-users?is_not_private=1` | `models/SystemUser.dart:406` (`screens/admin/user_picker_screen.dart:37`) | query `is_not_private=1` | `data` as a List, or `data.data` as a List → `SystemUser.fromJson`: `id, created_at, updated_at, first_name, last_name, name, username, sex, dob, phone_number, email, country, address, avatar, profile_photo, user_type, status, occupation, about, title, tribe/swimming, father_name, mother_name, child_1..4, sponsor_id, referral, current_address, phone_number_2` | All parsed with `to_str` / `int_parse` inside a try | Safe | Yes: `@insurance_users` |
| 34 | GET | `insurance-users/{id}` | `SystemUser.dart:437` | — | `data` (Map) | — | Unused (`getItemById` has no callers) | Yes: `@insurance_user_show` |
| 35 | POST | `insurance-users/{id}` + `_method=DELETE` | `SystemUser.dart:456` | `_method='DELETE'` | `code` | — | Unused. Method spoofing via FormData `_method` resolves to the DELETE route. | Yes: `@insurance_user_delete` |

### A5. Profiling (facilitator / admin)

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend route |
|---|---|---|---|---|---|---|---|---|
| 36 | POST | `users/register` | `screens/profiling/universal_user_registration_screen.dart:352` (reached from `profiling_menu_screen`, gated by my-roles) | `role`, `name`, `phone_number` (may be `''`), `email`, `password`, `password_confirmation`, `sex`, `country`, `address`, `district`, `subcounty`, `parish`, `village`, plus role-specific keys (`farm_size, crops_grown, livestock_owned, services_offered, certification, years_experience, business_*, products_sold, supplier_license, equipment_types, rental_rates, coverage_area, market_type, capacity, operating_hours, storage_capacity, collection_points, value_chains, warehouse_location, handling_capacity, assigned_area, specialization, qualifications, department, permissions_level`) | `code`, `message`, **`data.user`** → `LoggedInUserModel.fromJson`, then `.remember_token` → prefs `'token'`, then `user.save()` | `resp.data['user']` | **DUP: `ApiAuthController@register` wins** (api.php:99 overrides :37). It returns `data` = the flat user (with `token` / `remember_token`), not `{user: ...}`, so `data['user']` is null. The app then saves an **empty user row with id 0** into `logged_in_user_2` (junk row; `getLoggedInUser` still ends on the real user). `role` and all profiling fields are ignored, and a blank phone is rejected. **Danger if "fixed":** if the backend made `data.user` the new user with a token, the app would overwrite the facilitator's token and add the new user's row, so the session could switch to the newly registered person. | **DUP**: `ApiAuthController@register` wins over `Api\UserRegistrationController@register` |
| 37 | GET | `profiling/users?role=&status=&search=&verified=no&` | `screens/profiling/profiling_users_list_screen.dart:88` | query built into the path string (not URL-encoded) | `code`, `data[]` (`List<Map>.from`) → `name, phone_number, email, user_type, status, phone_number_verified_at` (null check), `id` (int_parse), `business_name, village, created_at`; **top-level `summary`** (`resp.raw['summary']`) → `total, active, verified` | `data` must be a List of Maps; `summary` must be a Map | **BROKEN-500 on prod:** `index()` always computes `whereNotNull('phone_number_verified_at')`, and that column does not exist in `users` (the baseline shows 500 "Unknown column", confirmed in prod columns). The list is always empty. `summary` sits **outside** `data`, so moving it inside the envelope breaks the stats. | Yes, but **BROKEN-500**: `UserRegistrationController@index` |
| 38 | POST | `profiling/users/{id}/status` | `profiling_users_list_screen.dart:132` | `action` (verify/activate/deactivate) | `code`, `message` | — | `verify` fails with a 500 SQL error (missing column). activate and deactivate work. A 422 JSON body is handled. | Yes: `@updateStatus` (`verify` broken) |
| 39 | GET | `ffs-groups?type=<All\|FFS\|FBS\|VSLA\|Association>&search=` | `screens/profiling/groups_list_screen.dart:60` | query in the path | `code`, `data` (iterated) → `FfsGroupModel.fromJson`: `id, name, code, type, type_text, district_id (int), district_name, village, total_members, facilitator_id, facilitator_name, status, status_text, establishment_date, estimated_members, admin_id, admin_name, secretary_id, secretary_name, treasurer_id, treasurer_name, subcounty_text, parish_text, meeting_frequency, description` | `for (var item in resp.data)` needs something iterable; the parsers are safe | A paginated Map `data` would iterate keys and produce garbage or throw (caught: "Failed to load groups"). `type=All` relies on the backend's `!= 'All'` check. The endpoint is public and not IP-scoped. | Yes: `FfsGroupController@index` |

### A6. Manifest, app-config and push registration

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend route |
|---|---|---|---|---|---|---|---|---|
| 40 | GET | `manifest` | `services/manifest_service.dart:80` (background) | — | `code` (== 1, raw map), `data` → `ManifestModel.fromJson`: `user.{id,name,email,phone_number,profile_photo,user_type,member_code,member_since,member_since_full}`, `balance.{total_balance,formatted_balance,available_balance,formatted_available,pending_balance,formatted_pending}`, `recent_transactions[]` (**`as List`**) → `{id,amount,formatted_amount,transaction_date,formatted_date,description,source,source_label,type,created_at}`, `statistics.{total_transactions,total_disbursements,formatted_disbursements,total_deposits,formatted_deposits,total_withdrawals,formatted_withdrawals}`, `quick_stats.{pending_transactions,completed_transactions,monthly_transactions,today_transactions}`, `app_info.{app_version,app_build,api_version,maintenance_mode (== true),maintenance_message (String?, direct),force_update (== true),minimum_version,latest_version,update_url,support_email,support_phone}`, `features.{vsla_enabled,...,ffs_activities_enabled}` (== true), `timestamp`, `server_time` | Booleans must be real `true` (`1` or `"1"` reads as false). `maintenance_message` is assigned directly to `String?`. | Everything is inside a try. **Effectively dead:** `UserAccountDashboardScreen`, the only caller, is never navigated to. The backend identifies the user through `$_SERVER['HTTP_USER_ID']` only (not the JWT), so a request without the `User-Id` header gets "Authentication required" (the baseline harness saw `code` 0 because it sent no `User-Id`). | **DUP**: `Api\ManifestController@getManifest` (api.php:690, EnsureTokenIsValid) wins over `ApiResurceController@manifest` (:94) |
| 41 | GET | `manifest` | `manifest_service.dart:139` (`fetchFromApi`) | — | same as #40 | same | same | same |
| 42 | GET | `app-config` | `manifest_service.dart:200` | — | `code`, `data` (returned raw as a Map) | — | **Unused** (`getAppConfig` has no callers). `force_update_guard_service.dart` and `app_update_service.dart` use only the Play `in_app_update` package and read **no** backend fields. | Yes: `ManifestController@getAppConfig` |
| 43 | POST | `onesignal/register-device` | `services/OneSignalService.dart:389` (`completeSetupAfterLogin` from login, switch account) | `user_id`, `player_id`, `device_type='mobile'`, `app_version='1.0.0'` | `success` (== true), `message` | `response['success']` on the raw result | **The route does not exist.** The 404 JSON reads as "failed" and the String case is caught, so it fails silently on every login. Push still works through `OneSignal.login(externalId)`. | **NO (missing)** |

`calls.tsv` coverage: every in-scope row is listed above. `screens/profiling/ffs_group_registration_screen.dart` and `screens/vsla_profiling/*` are not in this section's scope. The `users/me` caller in `vsla_group_manifest_service.dart:93` is also outside scope, but note that it is the **only** code path that actually persists a `users/me` refresh: it calls `fromJson(data)` and `save()` when `group_id` is non-empty.

---

### Critical flow shapes

Only the keys the app reads are shown; other keys are ignored. Types are JSON types.

**POST `users/login`** (`ApiAuthController@login`; matches today). Error: HTTP 400 `{code:0, status:0, success:false, message:String, data:""}`.
```json
{ "code": 1, "message": "String",
  "data": {
    "id": "int (>0 required)",
    "remember_token": "String JWT (len>4 required; THIS is the stored token)",
    "username": "String", "first_name": "String", "last_name": "String", "name": "String",
    "phone_number": "String", "email": "String", "avatar": "String", "user_type": "String",
    "group_id": "int|String|null", "group_name": "String", "group_code": "String",
    "ip_id": "int|null", "ip_name": "String|null", "ip_short_name": "String|null",
    "is_group_admin": "\"Yes\"|\"No\"", "is_group_secretary": "\"Yes\"|\"No\"", "is_group_treasurer": "\"Yes\"|\"No\"",
    "onboarding_step": "not_started|step_1_welcome|...|step_7_complete",
    "onboarding_completed_at": "String|null",
    "...": "any other LoggedInUserModel field, all optional"
  } }
```
No mismatch today. The backend also sends `token`, which the app ignores. Do **not** hide `remember_token` (or `id`) from the serialised User.

**POST `phone-verification/check-phone` | `send-otp` | `verify-otp`** (`PhoneVerificationController`; all HTTP 200).
```json
// check-phone / send-otp
{ "status": 1, "message": "String", "data": { "user_id": "int", "name": "String", "phone_number": "String" } }
// verify-otp
{ "status": 1, "message": "String",
  "data": { "user": { "id": "int", "name": "String", "phone_number": "String", "email": "String|null",
                      "is_group_admin": "Yes|No", "group_id": "int|null",
                      "remember_token": "String (optional; currently NOT sent)" } } }
// failure: { "status": 0, "message": "String" }
```
Mismatches:
- The app needs **`status`**. A `code`-only envelope breaks the flow.
- verify-otp returns no token.
- The forwarded `chairperson_data` is the flat check-phone data, but `VslaRegistrationScreen` expects `{user, group, secretary, treasurer}`, so the prefill from arguments is always empty.

**POST `vsla-onboarding/register-admin`** (`VslaOnboardingController@registerGroupAdmin`; matches).
```json
{ "code": 1, "message": "String",
  "data": { "user": { "id": "int", "remember_token": "String JWT", "...": "LoggedInUserModel fields" },
            "token": "String (ignored)", "already_onboarded": "bool (ignored)" } }
```
Mismatch: the app treats `phone_number` as optional, but the backend requires it.

**POST `users/register`** (the app expects the `UserRegistrationController` shape, but the backend serves `ApiAuthController`).
```json
// App expects:
{ "code": 1, "message": "String", "data": { "user": { "id": "int", "remember_token": "String" } } }
// Backend actually returns (ApiAuthController@register):
{ "code": 1, "status": 1, "success": true, "message": "Account created successfully.",
  "data": { "id": "int", "token": "...", "remember_token": "...", "...": "user" } }
```
Mismatch: `data.user` is absent, so the app saves an empty row with id 0. Either shape is unsafe because the app writes the registered user into the logged-in session table.

**GET `users/me`** (`ApiResurceController@my_profile`).
```json
// What LoggedInUserModel.getOnlineItems actually needs in order to do anything:
{ "code": 1, "data": [ { "id": "int", "...": "LoggedInUserModel fields" } ] }
// What the backend returns (and vsla_group_manifest_service expects):
{ "code": 1, "data": { "id": "int", "group_id": "int", "group_name": "String", "...": "user" } }
```
Mismatch (long-standing): the main refresh path is a no-op. Keep `data` a **Map**, since the manifest service depends on it. Changing it to a List would make `getOnlineItems` start replacing the session row, and with no `remember_token` in the `users/me` response that would not lose the prefs token, but it would change behaviour.

**GET `users/my-roles`**
```json
{ "code": 1, "data": { "roles": [ { "slug": "super_admin|ip_manager|field_facilitator|me_officer|vsla_treasurer|farmer_member|content_manager", "name": "String", "description": "String" } ], "slugs": ["String"] } }
```

**POST `vsla-onboarding/create-group` | `register-main-members` | `create-cycle`** (the app reads only the envelope).
```json
{ "code": 1, "message": "String", "data": { "sms_sent": "bool (register-main-members only)" } }
```
Server-side preconditions:
- create-group: `is_group_admin == 'Yes'`.
- register-main-members and create-cycle: the user must already have `group_id`.

**POST `vsla-onboarding/complete`** (matches). The server requires `onboarding_step == 'step_6_cycle'`.
```json
{ "code": 1, "message": "String",
  "data": { "summary": { "group_name": "String", "total_members": "int|String", "meeting_frequency": "String", "cycle_duration": "String" },
            "group": { "code": "String" },
            "secretary": { "name": "String", "phone_number": "String" } ,
            "treasurer": { "name": "String", "phone_number": "String" },
            "cycle": { "cycle_name": "String", "saving_type": "shares|any_amount", "share_value": "num|String" } } }
```

**GET `vsla-onboarding/data/savings-cycle`**: the app expects `{ "data": { "cycle": { "start_date": "yyyy-mm-dd", "end_date": "...", "share_value": "...", "...": "..." } } }`. The backend returns the fields flat under `data`, so the prefill is dead (mismatch).

**GET `manifest`** (`ManifestController@getManifest`; matches, but the caller is dead).
```json
{ "code": 1, "data": { "user": {"id":"int","name":"String"}, "balance": {"total_balance":"num","formatted_balance":"String"},
  "recent_transactions": [ {"id":"int","amount":"num","type":"String"} ],
  "statistics": {}, "quick_stats": {},
  "app_info": {"maintenance_mode":"bool","maintenance_message":"String|null","force_update":"bool","minimum_version":"String","latest_version":"String","update_url":"String"},
  "features": {"vsla_enabled":"bool"}, "timestamp": "String", "server_time": "String" } }
```

---

### Broken / missing routes

1. **`POST api/vsla-onboarding/update-step`** (`LoggedInUserModel.dart:819`) resolves to `/api/api/vsla-onboarding/update-step`, which is a 404 on every onboarding step. The intended route is `vsla-onboarding/update-step`.
2. **`POST onesignal/register-device`** (`OneSignalService.dart:389`): no backend route. It fails silently on every login.
3. **`POST users/register`** is declared twice (api.php:37 `UserRegistrationController`, api.php:99 `ApiAuthController`). The later one wins, so the response shape mismatches what `universal_user_registration_screen.dart:368` expects.
4. **`GET manifest`** is declared twice (api.php:94 `ApiResurceController@manifest`, api.php:690 `ManifestController@getManifest` with EnsureTokenIsValid). `ManifestController` wins, and its shape matches the app.
5. **BROKEN-500, verified:** `ApiResponser::success(..., 1)` passes `1` as the HTTP status, so Symfony throws `InvalidArgumentException: The HTTP status code "1" is not valid` **after** the data has been saved:
   - `POST update-profile` (`ApiResurceController@update_profile`, used by `AccountEdit.dart:451`)
   - `POST password-change` (`@password_change`, used by `AccountChangePassword.dart:197`)
   - `POST delete-account` (`@delete_profile`, used by `AccountSection.dart:347`)
6. **BROKEN-500 (schema):** `GET profiling/users` always fails with "Unknown column `phone_number_verified_at`" (confirmed in the baseline and in the prod column list). `POST profiling/users/{id}/status` with `action=verify` fails the same way.
7. Dead or malformed call sites; there is no impact unless they are revived:
   - `${API_BASE_URL}/user/update-profile` (`AccountController.dart:194`)
   - `/auth/login` (`AppStateController.dart:191`)
   - `/api/check-phone|send-otp|verify-otp` (`phone_verification_api.dart`)
   - `users/login` with `phone_number` (`login_screen_old.dart:39`)
   - `vsla-onboarding/data/all` (unused)
   - `app-config` (unused)
   - `manifest` (its only caller screen is unreachable)

---

### Top fragility risks (ranked)

1. **The session token is taken only from `data.remember_token`**: `screens/account/login_screen.dart:73-75`, `switch_account_sheet.dart:113-115`, `VslaRegistrationScreen.dart:279-281`. Adding `remember_token` to the User model's `$hidden`, renaming it, or returning only `token` means no token is ever stored. Every cold start would then hit `OnBoardingScreen.dart:74` (`token.length < 10`) and log the user out. `data.id` must also stay present and greater than 0 (`login_screen.dart:88`).
2. **Login and identity depend on the `User-Id` header and the injected FormData `user` field.** `EnsureTokenIsValid` trusts the `User-Id` header before the JWT. `password-change`, `account-verification`, `delete-account` and `update-profile` read `$request->user`, which is the FormData field Utils injects. OTP sign-up has no token until `register-admin` (`OTPVerificationScreen.dart:88`). Tightening auth to JWT-only would break OTP onboarding steps 3-7 for users without a token, and every endpoint keyed on `user`.
3. **Phone-OTP responses use `status`, not `code`** (`PhoneVerificationScreen.dart:51,85`, `OTPVerificationScreen.dart:80,127`, checked on the raw response). Normalising `PhoneVerificationController` to an envelope with `code` only would silently break group-admin self-registration.
4. **`users/register` duplicate and session overwrite** (`universal_user_registration_screen.dart:352-376`). Today it saves a junk id 0 row. If the duplicate is resolved in favour of `UserRegistrationController` and a token-bearing `data.user` is returned, the facilitator's token in prefs and the logged-in row can be **replaced by the newly registered user**. Any change here needs a coordinated app fix.
5. **`users/me` must remain a Map.** `LoggedInUserModel.dart:298` only acts on a List and is currently a no-op. `vsla_group_manifest_service.dart:93` requires a Map with `group_id`. Changing it either way alters behaviour: a List would wipe and replace the session row; anything else stops the group_id repair. The `group_id`, `group_name` and `onboarding_step` values from login are the only source of truth for routing chairpersons into onboarding.
6. **Three endpoints throw HTTP 500 after saving** (`ApiResurceController` lines 304, 809 and the `password_change` return). Users see "Failed" while the change, or even the account deletion, has already happened (`AccountSection.dart:347` does not log out). Fixing the status code is safe for the app, because RespondModel then reads `code` = 1.
7. **The profiling users list is dead in prod** (`profiling_users_list_screen.dart:88`). It also reads the top-level `summary` through `resp.raw['summary']` (line 93), so a fix must keep `summary` outside `data` and keep `data` as a List.
8. **`my-roles` slugs gate features** (`UserRoleModel.dart:164-171`, `vsla_menu_screen.dart:55`, `profiling_menu_screen.dart:31`). Renaming admin role slugs, or returning `roles` outside `data`, silently removes profiling and facilitator access.
9. **The onboarding step sync always 404s** (`LoggedInUserModel.dart:819`). The server's `onboarding_step` advances only inside the register-admin, create-group, register-main-members, create-cycle and complete endpoints. `complete` requires the server step to equal `step_6_cycle` (backend `completeOnboarding`). Do not remove the step side-effects from those controllers.
10. **Onboarding prefill mismatches.** None of these crash today:
    - savings-cycle: the app wants `data.cycle`, the backend sends fields flat (`VslaSavingsCycleScreen.dart:113`).
    - chairperson and main-members: the backend sends `first_name`/`last_name`, the app reads `name`.
    - `data.secretary` / `data.treasurer` are assigned to typed Maps outside the try (`VslaMainMembersScreen.dart:65-77`), so returning `[]` instead of `null` would crash the screen.
    - `locations/districts` uses `resp.data as List` (`VslaGroupCreationScreen.dart:104`); wrapping or paginating it breaks group creation.

---

## 3. VSLA Core

Scope: the group-member side of VSLA (meetings and offline sync, group manifest, cycles, loans, transactions, shareout, opening balances, social fund, group stats, attendance, user-loans, group config/reset, member delete, membership gating). There are **78 call sites** in calls.tsv for these files, and all of them are covered below. Mobile paths are relative to `lib/`. Backend paths are relative to `app/Http/Controllers/` unless shown otherwise.

Conventions used in the tables:
- **Envelope**: `Utils.http_get/post/put/delete` return the decoded JSON. On a non-2xx response with a JSON body, they return that body as a Map, so 4xx/5xx bodies reach the caller like a 200 would. POST/PUT/DELETE return a **String** on network failure or a non-JSON body. "direct `code==1`" means the code does `response['code'] == 1` without RespondModel. That expression throws on a String, so the result depends on the surrounding `try`.
- **FormData encoding (POST)**: Dio 5.8 `FormData.fromMap` uses `ListFormat.multi` (checked in `~/.pub-cache/.../dio-5.8.0+1/lib/src/utils.dart:82-95`):
  - A List of Maps becomes `key[0][sub]=…`, which PHP parses back into nested arrays.
  - `bool` is sent as the strings `"true"`/`"false"`.
  - `null` is sent as `""`, which `ConvertEmptyStringsToNull` turns back into null.
  - **An empty list sends no field at all.**
  - A List of primitives repeats the key without brackets, so PHP keeps only the last value.
- **PUT** sends a JSON body. **GET** query values go through Dio `queryParameters` unless the path already carries `?…`.
- "Backend" column: **Yes** means the route resolves (Controller@method). The other values are **NO (404)**, **NO (405 method)** and **SHADOWED**.

---

### Meetings & Sync

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| M1 | POST | `vsla-meetings/submit` | services/vsla_meeting_sync_service.dart:64 | FormData. Full payload in "Critical flow shapes" §1 | `success`, `code`, `message`, `meeting_id`, `processing_status`, `warnings`, `meeting_data`, `errors` (all top-level, **not** under `data`) | `success == true` (bool, strict). `Utils.int_parse(code) == 409` combined with `meeting_id != null` means duplicate. `warnings`/`errors` cast `as List` in screens (MeetingSummaryScreen.dart:272/284, VslaMeetingsHubScreen.dart:980/997) and items read as `w['message']` | Wrapped in try/catch: a String return becomes `success:false` and the meeting stays queued. **If `success` is renamed or becomes 1/"true", every submit counts as a failure → endless retries; the backend's local_id dedup (409) is the only thing preventing duplicates.** If `errors`/`warnings` become a Map (e.g. Laravel's default `$request->validate()` 422 `{errors:{field:[..]}}`), `as List` throws in the screen and the user only sees "Error: …". `VslaSyncManager` reads `result['data']?['id']`, which the service never returns, so `serverMeetingId` is always null (vsla_sync_manager.dart:155) | **Yes** — Api\VslaMeetingController@submit |
| M2 | GET | `projects/{cycleId}` | vsla_meeting_sync_service.dart:144 | none | `data` (Map) → `Project.fromJson` | `data != null` only. Project.fromJson: `id ?? 0` (int), `total_shares ?? 0` (int), `shares_sold ?? 0` (int), `group_id` (int?), `maximum_loan_multiple` (int?), `is_vsla_cycle`/`is_active_cycle` (String "Yes"/"No"), `start_date ?? ''` (String), `created_by` raw, `available_for_purchase ?? false` (bool) | Non-blocking: errors are swallowed and `group_id` falls back to `meeting.groupId`. If int columns come back as numeric strings or decimals, the TypeError is caught → null | **Yes** — ProjectController@show. Returns `{success:true,data:Project}` with **no `code` key** |
| M3 | GET | `vsla/meetings?cycle_id&status&search&sort_by&sort_order` (query built into the path string) | services/vsla_meeting_service.dart:40 | query: `cycle_id`(str), `status`, `search`, `sort_by`, `sort_order` | `code`, `message`, `data[]` → VslaMeeting.fromJson; `total`, `current_page`, `last_page`, `per_page` (optional) | `code: json['code'] ?? 0` assigned to an **int** field (a "1" string → TypeError → caught → empty list). If `data` is not a List, the list is silently empty. VslaMeeting uses `tryParse` everywhere (safe) | Paginating `data` into `{data:[…]}` would silently produce an empty list | **Yes** — VslaMeetingController@index (`vsla/meetings` group, api.php:348) |
| M4 | GET | `vsla/meetings/{id}` | vsla_meeting_service.dart:56 | none | `code`, `message`, `data` (Map) → VslaMeeting.fromJson incl. `attendance_data`/`transactions_data`/`loans_data`/`loan_repayments_data`/`social_fund_contributions_data`/`share_purchases_data`/`previous_action_plans_data`/`upcoming_action_plans_data` (each `is List` or `[]`) | as M3 | safe | **Yes** — @show |
| M5 | GET | `vsla/meetings/stats?cycle_id=` | vsla_meeting_service.dart:74 | `cycle_id` | `code == 1` (direct), `data` cached raw (vsla_offline_data_service.dart:741) | raw Map passthrough | — | **Yes** — @stats → `data{total_meetings,total_savings,completed_meetings,cancelled_meetings}` |
| M6 | DELETE | `vsla/meetings/{id}` | vsla_meeting_service.dart:88 | (FormData user/User-Id/user_id) | raw response returned | return type `Map<String,dynamic>`; a String error return throws inside the try → caught | — | **Yes** — @destroy (admin-only, 403 otherwise) |
| M7 | GET | `vsla/meetings/{id}/export-pdf` | vsla_meeting_service.dart:102 | none | raw response returned | expects JSON Map | Returns a binary PDF on success; http_get returns non-Map data and the caller (VslaMeetingDetailsScreen:62) must cope | **Yes** — @exportPdf (registered after `/{id}`, but `/{id}/export-pdf` has two segments, so it is not shadowed) |

Local queue (no HTTP of its own): `VslaOfflineMeetingService` keeps meetings in SQLite table `vsla_offline_meetings` with `submission_status` ∈ {pending, ready, submitted}. `getPendingMeetings()` feeds `VslaSyncManager._syncReadyToSubmitMeetings` (statuses ready|pending). After `success == true` (or 409 with `meeting_id`), the sync service calls `updateSubmissionStatus(status:'submitted')`. Submitted rows are kept for 30 days as a same-day duplicate guard (vsla_offline_meeting_service.dart:143-153). `VslaMeetingsHubScreen` deletes the local row instead (line ~966).

### Manifest

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| F1 | GET | `users/me` | services/vsla_group_manifest_service.dart:93 | none | `code == 1` (direct), `data` → `LoggedInUserModel.fromJson`, `data.group_id`, `data.group_name` | group_id treated as a String (`isEmpty`, `!= '0'`) | Only runs when the local `group_id` is empty. Exceptions are swallowed | **Yes** — ApiResurceController@my_profile |
| F2 | GET | `vsla/cycles` | vsla_group_manifest_service.dart:120 | none | `code == 1`, `data.group_id`, `data.group_name` | `.toString()` | fallback only | **Yes** — Api\VslaConfigurationController@getCycles |
| F3 | GET | `vsla/groups/{group_id}/manifest` | vsla_group_manifest_service.dart:153 | none | `code == 1` (direct), `data` → `VslaGroupManifest.fromJson` (full key list in §2) | See §2. `manifest.groupId = int.parse(user.group_id)` (local value, throws if it is non-numeric → caught → null) | **Any TypeError anywhere in the nested parse discards the whole manifest** (fetchFromApi returns null). The stale cache stays in SQLite `vsla_group_manifest` (single row id=1, `json_data` = app-side `toJson()`) | **Yes** — Api\VslaGroupManifestController@getManifest |
| F4 | GET | `vsla/groups/{group_id}/manifest/incremental` | vsla_group_manifest_service.dart:225 | query `since` = `lastSyncedAt.toIso8601String()` (UTC, e.g. `2026-10-31T09:00:00.000Z`) or `''` | `code == 1`, `data.has_updates` | `== true` strict bool | If `since == ''` the backend returns 400 (code 0) and the app returns the local cache (OK). If `has_updates` becomes 1/"true", the app never refreshes through this path | **Yes** — @getIncrementalUpdates |

### Cycles

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| C1 | GET | `vsla/cycles` | services/vsla_cycle_service.dart:41 (via VslaOfflineDataService.getCycles → cache key; consumed by VslaMeetingsHubScreen:116-146, MeetingBasicInfoScreen:62-80, MeetingSharePurchasesScreen:81-90, CyclesScreen:55-81, OfflineSyncScreen:236) | none | `code == 1`; `data.cycles[]`, `data.active_cycle`, `data.group_name`; per cycle through `parseCycleData`: `id`, `cycle_name`, `start_date`, `end_date`, `status`, `is_active_cycle`, `progress_percentage`, `saving_type`, `share_value`, `meeting_frequency`, `loan_interest_rate`, `interest_frequency`, `weekly_loan_interest_rate`, `monthly_loan_interest_rate`, `minimum_loan_amount`, `maximum_loan_multiple`, `late_payment_penalty`, `created_at`, `updated_at` | `is_active_cycle == true` (**bool required**, the string "Yes" gives false). `data as Map<String,dynamic>` (VslaMeetingsHubScreen:119/135). `(data['cycles'] as List?)`. `data['group_name'] ?? ''` assigned to a String | **Meetings are blocked when `data.active_cycle` is null** ("No active cycle found", VslaMeetingsHubScreen:148). The backend leaves `active_cycle` null when the computed status is `Completed` (end_date passed) even if `is_active_cycle='Yes'`, yet `vsla-meetings/submit` would still accept that cycle. Cached raw in `vsla_cache`, so shape drift persists offline | **Yes** — @getCycles |
| C2 | POST | `vsla/cycles` | vsla_cycle_service.dart:11 (CreateCycleScreen:236-253) | `cycle_name`(str), `start_date`/`end_date`(yyyy-MM-dd), `saving_type`(shares\|any_amount), `meeting_frequency`(Weekly\|Bi-weekly\|Monthly), `loan_interest_rate`(double), `interest_frequency`(Weekly\|Monthly), `minimum_loan_amount`(double), `maximum_loan_multiple`(int), `late_payment_penalty`(double), `share_value`(double, only when shares) | `code == 1`, `data`, `message` | direct `code==1` inside a try | 422 `data` is the validator errors Map; the app shows only `message` | **Yes** — @createCycle (enums match backend) |
| C3 | PUT | `vsla/cycles/{cycleId}` | vsla_cycle_service.dart:75 (CycleDetailScreen:136-166) | JSON: the C2 keys plus `weekly_loan_interest_rate`, `monthly_loan_interest_rate`, optional `start_date`/`end_date` | `code == 1`, `data`, `message` | as C2 | — | **Yes** — @updateCycle |
| C4 | PUT | `vsla/cycles/{cycleId}/status` | vsla_cycle_service.dart:175 (CycleDetailScreen:1010) | JSON `{is_active_cycle: "Yes"\|"No"}` | `code == 1`, `data`, `message` | — | — | **Yes** — @updateCycleStatus (`match put,patch`) |
| C5 | GET | `vsla/cycles` | screens/vsla/configurations/OpeningBalancesEntryScreen.dart:92 | none | `code != 1` check, `data` `as Map<String,dynamic>?`, `data.active_cycle` (Map), `data.cycles[]` with `is_active_cycle == true` or `status == 'Active'`, `id`, `cycle_name`, `group_id`, `data.group_id` | Utils.int_parse / to_str (safe) | safe | **Yes** — @getCycles |

### Loans

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| L1 | GET | `vsla/loans` | services/vsla_loan_api.dart:18 (VslaOfflineDataService.getLoans, LoanRequestsScreen via getPendingApprovals) | query `cycle_id`(str), `borrower_id`, `status` (`pending`/`overdue`) | `code == 1`, `data` **as List** → VslaLoan.fromJson | VslaLoan: `id ?? 0` int, `loan_number ?? ''`, `cycle_id ?? project_id ?? 0` int, `borrower_id ?? 0` int, `borrower.name ?? borrower_name`, `loan_amount`/`principal_amount` (tryParse), `interest_rate` (tryParse), `interest_frequency ?? 'per annum'` (String), `duration_months ?? loan_duration_months ?? 12` (**int**), `purpose` String?, `total_amount_due`/`total_repayable`, `amount_paid`, `balance`/`balance_remaining` (tryParse), `status ?? 'active'`, `request_date`/`actual_disbursement_date`/`expected_completion_date`/`last_payment_date`/`approval_date`/`created_at` (String?), `payment_progress` (tryParse), `is_overdue` (**bool?**), `days_overdue` (**int?**), `late_fee`, `repayments` **as List** | A TypeError (e.g. `duration_months` as "6" or 6.0, `is_overdue` as 0/1) → caught → `[]` (the loans list silently goes empty). `data` paginated → empty. The backend does not return `cycle_id`, so `projectId` is 0 | **Yes** — Api\VslaLoansController@index. Non-admins only see their own loans |
| L2 | GET | `vsla/loans/{id}` | vsla_loan_api.dart:36 | none | `code == 1`, `data` → VslaLoan.fromJson incl. `repayments[]` → VslaLoanRepayment | VslaLoanRepayment: `id`/`loan_id`/`payment_number` (int, `?? `), `payment_method ?? 'cash'`, `amount`/`principal_paid`/`interest_paid`/`late_fee_paid`/`balance_before`/`balance_after` (tryParse), `payment_date ?? ''` (String), `receipt_number ?? ''` | as L1 → null | **Yes** — @show |
| L3 | POST | `vsla/loans` | vsla_loan_api.dart:58 (LoanRequestFormScreen:324) | `project_id`, `borrower_id`, `amount`, `purpose`, `term_months`, `interest_rate` | `code == 1`, `message`, `data` → VslaLoan | direct `responseData['code']` (a String return → caught) | **Broken. The caller ignores the result and always toasts "Loan request submitted successfully"** (LoanRequestFormScreen.dart:324-334). Users believe a request was created | **NO (405)** — only GET `vsla/loans` exists |
| L4 | POST | `vsla/loans/{id}/approve` | vsla_loan_api.dart:91 (LoanRequestDetailsScreen:48) | `{}` | `code`, `message` | — | always "Failed" | **NO (404)** |
| L5 | POST | `vsla/loans/{id}/reject` | vsla_loan_api.dart:115 (LoanRequestDetailsScreen:133) | `reason` | `code`, `message` | — | always "Failed" | **NO (404)** |
| L6 | POST | `vsla/loans/{id}/disburse` | vsla_loan_api.dart:141 (no caller in lib/) | `{}` | `code`, `message` | — | dead | **NO (404)** |
| L7 | POST | `vsla/loans/{id}/repayments` | vsla_loan_api.dart:170 (LoanRepaymentFormScreen:455) | `amount`(double), `payment_method`(str), `payment_date`(str) | `code == 1`, `message`, `data` → VslaLoanRepayment.fromJson | lenient `??` defaults | The backend returns `data{loan_id,amount,payment_method,payment_date,new_balance,amount_paid,status}`, so `id`/`payment_number`/`receipt_number` are defaults. Validation uses `$request->validate()` inside `catch(\Exception)` → 500 "Error recording payment: The given data was invalid." (code 0, the app copes) | **Yes** — @recordRepayment |
| L8 | GET | `vsla/loans/{id}/repayments` | vsla_loan_api.dart:202 (no caller) | none | `data as List` | — | dead | **NO (405)** — only POST is defined |
| L9 | GET | `vsla/loans/{id}/statement` | vsla_loan_api.dart:221 (no caller) | none | `data as List` | — | dead | **NO (404)** |
| L10 | POST | `vsla/loans/check-eligibility` | vsla_loan_api.dart:253 (LoanRequestFormScreen:113) | `user_id`, `project_id`, `amount` | `code == 1`, `data` → LoanEligibility `{eligible ?? false (bool), reason ?? '', max_amount (tryParse)}` | — | Returns null, so eligibility is never shown. The form is not blocked by it | **NO (405)** — URI matches GET `vsla/loans/{id}` |
| L11 | GET | `projects/{projectId}` | screens/vsla/LoanRequestScreen.dart:96 | none | `code == 1`, `data` → Project | — | **`code` is never present** (ProjectController@show returns `{success,data}`), so `_project` is never set → `_maxLoanAmount = 0` → every loan is rejected "exceeds maximum (UGX 0)". **The screen is unreachable** (no navigation to `LoanRequestScreen(` in lib/) | **Yes** — ProjectController@show (shape mismatch) |

### Transactions

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| T1 | POST | `vsla/transactions/saving` | services/vsla_transaction_api.dart:31 (offline queue vsla_offline_queue_service.dart:311) | `user_id`(int), `project_id`(int), `amount`(double), `description`, `transaction_date?` | → `VslaTransactionResponse.fromJson(response)`: `code == 1 \|\| success == true`, `message ?? ''`, `data` **as Map<String,dynamic>?**, `data.user_transaction`, `data.group_transaction` → VslaTransaction, `data.user_balances` → VslaUserBalance, `data.group_balances` → VslaGroupBalance | `fromJson(Map)`: a String return → TypeError → caught → success false. On errors the backend returns `data: null`, which is OK. VslaTransaction: `id ?? 0` int, `project_id ?? 0` int, `created_by_id ?? 0` int, `owner_id ?? 0` int, `transaction_date ?? ''` String, `contra_entry_id` int?, `is_contra_entry` (1/true/'1'), `project`/`creator` raw | **If the server succeeds but parsing throws (e.g. `owner_id` as a string, `data` becoming a List), the queue marks it failed and retries up to 3× → duplicate savings records** | **Yes** — Api\VslaTransactionController@recordSaving (201) |
| T2 | POST | `vsla/transactions/loan-disbursement` | vsla_transaction_api.dart:62 (queue :321, LoanRequestScreen:171) | T1 + `interest_rate` | as T1 | as T1 | as T1 | **Yes** — @disburseLoan |
| T3 | POST | `vsla/transactions/loan-repayment` | vsla_transaction_api.dart:91 (queue :332) | as T1 | as T1 | as T1 | as T1 | **Yes** — @recordLoanRepayment |
| T4 | POST | `vsla/transactions/fine` | vsla_transaction_api.dart:119 (queue :342) | as T1 | as T1 | as T1 | as T1 | **Yes** — @recordFine |
| T5 | POST | `vsla/transactions/create` | screens/vsla/AddTransactionScreen.dart:325 | `user_id`, `project_id`, `transaction_type` ∈ {saving, fine, charge, welfare, social_fund, loan_repayment}, `amount`(double), `description`, `transaction_date` | `code == 1` (direct), `message` | direct (a String → caught → "Error: …" toast) | **Offline path queues these types (AddTransactionScreen.dart:302), but the queue only syncs saving/loan_disbursement/loan_repayment/fine. `charge`/`welfare`/`social_fund` hit "Unknown transaction type" (vsla_offline_queue_service.dart:353) and are never synced (data loss after 3 retries).** The online path is fine | **Yes** — @createTransaction (enum matches) |
| T6 | GET | `vsla/transactions/member-balance/{userId}?project_id=` | vsla_transaction_api.dart:145 (AddSavingsScreen:85, LoanRepaymentScreen:69, OfflineSyncScreen:194, LoanRequestScreen:73) | `project_id` in path | `code == 1`, `data.savings`, `data.loans`, `data.fines`, `data.interest`, `data.net_position` | tryParse (safe) | **Mismatch: the backend nests these under `data.balances.*`**, so every value shows **0.0** (no crash) | **Yes** — @getMemberBalance |
| T7 | GET | `vsla/transactions/group-balance/{groupId}?project_id=` | vsla_transaction_api.dart:168 | — | `data.cash`, `data.total_savings`, `data.loans_outstanding`, `data.fines_collected` | tryParse | **Mismatch: the backend uses `data.balances.*`**, so all values are 0 | **Yes** — @getGroupBalance |
| T8 | GET | `vsla/transactions/member-statement?user_id&project_id&account_type&start_date&end_date&page&per_page` | vsla_transaction_api.dart:210 (TransactionsListScreen:105, OfflineSyncScreen:190) | as listed (the backend ignores page/per_page/start/end and uses `limit`) | `data` **as List** → VslaTransaction | `as List` | **Mismatch: the backend returns `data{transactions[],balances,count}`**. `as List` throws → caught → **always `[]`** (empty transaction list) | **Yes** — @getMemberStatement |
| T9 | GET | `vsla/transactions/group-statement?group_id&…` | vsla_transaction_api.dart:249 (TransactionsListScreen:122) | as T8 with `group_id` | `data as List` | — | **Same mismatch → always empty** | **Yes** — @getGroupStatement |
| T10 | GET | `vsla/transactions/recent?project_id&group_id&limit` | vsla_transaction_api.dart:275 (OfflineSyncScreen:198) | — | `data as List` | — | **Same mismatch (`data.transactions`) → always empty** | **Yes** — @getRecentTransactions |
| T11 | GET | `vsla/dashboard?group_id&cycle_id` | vsla_transaction_api.dart:303 (VslaOfflineDataService.getDashboard) | — | `code == 1`, `data` (raw Map passthrough, cached) | — | consumers outside scope | **Yes** — Api\VslaDashboardController@getDashboard |
| T12 | GET | `vsla/transactions/dashboard-summary?user_id&project_id&group_id` | vsla_transaction_api.dart:331 (no caller found) | — | `data.user_balances`, `data.group_balances` | — | The backend returns `data.overview{…}` → zeros | **Yes** — @getDashboardSummary (shape mismatch, dead) |
| T13 | GET | `vsla/transactions/group-savings?group_id&project_id&limit` | vsla_transaction_api.dart:363 (GroupSavingsScreen:52, OfflineSyncScreen:260) | — | `code == 1`, `data.summary{total_inflow,total_outflow,net_cash_flow,inflow_count,outflow_count,total_count}`, `data.balances{cash,total_savings,loans_outstanding,fines_collected}`, `data.inflow/outflow{transactions[],categories[]}`; txn: `id, amount, amount_signed, formatted_amount, description, account_type, source, source_label, type, type_label, transaction_date, formatted_date, owner_type, owner_id, owner_name, created_by, is_contra_entry, contra_entry_id`; category: `label, amount, percentage` | `inflow_count ?? 0` → int; `id ?? 0`/`owner_id ?? 0` → int; `(… as List? ?? [])` | If `transactions` becomes a JSON object (non-sequential PHP keys after a filter) → TypeError → null | **Yes** — @getGroupSavings (shape matches) |

### Shareout (6-step wizard)

All paths use `_baseEndpoint = 'vsla/shareouts'`.

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| S1 | GET | `vsla/shareouts/available-cycles` | services/vsla_shareout_service.dart:23 | none | `code == 1`; `data` (List) **or** `data.data`/`data.cycles` → AvailableCycle: `cycle_id, cycle_name, group_id, group_name, start_date, end_date, share_value, has_existing_shareout (== true), existing_shareout_id, existing_shareout_status` | all tryParse/toString | tolerant | **Yes** — Api\VslaShareoutController@getAvailableCycles |
| S2 | POST | `vsla/shareouts/initiate` | :79 | `cycle_id` (int) | `code == 1`, `data.shareout_id` (int or numeric String), `data.status` | handles int/String | FormRequest 422 → `{message, errors}` with no `code` → failure with message (OK). An existing shareout returns code 1 with the existing id (OK) | **Yes** — @initiateShareout |
| S3 | POST | `vsla/shareouts/{id}/calculate` | :133 | `{}` | `code == 1`, `data.summary` → VslaShareoutSummary | — | — | **Yes** — @calculateDistributions |
| S4 | GET | `vsla/shareouts/{id}/distributions` | :171 | — | `code == 1`, `data.distributions` **as List** → MemberDistribution, `data.total_members` | MemberDistribution reads `contributions{savings,shares_count,shares_value,fines_paid,welfare_contribution}`, `entitlements{share_percentage,proportional_distribution,loan_interest_share,fine_share,total_entitled}`, `deductions{outstanding_loan_principal,outstanding_loan_interest,outstanding_loan_total,total_deductions}`, `distribution_id, member_id, member_name, final_payout, payment_status, payment_status_label, is_payable (== true)` | If a nested group became `[]` (PHP empty array) → `List['savings']` TypeError → the whole call fails | **Yes** — @getMemberDistributions |
| S5 | GET | `vsla/shareouts/{id}/summary` | :212 | — | `code == 1`, `data` → VslaShareoutSummary; `data.cycle_info`, `data.group_info`, `data.distribution_stats` (raw) | VslaShareoutSummary: `shareout_id, cycle_name, group_name, status, status_label, shareout_date, total_savings, total_share_value, total_loan_interest_earned, total_fines_collected, total_distributable_fund, total_outstanding_loans, total_actual_payout, total_members, total_shares, share_unit_value, final_share_value, can_recalculate/can_approve/can_complete/can_cancel (== true), distributions_count, paid_distributions_count` (all tryParse) | `can_*` must stay strict bools or the wizard buttons disappear | **Yes** — @getShareoutSummary |
| S6 | POST | `vsla/shareouts/{id}/approve` | :253 | `notes?` | `code == 1`, `data.status` | — | — | **Yes** — @approveShareout |
| S7 | POST | `vsla/shareouts/{id}/complete` | :281 | `{}` | `code == 1`, `data.status`, `data.cycle_closed == true` | — | — | **Yes** — @completeShareout |
| S8 | GET | `vsla/shareouts/{id}` | :309 | — | `code == 1`, `data.shareout` → Summary, `data.distributions` as List | — | — | **Yes** — @getShareout |
| S9 | POST | `vsla/shareouts/{id}/cancel` | :350 | `{}` | `code == 1`, `message` | — | — | **Yes** — @cancelShareout |
| S10 | GET | `vsla/shareouts/history` | :376 | — | `code == 1`, `data` **as List** → VslaShareoutSummary | — | `history` is declared before `{shareout_id}` (not shadowed). Cached as `toJson()` list in `vsla_cache` | **Yes** — @getShareoutHistory |

### Opening balance

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| O1 | GET | `vsla/opening-balance/status` | screens/vsla/configurations/OpeningBalancesEntryScreen.dart:185 | `project_id` (int) | `code == 1`, `data.submitted == true` | strict bool | If it becomes 1 or "Yes", the app re-shows the entry form and the submit then gets 409 | **Yes** — Api\VslaOpeningBalanceController@getStatus |
| O2 | GET | `vsla/opening-balance/members` | OpeningBalancesEntryScreen.dart:205 | `project_id` | `code == 1`, `data.project_id`, `data.project_name`, `data.group_id`, `data.members[]` (`id`, `name`, `member_code`) | Utils.int_parse/to_str; `Map.from(m as Map)` | safe | **Yes** — @getMembers |
| O3 | GET | `vsla/group-members` | OpeningBalancesEntryScreen.dart:245 | `project_id` | `code == 1`, `data` (List) of `{id,name,member_code}` | `is List` check | safe | **Yes** — VslaTransactionController@getGroupMembers |
| O4 | POST | `vsla/opening-balance/submit` | services/opening_balance_offline_service.dart:202 (OpeningBalancesReviewScreen:96, VslaSyncManager) | `group_id`(int), `cycle_id`(int), `notes`(str), **`members` = JSON string** of `[{member_id:int, name, member_code, total_shares:double, share_count:double, total_loan_amount:double, loan_balance:double, total_social_fund:double}]` | `code == 1`, `message`, `data` (passthrough); on failure `errors` (Map field→[msgs]) | handles a Map or String response (`json.decode` on a String; a non-JSON String throws → caught → `server_error:true`) | 409 "already submitted" counts as failure; the offline row retries 3× and is never marked synced | **Yes** — @store (decodes the string) |

### Social fund

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| SF1 | POST | `social-fund/transactions` | screens/vsla/AddSocialFundTransactionScreen.dart:149 | `group_id`, `cycle_id`, `member_id`(int), `transaction_type` ∈ {contribution, withdrawal}, `amount`(double), `reason`, `description` (null → ""), `transaction_date`(yyyy-MM-dd) | `success == true`, `message` | `response['success']` direct (a String → caught → "Error:" toast) | **Offline path queues `social_fund_contribution`/`social_fund_withdrawal` into VslaOfflineQueueService, which has no handler → never synced** (vsla_offline_queue_service.dart:353) | **Yes** — Api\SocialFundTransactionController@store (`success` only, no `code`) |
| SF2 | GET | `social-fund/balance` | services/vsla_offline_data_service.dart:1035 | `group_id`, `cycle_id` | `success == true`, `data` **as Map<String,dynamic>?** (cached as `balance`) | — | — | **Yes** — @getBalance `data{balance,total_contributions,total_withdrawals,transaction_count}` |
| SF3 | GET | `social-fund/transactions` | vsla_offline_data_service.dart:1044 | `group_id`, `cycle_id`, `per_page=100` | `success == true`, `data.transactions` → `List<Map>.from` | — | The backend validates `per_page ≤ 100` (OK) | **Yes** — @index `data{transactions[],balance,pagination}` |

### Attendance (and member lookups)

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| A1 | GET | `vsla/attendance` | services/attendance_service.dart:35 | `search`, `sort_by`, `sort_order`, `status`, `meeting_id`, `member_id` | `code` (assigned to **int**), `message`, `data` (List) → Attendance: `id` (int?), `meeting_id`/`member_id` (**`int.parse`** of toString, `'0'` if null), `is_present` (true/'true'/1), `absent_reason`, `created_at`/`updated_at` (tryParse), `member{name,first_name,last_name,member_code,phone_number,avatar}` (`as Map?`), `meeting{meeting_number,meeting_date,notes}` (`as Map?`) | `int.parse` throws on a non-numeric value. `code` as a String → TypeError (caught → empty) | — | **Yes** — Api\AttendanceController@index |
| A2 | GET | `vsla/attendance/{id}` | attendance_service.dart:49 (no caller found) | — | `code`, `message`, `data` → Attendance | — | — | **Yes** — @show |
| A3 | GET | `vsla/attendance/stats` | attendance_service.dart:77 (no caller found) | `cycle_id`, `meeting_id`, `member_id` | raw | — | — | **Yes** — @stats (declared before `/{id}`) |
| A4 | GET | `vsla/attendance/export-pdf` | attendance_service.dart:98 (AttendanceListScreen:131) | `meeting_id`, `member_id` | `code`, `message` | — | Always returns code 0, "Attendance record not found" (404) | **SHADOWED** by `vsla/attendance/{id}` (api.php:368 before 369) → AttendanceController@show('export-pdf') |
| A5 | GET | `agent-vsla/my-groups/{groupId}/members` | screens/vsla/MeetingAttendanceScreen.dart:53 | `search=''` | `code == 1`, `data.members[]`: `id` (**`as int?`**), `first_name`, `last_name`, `name`, `phone_number`, `email`, `member_code`, `status` (lowercased `== 'active'`) | `whereType<Map<String,dynamic>>()` | A status other than 'active'/'Active' (e.g. 1, "Pending") **drops that member from the attendance list**. On a TypeError it falls back to the manifest members (`isActive`) | **Yes** — Api\VslaFacilitatorController@groupMembers (`status ?? 'active'`) |
| A6 | GET | `members` | screens/vsla/MemberPickerScreen.dart:87 | `per_page=1000` | RespondModel: `code == 1`, `data` (List) → Member.fromJson | `data is List ? … : []` | Last fallback after the manifest and the local cache. Paginating would silently give an empty list | **Yes** — MemberController@index |

### User loans

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| U1 | GET | `user-loans` | services/loan_service.dart:19 (loans_list_screen:40) | `status?` | `code == 1`, `data` as List → Loan.fromJson: `id ?? 0`, `cycle_id ?? 0`, `meeting_id` (int?), `borrower_id ?? 0`, `loan_amount`/`interest_rate`/`total_amount_due`/`amount_paid`/`balance`/`progress_percentage` (tryParse), `duration_months ?? 0` (**int**), **`DateTime.parse(disbursement_date)`** (non-null), `due_date` (parse if non-null), `purpose ?? ''`, `status ?? 'active'`, **`DateTime.parse(created_at)`**, `formatted_loan_amount/formatted_total_due/formatted_amount_paid/formatted_balance ?? 'UGX 0.00'`, `is_overdue ?? false` (**bool**), `days_remaining` (**int?**), `cycle` (**Map?**), `meeting` (Map?), `loan_transactions` as List | Throws (and rethrows to the screen) on: a null `disbursement_date`, `days_remaining` as a float (Carbon 3 `diffInDays` returns float; currently Carbon 2.73 → int), `duration_months` as a decimal or string, `cycle` not a Map | **One bad loan row breaks the whole list (no per-item try)** | **Yes** — Api\UserLoanController@index (raw VslaLoan model + appended fields) |
| U2 | GET | `user-loans/{id}` | loan_service.dart:43 (loan_details_screen:36) | — | `code == 1`, `data` → Loan | as U1 | as U1 | **Yes** — @show |
| U3 | GET | `user-loans/statistics` | loan_service.dart:65 | — | `data.active_loans/paid_loans/overdue_loans/total_loans` (int, `?? 0`), `total_borrowed/total_paid/total_balance` (tryParse), `formatted_*` | int fields assigned directly | — | **Yes** — @statistics (literal is declared before `/{id}`) |
| U4 | GET | `user-loans/{loanId}/transactions` | loan_service.dart:90 | — | `data` as List → LoanTransaction: `id`, `loan_id` (int), `type ?? ''`, `amount` (tryParse), `description`, **`DateTime.parse(created_at)`**, `formatted_amount`, `is_positive ?? false` (bool) | — | The backend column is `transaction_type`, so `type` is always ''. LoanTransaction appends `formatted_amount`, and the controller overwrites it | **Yes** — @transactions |

### Group config / reset / stats / member delete

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| G1 | GET | `vsla/groups/{groupId}` | services/vsla_offline_data_service.dart:1102 & :1123 (GroupBasicInfoScreen:89) | — | `code == 1`, `data` **as Map<String,dynamic>**; screen reads `name, subcounty_text, parish_text, village, meeting_venue, description, meeting_day, meeting_frequency, establishment_date (DateTime.parse in try), code, district_name, admin/secretary/treasurer{name,phone}, total_members, male_members, female_members, status` | `_groupData['name'] ?? ''` assigned to a TextController (a non-String value → TypeError) | Plain members get 403 → "Failed to load group information" | **Yes** — Api\VslaConfigurationController@getGroupInfo (admin/secretary/treasurer only) |
| G2 | PUT | `vsla/groups/{groupId}/basic-info` | screens/vsla/configurations/GroupBasicInfoScreen.dart:187 | JSON `name, subcounty_text, parish_text, village, meeting_venue, description, establishment_date?, meeting_day?, meeting_frequency?` | `code == 1`, `message` | direct | Empty strings become null server-side; a present-but-null `name` is written as null (`$request->has`) | **Yes** — @updateGroupBasicInfo |
| G3 | GET | `vsla/groups/{groupId}/reset-preview` | screens/vsla/configurations/ResetGroupDataScreen.dart:81 | — | `code == 1`, `data.group_id` (int), `data.group_name`, `data.will_delete`/`will_preserve`/`will_reset` (`Map.from`), `data.total_records_to_delete` (int) | **`(e.value as int)`** on every will_delete/will_reset value (lines 327, 423, 454) | Any non-int count (string, float, null) **crashes the build** (red screen) | **Yes** — Api\VslaGroupResetController@resetPreview |
| G4 | POST | `vsla/groups/{groupId}/reset` | ResetGroupDataScreen.dart:116 | `confirmation_text='delete all'` | `code == 1`, `message` | direct | — | **Yes** — @resetGroupData |
| G5 | GET | `vsla/groups/{groupId}/members/{memberId}/delete-preview` | screens/members/MembersListScreen.dart:1231 | — | `code == 1`, `data` → app reads **`will_delete`** (Map) and **`total_records_to_delete`**; `(e.value as int)` at :1078 | — | **Mismatch: the backend returns `data_counts` and `total_records`** (plus `member_name`, `confirmation_name`), so the preview sheet shows no counts and 0 records (no crash because of the `?? {}`) | **Yes** — Api\VslaGroupManagementController@deleteMemberPreview |
| G6 | POST | `vsla/groups/{groupId}/members/{memberId}/delete` | MembersListScreen.dart:1162 | `confirmation_text` = local `member.name` (trimmed) | `code == 1`, `message` | direct | The backend compares (case-insensitive) against `name ?: first+' '+last`. If the local name differs from the server name → 422 | **Yes** — @deleteMember |
| G7 | GET | `vsla-groups/stats` | vsla_offline_data_service.dart:799 & :827 | `group_id`, `cycle_id` (strings) | `success == true`, `data` **as Map<String,dynamic>** (cached; GroupReportScreen reads e.g. `financial.social_fund_balance`) | — | — | **Yes** — Api\VslaGroupStatsController@getGroupStats (`success` only) |

### Membership gating (VSLA tab)

| # | Method | Path | Call site | Request | Response keys read | Type assumptions | Fragility | Backend |
|---|---|---|---|---|---|---|---|---|
| MS1 | GET | `membership-status` | screens/main_app/main_screen.dart:61 | none | RespondModel `code == 1`, `data.is_admin`, `data.has_valid_membership` | `== 1 \|\| == true` | **If `code == 1` and `has_valid_membership` is missing, renamed, false, or "Yes", every non-admin is sent to `MembershipPaymentScreen(canGoBack:false)` via `Get.offAll` (main_screen.dart:81), so the user is locked out.** Errors/non-1 codes are ignored (fail open) | **Yes** — ApiResurceController@membership_status. **No auth middleware**: it resolves the user from `userModel`/`user()`/`User-Id` header/`user_id`/`user`. `User::hasValidMembership()` currently hard-returns `true` (Models/User.php:1176) |
| MS2 | GET | `membership-status` | screens/main_app/tabs/vsla_tab.dart:141 | `user_id` | direct `response['code'] == 1`, `data.is_admin`, `data.has_valid_membership` | same | Same lockout via `Get.off` (vsla_tab.dart:159). Skipped when local `user_type == 'admin'` | **Yes** |

---

### Critical flow shapes

#### §1 Meeting submission — `POST vsla-meetings/submit`

**Request (multipart FormData; nested lists are bracket-encoded by Dio, not JSON strings):**

```jsonc
{
  "local_id": "string",                 // required (server: required|string|max:255); 409 dedup key
  "cycle_id": 0,                        // int; server IGNORES it if the user has a group (uses active cycle)
  "group_id": 0,                        // int|null (meeting.groupId ?? Project(cycle).group_id); server prefers user.group_id
  "meeting_date": "yyyy-MM-dd",         // required|date
  "meeting_number": 0,                  // int|null — sent but ignored (server generates)
  "notes": "string|null",
  "members_present": 0,                 // required|integer — server recomputes from attendance_data
  "members_absent": 0,
  "total_savings_collected": 0.0,       // all totals are recomputed server-side (calculateSubmittedTotals)
  "total_welfare_collected": 0.0,       // = sum(transactions where accountType=='welfare')
  "total_social_fund_collected": 0.0,
  "total_fines_collected": 0.0,
  "total_loans_disbursed": 0.0,
  "total_loans_repaid": 0.0,
  "total_shares_sold": 0,               // int (server: integer)
  "total_share_value": 0.0,             // = sum(sharePurchases.totalAmountPaid)
  "attendance_data": [                  // REQUIRED|array — if the list is empty Dio sends NOTHING → 422
    { "memberId": 0, "memberName": "", "memberCode": "", "phoneNumber": "",
      "isPresent": "true|false",        // bool stringified; server handles 'true'/'1'
      "arrivalTime": "string|null", "absentReason": "string|null" /* = attendance.notes */ }
  ],
  "transactions_data": [
    { "transactionId": "localId", "memberId": 0 /* ownerId */, "memberName": "",
      "accountType": "savings|fine|welfare|social_fund|penalty", "amount": 0.0,
      "description": "", "transactionDate": "" }
  ],
  "loan_repayments_data": [
    { "local_id": "", "loan_id": 0, "loan_number": "", "borrower_id": 0, "borrower_name": "",
      "amount": 0.0, "payment_method": "", "payment_date": "", "balance_before": 0.0,
      "balance_after": 0.0, "notes": "string|null" }
  ],
  "loans_data": [
    { "local_id": "", "borrower_id": 0, "borrower_name": "", "loan_amount": 0.0, "interest_rate": 0.0,
      "loan_purpose": "", "disbursement_date": "", "due_date": "", "repayment_period_months": 0,
      "guarantor_1_id": "int|null", "guarantor_1_name": "", "guarantor_2_id": "int|null",
      "guarantor_2_name": "", "approved_by_id": 0, "status": "" }
  ],
  "share_purchases_data": [
    { "local_id": "", "investor_id": 0, "investor_name": "", "number_of_shares": 0,
      "share_price_at_purchase": 0.0, "total_amount_paid": 0.0, "purchase_date": "", "payment_method": "" }
  ],
  "social_fund_contributions_data": [
    { "member_id": 0, "member_name": "", "contributed": "true|false", "amount": 0.0, "notes": "string|null" }
  ],
  "previous_action_plans_data": [
    { "planId": 0 /* SERVER plan id from manifest */, "action": "", "completionStatus": "",
      "completionNotes": "string|null", "status": "" }
  ],
  "upcoming_action_plans_data": [
    { "planId": "localId", "action": "description", "description": "notes",
      "assigned_to_member_id": "int|null", "due_date": "", "priority": "" }
  ],
  "user": "<id>", "User-Id": "<id>"     // injected by Utils.http_post
}
```

**Required response shape (top-level keys only; the app does NOT read `data`):**

```jsonc
// success (HTTP 200)
{ "success": true, "message": "string", "meeting_id": 0, "processing_status": "string",
  "warnings": [ { "message": "string" } ], "meeting_data": { } }
// duplicate (HTTP 409, returned as Map by Utils.http_post)
{ "success": false, "code": 409, "message": "string", "meeting_id": 0, "processing_status": "string" }
// failure (any status)
{ "success": false, "message": "string", "errors": [ { "message": "string" } ], "warnings": [] }
```

**How the app decides the outcome (vsla_meeting_sync_service.dart:68-127):**
- `null` → failure.
- `success == true` → mark the local row `submitted`, return success.
- Else `int_parse(code) == 409 && meeting_id != null` → mark `submitted`, return success with a `duplicate_submission` warning.
- Else → failure. The local row stays `pending`/`ready` and is retried on the next `VslaSyncManager.syncAll` (no retry cap for meetings).

**Mismatches / notes:**
1. The backend has two dedup paths. (a) The same `local_id` → 409, which the app handles. (b) The same group+date with a different `local_id` → **200 upsert** (`success:true`, "Meeting updated…"), which overwrites the earlier meeting's data and reprocesses it. The app treats this as a normal success.
2. Validation/cycle errors use `ApiResponser::error` → `{success:false, code:0, message, data:{errors|error_type…}}` at status 422/404/401. The app only shows `message`. Top-level `errors` is absent, so the screens skip the errors dialog (OK).
3. Processing failure returns 422 with top-level `errors: [ {type,message,…} ]` (a list, as the app expects).
4. **An empty attendance list is not sent at all**, so `attendance_data` (required|array) fails → 422 "Validation failed".
5. **Previous action plans:** the app sends the server plan `id` as `planId`, but `MeetingProcessingService::processActionPlans` looks the plan up by `VslaActionPlan.local_id`. The plan is never found → `plan_not_found` warning, and status updates are silently ignored.
6. `contributed:"false"` (string) is truthy in `processSocialFundContributions` (`!$contributed` on the string "false" is false). That is harmless only while amount=0 for non-contributors.
7. The server's `success:true` response uses `code: 200` (not 1). The app does not depend on `code==1` here, so a change to `code:1` is harmless. **Do not rename/retype `success`, `meeting_id`, or the 409 status/`code:409`.**

#### §2 Group manifest — `GET vsla/groups/{group_id}/manifest`

**Required response shape (only keys the app parses; all scalars go through Utils.int_parse/double_parse/to_str unless marked):**

```jsonc
{ "code": 1,                                          // direct == 1 (int!)
  "data": {
    "group_info": {                                   // Map (GroupInfo.fromJson(Map) — a PHP [] here = TypeError → whole manifest lost)
      "id": 0, "name": "", "code": "", "type": "", "status": "",
      "establishment_date": "date|null", "registration_date": "date|null", "description": "",
      "location": { "district_id": 0, "district_name": "", "subcounty_text": "", "parish_text": "", "village": "" },
      "meeting_details": { "venue": "", "day": "", "frequency": "", "time": "" },
      "statistics": { "total_members": 0, "male_members": 0, "female_members": 0, "youth_members": 0,
                      "pwd_members": 0, "active_members": 0, "inactive_members": 0 },
      "core_team": { "admin": {"id":0,"name":"","phone":"","email":""} /*|null*/, "secretary": null, "treasurer": null }
    },
    "cycle_info": null | {                            // null => app treats as "no active cycle"
      "id": 0, "name": "", "cycle_number": 0, "start_date": "", "end_date": "", "status": "",
      "saving_type": "shares|any_amount", "weeks_elapsed": 0, "weeks_remaining": 0, "progress_percentage": 0.0,
      "financial_summary": { "share_price": 0.0, "max_shares_per_member": 0, "total_shares_sold": 0,
        "total_share_value": 0.0, "total_savings": 0.0, "total_loans_disbursed": 0.0, "total_loans_repaid": 0.0,
        "outstanding_loans": 0.0, "total_fines_collected": 0.0, "welfare_fund": 0.0, "group_cash_balance": 0.0 },
      "targets": { "target_savings": 0.0, "target_loans": 0.0, "savings_progress": 0.0, "loans_progress": 0.0 }
    },
    "members": {
      "members": [ {                                  // `as List`
        "id": 0, "name": "", "phone": "", "email": "", "member_code": "", "role": "",
        "is_active": true,                            // STRICT `== true` (1/"Yes" => inactive => hidden from pickers/attendance)
        "joined_date": "yyyy-MM-dd",
        "financial_summary": { "total_shares": 0, "share_value": 0.0, "savings_balance": 0.0, "loan_balance": 0.0,
                               "fines_balance": 0.0, "welfare_contribution": 0.0, "net_balance": 0.0 },
        "statistics": { "meetings_attended": 0, "meetings_missed": 0, "attendance_rate": 0.0, "loans_taken": 0,
                        "loans_fully_repaid": 0, "current_active_loans": 0 } } ],
      "summary": { "total_members": 0, "active_members": 0, "members_with_loans": 0, "members_with_shares": 0,
                   "average_shares_per_member": 0.0, "average_savings": 0.0 }
    },
    "active_loans": [ {                               // `as List` (Map => TypeError)
      "id": 0, "loan_number": "", "borrower_id": 0, "borrower_name": "", "loan_amount": 0.0, "interest_rate": 0.0,
      "total_amount_due": 0.0, "amount_paid": 0.0, "balance": 0.0, "status": "",
      "disbursement_date": "String|null",             // assigned raw to String? (non-String => TypeError)
      "due_date": "String|null",                      // raw String?
      "duration_months": 0, "purpose": "String|null", // raw String?
      "is_overdue": false } ],                        // strict == true
    "recent_meetings": [ {                            // `as List`
      "id": 0, "meeting_number": 0, "date": "", "venue": "", "status": "", "action_plans_count": 0, "notes": "",
      "attendance": { "members_present": 0, "members_absent": 0, "attendance_rate": 0.0,
                      "absent_members": ["name"] },   // List<String>.from — non-String item => TypeError
      "financial_summary": { "total_savings_collected": 0.0, "total_loans_disbursed": 0.0, "total_loan_repayments": 0.0,
        "total_fines": 0.0, "total_welfare": 0.0, "shares_sold": 0, "share_value": 0.0, "meeting_cash_collected": 0.0 } } ],
    "action_plans": {
      "current_action_plans": [ { "id": 0, "description": "", "deadline": "", "status": "", "priority": "",
                                   "created_at": "", "days_remaining": 0,
                                   "responsible_member": {"id":0,"name":"","phone":"","email":""} } ],
      "completed_action_plans": [ { "id": 0, "description": "", "completed_date": "", "completion_notes": "",
                                     "responsible_member": "name string" } ],
      "summary": { "total_active": 0, "overdue": 0, "due_this_week": 0, "completed_this_month": 0 }
    },
    "dashboard": null | {
      "group_cash_position": { "total_cash": 0.0, "available_for_lending": 0.0, "reserved_funds": 0.0,
                               "welfare_fund": 0.0, "fines_collected": 0.0, "last_updated": "" },
      "savings_overview": { "total_savings": 0.0, "member_contributions": 0.0, "share_purchases": 0.0,
                            "average_per_member": 0.0, "highest_saver": "", "growth_this_month": 0.0 },
      "loans_overview": { "total_loans_disbursed": 0.0, "total_repaid": 0.0, "outstanding_balance": 0.0,
                          "active_loans_count": 0, "overdue_loans_count": 0, "overdue_amount": 0.0, "default_rate": 0.0 },
      "fines_and_penalties": { "total_fines_collected": 0.0, "fines_this_month": 0.0, "most_common_fine": "",
                               "members_with_fines": 0 }
    },
    "reminders": null | {
      "next_meeting": { "date": "", "venue": "", "time": "", "days_until": 0, "expected_attendees": 0 } /*|null*/,
      "loans_due_soon": [ { "member_name": "", "amount": 0.0, "due_date": "", "days_remaining": 0 } ],
      "action_plans_due": [ { "description": "", "responsible": "", "due_date": "", "days_remaining": 0 } ],
      "cycle_milestones": { "cycle_end_date": "", "days_until_end": 0, "shareout_planned": false /*strict*/, "shareout_date": "" }
    },
    "sync_info": { "synced_at": "ISO-8601" }          // drives lastSyncedAt → needsSync() + incremental `since`
  } }
```

Incremental (`…/manifest/incremental`): the app reads only `code == 1` and `data.has_updates` (**strict bool**). If it is true, the app re-fetches the full manifest. It ignores `members_updated`, `new_meetings`, `new_action_plans`, `financial_updates`.

**Mismatches / notes:**
- `social_fund_balance` is returned but `VslaGroupManifest.fromJson` drops it, so it never reaches the cache.
- `getCurrentCycleInfo` / `getMembersSummary` / `getRecentMeetings` filter `whereNotIn('status',['completed','closed'])`. That **excludes cycles whose `status` IS NULL** (SQL NOT IN with NULL). For such groups `cycle_info` is null and member balances are 0, while `vsla-meetings/submit` accepts the same cycle (`whereNull('status') OR status != 'completed'`). Backend-internal inconsistency that the app surfaces as "no cycle".
- If `sync_info.synced_at` is missing, `getLocalAge()` = 999999, so the app re-fetches every time and incremental sends `since=''` (400).
- `canAccessGroup` returns 403 for a user whose `group_id` differs, so a facilitator switching groups gets null.

#### §3 Loans (`vsla/loans` group)

```jsonc
// GET vsla/loans  (required)
{ "code": 1, "data": [ { "id": 0, "loan_number": "", "borrower_id": 0, "borrower_name": "",
  "loan_amount": "num|str", "interest_rate": "num|str", "total_amount_due": "num|str", "amount_paid": "num|str",
  "balance": "num|str", "status": "", "duration_months": 0 /* INT or null */, "purpose": null,
  "created_at": "String|null", "is_overdue": false /* bool|null, not 0/1 */ } ] }
// GET vsla/loans/{id}: same item + "days_overdue": 0 (int) + "repayments": [ { "id":0,"loan_id":0,"payment_number":0,
//   "payment_method":"", "amount":0, "principal_paid":0,"interest_paid":0,"late_fee_paid":0,"payment_date":"",
//   "balance_before":0,"balance_after":0,"receipt_number":"" } ]
// POST vsla/loans/{id}/repayments: { "code": 1, "message": "", "data": { ...lenient VslaLoanRepayment keys... } }
```

Backend today: `loan_amount` etc. are `decimal:2` casts and serialize as strings (the app tolerates this through tryParse). `duration_months` is a raw column, so it must stay an int. Missing endpoints: see "Broken / missing routes".

#### §4 Shareout wizard

```jsonc
// 1 GET available-cycles   { "code":1, "data":[ {cycle_id,cycle_name,group_id,group_name,start_date,end_date,share_value,
//                             has_existing_shareout:bool, existing_shareout_id:int|null, existing_shareout_status:str|null} ] }
// 2 POST initiate {cycle_id} { "code":1, "data":{ "shareout_id": int|numeric-string, "status": "draft" } }
// 3 POST {id}/calculate      { "code":1, "data":{ "summary": <Summary> } }
// 4 GET {id}/distributions   { "code":1, "data":{ "distributions":[<Distribution>], "total_members": int } }
// 5 GET {id}/summary         { "code":1, "data": <Summary> + { "cycle_info":{}, "group_info":{}, "distribution_stats":{} } }
// 6 POST {id}/approve {notes?} { "code":1, "data":{ "status": "" } }  then  POST {id}/complete  { "code":1, "data":{ "status":"", "cycle_closed": true } }
// extra: GET {id} { "code":1, "data":{ "shareout": <Summary>, "distributions":[...] } }; POST {id}/cancel; GET history { "code":1, "data":[<Summary>] }
// <Summary> = shareout_id,cycle_name,group_name,status,status_label,shareout_date,total_savings,total_share_value,
//   total_loan_interest_earned,total_fines_collected,total_distributable_fund,total_outstanding_loans,total_actual_payout,
//   total_members,total_shares,share_unit_value,final_share_value,can_recalculate,can_approve,can_complete,can_cancel (STRICT bools),
//   distributions_count,paid_distributions_count
// <Distribution> = distribution_id,member_id,member_name,contributions{..},entitlements{..},deductions{..},final_payout,
//   payment_status,payment_status_label,is_payable (strict bool)
```

The backend matches today (ApiResponser `success()` → `code:1`). Risk: `ApiResponser::success($a,$b)` argument-order heuristics. If a call passes `(array, array)` or `(string, string)`, `data` is dropped and steps 3–5 return no summary.

#### §5 Cycles

```jsonc
// GET vsla/cycles
{ "code":1, "data": { "group_id":0, "group_name":"", "active_cycle": <Cycle>|null, "cycles":[<Cycle>] } }
// <Cycle> = id, group_id, cycle_name, start_date, end_date, status, is_active_cycle (BOOL), progress_percentage,
//   saving_type, share_value, meeting_frequency, loan_interest_rate, interest_frequency, weekly_loan_interest_rate,
//   monthly_loan_interest_rate, minimum_loan_amount, maximum_loan_multiple, late_payment_penalty, created_at, updated_at
// POST/PUT/PUT status: { "code": 1, "message": "" }  (data not read)
```

Note: `updateCycleStatus`/`updateCycle` return `data.cycle` as a raw model (`is_active_cycle` "Yes"/"No"). The app does not read it, so the app must keep re-fetching `vsla/cycles` (which returns a bool).

#### §6 Opening balance

```jsonc
// GET status   { "code":1, "data":{ "submitted": bool } }
// GET members  { "code":1, "data":{ "project_id":0, "project_name":"", "group_id":0, "members":[{"id":0,"name":"","member_code":""}] } }
// POST submit  members = JSON STRING (backend must keep `is_string → json_decode`)
//   ok:   { "code":1, "message":"", "data":{...} }
//   fail: { "code":0, "message":"", "errors": {"members.0.total_shares":["…"]} }   // errors Map is formatted into the toast
//   409:  { "code":0, "message":"already submitted", "data":{...} }                // treated as failure
```

---

### Broken / missing routes

| App call | File:line | Backend result | User impact |
|---|---|---|---|
| POST `vsla/loans` | services/vsla_loan_api.dart:58 | **405** (only GET `/`) | **False success**: LoanRequestFormScreen.dart:324 ignores the result and toasts "submitted successfully" |
| POST `vsla/loans/{id}/approve` | vsla_loan_api.dart:91 | **404** | Approve always fails (LoanRequestDetailsScreen:48) |
| POST `vsla/loans/{id}/reject` | vsla_loan_api.dart:115 | **404** | Reject always fails (LoanRequestDetailsScreen:133) |
| POST `vsla/loans/{id}/disburse` | vsla_loan_api.dart:141 | **404** | no caller (dead) |
| GET `vsla/loans/{id}/repayments` | vsla_loan_api.dart:202 | **405** (only POST) | no caller (dead) |
| GET `vsla/loans/{id}/statement` | vsla_loan_api.dart:221 | **404** | no caller (dead) |
| POST `vsla/loans/check-eligibility` | vsla_loan_api.dart:253 | **405** (matches GET `/{id}`) | Eligibility never shown (LoanRequestFormScreen:113) |
| GET `vsla/attendance/export-pdf` | services/attendance_service.dart:98 | **SHADOWED** by `/{id}` (api.php:368 vs 369) → show() → 404 "Attendance record not found" | Export button always fails (AttendanceListScreen:131) |
| GET `projects/{id}` read with `code == 1` | screens/vsla/LoanRequestScreen.dart:96 | Route exists, but the response has no `code` | Max loan = 0 (screen unreachable) |

**Routes that exist but whose response shape does not match what the app reads** (no crash, wrong data):
- `vsla/transactions/member-balance/{id}`: the app reads `data.savings`, the backend sends `data.balances.savings`. All balances show 0. Callers: AddSavingsScreen, LoanRepaymentScreen.
- `vsla/transactions/group-balance/{id}`: same nesting problem (`data.balances.*`).
- `vsla/transactions/member-statement`, `group-statement`, `recent`: the app does `data as List`, the backend sends `data.transactions`. These always come back empty (TransactionsListScreen).
- `vsla/transactions/dashboard-summary`: the app reads `user_balances`/`group_balances`, the backend sends `data.overview`. No caller.
- `vsla/groups/{g}/members/{m}/delete-preview`: the app reads `will_delete`/`total_records_to_delete`, the backend sends `data_counts`/`total_records`.
- `vsla-meetings/submit` `previous_action_plans_data[].planId`: the app sends the server id, the backend looks it up by `local_id`. Plan status updates are dropped.
- `user-loans/{id}/transactions`: the app reads `type`, but the column is `transaction_type`, so it is always ''.

### Top fragility risks (ranked)

1. **Membership lockout.** If the backend returns `code:1` but `has_valid_membership` is not literally `true`/`1` (renamed, "Yes", missing), every non-admin is force-routed to `MembershipPaymentScreen(canGoBack:false)` and is stuck. Sites: screens/main_app/main_screen.dart:61-81 and screens/main_app/tabs/vsla_tab.dart:141-159. The route also has no auth middleware.
2. **Meeting submit contract.** vsla_meeting_sync_service.dart:79-96 depends on top-level `success === true`, the 409 status with `code:409` and `meeting_id`, and list-typed `errors`/`warnings`:
   - Changing `success` makes every submission "fail", so the app retries forever.
   - Removing the 409/local_id dedup produces duplicate meetings.
   - Wrapping fields in `data` breaks the result.
   - Map-typed `errors` (Laravel default validation) crashes the dialog at MeetingSummaryScreen.dart:284 and VslaMeetingsHubScreen.dart:997.
   - An empty attendance list returns 422 (`attendance_data` is required).
3. **Manifest is all-or-nothing.** In models/vsla_group_manifest.dart:
   - `as List` at :79, :86, :630, :942, :948 and `List<String>.from(absent_members)` at :871 throw on any type change.
   - `X.fromJson(json['…'])` throws if a nested object comes back as PHP `[]` (e.g. `statistics`, `core_team`, `dashboard`) (:64-103, :299-309).
   - Raw String? assignments for `active_loans[].disbursement_date/due_date/purpose` (:1437-1440) throw on non-Strings.
   - Any one of these discards the whole manifest, so member pickers, attendance, cycle and offline validation fall back to stale cache or nothing.
   - `members[].is_active` must stay a strict bool (:671). Otherwise members disappear from pickers and attendance.
4. **Offline queue data loss and duplicates.** In services/vsla_offline_queue_service.dart:305-358:
   - Queued `charge`/`welfare`/`social_fund` (AddTransactionScreen.dart:302) and `social_fund_contribution|withdrawal` (AddSocialFundTransactionScreen.dart:124) have no sync handler and are dropped after 3 retries.
   - For handled types, success depends on `VslaTransactionResponse.fromJson` parsing `data.user_transaction` etc. into int-typed fields (models/vsla_transaction_models.dart:134-159, 228). A shape or type change after a server-side success leads to retries, and up to 3 duplicate ledger entries.
5. **User-loans parse crashes the whole list.** models/loan_model.dart:75 and :80 call `DateTime.parse` on `disbursement_date`/`created_at` with no null guard. Also fragile: `days_remaining` typed `int?` (:88, breaks if Carbon is upgraded to 3.x, where diffInDays returns a float), `duration_months ?? 0` int (:70), `cycle` typed Map?. One bad row rethrows and the loans screen errors (services/loan_service.dart:19-36).
6. **Cycle resolution.** The app requires `data.active_cycle` from `vsla/cycles` and `is_active_cycle === true` (vsla_cycle_service.dart:111, VslaMeetingsHubScreen.dart:116-148). The backend nulls `active_cycle` once end_date has passed, and the manifest ignores NULL-status cycles. Meetings are then blocked in the app although the submit endpoint would accept them.
7. **Transaction read endpoints silently wrong.** In services/vsla_transaction_api.dart:147-149, 170-172, 212-214, 251-253, 277-279, nesting mismatches give zero balances and empty statements. Any backend "fix" must keep the app's flat `data.savings` and `data` (List) shape, or both shapes must be supported.
8. **Loan request false success.** screens/vsla/LoanRequestFormScreen.dart:324-334 always reports success for a 405.
9. **Hard casts in config screens.** `(e.value as int)` in ResetGroupDataScreen.dart:327, 423, 454 and MembersListScreen.dart:1078 crashes the build on a non-int count. `data as Map<String,dynamic>` is used for group info (vsla_offline_data_service.dart:1105) and stats (:803/:833).
10. **Direct `response['code'] == 1` on POST/PUT.** This throws on the String error return from Utils.http_post/put:
    - Caught (toast "Error: …"): AddTransactionScreen:337, GroupBasicInfoScreen:194, ResetGroupDataScreen:120, MembersListScreen:1166, VslaCycleService, VslaShareoutService, VslaLoanApi.
    - Breaks if the backend returns `code:"1"` or `true`: all of the above plus every GET that uses direct `== 1` (manifest :162, cycles, loans, attendance `code` typed int in models/attendance_model / attendance_service.dart AttendanceListResponse).

Minor app-side bug found along the way: VslaSyncManager.\_refreshOfflineCaches passes `projectId: groupId` (services/vsla_sync_manager.dart:109). Loans and statements are therefore fetched and cached with a group_id where a cycle_id is expected. For `vsla/loans?cycle_id=<groupId>`, the backend falls back to the active cycle only when a project with that id exists and belongs to another group. If no project has that id, it queries `cycle_id=<groupId>` literally, which returns wrong or empty results.

---

## 4. Facilitator, Groups/Members, FFS, AESA, Farm, Enterprise, KPI, Locations

Scope: 109 call sites (about 85 distinct METHOD+path pairs). Mobile paths are relative to `lib/`. Backend paths are relative to `/api/`. "RM" means the caller wraps the response in `RespondModel` (code read with `Utils.int_parse`, which is safe). "direct" means the caller reads `response['code'] == 1` itself.

How requests are encoded (dio 5.9.0 `FormData.fromMap`, default `ListFormat.multi`, checked in the pub-cache source):
- `null` values become `''`. Laravel's ConvertEmptyStringsToNull then turns them into `null`.
- A nested `Map` inside a `List` is sent as `key[0][field]`. PHP parses this correctly.
- A **list of scalars** is sent as the same key repeated **without brackets** (`k=a&k=b`). PHP keeps only the last value, as a string, so any `array` validation rule fails.
- Explicit `'k[$i]'` keys are fine in POST (multipart). In PUT (JSON body) they are sent as literal keys and are **not** turned into arrays.
- Method spoofing works: Laravel's Kernel enables `_method` override on multipart POST.

Shared failure behaviour:
- `ApiResponser::error()` defaults to HTTP 400 with `{success:false, code:0, status:0, message}`.
- Utils returns any non-2xx JSON body as a Map, so error status codes are handled the same as a 200 with `code:0`.
- **Exception:** `services/farm_service.dart` uses `package:http` and throws on any status other than 200 (201 is also allowed for createFarm and createCustomActivity). The backend's 4xx `message` is thrown away and the user sees `"Failed to …: 422"`.

### 1. Facilitator module (`agent-vsla/*`, `VslaFacilitatorController`, `VslaGroupManagementController`)

| # | Method | Path | Request keys | Response keys read | Type assumptions | Fragility | Backend route |
|---|---|---|---|---|---|---|---|
| 1 | GET | agent-vsla/dashboard | – | data.total_groups, data.total_members, data.active_cycles (displayed through `'$v'`) | `Map<String,dynamic>.from(resp.data)` | RM. Safe. A `null` stat shows as 0. | Yes, VslaFacilitatorController@dashboard |
| 2 | GET | agent-vsla/my-groups (screens/facilitator/facilitator_groups_screen.dart:46) | – | data[] → id, name, code, district, total_members, chairperson_name, meeting_frequency, active_cycle (null or {name}), created_at | `List<Map<String,dynamic>>.from(resp.data)`, so **data must be a List** | RM. If data becomes paginated or a Map, `from()` throws inside try/catch and the screen shows an empty list. `createdAt.isNotEmpty` needs a String (Y-m-d). | Yes, @myGroups |
| 3 | GET | agent-vsla/my-groups (facilitator_members_screen.dart:75) | – | data[] → id, name | `resp.data as List<dynamic>?` | Same as row 2: must be a List | Yes, @myGroups |
| 4 | GET | agent-vsla/my-groups/{id} (facilitator_group_detail_screen.dart:52) | – | see "Critical flow shapes" | `Map.from(d['group'])`, `List.from(d['members'])`, `List.from(d['cycles'])`, `_group['chairperson'] as Map<String,dynamic>?` | RM. chairperson, secretary and treasurer must each be `null` or an object (a non-Map value throws). `c['is_active'] == true` needs a real bool. | Yes, @groupDetail |
| 5 | GET | agent-vsla/my-groups/{id} (facilitator_group_edit_screen.dart:59) | – | data.group.{name, description, code, district, subcounty, subcounty_text, parish, parish_text, village, meeting_frequency, meeting_day, meeting_venue, estimated_members, establishment_date} | `_safeString`, `_parseDate` | RM. Safe. | Yes |
| 6 | PUT | agent-vsla/my-groups/{id} (detail sheet, :961) | JSON: name, description, meeting_frequency (Weekly/Bi-weekly/Monthly), meeting_day, meeting_venue, subcounty_text, parish_text, village, plus user, User-Id, user_id | code, message | – | RM. A 422 shows its message. | Yes, @updateGroup |
| 7 | PUT | agent-vsla/my-groups/{id} (edit screen, :174) | JSON: same as row 6 plus estimated_members (String, int 5–100) and establishment_date (yyyy-MM-dd). Empty strings are removed. | code, message | – | RM | Yes |
| 8 | PUT | agent-vsla/members/{id} (:701) | JSON: first_name, last_name, phone_number, sex (Male/Female) | code, message | – | RM. Backend returns 422 "No valid changes were provided" when nothing changed, and requires `phone_number` min:10. | Yes, @updateMember |
| 9 | PUT | agent-vsla/members/{id}/role (:789) | JSON: role ∈ Member/Chairperson/Secretary/Treasurer | code, message | – | RM | Yes, @updateMemberRole |
| 10 | POST | agent-vsla/my-groups/{id}/members (facilitator_add_member_screen.dart:61) | form: first_name, last_name, sex, role, phone_number?, password? (min 4) | code, message | – | RM | Yes, @addMember |
| 11 | POST | agent-vsla/my-groups/{id}/members/{mid}/set-password (facilitator_group_detail_screen.dart:1117) | form: **confirmation_text** | code, message | `resp['code']` direct | **Contract mismatch:** the backend requires `new_password` and always returns 422 "Please provide a new password". `_showSetPasswordDialog` (:1007) currently has **no caller** (dead code). If a String error comes back, the throw is caught. | Yes, VslaGroupManagementController@setMemberPassword |
| 12 | POST | same path (facilitator_set_password_screen.dart:52) | form: new_password | code, message | direct | Works. A String error is caught and shown as "Network error". | Yes |
| 13 | GET | agent-vsla/all-members | query: search?, group_id? | data.members[] (or data[]) → first_name, last_name, phone_number, role, is_group_admin, is_group_secretary, is_group_treasurer ('Yes'), group{name} or group_name, group_id | `d['members'] as List`, `m['group'] as Map<String,dynamic>?` | RM. Accepts either a Map or a List. | Yes, @allMembers |
| 14 | GET | agent-vsla/all-meetings | – | data[] → meeting_number, group_name, group_code, meeting_date, members_present, members_absent, total_savings, total_loans, total_loans_repaid, total_welfare, processing_status, has_errors, has_warnings | **`(m['total_savings'] ?? 0).toDouble()`** and the same for total_loans, total_loans_repaid, total_welfare. `has_errors == true`. | RM, but a throw inside `build()` is **not caught**. The backend currently casts to `(float)`. If anyone drops the cast, decimal columns arrive as Strings and `.toDouble()` throws NoSuchMethodError, crashing the screen. | Yes, @allMeetings |
| 15 | GET | agent-vsla/meeting-groups | – | data[] → id, name, code, member_count, active_cycle{id, saving_type} | **`group['id'] as int`, `cycle['id'] as int`** | Needs native int ids (fails with string-typed PDO results). | Yes, @meetingGroups |
| 16 | GET | agent-vsla/my-groups/{id}/delete-preview (delete_group_screen.dart:68) | – | data.group_name, **data.will_delete** (Map of ints), data.members[] {name, role, phone}, **data.total_records_to_delete** (int) | `_totalRecords` (int) = raw value, `(e.value as int)` | direct. **Key mismatch:** the backend returns `data_counts` and `total_records`, so the preview always shows 0 records and an empty breakdown. The backend has no `role` per member. | Yes, @deleteGroupPreview |
| 17 | POST | agent-vsla/my-groups/{id}/delete (:101) | form: confirmation_text = group name | code, message | direct | Works. A 422 comes back as a Map. | Yes, @deleteGroup |
| 18 | POST | agent-vsla/create-group (screens/vsla_profiling/VslaProfilingGroupScreen.dart:96) | form: name, description, meeting_frequency, establishment_date (yyyy-MM-dd or ''), year_of_establishment, district_id, estimated_members (`.toString()`, so `"null"` when empty), subcounty_text, parish_text, village | data.group_id, data.group_code (`?.toString()`) | – | RM. When estimated_members is empty the app sends the string `"null"`, which fails `integer` validation with a 400 toast. | Yes, VslaAgentOnboardingController@createGroup |
| 19 | POST | agent-vsla/register-officers (VslaProfilingOfficersScreen.dart:113) | form: group_id; chairperson_, secretary_, treasurer_ each with {name, phone, sex, email, nin}; send_sms '1' | data.sms_sent == true | – | RM. Phones must match `^(\+256\|0)7\d{8}$`. | Yes, @registerOfficers |
| 20 | POST | agent-vsla/create-cycle (VslaProfilingCycleScreen.dart:169) | form: group_id, cycle_name, start_date, end_date, saving_type (shares/any_amount), meeting_frequency, loan_interest_rate, interest_frequency (Weekly/Monthly), minimum_loan_amount, maximum_loan_multiple, late_payment_penalty, share_value?, weekly_ or monthly_loan_interest_rate | code, message | – | RM | Yes, @createCycle |
| 21 | GET | locations/karamoja-districts (VslaProfilingGroupScreen.dart:42) | – | data[] → id, name (`.toString()`) | `resp.data as List` | RM | Yes, ApiResurceController@locations_karamoja_districts (falls back to all `type=District` rows if the "Karamoja Region" row is missing) |

### 2. KPI (`KpiController`, `services/kpi_api.dart`; used only by facilitator_kpi_screen.dart)

| # | Method | Path | Request | Response keys read | Types | Fragility | Route |
|---|---|---|---|---|---|---|---|
| 22 | GET | kpi/facilitator-scorecard | facilitator_id?, week_start? | data.{overall_score, week_start, week_end, start_date, weeks_active}, data.scores.{groups, trainings, meetings, members, aesa, attendance}, data.actuals.{total_groups, total_members, trainings_this_week, meetings_this_week, meetings_per_group, avg_members_per_group, aesa_this_week, attendance_pct}, data.benchmarks.{min_groups, min_trainings_per_week, min_meetings_per_group_per_week, min_members_per_group, min_aesa_per_week, min_attendance_pct} | `Map.from(resp.data)`; `_toDouble` is safe | RM. Keys match KpiService exactly today. | Yes, @facilitatorScorecard |
| 23 | GET | kpi/ip-scorecard | ip_id? | Map (unused: no screen calls it) | – | – | Yes, @ipScorecard |
| 24 | GET | kpi/benchmarks | – | Map (unused) | – | – | Yes, @benchmarks |
| 25 | GET | kpi/facilitator-trend | weeks=8, facilitator_id? | data[] → week_start, overall_score | **`resp.data as List`** | Must be a List. Today it is `$trend[]`. | Yes, @facilitatorTrend |

### 3. Groups (`FfsGroupController`, models/FfsGroupModel.dart, models/GroupModel.dart)

| # | Method | Path | Request | Response keys read | Types | Fragility | Route |
|---|---|---|---|---|---|---|---|
| 26 | GET | ffs-groups (FfsGroupModel.dart:263) | – | data[] → FfsGroupModel.fromJson: id, name, code, type, type_text, district_id, district_name, village, total_members, facilitator_id, facilitator_name, status, status_text, establishment_date, estimated_members, admin_id, admin_name, secretary_id, secretary_name, treasurer_id, treasurer_name, subcounty_text, parish_text, meeting_frequency, description | All `Utils.int_parse` / `Utils.to_str` (safe) | RM. Only processed if data's runtimeType contains 'List'. **Deletes all local groups when online and then re-inserts them.** Backend sends nothing for total_members, admin_name, secretary_name or treasurer_name (not in `$appends`), so those default to 0 or ''. | Yes, FfsGroupController@index (no auth middleware; IP scoping works only with a valid JWT) |
| 27 | GET | ffs-groups/form-options (screens/groups/GroupRegisterScreen.dart:70) | – | data.districts[] {id, name}, data.facilitators[] {id, name}, data.meeting_days (List\<String\>), data.meeting_frequencies[] {value, label}, data.value_chains (List\<String\>) | **`Text(district['name'])`** and `Text(facilitator['name'])` need non-null Strings | direct (`response['code']`). A null facilitator `name` throws inside `build()`. | Yes, @getFormOptions |
| 28 | POST | ffs-groups (GroupRegisterScreen.dart:126) | form: name, type (FFS/FBS/VSLA/Association), district_id, village, meeting_venue, meeting_day, meeting_frequency, primary_value_chain, facilitator_id, contact_person_name, contact_person_phone, description | code, message | direct | Returns 201 (fine with Dio). A String error is caught. | Yes, @store (EnsureTokenIsValid) |
| 29 | GET | ffs-groups/form-options (screens/profiling/ffs_group_registration_screen.dart:60) | – | same as row 27 plus facilitators[].phone_number | `(resp.data['districts'] as List)` | RM | Yes |
| 30 | GET | ffs-groups/subcounties/{districtId} (:103) | – | data[] {id, name} | `resp.data as List` | **Always fails:** the query uses `where('type','Subcounty')->where('parent_id', …)`, but `locations` has no `parent_id` column. SQL error, then HTTP 500 `{code:0}`, so the dropdown is always empty. | Route exists, but **500**: @getSubcounties |
| 31 | GET | ffs-groups/parishes/{subcountyId} (:125) | – | data[] {id, name} | `as List` | Same `parent_id` bug, so 500 | Route exists, but **500**: @getParishes |
| 32 | POST | ffs-groups (:218) | form: name, type, code, registration_date (`DateTime.toString()`), district_id, subcounty_id, parish_id, village, meeting_venue, meeting_day, meeting_frequency, primary_value_chain, **secondary_value_chains (List\<String\>)**, facilitator_id, contact_person_*, latitude, longitude, description | code, message | RM | **The scalar list is encoded as `secondary_value_chains=a&secondary_value_chains=b`.** PHP receives a string, so `nullable\|array` returns 422 whenever at least one secondary value chain is chosen. | Yes, @store |
| 33 | GET | members?group_id= (screens/groups/GroupDetailScreen.dart:54) | query group_id | data[] → LoggedInUserModel.fromJson (all safe parsers) | `resp.data as List<dynamic>` | RM. Returns 403 with `data:[]` when the group is out of scope. | Yes, MemberController@index |
| 34 | GET | groups (models/GroupModel.dart via DynamicTable.getItems, used by screens/forms/PWDFormCreateScreen.dart:811) | is_not_private=1 | data (stored as JSON) | safe | **No route.** Returns 404. Only `api/{model}` exists, and that would need the path `api/groups`. | **NO (missing)** |

### 4. Members (`MemberController` in app/Http/Controllers, services/member_service.dart, models/member_model.dart)

`Member.fromJson` reads: id, first_name, last_name, name, phone_number, email, member_code, sex, dob, village, district_id, **district_name**, subcounty_id, **subcounty_name**, parish_id, **parish_name**, education_level, marital_status, occupation, national_id_number, household_size, emergency_contact_name, emergency_contact_phone, avatar, status, balance, loan_balance, created_at, updated_at. Every field uses `?.toString()`, `int.tryParse` or `double.tryParse`, so parsing is safe.

The backend sends `district`, `subcounty` and `parish` (names), not `*_name`, so the app's location names are always null (cosmetic).

`MemberResponse.fromJson`: `code` uses int.tryParse(toString) (safe); `errors as Map<String,dynamic>?` throws if the backend ever sends `errors: []`.

`MembersListResponse`: `data as List?`, and `pagination` is read at the **top level**. The backend sends no pagination, so `hasMore` is false.

| # | Method | Path | Request | Response keys read | Types | Fragility | Route |
|---|---|---|---|---|---|---|---|
| 35 | GET | members/{id} (screens/members/MemberDetailScreen.dart:35) | – | data → LoggedInUserModel.fromJson | safe | RM | Yes, @show |
| 36–39 | POST | members/{id}/send-credentials, members/{id}/send-welcome (MemberDetailScreen.dart:59, 81; MemberDetailsScreen.dart:112, 131) | {} | code, message | MemberDetailScreen uses RM. **MemberDetailsScreen uses direct `response['code']`.** | A String error is caught by try/catch | Yes, @sendCredentials / @sendWelcomeMessage |
| 40–42 | GET | locations/districts (MemberEditScreen.dart:52, MemberRegisterScreen.dart:56, MemberRegistrationScreen.dart:71) | – | data[] → id, name | `resp.data as List` | RM. **The duplicate route is resolved to LocationController@getDistricts** (declared later at routes/api.php:427, overriding :75), which returns `type='District' OR parent<1`. That **includes Region and Country rows** ("Central Region", "Kampla, Uganda", "Karamoja Region" in the local DB), and these can be saved as district_id. | Yes, LocationController@getDistricts (overrides ApiResurceController@locations_districts) |
| 43 | POST | members (MemberRegisterScreen.dart:238) | multipart: first_name, last_name, phone_number, sex, dob (DateTime.toString), email, district_id, village, education_level, marital_status, occupation, password, household_size, emergency_contact_*, avatar (file) | code, message | RM | Returns 201. **education_level options 'Secondary', 'Tertiary' and 'University' are not in the backend enum** (None, Primary, O-Level, A-Level, Certificate, Diploma, Degree, Masters, PhD), so choosing one returns 422. | Yes, @store |
| 44 | GET | members?page&per_page&search&status&sort_by&sort_order (member_service.dart:41) | query string built into the path | data[] → Member; pagination (top level) | `MembersListResponse` | Not paginated: returns everything. | Yes, @index |
| 45 | GET | members/{id} (member_service.dart:53) | – | data → Member | safe | A String error throws, caught as "Network error" | Yes, @show |
| 46 | POST | members (member_service.dart:90, used by MemberRegistrationScreen) | multipart from `Member.toJson()`: first_name, last_name, name, phone_number, email, member_code (''), sex, dob (yyyy-MM-dd), village, district_id, subcounty_id, parish_id, education_level, marital_status, occupation, national_id_number, household_size, emergency_*, avatar, status, balance, loan_balance, plus an avatar file | code, message, data → Member, data.avatar | `response['data']['avatar']` direct (inside try) | 201. Extra keys are ignored. | Yes, @store |
| 47 | POST + `_method=PUT` | members/{id} (member_service.dart:144, used by MemberEditScreen) | multipart: all of `Member.toJson()` plus password? and an avatar file | code, message, data → Member | – | Spoofing works. `status` must be 0 or 1. The same education_level mismatch applies (422). | Yes, PUT @update |
| 48 | DELETE | members/{id} (member_service.dart:163) | form user_id | code, message | direct | Only deactivates (`status=0`) | Yes, @destroy |
| 49 | POST | members/sync (member_service.dart:183, from member_offline_service.dart:403) | form: `members[i][temp_id]`, `members[i][action]` (create/update), `members[i][data][<Member.toJson keys>]` | **data.synced** (int), **data.failed** (int), **data.results** (List) | – | **Shape mismatch:** the backend returns `data:{success:[{temp_id, server_id, member}], failed:[{temp_id, error}]}`. `synced` and `results` are always empty or 0, and `failed` is a List. **On code==1 the app deletes the whole queue** (member_offline_service.dart:405-407) even when some items failed, so those items are lost. **The sync "create" path never sets group_id**, leaving orphan members (code format `MEM-YYYY-NNNN`). **The sync "update" path writes every key**, including the client's stale `balance`, `loan_balance` and `status`, over the server values. | Yes, @sync |

### 5. FFS Training Sessions (`Api\FfsTrainingSessionController`, services/ffs_training_session_service.dart, `baseUrl='ffs-training-sessions'`)

Model parsing (models/ffs_training_session_model.dart) uses safe `int.tryParse`, `double.tryParse` and `DateTime.tryParse` throughout, and guards lists with `is List`.

The response wrappers check `json is Map<String,dynamic>`, which makes them safe against a String error body. They assign `code: json['code'] ?? 0` to an **`int` field**, so if the backend sent `"1"` or `true`, a TypeError would be thrown and caught, and the user would see "Network error".

| # | Method | Path | Request | Response keys read | Types | Fragility | Route |
|---|---|---|---|---|---|---|---|
| 50 | GET | ffs-training-sessions | search, status, report_status, session_type, date_from, date_to, sort_by, sort_dir | data[] → FfsTrainingSession | `json['data'] is List`, otherwise [] | Pagination would silently produce an empty list | Yes, @index |
| 51 | GET | ffs-training-sessions/{id} | – | data (Map) → session plus participants[] {id, user_id, user_name, attendance_status, remarks} plus resolutions[] {id, resolution, description, gap_category, responsible_person_id, target_date, status, follow_up_notes, completed_at, is_overdue} | `data is Map<String,dynamic>` | App reads `responsible_person` as a nested Map, but the backend sends flat `responsible_person_name`, so the name is never shown. | Yes, @show |
| 52 | POST | ffs-training-sessions | form: title, session_type (classroom/field/demonstration/workshop/other), session_date, `group_ids[i]`, topic, description, venue, expected_participants, materials_used, notes, start_time ('HH:mm'), end_time | code, message, data | – | 201 is fine | Yes, @store |
| 53 | PUT | ffs-training-sessions/{id} (form edit and status change) | JSON: same keys as row 52, including **literal `"group_ids[0]"` keys**; or {status} | code, message, data | – | **JSON keys are not bracket-parsed, so edits to the target groups are silently ignored** (`group_ids` is `sometimes`). Returns 422 if the report is submitted or the status transition is not allowed (message shown). | Yes, @update |
| 54 | DELETE | ffs-training-sessions/{id} | – | code, message | – | – | Yes, @destroy |
| 55 | GET | ffs-training-sessions/stats | – | data.total_sessions, data.scheduled, data.ongoing, data.completed | `int.tryParse(toString)` | direct, but guarded by `is Map` | Yes, @stats (declared before /{id}) |
| 56 | GET | ffs-training-sessions/pending-reports | – | data[] → session | as row 50 | – | Yes, @pendingReports |
| 57 | GET | ffs-training-sessions/{id}/expected-members | – | data[] → **id**, name, (phone_number, group_id) | **`member['id'] as int`** (wizard :111, :520, :529) | Needs native int ids. A throw in `_loadData` is caught; a throw in `build()` (:529) is not. | Yes, @expectedMembers |
| 58 | POST | {id}/submit-report | {} | code, message | – | Unused by any screen | Yes |
| 59 | POST | {id}/unsubmit-report | {} | code, message | – | – | Yes |
| 60 | POST | {id}/wizard-report | form: `participants[i][user_id]`, `participants[i][attendance_status]` (present/absent), `participants[i][remarks]?`; `resolutions[i][resolution\|description\|gap_category\|responsible_person_id\|target_date]`; **`photos[i]` = local device file paths**; gps_latitude, gps_longitude; `attending_facilitator_ids[i]`; notes, challenges, recommendations, materials_used; submit '1'/'0' | code, message, data (Map, not otherwise used) | – | Encoding works. Photos are device paths, not uploads, so the server stores useless strings. Any participant `user_id` that no longer exists makes the whole report fail with 422. | Yes, @submitWizardReport |
| 61 | GET | {sid}/participants | – | data[] → participant | – | Unused | Yes |
| 62 | POST | {sid}/participants | form `participants[i][…]` | code, message | – | Unused | Yes, @syncParticipants |
| 63 | DELETE | {sid}/participants/{pid} | – | code | – | Unused | Yes |
| 64 | GET | {sid}/resolutions | – | data[] → resolution | – | Unused | Yes |
| 65 | POST | {sid}/resolutions (ffs_resolution_form_screen.dart:125) | form: resolution, gap_category (soil/water/seeds/pest/harvest/storage/marketing/livestock/other, **required**), description?, target_date?, follow_up_notes? | code, message, data (Map) → resolution | – | 201. `data` is the Eloquent model with `responsiblePerson` loaded under the snake_case key `responsible_person`, which the app parses correctly here. | Yes, @storeResolution |
| 66 | PUT | {sid}/resolutions/{rid} | JSON: as row 65 plus status | code, message, data | – | – | Yes, @updateResolution |
| 67 | DELETE | {sid}/resolutions/{rid} | – | code | – | – | Yes |

### 6. AESA (`Api\AesaController`, services/aesa_service.dart, models/aesa_models.dart)

Every AESA call checks `response is Map && response['code'] == 1`, so a String error is safe. The checks compare with **int `1` exactly**: `"1"` or `true` would count as failure.

Updates and deletes use POST with `_method=PUT` or `_method=DELETE`, which Laravel accepts (so this is not a method mismatch).

Model notes:
- `AesaSessionModel.id`, `AesaObservationModel.id`/`aesa_session_id` and `AesaCropObservationModel.id`/`aesa_session_id` use `int.parse(toString)`. **These keys are required**: if one is null, parsing `"null"` throws and the entire list falls back to [].
- `photos` uses `List<String>.from(json['photos'])`, so it must be an array or null. The model casts it to `array` today.
- Booleans (rainfall_occurrence, wounds_injuries, …) accept bool, 1 or '1'.
- `AesaDropdownOptions` reads every option key with `val as List`, so each must be a List (a PHP associative array would arrive as a Map and fail). `districts` accepts List\<String\> or List\<Map{name}\>. `groups`, `facilitators` and `farmers` use `Map.from(e)`.

| # | Method | Path | Request | Response keys read | Fragility | Route |
|---|---|---|---|---|---|---|
| 68 | GET | aesa-sessions | status, group_id, facilitator_id, date_from, date_to, search, my_sessions | data[] or data.data[] → AesaSessionModel | Handles both a list and a paginator | Yes, @index |
| 69 | GET | aesa-sessions/{id} | – | data → session plus observations[], crop_observations[] | – | Yes, @show |
| 70 | POST | aesa-sessions | form: observation_date (required), status 'draft', group_id, group_name_other, sub_county_text, village_text, observation_time 'HH:mm', facilitator_id, facilitator_name, mini_group_name, observation_location, observation_location_other | data → session; message toast | 201 is fine | Yes, @store |
| 71 | POST + `_method=PUT` | aesa-sessions/{id} (aesa_service.dart:113) | same keys as row 70 | data → session | – | Yes, PUT @update |
| 72 | POST + `_method=DELETE` | aesa-sessions/{id} (:132) | – | code | – | Yes, DELETE @destroy |
| 73 | POST | aesa-sessions/{id}/submit | {} | code, message | Returns 400 for "Only draft…" or "without any observations"; the message is shown | Yes, @submit |
| 74 | GET | aesa-sessions/{sid}/observations | – | data[] or data.data[] | – | Yes |
| 75 | GET | aesa-observations/{id} | – | data | – | Yes |
| 76 | POST | aesa-sessions/{sid}/observations | form: animal fields; booleans sent as '1'/'0' | data | 201 | Yes, @storeObservation |
| 77 | POST + `_method=PUT` | aesa-observations/{id} | same | data | – | Yes, PUT |
| 78 | POST + `_method=DELETE` | aesa-observations/{id} | – | code | – | Yes, DELETE |
| 79 | GET | aesa-sessions/dropdown-options | – | data.{animal_types, breeds, …, crop_* (≈65 keys)}, data.groups[], data.facilitators[], data.farmers[] (not sent, defaults to []), data.districts, data.current_user_id | Cached in SharedPreferences; if parsing fails, falls back to the cache | Yes (declared before /{id}) |
| 80 | GET | aesa-sessions/stats | – | data.total_sessions, data.total_observations | – | Yes |
| 81 | GET | aesa-sessions/export | status, date_from, date_to, group_id | data (Map) | Unused by screens | Yes |
| 82 | GET | aesa-sessions/{sid}/crop-observations | – | data[] or data.data[] | – | Yes |
| 83 | GET | aesa-crop-observations/{id} | – | data | – | Yes |
| 84 | POST | aesa-sessions/{sid}/crop-observations | form crop fields | data | 201 | Yes |
| 85 | POST + `_method=PUT` | aesa-crop-observations/{id} | form | data | – | Yes, PUT |
| 86 | POST + `_method=DELETE` | aesa-crop-observations/{id} | – | code | – | Yes, DELETE |

### 7. Enterprises and production protocols (services/enterprise_service.dart, models/enterprise_model.dart)

`Enterprise.fromJson` and `ProductionProtocol.fromJson` use only safe parsers (`int.tryParse`, `?.toString()`, `is_active == true\|1`). `production_protocols` uses `as List`.

| # | Method | Path | Request | Response keys read | Fragility | Route |
|---|---|---|---|---|---|---|
| 87 | GET | enterprises | sort_by, sort_order, type?, is_active '1'/'0', search? | data[] → Enterprise | Guarded by `is Map<String,dynamic>` and `code == 1`. `sort_by` goes straight into `orderBy`. | Yes, EnterpriseController@index |
| 88 | GET | enterprises/{id} | – | data → Enterprise | – | Yes, @show |
| 89 | GET | enterprises/statistics | – | data (Map) | Declared before /{id} | Yes |
| 90 | GET | production-protocols/enterprise/{eid} | – | data.protocols[], data.enterprise, data.summary (Map) | `data['protocols']` requires data to be a Map | Yes, ProductionProtocolController@getByEnterprise |
| 91 | GET | production-protocols/timeline/{eid} | – | data.enterprise, data.timeline (List), data.summary | – | Yes, @timeline |

### 8. Farm management (services/farm_service.dart: package:http, `baseUrl = AppConfig.API_BASE_URL`, so the URLs are correct)

Headers sent: Authorization Bearer, User-Id, Accept json.

Every call requires **statusCode == 200**, except createFarm and createCustomActivity, which also accept 201. A body is only read for those statuses. Any other status throws `"Failed to …: <status>"`, and the backend's 4xx/5xx `message` is never shown.

**FarmModel.fromJson** (models/farm_model.dart):
- **Fragile:** `id`, `enterprise_id` and `user_id` use int.parse when the value is not an int, so they must be present.
- **Non-null String fields:** `name`, `status`. `description`, `location_text`, `photo` and `photo_url` are nullable.
- **`DateTime.parse(json['start_date'])`:** required, and throws on null or a bad format. expected_end_date, actual_end_date, created_at and updated_at also use `DateTime.parse` when non-null.
- **Safe:** overall_score (tryParse), completed_activities_count and total_activities_count (int or tryParse), is_active (1/true), enterprise → Enterprise.fromJson, activities `as List`.

**FarmActivityModel.fromJson** (models/farm_activity_model.dart):
- **Non-null typed fields:** `id`, `farm_id` and `scheduled_week` are `int` with no parsing, `activity_name` and `status` are String, and `DateTime.parse(scheduled_date)` is required. A null or string value throws.
- `production_protocol_id` is `int?` with no parsing.
- `weight` uses `json['weight'] ?? 3` (int). target_value, actual_value and score use tryParse. is_mandatory accepts 1 or true.

| # | Method | Path | Request | Response keys read | Status expected | Fragility | Route |
|---|---|---|---|---|---|---|---|
| 92 | GET | farms[?status=] | – | code, data[] → FarmModel | 200 | `data as List` | Yes, FarmController@index |
| 93 | GET | farms/{id} | – | data → FarmModel | 200 (404 → throw) | – | Yes, @show |
| 94 | POST | farms (multipart) | enterprise_id, name, description?, start_date (ISO-8601 with time), gps_latitude?, gps_longitude?, location_text?, photo (file) | data → FarmModel | **201** or 200 | Backend returns 201: accepted. A 422 is thrown with only the status code. | Yes, @store |
| 95 | POST multipart + `_method=PUT` | farms/{id} | name?, description?, status? (planning/active/completed/abandoned), gps_*, location_text?, photo? | data → FarmModel | 200 | Spoofing works. Changing the backend to 201 or 204 would break this. | Yes, PUT @update |
| 96 | DELETE | farms/{id} | – | code | 200 | – | Yes, @destroy |
| 97 | GET | farms/{id}/activities[?status=] | – | data[] → FarmActivityModel | 200 | `data as List` | Yes, @getActivities |
| 98 | POST multipart | farm-activities/{id}/complete | completion_date (ISO), actual_value?, notes?, photo? | data → FarmActivityModel | 200 | – | Yes, @complete |
| 99 | POST JSON | farm-activities/{id}/skip | {notes} | data → FarmActivityModel | 200 | Backend requires `notes`. Empty `""` becomes null, then 422, then "Failed to skip activity: 422". | Yes, @skip |
| 100 | GET | farm-activities/overdue | – | data[] | 200 | – | Yes (declared before /{id}) |
| 101 | GET | farm-activities/upcoming | – | data[] | 200 | – | Yes |
| 102 | GET | farms/{id}/stats | – | data (Map: overall_score, progress_percentage, days_running, completed_activities, total_activities, status_breakdown) | 200 | Returned as a raw Map | Yes, @getStats |
| 103 | POST JSON | farm-activities | farm_id (int), activity_name, activity_description, scheduled_date (yyyy-MM-dd), is_mandatory (bool) | data → FarmActivityModel | **201** or 200 | Backend returns 201 (OK). Validation `activity_description min:10` and `scheduled_date after_or_equal:today` returns 422, shown only as the status code. The fresh model's `farm_id` is whatever the JSON sent (int today); `scheduled_week` is set. | Yes, FarmActivityController@store |

### 9. Locations (services/location_service.dart, models/LocationModel.dart; LoactionModel.dart makes no API call)

`LocationModel.fromJson` uses **`id: json['id'] as int`** and **`parent: json['parent'] as int?`**. Both fail if the ids are strings. name, type, code, name_text and detail use `?.toString()`.

| # | Method | Path | Success check | Response | Fragility | Route |
|---|---|---|---|---|---|---|
| 104 | GET | locations | **`response['success'] == true`** | data[] | Depends on ApiResponser's `success` key | Yes, ApiResurceController@locations |
| 105 | GET | locations/districts (used by AccountProfileScreen and universal_user_registration_screen) | `response['code'] == 1` | data[] {id, name, code} | Served by LocationController, **which has no `success` key**. Fine, because this method checks `code`. Region and Country rows are included (see rows 40–42). | Yes, LocationController@getDistricts (duplicate overrides ApiResurceController) |
| 106 | GET | locations/sub-counties | `success == true` | data[] {id, name, district_name} (raw DB::select) | Unused | Yes, ApiResurceController@locations_sub_counties |
| 107 | GET | locations/sub-counties/{districtId} | `success == true` | data[] | Unused. Note the second URI `locations/subcounties/{districtId}` (LocationController, uses the nonexistent `parent_id` column in an orWhere, so it returns 500); the app does not use it. | Yes, ApiResurceController@locations_sub_counties_by_district |
| 108 | GET | locations/parishes/{id} | **`success == true`** | data[] | Two routes match (`{sub_county_id}` at :79, `{subcountyId}` at :428). The first registered wins, so ApiResurceController answers (it has the `success` key). **If the order changes, LocationController answers with no `success` key and this always throws.** | Yes, ApiResurceController@locations_parishes (shadows LocationController@getParishes) |
| 109 | GET | locations/{id} | `success == true` | data (Map) | Unused | Yes, @location_details |

### Critical flow shapes

**Facilitator dashboard**: `GET agent-vsla/dashboard`
```json
{"code": 1, "data": {"total_groups": "int|num", "total_members": "int", "active_cycles": "int"}}
```
Mismatches: none.

**My groups**: `GET agent-vsla/my-groups` (data MUST be a JSON array)
```json
{"code": 1, "data": [{"id": "int|string", "name": "string|null", "code": "string|null", "district": "string|null",
  "total_members": "int", "chairperson_name": "string|null", "meeting_frequency": "string|null",
  "active_cycle": null, "created_at": "string (non-null preferred)"}]}
```
When the group has an active cycle, `active_cycle` is an object `{"name": "string"}` instead of null.

**Group detail**: `GET agent-vsla/my-groups/{id}`
```json
{"code": 1, "data": {
  "group": {"name": "string", "code": "string", "description": "string|null", "district": "string|null",
            "subcounty": "string|null", "subcounty_text": "string|null", "parish": "string|null", "parish_text": "string|null",
            "village": "string|null", "meeting_frequency": "Weekly|Bi-weekly|Monthly|null", "meeting_day": "string|null",
            "meeting_venue": "string|null", "estimated_members": "int|null", "establishment_date": "string|null",
            "ip_name": "string|null", "created_at": "string",
            "chairperson": "null | {name, phone}", "secretary": "null | {name, phone}", "treasurer": "null | {name, phone}"},
  "members": [{"id": "int", "name": "string", "first_name": "string", "last_name": "string", "phone_number": "string",
               "sex": "string", "role": "Member|Chairperson|Secretary|Treasurer"}],
  "cycles": [{"name": "string", "start_date": "string", "end_date": "string", "is_active": "bool (must be true/false)"}]}}
```
Mismatches: none for the GET.
- **Delete preview** expects `data.will_delete` (Map\<string,int\>) and `data.total_records_to_delete` (int), but the backend sends `data_counts` and `total_records`.
- **Set-password sheet** in the detail screen sends `confirmation_text`, but the backend requires `new_password` (dead code today).

**Meetings tab**: `GET agent-vsla/all-meetings`
```json
{"code": 1, "data": [{"meeting_number": "int", "group_name": "string", "group_code": "string", "meeting_date": "string",
   "members_present": "int", "members_absent": "int",
   "total_savings": "NUM (not string)", "total_loans": "NUM", "total_loans_repaid": "NUM", "total_welfare": "NUM",
   "processing_status": "string", "has_errors": "bool", "has_warnings": "bool"}]}
```
`GET agent-vsla/meeting-groups`:
```json
{"code": 1, "data": [{"id": "INT", "name": "string", "code": "string", "member_count": "int",
                      "active_cycle": "null | {id: INT, saving_type: string}"}]}
```

**Member registration and sync**
```json
POST members        -> 201 {"code": 1, "message": "string", "data": {"id": "int", "avatar": "string|null", "...Member keys": "..."}}
POST members/{id}   (_method=PUT) -> 200 {"code": 1, "data": {"...Member": "..."}}
POST members/sync   -> app expects {"code": 1, "message": "string", "data": {"synced": "int", "failed": "int", "results": []}}
```
Today the backend returns `{"code": 1, "data": {"success": [{"temp_id", "server_id", "member"}], "failed": [{"temp_id", "error"}]}}`.

Mismatches:
- (a) The sync response keys differ (see above).
- (b) The app clears its whole queue whenever code==1 (partial failures are lost).
- (c) The sync create path omits group_id.
- (d) The sync update path writes balance, loan_balance and status sent by the client.
- (e) education_level enum mismatch (422).
- (f) The app reads `district_name`, `subcounty_name` and `parish_name`, but the backend sends `district`, `subcounty` and `parish`.

**FFS training sessions**
```json
GET ffs-training-sessions             -> {"code": 1 (int), "data": ["<Session>"]}
GET ffs-training-sessions/{id}        -> {"code": 1, "data": {"<Session>": "...",
        "participants": [{"id": "int", "user_id": "int", "user_name": "string", "attendance_status": "string", "remarks": "string|null"}],
        "resolutions":  [{"id": "int", "resolution": "string", "description": "string", "gap_category": "string",
                          "responsible_person_id": "int|null", "responsible_person": "{first_name,last_name,name}|null",
                          "target_date": "date", "status": "string", "follow_up_notes": "string", "completed_at": "date",
                          "is_overdue": "bool|1"}]}}
GET .../{id}/expected-members         -> {"code": 1, "data": [{"id": "INT", "name": "string"}]}
POST .../{id}/wizard-report           -> {"code": 1, "message": "string", "data": "{...} (optional)"}
GET .../stats                         -> {"code": 1, "data": {"total_sessions": "int", "scheduled": "int", "ongoing": "int", "completed": "int"}}
```
Each `<Session>` contains: id, group_id, group_ids[], group_names[], group_name, facilitator_id, facilitator_name, co_facilitator_id, co_facilitator_name, title, description, topic, session_date, start_time, end_time, venue, session_type, session_type_text, status, status_text, report_status, report_status_text, submitted_at, submitted_by_id, expected_participants, actual_participants, materials_used, notes, challenges, recommendations, photo, gps_latitude, gps_longitude, photos[], attending_facilitator_ids[], attending_facilitator_names[], report_submitted_at, created_by_id, created_at, updated_at, participants_count, resolutions_count.

Mismatches:
- `serializeSession` does **not** send gps_latitude, gps_longitude, photos, attending_facilitator_ids, attending_facilitator_names or report_submitted_at, so a submitted wizard report's GPS, photos and facilitators never show again.
- Resolutions send `responsible_person_name` (flat), but the app reads the nested `responsible_person`.
- PUT updates send literal `group_ids[i]` JSON keys, so group edits are ignored.
- Wizard photos are local file paths.
- `code` must stay an int (wrappers assign it to `int`).

**Farm and farm activities**
```json
GET farms                       -> 200 {"code": 1, "data": ["<Farm>"]}
POST farms                      -> 201|200 {"code": 1, "data": "<Farm>"}
POST farms/{id} (_method=PUT)   -> 200 ONLY
GET farms/{id}/activities       -> 200 {"code": 1, "data": ["<Activity>"]}
POST farm-activities/{id}/complete|skip -> 200 ONLY {"code": 1, "data": "<Activity>"}
POST farm-activities            -> 201|200 {"code": 1, "data": "<Activity>"}
GET farms/{id}/stats            -> 200 {"code": 1, "data": {}}
```
Field requirements:
- `<Farm>`: id, enterprise_id and user_id must be present (int or numeric string). name and status are **non-null Strings**. start_date is a **parseable non-null date** (Laravel date cast to ISO is fine). gps_latitude, gps_longitude and overall_score may be numeric strings. completed_activities_count and total_activities_count are ints. is_active is bool or 1. enterprise is `<Enterprise>` or null. activities is a list of `<Activity>` or null.
- `<Activity>`: id, farm_id and scheduled_week are **native ints, non-null**. activity_name and status are **non-null Strings**. scheduled_date is a **non-null date**. production_protocol_id is int or null. weight is int or null. is_mandatory is bool or 1. target_value, actual_value and score are num or string.

Mismatches today: none. Status codes match the app's expectations (store returns 201, which the app accepts). Any 422 or 404 loses its server message.

**AESA session create and submit**
```json
POST aesa-sessions        -> 201 {"code": 1 (INT), "data": {"id": "int|numeric string, REQUIRED", "data_sheet_number": "string", "status": "draft",
                                  "observations": [{"id": "REQUIRED", "aesa_session_id": "REQUIRED", "photos": "array|null"}],
                                  "crop_observations": ["...same..."]}}
POST aesa-sessions/{id} + _method=PUT -> 200 {"code": 1, "data": "{session}"}
POST aesa-sessions/{id}/submit        -> {"code": 1}   (any 4xx JSON {"code": 0, "message": "..."} is toasted)
```
Mismatches: none.

### Broken / missing routes

| Call site | Problem |
|---|---|
| `GET groups` (models/GroupModel.dart via DynamicTable; screens/forms/PWDFormCreateScreen.dart:811) | **No route** (404). Legacy only. |
| `GET ffs-groups/subcounties/{id}`, `GET ffs-groups/parishes/{id}` (screens/profiling/ffs_group_registration_screen.dart:103, 125) | Routes exist but **always return 500**: FfsGroupController@getSubcounties/getParishes query the nonexistent `locations.parent_id` column (and `type='Subcounty'`). The subcounty and parish dropdowns are always empty. |
| `POST ffs-groups` with secondary_value_chains (ffs_group_registration_screen.dart:208) | Returns 422 whenever at least one is chosen (scalar list encoding, see section 3). |
| `POST agent-vsla/my-groups/{id}/members/{mid}/set-password` from facilitator_group_detail_screen.dart:1117 | Sends `confirmation_text`, but the backend requires `new_password`, so 422 (method currently unreferenced). |
| `GET agent-vsla/my-groups/{id}/delete-preview` | Route OK; **response key mismatch** (`will_delete` and `total_records_to_delete` vs `data_counts` and `total_records`). |
| `POST members/sync` | Route OK; **response shape mismatch** plus data-loss and data-integrity issues (section 4, row 49). |
| `PUT ffs-training-sessions/{id}` | Route OK; `group_ids[i]` keys in the JSON body are ignored. |
| `GET locations/districts` | **Duplicate declaration.** The later LocationController@getDistricts (routes/api.php:427) overrides ApiResurceController@locations_districts (:75) and returns Region and Country rows as districts. |
| `GET locations/parishes/{id}` | Two URI patterns. It works only because ApiResurceController (:79) is registered before LocationController (:428). LocationService needs the `success` key, which only the ApiResurceController version provides. |
| No method mismatches found | AESA, members and farms updates all send `_method=PUT`/`DELETE` on POST, which matches the PUT and DELETE routes. Every agent-vsla PUT is a real PUT. |

### Top fragility risks (ranked)

1. **Member offline sync loses and corrupts data.** services/member_offline_service.dart:403-407 calls `db.delete(queueTable)` whenever `code==1`, but MemberController@sync returns code 1 even when items fail. services/member_service.dart:188-190 reads `data.synced`, `data.failed` and `data.results`, but the backend returns `data.success[]` and `data.failed[]`. The sync create path does not set `group_id`, and the update path writes the client's `balance`, `loan_balance` and `status` over the server values (app/Http/Controllers/MemberController.php sync loop).
2. **Group location dropdowns return 500.** app/Http/Controllers/FfsGroupController.php:242 and :266 use `parent_id`, which does not exist in `locations`. The calls are at screens/profiling/ffs_group_registration_screen.dart:103 and :125.
3. **`locations/districts` duplicate route.** routes/api.php:427 overrides :75. LocationController@getDistricts (`orWhere('parent','<',1)`) adds "Central Region", "Kampla, Uganda" and "Karamoja Region" to the district pickers at screens/members/MemberRegisterScreen.dart:56, MemberEditScreen.dart:52, MemberRegistrationScreen.dart:71 and services/location_service.dart:27. Reordering the parishes routes (api.php:79 vs :428) would also break location_service.dart:83, because LocationController omits the `success` key.
4. **Scalar list in a FormData POST becomes a single string.** screens/profiling/ffs_group_registration_screen.dart:208 (`secondary_value_chains`) gets 422. The same applies to any future array field sent with `Utils.http_post`. Related: services/ffs_training_session_service.dart:83 (PUT JSON) sends literal `group_ids[i]` keys from screens/ffs_activities/ffs_session_form_screen.dart:239, so session group edits are silently dropped.
5. **Numbers must arrive as native types.**
   - `(m['total_savings'] ?? 0).toDouble()` in screens/facilitator/facilitator_meetings_screen.dart:285 and :355-358 is evaluated in `build()` with no catch. It crashes if the backend's `(float)` casts in VslaFacilitatorController@allMeetings are removed (decimal columns then arrive as strings).
   - `as int` on ids at facilitator_meetings_screen.dart:78 and :87, screens/ffs_activities/ffs_session_report_wizard_screen.dart:111, :520 and :529, models/LocationModel.dart:24-26 (`id`, `parent`), and models/farm_activity_model.dart:44-50 (`id`, `farm_id`, `scheduled_week`, `production_protocol_id`, all non-null typed with no parsing). These would crash if the PDO driver stringified ints or if a value were null.
6. **Facilitator delete preview keys.** screens/facilitator/delete_group_screen.dart:75-77 reads `will_delete` and `total_records_to_delete`. VslaGroupManagementController@deleteGroupPreview (:72-80) sends `data_counts` and `total_records`, so the user is told "0 records" before an irreversible delete.
7. **farm_service accepts only HTTP 200.** services/farm_service.dart:38, 76, 255, 290, 327, 385, 424, 459, 499 and 539 (201 is also allowed only at :161 and :588). Changing FarmController@update or FarmActivityController@complete/@skip to 201 or 204 breaks them. Every 422 message is replaced by "Failed to …: 422" (for example, skip with empty notes, or a custom activity with a description under 10 characters or a past date).
8. **Member enum and key mismatches.** The education options 'Secondary', 'Tertiary' and 'University' at screens/members/MemberRegisterScreen.dart:63-65 and MemberEditScreen.dart:59-61 are rejected by MemberController@store/@update (422). models/member_model.dart:84, 88 and 92 read `district_name`, `subcounty_name` and `parish_name`, which the backend does not send.
9. **FFS wizard data loss.** services/ffs_training_session_service.dart:230 sends local file paths as `photos[i]` (nothing is uploaded). `serializeSession` (FfsTrainingSessionController.php:86-160) never returns gps, photos or attending_facilitator_* for display (models/ffs_training_session_model.dart:173-193). The resolution responsible person is sent flat (`responsible_person_name`), but the app parses the nested `responsible_person` (model :450).
10. **`code` must stay int 1.**
    - Direct `== 1` checks: services/aesa_service.dart (all), services/enterprise_service.dart:28, services/ffs_training_session_service.dart:108, services/location_service.dart:30, screens/groups/GroupRegisterScreen.dart:72 and :128, delete_group_screen.dart:71 and :106, facilitator_set_password_screen.dart:58, MemberDetailsScreen.dart:115 and :134.
    - The FFS wrappers assign `json['code'] ?? 0` to an `int` field (ffs_training_session_service.dart:397, :433 and others), so a TypeError is thrown if `code` is ever `"1"` or `true`.
    - The direct callers that do not check `is Map` (MemberDetailsScreen, delete_group_screen, facilitator_set_password_screen, GroupRegisterScreen, member_service) wrap the call in try/catch, so a String error body leads to a generic "Error/Network error" toast rather than a crash.

---

## 5. E-commerce, Payments & Legacy Models

Scope: e-commerce (orders, products, cart, wishlist, reviews, delivery addresses, vendors, chat), payments (Pesapal, Universal Payments, Membership) and the generic legacy models. 120 call sites in total: 106 from calls.tsv in scope, 2 more `membership-status` gates (main_screen.dart, vsla_tab.dart) that are included because they gate the main screens, and 12 `ApiService` calls.

Abbreviations: **ARC** = `App\Http\Controllers\ApiResurceController`. **Envelope**: ARC `success()` returns `{success:true, code:1, status:1, message, data?}`, and `data` is omitted when null. ARC `error()` returns `{success:false, code:0, status:0, message}`, usually with HTTP 400. `UniversalPaymentController` and parts of `PesapalController` return `{success:bool, message, data}` with **no `code` key**. `RespondModel` = safe wrapper (see brief). "direct `code==1`" = caller indexes the raw response itself.

**Double-prefix** = path passed as `'${AppConfig.API_BASE_URL}/x'` into `Utils.http_*`. The final URL becomes `https://fao-ffs-mis.org/api/https://fao-ffs-mis.org/api/x`, which is always 404 and therefore BROKEN whatever the backend does.
**ApiService prefix**: `ApiService` Dio `baseUrl = "https://fao-ffs-mis.org/api"` (no trailing slash). Dio 5.8 builds the URL by plain concatenation (`baseUrl + path`), so `'api/products/1'` becomes `https://fao-ffs-mis.org/apiapi/products/1`, which is always 404 and therefore BROKEN.

### Reachability method
Live roots: `main.dart` → `OnBoardingScreen` → `MainScreen` → `HomeTab`. HomeTab quick actions are UserAccountDashboard, MyShopScreen ("Sell Farm Produce"), Farms, Weather, AESA, AI chat, MoreTab. Its core modules are VslaTab, EAdvisoryTab, **MarketplaceTab**, Profiling and FFS Activities. `main.dart` also calls `Utils.boot_system()` at startup.
Notable dead branches:
- `FullApp` (route `FullApp.tag`) is registered but only navigated to from dead screens (`UnpaidOrdersCheckScreen`, `ModernCheckoutScreen`, `ModernCartController`). That makes all of `screens/full_app/section/*` dead: SectionOrders, OrderDetailsScreen, SectionCart, SectionDashboard, AccountSection → BecomeVendorScreen/Form, plus `OrderPaymentScreen`.
- `vsla_tab.dart` methods `_buildQuickActions()` (FFS Sessions / Events / Projects / Withdrawals buttons), `_buildFinancialSummary()` (→ InvestmentsTab) and others are **never called**. So EventsScreen, SessionLocalScreen, ProjectsListScreen → ProjectDetailsScreen → `InitializePaymentScreen` → `UniversalPaymentDetailsScreen` are unreachable, and `MyAccountTab` is never instantiated.
- `user_account_dashboard_screen.dart::_buildMembershipStatusCard()` is never called, so `MembershipPaymentListScreen` is unreachable.
- `bindings/AppBindings.dart` is never used, so `AccountController`, `WishlistController`, `PaymentController`, `ApiService`, `ProductService` and `AppStateController` are dead. `AccountController` is otherwise only used by dead `AccountDashboardScreen`, `AddressManagementScreen` and `OrderHistoryScreen`.
- Legacy school/NGO screens (Students, Classes, Subjects, Sessions, PWD forms, Jobs, News, Counselling, Chats) and `ReviewsScreen`/`ReviewFormScreen`/`ProductReviewsSection`/`WishlistScreen`/`WishlistButton` have no live navigation path.

Legend: **Live** = reachable from a live screen. **Live-cond** = live code that only runs in a condition. **Dead** = unreachable or unreachable statement.

---

### Orders / Cart / Checkout

| # | Method | Path (normalised) | Call site | Request keys | Response keys read | Type assumptions | Fragility notes | Backend route | L/D |
|---|---|---|---|---|---|---|---|---|---|
| O1 | POST | `orders-create` | screens/cart/CheckoutScreen.dart:93 | multipart: `items` (JSON string of CartItem.toJson: id, product_name, product_price_1, product_quantity, product_feature_photo, product_id, color, size), `delivery` (JSON string of OrderOnline.toJson + phone_number_1/2, phone_number, **pay_on_delivery** bool) + injected `user`, `User-Id` | `code`, `message`, `data` → `OrderOnline.fromJson(data)` (id must be >0) | data = Order object (Map) | RespondModel (safe). **Contract mismatch:** backend reads `$r->pay_on_delivery` at TOP level, but the app sends it only inside the `delivery` JSON, so cash-on-delivery orders are saved as `PENDING_PAYMENT`/`manual`. Backend dereferences `$item->color`, `$item->size`, `$delivery->customer_phone_number_1`, so if the app drops those keys PHP raises "Undefined property" (500). `delivery_district` must be a DeliveryAddress id, or shipping cost and customer fields are silently skipped (all inside a try). | Yes → ARC@orders_create | **Live** (Marketplace → ProductScreen → CartScreen → CheckoutScreen) |
| O2 | GET | `orders` | models/OrderOnline.dart:241 (`getOnlineItems`, called by CheckoutScreen:109) | none (headers only) | `code`, `data` (List) → OrderOnline.fromJson each: id, created_at, updated_at, user, order_state, amount, date_created, payment_confirmation, date_updated, mail, delivery_district, temporary_id, temporary_text, description, customer_name, customer_phone_number_1/2, customer_address, order_total, order_details, stripe_id/text/url/paid, delivery_method, delivery_address_id/text/details, delivery_amount, payable_amount, items, pay_on_delivery | data must be a List (`runtimeType` contains 'List'); fields are parsed with to_str. `pay_on_delivery` is accepted as true/1/'1'/'true' | Paginating or wrapping the list (e.g. `{data:{data:[..]}}`) makes it silently empty. Local table is wiped (`deleteAll`) before insert when online. `items` is a JSON-encoded string from the backend (`json_encode($order->get_items())`), so changing it to an array is tolerated by to_str but the stored text is no longer JSON. | Yes → ARC@orders_get (auth('api') JWT, else User-Id) | **Live** |
| O3 | GET | `orders` | controllers/AccountController.dart:79 | none | direct `response['code']==1`; `data` as `List<dynamic>` | `List<dynamic> orderData = response['data'] ?? []` | Hard cast: a Map in `data` throws (caught) | Yes → ARC@orders_get | Dead |
| O4 | GET | `order?id=` | models/OrderOnline.dart:133 (`getOrder`) | query `id` | code, message, data → OrderOnline | Map | — | Yes → ARC@order | Dead (no callers) |
| O5 | GET | `orders` (OrderOnline2) | models/OrderOnline2.dart:97 | — | — | — | Whole file is commented out | Yes → ARC@orders_get | Dead (commented) |
| O6 | POST | `orders-create` | controllers/ModernCartController.dart:550 | `items` (JSON: product_id, product_name, product_quantity, product_price_1, color, size), `delivery` (JSON), `pay_on_delivery` ('true'/'false', top level, which is correct here) | direct `response['code']==1`, `response['data']`, `response['message']` | — | Direct indexing crashes on the String returned by a non-JSON error | Yes → ARC@orders_create | Dead |
| O7 | POST | `cancel-order` | screens/cart/UnpaidOrdersCheckScreen.dart:342 | `id` | code, message | — | RespondModel | Yes → ARC@orders_cancel | Dead |
| O8 | POST | `cancel-order` | screens/full_app/section/OrderDetailsScreen.dart:464 | `id` | code, message | — | RespondModel | Yes → ARC@orders_cancel | Dead |
| O9 | POST | `cancel-order` | screens/payment/OrderPaymentScreen.dart:1279 | `id` | code, message | — | — | Yes → ARC@orders_cancel | Dead |
| O10 | GET | `orders/{id}` | screens/payment/OrderPaymentScreen.dart:1107 | — | direct `code==1`; `data` as `Map<String,dynamic>`; data.payment_status, data.payment_confirmation | Map | Typed `Map<String,dynamic> orderData = …` | Yes → ARC@orders_get_by_id | Dead |
| O11 | GET | `orders` | screens/payment/OrderPaymentScreen.dart:1163 | — | direct `code==1` only | — | — | Yes → ARC@orders_get | Dead |
| O12 | POST | `orders/{id}/update-payment-status` | screens/payment/OrderPaymentScreen.dart:1042 | payment status payload | direct `code`, `message` | — | — | **NO (missing)** (only GET orders/{id}) | Dead |
| O13 | GET | `ping` | screens/payment/OrderPaymentScreen.dart:1076 | — | non-null check only | — | Always non-null (GET helper returns a Map even on 404), so "connected" is always true | **NO (missing)** | Dead |
| O14 | GET | `user-accounts/dashboard` | controllers/AccountController.dart:134 | — | direct `code==1`; data.balance, data.formatted_balance, data.recent_transactions, data.statistics | `?? defaults` | — | Yes → UserAccountController@getUserAccountDashboard | Dead |
| O15 | POST | `user/update-profile` (double-prefixed) | controllers/AccountController.dart:194 | user_id, first_name, last_name, email, phone_number, avatar? | direct `code`, `message` | — | Double prefix. Even un-prefixed, `user/update-profile` does not exist (backend has `users/update-profile`) | **NO (double-prefix + missing)** | Dead |
| O16 | POST | `api/cart/apply-discount` (ApiService) | controllers/ModernCartController.dart:320 | `code` | ApiResponse | — | `…/apiapi/cart/apply-discount` | **NO (ApiService prefix + missing)** | Dead |
| O17 | GET | `api/delivery-addresses` (ApiService) | controllers/ModernCartController.dart:371 | — | ApiResponse List | — | `…/apiapi/…` | **NO (prefix)** | Dead |
| O18 | POST | `api/delivery/calculate-fee` (ApiService) | controllers/ModernCartController.dart:410 | `address_id` | ApiResponse | — | — | **NO (prefix + missing)** | Dead |
| O19 | GET | `api/products/{id}/stock` (ApiService) | controllers/ModernCartController.dart:647 | — | ApiResponse Map | — | — | **NO (prefix + missing)** | Dead |
| O20 | GET | `api/cart/sync` (ApiService) | controllers/ModernCartController.dart:824 | — | ApiResponse List | — | — | **NO (prefix + missing)** | Dead |

### Products / Shop / Vendors

| # | Method | Path | Call site | Request keys | Response keys read | Type assumptions | Fragility notes | Backend route | L/D |
|---|---|---|---|---|---|---|---|---|---|
| P1 | GET | `products-1` | models/Product.dart:392 (`Product.getOnlineItems`; via MainController.getProducts, ProductsScreen, ProductSearchScreen) | none | `code`, `data` (List) → Product.fromJson: id, name, metric, currency, description, summary, price_1, price_2, feature_photo, rates, date_added, date_updated, user, category, sub_category, supplier, url, status, in_stock, keywords, p_type, category_text, has_colors, colors, has_sizes, sizes, local_id, home_section_1/2/3 | all to_str. **`rates` must be a JSON STRING** of `[{id,src,thumbnail,product_id}]` (appended accessor `json_encode`s it). **`summary` is parsed with `jsonDecode` as a key→value map.** `keywords` is decoded as a JSON price-tier list | Backend casts `summary => json`, so it arrives as an object; to_str gives a Dart `{k: v}` string, `jsonDecode` fails, and product attributes are silently empty. If `rates` becomes a real array, images fall back to `feature_photo` only. `data` must be a List. Local table is wiped before insert. Backend returns up to 1000 rows with one Image query per row (N+1). | Yes → ARC@products_1 | **Live** (MarketplaceTab, ProductsScreen, ProductSearchScreen) |
| P2 | GET | `products-1` | models/MyProduct.dart:157 (`getMyProducts`, via ShopService.loadMyProducts) | none | `code`, `data` (List) → MyProduct.fromJson | `resp.data is List` | "My Shop" stores ALL products (no user filter; see comment "NO FILTER"). If the backend starts scoping `products-1` to the current user, MyShop changes meaning and Marketplace (P1) loses other sellers' products. | Yes → ARC@products_1 | **Live** (HomeTab → MyShopScreen) |
| P3 | GET | `categories?is_not_private=1` | models/ProductCategory.dart:111 (via MainController.getCategories from MarketplaceTab, ProductsScreen, ProductCreateScreenSimple) | query `is_not_private=1` | `code`, `data` (List): id, category, image, image_origin, banner_image, show_in_banner, show_in_categories, attributes, category_text | to_str | `data` must be a List. `show_in_banner == 'Yes'` drives banners. | Yes → ARC@categories | **Live** |
| P4 | POST | `product-create` / `products-update` (variable `endpoint`) | screens/shop/ProductCreateScreenSimple.dart:206 | name, price_1, price_2, category, description, status='Active', currency='UGX', local_id, p_type='No', has_colors='No', has_sizes='No', home_section_1/2/3='No', [id when edit] + injected `user` | `code`, `message` | — | RespondModel. 30 s timeout returns a synthetic Map. product_create needs `user` (from injected field) and `local_id` ≥ 6 chars, and links images by `Image.parent_local_id == local_id`. products_update has no ownership or auth check. | Yes → ARC@product_create / ARC@products_update | **Live** (MyShopScreen) |
| P5 | POST | `products-delete` | services/shop_service.dart:140 | `id` | `result['code']==1 \|\| result['success']==true`; `data`, `message` | direct indexing of the raw response | If `message` contains **"not found"**, the app treats it as success and deletes locally. A future auth check returning "User not found." would make the app silently drop the product locally. A String (non-JSON error) crashes `result['code']` (rethrown, shown in a toast). Backend: no ownership check. | Yes → ARC@products_delete | **Live** (MyShopScreen:730) |
| P6 | POST | `products-delete` | screens/shop/ProductScreen.dart:797 | `id` | code, message | — | RespondModel | Yes → ARC@products_delete | **Live** |
| P7 | GET | `users/{item.user}` | screens/shop/ProductScreen.dart:100 (`_contactSeller`, button at :852) | — | `data.phone` \|\| `data.phone_number_1` | String | Route does not exist, so 404 JSON → toast "Seller's phone number not available". Contact Seller is **broken today**. | **NO (missing)**: only `users/me`, `users/my-roles` | **Live** |
| P8 | GET | `vendors` | models/VendorModel.dart:172 (MarketplaceTab:66, VendorsScreen:71) | none | `code`, `data` (List) → ~55 user fields incl. `password`, `remember_token`, business_* | to_str | Backend returns raw `Administrator` rows: **password hash and remember_token are exposed** (no `$hidden`). Hiding them is safe for the app (to_str default ''). | Yes → ARC@vendors | **Live** |
| P9 | POST | `products-create` | services/shop_service.dart:93 | product map | `json.decode(response)['code']` | expects String | `json.decode` on the Map returned on success throws, so it always fails | **NO (missing)** (backend has `product-create`) | Dead (no callers) |
| P10 | POST | `products-update` | services/shop_service.dart:117 | product map + id | `json.decode(response)` | expects String | Same `json.decode(Map)` bug | Yes → ARC@products_update | Dead (no callers) |
| P11 | POST | `product-create` | screens/shop/ProductCreateScreen2.dart:623 | product form | code, message | — | — | Yes → ARC@product_create | Dead |
| P12 | POST | `images-delete` | screens/shop/ProductCreateScreen.dart:1000 | `id` | code, message | — | — | Yes → ARC@images_delete | Dead |
| P13 | POST | `products-delete` | screens/full_app/section/SectionCart.dart:751 | `id` | code, message | — | — | Yes → ARC@products_delete | Dead |
| P14 | POST | `become-vendor` | screens/vendors/BecomeVendorFormScreen.dart:517 | LoggedInUserModel.toJson() + id + optional `file` | code, message | — | — | Yes → ARC@become_vendor | Dead (only via FullApp AccountSection) |
| P15 | GET | `api/products/category/{id}` (ApiService) | services/ProductService.dart:73 | — | ApiResponse | — | `…/apiapi/…` | **NO** | Dead |
| P16 | GET | `api/products/search` (ApiEndpoints.productSearch) | services/ProductService.dart:122 | search params | ApiResponse | — | — | **NO** | Dead |
| P17 | GET | `api/products/{id}` | services/ProductService.dart:157 | — | ApiResponse Map | — | Even un-prefixed `api/products/1` = 404 | **NO** | Dead |
| P18 | GET | `api/products/featured` | services/ProductService.dart:182 | — | List | — | — | **NO** | Dead |
| P19 | GET | `api/products/on-sale` | services/ProductService.dart:205 | — | List | — | — | **NO** | Dead |
| P20 | GET | `api/products/{id}/related` | services/ProductService.dart:228 | — | List | — | — | **NO** | Dead |
| P21 | GET | `api/products` (ApiEndpoints.products) | services/ProductService.dart:258 | — | List | — | Would also hit catch-all `api/api/{model}` if the prefix were fixed | **NO (prefix)** | Dead |

### Wishlist (all double-prefixed)

| # | Method | Path | Call site | Request keys | Response keys read | Type assumptions | Fragility | Backend route | L/D |
|---|---|---|---|---|---|---|---|---|---|
| W1 | GET | `wishlist_get` | models/WishlistItem.dart:72 | — | `data` (List) → user_id, product_id, product{Product} | — | Double prefix | **NO (double-prefix)**; un-prefixed exists → ARC@wishlist_get | Dead |
| W2 | POST | `wishlist_add` | models/WishlistItem.dart:91 | product_id | direct `code` | — | Double prefix | **NO (double-prefix)**; exists → ARC@wishlist_add | Dead |
| W3 | POST | `wishlist_remove` | models/WishlistItem.dart:115 | product_id | direct `code` | — | Double prefix | **NO (double-prefix)**; exists → ARC@wishlist_remove | Dead |
| W4 | POST | `wishlist_check` | models/WishlistItem.dart:139 | product_id | `data` | — | Double prefix | **NO (double-prefix)**; exists → ARC@wishlist_check | Dead |
| W5 | POST | `wishlist_clear` | models/WishlistItem.dart:183 | — | direct `code` | — | Double prefix **and** route missing | **NO (missing)** | Dead |
| W6 | POST | `wishlist_remove` | models/WishlistItem.dart:207 | product_id | direct `code` | — | Double prefix | **NO (double-prefix)** | Dead |

### Reviews (all double-prefixed; even if fixed, write routes use `auth:sanctum`, which the app's JWT/User-Id does not satisfy: 401 `"Unauthenticated."`)

| # | Method | Path | Call site | Request keys | Response keys read | Type assumptions | Fragility | Backend route | L/D |
|---|---|---|---|---|---|---|---|---|---|
| R1 | GET | `reviews?product_id=&page=` | models/Review.dart:114 | query in URL | `resp.data['data']` (paginator) | `RespondModel resp = await Utils.http_get(...)` | **Type error:** a Map is assigned to `RespondModel`, so it always throws (caught) | NO (double-prefix); un-prefixed → Api\ReviewController@index (public) | Dead |
| R2 | GET | `reviews/stats/{productId}` | models/Review.dart:134 | — | `data` → ReviewStats (rating_distribution as Map) | — | Path-param form does not exist (backend `reviews/stats?product_id=`) | **NO** | Dead |
| R3 | GET | `reviews/can-review/{productId}` | models/Review.dart:152 | — | `canReview` | — | — | **NO** | Dead |
| R4 | GET | `reviews/user-review/{productId}` | models/Review.dart:170 | — | `data` | — | Backend `reviews/user-review` (query, sanctum) | **NO** | Dead |
| R5 | POST | `reviews` | models/Review.dart:199 | product_id, rating, comment, images | code, message | RespondModel type error (as R1) | sanctum | NO (double-prefix; sanctum) | Dead |
| R6 | POST | `reviews/{id}` (update) | models/Review.dart:228 | rating, comment, images (no `_method`) | code | as R1 | Backend only has PUT, so 405 | **NO (method mismatch)** | Dead |
| R7 | POST | `reviews/{id}` + `_method=DELETE` | models/Review.dart:249 | `_method` | code | as R1 | Method spoofing would reach `DELETE reviews/{review}` (sanctum) | NO (double-prefix; sanctum) | Dead |
| R8 | POST | `reviews/{id}/helpful` | models/Review.dart:269 | — | code | as R1 | — | **NO (missing)** | Dead |
| R9 | GET | `reviews/can-review?product_id=&user_id=` | models/Review.dart:290 | — | `data.can_review` | as R1 | Would be captured by `reviews/{review}` (sanctum) | **NO** | Dead |
| R10 | GET | `reviews/user-review?product_id=&user_id=` | models/Review.dart:304 | — | data | as R1 | sanctum | NO (double-prefix; sanctum) | Dead |
| R11 | POST | `api/reviews/report` | widgets/ReviewCard.dart:383 | review_id, reason | direct `code` | — | Double prefix + `api/` + missing | **NO** | Dead |

### Delivery addresses

| # | Method | Path | Call site | Request keys | Response keys read | Type assumptions | Fragility | Backend route | L/D |
|---|---|---|---|---|---|---|---|---|---|
| A1 | GET | `delivery-addresses` | models/DeliveryAddress.dart:75 (via DeliveryAddressPickerScreen ← DeliveryAddressScreen ← CartScreen) | — | `code`, `data` (List): id, created_at, updated_at, address, latitude, longitude, shipping_cost | to_str | These are delivery **zones** with shipping_cost (global list, last 100); `id` is sent back as `delivery_district` in O1. `data` must be a List. | Yes → ARC@delivery_addresses | **Live** |
| A2 | GET | `delivery_addresses` (underscore) | controllers/AccountController.dart:109 | — | direct `code`; `data` as List | — | Wrong spelling | **NO (missing)**; backend is `delivery-addresses` | Dead |
| A3 | POST | `addresses/add` | controllers/AccountController.dart:243 | user_id, title, address, district, contact_person, contact_phone, is_default | direct `code`, `message` | — | Double prefix + missing | **NO** | Dead |
| A4 | POST | `addresses/update` | controllers/AccountController.dart:282 | address_id + A3 keys | same | — | same | **NO** | Dead |
| A5 | POST | `addresses/delete` | controllers/AccountController.dart:314 | address_id, user_id | same | — | same | **NO** | Dead |
| A6 | POST | `addresses/set-default` | controllers/AccountController.dart:340 | address_id, user_id | same | — | same | **NO** | Dead |

### Chat (routes exist but **controller methods do not exist**: ARC has no `chat_start`, `chat_send`, `chat_mark_as_read`, `chat_heads` or `chat_messages`, so every call is a 500 BadMethodCallException)

| # | Method | Path | Call site | Request keys | Response keys read | Type assumptions | Fragility | Backend route | L/D |
|---|---|---|---|---|---|---|---|---|---|
| C1 | GET | `chat-heads` | models/ChatHead.dart:122 | — | `code`, `data` List → ChatHead fields | to_str | 500 (method missing) | Route yes, **method MISSING** (ARC@chat_heads) | Dead (MainController.getChatHeads has no callers; ChatsScreen only from full_app_old) |
| C2 | GET | `chat-messages` | models/ChatMessage.dart:119 | params incl. chat_head_id, doDeleteAll | `code`, `data` List | to_str | 500 | Route yes, **method MISSING** | Dead |
| C3 | POST | `chat-send` | models/ChatMessage.dart:285 | ChatMessage.toJson() | code, message, data → ChatMessage | — | 500 | Route yes, **method MISSING** | Dead |
| C4 | POST | `chat-mark-as-read` | screens/chat/chat_screen.dart:126 | receiver_id, chat_head_id | — | — | 500 | Route yes, **method MISSING** | Dead |
| C5 | POST | `chat-start` | screens/chat/chat_screen.dart:727 | sender_id, receiver_id, product_id | code, message, data → ChatHead | — | 500 | Route yes, **method MISSING** | Dead |

### Pesapal

| # | Method | Path | Call site | Request keys | Response keys read | Type assumptions | Fragility | Backend route | L/D |
|---|---|---|---|---|---|---|---|---|---|
| PP1 | POST | `pesapal/initialize` | screens/payment/UniversalPaymentDetailsScreen.dart:156 | **payment_id**, amount, currency, description | direct `code==1`; `data` as `Map<String,dynamic>`; data.redirect_url \|\| data.payment_url; data.order_tracking_id \|\| data.transaction_id | Map | **Contract mismatch:** backend validates `order_id required\|exists:orders,id`, so it always returns code 0 "validation" for universal payments. The fallback "Pay Now" only works when `pesapal_redirect_url` is already on the payment. | Yes → PesapalController@initialize (wrong payload) | Dead (UniversalPayment flow unreachable, see U-rows) |
| PP2 | POST | `pesapal/initialize` | screens/payment/OrderPaymentScreen.dart:725 | order_id, amount, … | direct `code`; data.redirect_url, data.order_tracking_id | Map | — | Yes → PesapalController@initialize | Dead |
| PP3 | GET | `pesapal/check-pesapal-status/{orderId}` | screens/payment/OrderPaymentScreen.dart:802 | — | direct `code`; data.message, data.payment_status \|\| data.status | Map | — | Yes → PesapalController@status | Dead |
| PP4 | GET | `pesapal/status/{orderId}` | screens/payment/OrderPaymentScreen.dart:851 | — | direct `code`; data.status | Map | — | Yes → PesapalController@status | Dead |
| PP5 | GET | `pesapal/check-pesapal-status/{orderId}` | screens/payment/OrderPaymentScreen.dart:907 | — | as PP3 | — | — | Yes | Dead |
| PP6 | GET | `pesapal/status/{orderId}` | screens/payment/OrderPaymentScreen.dart:936 | — | as PP4 | — | — | Yes | Dead |
| PP7 | POST | `pesapal/initialize` | services/PesapalService.dart:99 | order_id, amount, … | direct `code`, data → PesapalTransaction | — | Double prefix | **NO (double-prefix)** | Dead |
| PP8 | GET | `pesapal/config` | services/PesapalService.dart:129 | — | direct `code`, data | — | Double prefix. Backend returns `success` with no `code`, so it would also fail | **NO (double-prefix)** | Dead |
| PP9 | GET | `pesapal/status/{orderId}` | services/PesapalService.dart:148 | — | code, data | — | Double prefix | **NO (double-prefix)** | Dead |
| PP10 | POST | `pesapal/callback` | services/PesapalService.dart:171 | order_id, … | code, data | — | Double prefix | **NO (double-prefix)** | Dead |
| PP11 | POST | `pesapal/verify` | services/PesapalService.dart:196 | — | data.verified | — | Double prefix + missing | **NO** | Dead |
| PP12 | GET | `pesapal/payment-methods` | services/PesapalService.dart:217 | — | data List | — | Double prefix + missing | **NO** | Dead |

### Universal Payments (envelope `{success, message, data}`, no `code`)

| # | Method | Path | Call site | Request keys | Response keys read | Type assumptions | Fragility | Backend route | L/D |
|---|---|---|---|---|---|---|---|---|---|
| U1 | POST | `universal-payments/initialize` | models/UniversalPayment.dart:588 (from InitializePaymentScreen:377) | payment_type, payment_category, user_id, customer_name, customer_phone, customer_email, customer_address, **payment_items** (list of maps {type,id,amount,description,metadata{…}} sent as multipart nested arrays), description, payment_gateway='pesapal', callback_url (null) | `resp['success']==true`; data.payment (→ fromJson); screen re-reads `response['data']['payment']` | resp is Map | Validator requires `payment_items.*.type/id(int)/amount`. Relies on Dio FormData bracket encoding of nested list/maps. On failure the else-branch does `resp['message']` (crashes if resp is the String error). Backend returns 201 (OK). | Yes → UniversalPaymentController@initialize | Dead (only via unreachable ProjectsList → ProjectDetails) |
| U2 | GET | `universal-payments/status/{id}` | models/UniversalPayment.dart:611 (InitializePaymentScreen:433, UniversalPaymentDetailsScreen:60/131/235) | — | `success`, `data.payment` → fromJson (`items_processed` via `Utils.int_parse`) | — | **Backend casts `items_processed` to boolean**, so JSON `true` → `int_parse('true')` = 0, so `itemsProcessed` is ALWAYS false. The UI never shows "processed" and keeps offering "Process items". | Yes → UniversalPaymentController@checkStatus | Dead |
| U3 | POST | `universal-payments/{id}/process` | screens/payment/UniversalPaymentDetailsScreen.dart:99 | — | `resp is Map && success==true`; message | — | Safe (checks `is Map`) | Yes → UniversalPaymentController@processItems | Dead |
| U4 | GET | `universal-payments?per_page=100&user_id&status&payment_type` | models/UniversalPayment.dart:515 | query in path | `success`, `data.data` (paginator list) | — | — | Yes → UniversalPaymentController@index | Dead (no callers) |
| U5 | GET | `universal-payments/{id}` | models/UniversalPayment.dart:544 | — | `success`, `data.payment` | — | — | Yes → UniversalPaymentController@show | Dead (no callers) |
| U6 | POST | `universal-payments/{id}/process` | models/UniversalPayment.dart:628 | — | `success`, message | — | else-branch `resp['message']` on a String crashes | Yes → UniversalPaymentController@processItems | Dead (no callers) |

### Membership

| # | Method | Path | Call site | Request keys | Response keys read | Type assumptions | Fragility | Backend route | L/D |
|---|---|---|---|---|---|---|---|---|---|
| M1 | GET | `membership-status` | screens/main_app/main_screen.dart:61 (`_checkMembershipOnLoad`, every MainScreen init) | none (relies on `User-Id` header; backend also accepts `user_id`/`user` query) | RespondModel `code==1`; `data.is_admin`, `data.has_valid_membership` | accepts `true` or `1` ONLY | **App-gating.** If code==1 and NOT (`is_admin`∈{true,1}) and NOT (`has_valid_membership`∈{true,1}), then `Get.offAll(MembershipPaymentScreen(canGoBack:false))`, which is a hard lockout (back disabled; "Proceed to payment" is stubbed "coming soon"). `"1"`, `"true"`, `"Yes"` or missing key count as false. Fails OPEN on code≠1 or exception. Backend today: `User::hasValidMembership()` returns `true` unconditionally; `isAdmin()` = `user_type=='admin'`. `$request->user()` uses the web guard (null in API), so the user is resolved from the `User-Id` header. | Yes → ARC@membership_status (no auth middleware) | **Live** |
| M2 | GET | `membership-status?user_id=` | screens/main_app/tabs/vsla_tab.dart:141 | `user_id` | direct `response['code']==1`; `data['is_admin']`, `data['has_valid_membership']` | same bool/int rule | Same lockout via `Get.off(MembershipPaymentScreen(canGoBack:false))`. Skipped client-side if `user_type=='admin'`. `data` null throws (caught, fail-open). | Yes → ARC@membership_status | **Live** (VslaTab open) |
| M3 | GET | `membership-status` | screens/user/user_account_dashboard_screen.dart:98 | none | direct `code==1`; `data` assigned to `Map<String,dynamic>? membershipStatus` | data must be a Map | A List or other type throws (inside try, caught). Consumers (`_buildMembershipStatusCard`: `bool x = data['has_valid_membership'] ?? false` would crash on int 1; `String? = data['membership_type']`) are **never called**. | Yes → ARC@membership_status | **Live** (fetch); render dead |
| M4 | GET | `api/membership-benefits` | models/MembershipPayment.dart:415 (from MembershipPaymentScreen:44) | — | `data.benefits` (List<String>) | `RespondModel resp = await Utils.http_get(...)` | **Always fails client-side:** a Map assigned to `RespondModel` throws a TypeError (caught, returns null). The path also hits catch-all `api/api/{model}` → index("membership-benefits"), which returns "Model not found". The screen shows a fallback benefits list. | catch-all `api/{model}` → ARC@index (wrong); correct route `membership-benefits` → ARC@membership_benefits exists | **Live-cond** (only when M1/M2 redirect) |
| M5 | GET | `membership-check` | screens/membership/MembershipPaymentScreen.dart:70 | none (User-Id header) | RespondModel; data.can_access_app, data.has_valid_membership, data.is_admin, data.requires_payment | true/1 | **The only way out of the lockout:** `can_access_app` truthy → `offAllNamed('/OnBoardingScreen')` | Yes → ARC@membership_check | **Live-cond** |
| M6 | GET | `membership-status` | models/MembershipPayment.dart:398 (`getMembershipStatus`) | user_id | data | `RespondModel resp = await http_get` (TypeError) | Always fails | Yes → ARC@membership_status | Dead (no callers) |
| M7 | GET | `api/membership-payments` | models/MembershipPayment.dart:319 (`getItems`) | params | data List | TypeError (as M4) | catch-all | catch-all `api/{model}` → ARC@index | Dead |
| M8 | POST | `api/membership-payment` | models/MembershipPayment.dart:369 (`createPayment`) | user_id, amount, payment_method, membership_type, payment_phone_number?, payment_account_number?, notes? | code, data | TypeError | Hits catch-all POST `api/api/{model}` → ARC@update ("MembershipPayment" class exists, so it could **create a row through the generic updater** if the TypeError were fixed) | catch-all `api/{model}` → ARC@update (wrong); correct `membership-payment` exists | Dead (no callers) |
| M9 | POST | `api/membership-payment/confirm` | models/MembershipPayment.dart:446 | payment_reference?, payment_id? | code | TypeError | Multi-segment under `api/api/…` = 404 | **NO** | Dead |
| M10 | GET | `membership-status` | screens/membership/MembershipPaymentListScreen.dart:41 | — | RespondModel; data.has_valid_membership, is_membership_paid, membership_type, membership_expiry_date | `bool = data[...] ?? false` (int 1 crashes); `String? = membership_type` | — | Yes → ARC@membership_status | Dead (only from a dead method / MyAccountTab) |
| M11 | GET | `membership-payments` | screens/membership/MembershipPaymentListScreen.dart:49 | — | RespondModel; data List → MembershipPayment.fromJson (to_str) | `data is List` | — | Yes → ARC@membership_payments_list | Dead |

### Generic legacy models (pattern: `RespondModel(await Utils.http_get(endPoint,{}))` → `code==1` → `data` runtimeType contains 'List' → wipe local table → batch insert `fromJson` with to_str/int_parse; non-list or error = silently empty)

| # | Method | Path (resolved) | Call site | Request keys | Response keys read | Fragility | Backend route | L/D |
|---|---|---|---|---|---|---|---|---|
| G1 | GET | `associations` | models/Association.dart:115 | — | data List | — | **NO (missing)** (ARC@associations exists but no route) | Dead |
| G2 | GET | `counselling-centres` | models/CounsellingCentre.dart:115 | — | data List | — | **NO** | Dead |
| G3 | GET | `events` | models/Event.dart:99 | — | data List | — | **NO** | Dead |
| G4 | GET | `events` | models/EventModel.dart:105 (EventsScreen) | — | data List | — | **NO** | Dead (EventsScreen only from the uncalled `vsla_tab._buildQuickActions`) |
| G5 | GET | `institutions` | models/Institution.dart:115 | — | data List | — | **NO** | Dead |
| G6 | GET | `jobs` | models/Job.dart:114 | — | data List | — | **NO** (ARC@jobs has no route) | Dead |
| G7 | GET | `my-subjects` | models/MySubjects.dart:94 | — | data List | — | **NO** | Dead |
| G8 | GET | `news-posts` | models/NewsPost.dart:89 | — | data List | — | **NO** | Dead |
| G9 | GET | `transactions` | models/OldTransaction.dart:84 (`Transaction` class) | — | data List | Route exists but is the new account `TransactionController` (different schema / possibly paginated). If ever reached, it would wipe and replace the legacy local table with mismatched rows. | Yes → TransactionController@index (semantic mismatch) | Dead (SectionSuspect, StudentScreen) |
| G10 | GET | `people` | models/Person.dart:141 | — | data List | — | **NO** (ARC@people has no route) | Dead |
| G11 | GET | `service-providers` | models/ServiceProvider.dart:106 | — | data List | — | **NO** | Dead |
| G12 | GET | `my-sessions` | models/SessionOnline.dart:109 | — | data List | — | **NO** | Dead |
| G13 | GET | `my-students` | models/UserModel.dart:288 | — | data List → UserModel | — | **NO** | Dead |
| G14 | GET | `groups` / `api/Disability` / `my-classes` (variable `endPoint`) | models/DynamicTable.dart:32 (GroupModel, Disability, MyClasses) | `is_not_private=1` | data List | `api/Disability` hits catch-all → ARC@index(model Disability) | groups **NO**; `api/Disability` catch-all → ARC@index; my-classes **NO** | Dead (PWDFormCreateScreen, ClassesScreen) |
| G15 | POST | `session-create` | models/SessionLocal.dart:92 (`uploadPending` ← `Utils.boot_system()` ← main.dart:22) | id, due_date, type, title, present, academic_class_id, subject_id, service_id | code | Runs at every app start but only for local `SessionLocal` rows with `closed=='yes'` (legacy data). Harmless 404 today. | **NO (missing)** | **Live** (startup, normally inert) |
| G16 | POST | `people` | screens/forms/PWDFormCreateScreen.dart:85 | person form + file | code, message | — | **NO** | Dead |
| G17 | POST | `update-bio/{id}` | screens/sessions/SessionCreateNewScreen.dart:122 | {} | code | The statement sits after `return;` (unreachable) | **NO** | Dead |
| G18 | POST | `update-bio/{id}` | screens/students/StudentEditBioScreen.dart:64 | first_name, last_name, `'given_name\t'` (key contains a TAB), sex, nationality, home_address | code, message | — | **NO** | Dead |
| G19 | POST | `update-guardian/{id}` | screens/students/StudentEditGuardianScreen.dart:66 | phone_number_1, phone_number_2, email | code, message | — | **NO** | Dead |

### Media upload

| # | Method | Path | Call site | Request keys | Response keys read | Fragility | Backend route | L/D |
|---|---|---|---|---|---|---|---|---|
| U-M1 | POST | `post-media-upload` | models/ImageModelLocal.dart:83 (`ImageModel.uploadSelf`, from ProductCreateScreenSimple:195) | multipart `file`, parent_id ('0' default), note, product_id, parent_endpoint, parent_local_id, type='Product' + injected `user` | `code`, `message` | Backend requires `user` (injected), `parent_local_id` ≥ 6 chars and a file in `$_FILES`. On success it returns `{code:1,message:'Success'}` with no data (the success() argument order drops the "N files uploaded" text). Product images are linked later in product_create by `parent_local_id == local_id`. | Yes → ARC@upload_media | **Live** (MyShop add product) |
| U-M2 | POST | `post-media-upload` | models/LocalImageToUploadModel.dart:30 | file, name, path, parent_id_online, **parent_id_local**, parent_type | code, data → UserModel when parent_type=='user-photo' | **Key mismatch:** backend needs `parent_local_id`, so it returns "Local parent ID is missing." | Yes → ARC@upload_media (wrong keys) | Dead (StudentEditPhotoScreen) |
| U-M3 | GET | `''` (empty) | models/ImageModelLocal.dart:194 | — | — | Unreachable: statement follows `return [];` | n/a (would hit `api/`) | Dead |

---

### Broken / missing routes

**Live code (user-visible or startup):**
1. `GET users/{id}`: ProductScreen.dart:100 "Contact seller". No route (only `users/me`, `users/my-roles`), so the feature is broken today.
2. `GET api/membership-benefits`: MembershipPayment.dart:415 (Live-cond). It hits catch-all `api/api/{model}` (wrong controller) and also always throws client-side (`RespondModel resp = await http_get`). The correct route `membership-benefits` exists.
3. `POST orders-create` (CheckoutScreen.dart:93): route OK, but **`pay_on_delivery` is sent inside `delivery` JSON while the backend reads it top-level**, so every live COD order is recorded as `PENDING_PAYMENT`/`manual`.
4. `POST session-create`: SessionLocal.dart:92 at startup. No route (inert unless legacy local rows exist).
5. MembershipPaymentScreen `_proceedToPayment` has no endpoint at all ("coming soon"). If M1/M2 ever redirect, the user cannot pay.

**Dead code:**
- Double-prefixed (`${AppConfig.API_BASE_URL}/…` into Utils.http_*): AccountController `user/update-profile`, `addresses/add|update|delete|set-default`; WishlistItem `wishlist_get|add|remove|check|clear` (`wishlist_clear` is also missing); Review.dart (all 10); ReviewCard `api/reviews/report`; PesapalService (all 6; `pesapal/verify` and `pesapal/payment-methods` are also missing).
- ApiService (`baseUrl` + `'api/…'` gives `/apiapi/…`): ModernCartController ×5, ProductService ×7. None of these backend routes exist even when correctly prefixed.
- Missing route: `delivery_addresses` (underscore), `products-create`, `orders/{id}/update-payment-status`, `ping`, `associations`, `counselling-centres`, `events` ×2, `institutions`, `jobs`, `my-subjects`, `news-posts`, `people` (GET + POST), `service-providers`, `my-sessions`, `my-students`, `groups`, `my-classes`, `update-bio/{id}` ×2, `update-guardian/{id}`, `api/membership-payment/confirm`.
- Route exists, controller method missing (500): `chat-heads`, `chat-messages`, `chat-send`, `chat-start`, `chat-mark-as-read`.
- Wrong controller via catch-all `api/{model}`: `api/membership-payments` (index), `api/membership-payment` (update, would write via the generic updater), `api/Disability`.
- Contract mismatch: `pesapal/initialize` from UniversalPaymentDetailsScreen sends `payment_id` (backend requires `order_id`); LocalImageToUploadModel sends `parent_id_local` (backend requires `parent_local_id`); Review update via POST without `_method` (PUT only); review write routes need `auth:sanctum`.
- Semantic mismatch: OldTransaction `transactions` maps to the new TransactionController.
- Client-side always-fails: shop_service `createProduct`/`updateProduct` (`json.decode(Map)`); MembershipPayment `getItems`/`createPayment`/`getMembershipStatus`/`confirmPayment` and all Review.dart RespondModel calls (Map assigned to `RespondModel`).

### Top fragility risks (ranked; Live first)

1. **Membership gate can hard-lock the whole app.** main_screen.dart:61-82 and vsla_tab.dart:141-160. Any `code:1` response where `data.has_valid_membership` and `data.is_admin` are not exactly `true`/`1` sends non-admins to `MembershipPaymentScreen(canGoBack:false)`. That screen's back button is disabled (WillPopScope), payment is stubbed (MembershipPaymentScreen.dart:135-145), and benefits fail (M4). The only exit is `membership-check` returning `can_access_app` truthy (MembershipPaymentScreen.dart:70-109). Today it works only because `User::hasValidMembership()` returns `true` unconditionally (app/Models/User.php:1176-1180). **Do not** change that method, the key names, or their boolean type (e.g. to `"Yes"`, `"1"`), and keep membership-status resolvable from the `User-Id` header (the route has no auth middleware; `$request->user()` uses the web guard and is null).
2. **orders-create `pay_on_delivery` mismatch.** CheckoutScreen.dart:71-96 vs ApiResurceController.php:1622 (`$r->pay_on_delivery`). Live COD orders are stored as `PENDING_PAYMENT`. Also `$item->color/size` and `$delivery->customer_phone_number_1` are dereferenced without isset (ApiResurceController.php:1586-1666), so the app must keep sending those keys or the request becomes a 500.
3. **products-delete "not found" heuristic.** shop_service.dart:163-181 treats any error message containing "not found" as success and deletes locally. If the backend adds an auth/ownership check that returns "User not found." (ARC's usual wording), MyShop silently drops products locally while they remain on the server. Note the backend currently has no ownership check on `products-delete`/`products-update` (security).
4. **`products-1` / `categories` / `vendors` / `delivery-addresses` / `orders` must keep returning a bare List in `data`.** Product.dart:392-425, MyProduct.dart:157-205, ProductCategory.dart:111-130, VendorModel.dart:172, DeliveryAddress.dart:75, OrderOnline.dart:241-275. Paginating or wrapping makes Marketplace, My Shop, the checkout address picker and orders silently empty, and the code wipes the local cache first. Product field formats are load-bearing: `rates` must stay a JSON-encoded string, `keywords` a JSON price-tier string, `feature_photo` with an `images/` prefix. `summary` is already broken: backend casts it to json and the app expects a JSON string (Product.dart:244-256), so attributes are always empty. MyShop shows all products because `products-1` is unscoped; scoping it per user would change Marketplace too.
5. **Contact Seller broken.** ProductScreen.dart:100-115 calls the non-existent `GET users/{id}` and reads `data.phone`/`data.phone_number_1`. Either add the route (returning `{code:1,data:{phone_number_1,…}}`) or fix the app.
6. **Product create/image upload contract.** ProductCreateScreenSimple.dart:181-214 and ImageModelLocal.dart:65-83. `post-media-upload` needs `user` (auto-injected), `parent_local_id` ≥ 6 chars and `file`. `product-create` links images by `local_id` and uses `$r->user` as owner. Renaming these fields breaks live product listing.
7. **`vendors` leaks credentials.** ARC@vendors returns raw `Administrator` rows including `password` and `remember_token` (VendorModel.dart:112,118 reads them). Hiding them is SAFE for the app (to_str default '') and recommended.
8. **Universal Payments (dead today, but latent).** `items_processed` is cast to boolean but parsed with `int_parse`, so it is always false (UniversalPayment.dart:372; UniversalPaymentDetailsScreen.dart:68-72). "Pay Now" fallback sends `payment_id` to `pesapal/initialize`, which requires `order_id` (UniversalPaymentDetailsScreen.dart:156). The else-branches do `resp['message']` on a possible String (UniversalPayment.dart:604, 639). Reaching this flow requires the uncalled `vsla_tab._buildQuickActions/_buildFinancialSummary`.
9. **Chat endpoints 500.** The routes in routes/api.php:87-91 point at ARC methods that do not exist. They are dead in the app, but any route:cache/static-analysis tooling will flag them. Remove or implement them.
10. **Direct `response['code']==1` on a POST result** (crashes when Utils.http_post returns its String error form). Live occurrences: shop_service.dart:146 (guarded by try/rethrow). Dead occurrences: AccountController (all), WishlistItem, PesapalService, OrderPaymentScreen, ModernCartController:557, ReviewCard:391.

---

## 6. Projects/Investments/Finance, Dashboards, Advisory, Market, Weather, Medical

Scope: mobile `lib/` call sites for projects/investments/disbursements/account-transactions/withdraw-requests/dashboards, advisory (Utils and Dio clients), market prices, backend weather, medical service requests, and the external services. Every path is under `/api/`. Route ownership comes from `routes/api.php`, registration order checked. Response shapes were cross-checked against controller source and against the prod-copy GET snapshot (`scratchpad/baseline/results.json`).

### How reachability was decided

I built the transitive import graph from `lib/main.dart`, then checked each screen to see whether the navigation call is inside a widget/method that actually runs.

- **Live**: reachable through a widget that is really built.
- **Dead**: no live caller.

Key navigation facts:
- `home_tab.dart:916` opens `VslaTab` (Live).
  - `VslaTab._refreshDashboardFromApi()` (`vsla_tab.dart:357`) runs on every open, so `dashboard/main` is Live.
  - `vsla_tab.dart` defines `_buildQuickActions()` (:604), `_buildFinancialSummary()` (:757) and `_buildVslaOverviewCards()` (:987), but **never calls them**. As a result:
    - These links are unreachable from VslaTab: `ProjectsListScreen`, `InvestmentsTab`, `MyTransactionsScreen` and `WithdrawRequestListScreen`.
    - The `dashboard/main` payload is fetched and cached, but only `message` is ever shown (as a toast).
- `InvestmentsTab` is only opened from that dead method. So the whole Projects/Investments UI (`ProjectsListScreen`, `ProjectDetailsScreen`, `InitializePaymentScreen`, `my_investments_screen`, `my_shares_screen`, `my_transactions_screen`) is **Dead**.
  - `main.dart:69-70` registers `/my-shares` and `/my-transactions`, but they are only pushed from dead screens.
- `MyAccountTab` is never instantiated (Dead). `screens/account/AccountDashboardScreen.dart` is never referenced (Dead), and with it `MedicalServiceRequestListScreen`, `projects/MyInvestmentsScreen` and `controllers/AccountController`.
- `UserAccountDashboardScreen` is Live, opened from `home_tab.dart:840` ("My Account Balance") and `more_tab.dart:83`. From it:
  - `AccountTransactionListScreen` (:168), `WithdrawRequestFormScreen` (:174) and `WithdrawRequestListScreen` (:178) are Live.
- `MoreTab` (avatar tap, `home_tab.dart:530`) opens Advisory, `AllMarketPricesScreen` and `WeatherScreen` (openweather). When `_isAdmin()`, it also opens `UsersListScreen`, which leads to `UserAccountDetailsScreen` and `AccountTransactionFormScreen`. All of these are Live.
- `OfflineSyncScreen` (`home_tab.dart:611`) is Live. It refreshes market caches and advisory caches, and syncs advisory queues.
- `vsla_sync_manager.dart` runs at boot (Live):
  - `AccountTransactionCacheService.refreshForLoggedInUser` (:73)
  - `AccountTransactionOfflineService.syncQueue` (:125)
- No importers: `disbursement_api.dart`, `investment_transaction_api.dart` (only `screens/admin/*` that nothing opens), `weather_service.dart` and `MedicalServiceRequestService` screens. All Dead.

Notation used in the tables:
- **RM** means the caller wraps the response in `RespondModel`.
- **`code==1` direct** means the caller does a raw `response['code'] == 1` comparison. That fails if the backend sends `"1"` or `true`.
- **String-crash**: `Utils.http_post`/`http_put`/`http_delete` return a jsonEncoded String on a non-JSON error, and `String['code']` then throws. The tables say whether that throw is caught.

---

### 1. Projects (ProjectService.dart): all Dead

The backend group is `Route::prefix('projects')->middleware(EnsureTokenIsValid)` (api.php:940-957). Every response uses the envelope `{success, data}` with **no `code`**, and the app checks `data['success'] == true`.

| # | Method | Path | Request keys | Response keys read | Type assumptions | Fragility | Backend route | Live? |
|---|---|---|---|---|---|---|---|---|
| P1 | GET | `projects` (ProjectService.dart:21) | query `status`?:str, `search`?:str | `success`, `message`, `data[]` → `Project.fromJson` | `for item in data['data']` (List assumed); `Project.fromJson`: `id: json['id'] ?? 0`, `total_shares`/`shares_sold`/`group_id`/`maximum_loan_multiple` raw-assigned (int expected), money fields via `double.tryParse` | int fields get a TypeError if sent as string; try/catch rethrows as Exception | Yes, ProjectController@index | Dead (ProjectsListScreen:31, InitializePaymentScreen:63) |
| P2 | GET | `projects/{id}` (:42) | none | `success`, `data` (Map) → Project | same as P1 | same | Yes, ProjectController@show | Dead (InitializePaymentScreen:91) |
| P3 | GET | `projects/{id}/details` (:58) | none | `success`, `data` returned raw (Map). Screen reads `data.project`, `data.financial_summary`, … | `return data['data']` typed `Map<String,dynamic>` | `data` as a List gives a TypeError, which is caught | Yes, ProjectController@getDetails | Dead (ProjectDetailsScreen:42) |
| P4 | POST | `projects` (:82) | all values `.toString()` (multipart) | `success`, `data` → Project | n/a | String-crash caught by try | Yes, ProjectController@store | Dead (no caller) |
| P5 | PUT | `projects/{id}` (:99) | JSON body (caller map) | `success`, `data` → Project | n/a | caught | Yes, ProjectController@update | Dead (no caller) |
| P6 | DELETE | `projects/{id}` (:115) | none | `success` | n/a | caught | Yes, ProjectController@destroy | Dead (no caller) |
| P7 | GET | `projects/shares/my-shares` (:128) | none | `success`, `data.shares[]` → `ProjectShare.fromJson`, `data.summary`, `data.projects_invested` | `for item in data['data']['shares']` | missing `shares` gives a null-iteration crash, which is caught | Yes, ProjectShareController@getUserShares (two segments, so `projects/{id}` does not capture it) | Dead (projects/MyInvestmentsScreen:31) |
| P8 | POST | `projects/shares/initiate-purchase` (:157) | `project_id`:str, `number_of_shares`:str | `success`, `data` (Map) returned raw | n/a | caught | Yes, ProjectShareController@initiatePurchase | Dead (no caller) |
| P9 | GET | `projects/transactions` (:193) | query `project_id`?, `type`?, `source`? | `success`, `data[]` → `ProjectTransaction.fromJson` | List assumed | **SHADOWED**: GET `projects/{id}` (api.php:944) is registered before `projects/transactions` (:949). The request hits ProjectController@show('transactions') and the snapshot gets 404 `{success:false,message:"Project not found"}` | **SHADOWED** (intended ProjectTransactionController@index) | Dead (no caller) |
| P10 | POST | `projects/transactions` (:220) | caller map, all `.toString()` | `success`, `data` → ProjectTransaction | n/a | caught | Yes, ProjectTransactionController@store (no POST `{id}` to shadow it) | Dead |
| P11 | DELETE | `projects/transactions/{id}` (:236) | none | `success` | n/a | caught | Yes, ProjectTransactionController@destroy | Dead |

### 2. Investments: investment_api.dart, investments_api.dart (all Dead)

There is **no auth middleware**. InvestmentController takes the user from `$request->user_id ?? Auth::id()`. The envelope is `{success, data}` with **no `code`**. On a missing user_id the backend returns HTTP 400 `{success:false}`.

| # | Method | Path | Request | Response keys read | Type assumptions | Fragility | Backend route | Live? |
|---|---|---|---|---|---|---|---|---|
| I1 | GET | `investments/dashboard?user_id=$userId` (investment_api.dart:9) | `user_id` in the path query | `success`, `data` → `InvestmentDashboard.fromJson` (`summary.total_invested`, `current_value`, `realized_profits`, `projected_profits`, `total_returns`, `total_profit_loss`, `roi_percentage`, `portfolio_breakdown[]`: `amount_invested`, `current_value`, `percentage_of_portfolio`, `profit_share`, …) | **`double.parse(json['x'].toString())`** on about 15 fields; `json['portfolio_breakdown'] as List` | any null field gives `"null"` and a FormatException; caught, returns null | Yes, InvestmentController@getDashboard | Dead (my_investments_screen:68) |
| I2 | GET | `investments/shares?user_id=` (:29) | as above | `success`, `data` **as List** → `ProjectShareModel.fromJson` (`id`, `project_id` raw ints; `purchase_price`, `current_price`, `amount_invested`, `current_value`, `profit_loss`, `profit_share`, `total_return` via `double.parse`) | `as List`, `double.parse` | caught, returns [] | Yes, InvestmentController@getMyShares | Dead (my_shares_screen:68) |
| I3 | GET | `investments/shares/{id}` (:50) | none | `success`, `data` raw Map | n/a | caught | Yes, InvestmentController@getShareDetails | Dead (no caller) |
| I4 | GET | `investments/transactions?user_id=` (:70) | as above | `success`, `data.transactions` **as List** → `InvestmentTransaction.fromJson`, `data.summary` → `TransactionSummary.fromJson` | `as List` | caught. **Caller bug**: my_transactions_screen.dart:40 hardcodes `userId: 1` | Yes, InvestmentController@getMyTransactions | Dead |
| I5 | GET | `dashboard/investments-overview` (investments_api.dart:7) | none (user from `auth('api')` or `$_SERVER['HTTP_USER_ID']`) | `code==1` direct; `data.total_investment`, `formatted_total_investment`, `total_returns`, `formatted_total_returns`, `current_portfolio_value`, `formatted_portfolio_value`, `overall_roi`, `formatted_roi`, `user_projects`, `recent_purchases`, `available_projects`, `statistics` (all `?? default`) | none typed | safe (GET never returns a String) | Yes, DashboardController@getInvestmentsOverview (keys match :453-466) | Dead (investments_tab:41) |

### 3. Investment transactions (admin): investment_transaction_api.dart (all Dead, no importer reachable)

There is no auth middleware. The envelope mixes formats: index/show/projects return `{success, data, summary?, pagination?}` and store/update/destroy return `Utils::success` `{code:1, …}`. The app accepts `code==1 || success==true` on writes.

| # | Method | Path | Request | Response keys read | Type assumptions | Fragility | Backend route | Live? |
|---|---|---|---|---|---|---|---|---|
| IT1 | GET | `investment-transactions` (:33) | `sort_by`, `sort_order`, `page`, `per_page`, `project_id`?, `type`?, `source`?, `start_date`?, `end_date`?, `search`? (all str) | `success`, **top-level** `data` as List → `InvestmentTransactionModel`, **top-level** `summary` → `TransactionSummaryModel` (`total_count` raw int; `total_income`/`total_expense`/`net_amount` via `double.parse(( ?? 0).toString())`), top-level `pagination` | `as List` | `summary` moved inside `data` would make fromJson(null) crash, which is caught | Yes, InvestmentTransactionController@index | Dead |
| IT2 | GET | `investment-transactions/{id}` (:58) | none | `success`, `data` → model (`id`/`project_id`/`amount` tryParse; `can_edit`/`can_delete`/`is_automated` raw bool) | bool raw | int 0/1 instead of bool gives a TypeError, caught | Yes, @show | Dead |
| IT3 | GET | `investment-transactions/projects` (:77) | none | `success`, `data` as List → `ProjectDropdownModel` (`id`, `title`, `status`, `status_label`, `image` raw) | raw assignments | caught | Yes, @getProjects (declared before `/{id}`) | Dead |
| IT4 | POST | `investment-transactions` (:104) | `project_id`, `amount`, `type`, `description`, `transaction_date` (str) | `code`/`success`, `message`, `data` → model | n/a | String-crash caught | Yes, @store | Dead |
| IT5 | POST | `investment-transactions/{id}` + `_method=PUT` (:153) | `_method`, `amount`?, `type`?, `description`?, `transaction_date`? | as above | n/a | works via Laravel method spoofing | Yes (spoofed to PUT, @update) | Dead |
| IT6 | POST | `investment-transactions/{id}` + `_method=DELETE` (:181) | `_method` | `code`/`success`, `message` | n/a | spoofed | Yes (spoofed to DELETE, @destroy) | Dead |

### 4. Disbursements (admin): disbursement_api.dart (all Dead, no reachable importer)

There is no auth middleware. Envelope is `Utils::success`/`error` `{code, message, data}`.

| # | Method | Path | Request | Response keys read | Type assumptions | Fragility | Backend route | Live? |
|---|---|---|---|---|---|---|---|---|
| D1 | GET | `disbursements` (:31) | `sort_by`, `sort_order`, `page`, `per_page`, `project_id`?, `start_date`?, `end_date`?, `search`? | `code==1`; `data.disbursements` (`as List?`) → `DisbursementModel` (all tryParse/toString, safe); `data.summary` (`total_disbursed` tryParse, `total_disbursements` raw int); `data.pagination` | `as List?` | safe | Yes, DisbursementController@index | Dead |
| D2 | GET | `disbursements/{id}` (:72) | none | `data.disbursement` → model; `data.account_transactions` (`as List?`) → `InvestorDisbursementModel` | n/a | safe | Yes, @show | Dead |
| D3 | POST | `disbursements` (:125) | `project_id`:int, `amount`:double, `disbursement_date`:str, `description`? | `data.disbursement`, `data.account_transactions`, `data.total_investors` | n/a | String-crash caught | Yes, @store | Dead |
| D4 | POST | `disbursements/{id}` + `_method=PUT` (:188) | `_method`, `disbursement_date`?, `description`? | `data` → DisbursementModel (flat, not wrapped) | n/a | spoofed OK | Yes (spoofed to PUT, @update) | Dead |
| D5 | POST | `disbursements/{id}` + `_method=DELETE` (:220) | `_method` | `code`, `message` | n/a | spoofed OK | Yes (spoofed to DELETE, @destroy) | Dead |
| D6 | GET | `disbursements/projects` (:248) | none | `data` as List → `ProjectDropdownModel` | `as List` | caught | Yes, @getProjects (declared before `/{id}`) | Dead |

### 5. Account transactions and user accounts: account_transaction_api.dart (+ offline/cache services)

There is **no auth middleware** on `account-transactions/*` or `user-accounts/*`. `user-accounts/dashboard` uses `Utils::get_user_id()`, which reads `$_SERVER['HTTP_USER_ID']`, then `?user_id`, then the POST `user_id` field. The envelope is `Utils::success`/`error`. Every API method is wrapped in try/catch and returns a map.

| # | Method | Path | Request keys | Response keys read | Type assumptions | Fragility | Backend route | Live? |
|---|---|---|---|---|---|---|---|---|
| A1 | GET | `account-transactions` (account_transaction_api.dart:38) | `sort_by`:str (default `transaction_date`), `sort_order`:str, `page`:str, `per_page`:str, `user_id`?:int, `source`?, `type`? (`credit`/`debit`), `start_date`?, `end_date`?, `search`? | `code==1` direct; `data.transactions` (`as List?`) → `AccountTransactionModel.fromJson` (`id`, `user_id`, `created_by_id` int.tryParse; `amount` double.tryParse; `user_name`, `formatted_amount`, `transaction_date`, `formatted_date`, `source`, `source_label`, `type`, `created_by`, `created_at` raw `?? default` into String; `description` String?; `related_disbursement_id` tryParse; `can_edit`, `can_delete` raw `?? false` into bool); `data.summary` → `balance`, `total_credit`, `total_debit` (tryParse); `data.pagination.current_page`, `last_page`, `total` (raw into int, read in account_transaction_list_screen.dart:177-180) | String fields assigned raw (a number gives a TypeError); bools raw; pagination raw int | Snapshot types: `amount` string, `total_credit` string, `balance` int. tryParse absorbs both. **Screen crash**: account_transaction_list_screen.dart:170/172 does `transactions = result['transactions']` whenever `result != null`, including the failure map (no `transactions` key). That assigns null to `List<AccountTransactionModel>` and throws inside setState, so the spinner never stops. Backend `sort_by` is passed straight to `orderBy`. **Privacy**: no server-side scoping. A non-admin who taps "Clear filters" (`_clearFilters`, :205) sends no `user_id` and receives every user's transactions. `Get.arguments` from user_account_details_screen.dart:79 is ignored | Yes, AccountTransactionController@index | **Live** (AccountTransactionListScreen:150; AccountTransactionCacheService.refreshForUser:172 at boot via vsla_sync_manager:73, with `per_page=50`) |
| A2 | GET | `account-transactions/{id}` (:80) | none | `code==1`, `data` → AccountTransactionModel | as A1 | safe | Yes, @show | Dead (no caller) |
| A3 | POST | `account-transactions` (:142) | multipart `user_id`:int, `amount`:double, `transaction_date`:`yyyy-MM-dd`, `source`:`deposit`/`withdrawal`, `created_by_id`:int (logged-in user), `description`? | `code==1`, `message`, `data` → AccountTransactionModel | n/a | **ALWAYS FAILS**: AccountTransactionController.php:116 calls `Utils::get_user_from_request($request)`, which does not exist (it has never existed in git history; `Utils extends Model`). This raises `BadMethodCallException` outside the try, so the response is HTTP 500 `{"message":"Server Error"}`. I reproduced it with tinker. The app sees `code` null and shows the toast "Server Error". Offline-queued transactions (AccountTransactionOfflineService._submitTransaction:270) never sync. Even after a fix, the endpoint requires `user_type === 'Admin'` | Route exists (@store) but is **BROKEN at runtime** | **Live** (AccountTransactionFormScreen:188, admin-only path via UsersList → UserAccountDetails → form; offline syncQueue at boot via vsla_sync_manager:125) |
| A4 | POST | `account-transactions/{id}` + `_method=DELETE` (:176) | `_method=DELETE` | `code`, `message` | n/a | spoofing works | Yes (spoofed to DELETE, @destroy) | Dead (no caller) |
| A5 | GET | `user-accounts/dashboard` (:204) | none (User-Id header) | `code==1`, `data` → `UserAccountDashboardModel`: `user.id` (raw int), `user.name`, `user.email` (`?? 'N/A'`), `user.phone_number`; `balance` tryParse; `formatted_balance`; `recent_transactions` (`as List?`); `statistics.total_disbursements`, `total_deposits`, `total_withdrawals` (tryParse), `formatted_*`, `total_transactions` (raw int) | raw int `id`/`total_transactions` | depends only on the User-Id header (`$_SERVER['HTTP_USER_ID']`); the snapshot without the header got code 0 | Yes, UserAccountController@getUserAccountDashboard | Dead (no caller of `getUserDashboard`) |
| A5b | GET | `user-accounts/dashboard` (controllers/AccountController.dart:134) | none | `code==1`; `data.balance`, `formatted_balance`, `recent_transactions`, `statistics` (all `??`) | none | safe | Yes, as A5 | Dead (AccountController is only bound for dead screens) |
| A6 | GET | `user-accounts/user-dashboard/{userId}` (:237) | none | as A5, plus recent_transactions use the full AccountTransactionModel keys (`user_id`, `user_name`, `related_disbursement_id`, `created_by`, `created_by_id`, `created_at`, `can_edit`, `can_delete`) | as A1/A5 | snapshot: `user.email` null (handled), `statistics.total_deposits` string (tryParse), `total_disbursements` int. Screen checks `result['code'] == 1` (the wrapper's own map, safe) | Yes, UserAccountController@getUserDashboard | **Live** (UserAccountDetailsScreen:56, admin) |
| A7 | GET | `user-accounts/users-list` (:280) | `sort_by`:`name`, `sort_order`:`asc`, `search`? | `code==1`; `data.users` (`as List?`) → `UserWithBalanceModel`: `id` (raw int), `name`, `email` (`?? 'N/A'`), `phone_number` (String?), `balance` tryParse, `formatted_balance`, `transaction_count` (raw int), `member_since`; `data.summary.total_users` (raw int), `total_balance` (tryParse), `formatted_total_balance` | raw int | Backend ignores `sort_by=name`. It runs 2 queries per user over **all** users, so latency/timeout risk grows with the user table. `$user->created_at->format()` on a null created_at fails the whole call | Yes, UserAccountController@getAllUsersWithBalances | **Live** (UsersListScreen:49, admin via MoreTab) |

### 6. Withdraw requests: withdraw_request_api.dart

There is no auth middleware. The user comes from `Utils::get_user_id()`, so it **depends on the `User-Id` header** (JWT is ignored). The envelope is `Utils::success`/`error`, and the error `data` is always `""`.

| # | Method | Path | Request | Response keys read | Type assumptions | Fragility | Backend route | Live? |
|---|---|---|---|---|---|---|---|---|
| W1 | GET | `withdraw-requests/balance` (:8) | none | `code==1`; `data` → `WithdrawBalanceModel`: `current_balance`, `pending_withdrawals`, `available_balance`, `total_withdrawn` (tryParse), `formatted_balance`, `formatted_pending`, `formatted_available` (raw `?? default` into String) | String raw | safe. Removing User-Id header support breaks it | Yes, WithdrawRequestController@getBalance (declared before `/{id}`) | **Live** (withdraw_request_form_screen:56) |
| W2 | GET | `withdraw-requests` (:39) | none | `code==1`; `data` is List (checked with `is List`) → `WithdrawRequestModel.fromJson`: `id`, `user_id` (**raw `?? 0` into int**), `amount`, `account_balance_before` (tryParse), `status`, `created_at`, `updated_at`, `formatted_amount`, `formatted_balance`, `status_label` (raw into String), `description`, `payment_method`, `payment_phone_number`, `admin_note`, `processed_at` (String?), `processed_by_id`, `account_transaction_id` (**raw into int?**), `can_be_processed` (raw into bool), `user`, `processed_by`, `account_transaction` (raw into `Map?`) | raw int/bool/Map | Model casts are `user_id` integer and `amount` decimal (a string, handled). `processed_at` is cast to datetime and serialises as an ISO string. A relation serialised as `[]` instead of null would throw a TypeError, which is caught | Yes, @index | **Live** (withdraw_request_list_screen:35) |
| W3 | GET | `withdraw-requests/{id}` (:79) | none | `data` → WithdrawRequestModel | as W2 | n/a | Yes, @show | Dead (no caller) |
| W4 | POST | `withdraw-requests` (:126) | multipart `amount`:double (e.g. `5000.0`), `payment_method`:`mobile_money`/`bank_transfer`, `payment_phone_number`:str (always sent, may be `""`), `description`? | `code==1`, `message`, `data` → WithdrawRequestModel | n/a | Validation: `amount` min:1000. `payment_phone_number` is `required_if:mobile_money\|string`, so an empty phone on bank_transfer becomes null and fails `string` ("must be a string"). String-crash caught | Yes, @store | **Live** (withdraw_request_form_screen:91) |
| W5 | DELETE | `withdraw-requests/{id}` (:158) | FormData user/User-Id/user_id | `code==1`, `message` | n/a | caught | Yes, @cancel | **Live** (withdraw_request_list_screen:75) |

### 7. Dashboards

| # | Method | Path | Request | Response keys read | Type assumptions | Fragility | Backend route | Live? |
|---|---|---|---|---|---|---|---|---|
| DB1 | GET | `dashboard/main` (dashboard_api.dart:7) | none (user from `auth('api')->user()` JWT, falling back to `$_SERVER['HTTP_USER_ID']`) | `code==1`; `data.user`, `data.financial_overview` (`formatted_account_balance`, `formatted_total_investments`, `total_savings`, read only by unbuilt widgets), `data.recent_medical_services` (`as List?`; per item `id`, `service_name`, `service_type`, `status`, `status_label`, `user_name`, `user_phone`, `cost`, `formatted_cost`, `description`, `created_at`, `formatted_date`), `data.quick_stats` (`active_loans`, `total_members`, `cycle_progress`, unused). vsla_tab.dart:365-367 assigns `result['financial_overview'] ?? {}` to `Map<String,dynamic>` and caches the full result | `as List?`; Map assign | `financial_overview`/`quick_stats` serialised as `[]` throw a TypeError, which is caught (toast only if nothing is cached). The endpoint touches the AccountTransaction, ProjectShare, InsuranceSubscriptionPayment, InsuranceSubscription and MedicalServiceRequest tables. A failure in any of them returns code 0. `getRecentMedicalServices` leaks the last 20 medical requests of **all** users (names/phones) | Yes, DashboardController@getMainDashboard | **Live** (vsla_tab:357, every open) |
| DB2 | GET | `dashboard/my-account-overview` (my_account_api.dart:8) | none | `code==1`; `data.user`, `account_balance`, `formatted_balance`, `account_transactions`, `investments`, `recent_transactions`, `quick_stats`, `activity_summary` (all `??`) | none | safe | Yes, DashboardController@getMyAccountOverview | Dead (MyAccountTab never built) |
| DB3 | GET | `membership-status` (user_account_dashboard_screen.dart:98) | none | `code==1` direct; `data` stored in `membershipStatus`, which is **never read** | none | safe (GET, inside try) | Yes, ApiResurceController@membership_status | **Live** |

### 8. Advisory

There are two transports:
- **Utils.http_*** sends the User-Id header and Bearer token.
- **Dio** (`AdvisoryApiService._dio`, `_uploadFormData`, `AdvisoryOfflineService._dio`) sends **Bearer only**, with no User-Id. Dio 5.9 builds the URL by plain concatenation, `baseUrl + path` (`options.dart:610`). So `'/advisory/x'` resolves to `https://fao-ffs-mis.org/api/advisory/x` (OK), but `'advisory/x'` resolves to `https://fao-ffs-mis.org/apiadvisory/x` (**BROKEN**).

On the backend:
- Public routes (no middleware): `advisory/categories`, `posts*`, `posts/{id}/like`, `questions`, `questions/{id}`.
- `EnsureTokenIsValid` group: `questions/my/list`, POST `questions`, `questions/{id}/answers`, `questions/{id}/like`, `answers/{id}/like`.
- For Bearer-only Dio calls, the middleware has no User-Id header, so it base64-decodes the JWT `sub` claim without checking signature or expiry and sets `$request->user`. Bearer-only calls therefore work as long as prefs `token` is non-empty; with an empty token the middleware returns 401.

The envelope is the ApiResponser trait: `{success, code:1, status:1, message, data}`. **Errors use HTTP 400/401/404**. The Dio helpers catch DioException and return `{'code':0,'message':'Request failed: DioException…'}`, which **discards the backend message** (e.g. validation "content min:20").

All advisory models use `Utils.int_parse`/`Utils.to_str`. Booleans are tested as `== true || == 1`, so a backend `"Yes"` would silently read as false. `tags` uses `List<String>.from` (non-string items would throw).

| # | Method | Path | Transport | Request | Response keys read | Fragility | Backend route | Live? |
|---|---|---|---|---|---|---|---|---|
| AD1 | GET | `advisory/categories` (advisory_api.dart:121) | Dio | none | `code==1`; `data` (`is List`, else []) → `AdvisoryCategory`: `id`, `name`, `description`, `image`, `icon`, `order`, `posts_count` | robust | Yes, AdvisoryController@getCategories | **Live** (ArticlesScreen) |
| AD2 | GET | `advisory/posts` (:186) | Dio | `page`:int, `per_page`:int, `category_id`?, `category_name`?, `language`?, `search`?, `featured`? (`yes`/`no`), `has_pdf`/`has_video`/`has_audio`? (`yes`) | `code==1`; `data` is Map, `data.posts[]` → `AdvisoryPost` (`id`, `title`, `content`, `image`, `category_id`, `category_name`, `author_name`, `published_at`, `view_count`, `likes_count`, `language`, `featured`, `tags`, `has_video`, `video_url`, `has_audio`, `audio_url`, `has_youtube_video`, `youtube_video_url`, `has_pdf`, `pdf_url`); `data.pagination` passed through (keys not read) | `data.posts` must stay nested under `data` | Yes, @getPosts | **Live** (ArticlesScreen) |
| AD3 | GET | `advisory/posts/featured` (:236) | Dio | `limit` | `data` List | n/a | Yes, @getFeaturedPosts | Dead |
| AD4 | GET | `advisory/posts/{id}` (:280) | Dio | none | `data` → AdvisoryPost | n/a | Yes, @getPost | **Live** (ArticleDetailScreen) |
| AD5 | POST | `advisory/posts/{id}/like` (:321) | Dio | none | `code==1`; `data.likes_count` (int_parse) | public, so user identity is unused | Yes, @likePost | **Live** |
| AD6 | GET | `advisory/questions` (:377) | Dio | `page`, `per_page`, `status`? (`open`/`answered`), `search`? | `data.questions[]` → `FarmerQuestion` (`id`, `title`, `content`, `author_name`, `author_location`, `status`, `view_count`, `likes_count`, `answers_count`, `has_image`, `image_url`, `has_audio`, `audio_url`, `created_at`); `data.pagination` | robust | Yes, FarmerQuestionController@getQuestions | **Live** (FarmerQuestionsScreen) |
| AD7 | GET | `advisory/questions/my/list` (:432) | Dio | `page`, `per_page` | `data.questions[]` | n/a | Yes, @getMyQuestions (middleware decodes sub) | Dead |
| AD8 | GET | `advisory/questions/{id}` (:468) | Dio | none | `data` → FarmerQuestion; `data.answers[]` → `FarmerQuestionAnswer` (`id`, `question_id`, `content`, `author_name`, `author_location`, `author_avatar`, `likes_count`, `is_accepted`, `has_image`/`image_url`, `has_audio`/`audio_url`, `has_video`/`video_url`, `has_youtube_video`/`youtube_video_url`, `has_pdf`/`pdf_url`, `created_at`) | Backend answer objects carry **no `question_id`**, so cached answers save with question_id 0 and the offline fallback (`where question_id = X`, :505) is always empty | Yes, @getQuestion | **Live** (QuestionDetailScreen) |
| AD9 | POST | `advisory/questions` (:546) | Dio `_uploadFormData`, Bearer only | multipart `title`, `content`, files `image`?, `audio`? | `code==1`; `data` → FarmerQuestion | 400 validation message lost | Yes, @postQuestion (middleware decodes sub) | Dead (`AdvisoryApiService.postQuestion` has no caller) |
| AD10 | POST | `advisory/questions/{id}/answers` (:621) | Dio upload, Bearer only | multipart `content` (min 20), files `image`?, `audio`?, `video`?, `pdf`?, field `youtube_video_url`? | `code==1`, `message`, `data` | validation 400 becomes "Upload failed: DioException…" toast | Yes, @postAnswer | **Live** (QuestionDetailScreen) |
| AD11 | POST | `advisory/questions/{id}/like` (:666) | Dio, Bearer only | none | `code==1`; `data.likes_count` | 401 if token empty | Yes, @likeQuestion | **Live** |
| AD12 | POST | `advisory/answers/{id}/like` (:711) | Dio | none | `data.likes_count` | n/a | Yes, @likeAnswer | Dead (`likeAnswer` has no caller) |
| AD13 | POST | `advisory/questions` (screens/advisory/PostQuestionScreen.dart:896) | **Utils.http_post** | multipart `title` (min 10), `content` (min 20), `image`? (MultipartFile jpeg/png ≤5MB), `audio`? (MultipartFile; ext whitelist mp3/wav/m4a/aac/ogg/oga/webm/3gp/flac/mp4/mpeg); plus injected `user`, `User-Id` | `code==1` **direct**; `message` | 4xx JSON bodies are returned as a Map, so the backend message is shown. A non-JSON error returns a String, and `String['code']` throws inside try (caught; toast shows the TypeError text). Note: backend `uploadMedia` audio whitelist omits `mp4`/`mpeg` even though validation allows them | Yes, @postQuestion | **Live** (online path) |
| AD14 | POST | `advisory/questions` (advisory_offline_service.dart:440, private Dio) | Dio, Bearer only, **path without leading `/`** | multipart `title`, `content`, `image`?, `audio`? | `code==1`; `data` → FarmerQuestion | **BROKEN URL**: `https://fao-ffs-mis.org/apiadvisory/questions` returns 404. Every offline-queued question fails, `retry_count` reaches the max of 3, and the item stays stuck | **NO**: the app-side URL never reaches `/api/advisory/questions` | **Live** (PostQuestionScreen:838 offline queue → OfflineSyncScreen syncQueue) |
| AD15 | POST | `advisory/posts/{id}/like`, `advisory/questions/{id}/like`, `advisory/answers/{id}/like` (advisory_offline_service.dart:287, `_likeEndpoint` :586) | Utils.http_post | `{}` plus injected user fields | `code==1` | String error throws inside try (caught) | Yes (@likePost / @likeQuestion / @likeAnswer) | **Live** for post/question (queued when offline); the answer variant is Dead |
| AD16 | GET | `advisory/categories` (advisory_offline_service.dart:472) | Utils | none | `code==1 && data is List` → AdvisoryCategory | robust | Yes | **Live** (OfflineSyncScreen) |
| AD17 | GET | `advisory/posts` (:482) | Utils | `per_page`:`50` | `data is Map && data.posts is List` | robust | Yes | **Live** |
| AD18 | GET | `advisory/questions` (:504) | Utils | `per_page`:`50` | `data.questions is List` | robust | Yes | **Live** |

### 9. Market prices

These routes are public. The envelope is ApiResponser `{success, code, status, message, data}`. The app checks `code == 1 || success == true`, then `response['data'] as List<dynamic>`. All fetches are in try/catch and return `[]` (or the cache) on any error.

| # | Method | Path | Request | Response keys read | Type assumptions | Fragility | Backend route | Live? |
|---|---|---|---|---|---|---|---|---|
| M1 | GET | `market-price-categories` (models/MarketPriceCategory.dart:65) | none | `data[]` → `id`, `order`, `products_count` (`int.tryParse(x.toString())`), `name`, `description`, `photo`, `icon`, `created_at`, `updated_at` (toString) | `as List<dynamic>` | Map-shaped `data` (missing `->values()`) gives a cast error and a silent [] | Yes, ApiResurceController@market_price_categories | **Live** (OfflineSyncScreen:287) |
| M2 | GET | `market-price-products` (models/MarketPriceProduct.dart:102) | `category_id`? | `data[]` → `id`, `category_id` (tryParse), `category_name`, `name`, `description`, `photo`, `unit` (toString, unit default `kg`), **`latest_price` → `Map<String,dynamic>.from(...)`** (snapshot keys `price`, `price_min`, `price_max`, `currency`, `date`, `market_name`, `district_name`), `created_at`, `updated_at` | `Map.from` | `latest_price` sent as `[]` instead of null makes `Map.from(List)` throw, and the **whole list is dropped** | Yes, @market_price_products | **Live** (AllMarketPricesScreen:45, OfflineSyncScreen:288) |
| M3 | GET | `market-prices` (models/MarketPrice.dart:159) | `product_id`?, `district_id`?, `sub_county_id`?, `start_date`?, `end_date`?, `limit`? (all str) | `data[]` → `id`, `product_id`, `category_id` (`int.tryParse(x.toString())`; **`.toString()` on null gives `"null"`, and tryParse then returns 0, so it is safe**), `district_id`/`sub_county_id` (nullable tryParse), `product_name`, `product_photo`, `category_name`, `district_name`, `sub_county_name`, `market_name`, `price` (tryParse), `price_min`, `price_max`, `currency`, `unit`, `quantity`, `date`, `source`, `notes`, `created_at` | `as List<dynamic>` | Without filters, the local table is wiped before insert | Yes, @market_prices | **Live** (AllMarketPricesScreen:48, MarketPricePriceListScreen via `getPricesForProduct`) |
| M4 | GET | `market-prices-latest` (models/MarketPrice.dart:207) | none | same keys as M3 | as M3 | Backend reads `$product->category->name`; a product with a missing category gives a 500 and an empty list | Yes, @market_prices_latest | **Live** (OfflineSyncScreen:291) |

### 10. Weather / AI / external

| # | Method | Path | Request | Response keys read | Fragility | Backend route | Live? |
|---|---|---|---|---|---|---|---|
| WX1 | GET | `weather/forecast` (services/weather_service.dart:57; Dio, absolute `${API_BASE_URL}/weather/forecast`, **no auth headers at all**) | `latitude`:double, `longitude`:double | requires `statusCode == 200`; `code==1`; `data.list[]` → `DailyForecast.fromApiResponse` (`dt`, `temp.day/min/max/night/eve/morn`, `weather[0].main/description/icon`, `sunrise`, `sunset`, `speed`, `deg`, `clouds`, `pop`, `rain`, `pressure`, `humidity`; uses `.toDouble()` on raw values, which throws on strings); `data.city.name` | A validation failure returns HTTP 400, which Dio throws; caught | Yes, Api\WeatherController@getForecast (public) | **Dead** (weather_service.dart has no importer) |
| EXT1 | GET | `https://maps.googleapis.com/maps/api/geocode/json` (weather_service.dart:210, 242) | external | n/a | n/a | external | Dead |
| EXT2 | GET | `https://api.openweathermap.org/data/2.5/weather`, `/forecast` (openweather_service.dart:24, 51, 77, 103) and **`http://`** `api.openweathermap.org/data/2.5/air_pollution` (:131, cleartext) | external | n/a | n/a | external | **Live** (WeatherScreen via home_tab:861 / more_tab) |
| EXT3 | POST | `https://manya-ai-aeh8d2gaepdpb8eq.eastus-01.azurewebsites.net/ask` (ai_chat_service.dart:16, 88) | external | n/a | n/a | external | **Live** (ManyaAIChatScreen via home_tab:883) |

### 11. Medical service requests: MedicalServiceRequestService.dart (all Dead)

There is no auth middleware. **The backend envelope is `{success, message, data}` with NO `code` key**: MedicalServiceRequestController has zero occurrences of `'code'`, and the snapshot confirms it. The app treats success as `responseData['code'] == 1` on every call, so **every call would report failure even on HTTP 200**. The module is non-functional as written.

| # | Method | Path | Request | Response keys read | Type assumptions | Backend route | Live? |
|---|---|---|---|---|---|---|---|
| MD1 | GET | `medical-service-requests` (:25) | `page`, `status`?, `urgency_level`?, `service_type`? | `code==1`; `data` (a Laravel paginator: `data.data[]`, `current_page`, …) | n/a | Yes, MedicalServiceRequestController@index | Dead |
| MD2 | GET | `medical-service-requests` (:73) | `user_id`, `page`, filters | as MD1 | n/a | Yes, @index | Dead |
| MD3 | POST | `medical-service-requests` (:131) | `user_id`, `service_type`, `urgency_level`, `symptoms_description`, `additional_notes`?, `preferred_hospital`?, `preferred_doctor`?, `preferred_date`?, `preferred_time`?, `contact_phone`?, `contact_email`?, `contact_address`? | `code==1`, `data`, `errors` | `MedicalServiceRequest.fromJson` uses **`as int` / `as String` hard casts** (`id`, `user_id`, `reference_number`, `service_type`, `service_type_label`, `urgency_level`, `urgency_label`, `status`, `status_label`, `symptoms_description`, `created_at`, `updated_at`) and `List<String>.from(attachments as List)` | Yes, @store | Dead |
| MD4 | GET | `medical-service-requests/{id}` (:159) | none | `code==1`, `data` | as MD3 | Yes, @show | Dead |
| MD5 | GET | `medical-service-requests/reference/{reference}` (:186) | none | `code==1`, `data` | as MD3 | Yes, @getByReference (declared before `/{id}`) | Dead |
| MD6 | POST | `medical-service-requests/{id}` + `_method=PUT` (:244) | `_method`, editable fields | `code==1`, `data`, `errors` | as MD3 | Yes (spoofed to PUT, @update) | Dead |
| MD7 | POST | `medical-service-requests/{id}/admin-update` (:309) | `_method=PUT`, `status`, `admin_feedback`?, `assigned_hospital`?, `assigned_doctor`?, `scheduled_date`?, `scheduled_time`?, `estimated_cost`?, `patient_payment`? | `code==1`, `data` | n/a | **NO (missing)**. The backend has `POST {id}/review` (@review), not `admin-update`, and the spoofed PUT on `{id}/admin-update` matches nothing | Dead |
| MD8 | POST | `medical-service-requests/{id}/cancel` (:344) | `cancellation_reason`? | `code==1`, `data` | n/a | Yes, @cancel | Dead |
| MD9 | GET | `medical-service-requests/stats` (:376) | none | `code==1`, `data` | n/a | Yes, @stats (declared before `/{id}`) | Dead |

---

### Broken / missing routes

**Live:**
1. **POST `account-transactions`** (A3). The route exists, but AccountTransactionController.php:116 calls the undefined `Utils::get_user_from_request()`, so every call returns HTTP 500. This breaks the admin "Add transaction" form (account_transaction_form_screen.dart:188) and the boot-time offline queue sync (account_transaction_offline_service.dart:270). Queued items never clear.
2. **POST `advisory/questions` from the offline sync** (AD14). advisory_offline_service.dart:440 uses Dio with baseUrl `…/api` and path `'advisory/questions'` (no leading slash). The URL becomes `https://fao-ffs-mis.org/apiadvisory/questions`, which 404s. Offline-posted questions never upload.

**Dead (no live caller today, but broken if revived):**
3. GET `projects/transactions` (P9) is **SHADOWED** by GET `projects/{id}` (api.php:944 before :949) and returns 404 "Project not found".
4. POST `medical-service-requests/{id}/admin-update` (MD7): no such route; the backend has `{id}/review`.
5. The whole medical module has an envelope mismatch: the app requires `code==1` and the backend never sends `code` (MD1-MD9).
6. GET `investments/transactions` from my_transactions_screen.dart:40: the route is fine, but `userId: 1` is hardcoded.

Everything else resolves to a route, including the `_method=PUT/DELETE` spoofed POSTs on disbursements, investment-transactions, account-transactions and medical (Laravel's method override applies to multipart POST).

### Top fragility risks (ranked)

1. **POST account-transactions is fatally broken on the backend.** `app/Http/Controllers/AccountTransactionController.php:116`, reached from `lib/screens/admin/account_transaction_form_screen.dart:188` and `lib/services/account_transaction_offline_service.dart:270`. Any backend change here is a fix, not a contract break. The fix must keep the response `{code:1, data:<formatTransaction>}` and must keep accepting multipart `user_id`, `amount`, `transaction_date`, `source`, `created_by_id`.
2. **Offline advisory question upload hits the wrong URL.** `lib/services/advisory_offline_service.dart:440`. This is client-side, so the backend cannot fix it unless it adds a top-level `apiadvisory/questions` route (not recommended).
3. **The account transaction list freezes on any non-success response.** `lib/screens/admin/account_transaction_list_screen.dart:170-172` assigns `result['transactions']` (null on failure) to a non-nullable List inside setState. Any backend `code:0` (DB error, bad `sort_by`, validation) leaves the spinner on forever. Keep `account-transactions` returning `code:1` with `data.transactions` as a List and `data.pagination.{current_page,last_page,total}` as **ints** (assigned raw at :177-180).
4. **User identity on finance endpoints comes only from the `User-Id` header.** `withdraw-requests/*`, `user-accounts/dashboard`, `dashboard/main` (fallback) and `dashboard/my-account-overview` call `Utils::get_user_id()`, which reads `$_SERVER['HTTP_USER_ID']` (`app/Models/Utils.php:463`), with no middleware. Live callers: `withdraw_request_api.dart:8,39,126,158` and `dashboard_api.dart:7`. If the backend moves to JWT-only identity, it must still accept `User-Id`, because the app always sends it but the JWT can be expired (60-minute TTL). Conversely, `account-transactions` GET has **no** server-side scoping. A non-admin who taps "Clear filters" (`account_transaction_list_screen.dart:205`) gets every user's ledger. Adding scoping is safe for the app.
5. **Dio-based advisory calls are Bearer-only and drop error bodies.** `lib/services/advisory_api.dart:23-101`. Protected routes depend on `EnsureTokenIsValid` decoding the JWT `sub` without verification (`EnsureTokenIsValid.php:35-50`). If the middleware starts verifying signature/expiry, `postAnswer` (:621) and `likeQuestion` (:666) start failing after token expiry, because no User-Id header is sent. Validation errors use HTTP 400 (ApiResponser `error()` default), so users see "Upload failed: DioException…" instead of the message. Returning 200 with `code:0` would surface the message.
6. **Withdraw request model has raw typed fields.** `lib/models/withdraw_request_model.dart:52-74`: `id`/`user_id` into int (`?? 0`), `processed_by_id`/`account_transaction_id` into int?, `can_be_processed` into bool, `user`/`processed_by`/`account_transaction` into Map?. These rely on the model casts (`WithdrawRequest::$casts` integer) and on relations serialising as null rather than `[]`. Changing casts (e.g. id as string) or the appends (`formatted_amount`, `formatted_balance`, `status_label`, `can_be_processed`) breaks the live withdrawal list (the error is caught and shown as an "Error: …" message).
7. **Market products drop the whole list on one bad `latest_price`.** `lib/models/MarketPriceProduct.dart:43-44` does `Map<String,dynamic>.from(json['latest_price'])`. `latest_price` must stay an object or null, never `[]`. All market endpoints must keep `data` as a JSON **array** (`->values()`), because the app does `as List<dynamic>` at MarketPrice.dart:163/211, MarketPriceCategory.dart:69 and MarketPriceProduct.dart:106.
8. **The admin users list is unbounded N+1.** `app/Http/Controllers/UserAccountController.php` `getAllUsersWithBalances` (2 queries per user over all users), live via `users_list_screen.dart:49`. A latency/timeout risk as the user table grows. App-side fields `id`, `transaction_count` and `summary.total_users` are assigned raw into int (`user_account_model.dart:114,120,139`).
9. **`dashboard/main` has a broad dependency surface.** `app/Http/Controllers/DashboardController.php:22-66` touches the insurance, medical and project_share tables, and any failure there becomes code 0. This is low impact today, because vsla_tab only toasts the `message` when no cache exists. It also exposes all users' recent medical requests (names/phones) to any caller.
10. **Advisory answers have no `question_id`.** `FarmerQuestionController` `formatAnswer` omits it, so `AdvisoryApiService.getQuestion` (advisory_api.dart:480) caches answers with `question_id=0` and the offline answer view is always empty. Adding `question_id` to the answer payload is a safe, additive improvement.

---

---

## 7. Consolidated broken and missing routes

The module sections above (A to E) give the details and file:line references.

### 7.1 Live code (user-visible today)

| # | App call | Call site | What happens | Section |
|---|---|---|---|---|
| 1 | POST `api/vsla-onboarding/update-step` | models/LoggedInUserModel.dart:819 | Path becomes `/api/api/…`, giving 404 on every onboarding step. The server step only moves forward as a side effect of the other onboarding endpoints. | A |
| 2 | POST `onesignal/register-device` | services/OneSignalService.dart:389 | **No route.** Fails silently on every login, so no device registration happens. | A |
| 3 | POST `users/register` (profiling) | screens/profiling/universal_user_registration_screen.dart:352 | Declared twice (api.php:37 and :99); `ApiAuthController@register` wins. It returns a flat user, not `data.user`, so the app saves a junk user row with id 0. | A |
| 4 | POST `update-profile`, `password-change`, `delete-account` | AccountEdit.dart:451, AccountChangePassword.dart:197, AccountSection.dart:347 | `ApiResponser::success($data, $msg, 1)` passes **1 as the HTTP status**, so Symfony throws, giving HTTP 500 **after** the change is saved. Users see "Failed"; after an account deletion the app doesn't log the user out. | A |
| 5 | GET `profiling/users`; POST `profiling/users/{id}/status` (verify) | profiling_users_list_screen.dart:88, :132 | 500: unknown column `phone_number_verified_at` (confirmed: `users` has no `*verified*` columns locally). | A |
| 6 | POST `vsla/loans` | services/vsla_loan_api.dart:58 | **405** (only GET `/`). LoanRequestFormScreen.dart:324 ignores the result and shows "submitted successfully" (**false success**). | B |
| 7 | POST `vsla/loans/{id}/approve`, `/reject` | vsla_loan_api.dart:91, :115 | **404.** Approve and reject always fail. | B |
| 8 | POST `vsla/loans/check-eligibility` | vsla_loan_api.dart:253 | **405** (matches GET `/{id}`). Eligibility is never shown. | B |
| 9 | GET `vsla/attendance/export-pdf` | services/attendance_service.dart:98 | **SHADOWED** by `vsla/attendance/{id}`, so it returns 404 "Attendance record not found". | B |
| 10 | GET `ffs-groups/subcounties/{id}`, `ffs-groups/parishes/{id}` | screens/profiling/ffs_group_registration_screen.dart:103, :125 | 500. FfsGroupController queries `locations.parent_id`, but the live table has `parent` (confirmed in the local DB). Dropdowns are always empty. | C |
| 11 | POST `ffs-groups` with `secondary_value_chains` | ffs_group_registration_screen.dart:208 | A list sent through FormData arrives as a single scalar, giving 422. | C |
| 12 | GET `locations/districts` | MemberRegisterScreen:56, MemberEditScreen:52, VslaGroupCreationScreen:99, location_service:27 | Declared twice; the later `LocationController@getDistricts` wins and also returns Region and Country rows. | C |
| 13 | POST `account-transactions` | admin/account_transaction_form_screen.dart:188, account_transaction_offline_service.dart:270 | 500. `Utils::get_user_from_request()` doesn't exist (confirmed: there is no such function in `app/`). The offline queue never clears. | E |
| 14 | POST `advisory/questions` (offline upload) | services/advisory_offline_service.dart:440 | Dio path has no leading slash, so the URL becomes `…/apiadvisory/questions` (404). Offline questions never upload. | E |
| 15 | GET `users/{id}` ("Contact seller") | screens/shop/ProductScreen.dart:100 | **No route.** | D |
| 16 | GET `api/membership-benefits` | models/MembershipPayment.dart:415 | Hits the catch-all `api/{model}`, and the app also throws on the result (a Map assigned to `RespondModel`). Only reached if the membership gate ever redirects. | D |
| 17 | POST `orders-create` | screens/cart/CheckoutScreen.dart:93 | Route OK, but `pay_on_delivery` is sent inside the `delivery` JSON while the backend reads it top-level, so cash-on-delivery orders are saved as `PENDING_PAYMENT`. | D |
| 18 | POST `session-create` | models/SessionLocal.dart:92 (startup) | **No route.** Does nothing unless old local rows exist. | D |

**The route exists but the response shape doesn't match what the app reads** (wrong data, no crash):

| Endpoint | App reads | Backend sends | Section |
|---|---|---|---|
| `POST members/sync` | `data.synced`, `data.failed`, `data.results`; clears the whole queue whenever `code==1` | `data.success[]`, `data.failed[]`; returns code 1 even when items fail. **Data loss.** | C |
| `GET agent-vsla/my-groups/{id}/delete-preview`, `GET vsla/groups/{g}/members/{m}/delete-preview` | `will_delete`, `total_records_to_delete` | `data_counts`, `total_records`. The user sees "0 records" before an irreversible delete. | B, C |
| `GET vsla/transactions/member-balance/{id}`, `group-balance/{id}` | `data.savings` … | `data.balances.savings` … (balances show 0) | B |
| `GET vsla/transactions/member-statement`, `group-statement`, `recent` | `data` as a List | `data.transactions` (lists are always empty) | B |
| `POST vsla-meetings/submit` `previous_action_plans_data[].planId` | sends the server id | backend looks the plan up by `local_id`, so plan status updates are dropped | B |
| `GET user-loans/{id}/transactions` | `type` | `transaction_type` | B |
| `GET vsla-onboarding/data/savings-cycle` | `data.cycle` | fields flat under `data` (prefill never runs) | A |
| `GET vsla-onboarding/data/chairperson`, `main-members` | `name` | `first_name`, `last_name` | A |
| `POST agent-vsla/my-groups/{id}/members/{mid}/set-password` (group detail screen variant) | sends `confirmation_text` | requires `new_password` (422; that method currently has no caller) | C |
| `GET ffs-training-sessions/{id}` resolutions | nested `responsible_person` | flat `responsible_person_name` | C |
| `PUT ffs-training-sessions/{id}` | sends literal `"group_ids[0]"` keys in a JSON body | JSON keys aren't bracket-parsed, so group edits are silently ignored | C |
| Member register and edit | education options `Secondary`, `Tertiary`, `University`; reads `district_name`, `subcounty_name`, `parish_name` | rejected by validation (422); name fields never sent | C |

### 7.2 Dead code (only matters if revived)

- **Double-prefixed** (`'${AppConfig.API_BASE_URL}/…'` passed into `Utils.http_*`):
  - AccountController: `user/update-profile`, `addresses/add|update|delete|set-default`
  - WishlistItem: `wishlist_get|add|remove|check|clear` (`wishlist_clear` is also missing on the backend)
  - Review.dart (10 calls) and ReviewCard `api/reviews/report`
  - PesapalService (6 calls; `pesapal/verify` and `pesapal/payment-methods` are also missing)
- **ApiService `'api/…'` gives `/apiapi/…`:** ModernCartController (5 calls), ProductService (7 calls), AppStateController `/auth/login`.
- **PhoneVerificationApi** `/api/check-phone|send-otp|verify-otp` gives `/api/api/…`. Unused; the live screens call `phone-verification/*` correctly.
- **No route:** `delivery_addresses` (underscore), `products-create`, `orders/{id}/update-payment-status`, `ping`, `associations`, `counselling-centres`, `events`, `institutions`, `jobs`, `my-subjects`, `news-posts`, `people` (GET and POST), `service-providers`, `my-sessions`, `my-students`, `my-classes`, `groups`, `update-bio/{id}`, `update-guardian/{id}`, `api/membership-payment/confirm`, `medical-service-requests/{id}/admin-update` (the backend has `/review`).
- **Route exists but the controller method is missing (500):** `chat-start`, `chat-send`, `chat-mark-as-read`, `chat-heads`, `chat-messages`.
- **SHADOWED:** GET `projects/transactions` is captured by `projects/{id}` (api.php `projects/{id}` is declared first).
- **Wrong controller via the catch-all `api/{model}`:** `api/membership-payments`, `api/membership-payment`, `api/Disability`.
- **Envelope mismatch:** the medical module checks `code==1`, but the backend sends `{success,…}` with no `code`. `LoanRequestScreen` reads `code` from `projects/{id}`, which also sends `success` only.
- **Payload mismatch:**
  - `pesapal/initialize` from UniversalPaymentDetailsScreen sends `payment_id`, but the backend requires `order_id`.
  - LocalImageToUploadModel sends `parent_id_local` instead of `parent_local_id`.
  - Review update is a POST without `_method`.
  - Review write routes need `auth:sanctum`, which the app's credentials can't satisfy.

---

## 8. Backend routes the mobile app never calls

These are candidates that mobile doesn't use. **This is not a deletion recommendation.** Some serve the web admin, gateways or ops, and some are the intended targets of broken app calls (see the Note column). There are 107 routes. Routes that only dead app code calls are **not** listed here; they appear as rows in the module tables.

| Method | URI | Controller | Note |
|---|---|---|---|
| GET | `profiling/users/filter-options` | UserRegistrationController@getFilterOptions |  |
| GET | `profiling/users/{id}` | UserRegistrationController@show |  |
| POST | `profile-update-comprehensive` | ApiResurceController@profile_update_comprehensive |  |
| POST | `orders-with-payment` | ApiResurceController@orders_with_payment |  |
| GET | `orders/check-pending-emails` | ApiResurceController@check_and_send_pending_emails |  |
| GET | `products/{id}` | ApiResurceController@product_get_by_id |  |
| GET | `products` | ApiResurceController@products | App uses `products-1` |
| GET | `live-search` | ApiResurceController@live_search |  |
| GET | `search-history` | ApiResurceController@search_history |  |
| POST | `search-history/clear` | ApiResurceController@clear_search_history |  |
| GET | `vsla-onboarding/config` | VslaOnboardingController@getOnboardingConfig |  |
| GET | `vsla-onboarding/status` | VslaOnboardingController@getOnboardingStatus |  |
| POST | `vsla-onboarding/update-step` | VslaOnboardingController@updateOnboardingStep | **Intended** target of LoggedInUserModel.dart:819, but the app calls `api/vsla-onboarding/update-step` (404) |
| GET | `agent-vsla/facilitator-info` | VslaAgentOnboardingController@facilitatorInfo |  |
| GET | `agent-vsla/my-groups/{id}/meetings` | VslaFacilitatorController@groupMeetings |  |
| GET | `agent-vsla/my-groups/{id}/manifest` | VslaFacilitatorController@groupManifest | Facilitators read group data via `agent-vsla/my-groups/{id}` |
| POST | `vsla/groups/{group_id}/members/{member_id}/reset-password` | VslaGroupManagementController@resetMemberPassword |  |
| POST | `vsla/cycles/auto-activate` | VslaConfigurationController@autoActivateCycle |  |
| GET | `vsla-meetings/stats` | VslaMeetingController@stats |  |
| GET | `vsla-meetings/export` | VslaMeetingController@exportCsv |  |
| GET | `vsla-meetings` | VslaMeetingController@index |  |
| GET | `vsla-meetings/{id}/export-pdf` | VslaMeetingController@exportPdf |  |
| PUT | `vsla-meetings/{id}/reprocess` | VslaMeetingController@reprocess |  |
| POST | `vsla/opening-balance/reprocess` | VslaOpeningBalanceController@reprocess |  |
| GET | `social-fund/transactions/{id}` | SocialFundTransactionController@show |  |
| POST | `vsla/meetings/submit` | VslaMeetingController@submit | Duplicate of `vsla-meetings/submit`, which the app uses |
| GET | `vsla/meetings/export` | VslaMeetingController@exportCsv |  |
| PUT | `vsla/meetings/{id}/reprocess` | VslaMeetingController@reprocess |  |
| GET | `vsla/cycles/{id}/report-pdf` | VslaMeetingController@cycleReport |  |
| GET | `loan-transactions/{loanId}` | LoanTransactionController@index |  |
| GET | `loan-transactions/{loanId}/balance` | LoanTransactionController@balance |  |
| POST | `loan-transactions/payment` | LoanTransactionController@createPayment |  |
| POST | `loan-transactions/penalty` | LoanTransactionController@addPenalty |  |
| POST | `loan-transactions/waiver` | LoanTransactionController@addWaiver |  |
| GET | `locations/subcounties/{districtId}` | LocationController@getSubcounties | App uses `locations/sub-counties/{id}` |
| GET | `reviews/stats` | ReviewController@stats |  |
| POST | `pesapal/ipn` | PesapalController@ipn | Gateway webhook, not for mobile |
| POST | `pesapal/register-ipn` | PesapalController@registerIpn | Admin/ops |
| POST | `pesapal/test` | PesapalController@test | Ops |
| GET | `admin/pesapal/analytics` | PesapalAdminController@analytics |  |
| GET | `admin/pesapal/transaction/{id}` | PesapalAdminController@transactionDetails |  |
| GET | `admin/pesapal/failed-transactions` | PesapalAdminController@failedTransactions |  |
| POST | `admin/pesapal/retry/{id}` | PesapalAdminController@retryTransaction |  |
| GET | `admin/pesapal/export` | PesapalAdminController@exportTransactions |  |
| GET | `advisory/posts/category` | AdvisoryController@getPostsByCategory |  |
| GET | `advisory/posts/search` | AdvisoryController@searchPosts |  |
| GET | `universal-payments/callback` | UniversalPaymentController@handleCallback | Gateway callback |
| POST | `universal-payments/callback` | UniversalPaymentController@handleCallback | Gateway callback |
| POST | `universal-payments/ipn` | UniversalPaymentController@handleIPN | Gateway webhook |
| GET | `dashboard/pending-insurance-transactions` | DashboardController@getPendingInsuranceTransactions |  |
| GET | `dashboard/insurance-overview` | DashboardController@getInsuranceOverview |  |
| GET | `dashboard/debug-projects` | closure | Debug closure; exposes data, no auth |
| POST | `fix-project-shares` | closure | One-time closure, **no auth, writes data** |
| POST | `membership-payment` | ApiResurceController@membership_payment_create | App calls `api/membership-payment` instead (catch-all) |
| POST | `membership-payment/confirm` | ApiResurceController@membership_payment_confirm | App calls `api/membership-payment/confirm` (404) |
| GET | `membership-benefits` | ApiResurceController@membership_benefits | App calls `api/membership-benefits` (catch-all) |
| GET | `transactions/stats` | TransactionController@stats |  |
| GET | `transactions/user/{userId}/balance` | TransactionController@getUserBalance |  |
| GET | `transactions/{id}` | TransactionController@show |  |
| PUT | `transactions/{id}` | TransactionController@update |  |
| PATCH | `transactions/{id}` | TransactionController@update |  |
| DELETE | `transactions/{id}` | TransactionController@destroy |  |
| POST | `transactions/{id}` | TransactionController@update |  |
| GET | `insurance-programs` | InsuranceProgramController@index |  |
| GET | `insurance-programs/stats` | InsuranceProgramController@stats |  |
| GET | `insurance-programs/{id}` | InsuranceProgramController@show |  |
| POST | `insurance-programs` | InsuranceProgramController@store |  |
| PUT | `insurance-programs/{id}` | InsuranceProgramController@update |  |
| PATCH | `insurance-programs/{id}` | InsuranceProgramController@update |  |
| DELETE | `insurance-programs/{id}` | InsuranceProgramController@destroy |  |
| POST | `insurance-programs/{id}` | InsuranceProgramController@update |  |
| GET | `insurance-subscriptions` | InsuranceSubscriptionController@index |  |
| GET | `insurance-subscriptions/user/{userId}` | InsuranceSubscriptionController@getUserSubscription |  |
| GET | `insurance-subscriptions/{id}` | InsuranceSubscriptionController@show |  |
| POST | `insurance-subscriptions` | InsuranceSubscriptionController@store |  |
| PUT | `insurance-subscriptions/{id}` | InsuranceSubscriptionController@update |  |
| PATCH | `insurance-subscriptions/{id}` | InsuranceSubscriptionController@update |  |
| POST | `insurance-subscriptions/{id}/suspend` | InsuranceSubscriptionController@suspend |  |
| POST | `insurance-subscriptions/{id}/activate` | InsuranceSubscriptionController@activate |  |
| POST | `insurance-subscriptions/{id}/cancel` | InsuranceSubscriptionController@cancel |  |
| DELETE | `insurance-subscriptions/{id}` | InsuranceSubscriptionController@destroy |  |
| POST | `insurance-subscriptions/{id}` | InsuranceSubscriptionController@update |  |
| GET | `insurance-subscription-payments` | InsuranceSubscriptionPaymentController@index |  |
| GET | `insurance-subscription-payments/stats` | InsuranceSubscriptionPaymentController@stats |  |
| GET | `insurance-subscription-payments/overdue` | InsuranceSubscriptionPaymentController@getOverdue |  |
| GET | `insurance-subscription-payments/user/{userId}` | InsuranceSubscriptionPaymentController@getUserPayments |  |
| GET | `insurance-subscription-payments/{id}` | InsuranceSubscriptionPaymentController@show |  |
| PUT | `insurance-subscription-payments/{id}` | InsuranceSubscriptionPaymentController@update |  |
| PATCH | `insurance-subscription-payments/{id}` | InsuranceSubscriptionPaymentController@update |  |
| POST | `insurance-subscription-payments/{id}/pay` | InsuranceSubscriptionPaymentController@markAsPaid |  |
| POST | `insurance-subscription-payments/{id}` | InsuranceSubscriptionPaymentController@update |  |
| GET | `medical-service-requests/user/{userId}` | MedicalServiceRequestController@getUserRequests |  |
| POST | `medical-service-requests/{id}/review` | MedicalServiceRequestController@review |  |
| GET | `ajax` | closure | Laravel-Admin widget |
| GET | `ajax/products` | closure | Laravel-Admin widget |
| GET | `ajax/orders` | closure | Laravel-Admin widget |
| GET | `ajax/product-details/{id}` | closure | Laravel-Admin widget |
| GET | `production-protocols` | ProductionProtocolController@index |  |
| GET | `production-protocols/{id}` | ProductionProtocolController@show |  |
| POST | `production-protocols` | ProductionProtocolController@store |  |
| PUT | `production-protocols/{id}` | ProductionProtocolController@update |  |
| DELETE | `production-protocols/{id}` | ProductionProtocolController@destroy |  |
| GET | `farms/{id}/calendar` | FarmController@getCalendarView |  |
| GET | `farm-activities` | FarmActivityController@index |  |
| POST | `farm-activities/{id}/upload-photo` | FarmActivityController@uploadPhoto |  |
| GET | `aesa-sessions/{id}/export-pdf` | AesaController@exportSessionPdf |  |
| GET | `market-price-trend` | ApiResurceController@market_price_trend |  |

---

## 9. Backend change checklist: invariants the app needs

Check a backend PR against these points. The section in brackets has the details.

**Envelope and transport (section 1)**
1. `code` stays the **integer** `1` for success and `0` for failure. Don't change it to `"1"` or `true`, and don't drop it in favour of `success` alone, except on the endpoints that already use `success`/`status` (next point).
2. Keep the existing per-endpoint keys exactly as they are:
   - `vsla-meetings/submit` uses a top-level `success: true`, `meeting_id` and `processing_status`, plus `warnings` and `errors` as **lists**. Duplicates use body `code: 409` with `meeting_id`. (B §1)
   - `phone-verification/*` use `status: 1`. (A)
   - `universal-payments`, `projects` and `medical` use `success`. (D, E)
3. Error responses must keep a **JSON object body** with a `message`. The HTTP status may change freely, **except** on `farms*` and `farm-activities*`: farm_service accepts only 200 there (201 only for the two create endpoints).
4. Never return 204 or an empty body, and never print PHP notices before the JSON.
5. Keep accepting the `User-Id` header, and the FormData `user` field where controllers read `$request->user`. Don't require a valid or unexpired JWT: the app never refreshes it, and OTP onboarding has no token.
6. Login (`users/login`) must return `data.remember_token`, at least 5 characters long, plus `data.id` greater than 0 and the full user fields (A §Critical). Don't add `remember_token` to `$hidden`.

**Data shapes**
7. List endpoints keep `data` as a **bare JSON array**, not paginated and not `{items:[]}`. Use `->values()` so arrays don't serialise as objects. This covers locations, market prices, members, ffs-training-sessions, aesa, farms, user-loans, vsla/cycles lists, orders, products-1, categories, vendors and delivery-addresses. Exceptions with a wrapper the app expects: `account-transactions` (`data.transactions` plus integer `data.pagination.*`), `profiling/users` (top-level `summary`) and `vsla/cycles` (`data.active_cycle`). (B, C, E)
8. Nested objects must be an object or `null`, never `[]`. This matters most for the manifest's `statistics`, `core_team`, `dashboard`, `active_cycle` and `group`; for `latest_price` in market products; and for withdraw-request relations. (B §2, E)
9. Numeric fields must stay JSON numbers wherever the app hard-casts them:
   - ids in `agent-vsla/all-meetings`, `ffs-training-sessions/{id}/expected-members`, locations (`id`, `parent`) and farm activities
   - `(float)` totals in facilitator meetings
   - counts in reset-preview
   - `days_remaining` in user-loans (watch for Carbon 3 returning floats). (B, C)
10. `members[].is_active` in the manifest stays a strict boolean. Dates that the app passes to `DateTime.parse` without a null guard must not be null (`user-loans`: `disbursement_date`, `created_at`). (B)
11. `membership-status` must keep returning `data.has_valid_membership` / `data.is_admin` as `true` or `1`. Today the only thing preventing a hard lock-out of every non-admin is `User::hasValidMembership()` returning `true`; keep it that way. (B, D)
12. `users/me` stays a single object (Map) that includes `group_id`. `users/my-roles` keeps its role slugs inside `data`. (A)
13. `vsla/groups/{id}/manifest` is parsed all-or-nothing: **one** type change anywhere discards the whole offline dataset. Treat every key in B §2 as frozen.

**Route table**
14. Don't reorder routes in a way that changes which duplicate wins:
    - `users/register`: ApiAuthController wins today.
    - `manifest`: ManifestController wins.
    - `locations/districts`: LocationController wins.
    - `locations/parishes/{id}`: ApiResurceController matches first, and the app needs its `success` key.
15. When fixing a shadowed or missing route (section 7), match the response shape the app already reads, not the controller's current shape.

---

## 10. Critical flows: where to find the required shapes

| Flow | Endpoints | Required request/response skeleton |
|---|---|---|
| Login / switch account / forgot password | `users/login` | Section A "Critical flow shapes" |
| Phone OTP self-registration | `phone-verification/check-phone`, `send-otp`, `verify-otp` (`status`-based) | Section A |
| Registration | `vsla-onboarding/register-admin`, `users/register` | Section A |
| VSLA self-onboarding | `vsla-onboarding/create-group`, `register-main-members`, `create-cycle`, `complete`, `data/*` | Section A |
| Session refresh | `users/me`, `users/my-roles`, `manifest` | Section A |
| Meeting submission and offline sync | `vsla-meetings/submit` (full payload) | Section B §1 |
| Group manifest / dashboard (offline cache) | `vsla/groups/{id}/manifest`, `/manifest/incremental`, `vsla/cycles`, `vsla/groups/{id}`, `vsla-groups/stats` | Section B §2, §5 |
| Loans | `vsla/loans*`, `user-loans*` | Section B §3 |
| Shareout 6-step wizard | `vsla/shareouts/*` | Section B §4 |
| Opening balances | `vsla/opening-balance/*` | Section B §6 |
| Facilitator hub / groups / members | `agent-vsla/*`, `members*` | Section C "Critical flow shapes" |
| FFS training sessions | `ffs-training-sessions*` incl. `wizard-report` | Section C |
| Farm and farm activities | `farms*`, `farm-activities*` (package:http, 200-only) | Section C |
| AESA | `aesa-sessions*`, `aesa-observations*`, `aesa-crop-observations*` | Section C |
