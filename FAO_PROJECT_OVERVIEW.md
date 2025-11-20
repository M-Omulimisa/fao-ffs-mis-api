# FAO FFS Digital MIS - Project Overview & Technical Specification

**Document Version:** 1.0  
**Last Updated:** November 20, 2025  
**Status:** Pre-Implementation Planning Phase

---

## Executive Summary

The Food and Agriculture Organization (FAO) of the United Nations seeks to develop and deploy a Digital Management Information System (MIS) for Farmer Field Schools (FFS) activities under the FOSTER Project in Karamoja, Uganda. This system will digitize agropastoral learning, financial inclusion, and market linkages for vulnerable communities across 9 districts.

**Project Code:** UNJP/UGA/068/EC  
**Tender Reference:** 2025/FRUGA/FRUGA...  
**Contract Duration:** 6 Months  
**Budget Range:** USD 50,000 - 150,000 (competitive bidding)  
**Target Beneficiaries:** 5,000+ farmers in FFS/FBS/VSLA groups

---

## 1. Project Context & Background

### The Challenge

Karamoja subregion faces:
- **Food Insecurity:** Chronic malnutrition, climate shocks
- **Weak Institutions:** Limited data systems, paper-based records
- **Digital Divide:** <50% internet access, low digital literacy
- **Gender Gaps:** Women underserved in agricultural services

### The Solution

A **mobile-first, offline-capable MIS** that:
1. Digitizes FFS group operations (registration, training, M&E)
2. Enables VSLA financial tracking (savings, loans, interest)
3. Delivers localized e-advisory content (IVR, SMS, app)
4. Connects farmers to markets (inputs, buyers, prices)
5. Provides real-time dashboards for FAO/IP decision-making

### Target Districts (Karamoja)

1. Abim
2. Amudat
3. Kaabong
4. Kotido
5. Moroto
6. Napak
7. Nakapiripirit
8. Nabilatuk
9. Karenga

---

## 2. System Architecture

### Three-Layer Foundation

```
┌─────────────────────────────────────────────────────────────┐
│                    LAYER 1: FOUNDATION                       │
│  ┌─────────────────────┐  ┌─────────────────────────────┐  │
│  │ F1: Platform Core   │  │ F2: System Admin &          │  │
│  │ - RBAC              │  │     Configuration           │  │
│  │ - Security          │  │ - Location Master Data      │  │
│  │ - Offline Sync      │  │ - Notification Engine       │  │
│  └─────────────────────┘  └─────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│              LAYER 2: CORE FUNCTIONAL MODULES                │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐      │
│  │ Module 1 │ │ Module 2 │ │ Module 3 │ │ Module 4 │      │
│  │ Group &  │ │ Training │ │   VSLA   │ │ Advisory │      │
│  │ Member   │ │   & AESA │ │  Ledger  │ │   Hub    │      │
│  │ Registry │ │ Tracking │ │          │ │          │      │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘      │
│  ┌──────────┐ ┌──────────┐                                 │
│  │ Module 5 │ │ Module 6 │                                 │
│  │  Market  │ │   MEL    │                                 │
│  │ Linkages │ │ Dashboard│                                 │
│  └──────────┘ └──────────┘                                 │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│         LAYER 3: DELIVERY & SUSTAINABILITY ENABLERS          │
│  - Mobile App (Flutter) with Offline Storage                │
│  - Training Materials (ToT Model)                            │
│  - Technical Documentation & Handover Package                │
│  - Device Management (Rugged Tablets)                        │
└─────────────────────────────────────────────────────────────┘
```

### User Roles & Permissions

| Role | Access Level | Key Functions |
|------|--------------|---------------|
| **Super Admin (FAO)** | Full system access | System config, user management, global reports |
| **IP Manager (Implementing Partner)** | Multi-district | View/edit assigned groups, approve payments, generate reports |
| **Field Facilitator** | Group-level | Register members, log training, record AESA, manage VSLA |
| **VSLA Treasurer** | Group finances | Record savings/loans, generate financial reports |
| **Farmer Member** | Personal profile | View training history, access advisory content, check VSLA balance |
| **M&E Officer (View-Only)** | Read-only | Access dashboards, export data, generate donor reports |

---

## 3. Core Modules - Detailed Specification

### Module 1: Group & Member Registry

**Purpose:** Single source of truth for all FFS/FBS/VSLA groups and members

#### Features

1. **Group Onboarding**
   - Create profiles for FFS, FBS, VSLA
   - Capture: Group Name, Type, Location (GPS), Formation Date, Value Chain
   - Upload group photo
   - Assign facilitator

2. **Member Management**
   - Register individuals: Name, Gender, Age, Phone, Photo
   - Assign role in group (Leader, Treasurer, Secretary, Member)
   - Track membership status (Active/Inactive)
   - Link members across FFS-FBS-VSLA

3. **Inter-Group Linkages**
   - Visualize FFS → FBS → VSLA relationships
   - Shared member identification
   - Cross-group reporting

#### Database Schema

```sql
-- Groups Table
CREATE TABLE groups (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    group_type ENUM('FFS', 'FBS', 'VSLA'),
    value_chain_id BIGINT FOREIGN KEY,
    location_id BIGINT FOREIGN KEY, -- District/Sub-county
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    formation_date DATE,
    facilitator_id BIGINT FOREIGN KEY REFERENCES users(id),
    photo_url VARCHAR(500),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Group Members (Pivot Table)
CREATE TABLE group_members (
    id BIGINT PRIMARY KEY,
    group_id BIGINT FOREIGN KEY,
    user_id BIGINT FOREIGN KEY,
    role ENUM('Leader', 'Treasurer', 'Secretary', 'Member') DEFAULT 'Member',
    joined_at DATE,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(group_id, user_id)
);
```

---

### Module 2: Training & Field Activity Management

**Purpose:** Digitize FFS pedagogical process (AESA, GAP, training sessions)

#### Features

1. **AESA (Agro-Ecosystem Analysis) Tracker**
   - Digital forms for field observations
   - Record: Pests, diseases, soil moisture, plant health
   - Photo attachments (multiple per observation)
   - GPS-tagged observation points
   - Offline data capture

2. **Training Session Manager**
   - Schedule sessions (date, time, topic, facilitator)
   - Digital attendance log (gender-disaggregated)
   - Record outcomes and facilitator notes
   - Track session completion vs. planned curriculum

3. **Training Content Library**
   - Repository of guides, videos, images
   - Organized by topic (AESA, GAP, Climate-Smart practices)
   - Downloadable for offline access
   - Multi-language support (English, Karimojong, Ateso)

#### Database Schema

```sql
-- AESA Observations
CREATE TABLE aesa_observations (
    id BIGINT PRIMARY KEY,
    group_id BIGINT FOREIGN KEY,
    facilitator_id BIGINT FOREIGN KEY REFERENCES users(id),
    observation_date DATE,
    plot_location VARCHAR(255),
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    pest_disease_notes TEXT,
    soil_moisture_notes TEXT,
    plant_health_notes TEXT,
    photos JSON, -- Array of image URLs
    weather_conditions VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Training Sessions
CREATE TABLE training_sessions (
    id BIGINT PRIMARY KEY,
    group_id BIGINT FOREIGN KEY,
    training_topic_id BIGINT FOREIGN KEY,
    session_date DATE,
    start_time TIME,
    end_time TIME,
    facilitator_id BIGINT FOREIGN KEY REFERENCES users(id),
    planned_attendance INT,
    actual_attendance INT,
    notes TEXT,
    status ENUM('Planned', 'Completed', 'Cancelled') DEFAULT 'Planned',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Training Attendance (reuse existing Participants table)
CREATE TABLE participants (
    id BIGINT PRIMARY KEY,
    training_session_id BIGINT FOREIGN KEY,
    user_id BIGINT FOREIGN KEY,
    attendance_status ENUM('Present', 'Absent', 'Late') DEFAULT 'Present',
    created_at TIMESTAMP
);
```

---

### Module 3: Financial Inclusion (VSLA Digital Ledger)

**Purpose:** Digitize VSLA savings, loans, and financial management

#### Features

1. **Savings Cycle Management**
   - Define cycles (start/end date, share price)
   - Track total group savings
   - Member share purchase records
   - Social fund contributions

2. **Loan Management**
   - Process loan applications
   - Record disbursements and terms
   - Track repayments (principal + interest)
   - Generate repayment schedules
   - Overdue loan alerts

3. **Financial Reporting**
   - Group fund summary (total savings, loans outstanding)
   - Member account statements
   - Loan book (all active loans)
   - Interest income tracking
   - Disbursement readiness calculator

#### Database Schema

```sql
-- VSLA Cycles
CREATE TABLE vsla_cycles (
    id BIGINT PRIMARY KEY,
    group_id BIGINT FOREIGN KEY,
    cycle_name VARCHAR(255), -- e.g., "2025 Planting Season"
    start_date DATE,
    end_date DATE,
    share_price DECIMAL(10, 2),
    status ENUM('Active', 'Closed') DEFAULT 'Active',
    total_savings DECIMAL(12, 2) DEFAULT 0,
    total_loans_outstanding DECIMAL(12, 2) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- VSLA Transactions (extend existing AccountTransaction)
CREATE TABLE account_transactions (
    id BIGINT PRIMARY KEY,
    vsla_cycle_id BIGINT FOREIGN KEY,
    user_id BIGINT FOREIGN KEY, -- Member
    transaction_type ENUM('SHARE_PURCHASE', 'LOAN_DISBURSEMENT', 'LOAN_REPAYMENT', 'INTEREST_PAYMENT', 'FINE'),
    amount DECIMAL(10, 2),
    share_value DECIMAL(10, 2) NULL,
    shares_count INT NULL,
    transaction_date DATE,
    description TEXT,
    recorded_by_id BIGINT FOREIGN KEY REFERENCES users(id), -- Treasurer
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- VSLA Loans
CREATE TABLE vsla_loans (
    id BIGINT PRIMARY KEY,
    vsla_cycle_id BIGINT FOREIGN KEY,
    borrower_id BIGINT FOREIGN KEY REFERENCES users(id),
    principal_amount DECIMAL(10, 2),
    interest_rate DECIMAL(5, 2), -- Percentage per cycle
    disbursement_date DATE,
    due_date DATE,
    total_amount_due DECIMAL(10, 2), -- Principal + Interest
    amount_repaid DECIMAL(10, 2) DEFAULT 0,
    status ENUM('ACTIVE', 'REPAID', 'OVERDUE', 'WRITTEN_OFF') DEFAULT 'ACTIVE',
    approved_by BIGINT FOREIGN KEY REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Integration with Mobile Money

- API connectors for MTN, Airtel (Uganda)
- Record mobile money reference numbers
- Link payments to VSLA transactions

---

### Module 4: Advisory Hub & E-Learning

**Purpose:** Deliver timely, localized agricultural knowledge to farmers

#### Features

1. **Content Management System**
   - Create/publish articles, audio, video, infographics
   - Tag by topic (pest control, post-harvest, nutrition)
   - Tag by value chain (sorghum, cassava, livestock)
   - Tag by season (planting, growing, harvest)

2. **Multi-Channel Delivery**
   - **Mobile App:** In-app library with search
   - **IVR (Interactive Voice Response):** Toll-free number, menu-driven audio content
   - **USSD:** *123# style text menus for feature phones
   - **SMS:** Broadcast alerts (weather, pest outbreaks, market prices)

3. **E-Learning Modules**
   - Structured courses (e.g., "Climate-Smart Agriculture 101")
   - Interactive lessons with quizzes
   - Track farmer progress and completion
   - Issue digital certificates

#### Database Schema

```sql
-- Advisory Content (extend existing NewsPost)
CREATE TABLE advisory_contents (
    id BIGINT PRIMARY KEY,
    title VARCHAR(500),
    body TEXT,
    content_type ENUM('ARTICLE', 'AUDIO', 'VIDEO', 'INFOGRAPHIC') DEFAULT 'ARTICLE',
    audio_file_url VARCHAR(500) NULL,
    video_file_url VARCHAR(500) NULL,
    thumbnail_url VARCHAR(500),
    value_chain_id BIGINT FOREIGN KEY,
    season_tag ENUM('PLANTING', 'GROWING', 'HARVEST', 'POST_HARVEST', 'ALL_SEASON'),
    delivery_channel JSON, -- ["APP", "IVR", "USSD", "SMS"]
    available_offline BOOLEAN DEFAULT FALSE,
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- E-Learning Courses
CREATE TABLE courses (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    value_chain_id BIGINT FOREIGN KEY,
    duration_minutes INT,
    thumbnail VARCHAR(500),
    difficulty ENUM('BEGINNER', 'INTERMEDIATE', 'ADVANCED') DEFAULT 'BEGINNER',
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Course Modules (Lessons)
CREATE TABLE course_modules (
    id BIGINT PRIMARY KEY,
    course_id BIGINT FOREIGN KEY,
    title VARCHAR(255),
    content TEXT,
    video_url VARCHAR(500) NULL,
    order INT DEFAULT 0,
    quiz_questions JSON, -- [{"question": "...", "options": [...], "correct_answer": "..."}]
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- User Course Progress
CREATE TABLE user_course_progress (
    id BIGINT PRIMARY KEY,
    user_id BIGINT FOREIGN KEY,
    course_id BIGINT FOREIGN KEY,
    current_module_id BIGINT FOREIGN KEY REFERENCES course_modules(id),
    progress_percentage INT DEFAULT 0,
    quiz_score INT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(user_id, course_id)
);
```

---

### Module 5: Market Linkages (E-Marketplace)

**Purpose:** Connect farmers to input suppliers, equipment providers, and buyers

**Note:** This is NOT a full e-commerce platform. Focus on **information and linkages**, not transactions.

#### Features

1. **Service Provider Directory**
   - List agri-input dealers (seeds, fertilizers)
   - List equipment providers (tractors, sprayers)
   - List commodity buyers (cooperatives, traders)
   - Contact info, location, certifications

2. **Commodity Price Information**
   - Current market prices from nearby markets
   - Price trends (weekly/monthly)
   - Compare prices across markets

3. **Produce & Needs Bulletin Board**
   - Groups post available produce (e.g., "500kg sorghum available")
   - Groups post input needs (e.g., "Need 50kg drought-tolerant seeds")
   - Service providers can respond

#### Database Schema

```sql
-- Service Providers (extend existing)
CREATE TABLE service_providers (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    provider_type ENUM('AGRI_INPUT', 'EQUIPMENT', 'BUYER', 'TRANSPORTER'),
    contact_phone VARCHAR(20),
    location_id BIGINT FOREIGN KEY,
    certification VARCHAR(255) NULL, -- e.g., "Certified Seed Dealer"
    value_chains_served JSON, -- ["Sorghum", "Cassava"]
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Commodity Market Prices
CREATE TABLE commodity_prices (
    id BIGINT PRIMARY KEY,
    commodity_name VARCHAR(255), -- e.g., "Sorghum (per kg)"
    market_location_id BIGINT FOREIGN KEY REFERENCES locations(id),
    price_ugx DECIMAL(10, 2),
    price_date DATE,
    source VARCHAR(255), -- e.g., "MAAIF", "District Production Office"
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Group Produce Listings
CREATE TABLE produce_listings (
    id BIGINT PRIMARY KEY,
    group_id BIGINT FOREIGN KEY,
    commodity_name VARCHAR(255),
    quantity_kg DECIMAL(10, 2),
    asking_price_per_kg DECIMAL(10, 2),
    available_from DATE,
    available_until DATE NULL,
    status ENUM('AVAILABLE', 'SOLD', 'EXPIRED') DEFAULT 'AVAILABLE',
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Group Input Requests
CREATE TABLE input_requests (
    id BIGINT PRIMARY KEY,
    group_id BIGINT FOREIGN KEY,
    input_type VARCHAR(255), -- e.g., "Seeds", "Fertilizer"
    quantity_needed DECIMAL(10, 2),
    unit VARCHAR(50), -- e.g., "kg", "bags"
    needed_by DATE,
    status ENUM('OPEN', 'FULFILLED', 'CANCELLED') DEFAULT 'OPEN',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

### Module 6: MEL Dashboard (Monitoring, Evaluation & Learning)

**Purpose:** Real-time visualization of project indicators for adaptive management

#### Features

1. **Key Performance Indicators (KPIs)**
   - Total FFS/FBS/VSLA groups (by district, value chain, status)
   - Total members (disaggregated by gender, age)
   - Training sessions conducted vs. planned
   - VSLA savings mobilized (total, average per group)
   - Loans disbursed and repayment rates
   - Advisory content accessed (by channel)
   - Farmer course completion rates

2. **Interactive Visualizations**
   - Line charts (trends over time)
   - Bar charts (comparisons by district/value chain)
   - Pie charts (gender ratios, group types)
   - Geographic maps (group distribution, activity hotspots)

3. **Advanced Filtering**
   - By date range (daily, weekly, monthly, custom)
   - By location (district, sub-county, parish)
   - By group type (FFS, FBS, VSLA)
   - By value chain
   - By gender

4. **Report Generation**
   - Pre-built templates for donor reports (EU, FAO)
   - Government reporting (MAAIF, OPM)
   - Export to Excel, PDF
   - Schedule automated email reports

#### Database Schema

```sql
-- MEL Indicators (Meta-Configuration)
CREATE TABLE mel_indicators (
    id BIGINT PRIMARY KEY,
    indicator_name VARCHAR(255), -- e.g., "Total Active FFS Groups"
    description TEXT,
    data_source VARCHAR(255), -- e.g., "groups table WHERE status='Active'"
    calculation_formula TEXT, -- e.g., "COUNT(*)"
    indicator_type ENUM('NUMERIC', 'PERCENTAGE', 'TREND') DEFAULT 'NUMERIC',
    target_value INT NULL,
    unit VARCHAR(50), -- e.g., "groups", "UGX", "%"
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- MEL Snapshots (for historical tracking)
CREATE TABLE mel_snapshots (
    id BIGINT PRIMARY KEY,
    indicator_id BIGINT FOREIGN KEY,
    value DECIMAL(15, 2),
    snapshot_date DATE,
    location_id BIGINT FOREIGN KEY NULL, -- For district-level snapshots
    created_at TIMESTAMP
);
```

#### Dashboard Implementation

Uses **Laravel Admin** with **ChartJS** extension. Example controller:

```php
// File: app/Admin/Controllers/FfsDashboardController.php
public function index()
{
    $metrics = [
        'total_ffs_groups' => Group::where('group_type', 'FFS')->where('status', 'Active')->count(),
        'total_members' => DB::table('group_members')->distinct('user_id')->count(),
        'total_vsla_savings' => VslaCycle::where('status', 'Active')->sum('total_savings'),
        'training_sessions_this_month' => TrainingSession::whereMonth('session_date', now()->month)->count(),
        'gender_breakdown' => User::selectRaw('gender, COUNT(*) as count')->groupBy('gender')->pluck('count', 'gender'),
    ];
    
    return view('admin.ffs_dashboard', compact('metrics'));
}
```

---

## 4. Offline-First Architecture

### Challenge

Karamoja has **limited connectivity**:
- <50% internet access
- Intermittent power
- Remote field locations

### Solution: Hybrid Online-Offline System

#### Mobile App (Flutter)
- **Local Database:** SQLite/Hive for offline data storage
- **Sync Queue:** Store create/update operations when offline
- **Conflict Resolution:** Timestamp-based (last-write-wins) or manual review

#### Backend API
- **Sync Endpoints:**
  ```
  POST /api/sync/upload     - Upload offline changes
  GET  /api/sync/download   - Download updates since last sync
  POST /api/sync/resolve    - Manual conflict resolution
  ```
- **Change Tracking:** Track `updated_at` timestamps
- **Batch Processing:** Accept arrays of records for efficiency

#### Workflow Example

1. **Offline Recording:**
   - Facilitator logs training session on tablet (no internet)
   - Data saved to local SQLite database
   - UI shows "Pending Sync" badge

2. **Connectivity Restored:**
   - App detects internet
   - Triggers sync: uploads queued records
   - Downloads updates from server
   - Resolves conflicts (if any)
   - UI shows "Synced ✓"

3. **Conflict Handling:**
   - Two facilitators edit same group offline
   - Server detects conflict (different `updated_at`)
   - Admin reviews and chooses correct version

---

## 5. Security & Data Protection

### Compliance Requirements

- **Uganda Data Protection and Privacy Act (2019)**
- **FAO Data Governance Standards**
- **EU GDPR (donor requirement)**

### Security Measures

1. **Authentication**
   - JWT tokens with expiration (7 days)
   - Password hashing (bcrypt)
   - Two-factor authentication (SMS OTP) for admin users

2. **Data Encryption**
   - **In Transit:** HTTPS/TLS 1.3
   - **At Rest:** Database encryption (MySQL AES-256)
   - **Mobile Device:** Encrypted SQLite databases

3. **Role-Based Access Control (RBAC)**
   - Granular permissions per module
   - Group-level data isolation (facilitators only see their groups)
   - Audit logs for sensitive operations

4. **Informed Consent**
   - Digital consent capture during member registration
   - Explain data usage in local languages
   - Option to withdraw consent

5. **Audit Trail**
   - Log all data modifications (who, what, when, IP)
   - Immutable audit log (append-only table)
   - Retention: 7 years

6. **Device Security**
   - Password-protected tablets
   - Remote wipe capability (via MDM)
   - Automatic logout after 15 minutes inactivity

---

## 6. Implementation Roadmap

### Phase 1: Inception & Planning (Month 1 - Weeks 1-2)

**Activities:**
- Kickoff meeting with FAO, IPs, district officials
- User requirements validation workshops
- Finalize technical specifications
- Set up development environment
- Database schema design

**Deliverable:** Inception Report with detailed workplan

---

### Phase 2: System Design & Development (Month 1-2 - Weeks 3-10)

**Month 1:**
- Week 3-4: Core infrastructure (auth, RBAC, location data)
- Week 4-5: Module 1 (Group Registry) + Module 6 (Basic Dashboard)

**Month 2:**
- Week 6-7: Module 2 (Training) + Module 3 (VSLA Ledger)
- Week 8: Module 4 (Advisory Hub) + Module 5 (E-Marketplace)
- Week 9-10: Offline sync engine, mobile app integration

**Deliverable:** Functional MIS Prototype (MVP)

---

### Phase 3: Testing & Capacity Building (Month 3 - Weeks 11-14)

**Activities:**
- User Acceptance Testing (UAT) with 2-3 pilot groups
- Bug fixing and refinements
- Prepare training materials (manuals, videos, job aids)
- Conduct Training of Trainers (ToT) with IP staff and facilitators

**Deliverable:**
- Training Toolkit (facilitator guides, user manuals in English & Karimojong)
- ToT Completion Report

---

### Phase 4: Field Deployment (Month 4 - Weeks 15-18)

**Activities:**
- Procure and configure rugged tablets (30-50 devices)
- Install mobile app and preload content
- Distribute devices to field facilitators
- Onboard pilot FFS/VSLA groups
- Provide on-site support

**Deliverable:** Deployment Report with device inventory

---

### Phase 5: Monitoring & Refinement (Month 5 - Weeks 19-22)

**Activities:**
- Weekly check-ins with facilitators
- Monitor system usage and data quality
- Address technical issues
- Incorporate user feedback
- Scale to remaining groups

**Deliverable:** Mid-Term Progress Report

---

### Phase 6: Handover & Sustainability (Month 6 - Weeks 23-26)

**Activities:**
- Final system testing and optimization
- Complete technical documentation (API docs, architecture diagrams)
- Transfer source code to FAO GitHub repository
- Establish helpdesk and maintenance protocols
- Final training refreshers
- Project closure meeting

**Deliverable:**
- Complete source code and technical documentation
- Handover certificate
- Final Project Report

---

## 7. Budget & Resources

### Team Composition

| Role | FTE | Duration | Responsibilities |
|------|-----|----------|------------------|
| **Team Leader / Solution Architect** | 0.5 | 6 months | Overall design, stakeholder management, quality assurance |
| **Senior Backend Developer** | 1.0 | 5 months | Laravel API, database, admin panel |
| **Mobile App Developer (Flutter)** | 1.0 | 4 months | Offline-first mobile app, sync engine |
| **UI/UX Designer** | 0.3 | 2 months | User interface design, training materials |
| **DevOps Engineer** | 0.2 | 6 months | Server setup, deployment, security |
| **Training Specialist** | 0.3 | 2 months | ToT curriculum, capacity building |

**Total Effort:** ~18 person-months

### Infrastructure Requirements

1. **Servers:**
   - Production server (VPS: 8GB RAM, 4 vCPU, 200GB SSD)
   - Staging server (for testing)
   - Database backup server

2. **Domain & SSL:**
   - Domain registration: ffsmis.fao.org
   - SSL certificate (Let's Encrypt or commercial)

3. **Third-Party Services:**
   - SMS gateway (Uganda telco APIs)
   - OneSignal (push notifications)
   - IVR service provider (e.g., Africa's Talking)

4. **Field Devices:**
   - 40 rugged Android tablets (10-inch, 4GB RAM, 64GB storage)
   - Solar charging kits (for remote areas)

---

## 8. Success Metrics

### Technical Success Criteria

- [ ] 99% uptime during working hours (8am-6pm EAT)
- [ ] <3 seconds page load time (on 3G connection)
- [ ] 100% offline functionality for core features
- [ ] Zero critical security vulnerabilities
- [ ] Data sync accuracy >99.5%

### Adoption Metrics (6 months post-deployment)

- [ ] 80% of field facilitators actively using the system
- [ ] 60% of VSLA groups have complete digital ledgers
- [ ] 1,000+ training sessions logged
- [ ] 5,000+ farmers with digital profiles
- [ ] 500+ AESA observations recorded

### Impact Indicators (12 months)

- [ ] 30% reduction in reporting time for IPs
- [ ] 50% improvement in data quality (completeness, accuracy)
- [ ] 70% of farmers report improved access to advisory services
- [ ] 25% increase in VSLA savings mobilization

---

## 9. Risk Management

| Risk | Likelihood | Impact | Mitigation Strategy |
|------|-----------|--------|---------------------|
| **Low Digital Literacy** | High | High | Simplified UI, visual icons, peer-led training, youth digital champions |
| **Poor Connectivity** | High | High | Offline-first design, opportunistic sync, SMS fallback |
| **Device Theft/Damage** | Medium | Medium | Device insurance, remote wipe, rugged hardware, community agreements |
| **Data Privacy Breach** | Low | Critical | Encryption, penetration testing, audit logs, GDPR compliance |
| **Staff Turnover** | Medium | Medium | Comprehensive documentation, modular code, knowledge transfer sessions |
| **Scope Creep** | Medium | High | Strict change control, phased delivery, prioritize MVP |

---

## 10. Sustainability Plan

### Technical Sustainability

1. **Open-Source Stack:** No vendor lock-in (Laravel, MySQL, Flutter)
2. **Local Hosting:** Option to host on Ugandan servers (e.g., Datanet, Raxio)
3. **Documentation:** Complete API docs, architecture diagrams, deployment guides
4. **Code Quality:** Unit tests, code comments, PSR-12 standards

### Institutional Sustainability

1. **Capacity Building:** ToT model trains local facilitators to train others
2. **Government Integration:** Align with MAAIF e-extension platform
3. **Community Ownership:** VSLA groups manage their own data
4. **Local IT Support:** Train district ICT officers as system administrators

### Financial Sustainability

1. **Low Operating Costs:** ~USD 500/month (server, SMS, maintenance)
2. **Grant Funding:** Eligible for USAID, EU, World Bank digital agriculture grants
3. **Government Budget:** Advocate for inclusion in MAAIF annual budget
4. **Cost Recovery:** Minimal fees for premium features (e.g., advanced analytics)

---

## 11. Next Steps

### For Implementation Team

1. **Environment Setup**
   - [ ] Clone existing codebase (DTEHM base)
   - [ ] Create fresh database: `fao_ffs_mis`
   - [ ] Update .env configuration
   - [ ] Run migrations for new FFS tables

2. **Documentation Review**
   - [ ] Read `FAO_FFS_MODULE_MAPPING_ANALYSIS.md`
   - [ ] Study RFP requirements document
   - [ ] Review Karamoja district data

3. **Technical Planning**
   - [ ] Draft database schema (ERD)
   - [ ] Design API endpoints (Postman collection)
   - [ ] Sketch mobile app wireframes
   - [ ] Plan offline sync algorithm

### For FAO/Client

1. **Approvals**
   - [ ] Approve technical specifications
   - [ ] Confirm budget allocation
   - [ ] Sign contract and NDA

2. **Data Preparation**
   - [ ] Provide list of districts, sub-counties, parishes
   - [ ] Share existing FFS group data (if any)
   - [ ] Provide value chain priorities (crops/livestock)

3. **Stakeholder Engagement**
   - [ ] Schedule kickoff meeting
   - [ ] Introduce implementing partners
   - [ ] Coordinate with district officials

---

**Document End**

*For questions or clarifications, contact:*  
**FAO Uganda:** [Contact details from RFP]  
**Development Team Lead:** [To be assigned]
