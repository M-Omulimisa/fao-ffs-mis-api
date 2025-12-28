# Farm Module - Complete Implementation Plan

## Overview
The Farm module represents real-world instances of Enterprises, allowing farmers to track and manage production activities based on enterprise protocols.

## Core Concepts

### 1. Farm
- **Definition**: A real instance of an Enterprise in practice
- **Owner**: Farmer (User)
- **Purpose**: Track actual farming operations based on enterprise protocols
- **Lifecycle**: Planning → Active → Completed/Abandoned

### 2. Farm Activity
- **Definition**: Scheduled task that farmer needs to complete
- **Types**: 
  - Auto-generated (from enterprise protocols)
  - Manual (created by farmer)
- **Tracking**: Scheduled date, actual date, completion status, photos, notes, scores

---

## Database Schema

### Table: `farms`
```sql
- id (bigint, PK)
- enterprise_id (FK → enterprises)
- user_id (FK → users) - farmer
- name (string)
- description (text, nullable)
- status (enum: planning, active, completed, abandoned)
- start_date (date)
- expected_end_date (date, calculated from enterprise duration)
- actual_end_date (date, nullable)
- gps_latitude (decimal, nullable)
- gps_longitude (decimal, nullable)
- location_text (string, nullable)
- photo (string, nullable)
- overall_score (decimal, default 0)
- completed_activities_count (int, default 0)
- total_activities_count (int, default 0)
- is_active (boolean, default true)
- created_at (timestamp)
- updated_at (timestamp)
```

### Table: `farm_activities`
```sql
- id (bigint, PK)
- farm_id (FK → farms)
- production_protocol_id (FK → production_protocols, nullable)
- activity_name (string)
- activity_description (text, nullable)
- scheduled_date (date)
- scheduled_week (int) - week number from start
- actual_completion_date (date, nullable)
- status (enum: pending, done, skipped, overdue)
- is_mandatory (boolean, default false)
- weight (int, 1-5) - importance from protocol
- target_value (decimal, nullable)
- actual_value (decimal, nullable)
- score (decimal, default 0)
- notes (text, nullable)
- photo (string, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

---

## Features & Functionality

### Farm Management
1. **Create Farm**
   - Select enterprise
   - Enter farm details (name, location, GPS)
   - Set start date
   - Auto-generate activities from protocols
   - Upload farm photo

2. **View Farms**
   - List all farms (active, completed)
   - Filter by status, enterprise type
   - Sort by date, progress
   - Search by name

3. **Farm Details**
   - Overview stats (progress, score, activities)
   - Location on map
   - Timeline/calendar view
   - Activity list

### Farm Activity Management
1. **Auto-generation**
   - Create activities from enterprise protocols
   - Calculate scheduled dates based on farm start date
   - Inherit weight/importance from protocols

2. **Activity Completion**
   - Mark as done/skipped
   - Record actual completion date
   - Add notes and photos
   - Enter actual values achieved
   - Calculate score

3. **Activity Views**
   - **List View**: All activities sorted by date/status
   - **Calendar View**: Monthly calendar with activities
   - **Filter**: By status, date range, weight
   - **Sort**: By date, priority, status

4. **Activity Status**
   - **Pending**: Not yet done, not overdue
   - **Overdue**: Pending past scheduled date
   - **Done**: Completed on time
   - **Skipped**: Farmer chose to skip

### Scoring System
```
Activity Score = Base Points × Time Factor × Completion Factor

Base Points:
- Weight 5 (Critical): 100 points
- Weight 4 (Very High): 80 points
- Weight 3 (High): 60 points
- Weight 2 (Medium): 40 points
- Weight 1 (Normal): 20 points

Time Factor:
- Done early (before scheduled): 1.1x
- Done on time (±2 days): 1.0x
- Done late (3-7 days): 0.8x
- Done very late (8-14 days): 0.6x
- Done extremely late (>14 days): 0.4x

Completion Factor:
- Done: 1.0x
- Skipped (non-mandatory): 0.0x
- Skipped (mandatory): -0.5x (penalty)

Farm Overall Score = Sum(Activity Scores) / Total Possible Points × 100
```

---

## API Endpoints

### Farms
```
GET    /api/farms                    - List all farms for user
POST   /api/farms                    - Create new farm
GET    /api/farms/{id}               - Get farm details
PUT    /api/farms/{id}               - Update farm
DELETE /api/farms/{id}               - Delete farm
GET    /api/farms/{id}/activities    - Get farm activities
GET    /api/farms/{id}/calendar      - Get calendar view
GET    /api/farms/{id}/stats         - Get farm statistics
```

### Farm Activities
```
GET    /api/farm-activities                 - List activities
POST   /api/farm-activities                 - Create activity (manual)
GET    /api/farm-activities/{id}            - Get activity details
PUT    /api/farm-activities/{id}            - Update activity
DELETE /api/farm-activities/{id}            - Delete activity
POST   /api/farm-activities/{id}/complete   - Mark as done
POST   /api/farm-activities/{id}/skip       - Mark as skipped
```

---

## Mobile App UI/UX Design

### Screen Structure
```
FFS Activities Menu
├── Enterprises (existing)
│   ├── Enterprise List
│   └── Enterprise Details
│       └── Protocols List
└── My Farms (NEW)
    ├── Farms List
    ├── Create Farm
    ├── Farm Details
    │   ├── Overview Tab
    │   ├── Activities Tab (List View)
    │   └── Calendar Tab
    └── Activity Details
        └── Complete/Skip Activity
```

### Design Guidelines
- **Consistent Colors**: Use ModernTheme primary (#418FDE)
- **Status Colors**:
  - Pending: Grey (#9E9E9E)
  - Done: Green (#00A651)
  - Skipped: Orange (#F57C00)
  - Overdue: Red (#D32F2F)
- **Photos**: CachedNetworkImage for farm and activity photos
- **Cards**: Clean, bordered cards with 3px left border for priority
- **Responsive**: IntrinsicHeight, Wrap for flexible layouts
- **Icons**: Feather Icons for consistency

### Key Screens

#### 1. Farms List Screen
```
┌─────────────────────────────────────┐
│ ← My Farms               [+ Create] │
├─────────────────────────────────────┤
│ [All] [Active] [Completed]          │
├─────────────────────────────────────┤
│ ┌───────────────────────────────┐   │
│ │ [Photo] Dairy Farm 2024      │   │
│ │         Dairy Cattle Mgmt     │   │
│ │         45% Complete • 72/100 │   │
│ │         📍 Kampala            │   │
│ └───────────────────────────────┘   │
│ ┌───────────────────────────────┐   │
│ │ [Photo] Maize Production      │   │
│ │         Maize Farming         │   │
│ │         80% Complete • 12/15  │   │
│ │         📍 Mbarara           │   │
│ └───────────────────────────────┘   │
└─────────────────────────────────────┘
```

#### 2. Farm Details Screen
```
┌─────────────────────────────────────┐
│ [Farm Photo Header]                 │
│ Dairy Farm 2024                     │
├─────────────────────────────────────┤
│ [Overview] [Activities] [Calendar]  │
├─────────────────────────────────────┤
│ 📊 Progress: 45% (18/40)            │
│ ⭐ Score: 72/100                    │
│ 📅 Started: 15 Jan 2024             │
│ 📍 Location: Kampala District       │
│                                     │
│ Status Breakdown:                   │
│ ✓ Done: 18  ⏳ Pending: 20         │
│ ⊘ Skipped: 2  🔴 Overdue: 0        │
└─────────────────────────────────────┘
```

#### 3. Activities List View
```
┌─────────────────────────────────────┐
│ [🔴 Overdue] [⏳ Pending] [✓ Done] │
├─────────────────────────────────────┤
│ ▎1  Initial Vaccination            │
│ │   ⭐⭐⭐ HIGH • MANDATORY         │
│ │   📅 Jan 15, 2024                │
│ │   ✓ Done on Jan 14 (+1 day)     │
│ │   Score: 110/100                 │
├─────────────────────────────────────┤
│ ▎2  Feeding Program Setup          │
│ │   ⭐⭐ MEDIUM                    │
│ │   📅 Jan 22, 2024                │
│ │   ⏳ Pending (due in 3 days)     │
└─────────────────────────────────────┘
```

#### 4. Calendar View
```
┌─────────────────────────────────────┐
│     ← January 2024 →                │
├─────────────────────────────────────┤
│ Sun Mon Tue Wed Thu Fri Sat         │
│  1   2   3   4   5   6   7          │
│         🔵  🔵              🟢      │
│  8   9  10  11  12  13  14          │
│ 🔵      🟢  🔵              🟠      │
│ 15  16  17  18  19  20  21          │
│ 🟢  🔵      🔵  🔵              🔵  │
│                                     │
│ Legend:                             │
│ 🔵 Pending  🟢 Done  🟠 Skipped    │
│ 🔴 Overdue                          │
└─────────────────────────────────────┘
```

#### 5. Complete Activity Screen
```
┌─────────────────────────────────────┐
│ ← Initial Vaccination               │
├─────────────────────────────────────┤
│ Description:                        │
│ Administer first round of vaccines  │
│ to all livestock...                 │
│                                     │
│ Scheduled: Jan 15, 2024             │
│ Weight: ⭐⭐⭐ HIGH                 │
│ Status: MANDATORY                   │
│                                     │
│ ┌─────────────────────────────────┐ │
│ │ Completion Date:                │ │
│ │ [Select Date] Jan 14, 2024      │ │
│ │                                 │ │
│ │ Target: 50 animals              │ │
│ │ Actual: [Input] 48 animals      │ │
│ │                                 │ │
│ │ Notes:                          │ │
│ │ [Text area]                     │ │
│ │                                 │ │
│ │ Photo: [Upload/Camera]          │ │
│ │ [Preview if uploaded]           │ │
│ └─────────────────────────────────┘ │
│                                     │
│ [Mark as Done]  [Skip Activity]     │
└─────────────────────────────────────┘
```

---

## Implementation Steps

### Phase 1: Backend Foundation
1. ✅ Create migrations (farms, farm_activities)
2. ✅ Create models (Farm, FarmActivity)
3. ✅ Define relationships
4. ✅ Add seeders with dummy data

### Phase 2: Laravel Admin
1. ✅ FarmController (CRUD)
2. ✅ FarmActivityController (CRUD)
3. ✅ Add to admin menu
4. ✅ Test admin interface

### Phase 3: API Development
1. ✅ FarmController API
2. ✅ FarmActivityController API
3. ✅ Scoring logic
4. ✅ Calendar view logic
5. ✅ Test with Postman

### Phase 4: Mobile App (Flutter)
1. ✅ Farm model
2. ✅ FarmActivity model
3. ✅ FarmService (API calls)
4. ✅ Farms List Screen
5. ✅ Farm Details Screen
6. ✅ Activities List/Calendar Views
7. ✅ Complete Activity Screen
8. ✅ Create Farm Screen
9. ✅ Navigation integration

### Phase 5: Testing & Polish
1. ✅ End-to-end testing
2. ✅ Bug fixes
3. ✅ Performance optimization
4. ✅ Documentation

---

## Notifications & Reminders (Future)
- Daily reminder for activities due today
- Weekly summary of pending activities
- Overdue activity alerts
- Achievement notifications (milestones reached)

---

## Success Metrics
- **User Engagement**: Number of active farms per farmer
- **Completion Rate**: % of activities completed vs total
- **Average Score**: Mean farm score across all users
- **Timeliness**: % of activities done on/before scheduled date
- **Photo Upload**: % of activities with photos attached

---

## Timeline Estimate
- Phase 1 (Backend): 2-3 hours
- Phase 2 (Admin): 1-2 hours
- Phase 3 (API): 2-3 hours
- Phase 4 (Mobile): 4-5 hours
- Phase 5 (Testing): 1-2 hours
- **Total**: 10-15 hours

---

*Implementation Date: December 27, 2025*
