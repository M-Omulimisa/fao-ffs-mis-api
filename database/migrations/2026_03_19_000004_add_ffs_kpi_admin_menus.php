<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddFfsKpiAdminMenus extends Migration
{
    public function up()
    {
        $table = config('admin.database.menu_table', 'admin_menu');

        if (!Schema::hasTable($table)) {
            return;
        }

        // Find max order to place FFS KPIs after existing items
        $maxOrder = DB::table($table)->max('order') ?? 80;
        $parentOrder = $maxOrder + 5;

        // Skip if already installed
        $exists = DB::table($table)
            ->where('title', 'FFS KPIs')
            ->where('parent_id', 0)
            ->exists();

        if ($exists) {
            return;
        }

        // Insert parent menu
        $parentId = DB::table($table)->insertGetId([
            'parent_id'  => 0,
            'order'      => $parentOrder,
            'title'      => 'FFS KPIs',
            'icon'       => 'fa-line-chart',
            'uri'        => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert child menus
        DB::table($table)->insert([
            [
                'parent_id'  => $parentId,
                'order'      => 1,
                'title'      => 'KPI Dashboard',
                'icon'       => 'fa-dashboard',
                'uri'        => 'ffs-kpi-dashboard',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'parent_id'  => $parentId,
                'order'      => 2,
                'title'      => 'IP KPI Entries',
                'icon'       => 'fa-table',
                'uri'        => 'ffs-kpi-ip-entries',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'parent_id'  => $parentId,
                'order'      => 3,
                'title'      => 'Facilitator KPI Entries',
                'icon'       => 'fa-users',
                'uri'        => 'ffs-kpi-facilitator-entries',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down()
    {
        $table = config('admin.database.menu_table', 'admin_menu');

        if (!Schema::hasTable($table)) {
            return;
        }

        $parent = DB::table($table)
            ->where('title', 'FFS KPIs')
            ->where('parent_id', 0)
            ->first();

        if ($parent) {
            DB::table($table)->where('parent_id', $parent->id)->delete();
            DB::table($table)->where('id', $parent->id)->delete();
        }
    }
}
