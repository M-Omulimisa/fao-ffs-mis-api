# Enterprise & Production Protocol System - Complete Implementation

## Overview
A comprehensive system for managing farming ventures (Enterprises) and their activity blueprints (Production Protocols). Farmers can select an enterprise type (livestock or crop) and follow a structured plan with defined activities to successfully run their farming venture.

## Database Structure

### 1. Enterprises Table
```sql
CREATE TABLE enterprises (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE,
    description TEXT,
    type ENUM('livestock', 'crop'),
    duration INT COMMENT 'Duration in months',
    photo VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_by_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

**Indexes:**
- `name` - For search optimization
- `type` - For filtering by enterprise type
- `is_active` - For filtering active/inactive enterprises

### 2. Production Protocols Table
```sql
CREATE TABLE production_protocols (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    enterprise_id BIGINT,
    activity_name VARCHAR(255),
    activity_description TEXT,
    start_time INT COMMENT 'Start time in weeks',
    end_time INT COMMENT 'End time in weeks',
    is_compulsory BOOLEAN DEFAULT TRUE,
    photo VARCHAR(255),
    order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_by_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE CASCADE
);
```

**Indexes:**
- `enterprise_id` - For foreign key lookups
- `(enterprise_id, start_time)` - For timeline queries
- `(enterprise_id, is_compulsory)` - For filtering mandatory activities
- `is_active` - For filtering active protocols

## Models

### Enterprise Model
**Location:** `app/Models/Enterprise.php`

**Key Features:**
- Soft deletes enabled
- Automatic validation on create/update
- Photo URL generation with storage path
- Human-readable duration conversion
- Cascade delete of related protocols

**Relationships:**
- `productionProtocols()` - All protocols for this enterprise
- `activeProtocols()` - Only active protocols, ordered
- `compulsoryProtocols()` - Only mandatory protocols
- `creator()` - User who created the enterprise

**Computed Attributes:**
- `type_text` - Capitalized type name
- `duration_text` - Human-readable duration (e.g., "1 year 3 months")
- `photo_url` - Full URL to photo
- `total_protocols` - Count of all protocols
- `compulsory_protocols_count` - Count of mandatory protocols

**Scopes:**
- `ofType($type)` - Filter by livestock/crop
- `active()` - Only active enterprises

**Validation Rules:**
- name: required, unique, max 255 chars
- type: required, must be 'livestock' or 'crop'
- duration: required, integer, 1-120 months
- description: optional, string
- photo: optional, string

### ProductionProtocol Model
**Location:** `app/Models/ProductionProtocol.php`

**Key Features:**
- Soft deletes enabled
- Automatic validation on create/update
- Validates timing constraints (end >= start)
- Validates against enterprise duration
- Photo URL generation

**Relationships:**
- `enterprise()` - Parent enterprise
- `creator()` - User who created the protocol

**Computed Attributes:**
- `duration_weeks` - Calculated weeks duration
- `duration_text` - Human-readable duration
- `start_time_text` - "Week X"
- `end_time_text` - "Week Y"
- `compulsory_text` - "Mandatory" or "Optional"
- `photo_url` - Full URL to photo

**Scopes:**
- `forEnterprise($id)` - Protocols for specific enterprise
- `compulsory()` - Only mandatory protocols
- `optional()` - Only optional protocols
- `active()` - Only active protocols
- `orderByTime($direction)` - Sort by start time

**Methods:**
- `overlapsWith($other)` - Check if protocols overlap
- `getOverlappingProtocols()` - Find overlapping protocols

**Validation Rules:**
- enterprise_id: required, must exist in enterprises
- activity_name: required, max 255 chars
- activity_description: optional, string
- start_time: required, integer, >= 0
- end_time: required, integer, >= start_time, <= enterprise duration
- is_compulsory: boolean
- photo: optional, string
- order: integer, >= 0

## Laravel-Admin Controllers

### EnterpriseController
**Location:** `app/Admin/Controllers/EnterpriseController.php`

**Grid Features:**
- Columns: Photo, Name, Type (labeled), Duration (formatted), Protocol count, Status (switch), Created date
- Filters: Name search, Type select, Status select
- Sortable columns: ID, Name, Type, Created date
- Inline status toggle

**Form Features:**
- Name input with validation
- Type dropdown (Livestock/Crop)
- Duration number input (1-120 months)
- Photo upload to 'enterprises' folder
- Description textarea
- Active status switch
- Auto-populate created_by_id

**Show Features:**
- All enterprise details
- Related protocols table with columns: Activity, Start, End, Duration, Type, Active

### ProductionProtocolController
**Location:** `app/Admin/Controllers/ProductionProtocolController.php`

**Grid Features:**
- Columns: ID, Enterprise, Activity, Start Week, End Week, Duration, Type (Mandatory/Optional), Order (editable), Status (switch)
- Filters: Enterprise dropdown, Activity name search, Type select, Status select, Start week range
- Sortable columns: ID, Enterprise, Activity, Start time, End time, Order
- Inline order editing
- Color-coded labels for types

**Form Features:**
- Enterprise dropdown (only active enterprises)
- Activity name input
- Activity description textarea
- Start week number input
- End week number input
- Mandatory switch
- Display order input
- Photo upload to 'protocols' folder
- Active status switch
- Validation: end >= start, end <= enterprise duration

## API Endpoints

### Enterprise Endpoints

#### GET `/api/enterprises`
**Description:** Get all enterprises with filters
**Query Parameters:**
- `type` - Filter by 'livestock' or 'crop'
- `is_active` - Filter by active status (default: true)
- `search` - Search by name
- `sort_by` - Sort field (default: 'created_at')
- `sort_order` - Sort direction (default: 'desc')
- `per_page` - Items per page (default: 20)

**Response:**
```json
{
    "code": 1,
    "message": "Enterprises retrieved successfully",
    "data": [...],
    "pagination": {
        "total": 50,
        "per_page": 20,
        "current_page": 1,
        "last_page": 3
    }
}
```

#### GET `/api/enterprises/{id}`
**Description:** Get single enterprise with all active protocols
**Response:**
```json
{
    "code": 1,
    "message": "Enterprise retrieved successfully",
    "data": {
        "id": 1,
        "name": "Dairy Farming",
        "type": "livestock",
        "duration": 12,
        "duration_text": "1 year",
        "photo_url": "https://...",
        "production_protocols": [...]
    }
}
```

#### POST `/api/enterprises`
**Description:** Create new enterprise (requires authentication)
**Headers:** `Authorization: Bearer {token}`
**Body:**
```json
{
    "name": "Poultry Farming",
    "type": "livestock",
    "duration": 6,
    "description": "Commercial egg production",
    "photo": "path/to/photo.jpg",
    "is_active": true
}
```

#### PUT `/api/enterprises/{id}`
**Description:** Update enterprise (requires authentication)
**Headers:** `Authorization: Bearer {token}`

#### DELETE `/api/enterprises/{id}`
**Description:** Delete enterprise (requires authentication)
**Headers:** `Authorization: Bearer {token}`

#### GET `/api/enterprises/statistics`
**Description:** Get enterprise statistics
**Response:**
```json
{
    "code": 1,
    "message": "Statistics retrieved successfully",
    "data": {
        "total_enterprises": 25,
        "active_enterprises": 22,
        "livestock_enterprises": 12,
        "crop_enterprises": 13,
        "total_protocols": 150,
        "by_type": {
            "livestock": {"count": 12, "active": 10},
            "crop": {"count": 13, "active": 12}
        }
    }
}
```

### Production Protocol Endpoints

#### GET `/api/production-protocols`
**Description:** Get all protocols with filters
**Query Parameters:**
- `enterprise_id` - Filter by enterprise
- `is_compulsory` - Filter by mandatory/optional
- `is_active` - Filter by active status (default: true)
- `search` - Search by activity name
- `sort_by` - Sort field (default: 'start_time')
- `sort_order` - Sort direction (default: 'asc')
- `per_page` - Items per page (default: 50)

#### GET `/api/production-protocols/enterprise/{enterpriseId}`
**Description:** Get all protocols for specific enterprise with summary
**Response:**
```json
{
    "code": 1,
    "message": "Production protocols retrieved successfully",
    "data": {
        "enterprise": {...},
        "protocols": [...],
        "summary": {
            "total_protocols": 10,
            "compulsory": 7,
            "optional": 3
        }
    }
}
```

#### GET `/api/production-protocols/timeline/{enterpriseId}`
**Description:** Get timeline view of protocols organized by weeks
**Response:**
```json
{
    "code": 1,
    "message": "Timeline retrieved successfully",
    "data": {
        "enterprise": {...},
        "timeline": [
            {
                "week": 0,
                "activities": [...]
            },
            {
                "week": 4,
                "activities": [...]
            }
        ],
        "summary": {
            "total_weeks": 48,
            "active_weeks": 32,
            "total_protocols": 12
        }
    }
}
```

#### GET `/api/production-protocols/{id}`
**Description:** Get single protocol with enterprise details

#### POST `/api/production-protocols`
**Description:** Create new protocol (requires authentication)
**Headers:** `Authorization: Bearer {token}`
**Body:**
```json
{
    "enterprise_id": 1,
    "activity_name": "Vaccination",
    "activity_description": "Administer required vaccines",
    "start_time": 2,
    "end_time": 4,
    "is_compulsory": true,
    "photo": "path/to/photo.jpg",
    "order": 10
}
```

#### PUT `/api/production-protocols/{id}`
**Description:** Update protocol (requires authentication)

#### DELETE `/api/production-protocols/{id}`
**Description:** Delete protocol (requires authentication)

## Usage Examples

### Example 1: Creating a Livestock Enterprise

**Step 1:** Create the enterprise
```json
POST /api/enterprises
{
    "name": "Dairy Cattle Production",
    "type": "livestock",
    "duration": 24,
    "description": "Commercial dairy production with high-yield breeds"
}
```

**Step 2:** Add production protocols
```json
POST /api/production-protocols
{
    "enterprise_id": 1,
    "activity_name": "Breed Selection & Purchase",
    "activity_description": "Select and purchase high-quality dairy breeds",
    "start_time": 0,
    "end_time": 2,
    "is_compulsory": true,
    "order": 1
}

POST /api/production-protocols
{
    "enterprise_id": 1,
    "activity_name": "Housing Construction",
    "activity_description": "Build appropriate cattle housing with proper ventilation",
    "start_time": 0,
    "end_time": 4,
    "is_compulsory": true,
    "order": 2
}

POST /api/production-protocols
{
    "enterprise_id": 1,
    "activity_name": "Initial Vaccination",
    "activity_description": "Administer all required vaccines",
    "start_time": 2,
    "end_time": 4,
    "is_compulsory": true,
    "order": 3
}
```

### Example 2: Querying Crop Enterprises

```http
GET /api/enterprises?type=crop&is_active=true
```

### Example 3: Getting Enterprise Timeline

```http
GET /api/production-protocols/timeline/1
```

This returns a week-by-week breakdown of all activities.

## Validation Rules Summary

### Enterprise Validation
- Name must be unique
- Type must be 'livestock' or 'crop'
- Duration must be 1-120 months
- All validations enforced at model and controller level

### Production Protocol Validation
- Enterprise must exist and be valid
- Start time must be >= 0
- End time must be >= start time
- End time cannot exceed enterprise duration (months * 4 weeks)
- Order must be >= 0

## Admin Panel Access

### Enterprises
**URL:** `/admin/enterprises`
- List all enterprises
- Create new enterprise
- Edit existing enterprise
- Toggle active status
- View related protocols

### Production Protocols
**URL:** `/admin/production-protocols`
- List all protocols
- Filter by enterprise
- Create new protocol
- Edit existing protocol
- Inline edit display order
- Toggle active status

## File Uploads

### Enterprise Photos
**Storage:** `storage/app/public/enterprises/`
**Access URL:** `https://your-domain.com/storage/enterprises/filename.jpg`

### Protocol Photos
**Storage:** `storage/app/public/protocols/`
**Access URL:** `https://your-domain.com/storage/protocols/filename.jpg`

## Database Migrations

**Run migrations:**
```bash
php artisan migrate
```

This will create:
1. `enterprises` table
2. `production_protocols` table (with foreign key to enterprises)

## Testing Checklist

- [ ] Create enterprise via API
- [ ] Create enterprise via admin panel
- [ ] Add protocols to enterprise
- [ ] Validate time constraints (end >= start)
- [ ] Validate duration constraints (protocols within enterprise duration)
- [ ] Test photo uploads
- [ ] Test filtering (by type, status, etc.)
- [ ] Test timeline endpoint
- [ ] Test statistics endpoint
- [ ] Test soft deletes
- [ ] Test cascade delete (enterprise → protocols)
- [ ] Test search functionality
- [ ] Test sorting
- [ ] Test pagination

## Future Enhancements

1. **Farmer Enterprise Tracking**
   - Track farmers' adopted enterprises
   - Monitor activity completion
   - Send reminders for upcoming activities

2. **Resource Requirements**
   - Add resource lists per protocol (tools, materials, costs)
   - Budget estimation

3. **Seasonal Adjustments**
   - Season-specific protocols
   - Weather-based recommendations

4. **Success Metrics**
   - Track completion rates
   - Yield/production data
   - ROI calculations

5. **Mobile App Integration**
   - Browse enterprises
   - Start enterprise
   - Track progress
   - Get notifications

## Notes

- All timestamps are in UTC
- Soft deletes enabled for data recovery
- Photo uploads use Laravel's storage system
- All API responses follow consistent format with `code`, `message`, `data`
- Authentication required for create/update/delete operations
- Read operations are public (can be restricted as needed)
