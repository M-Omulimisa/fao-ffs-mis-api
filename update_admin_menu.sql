-- ========================================
-- Laravel-Admin Menu Structure Update
-- Date: 29 October 2025
-- Purpose: Clean menu and add new modules
-- ========================================

-- Step 1: Backup current menu (optional - for safety)
-- CREATE TABLE admin_menu_backup AS SELECT * FROM admin_menu;

-- Step 2: Clear all menu items except Dashboard (id=1)
DELETE FROM admin_menu WHERE id > 1;

-- Step 3: Reset auto increment
ALTER TABLE admin_menu AUTO_INCREMENT = 2;

-- Step 4: Insert new menu structure
-- ========================================
-- INVESTMENT MANAGEMENT
-- ========================================
INSERT INTO admin_menu (parent_id, `order`, title, icon, uri, created_at, updated_at) VALUES
(0, 100, 'Investments', 'fa-chart-line', NULL, NOW(), NOW());

SET @investment_id = LAST_INSERT_ID();

INSERT INTO admin_menu (parent_id, `order`, title, icon, uri, created_at, updated_at) VALUES
(@investment_id, 101, 'Projects', 'fa-project-diagram', 'projects', NOW(), NOW()),
(@investment_id, 102, 'Project Shares', 'fa-users', 'project-shares', NOW(), NOW()),
(@investment_id, 103, 'Transactions', 'fa-exchange-alt', 'project-transactions', NOW(), NOW()),
(@investment_id, 104, 'Disbursements', 'fa-hand-holding-usd', 'disbursements', NOW(), NOW()),
(@investment_id, 105, 'Account Transactions', 'fa-wallet', 'account-transactions', NOW(), NOW());

-- ========================================
-- INSURANCE MANAGEMENT
-- ========================================
INSERT INTO admin_menu (parent_id, `order`, title, icon, uri, created_at, updated_at) VALUES
(0, 200, 'Insurance', 'fa-shield-alt', NULL, NOW(), NOW());

SET @insurance_id = LAST_INSERT_ID();

INSERT INTO admin_menu (parent_id, `order`, title, icon, uri, created_at, updated_at) VALUES
(@insurance_id, 201, 'Programs', 'fa-file-medical', 'insurance-programs', NOW(), NOW()),
(@insurance_id, 202, 'Subscriptions', 'fa-calendar-check', 'insurance-subscriptions', NOW(), NOW()),
(@insurance_id, 203, 'Users', 'fa-user-shield', 'insurance-users', NOW(), NOW()),
(@insurance_id, 204, 'Transactions', 'fa-piggy-bank', 'insurance-transactions', NOW(), NOW());

-- ========================================
-- MEDICAL SERVICES
-- ========================================
INSERT INTO admin_menu (parent_id, `order`, title, icon, uri, created_at, updated_at) VALUES
(0, 300, 'Medical Services', 'fa-hospital', 'medical-service-requests', NOW(), NOW());

-- ========================================
-- E-COMMERCE
-- ========================================
INSERT INTO admin_menu (parent_id, `order`, title, icon, uri, created_at, updated_at) VALUES
(0, 400, 'E-Commerce', 'fa-shopping-cart', NULL, NOW(), NOW());

SET @ecommerce_id = LAST_INSERT_ID();

INSERT INTO admin_menu (parent_id, `order`, title, icon, uri, created_at, updated_at) VALUES
(@ecommerce_id, 401, 'Products', 'fa-box', 'products', NOW(), NOW()),
(@ecommerce_id, 402, 'Orders', 'fa-shopping-bag', 'orders', NOW(), NOW());

-- ========================================
-- SYSTEM MANAGEMENT
-- ========================================
INSERT INTO admin_menu (parent_id, `order`, title, icon, uri, created_at, updated_at) VALUES
(0, 500, 'System', 'fa-cog', NULL, NOW(), NOW());

SET @system_id = LAST_INSERT_ID();

INSERT INTO admin_menu (parent_id, `order`, title, icon, uri, created_at, updated_at) VALUES
(@system_id, 501, 'Users', 'fa-users', 'users', NOW(), NOW()),
(@system_id, 502, 'Notifications', 'fa-bell', 'notifications', NOW(), NOW()),
(@system_id, 503, 'Configurations', 'fa-wrench', 'system-configurations', NOW(), NOW());

-- ========================================
-- Verification Query
-- ========================================
-- Run this to verify the menu structure:
-- SELECT id, parent_id, `order`, title, icon, uri FROM admin_menu ORDER BY `order`;

-- ========================================
-- Menu Structure Preview:
-- ========================================
-- Dashboard (id=1, existing)
-- └─ Investments
--    ├─ Projects
--    ├─ Project Shares
--    ├─ Transactions
--    ├─ Disbursements
--    └─ Account Transactions
-- └─ Insurance
--    ├─ Programs
--    ├─ Subscriptions
--    ├─ Users
--    └─ Transactions
-- └─ Medical Services
-- └─ E-Commerce
--    ├─ Products
--    └─ Orders
-- └─ System
--    ├─ Users
--    ├─ Notifications
--    └─ Configurations
-- ========================================
