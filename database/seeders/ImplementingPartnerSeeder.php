<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ImplementingPartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImplementingPartnerSeeder extends Seeder
{
    /**
     * Seed implementing partners from known IPs and backfill ip_id from legacy ip_name.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('=== Implementing Partners Seeder ===');

        // 1. Create known IPs (from legacy hardcoded dropdown)
        $ips = [
            [
                'name' => 'Karamoja Agro-Pastoral Development Programme',
                'short_name' => 'KADP',
                'description' => 'Karamoja Agro-Pastoral Development Programme',
                'status' => 'active',
            ],
            [
                'name' => 'Ecosystems Based Adaptation',
                'short_name' => 'ECO',
                'description' => 'Ecosystems Based Adaptation Programme',
                'status' => 'active',
            ],
            [
                'name' => 'Green Agriculture Development',
                'short_name' => 'GARD',
                'description' => 'Green Agriculture Development Programme',
                'status' => 'active',
            ],
        ];

        foreach ($ips as $ipData) {
            $ip = ImplementingPartner::firstOrCreate(
                ['short_name' => $ipData['short_name']],
                $ipData
            );
            $this->command->info("  IP: {$ip->name} ({$ip->short_name}) - ID: {$ip->id}");
        }

        // 2. Check for any other ip_name values in ffs_groups not covered above
        $otherIpNames = DB::table('ffs_groups')
            ->select('ip_name')
            ->whereNotNull('ip_name')
            ->where('ip_name', '!=', '')
            ->whereNotIn('ip_name', ['KADP', 'ECO', 'GARD'])
            ->distinct()
            ->pluck('ip_name');

        foreach ($otherIpNames as $ipName) {
            $ip = ImplementingPartner::firstOrCreate(
                ['short_name' => $ipName],
                [
                    'name' => $ipName,
                    'short_name' => $ipName,
                    'status' => 'active',
                ]
            );
            $this->command->info("  New IP from legacy data: {$ip->name} ({$ip->short_name}) - ID: {$ip->id}");
        }

        // 3. Backfill ip_id on ffs_groups from legacy ip_name
        $allIps = ImplementingPartner::all();
        $backfillCount = 0;
        foreach ($allIps as $ip) {
            $updated = DB::table('ffs_groups')
                ->where('ip_name', $ip->short_name)
                ->whereNull('ip_id')
                ->update(['ip_id' => $ip->id]);
            $backfillCount += $updated;
        }
        $this->command->info("  Backfilled ip_id on {$backfillCount} ffs_groups from legacy ip_name");

        // 4. Backfill ip_id on users from their group's IP
        $usersUpdated = DB::statement("
            UPDATE users u
            INNER JOIN ffs_groups g ON u.group_id = g.id
            SET u.ip_id = g.ip_id
            WHERE u.ip_id IS NULL AND g.ip_id IS NOT NULL
        ");
        $usersCount = DB::table('users')->whereNotNull('ip_id')->count();
        $this->command->info("  Users with ip_id set: {$usersCount}");

        // 5. Backfill ip_id on ffs_training_sessions from their group's IP
        // Training sessions have group_id through participants or direct link
        if (\Schema::hasColumn('ffs_training_sessions', 'group_id')) {
            DB::statement("
                UPDATE ffs_training_sessions ts
                INNER JOIN ffs_groups g ON ts.group_id = g.id
                SET ts.ip_id = g.ip_id
                WHERE ts.ip_id IS NULL AND g.ip_id IS NOT NULL
            ");
        }
        $sessionsCount = DB::table('ffs_training_sessions')->whereNotNull('ip_id')->count();
        $this->command->info("  Training sessions with ip_id set: {$sessionsCount}");

        // 6. Backfill ip_id on vsla_meetings from their group's IP
        if (\Schema::hasTable('vsla_meetings') && \Schema::hasColumn('vsla_meetings', 'ip_id')) {
            DB::statement("
                UPDATE vsla_meetings vm
                INNER JOIN ffs_groups g ON vm.group_id = g.id
                SET vm.ip_id = g.ip_id
                WHERE vm.ip_id IS NULL AND g.ip_id IS NOT NULL
            ");
            $meetingsCount = DB::table('vsla_meetings')->whereNotNull('ip_id')->count();
            $this->command->info("  VSLA meetings with ip_id set: {$meetingsCount}");
        }

        // 7. Backfill ip_id on advisory_posts from author's IP
        if (\Schema::hasTable('advisory_posts') && \Schema::hasColumn('advisory_posts', 'ip_id')) {
            DB::statement("
                UPDATE advisory_posts ap
                INNER JOIN users u ON ap.author_id = u.id
                SET ap.ip_id = u.ip_id
                WHERE ap.ip_id IS NULL AND u.ip_id IS NOT NULL
            ");
            $postsCount = DB::table('advisory_posts')->whereNotNull('ip_id')->count();
            $this->command->info("  Advisory posts with ip_id set: {$postsCount}");
        }

        // Summary
        $this->command->info('');
        $this->command->info('=== Summary ===');
        $this->command->info("  Total IPs: " . ImplementingPartner::count());
        $this->command->info("  Groups with IP: " . DB::table('ffs_groups')->whereNotNull('ip_id')->count() . " / " . DB::table('ffs_groups')->count());
        $this->command->info("  Users with IP: " . DB::table('users')->whereNotNull('ip_id')->count() . " / " . DB::table('users')->count());
        $this->command->info("  Sessions with IP: " . DB::table('ffs_training_sessions')->whereNotNull('ip_id')->count() . " / " . DB::table('ffs_training_sessions')->count());
        $this->command->info('=== Done ===');
    }
}
