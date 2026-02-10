<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Adds Series Movies & Movies menu items to the admin panel.
 *
 * Run:  php artisan db:seed --class=AdminMenuContentSeeder
 *
 * Safe to run multiple times — it skips if the parent menu already exists.
 */
class AdminMenuContentSeeder extends Seeder
{
    public function run(): void
    {
        $table = config('admin.database.menu_table', 'admin_menu');

        // ── Guard: skip if already seeded ──────────────────
        $exists = DB::table($table)->where('title', 'Content & Debug')->first();
        if ($exists) {
            $this->command->info('⏭  Content & Debug menu already exists (id: ' . $exists->id . '). Skipping.');
            return;
        }

        // ── Determine next order value ─────────────────────
        $maxOrder = DB::table($table)->max('order') ?? 0;
        $order    = $maxOrder + 1;

        $now = now();

        // ── 1. Top-level parent: "Content & Debug" ────────
        $parentId = DB::table($table)->insertGetId([
            'parent_id'  => 0,
            'order'      => $order++,
            'title'      => 'Content & Debug',
            'icon'       => 'fa-bug',
            'uri'        => '#',
            'permission' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ── 2. Series Movies group ────────────────────────
        $seriesParent = DB::table($table)->insertGetId([
            'parent_id'  => $parentId,
            'order'      => $order++,
            'title'      => 'Series Movies',
            'icon'       => 'fa-film',
            'uri'        => '#',
            'permission' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $seriesItems = [
            ['title' => 'All Series Movies',    'icon' => 'fa-list',              'uri' => 'series-movies'],
            ['title' => 'Pending Fix',           'icon' => 'fa-clock',             'uri' => 'series-movies-pending'],
            ['title' => 'Fixed (Success)',       'icon' => 'fa-check-circle',      'uri' => 'series-movies-success'],
            ['title' => 'Failed Fix',            'icon' => 'fa-exclamation-circle', 'uri' => 'series-movies-fail'],
            ['title' => 'Add New Series',        'icon' => 'fa-plus-circle',       'uri' => 'series-movies/create'],
        ];

        foreach ($seriesItems as $item) {
            DB::table($table)->insert([
                'parent_id'  => $seriesParent,
                'order'      => $order++,
                'title'      => $item['title'],
                'icon'       => $item['icon'],
                'uri'        => $item['uri'],
                'permission' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ── 3. Movies group ───────────────────────────────
        $moviesParent = DB::table($table)->insertGetId([
            'parent_id'  => $parentId,
            'order'      => $order++,
            'title'      => 'Movies',
            'icon'       => 'fa-video',
            'uri'        => '#',
            'permission' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $movieItems = [
            ['title' => 'All Movies',            'icon' => 'fa-list',              'uri' => 'movies'],
            ['title' => 'Pending Fix',            'icon' => 'fa-clock',             'uri' => 'movies-pending'],
            ['title' => 'Fixed (Success)',        'icon' => 'fa-check-circle',      'uri' => 'movies-success'],
            ['title' => 'Failed Fix',             'icon' => 'fa-exclamation-circle', 'uri' => 'movies-fail'],
            ['title' => 'Add New Movie',          'icon' => 'fa-plus-circle',       'uri' => 'movies/create'],
        ];

        foreach ($movieItems as $item) {
            DB::table($table)->insert([
                'parent_id'  => $moviesParent,
                'order'      => $order++,
                'title'      => $item['title'],
                'icon'       => $item['icon'],
                'uri'        => $item['uri'],
                'permission' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info("✅ Content & Debug menu created with Series Movies + Movies sub-items.");
    }
}
