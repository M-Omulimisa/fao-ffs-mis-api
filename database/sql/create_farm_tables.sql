-- Farm Module Database Setup
-- Run this SQL file to create the farm module tables
-- This avoids Laravel migration issues

-- ==============================================
-- 1. CREATE FARMS TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS `farms` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `enterprise_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('planning', 'active', 'completed', 'abandoned') DEFAULT 'planning',
    `start_date` DATE NOT NULL,
    `expected_end_date` DATE NOT NULL,
    `actual_end_date` DATE NULL,
    `gps_latitude` DECIMAL(10,7) NULL,
    `gps_longitude` DECIMAL(10,7) NULL,
    `location_text` VARCHAR(255) NULL,
    `photo` VARCHAR(255) NULL,
    `overall_score` DECIMAL(5,2) DEFAULT 0.00,
    `completed_activities_count` INT DEFAULT 0,
    `total_activities_count` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_enterprise_id` (`enterprise_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- 2. CREATE FARM ACTIVITIES TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS `farm_activities` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `farm_id` BIGINT UNSIGNED NOT NULL,
    `production_protocol_id` BIGINT UNSIGNED NULL,
    `activity_name` VARCHAR(255) NOT NULL,
    `activity_description` TEXT NULL,
    `scheduled_date` DATE NOT NULL,
    `scheduled_week` INT DEFAULT 1,
    `actual_completion_date` DATE NULL,
    `status` ENUM('pending', 'done', 'skipped', 'overdue') DEFAULT 'pending',
    `is_mandatory` TINYINT(1) DEFAULT 0,
    `weight` INT DEFAULT 1,
    `target_value` DECIMAL(10,2) NULL,
    `actual_value` DECIMAL(10,2) NULL,
    `score` DECIMAL(6,2) DEFAULT 0.00,
    `notes` TEXT NULL,
    `photo` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_farm_id` (`farm_id`),
    INDEX `idx_production_protocol_id` (`production_protocol_id`),
    INDEX `idx_scheduled_date` (`scheduled_date`),
    INDEX `idx_status` (`status`),
    INDEX `idx_farm_scheduled` (`farm_id`, `scheduled_date`),
    INDEX `idx_farm_status` (`farm_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- 3. ADD FOREIGN KEYS (Optional - only if your DB supports it)
-- Uncomment these if you want foreign key constraints
-- ==============================================
-- ALTER TABLE `farms` 
--     ADD CONSTRAINT `fk_farms_enterprise` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises`(`id`),
--     ADD CONSTRAINT `fk_farms_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`);

-- ALTER TABLE `farm_activities`
--     ADD CONSTRAINT `fk_farm_activities_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms`(`id`),
--     ADD CONSTRAINT `fk_farm_activities_protocol` FOREIGN KEY (`production_protocol_id`) REFERENCES `production_protocols`(`id`);

-- ==============================================
-- 4. VERIFY TABLES CREATED
-- ==============================================
SHOW TABLES LIKE 'farm%';

SELECT 
    'farms' as table_name,
    COUNT(*) as column_count 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
    AND table_name = 'farms'
UNION ALL
SELECT 
    'farm_activities' as table_name,
    COUNT(*) as column_count 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
    AND table_name = 'farm_activities';
