<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeNameCasing extends Command
{
    protected $signature = 'app:normalize-names
                            {--dry-run : Preview changes without saving to the database}';

    protected $description = 'Normalize name/title fields to Title Case across all key tables';

    /**
     * Tables and their name-type fields that should be Title Cased.
     * Excluded: email, username, phone, national_id, member_code, password, codes, etc.
     */
    private array $map = [
        'users' => [
            'fields' => ['name', 'first_name', 'last_name', 'emergency_contact_name'],
        ],
        'ffs_groups' => [
            'fields' => ['name', 'contact_person_name', 'ip_name'],
        ],
        'ffs_training_sessions' => [
            'fields' => ['title'],
        ],
        'locations' => [
            'fields' => ['name'],
        ],
        'implementing_partners' => [
            'fields' => ['name', 'contact_person'],
        ],
        'projects' => [
            'fields' => ['title', 'cycle_name'],
        ],
        'vsla_profiles' => [
            'fields' => ['group_name', 'cycle_name', 'chair_first_name', 'chair_last_name'],
        ],
        'enterprises' => [
            'fields' => ['name'],
        ],
        'insurance_programs' => [
            'fields' => ['name'],
        ],
        'advisory_categories' => [
            'fields' => ['name'],
        ],
        // advisory_posts and farmer_questions excluded: their titles/author_names
        // contain acronyms (FAO, FFS, AESA) that ucwords would incorrectly lowercase.
        'market_price_categories' => [
            'fields' => ['name'],
        ],
        'market_price_products' => [
            'fields' => ['name'],
        ],
        'farms' => [
            'fields' => ['name'],
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

            $this->info("  [{$table}]  fields: " . implode(', ', $fields));

            DB::table($table)
                ->orderBy('id')
                ->chunk(200, function ($rows) use ($table, $fields, $dryRun, &$updated, &$skipped) {
                    foreach ($rows as $row) {
                        $changes = [];

                        foreach ($fields as $field) {
                            $original = $row->$field ?? null;

                            if ($original === null) {
                                continue;
                            }

                            $normalized = $this->toTitleCase($original);

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

    /** Strip invisible Unicode format chars, then apply Title Case. */
    private function toTitleCase(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $clean = preg_replace('/\p{Cf}+/u', '', $value);
        $clean = trim($clean ?? '');
        return $clean !== '' ? ucwords(mb_strtolower($clean)) : $value;
    }
}
