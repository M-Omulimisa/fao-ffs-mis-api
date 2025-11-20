# FAO FFS-MIS Module Mapping & Reusability Analysis

**Project:** Digital Management Information System for Farmer Field Schools (FFS)  
**Client:** Food and Agriculture Organization (FAO) - Uganda  
**Source Codebase:** DTEHM Insurance & E-commerce Platform (Laravel 8+)  
**Analysis Date:** November 20, 2025

---

## Executive Summary

This document maps existing DTEHM system components to the FAO FFS-MIS requirements. The existing Laravel codebase provides **60-70% reusable infrastructure**, requiring strategic adaptation rather than ground-up development.

### Reusability Score by Layer:
- **Foundation Layer (Platform Core):** 85% Reusable ✅
- **Core Modules:** 40% Reusable (needs significant adaptation) ⚠️
- **UI/Admin Panel:** 70% Reusable ✅
- **Mobile API Architecture:** 90% Reusable ✅

---

## 🏗️ LAYER 1: Foundation & Cross-Cutting Modules

### Module F1: Platform Core & Security

| Component | Existing System | Reusability | Adaptation Required |
|-----------|----------------|-------------|---------------------|
| **User Management** | ✅ Robust system with JWT auth, role-based access | **95% REUSABLE** | Extend roles for: FAO Admin, IP Manager, Field Facilitator, VSLA Treasurer, Farmer Member |
| **Authentication** | ✅ JWT (Tymon), Laravel Sanctum, Custom Auth Controller | **100% REUSABLE** | None - perfect fit for mobile-first architecture |
| **RBAC** | ✅ Laravel Admin roles (`admin_role_users` table) | **90% REUSABLE** | Add FFS-specific roles and permissions |
| **Data Encryption** | ✅ HTTPS, bcrypt passwords, encrypted sessions | **100% REUSABLE** | Add field-level encryption for sensitive VSLA data |
| **Audit Logging** | ⚠️ Basic timestamps (`created_at`, `updated_at`) | **60% REUSABLE** | Implement comprehensive audit trail (who/what/when) |
| **Consent Management** | ❌ Not present | **0% - BUILD NEW** | Required for GDPR/Uganda Data Protection Act compliance |

**Recommendation:** Keep existing auth system. Add:
- `AuditLog` model for comprehensive tracking
- `UserConsent` model for data protection compliance
- Extend `AdminRole` with FFS-specific permissions

---

### Module F2: System Administration & Configuration

| Component | Existing System | Reusability | Adaptation Required |
|-----------|----------------|-------------|---------------------|
| **Location Management** | ✅ `Location` model (districts, sub-counties) | **100% REUSABLE** | Already has Uganda admin structure - perfect for Karamoja's 9 districts |
| **Master Data** | ✅ Laravel Admin with CRUD for reference data | **85% REUSABLE** | Create new masters for: Value Chains, Crop Types, Training Topics |
| **Configuration System** | ✅ `SystemConfiguration` model | **100% REUSABLE** | Direct reuse for app settings |
| **Notification Engine** | ✅ OneSignal integration (`OneSignalService`) | **90% REUSABLE** | Extend for SMS/USSD via Uganda telcos |

**Recommendation:** Direct reuse with minor additions. Create:
- `ValueChain` model (Sorghum, Cassava, Livestock, etc.)
- `TrainingTopic` model (AESA, GAP, Climate-Smart practices)
- Extend `Location` with GPS coordinates for field mapping

---

## 🎯 LAYER 2: Core Functional Modules

### Module 1: Group & Member Registry

| Required Feature | Existing Equivalent | Reusability | Adaptation Strategy |
|------------------|---------------------|-------------|---------------------|
| **Group Profiling** | `Group` model exists (basic structure) | **70% REUSABLE** | Extend with: `group_type` (FFS/FBS/VSLA), `formation_date`, `value_chain_id`, `gps_coordinates` |
| **Member Management** | `User` model with hierarchies | **80% REUSABLE** | Already has: demographics, roles, parent-child relationships (perfect for group membership) |
| **Group-Member Link** | `DtehmMembership` (payment-focused) | **50% ADAPTABLE** | Create `GroupMember` pivot table: `group_id`, `user_id`, `role` (Leader/Treasurer/Member), `joined_at` |
| **Member Registration** | Admin panel with simplified forms | **90% REUSABLE** | Already captures: name, phone, gender, DOB, address - exact match for FFS needs |

**Adaptation Plan:**
```php
// Extend existing Group model
Schema::table('groups', function (Blueprint $table) {
    $table->enum('group_type', ['FFS', 'FBS', 'VSLA'])->after('name');
    $table->foreignId('value_chain_id')->nullable();
    $table->date('formation_date')->nullable();
    $table->string('gps_latitude')->nullable();
    $table->string('gps_longitude')->nullable();
    $table->foreignId('facilitator_id')->nullable(); // Link to User (facilitator)
});

// Create new GroupMember pivot
Schema::create('group_members', function (Blueprint $table) {
    $table->id();
    $table->foreignId('group_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('role', ['Leader', 'Treasurer', 'Secretary', 'Member'])->default('Member');
    $table->date('joined_at');
    $table->enum('status', ['Active', 'Inactive'])->default('Active');
    $table->timestamps();
});
```

---

### Module 2: Training & Field Activity Management

| Required Feature | Existing Equivalent | Reusability | Adaptation Strategy |
|------------------|---------------------|-------------|---------------------|
| **AESA Tracker** | ❌ None | **0% - BUILD NEW** | Create `AesaObservation` model with photo upload, pest/disease tracking |
| **Training Sessions** | `NewsPost` (content publishing) + `Job` (scheduling) | **40% ADAPTABLE** | Repurpose structure. Create `TrainingSession` model |
| **Attendance Logging** | `Participant` model exists! | **85% REUSABLE** | Already tracks: event attendance, user links - PERFECT FIT |
| **Training Content Library** | `NewsPost` with categories | **75% REUSABLE** | Rename/extend as `TrainingMaterial`. Add offline download flags |

**Adaptation Plan:**
```php
// NEW: AESA Observations
Schema::create('aesa_observations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('group_id')->constrained();
    $table->foreignId('facilitator_id')->constrained('users');
    $table->date('observation_date');
    $table->text('pest_disease_notes')->nullable();
    $table->text('soil_moisture_notes')->nullable();
    $table->text('plant_health_notes')->nullable();
    $table->json('photos')->nullable(); // Array of image paths
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});

// NEW: Training Sessions (extend Event concept)
Schema::create('training_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('group_id')->constrained();
    $table->foreignId('training_topic_id')->constrained();
    $table->date('session_date');
    $table->time('start_time')->nullable();
    $table->time('end_time')->nullable();
    $table->foreignId('facilitator_id')->constrained('users');
    $table->text('notes')->nullable();
    $table->integer('planned_attendance')->nullable();
    $table->integer('actual_attendance')->nullable();
    $table->timestamps();
});

// ADAPT existing Participant model
Schema::table('participants', function (Blueprint $table) {
    $table->foreignId('training_session_id')->nullable()->after('id');
    $table->enum('attendance_status', ['Present', 'Absent', 'Late'])->default('Present');
});
```

---

### Module 3: Financial Inclusion (VSLA Digital Ledger)

| Required Feature | Existing Equivalent | Reusability | Adaptation Strategy |
|------------------|---------------------|-------------|---------------------|
| **Savings Tracking** | `ProjectTransaction` (investment tracking) | **65% ADAPTABLE** | Similar structure: user, amount, date, type. Repurpose for VSLA |
| **Loan Management** | ❌ None (but has payment tracking) | **30% ADAPTABLE** | Adapt `UniversalPayment` workflow for loan disbursement/repayment |
| **Financial Ledger** | `AccountTransaction` model | **75% REUSABLE** | Already tracks: debits, credits, balances - PERFECT for VSLA ledger |
| **Group Funds** | `Project` model (tracks pooled investments) | **70% ADAPTABLE** | Has: `total_investment`, `total_returns` - similar to VSLA fund tracking |

**Adaptation Plan:**
```php
// ADAPT AccountTransaction for VSLA
Schema::table('account_transactions', function (Blueprint $table) {
    $table->foreignId('vsla_group_id')->nullable()->after('user_id');
    $table->enum('transaction_type', ['SHARE_PURCHASE', 'LOAN_DISBURSEMENT', 'LOAN_REPAYMENT', 'INTEREST_PAYMENT', 'FINE'])->after('type');
    $table->decimal('share_value', 10, 2)->nullable();
    $table->integer('shares_count')->nullable();
});

// NEW: VSLA Savings Cycles
Schema::create('vsla_cycles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('group_id')->constrained();
    $table->string('cycle_name'); // e.g., "2025 Planting Season"
    $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->decimal('share_price', 10, 2);
    $table->enum('status', ['Active', 'Closed'])->default('Active');
    $table->decimal('total_savings', 12, 2)->default(0);
    $table->decimal('total_loans_outstanding', 12, 2)->default(0);
    $table->timestamps();
});

// NEW: VSLA Loans
Schema::create('vsla_loans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vsla_cycle_id')->constrained();
    $table->foreignId('borrower_id')->constrained('users');
    $table->decimal('principal_amount', 10, 2);
    $table->decimal('interest_rate', 5, 2); // Percentage
    $table->date('disbursement_date');
    $table->date('due_date');
    $table->decimal('total_amount_due', 10, 2); // Principal + Interest
    $table->decimal('amount_repaid', 10, 2)->default(0);
    $table->enum('status', ['ACTIVE', 'REPAID', 'OVERDUE', 'WRITTEN_OFF'])->default('ACTIVE');
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->timestamps();
});
```

**Why this works:** The existing `AccountTransaction` system already handles:
- User-level balance tracking
- Debit/credit operations
- Transaction history
- We just need to extend it for group-level aggregation

---

### Module 4: Advisory Hub & E-Learning

| Required Feature | Existing Equivalent | Reusability | Adaptation Strategy |
|------------------|---------------------|-------------|---------------------|
| **Content Management** | `NewsPost` + `PostCategory` | **90% REUSABLE** | Already has: rich text editor (Summernote), categories, images. Rename to `AdvisoryContent` |
| **Multi-Channel Delivery** | API endpoints for mobile apps | **80% REUSABLE** | Existing REST API serves Flutter/React. Add IVR/USSD endpoints |
| **E-Learning Modules** | ❌ None | **0% - BUILD NEW** | Create `Course`, `CourseModule`, `UserProgress` models |
| **Content Tagging** | Dynamic tagging system exists | **100% REUSABLE** | `products` table has `tags` field. Same approach for content |

**Adaptation Plan:**
```php
// RENAME NewsPost → AdvisoryContent (or keep table, update semantically)
Schema::table('news_posts', function (Blueprint $table) {
    $table->renameColumn('news_posts', 'advisory_contents'); // Optional
    $table->foreignId('value_chain_id')->nullable();
    $table->enum('content_type', ['ARTICLE', 'AUDIO', 'VIDEO', 'INFOGRAPHIC'])->default('ARTICLE');
    $table->string('audio_file_url')->nullable();
    $table->string('video_file_url')->nullable();
    $table->boolean('available_offline')->default(false);
    $table->enum('delivery_channel', ['APP', 'IVR', 'USSD', 'SMS'])->default('APP');
});

// NEW: E-Learning System
Schema::create('courses', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description');
    $table->foreignId('value_chain_id')->nullable();
    $table->integer('duration_minutes')->nullable();
    $table->string('thumbnail')->nullable();
    $table->enum('difficulty', ['BEGINNER', 'INTERMEDIATE', 'ADVANCED'])->default('BEGINNER');
    $table->boolean('is_published')->default(false);
    $table->timestamps();
});

Schema::create('course_modules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('course_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('content');
    $table->string('video_url')->nullable();
    $table->integer('order')->default(0);
    $table->json('quiz_questions')->nullable(); // [{question, options, answer}]
    $table->timestamps();
});

Schema::create('user_course_progress', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('course_id')->constrained()->cascadeOnDelete();
    $table->foreignId('current_module_id')->nullable()->constrained('course_modules');
    $table->integer('progress_percentage')->default(0);
    $table->integer('quiz_score')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
```

---

### Module 5: Market Linkages (E-Marketplace)

| Required Feature | Existing Equivalent | Reusability | Adaptation Strategy |
|------------------|---------------------|-------------|---------------------|
| **Service Provider Directory** | `ServiceProvider` model exists! | **95% REUSABLE** | Already has: name, contact, location, services. ADD: `provider_type` (Input/Equipment/Buyer) |
| **Price Information** | `Product` model with pricing | **70% ADAPTABLE** | Repurpose for commodity pricing. Add `CommodityPrice` model |
| **Produce Listings** | `Product` model | **60% ADAPTABLE** | Create `ProduceListing` linking groups to available produce |
| **Order/Request System** | `Order`, `OrderedItem` models | **50% ADAPTABLE** | Simplify for group-level bulk orders (not full e-commerce) |

**Adaptation Plan:**
```php
// EXTEND ServiceProvider
Schema::table('service_providers', function (Blueprint $table) {
    $table->enum('provider_type', ['AGRI_INPUT', 'EQUIPMENT', 'BUYER', 'TRANSPORTER'])->after('name');
    $table->string('certification')->nullable(); // e.g., "Certified Seed Dealer"
    $table->json('value_chains_served')->nullable(); // ["Sorghum", "Cassava"]
});

// NEW: Commodity Market Prices
Schema::create('commodity_prices', function (Blueprint $table) {
    $table->id();
    $table->string('commodity_name'); // e.g., "Sorghum (per kg)"
    $table->foreignId('market_location_id')->constrained('locations');
    $table->decimal('price_ugx', 10, 2);
    $table->date('price_date');
    $table->string('source')->nullable(); // e.g., "Ministry of Agriculture"
    $table->timestamps();
});

// NEW: Group Produce Listings
Schema::create('produce_listings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('group_id')->constrained();
    $table->string('commodity_name');
    $table->decimal('quantity_kg', 10, 2);
    $table->decimal('asking_price_per_kg', 10, 2);
    $table->date('available_from');
    $table->date('available_until')->nullable();
    $table->enum('status', ['AVAILABLE', 'SOLD', 'EXPIRED'])->default('AVAILABLE');
    $table->text('notes')->nullable();
    $table->timestamps();
});

// NEW: Group Input Needs
Schema::create('input_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('group_id')->constrained();
    $table->string('input_type'); // e.g., "Seeds", "Fertilizer"
    $table->decimal('quantity_needed', 10, 2);
    $table->string('unit'); // e.g., "kg", "bags"
    $table->date('needed_by');
    $table->enum('status', ['OPEN', 'FULFILLED', 'CANCELLED'])->default('OPEN');
    $table->timestamps();
});
```

**Rationale:** The RFP emphasizes "linkages" not transactions. We're creating a bulletin board, not a payment gateway.

---

### Module 6: MEL Dashboard (Monitoring, Evaluation & Learning)

| Required Feature | Existing Equivalent | Reusability | Adaptation Strategy |
|------------------|---------------------|-------------|---------------------|
| **Real-Time KPI Dashboard** | Laravel Admin charts (ChartJS extension) | **90% REUSABLE** | Already has widgets, metrics. Create FFS-specific indicators |
| **Data Visualization** | `laravel-admin-ext/chartjs` | **100% REUSABLE** | Direct reuse. Add custom FFS charts |
| **Filtering System** | Laravel Admin grid filters | **95% REUSABLE** | Existing filters by date, location, category - perfect fit |
| **Report Generator** | `barryvdh/laravel-dompdf` for PDFs | **100% REUSABLE** | Already generates invoices/reports. Create FFS templates |
| **Export to Excel** | Laravel Excel (implied) | **90% REUSABLE** | Standard Laravel export functionality |

**Adaptation Plan:**
```php
// NEW: Dashboard Indicators
Schema::create('mel_indicators', function (Blueprint $table) {
    $table->id();
    $table->string('indicator_name'); // e.g., "FFS Groups Active"
    $table->text('description');
    $table->string('data_source'); // e.g., "groups table"
    $table->string('calculation_formula'); // e.g., "COUNT WHERE status=Active"
    $table->enum('indicator_type', ['NUMERIC', 'PERCENTAGE', 'TREND'])->default('NUMERIC');
    $table->integer('target_value')->nullable();
    $table->timestamps();
});

// Dashboard Controller (extend existing admin dashboard)
// File: app/Admin/Controllers/FfsDashboardController.php
public function index()
{
    $metrics = [
        'total_groups' => Group::where('group_type', 'FFS')->count(),
        'total_members' => GroupMember::distinct('user_id')->count(),
        'active_vsla_cycles' => VslaCycle::where('status', 'Active')->count(),
        'total_savings' => VslaCycle::sum('total_savings'),
        'training_sessions_this_month' => TrainingSession::whereMonth('created_at', now()->month)->count(),
        'gender_breakdown' => User::selectRaw('gender, COUNT(*) as count')->groupBy('gender')->get(),
    ];
    
    return view('admin.ffs_dashboard', compact('metrics'));
}
```

---

## 🏗️ LAYER 3: Implementation Enablers

### Offline-First Capability

**Existing:** None (fully online system)  
**Required:** SQLite local storage, sync engine  
**Strategy:**

1. **Mobile App:** Use Flutter with Hive/SQLite for offline storage
2. **Backend API:** Add sync endpoints
```php
// NEW API Endpoints for Sync
Route::post('sync/upload', [SyncController::class, 'upload']); // Upload offline data
Route::get('sync/download', [SyncController::class, 'download']); // Download updates
Route::post('sync/conflict-resolve', [SyncController::class, 'resolveConflict']);
```

3. **Conflict Resolution:** Timestamp-based (last-write-wins) or manual resolution

---

### Device Management

**Existing:** None  
**Required:** Rugged tablet tracking  
**Strategy:**

```php
Schema::create('field_devices', function (Blueprint $table) {
    $table->id();
    $table->string('device_id')->unique(); // IMEI or UUID
    $table->string('device_model');
    $table->foreignId('assigned_to_user_id')->nullable()->constrained('users');
    $table->foreignId('assigned_to_group_id')->nullable()->constrained('groups');
    $table->date('assigned_date')->nullable();
    $table->enum('status', ['ACTIVE', 'INACTIVE', 'LOST', 'DAMAGED'])->default('ACTIVE');
    $table->timestamp('last_sync_at')->nullable();
    $table->string('app_version')->nullable();
    $table->timestamps();
});
```

---

## 📊 Summary: What We Keep vs. What We Build

### ✅ KEEP AS-IS (Direct Reuse)

1. **Laravel 8 Framework** - Stable, mature, well-documented
2. **JWT Authentication** - Perfect for mobile API
3. **Location Management** - Already has Uganda districts
4. **Laravel Admin Panel** - Powerful CRUD interface for FAO staff
5. **File Upload System** - For photos, documents
6. **Email/SMS Infrastructure** - Notification system
7. **API Architecture** - REST endpoints for mobile apps
8. **Database Design Patterns** - Migrations, relationships, soft deletes

### ⚙️ ADAPT (60-80% Reusable)

1. **Group Management** - Extend existing `Group` model
2. **User/Member System** - Add FFS-specific fields
3. **Transaction System** - Repurpose for VSLA ledger
4. **Content Management** - Rename NewsPost → Advisory Content
5. **Service Providers** - Add agricultural focus
6. **Admin Dashboard** - Add FFS-specific metrics

### 🆕 BUILD FROM SCRATCH (20-40% New Code)

1. **AESA Observation Tracker** - Unique to FFS methodology
2. **Training Session Logger** - Field-specific workflows
3. **VSLA Loan Management** - Financial inclusion module
4. **E-Learning System** - Interactive courses with quizzes
5. **Offline Sync Engine** - Critical for low-connectivity
6. **Field Device Management** - Tablet tracking
7. **IVR/USSD Integration** - Multi-channel content delivery
8. **MEL Indicator Framework** - Project-specific KPIs

---

## 🎯 Development Effort Estimation

| Module | Reuse % | New Build % | Estimated Days |
|--------|---------|-------------|----------------|
| **Foundation (Auth, Security)** | 85% | 15% | 5 days |
| **Module 1: Group Registry** | 75% | 25% | 8 days |
| **Module 2: Training Management** | 40% | 60% | 15 days |
| **Module 3: VSLA Ledger** | 65% | 35% | 12 days |
| **Module 4: Advisory Hub** | 80% | 20% | 10 days |
| **Module 5: E-Marketplace** | 70% | 30% | 8 days |
| **Module 6: MEL Dashboard** | 90% | 10% | 7 days |
| **Offline Sync System** | 0% | 100% | 10 days |
| **Mobile App (Flutter)** | 50% | 50% | 20 days |
| **Testing & Refinement** | - | - | 15 days |
| **TOTAL** | - | - | **110 days (22 weeks)** |

**Timeline:** Fits within **Month 1-2** of the 6-month project (with 2-3 developers).

---

## 🚀 Next Steps

1. **Archive old documentation** - Move DTEHM-specific docs to `/docs/legacy/`
2. **Update README** - Reflect FAO FFS-MIS project
3. **Create migration plan** - Database schema evolution roadmap
4. **Set up development branches** - `feature/ffs-groups`, `feature/vsla-ledger`, etc.
5. **Initialize new modules** - Stub out models, controllers, migrations
6. **Design database schema** - Full ERD for FFS-MIS

---

**Prepared by:** AI Assistant  
**Review Status:** Pending Technical Lead Approval  
**Next Review:** Before implementation kickoff
