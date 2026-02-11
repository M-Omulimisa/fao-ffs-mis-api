<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * FAO FFS-MIS: Complete Admin Menu Rebuild
 * 
 * This seeder cleans the existing menu and creates a fresh, well-organized
 * menu structure for the Farmer Field School Management Information System.
 * 
 * Font Awesome 4.7.0 icons used (Laravel-Admin default)
 * 
 * Run: php artisan db:seed --class=AdminMenuCleanSeeder --force
 */
class AdminMenuCleanSeeder extends Seeder
{
    private $table;
    private $order = 0;
    private $now;

    public function run(): void
    {
        $this->table = config('admin.database.menu_table', 'admin_menu');
        $this->now = now();

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║       FAO FFS-MIS: Admin Menu Clean & Rebuild            ║');
        $this->command->info('╚══════════════════════════════════════════════════════════╝');

        // Step 1: Backup current menu
        $currentCount = DB::table($this->table)->count();
        $this->command->warn("  → Current menu has {$currentCount} items");

        // Step 2: Clean menu (except Laravel-Admin default items)
        $this->command->info('  → Cleaning menu table...');
        DB::table($this->table)->truncate();

        // Step 3: Seed new menu structure
        $this->command->info('  → Seeding new menu structure...');
        $this->seedMenu();

        $newCount = DB::table($this->table)->count();
        $this->command->info("  ✓ Created {$newCount} menu items");
        $this->command->info('');
        $this->command->info('  Menu rebuild complete!');
        $this->command->info('');
    }

    private function seedMenu(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 1. DASHBOARD (Always first)
        // ═══════════════════════════════════════════════════════════
        $this->insertItem(0, 'Dashboard', 'fa-dashboard', '/');

        // ═══════════════════════════════════════════════════════════
        // 2. GROUPS & MEMBERS (Core data)
        // ═══════════════════════════════════════════════════════════
        $groupsId = $this->insertItem(0, 'Groups & Members', 'fa-users', null);
        
        // 2.1 All Groups submenu
        $allGroupsId = $this->insertItem($groupsId, 'All Groups', 'fa-th-list', null);
        $this->insertItem($allGroupsId, 'Register New Group', 'fa-plus-circle', 'ffs-all-groups/create');
        $this->insertItem($allGroupsId, 'All Groups List', 'fa-list', 'ffs-all-groups');
        $this->insertItem($allGroupsId, 'Farmer Field Schools', 'fa-graduation-cap', 'ffs-farmer-field-schools');
        $this->insertItem($allGroupsId, 'Farmer Business Schools', 'fa-briefcase', 'ffs-farmer-business-schools');
        $this->insertItem($allGroupsId, 'VSLAs', 'fa-bank', 'ffs-vslas');
        $this->insertItem($allGroupsId, 'Group Associations', 'fa-sitemap', 'ffs-group-associations');

        // 2.2 Members submenu
        $membersId = $this->insertItem($groupsId, 'Members', 'fa-user', null);
        $this->insertItem($membersId, 'Add New Member', 'fa-user-plus', 'ffs-members/create');
        $this->insertItem($membersId, 'All Members', 'fa-list-ul', 'ffs-members');

        // ═══════════════════════════════════════════════════════════
        // 3. TRAINING & FIELD ACTIVITIES
        // ═══════════════════════════════════════════════════════════
        $trainingId = $this->insertItem(0, 'Training & Field', 'fa-calendar-check-o', null);
        $this->insertItem($trainingId, 'Schedule New Session', 'fa-plus-circle', 'ffs-training-sessions/create');
        $this->insertItem($trainingId, 'All Training Sessions', 'fa-calendar', 'ffs-training-sessions');
        $this->insertItem($trainingId, 'Session Attendance', 'fa-check-square-o', 'ffs-session-participants');
        $this->insertItem($trainingId, 'Session Resolutions', 'fa-tasks', 'ffs-session-resolutions');

        // ═══════════════════════════════════════════════════════════
        // 4. VSLA FINANCE
        // ═══════════════════════════════════════════════════════════
        $vslaId = $this->insertItem(0, 'VSLA Finance', 'fa-money', null);
        $this->insertItem($vslaId, 'VSLA Cycles', 'fa-refresh', 'cycles');
        $this->insertItem($vslaId, 'VSLA Meetings', 'fa-handshake-o', 'vsla-meetings');
        $this->insertItem($vslaId, 'Meeting Attendance', 'fa-user-circle-o', 'vsla-meeting-attendance');
        $this->insertItem($vslaId, 'Loans', 'fa-credit-card', 'vsla-loans');
        $this->insertItem($vslaId, 'Loan Transactions', 'fa-exchange', 'loan-transactions');
        $this->insertItem($vslaId, 'Savings & Shares', 'fa-database', 'account-transactions');
        $this->insertItem($vslaId, 'Member Balances', 'fa-line-chart', 'financial-accounts');
        $this->insertItem($vslaId, 'Action Plans', 'fa-list-ol', 'vsla-action-plans');

        // ═══════════════════════════════════════════════════════════
        // 5. ADVISORY & LEARNING
        // ═══════════════════════════════════════════════════════════
        $advisoryId = $this->insertItem(0, 'Advisory & Learning', 'fa-book', null);
        $this->insertItem($advisoryId, 'Categories', 'fa-folder-open', 'advisory-categories');
        $this->insertItem($advisoryId, 'Articles', 'fa-file-text', 'advisory-posts');
        $this->insertItem($advisoryId, 'Farmer Questions', 'fa-question-circle', 'farmer-questions');
        $this->insertItem($advisoryId, 'Answers Moderation', 'fa-comments', 'farmer-question-answers');

        // ═══════════════════════════════════════════════════════════
        // 6. ENTERPRISES & PRODUCTION
        // ═══════════════════════════════════════════════════════════
        $enterpriseId = $this->insertItem(0, 'Enterprises', 'fa-leaf', null);
        $this->insertItem($enterpriseId, 'All Enterprises', 'fa-industry', 'enterprises');
        $this->insertItem($enterpriseId, 'Production Protocols', 'fa-clipboard', 'production-protocols');

        // ═══════════════════════════════════════════════════════════
        // 7. MARKET PRICES
        // ═══════════════════════════════════════════════════════════
        $marketId = $this->insertItem(0, 'Market Prices', 'fa-bar-chart', null);
        $this->insertItem($marketId, 'Price Categories', 'fa-tags', 'market-price-categories');
        $this->insertItem($marketId, 'Products', 'fa-cube', 'market-price-products');
        $this->insertItem($marketId, 'Price Records', 'fa-line-chart', 'market-prices');

        // ═══════════════════════════════════════════════════════════
        // 8. SYSTEM ADMINISTRATION
        // ═══════════════════════════════════════════════════════════
        $adminId = $this->insertItem(0, 'System Administration', 'fa-cogs', null);
        $this->insertItem($adminId, 'Implementing Partners', 'fa-building-o', 'implementing-partners');
        $this->insertItem($adminId, 'System Users', 'fa-user-secret', 'users');
        $this->insertItem($adminId, 'Data Import', 'fa-upload', 'import-tasks');
        $this->insertItem($adminId, 'System Config', 'fa-sliders', 'system-configurations');
        $this->insertItem($adminId, 'Payment Records', 'fa-credit-card-alt', 'pesapal-payments');

        // ═══════════════════════════════════════════════════════════
        // 9. LARAVEL-ADMIN DEFAULTS (Auth)
        // ═══════════════════════════════════════════════════════════
        $authId = $this->insertItem(0, 'Auth Management', 'fa-shield', null);
        $this->insertItem($authId, 'Admin Users', 'fa-users', 'auth/users');
        $this->insertItem($authId, 'Roles', 'fa-user', 'auth/roles');
        $this->insertItem($authId, 'Permissions', 'fa-ban', 'auth/permissions');
        $this->insertItem($authId, 'Menu', 'fa-bars', 'auth/menu');
        $this->insertItem($authId, 'Operation Log', 'fa-history', 'auth/logs');
    }

    /**
     * Insert a menu item and return its ID
     */
    private function insertItem(int $parentId, string $title, string $icon, ?string $uri): int
    {
        $this->order++;
        
        return DB::table($this->table)->insertGetId([
            'parent_id' => $parentId,
            'order' => $this->order,
            'title' => $title,
            'icon' => $icon,
            'uri' => $uri,
            'permission' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
    }
}
