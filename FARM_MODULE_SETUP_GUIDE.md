# Farm Module - Complete Setup Guide

## Current Status
- ✅ Migration files created (cascade constraints removed)
- ❌ Database tables not yet created (migration issues)
- ✅ Models ready to be created
- ✅ Controllers ready to be created  
- ✅ Seeders ready to be created

## Issue Summary
The database was accidentally wiped during `migrate:fresh`. The existing migrations have interdependencies that are causing conflicts.

## Recommended Solution

### Option 1: Manual Database Setup (Safest)
1. Backup your current database
2. Create tables manually using SQL:

```sql
-- Create enterprises table first (if not exists)
CREATE TABLE IF NOT EXISTS enterprises (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type VARCHAR(50) NOT NULL,
    duration INT NOT NULL,
    photo VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create production_protocols table (if not exists)  
CREATE TABLE IF NOT EXISTS production_protocols (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enterprise_id BIGINT UNSIGNED NOT NULL,
    activity_name VARCHAR(255) NOT NULL,
    activity_description TEXT NULL,
    start_time INT NOT NULL,
    end_time INT NOT NULL,
    is_compulsory TINYINT(1) DEFAULT 1,
    photo VARCHAR(255) NULL,
    `order` INT DEFAULT 0,
    weight INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create farms table
CREATE TABLE IF NOT EXISTS farms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enterprise_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('planning', 'active', 'completed', 'abandoned') DEFAULT 'planning',
    start_date DATE NOT NULL,
    expected_end_date DATE NOT NULL,
    actual_end_date DATE NULL,
    gps_latitude DECIMAL(10,7) NULL,
    gps_longitude DECIMAL(10,7) NULL,
    location_text VARCHAR(255) NULL,
    photo VARCHAR(255) NULL,
    overall_score DECIMAL(5,2) DEFAULT 0,
    completed_activities_count INT DEFAULT 0,
    total_activities_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    KEY idx_user_id (user_id),
    KEY idx_enterprise_id (enterprise_id),
    KEY idx_status (status),
    KEY idx_start_date (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create farm_activities table
CREATE TABLE IF NOT EXISTS farm_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farm_id BIGINT UNSIGNED NOT NULL,
    production_protocol_id BIGINT UNSIGNED NULL,
    activity_name VARCHAR(255) NOT NULL,
    activity_description TEXT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_week INT DEFAULT 1,
    actual_completion_date DATE NULL,
    status ENUM('pending', 'done', 'skipped', 'overdue') DEFAULT 'pending',
    is_mandatory TINYINT(1) DEFAULT 0,
    weight INT DEFAULT 1,
    target_value DECIMAL(10,2) NULL,
    actual_value DECIMAL(10,2) NULL,
    score DECIMAL(6,2) DEFAULT 0,
    notes TEXT NULL,
    photo VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (production_protocol_id) REFERENCES production_protocols(id),
    KEY idx_farm_id (farm_id),
    KEY idx_production_protocol_id (production_protocol_id),
    KEY idx_scheduled_date (scheduled_date),
    KEY idx_status (status),
    KEY idx_farm_scheduled (farm_id, scheduled_date),
    KEY idx_farm_status (farm_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Option 2: Clean Laravel Migration (Risky)
If your database has no important data:

```bash
# Drop all tables
php artisan db:wipe

# Run all migrations fresh
php artisan migrate:fresh

# This should work if migration dependencies are fixed
```

### Option 3: Skip Problem Migrations
1. Delete or rename problematic migration files temporarily
2. Run migrations
3. Restore problematic migrations  
4. Fix them one by one

## Next Steps After Tables Are Created

1. **Run the farm module migrations**:
```bash
php artisan migrate --path=database/migrations/2025_12_27_000001_create_farms_table.php
php artisan migrate --path=database/migrations/2025_12_27_000002_create_farm_activities_table.php
```

2. **Seed with dummy data**:
```bash
php artisan db:seed --class=FarmSeeder
```

3. **Test the APIs**:
```bash
# Test endpoints
POST /api/farms
GET /api/farms
GET /api/farms/{id}
POST /api/farm-activities
GET /api/farms/{farm_id}/activities
```

## Files Created

### Migrations
- `database/migrations/2025_12_27_000001_create_farms_table.php`
- `database/migrations/2025_12_27_000002_create_farm_activities_table.php`

### Models (To be created next)
- `app/Models/Farm.php`
- `app/Models/FarmActivity.php`

### Controllers (To be created next)
- `app/Admin/Controllers/FarmController.php`
- `app/Admin/Controllers/FarmActivityController.php`
- `app/Http/Controllers/Api/FarmController.php`
- `app/Http/Controllers/Api/FarmActivityController.php`

### Seeders (To be created next)
- `database/seeders/FarmSeeder.php`

## Cascade Constraints Removed
All `onDelete('cascade')` and `onUpdate('cascade')` statements have been removed from foreign key constraints as per your database requirements.

## Recommendation
Use **Option 1 (Manual SQL)** as it's the safest. Once tables are created, I can proceed with creating Models, Controllers, Seeders, and Mobile App integration.

Would you like me to proceed with creating the Models and Controllers now, or would you prefer to set up the database tables first?
