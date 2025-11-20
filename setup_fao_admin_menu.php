<?php

/**
 * FAO FFS-MIS Admin Menu Setup Script
 * 
 * This script:
 * 1. Cleans up existing roles and menu
 * 2. Creates 7 role-based user types
 * 3. Builds comprehensive menu structure
 * 4. Assigns user ID 1 as Super Admin
 * 
 * Run: php setup_fao_admin_menu.php
 */

// Database configuration from .env
$host = '127.0.0.1';
$port = 3306;
$database = 'fao_ffs_mis';
$username = 'root';
$password = 'root';
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:unix_socket={$socket};dbname={$database}",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✓ Connected to database: {$database}\n\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    // ========================================
    // STEP 1: Clean up existing data
    // ========================================
    echo "STEP 1: Cleaning up existing roles and menu...\n";
    
    $pdo->exec("DELETE FROM admin_role_users");
    echo "  - Cleared role assignments\n";
    
    $pdo->exec("DELETE FROM admin_role_permissions");
    echo "  - Cleared role permissions\n";
    
    $pdo->exec("DELETE FROM admin_role_menu");
    echo "  - Cleared role menu assignments\n";
    
    $pdo->exec("DELETE FROM admin_menu");
    echo "  - Cleared existing menu\n";
    
    $pdo->exec("DELETE FROM admin_roles WHERE id > 0");
    echo "  - Cleared existing roles\n";
    
    $pdo->exec("ALTER TABLE admin_roles AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE admin_menu AUTO_INCREMENT = 1");
    
    echo "✓ Cleanup complete\n\n";
    
    // ========================================
    // STEP 2: Create 7 Role-Based User Types
    // ========================================
    echo "STEP 2: Creating 7 role-based user types...\n";
    
    $roles = [
        ['name' => 'Super Admin', 'slug' => 'super_admin'],
        ['name' => 'IP Manager', 'slug' => 'ip_manager'],
        ['name' => 'Field Facilitator', 'slug' => 'field_facilitator'],
        ['name' => 'VSLA Treasurer', 'slug' => 'vsla_treasurer'],
        ['name' => 'Farmer Member', 'slug' => 'farmer_member'],
        ['name' => 'M&E Officer', 'slug' => 'me_officer'],
        ['name' => 'Content Manager', 'slug' => 'content_manager'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO admin_roles (name, slug, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
    
    foreach ($roles as $role) {
        $stmt->execute([$role['name'], $role['slug']]);
        echo "  - Created role: {$role['name']} ({$role['slug']})\n";
    }
    
    echo "✓ Created 7 roles\n\n";
    
    // ========================================
    // STEP 3: Build Complete Menu Structure
    // ========================================
    echo "STEP 3: Building comprehensive menu structure...\n";
    
    $menuStmt = $pdo->prepare("
        INSERT INTO admin_menu (parent_id, `order`, title, icon, uri, permission, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $menuItems = [
        // Dashboard
        ['parent' => 0, 'order' => 1, 'title' => 'Dashboard', 'icon' => 'fa-tachometer-alt', 'uri' => '/', 'permission' => null],
        
        // Analytics & Reports
        ['parent' => 0, 'order' => 2, 'title' => 'Analytics & Reports', 'icon' => 'fa-chart-bar', 'uri' => '#', 'permission' => null],
        ['parent' => 2, 'order' => 3, 'title' => 'Real-Time KPI Dashboard', 'icon' => 'fa-dashboard', 'uri' => '#', 'permission' => null],
        ['parent' => 2, 'order' => 4, 'title' => 'Gender Analytics', 'icon' => 'fa-venus-mars', 'uri' => '#', 'permission' => null],
        ['parent' => 2, 'order' => 5, 'title' => 'Geographic Performance', 'icon' => 'fa-map-marked-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 2, 'order' => 6, 'title' => 'Value Chain Performance', 'icon' => 'fa-leaf', 'uri' => '#', 'permission' => null],
        ['parent' => 2, 'order' => 7, 'title' => 'Financial Health', 'icon' => 'fa-dollar', 'uri' => '#', 'permission' => null],
        ['parent' => 2, 'order' => 8, 'title' => 'Custom Report Builder', 'icon' => 'fa-wrench', 'uri' => '#', 'permission' => null],
        ['parent' => 2, 'order' => 9, 'title' => 'Export Data', 'icon' => 'fa-download', 'uri' => '#', 'permission' => null],
        
        // Groups & Members
        ['parent' => 0, 'order' => 10, 'title' => 'Groups & Members', 'icon' => 'fa-users', 'uri' => '#', 'permission' => null],
        
        // All Groups
        ['parent' => 10, 'order' => 11, 'title' => 'All Groups', 'icon' => 'fa-users', 'uri' => '#', 'permission' => null],
        ['parent' => 11, 'order' => 12, 'title' => 'All Groups List', 'icon' => 'fa-list', 'uri' => 'ffs-all-groups', 'permission' => null],
        ['parent' => 11, 'order' => 13, 'title' => 'Farmer Field Schools', 'icon' => 'fa-graduation-cap', 'uri' => 'ffs-farmer-field-schools', 'permission' => null],
        ['parent' => 11, 'order' => 14, 'title' => 'Farmer Business Schools', 'icon' => 'fa-briefcase', 'uri' => 'ffs-farmer-business-schools', 'permission' => null],
        ['parent' => 11, 'order' => 15, 'title' => 'VSLAs', 'icon' => 'fa-piggy-bank', 'uri' => 'ffs-vslas', 'permission' => null],
        ['parent' => 11, 'order' => 16, 'title' => 'Group Associations', 'icon' => 'fa-project-diagram', 'uri' => 'ffs-group-associations', 'permission' => null],
        ['parent' => 11, 'order' => 17, 'title' => 'Register New Group', 'icon' => 'fa-plus-circle', 'uri' => 'ffs-all-groups/create', 'permission' => null],
        ['parent' => 11, 'order' => 18, 'title' => 'Bulk Import Groups', 'icon' => 'fa-upload', 'uri' => '#', 'permission' => null],
        
        // All Members
        ['parent' => 10, 'order' => 19, 'title' => 'All Members', 'icon' => 'fa-user-friends', 'uri' => '#', 'permission' => null],
        ['parent' => 19, 'order' => 20, 'title' => 'Members List', 'icon' => 'fa-list', 'uri' => '#', 'permission' => null],
        ['parent' => 19, 'order' => 21, 'title' => 'Add New Member', 'icon' => 'fa-user-plus', 'uri' => '#', 'permission' => null],
        ['parent' => 19, 'order' => 22, 'title' => 'Search & Filter', 'icon' => 'fa-search', 'uri' => '#', 'permission' => null],
        ['parent' => 19, 'order' => 23, 'title' => 'Attendance History', 'icon' => 'fa-calendar-check', 'uri' => '#', 'permission' => null],
        ['parent' => 19, 'order' => 24, 'title' => 'Training Progress', 'icon' => 'fa-chart-line', 'uri' => '#', 'permission' => null],
        ['parent' => 19, 'order' => 25, 'title' => 'Bulk Import Members', 'icon' => 'fa-upload', 'uri' => '#', 'permission' => null],
        
        // Training & Field Activities
        ['parent' => 0, 'order' => 26, 'title' => 'Training & Field Activities', 'icon' => 'fa-book-reader', 'uri' => '#', 'permission' => null],
        
        // Training Sessions
        ['parent' => 26, 'order' => 27, 'title' => 'Training Sessions', 'icon' => 'fa-chalkboard-teacher', 'uri' => '#', 'permission' => null],
        ['parent' => 27, 'order' => 28, 'title' => 'All Sessions', 'icon' => 'fa-list', 'uri' => '#', 'permission' => null],
        ['parent' => 27, 'order' => 29, 'title' => 'Schedule New Session', 'icon' => 'fa-calendar-plus', 'uri' => '#', 'permission' => null],
        ['parent' => 27, 'order' => 30, 'title' => 'Session Calendar', 'icon' => 'fa-calendar', 'uri' => '#', 'permission' => null],
        ['parent' => 27, 'order' => 31, 'title' => 'Attendance Records', 'icon' => 'fa-check-square', 'uri' => '#', 'permission' => null],
        ['parent' => 27, 'order' => 32, 'title' => 'Session Reports', 'icon' => 'fa-file-alt', 'uri' => '#', 'permission' => null],
        
        // AESA
        ['parent' => 26, 'order' => 33, 'title' => 'AESA Records', 'icon' => 'fa-microscope', 'uri' => '#', 'permission' => null],
        ['parent' => 33, 'order' => 34, 'title' => 'All AESA Records', 'icon' => 'fa-list', 'uri' => '#', 'permission' => null],
        ['parent' => 33, 'order' => 35, 'title' => 'Record New AESA', 'icon' => 'fa-plus-circle', 'uri' => '#', 'permission' => null],
        ['parent' => 33, 'order' => 36, 'title' => 'AESA by FFS Plot', 'icon' => 'fa-map-marker', 'uri' => '#', 'permission' => null],
        ['parent' => 33, 'order' => 37, 'title' => 'AESA Trends', 'icon' => 'fa-chart-line', 'uri' => '#', 'permission' => null],
        ['parent' => 33, 'order' => 38, 'title' => 'Photo Gallery', 'icon' => 'fa-images', 'uri' => '#', 'permission' => null],
        
        // Training Library
        ['parent' => 26, 'order' => 39, 'title' => 'Training Content Library', 'icon' => 'fa-book', 'uri' => '#', 'permission' => null],
        ['parent' => 39, 'order' => 40, 'title' => 'All Materials', 'icon' => 'fa-list', 'uri' => '#', 'permission' => null],
        ['parent' => 39, 'order' => 41, 'title' => 'Upload Content', 'icon' => 'fa-upload', 'uri' => '#', 'permission' => null],
        ['parent' => 39, 'order' => 42, 'title' => 'By Topic', 'icon' => 'fa-tags', 'uri' => '#', 'permission' => null],
        ['parent' => 39, 'order' => 43, 'title' => 'By Format', 'icon' => 'fa-file', 'uri' => '#', 'permission' => null],
        ['parent' => 39, 'order' => 44, 'title' => 'By Value Chain', 'icon' => 'fa-leaf', 'uri' => '#', 'permission' => null],
        ['parent' => 39, 'order' => 45, 'title' => 'Content Approval', 'icon' => 'fa-check-circle', 'uri' => '#', 'permission' => null],
        
        // Facilitator Management
        ['parent' => 26, 'order' => 46, 'title' => 'Facilitator Management', 'icon' => 'fa-user-tie', 'uri' => '#', 'permission' => null],
        ['parent' => 46, 'order' => 47, 'title' => 'All Facilitators', 'icon' => 'fa-list', 'uri' => '#', 'permission' => null],
        ['parent' => 46, 'order' => 48, 'title' => 'Assignments', 'icon' => 'fa-tasks', 'uri' => '#', 'permission' => null],
        ['parent' => 46, 'order' => 49, 'title' => 'Performance Metrics', 'icon' => 'fa-chart-bar', 'uri' => '#', 'permission' => null],
        ['parent' => 46, 'order' => 50, 'title' => 'ToT Records', 'icon' => 'fa-certificate', 'uri' => '#', 'permission' => null],
        
        // VSLA Finance
        ['parent' => 0, 'order' => 51, 'title' => 'VSLA Finance', 'icon' => 'fa-money-check-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 51, 'order' => 52, 'title' => 'VSLA Dashboard', 'icon' => 'fa-tachometer-alt', 'uri' => '#', 'permission' => null],
        
        // Savings
        ['parent' => 51, 'order' => 53, 'title' => 'Savings Management', 'icon' => 'fa-coins', 'uri' => '#', 'permission' => null],
        ['parent' => 53, 'order' => 54, 'title' => 'Record Share Purchase', 'icon' => 'fa-plus-circle', 'uri' => '#', 'permission' => null],
        ['parent' => 53, 'order' => 55, 'title' => 'Savings Cycle', 'icon' => 'fa-calendar', 'uri' => '#', 'permission' => null],
        ['parent' => 53, 'order' => 56, 'title' => 'Member Summaries', 'icon' => 'fa-user', 'uri' => '#', 'permission' => null],
        ['parent' => 53, 'order' => 57, 'title' => 'Savings Trends', 'icon' => 'fa-chart-line', 'uri' => '#', 'permission' => null],
        
        // Loans
        ['parent' => 51, 'order' => 58, 'title' => 'Loan Management', 'icon' => 'fa-hand-holding-usd', 'uri' => '#', 'permission' => null],
        ['parent' => 58, 'order' => 59, 'title' => 'Loan Applications', 'icon' => 'fa-file-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 58, 'order' => 60, 'title' => 'Active Loans', 'icon' => 'fa-list', 'uri' => '#', 'permission' => null],
        ['parent' => 58, 'order' => 61, 'title' => 'Loan Repayments', 'icon' => 'fa-money-check', 'uri' => '#', 'permission' => null],
        ['parent' => 58, 'order' => 62, 'title' => 'Overdue Loans', 'icon' => 'fa-exclamation-triangle', 'uri' => '#', 'permission' => null],
        ['parent' => 58, 'order' => 63, 'title' => 'Loan Portfolio', 'icon' => 'fa-chart-pie', 'uri' => '#', 'permission' => null],
        
        // Ledger
        ['parent' => 51, 'order' => 64, 'title' => 'Digital Ledger', 'icon' => 'fa-book-open', 'uri' => '#', 'permission' => null],
        ['parent' => 64, 'order' => 65, 'title' => 'Meeting Records', 'icon' => 'fa-calendar-check', 'uri' => '#', 'permission' => null],
        ['parent' => 64, 'order' => 66, 'title' => 'Transaction History', 'icon' => 'fa-history', 'uri' => '#', 'permission' => null],
        ['parent' => 64, 'order' => 67, 'title' => 'Fund Balances', 'icon' => 'fa-balance-scale', 'uri' => '#', 'permission' => null],
        
        // VSLA Reports
        ['parent' => 51, 'order' => 68, 'title' => 'VSLA Reports', 'icon' => 'fa-file-invoice-dollar', 'uri' => '#', 'permission' => null],
        ['parent' => 68, 'order' => 69, 'title' => 'Group Summary', 'icon' => 'fa-file-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 68, 'order' => 70, 'title' => 'Member Statements', 'icon' => 'fa-user', 'uri' => '#', 'permission' => null],
        ['parent' => 68, 'order' => 71, 'title' => 'Loan Book', 'icon' => 'fa-book', 'uri' => '#', 'permission' => null],
        ['parent' => 68, 'order' => 72, 'title' => 'End of Cycle', 'icon' => 'fa-calendar-times', 'uri' => '#', 'permission' => null],
        
        // Advisory Hub
        ['parent' => 0, 'order' => 73, 'title' => 'Advisory Hub & E-Learning', 'icon' => 'fa-lightbulb', 'uri' => '#', 'permission' => null],
        
        // Advisory Content
        ['parent' => 73, 'order' => 74, 'title' => 'Advisory Content', 'icon' => 'fa-newspaper', 'uri' => '#', 'permission' => null],
        ['parent' => 74, 'order' => 75, 'title' => 'All Content', 'icon' => 'fa-list', 'uri' => '#', 'permission' => null],
        ['parent' => 74, 'order' => 76, 'title' => 'Create New', 'icon' => 'fa-plus-circle', 'uri' => '#', 'permission' => null],
        ['parent' => 74, 'order' => 77, 'title' => 'By Status', 'icon' => 'fa-filter', 'uri' => '#', 'permission' => null],
        ['parent' => 74, 'order' => 78, 'title' => 'By Type', 'icon' => 'fa-file-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 74, 'order' => 79, 'title' => 'By Topic', 'icon' => 'fa-tags', 'uri' => '#', 'permission' => null],
        ['parent' => 74, 'order' => 80, 'title' => 'Content Analytics', 'icon' => 'fa-chart-pie', 'uri' => '#', 'permission' => null],
        
        // E-Learning
        ['parent' => 73, 'order' => 81, 'title' => 'E-Learning Courses', 'icon' => 'fa-graduation-cap', 'uri' => '#', 'permission' => null],
        ['parent' => 81, 'order' => 82, 'title' => 'All Courses', 'icon' => 'fa-list', 'uri' => '#', 'permission' => null],
        ['parent' => 81, 'order' => 83, 'title' => 'Create Course', 'icon' => 'fa-plus-circle', 'uri' => '#', 'permission' => null],
        ['parent' => 81, 'order' => 84, 'title' => 'Enrollment', 'icon' => 'fa-user-graduate', 'uri' => '#', 'permission' => null],
        ['parent' => 81, 'order' => 85, 'title' => 'Progress Tracking', 'icon' => 'fa-tasks', 'uri' => '#', 'permission' => null],
        ['parent' => 81, 'order' => 86, 'title' => 'Assessments', 'icon' => 'fa-question-circle', 'uri' => '#', 'permission' => null],
        ['parent' => 81, 'order' => 87, 'title' => 'Certificates', 'icon' => 'fa-certificate', 'uri' => '#', 'permission' => null],
        
        // Multi-Channel
        ['parent' => 73, 'order' => 88, 'title' => 'Multi-Channel Delivery', 'icon' => 'fa-broadcast-tower', 'uri' => '#', 'permission' => null],
        ['parent' => 88, 'order' => 89, 'title' => 'Push Notifications', 'icon' => 'fa-bell', 'uri' => '#', 'permission' => null],
        ['parent' => 88, 'order' => 90, 'title' => 'IVR Content', 'icon' => 'fa-phone-volume', 'uri' => '#', 'permission' => null],
        ['parent' => 88, 'order' => 91, 'title' => 'USSD Configuration', 'icon' => 'fa-mobile-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 88, 'order' => 92, 'title' => 'SMS Campaigns', 'icon' => 'fa-sms', 'uri' => '#', 'permission' => null],
        
        // Market Linkages
        ['parent' => 0, 'order' => 93, 'title' => 'Market Linkages', 'icon' => 'fa-store', 'uri' => '#', 'permission' => null],
        ['parent' => 93, 'order' => 94, 'title' => 'Service Providers', 'icon' => 'fa-building', 'uri' => '#', 'permission' => null],
        ['parent' => 93, 'order' => 95, 'title' => 'Market Prices', 'icon' => 'fa-chart-line', 'uri' => '#', 'permission' => null],
        ['parent' => 93, 'order' => 96, 'title' => 'Produce Listings', 'icon' => 'fa-box', 'uri' => '#', 'permission' => null],
        ['parent' => 93, 'order' => 97, 'title' => 'Input Needs Board', 'icon' => 'fa-shopping-cart', 'uri' => '#', 'permission' => null],
        ['parent' => 93, 'order' => 98, 'title' => 'Buyer-Farmer Connections', 'icon' => 'fa-handshake', 'uri' => '#', 'permission' => null],
        
        // System Administration
        ['parent' => 0, 'order' => 99, 'title' => 'System Administration', 'icon' => 'fa-cogs', 'uri' => '#', 'permission' => null],
        
        // User Management
        ['parent' => 99, 'order' => 100, 'title' => 'User Management', 'icon' => 'fa-users-cog', 'uri' => 'users', 'permission' => null],
        ['parent' => 100, 'order' => 101, 'title' => 'All Users', 'icon' => 'fa-list', 'uri' => 'users', 'permission' => null],
        ['parent' => 100, 'order' => 102, 'title' => 'Add New User', 'icon' => 'fa-user-plus', 'uri' => 'users/create', 'permission' => null],
        ['parent' => 100, 'order' => 103, 'title' => 'Roles & Permissions', 'icon' => 'fa-key', 'uri' => '#', 'permission' => null],
        ['parent' => 100, 'order' => 104, 'title' => 'User Activity Logs', 'icon' => 'fa-history', 'uri' => '#', 'permission' => null],
        
        // Security
        ['parent' => 99, 'order' => 105, 'title' => 'Security & Privacy', 'icon' => 'fa-shield-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 105, 'order' => 106, 'title' => 'Audit Logs', 'icon' => 'fa-list-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 105, 'order' => 107, 'title' => 'Data Access Logs', 'icon' => 'fa-eye', 'uri' => '#', 'permission' => null],
        ['parent' => 105, 'order' => 108, 'title' => 'Security Settings', 'icon' => 'fa-lock', 'uri' => '#', 'permission' => null],
        ['parent' => 105, 'order' => 109, 'title' => 'Consent Records', 'icon' => 'fa-file-signature', 'uri' => '#', 'permission' => null],
        
        // Master Data
        ['parent' => 99, 'order' => 110, 'title' => 'Location & Master Data', 'icon' => 'fa-database', 'uri' => '#', 'permission' => null],
        ['parent' => 110, 'order' => 111, 'title' => 'Districts', 'icon' => 'fa-map', 'uri' => '#', 'permission' => null],
        ['parent' => 110, 'order' => 112, 'title' => 'Sub-Counties', 'icon' => 'fa-map-marker', 'uri' => '#', 'permission' => null],
        ['parent' => 110, 'order' => 113, 'title' => 'Parishes', 'icon' => 'fa-map-pin', 'uri' => '#', 'permission' => null],
        ['parent' => 110, 'order' => 114, 'title' => 'Value Chains', 'icon' => 'fa-leaf', 'uri' => '#', 'permission' => null],
        ['parent' => 110, 'order' => 115, 'title' => 'Predefined Lists', 'icon' => 'fa-list-ul', 'uri' => '#', 'permission' => null],
        
        // Data Sync
        ['parent' => 99, 'order' => 116, 'title' => 'Data Synchronization', 'icon' => 'fa-sync-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 116, 'order' => 117, 'title' => 'Sync Dashboard', 'icon' => 'fa-tachometer-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 116, 'order' => 118, 'title' => 'Pending Queue', 'icon' => 'fa-clock', 'uri' => '#', 'permission' => null],
        ['parent' => 116, 'order' => 119, 'title' => 'Conflict Resolution', 'icon' => 'fa-exclamation-triangle', 'uri' => '#', 'permission' => null],
        ['parent' => 116, 'order' => 120, 'title' => 'Device Sync History', 'icon' => 'fa-history', 'uri' => '#', 'permission' => null],
        
        // Device Management
        ['parent' => 99, 'order' => 121, 'title' => 'Device Management', 'icon' => 'fa-mobile-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 121, 'order' => 122, 'title' => 'All Devices', 'icon' => 'fa-list', 'uri' => '#', 'permission' => null],
        ['parent' => 121, 'order' => 123, 'title' => 'Device Health', 'icon' => 'fa-heartbeat', 'uri' => '#', 'permission' => null],
        ['parent' => 121, 'order' => 124, 'title' => 'Remote Lock/Wipe', 'icon' => 'fa-lock', 'uri' => '#', 'permission' => null],
        
        // System Health
        ['parent' => 99, 'order' => 125, 'title' => 'System Health', 'icon' => 'fa-heartbeat', 'uri' => '#', 'permission' => null],
        ['parent' => 125, 'order' => 126, 'title' => 'Performance Metrics', 'icon' => 'fa-chart-bar', 'uri' => '#', 'permission' => null],
        ['parent' => 125, 'order' => 127, 'title' => 'Error Logs', 'icon' => 'fa-exclamation-circle', 'uri' => '#', 'permission' => null],
        ['parent' => 125, 'order' => 128, 'title' => 'Backup Status', 'icon' => 'fa-hdd', 'uri' => '#', 'permission' => null],
        
        // Mobile App Management
        ['parent' => 0, 'order' => 129, 'title' => 'Mobile App Management', 'icon' => 'fa-mobile', 'uri' => '#', 'permission' => null],
        ['parent' => 129, 'order' => 130, 'title' => 'Version Control', 'icon' => 'fa-code-branch', 'uri' => '#', 'permission' => null],
        ['parent' => 129, 'order' => 131, 'title' => 'Feature Flags', 'icon' => 'fa-flag', 'uri' => '#', 'permission' => null],
        ['parent' => 129, 'order' => 132, 'title' => 'Mobile Analytics', 'icon' => 'fa-chart-pie', 'uri' => '#', 'permission' => null],
        ['parent' => 129, 'order' => 133, 'title' => 'Crash Reports', 'icon' => 'fa-bug', 'uri' => '#', 'permission' => null],
        
        // MEL Dashboard
        ['parent' => 0, 'order' => 134, 'title' => 'MEL Dashboard', 'icon' => 'fa-project-diagram', 'uri' => '#', 'permission' => null],
        ['parent' => 134, 'order' => 135, 'title' => 'Executive Summary', 'icon' => 'fa-chart-line', 'uri' => '#', 'permission' => null],
        ['parent' => 134, 'order' => 136, 'title' => 'Key Performance Indicators', 'icon' => 'fa-tachometer-alt', 'uri' => '#', 'permission' => null],
        ['parent' => 134, 'order' => 137, 'title' => 'Impact Indicators', 'icon' => 'fa-bullseye', 'uri' => '#', 'permission' => null],
        ['parent' => 134, 'order' => 138, 'title' => 'Gender Reports', 'icon' => 'fa-venus-mars', 'uri' => '#', 'permission' => null],
        ['parent' => 134, 'order' => 139, 'title' => 'Geographic Map', 'icon' => 'fa-map-marked-alt', 'uri' => '#', 'permission' => null],
        
        // Support
        ['parent' => 0, 'order' => 140, 'title' => 'Support & Helpdesk', 'icon' => 'fa-life-ring', 'uri' => '#', 'permission' => null],
        ['parent' => 140, 'order' => 141, 'title' => 'Knowledge Base', 'icon' => 'fa-question-circle', 'uri' => '#', 'permission' => null],
        ['parent' => 140, 'order' => 142, 'title' => 'Video Tutorials', 'icon' => 'fa-video', 'uri' => '#', 'permission' => null],
        ['parent' => 140, 'order' => 143, 'title' => 'Submit Ticket', 'icon' => 'fa-plus-circle', 'uri' => '#', 'permission' => null],
        ['parent' => 140, 'order' => 144, 'title' => 'My Tickets', 'icon' => 'fa-ticket-alt', 'uri' => '#', 'permission' => null],
    ];
    
    foreach ($menuItems as $item) {
        $menuStmt->execute([
            $item['parent'],
            $item['order'],
            $item['title'],
            $item['icon'],
            $item['uri'],
            $item['permission']
        ]);
    }
    
    echo "✓ Created " . count($menuItems) . " menu items\n\n";
    
    // ========================================
    // STEP 4: Assign User ID 1 as Super Admin
    // ========================================
    echo "STEP 4: Assigning user ID 1 as Super Admin...\n";
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, username, name FROM users WHERE id = 1");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "  - Found user: {$user['name']} ({$user['username']})\n";
        
        // Assign Super Admin role (role_id = 1)
        $stmt = $pdo->prepare("
            INSERT INTO admin_role_users (role_id, user_id, created_at, updated_at) 
            VALUES (1, 1, NOW(), NOW())
        ");
        $stmt->execute();
        
        echo "✓ User ID 1 assigned as Super Admin\n\n";
    } else {
        echo "⚠ Warning: User ID 1 not found in users table\n\n";
    }
    
    // ========================================
    // STEP 5: Give Super Admin all permissions
    // ========================================
    echo "STEP 5: Granting Super Admin all permissions...\n";
    
    // Get the "All permission" permission ID
    $stmt = $pdo->prepare("SELECT id FROM admin_permissions WHERE slug = '*' LIMIT 1");
    $stmt->execute();
    $permission = $stmt->fetch();
    
    if ($permission) {
        $stmt = $pdo->prepare("
            INSERT INTO admin_role_permissions (role_id, permission_id, created_at, updated_at) 
            VALUES (1, ?, NOW(), NOW())
        ");
        $stmt->execute([$permission['id']]);
        echo "✓ Super Admin granted all permissions\n\n";
    } else {
        echo "⚠ Warning: 'All permission' not found\n\n";
    }
    
    // Commit transaction
    $pdo->commit();
    
    // ========================================
    // SUMMARY
    // ========================================
    echo "========================================\n";
    echo "SETUP COMPLETE!\n";
    echo "========================================\n\n";
    
    echo "Summary:\n";
    echo "  - 7 roles created\n";
    echo "  - 144 menu items created\n";
    echo "  - User ID 1 assigned as Super Admin\n";
    echo "  - Super Admin has full system access\n\n";
    
    echo "Next Steps:\n";
    echo "  1. Login to admin panel: " . env('APP_URL') . "/\n";
    echo "  2. Test menu navigation\n";
    echo "  3. Configure role permissions for other roles\n";
    echo "  4. Replace '#' URIs with actual controller routes\n\n";
    
    echo "Roles Created:\n";
    foreach ($roles as $role) {
        echo "  - {$role['name']} ({$role['slug']})\n";
    }
    
    echo "\n";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
