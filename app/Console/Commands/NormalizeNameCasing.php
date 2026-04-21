<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeNameCasing extends Command
{
    use \App\Traits\TitleCase;

    protected $signature = 'app:normalize-names
                            {--dry-run : Preview changes without saving to the database}';

    protected $description = 'Normalize name/title fields to Title Case across all key tables';

    /**
     * Tables and their name-type fields that should be Title Cased.
     * Excluded: email, username, phone, national_id, member_code, password, codes, etc.
     */
    private array $map = [
        'users' => [
            'fields' => [
                'name' => 'title',
                'first_name' => 'title',
                'last_name' => 'title',
                'emergency_contact_name' => 'title',
            ],
        ],
        'ffs_groups' => [
            'fields' => [
                'name' => 'upper',
                'contact_person_name' => 'title',
                'ip_name' => 'title',
                'subcounty_text' => 'title',
                'parish_text' => 'title',
                'village' => 'title',
            ],
        ],
        'ffs_training_sessions' => [
            'fields' => ['title' => 'title'],
        ],
        'locations' => [
            'fields' => ['name' => 'title'],
        ],
        'implementing_partners' => [
            'fields' => ['name' => 'title', 'contact_person' => 'title'],
        ],
        'projects' => [
            'fields' => ['title' => 'title', 'cycle_name' => 'title'],
        ],
        'vsla_profiles' => [
            'fields' => [
                'group_name' => 'upper',
                'cycle_name' => 'title',
                'chair_first_name' => 'title',
                'chair_last_name' => 'title',
            ],
        ],
        'enterprises' => [
            'fields' => ['name' => 'title'],
        ],
        'insurance_programs' => [
            'fields' => ['name' => 'title'],
        ],
        'advisory_categories' => [
            'fields' => ['name' => 'title'],
        ],
        // advisory_posts and farmer_questions excluded: their titles/author_names
        // contain acronyms (FAO, FFS, AESA) that ucwords would incorrectly lowercase.
        'market_price_categories' => [
            'fields' => ['name' => 'title'],
        ],
        'market_price_products' => [
            'fields' => ['name' => 'title'],
        ],
        'farms' => [
            'fields' => ['name' => 'title'],
        ],
    ];

    public function handle(): int
    {
        $dryRun   = $this->option('dry-run');
        $updated  = 0;
        $skipped  = 0;

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be written to the database.');
            $this->newLine();
        }

        foreach ($this->map as $table => $cfg) {
            $fields = $cfg['fields'];

            $this->info("  [{$table}]  fields: " . implode(', ', array_keys($fields)));

            DB::table($table)
                ->orderBy('id')
                ->chunk(200, function ($rows) use ($table, $fields, $dryRun, &$updated, &$skipped) {
                    foreach ($rows as $row) {
                        $changes = [];

                        foreach ($fields as $field => $mode) {
                            $original = $row->$field ?? null;

                            if ($original === null) {
                                continue;
                            }

                            $normalized = $this->normalizeCase($original, $mode);

                            if ($normalized === $original) {
                                $skipped++;
                                continue;
                            }

                            $changes[$field] = $normalized;
                        }

                        if (empty($changes)) {
                            continue;
                        }

                        if ($dryRun) {
                            foreach ($changes as $field => $newValue) {
                                $this->line(
                                    "    id={$row->id}  {$field}: " .
                                    "\"{$row->$field}\" → \"{$newValue}\""
                                );
                            }
                        } else {
                            DB::table($table)
                                ->where('id', $row->id)
                                ->update($changes);
                        }

                        $updated += count($changes);
                    }
                });
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("Dry run complete — {$updated} field(s) would be updated, {$skipped} already in Title Case.");
        } else {
            $this->info("Done — {$updated} field(s) updated to Title Case, {$skipped} were already correct.");
        }

        return 0;
    }

    // Uses App\Traits\TitleCase::normalizeCase()
}
