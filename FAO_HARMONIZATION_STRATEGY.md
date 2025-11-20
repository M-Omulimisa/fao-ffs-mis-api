# FAO FFS-MIS Harmonization Strategy
## Refactoring DTEHM System to FFS-Focused Platform

**Document Purpose:** Detailed technical guide for transforming the existing DTEHM insurance/e-commerce codebase into the FAO FFS Digital MIS  
**Last Updated:** November 20, 2025  
**Status:** Technical Planning Phase

---

## Table of Contents

1. [Strategic Approach](#strategic-approach)
2. [Database Schema Evolution](#database-schema-evolution)
3. [Model Refactoring Plan](#model-refactoring-plan)
4. [Controller Adaptation](#controller-adaptation)
5. [API Endpoint Restructuring](#api-endpoint-restructuring)
6. [Admin Panel Customization](#admin-panel-customization)
7. [Code Cleanup & Legacy Removal](#code-cleanup--legacy-removal)
8. [Migration Execution Plan](#migration-execution-plan)

---

## 1. Strategic Approach

### Philosophy: "Evolve, Don't Demolish"

We will **strategically refactor** the existing DTEHM codebase rather than starting from scratch. This approach:

✅ **Preserves:** Proven authentication, RBAC, location data, admin panel infrastructure  
✅ **Adapts:** User management, group system, transaction tracking for FFS/VSLA needs  
✅ **Removes:** E-commerce products, orders, payment gateways (Stripe), insurance-specific modules  
✅ **Adds:** Training management, AESA tracking, VSLA ledger, advisory content, e-marketplace

---

### Three-Phase Refactoring Strategy

```
Phase 1: Foundation Cleanup (Week 1-2)
├── Archive legacy DTEHM docs
├── Remove unused e-commerce tables
├── Extend User model for FFS roles
└── Seed Karamoja location data

Phase 2: Core Module Development (Week 3-10)
├── Adapt Group model → FFS/FBS/VSLA
├── Extend AccountTransaction → VSLA ledger
├── Repurpose NewsPost → AdvisoryContent
└── Build NEW: Training, AESA, Courses

Phase 3: Integration & Polish (Week 11-14)
├── Connect modules (Group ↔ Training ↔ VSLA)
├── Build MEL dashboard
└── Remove dead code, optimize queries
```

---

## 2. Database Schema Evolution

### 2.1. Tables to KEEP (Direct Reuse)

| Table | Reason | Adaptation Needed |
|-------|--------|-------------------|
| `users` | Core authentication, profiles | Add FFS-specific fields (see below) |
| `locations` | Uganda admin units (districts, sub-counties) | Seed Karamoja data, add GPS coordinates |
| `groups` | Existing group structure | Extend with `group_type`, `value_chain_id` |
| `admin_users`, `admin_roles` | Laravel Admin panel | Add FFS roles (IP Manager, Facilitator) |
| `account_transactions` | Financial tracking | Extend for VSLA transactions |
| `news_posts`, `post_categories` | Content publishing | Rename/extend as `advisory_contents` |
| `service_providers` | Directory | Extend with `provider_type` (agri-input, buyer) |
| `participants` | Event attendance | Adapt for training session attendance |

---

### 2.2. Tables to ADAPT (Modify Structure)

#### **`users` Table Extensions**

```sql
-- Add FFS-specific fields
ALTER TABLE users 
ADD COLUMN ffs_role ENUM('FAO_ADMIN', 'IP_MANAGER', 'FIELD_FACILITATOR', 'VSLA_TREASURER', 'FARMER_MEMBER', 'MEO_VIEWER') NULL AFTER user_type,
ADD COLUMN assigned_district_id BIGINT NULL AFTER location_id,
ADD COLUMN primary_language ENUM('English', 'Karimojong', 'Ateso', 'Luganda') DEFAULT 'English',
ADD COLUMN digital_literacy_level ENUM('BEGINNER', 'INTERMEDIATE', 'ADVANCED') DEFAULT 'BEGINNER',
ADD COLUMN last_training_date DATE NULL,
ADD FOREIGN KEY (assigned_district_id) REFERENCES locations(id);

-- Remove insurance-specific fields (if we want to clean up)
ALTER TABLE users
DROP COLUMN dtehm_membership_paid_at,
DROP COLUMN dtehm_membership_amount,
DROP COLUMN dtehm_membership_payment_id;
```

---

#### **`groups` Table Extensions**

```sql
-- Extend existing groups table
ALTER TABLE groups
ADD COLUMN group_type ENUM('FFS', 'FBS', 'VSLA') NOT NULL DEFAULT 'FFS' AFTER name,
ADD COLUMN value_chain_id BIGINT NULL AFTER group_type,
ADD COLUMN formation_date DATE NULL,
ADD COLUMN gps_latitude DECIMAL(10, 8) NULL,
ADD COLUMN gps_longitude DECIMAL(11, 8) NULL,
ADD COLUMN facilitator_id BIGINT NULL,
ADD COLUMN photo_url VARCHAR(500) NULL,
ADD COLUMN meeting_frequency ENUM('WEEKLY', 'BIWEEKLY', 'MONTHLY') DEFAULT 'WEEKLY',
ADD FOREIGN KEY (value_chain_id) REFERENCES value_chains(id),
ADD FOREIGN KEY (facilitator_id) REFERENCES users(id);

-- Add indexes for performance
CREATE INDEX idx_groups_type ON groups(group_type);
CREATE INDEX idx_groups_location ON groups(location_id);
```

---

#### **`account_transactions` Table Extensions (for VSLA)**

```sql
-- Extend for VSLA-specific transaction types
ALTER TABLE account_transactions
ADD COLUMN vsla_cycle_id BIGINT NULL AFTER user_id,
ADD COLUMN transaction_category ENUM('SHARE_PURCHASE', 'LOAN_DISBURSEMENT', 'LOAN_REPAYMENT', 'INTEREST_PAYMENT', 'FINE', 'SOCIAL_FUND', 'OTHER') NULL AFTER type,
ADD COLUMN share_value DECIMAL(10, 2) NULL,
ADD COLUMN shares_count INT NULL,
ADD COLUMN recorded_by_id BIGINT NULL COMMENT 'Treasurer who recorded transaction',
ADD FOREIGN KEY (vsla_cycle_id) REFERENCES vsla_cycles(id),
ADD FOREIGN KEY (recorded_by_id) REFERENCES users(id);

-- Rename 'type' column for clarity (optional)
ALTER TABLE account_transactions CHANGE COLUMN type legacy_type VARCHAR(255);
```

---

#### **`news_posts` → `advisory_contents` (Semantic Rename)**

```sql
-- Option 1: Rename table (cleaner but breaks existing code)
RENAME TABLE news_posts TO advisory_contents;

-- Option 2: Keep table name, extend fields (safer)
ALTER TABLE news_posts
ADD COLUMN content_type ENUM('ARTICLE', 'AUDIO', 'VIDEO', 'INFOGRAPHIC') DEFAULT 'ARTICLE' AFTER description,
ADD COLUMN audio_file_url VARCHAR(500) NULL,
ADD COLUMN video_file_url VARCHAR(500) NULL,
ADD COLUMN value_chain_id BIGINT NULL,
ADD COLUMN season_tag ENUM('PLANTING', 'GROWING', 'HARVEST', 'POST_HARVEST', 'ALL_SEASON') DEFAULT 'ALL_SEASON',
ADD COLUMN delivery_channels JSON COMMENT 'Array: ["APP", "IVR", "USSD", "SMS"]',
ADD COLUMN available_offline BOOLEAN DEFAULT FALSE,
ADD FOREIGN KEY (value_chain_id) REFERENCES value_chains(id);

-- Update post_category for FFS topics
UPDATE post_categories SET name = 'Pest Management' WHERE name = 'Health Tips';
```

---

#### **`service_providers` Table Extensions**

```sql
ALTER TABLE service_providers
ADD COLUMN provider_type ENUM('AGRI_INPUT', 'EQUIPMENT', 'BUYER', 'TRANSPORTER', 'EXTENSION_SERVICE') NOT NULL DEFAULT 'AGRI_INPUT' AFTER name,
ADD COLUMN certification VARCHAR(255) NULL COMMENT 'e.g., Certified Seed Dealer',
ADD COLUMN value_chains_served JSON NULL COMMENT 'Array: ["Sorghum", "Cassava"]',
ADD COLUMN verified BOOLEAN DEFAULT FALSE,
ADD COLUMN verification_date DATE NULL;
```

---

### 2.3. Tables to ARCHIVE/DROP (Legacy E-commerce)

```sql
-- Move to archive database or drop after backup

-- E-commerce product tables
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS product_categories CASCADE;
DROP TABLE IF EXISTS product_has_attributes CASCADE;
DROP TABLE IF EXISTS product_orders CASCADE;
DROP TABLE IF EXISTS ordered_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS wishlists CASCADE;
DROP TABLE IF EXISTS reviews CASCADE;

-- Insurance tables (if not needed)
DROP TABLE IF EXISTS insurance_programs CASCADE;
DROP TABLE IF EXISTS insurance_subscriptions CASCADE;
DROP TABLE IF EXISTS insurance_subscription_payments CASCADE;
DROP TABLE IF EXISTS dtehm_memberships CASCADE;
DROP TABLE IF EXISTS membership_payments CASCADE;

-- Payment gateway tables (keep only Pesapal for mobile money)
DROP TABLE IF EXISTS stripe_transactions CASCADE; -- If exists

-- Other legacy tables
DROP TABLE IF EXISTS deliveries CASCADE;
DROP TABLE IF EXISTS delivery_addresses CASCADE;
DROP TABLE IF EXISTS invoices CASCADE;
DROP TABLE IF EXISTS invoice_items CASCADE;
DROP TABLE IF EXISTS quotations CASCADE;
DROP TABLE IF EXISTS quotation_items CASCADE;
```

**⚠️ CRITICAL:** Backup database before dropping tables!

```bash
mysqldump -u root -p fao_ffs_mis > backup_before_cleanup_$(date +%Y%m%d).sql
```

---

### 2.4. NEW Tables to CREATE (FFS-Specific)

#### **Master Data Tables**

```sql
-- Value Chains
CREATE TABLE value_chains (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    icon_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO value_chains (name, description) VALUES
('Sorghum', 'Drought-resistant cereal crop'),
('Cassava', 'Root crop for food security'),
('Livestock (Cattle)', 'Pastoral cattle rearing'),
('Livestock (Goats)', 'Small ruminants'),
('Poultry', 'Chicken and eggs'),
('Vegetables', 'Horticultural crops'),
('Apiculture', 'Beekeeping and honey production');

-- Training Topics
CREATE TABLE training_topics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    category ENUM('AESA', 'GAP', 'CLIMATE_SMART', 'BUSINESS_SKILLS', 'FINANCIAL_LITERACY', 'NUTRITION', 'POST_HARVEST') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO training_topics (name, category, description) VALUES
('Agro-Ecosystem Analysis (AESA)', 'AESA', 'Field observation and decision-making'),
('Good Agronomic Practices (GAP)', 'GAP', 'Best practices for crop production'),
('Integrated Pest Management', 'CLIMATE_SMART', 'Eco-friendly pest control'),
('Water Harvesting Techniques', 'CLIMATE_SMART', 'Rainwater collection and storage'),
('Group Savings Management', 'FINANCIAL_LITERACY', 'VSLA best practices'),
('Market Linkages', 'BUSINESS_SKILLS', 'Connecting to buyers');
```

---

#### **Group Management Tables**

```sql
-- Group Members (Pivot Table)
CREATE TABLE group_members (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    group_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    role ENUM('LEADER', 'TREASURER', 'SECRETARY', 'MEMBER') DEFAULT 'MEMBER',
    joined_at DATE NOT NULL,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    exit_date DATE NULL,
    exit_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_group_user (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_group_members_status ON group_members(status);
```

---

#### **Training & AESA Tables**

```sql
-- Training Sessions
CREATE TABLE training_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    group_id BIGINT NOT NULL,
    training_topic_id BIGINT NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    facilitator_id BIGINT NOT NULL,
    planned_attendance INT NULL,
    actual_attendance INT NULL,
    male_attendance INT DEFAULT 0,
    female_attendance INT DEFAULT 0,
    notes TEXT NULL,
    status ENUM('PLANNED', 'COMPLETED', 'CANCELLED') DEFAULT 'PLANNED',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (training_topic_id) REFERENCES training_topics(id),
    FOREIGN KEY (facilitator_id) REFERENCES users(id)
);

CREATE INDEX idx_training_date ON training_sessions(session_date);
CREATE INDEX idx_training_facilitator ON training_sessions(facilitator_id);

-- AESA Observations
CREATE TABLE aesa_observations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    group_id BIGINT NOT NULL,
    facilitator_id BIGINT NOT NULL,
    observation_date DATE NOT NULL,
    plot_location VARCHAR(255) NULL,
    gps_latitude DECIMAL(10, 8) NULL,
    gps_longitude DECIMAL(11, 8) NULL,
    crop_stage VARCHAR(100) NULL COMMENT 'e.g., Flowering, Fruiting',
    pest_disease_notes TEXT NULL,
    soil_moisture_notes TEXT NULL,
    plant_health_notes TEXT NULL,
    weather_conditions VARCHAR(255) NULL,
    photos JSON NULL COMMENT 'Array of image URLs',
    recommended_actions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (facilitator_id) REFERENCES users(id)
);

CREATE INDEX idx_aesa_date ON aesa_observations(observation_date);
```

---

#### **VSLA Financial Tables**

```sql
-- VSLA Cycles
CREATE TABLE vsla_cycles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    group_id BIGINT NOT NULL,
    cycle_name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    share_price DECIMAL(10, 2) NOT NULL,
    status ENUM('ACTIVE', 'CLOSED') DEFAULT 'ACTIVE',
    total_savings DECIMAL(12, 2) DEFAULT 0.00,
    total_loans_outstanding DECIMAL(12, 2) DEFAULT 0.00,
    total_social_fund DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
);

CREATE INDEX idx_vsla_status ON vsla_cycles(status);

-- VSLA Loans
CREATE TABLE vsla_loans (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    vsla_cycle_id BIGINT NOT NULL,
    borrower_id BIGINT NOT NULL,
    loan_reference VARCHAR(50) UNIQUE,
    principal_amount DECIMAL(10, 2) NOT NULL,
    interest_rate DECIMAL(5, 2) NOT NULL COMMENT 'Percentage per cycle',
    disbursement_date DATE NOT NULL,
    due_date DATE NOT NULL,
    total_amount_due DECIMAL(10, 2) NOT NULL COMMENT 'Principal + Interest',
    amount_repaid DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('ACTIVE', 'REPAID', 'OVERDUE', 'WRITTEN_OFF') DEFAULT 'ACTIVE',
    purpose TEXT NULL,
    approved_by BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vsla_cycle_id) REFERENCES vsla_cycles(id) ON DELETE CASCADE,
    FOREIGN KEY (borrower_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE INDEX idx_vsla_loans_status ON vsla_loans(status);
```

---

#### **E-Learning Tables**

```sql
-- Courses
CREATE TABLE courses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    value_chain_id BIGINT NULL,
    duration_minutes INT NULL,
    thumbnail VARCHAR(500) NULL,
    difficulty ENUM('BEGINNER', 'INTERMEDIATE', 'ADVANCED') DEFAULT 'BEGINNER',
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (value_chain_id) REFERENCES value_chains(id)
);

-- Course Modules (Lessons)
CREATE TABLE course_modules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    course_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    video_url VARCHAR(500) NULL,
    order_index INT DEFAULT 0,
    quiz_questions JSON NULL COMMENT '[{question, options, correct_answer}]',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- User Course Progress
CREATE TABLE user_course_progress (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    course_id BIGINT NOT NULL,
    current_module_id BIGINT NULL,
    progress_percentage INT DEFAULT 0,
    quiz_score INT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_course (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (current_module_id) REFERENCES course_modules(id) ON DELETE SET NULL
);
```

---

#### **E-Marketplace Tables**

```sql
-- Commodity Market Prices
CREATE TABLE commodity_prices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    commodity_name VARCHAR(255) NOT NULL,
    market_location_id BIGINT NOT NULL,
    price_ugx DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(50) DEFAULT 'kg',
    price_date DATE NOT NULL,
    source VARCHAR(255) NULL COMMENT 'e.g., MAAIF, District Office',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (market_location_id) REFERENCES locations(id)
);

CREATE INDEX idx_commodity_date ON commodity_prices(price_date);

-- Group Produce Listings
CREATE TABLE produce_listings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    group_id BIGINT NOT NULL,
    commodity_name VARCHAR(255) NOT NULL,
    quantity_kg DECIMAL(10, 2) NOT NULL,
    asking_price_per_kg DECIMAL(10, 2) NOT NULL,
    available_from DATE NOT NULL,
    available_until DATE NULL,
    status ENUM('AVAILABLE', 'SOLD', 'EXPIRED') DEFAULT 'AVAILABLE',
    contact_person VARCHAR(255) NULL,
    contact_phone VARCHAR(20) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
);

-- Group Input Requests
CREATE TABLE input_requests (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    group_id BIGINT NOT NULL,
    input_type VARCHAR(255) NOT NULL,
    quantity_needed DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    needed_by DATE NOT NULL,
    status ENUM('OPEN', 'FULFILLED', 'CANCELLED') DEFAULT 'OPEN',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
);
```

---

#### **MEL Dashboard Tables**

```sql
-- MEL Indicators (Configuration)
CREATE TABLE mel_indicators (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    indicator_name VARCHAR(255) NOT NULL,
    description TEXT,
    data_source TEXT COMMENT 'SQL query or table name',
    calculation_formula TEXT,
    indicator_type ENUM('NUMERIC', 'PERCENTAGE', 'TREND') DEFAULT 'NUMERIC',
    target_value DECIMAL(15, 2) NULL,
    unit VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- MEL Snapshots (Historical Data)
CREATE TABLE mel_snapshots (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    indicator_id BIGINT NOT NULL,
    value DECIMAL(15, 2) NOT NULL,
    snapshot_date DATE NOT NULL,
    location_id BIGINT NULL COMMENT 'For district-level snapshots',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (indicator_id) REFERENCES mel_indicators(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id)
);

CREATE INDEX idx_mel_snapshot_date ON mel_snapshots(snapshot_date);
```

---

## 3. Model Refactoring Plan

### 3.1. KEEP & EXTEND (Modify Existing Models)

#### **User Model (`app/Models/User.php`)**

**Current State:** Has DTEHM membership fields, insurance references  
**Target State:** FFS-focused user with roles and district assignments

**Changes:**

```php
// File: app/Models/User.php

// ADD new relationships
public function assignedDistrict()
{
    return $this->belongsTo(Location::class, 'assigned_district_id');
}

public function facilitatedGroups()
{
    return $this->hasMany(Group::class, 'facilitator_id');
}

public function groupMemberships()
{
    return $this->hasMany(GroupMember::class);
}

public function trainingSessionsConducted()
{
    return $this->hasMany(TrainingSession::class, 'facilitator_id');
}

public function aesaObservations()
{
    return $this->hasMany(AesaObservation::class, 'facilitator_id');
}

public function vslaTransactions()
{
    return $this->hasMany(AccountTransaction::class);
}

public function vslaLoans()
{
    return $this->hasMany(VslaLoan::class, 'borrower_id');
}

public function courseProgress()
{
    return $this->hasMany(UserCourseProgress::class);
}

// ADD scopes for FFS roles
public function scopeFacilitators($query)
{
    return $query->where('ffs_role', 'FIELD_FACILITATOR');
}

public function scopeIpManagers($query)
{
    return $query->where('ffs_role', 'IP_MANAGER');
}

// REMOVE insurance-specific relationships (if cleaning up)
// Comment out or delete:
// public function dtehmMembershipPayment() { ... }
// public function insuranceSubscriptions() { ... }
```

---

#### **Group Model (`app/Models/Group.php`)**

**Changes:**

```php
// File: app/Models/Group.php

// EXTEND fillable fields
protected $fillable = [
    'name',
    'group_type',
    'value_chain_id',
    'location_id',
    'formation_date',
    'gps_latitude',
    'gps_longitude',
    'facilitator_id',
    'photo_url',
    'meeting_frequency',
    'status',
];

// ADD casts
protected $casts = [
    'formation_date' => 'date',
    'gps_latitude' => 'decimal:8',
    'gps_longitude' => 'decimal:8',
];

// ADD relationships
public function valueChain()
{
    return $this->belongsTo(ValueChain::class);
}

public function facilitator()
{
    return $this->belongsTo(User::class, 'facilitator_id');
}

public function members()
{
    return $this->hasMany(GroupMember::class);
}

public function activeMembers()
{
    return $this->hasMany(GroupMember::class)->where('status', 'ACTIVE');
}

public function trainingSessions()
{
    return $this->hasMany(TrainingSession::class);
}

public function aesaObservations()
{
    return $this->hasMany(AesaObservation::class);
}

public function vslaCycles()
{
    return $this->hasMany(VslaCycle::class);
}

public function produceListings()
{
    return $this->hasMany(ProduceListing::class);
}

public function inputRequests()
{
    return $this->hasMany(InputRequest::class);
}

// ADD scopes
public function scopeOfType($query, $type)
{
    return $query->where('group_type', $type);
}

public function scopeInDistrict($query, $districtId)
{
    return $query->whereHas('location', function($q) use ($districtId) {
        $q->where('parent_id', $districtId)->orWhere('id', $districtId);
    });
}

// ADD helper methods
public function getMemberCount()
{
    return $this->activeMembers()->count();
}

public function getGenderBreakdown()
{
    return $this->activeMembers()
        ->join('users', 'group_members.user_id', '=', 'users.id')
        ->selectRaw('users.gender, COUNT(*) as count')
        ->groupBy('users.gender')
        ->pluck('count', 'gender');
}
```

---

#### **AccountTransaction Model (VSLA Extension)**

```php
// File: app/Models/AccountTransaction.php

// EXTEND fillable
protected $fillable = [
    'user_id',
    'vsla_cycle_id',
    'type', // Legacy field, keep for backward compatibility
    'transaction_category', // New FFS-specific field
    'amount',
    'share_value',
    'shares_count',
    'description',
    'transaction_date',
    'recorded_by_id',
];

// ADD casts
protected $casts = [
    'transaction_date' => 'date',
    'amount' => 'decimal:2',
    'share_value' => 'decimal:2',
];

// ADD relationships
public function vslaCycle()
{
    return $this->belongsTo(VslaCycle::class);
}

public function recordedBy()
{
    return $this->belongsTo(User::class, 'recorded_by_id');
}

// ADD scopes
public function scopeSharePurchases($query)
{
    return $query->where('transaction_category', 'SHARE_PURCHASE');
}

public function scopeLoanDisbursements($query)
{
    return $query->where('transaction_category', 'LOAN_DISBURSEMENT');
}

public function scopeLoanRepayments($query)
{
    return $query->where('transaction_category', 'LOAN_REPAYMENT');
}
```

---

### 3.2. CREATE NEW (FFS-Specific Models)

#### **ValueChain Model**

```php
// File: app/Models/ValueChain.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValueChain extends Model
{
    protected $fillable = ['name', 'description', 'icon_url'];

    // Relationships
    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function advisoryContents()
    {
        return $this->hasMany(AdvisoryContent::class); // Or NewsPost
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function serviceProviders()
    {
        return $this->whereJsonContains('value_chains_served', $this->name)->get();
    }
}
```

---

#### **TrainingSession Model**

```php
// File: app/Models/TrainingSession.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSession extends Model
{
    protected $fillable = [
        'group_id',
        'training_topic_id',
        'session_date',
        'start_time',
        'end_time',
        'facilitator_id',
        'planned_attendance',
        'actual_attendance',
        'male_attendance',
        'female_attendance',
        'notes',
        'status',
    ];

    protected $casts = [
        'session_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    // Relationships
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function trainingTopic()
    {
        return $this->belongsTo(TrainingTopic::class);
    }

    public function facilitator()
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('session_date', now()->month);
    }

    // Helper methods
    public function markAsCompleted()
    {
        $this->status = 'COMPLETED';
        $this->actual_attendance = $this->participants()->count();
        $this->male_attendance = $this->participants()
            ->join('users', 'participants.user_id', '=', 'users.id')
            ->where('users.gender', 'Male')->count();
        $this->female_attendance = $this->participants()
            ->join('users', 'participants.user_id', '=', 'users.id')
            ->where('users.gender', 'Female')->count();
        $this->save();
    }
}
```

---

#### **VslaCycle & VslaLoan Models**

```php
// File: app/Models/VslaCycle.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VslaCycle extends Model
{
    protected $fillable = [
        'group_id', 'cycle_name', 'start_date', 'end_date', 
        'share_price', 'status', 'total_savings', 'total_loans_outstanding', 'total_social_fund'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'share_price' => 'decimal:2',
        'total_savings' => 'decimal:2',
        'total_loans_outstanding' => 'decimal:2',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function loans()
    {
        return $this->hasMany(VslaLoan::class);
    }

    // Calculate total savings from transactions
    public function recalculateTotals()
    {
        $this->total_savings = $this->transactions()
            ->where('transaction_category', 'SHARE_PURCHASE')
            ->sum('amount');
        
        $this->total_loans_outstanding = $this->loans()
            ->where('status', 'ACTIVE')
            ->sum(DB::raw('total_amount_due - amount_repaid'));
        
        $this->save();
    }
}
```

```php
// File: app/Models/VslaLoan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VslaLoan extends Model
{
    protected $fillable = [
        'vsla_cycle_id', 'borrower_id', 'loan_reference', 'principal_amount', 
        'interest_rate', 'disbursement_date', 'due_date', 'total_amount_due', 
        'amount_repaid', 'status', 'purpose', 'approved_by'
    ];

    protected $casts = [
        'disbursement_date' => 'date',
        'due_date' => 'date',
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'total_amount_due' => 'decimal:2',
        'amount_repaid' => 'decimal:2',
    ];

    // Auto-generate loan reference on creating
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($loan) {
            if (empty($loan->loan_reference)) {
                $loan->loan_reference = 'VSLA-' . strtoupper(uniqid());
            }
            
            // Auto-calculate total_amount_due
            $loan->total_amount_due = $loan->principal_amount + 
                ($loan->principal_amount * $loan->interest_rate / 100);
        });
    }

    public function vslaCycle()
    {
        return $this->belongsTo(VslaCycle::class);
    }

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Helper methods
    public function getBalanceRemaining()
    {
        return $this->total_amount_due - $this->amount_repaid;
    }

    public function isOverdue()
    {
        return $this->status === 'ACTIVE' && 
               $this->due_date->isPast() && 
               $this->amount_repaid < $this->total_amount_due;
    }
}
```

---

## 4. Controller Adaptation

### 4.1. ARCHIVE Unused Controllers

```bash
# Move to /app/Http/Controllers/Legacy/
mkdir -p app/Http/Controllers/Legacy

mv app/Http/Controllers/ProductController.php app/Http/Controllers/Legacy/
mv app/Http/Controllers/OrderController.php app/Http/Controllers/Legacy/
mv app/Http/Controllers/InsuranceProgramController.php app/Http/Controllers/Legacy/
# ... etc for all e-commerce/insurance controllers
```

---

### 4.2. CREATE NEW FFS Controllers

#### **GroupController (API)**

```php
// File: app/Http/Controllers/Api/GroupController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    // List groups (with filters)
    public function index(Request $request)
    {
        $query = Group::with(['valueChain', 'facilitator', 'location']);

        // Filters
        if ($request->has('group_type')) {
            $query->where('group_type', $request->group_type);
        }

        if ($request->has('district_id')) {
            $query->where('location_id', $request->district_id);
        }

        if ($request->has('facilitator_id')) {
            $query->where('facilitator_id', $request->facilitator_id);
        }

        $groups = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $groups
        ]);
    }

    // Create group
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group_type' => 'required|in:FFS,FBS,VSLA',
            'value_chain_id' => 'nullable|exists:value_chains,id',
            'location_id' => 'required|exists:locations,id',
            'formation_date' => 'required|date',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
            'facilitator_id' => 'required|exists:users,id',
            'photo' => 'nullable|image|max:2048', // 2MB max
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('groups', 'public');
            $validated['photo_url'] = $path;
        }

        $group = Group::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully',
            'data' => $group->load(['valueChain', 'facilitator', 'location'])
        ], 201);
    }

    // Get single group with members
    public function show($id)
    {
        $group = Group::with([
            'valueChain', 
            'facilitator', 
            'location', 
            'members.user'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $group
        ]);
    }

    // Update group
    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'string|max:255',
            'status' => 'in:ACTIVE,INACTIVE',
            // ... other fields
        ]);

        $group->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Group updated successfully',
            'data' => $group
        ]);
    }

    // Add member to group
    public function addMember(Request $request, $groupId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:LEADER,TREASURER,SECRETARY,MEMBER',
            'joined_at' => 'required|date',
        ]);

        $validated['group_id'] = $groupId;

        $member = GroupMember::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Member added successfully',
            'data' => $member->load('user')
        ], 201);
    }

    // Get group members
    public function members($groupId)
    {
        $members = GroupMember::where('group_id', $groupId)
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $members
        ]);
    }
}
```

---

## 5. API Endpoint Restructuring

### 5.1. NEW API Routes (Add to `routes/api.php`)

```php
// File: routes/api.php

use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\TrainingController;
use App\Http\Controllers\Api\VslaController;
use App\Http\Controllers\Api\AdvisoryController;
use App\Http\Controllers\Api\MarketplaceController;

// ============= FFS GROUPS =============
Route::prefix('groups')->group(function () {
    Route::get('/', [GroupController::class, 'index']);
    Route::post('/', [GroupController::class, 'store']);
    Route::get('/{id}', [GroupController::class, 'show']);
    Route::put('/{id}', [GroupController::class, 'update']);
    Route::delete('/{id}', [GroupController::class, 'destroy']);
    
    // Members
    Route::post('/{id}/members', [GroupController::class, 'addMember']);
    Route::get('/{id}/members', [GroupController::class, 'members']);
    Route::delete('/{id}/members/{memberId}', [GroupController::class, 'removeMember']);
});

// ============= TRAINING & AESA =============
Route::prefix('training')->group(function () {
    Route::get('/sessions', [TrainingController::class, 'index']);
    Route::post('/sessions', [TrainingController::class, 'store']);
    Route::get('/sessions/{id}', [TrainingController::class, 'show']);
    Route::put('/sessions/{id}', [TrainingController::class, 'update']);
    Route::post('/sessions/{id}/attendance', [TrainingController::class, 'recordAttendance']);
    Route::post('/sessions/{id}/complete', [TrainingController::class, 'markAsCompleted']);
    
    // AESA
    Route::get('/aesa', [TrainingController::class, 'aesaIndex']);
    Route::post('/aesa', [TrainingController::class, 'aesaStore']);
    Route::get('/aesa/{id}', [TrainingController::class, 'aesaShow']);
    
    // Topics
    Route::get('/topics', [TrainingController::class, 'topics']);
});

// ============= VSLA LEDGER =============
Route::prefix('vsla')->group(function () {
    Route::get('/cycles', [VslaController::class, 'cyclesIndex']);
    Route::post('/cycles', [VslaController::class, 'cycleStore']);
    Route::get('/cycles/{id}', [VslaController::class, 'cycleShow']);
    
    // Transactions
    Route::post('/transactions', [VslaController::class, 'recordTransaction']);
    Route::get('/transactions', [VslaController::class, 'transactionIndex']);
    
    // Loans
    Route::post('/loans', [VslaController::class, 'loanApply']);
    Route::put('/loans/{id}/approve', [VslaController::class, 'loanApprove']);
    Route::post('/loans/{id}/repayment', [VslaController::class, 'loanRepayment']);
    Route::get('/loans', [VslaController::class, 'loanIndex']);
    
    // Reports
    Route::get('/cycles/{id}/summary', [VslaController::class, 'cycleSummary']);
    Route::get('/cycles/{id}/member-statement', [VslaController::class, 'memberStatement']);
});

// ============= ADVISORY CONTENT =============
Route::prefix('advisory')->group(function () {
    Route::get('/content', [AdvisoryController::class, 'index']); // List articles
    Route::get('/content/{id}', [AdvisoryController::class, 'show']);
    Route::post('/content/{id}/download', [AdvisoryController::class, 'markAsDownloaded']);
    
    // Courses
    Route::get('/courses', [AdvisoryController::class, 'coursesIndex']);
    Route::get('/courses/{id}', [AdvisoryController::class, 'courseShow']);
    Route::post('/courses/{id}/enroll', [AdvisoryController::class, 'courseEnroll']);
    Route::post('/courses/{courseId}/modules/{moduleId}/complete', [AdvisoryController::class, 'moduleComplete']);
    Route::get('/my-courses', [AdvisoryController::class, 'myCourses']);
});

// ============= E-MARKETPLACE =============
Route::prefix('marketplace')->group(function () {
    // Service Providers
    Route::get('/providers', [MarketplaceController::class, 'providersIndex']);
    Route::get('/providers/{id}', [MarketplaceController::class, 'providersShow']);
    
    // Commodity Prices
    Route::get('/prices', [MarketplaceController::class, 'pricesIndex']);
    
    // Produce Listings
    Route::get('/produce', [MarketplaceController::class, 'produceIndex']);
    Route::post('/produce', [MarketplaceController::class, 'produceStore']);
    Route::put('/produce/{id}', [MarketplaceController::class, 'produceUpdate']);
    
    // Input Requests
    Route::get('/input-requests', [MarketplaceController::class, 'inputRequestsIndex']);
    Route::post('/input-requests', [MarketplaceController::class, 'inputRequestsStore']);
});

// ============= MASTER DATA =============
Route::get('/value-chains', function () {
    return response()->json(['success' => true, 'data' => \App\Models\ValueChain::all()]);
});

Route::get('/training-topics', function () {
    return response()->json(['success' => true, 'data' => \App\Models\TrainingTopic::all()]);
});
```

---

### 5.2. DEPRECATE Old E-commerce Routes

```php
// Comment out or remove in routes/api.php

// Route::get('products', [ApiResurceController::class, 'products']);
// Route::post('orders', [ApiResurceController::class, "orders_submit"]);
// Route::get('wishlist_get', [ApiResurceController::class, 'wishlist_get']);
// ... etc
```

---

## 6. Admin Panel Customization

### 6.1. Create FFS-Specific Admin Controllers

**Location:** `app/Admin/Controllers/`

**New Controllers:**

1. `FfsGroupController.php` - Manage groups
2. `TrainingSessionController.php` - View training logs
3. `VslaCycleController.php` - Manage VSLA cycles
4. `AdvisoryContentController.php` - Publish advisory content
5. `MelDashboardController.php` - View M&E metrics

**Example: FfsGroupController**

```php
// File: app/Admin/Controllers/FfsGroupController.php

namespace App\Admin\Controllers;

use App\Models\Group;
use App\Models\ValueChain;
use App\Models\Location;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FfsGroupController extends AdminController
{
    protected $title = 'FFS/FBS/VSLA Groups';

    protected function grid()
    {
        $grid = new Grid(new Group());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Group Name'));
        $grid->column('group_type', __('Type'))->label([
            'FFS' => 'success',
            'FBS' => 'info',
            'VSLA' => 'warning',
        ]);
        $grid->column('valueChain.name', __('Value Chain'));
        $grid->column('location.name', __('Location'));
        $grid->column('facilitator.name', __('Facilitator'));
        $grid->column('formation_date', __('Formed'));
        $grid->column('status', __('Status'))->label([
            'ACTIVE' => 'success',
            'INACTIVE' => 'danger',
        ]);

        $grid->filter(function($filter){
            $filter->like('name', 'Group Name');
            $filter->equal('group_type', 'Type')->select(['FFS' => 'FFS', 'FBS' => 'FBS', 'VSLA' => 'VSLA']);
            $filter->equal('location_id', 'District')->select(Location::pluck('name', 'id'));
            $filter->equal('status', 'Status')->select(['ACTIVE' => 'Active', 'INACTIVE' => 'Inactive']);
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Group());

        $form->text('name', __('Group Name'))->required();
        $form->select('group_type', __('Type'))->options([
            'FFS' => 'Farmer Field School',
            'FBS' => 'Farmer Business School',
            'VSLA' => 'Village Savings & Loan Association',
        ])->required();

        $form->select('value_chain_id', __('Value Chain'))
            ->options(ValueChain::pluck('name', 'id'));

        $form->select('location_id', __('Location'))
            ->options(Location::where('order', 1)->pluck('name', 'id'))
            ->required();

        $form->date('formation_date', __('Formation Date'))->default(now());

        $form->decimal('gps_latitude', __('GPS Latitude'));
        $form->decimal('gps_longitude', __('GPS Longitude'));

        $form->select('facilitator_id', __('Facilitator'))
            ->options(User::where('ffs_role', 'FIELD_FACILITATOR')->pluck('name', 'id'));

        $form->image('photo_url', __('Group Photo'))->move('groups');

        $form->select('meeting_frequency', __('Meeting Frequency'))
            ->options([
                'WEEKLY' => 'Weekly',
                'BIWEEKLY' => 'Bi-Weekly',
                'MONTHLY' => 'Monthly',
            ]);

        $form->select('status', __('Status'))->options([
            'ACTIVE' => 'Active',
            'INACTIVE' => 'Inactive',
        ])->default('ACTIVE');

        return $form;
    }
}
```

---

### 6.2. Update Admin Menu

```php
// File: app/Admin/routes.php

$router->group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');

    // ========== FFS MANAGEMENT ==========
    $router->resource('ffs-groups', FfsGroupController::class);
    $router->resource('training-sessions', TrainingSessionController::class);
    $router->resource('aesa-observations', AesaObservationController::class);
    $router->resource('vsla-cycles', VslaCycleController::class);
    $router->resource('vsla-loans', VslaLoanController::class);
    
    // ========== CONTENT & ADVISORY ==========
    $router->resource('advisory-content', AdvisoryContentController::class);
    $router->resource('courses', CourseController::class);
    $router->resource('service-providers', ServiceProviderController::class);
    
    // ========== MASTER DATA ==========
    $router->resource('value-chains', ValueChainController::class);
    $router->resource('training-topics', TrainingTopicController::class);
    
    // ========== M&E ==========
    $router->get('mel-dashboard', 'MelDashboardController@index');
    $router->get('reports/groups-summary', 'ReportController@groupsSummary');
    $router->get('reports/training-summary', 'ReportController@trainingSummary');
    $router->get('reports/vsla-summary', 'ReportController@vslaSummary');
    
    // ========== USER MANAGEMENT ==========
    $router->resource('users', UserController::class); // Keep existing
    $router->resource('locations', LocationController::class); // Keep existing
});
```

---

## 7. Code Cleanup & Legacy Removal

### Step 1: Archive Old Documentation

```bash
mkdir docs/legacy
mv *DTEHM*.md docs/legacy/
mv *INSURANCE*.md docs/legacy/
mv *ECOMMERCE*.md docs/legacy/
mv *ORDER*.md docs/legacy/
mv *PRODUCT*.md docs/legacy/
```

---

### Step 2: Remove Unused Dependencies

```bash
# Edit composer.json, remove:
# - "stripe/stripe-php": "^13.2"  (if not using Stripe)

# Keep these:
# - "tymon/jwt-auth"
# - "encore/laravel-admin"
# - "barryvdh/laravel-dompdf"
# - "guzzlehttp/guzzle"

composer update
```

---

### Step 3: Clean Up .env

```bash
# Remove Stripe keys
STRIPE_KEY=...
STRIPE_SECRET=...

# Keep Pesapal (for mobile money)
PESAPAL_CONSUMER_KEY=...
PESAPAL_CONSUMER_SECRET=...
```

---

### Step 4: Update APP_NAME

```env
APP_NAME="FAO FFS MIS"
DB_DATABASE=fao_ffs_mis
```

---

## 8. Migration Execution Plan

### Execution Order (Critical!)

```bash
# Week 1: Backup & Foundation
mysqldump -u root -p fao_ffs_mis > backup_pre_migration.sql

# Week 1: Master Data
php artisan migrate --path=database/migrations/2025_11_20_create_value_chains_table.php
php artisan migrate --path=database/migrations/2025_11_20_create_training_topics_table.php
php artisan db:seed --class=ValueChainSeeder
php artisan db:seed --class=TrainingTopicSeeder

# Week 2: Extend Core Tables
php artisan migrate --path=database/migrations/2025_11_20_extend_users_table.php
php artisan migrate --path=database/migrations/2025_11_20_extend_groups_table.php
php artisan migrate --path=database/migrations/2025_11_20_extend_account_transactions_table.php
php artisan migrate --path=database/migrations/2025_11_20_extend_news_posts_table.php
php artisan migrate --path=database/migrations/2025_11_20_extend_service_providers_table.php

# Week 2: New FFS Tables
php artisan migrate --path=database/migrations/2025_11_20_create_group_members_table.php
php artisan migrate --path=database/migrations/2025_11_20_create_training_sessions_table.php
php artisan migrate --path=database/migrations/2025_11_20_create_aesa_observations_table.php
php artisan migrate --path=database/migrations/2025_11_20_create_vsla_cycles_table.php
php artisan migrate --path=database/migrations/2025_11_20_create_vsla_loans_table.php
php artisan migrate --path=database/migrations/2025_11_20_create_courses_tables.php
php artisan migrate --path=database/migrations/2025_11_20_create_marketplace_tables.php
php artisan migrate --path=database/migrations/2025_11_20_create_mel_tables.php

# Week 3: Seed Karamoja Location Data
php artisan db:seed --class=KaramojaLocationSeeder

# Week 3+: Drop Legacy Tables (AFTER BACKUP!)
php artisan migrate --path=database/migrations/2025_11_20_drop_legacy_tables.php
```

---

### Rollback Plan

```bash
# If something goes wrong
mysql -u root -p fao_ffs_mis < backup_pre_migration.sql

# Then fix migration and re-run
```

---

## Conclusion

This harmonization strategy provides a clear, step-by-step technical roadmap for transforming the DTEHM codebase into the FAO FFS Digital MIS. By **evolving rather than rebuilding**, we:

✅ Save 60-70% development time  
✅ Preserve proven infrastructure (auth, admin panel, API architecture)  
✅ Focus effort on FFS-specific features (Training, AESA, VSLA)  
✅ Maintain code quality and documentation standards

**Next Steps:**
1. Review and approve this harmonization strategy
2. Create migration files in sequence
3. Begin Week 1 execution: Foundation cleanup
4. Proceed with Sprint 1 development

---

**Document Status:** READY FOR IMPLEMENTATION  
**Approval Required From:** Technical Lead, FAO Project Manager
