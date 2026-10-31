<?php

use App\Http\Middleware\AdminLogOperation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The stock Laravel-Admin operation log stored every request body verbatim,
 * so member/facilitator passwords were readable at /admin/auth/logs.
 * New entries are redacted by AdminLogOperation; this cleans the old ones.
 */
class RedactSecretsInAdminOperationLog extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('admin_operation_log')) {
            return;
        }

        DB::table('admin_operation_log')
            ->where(function ($q) {
                foreach (AdminLogOperation::SECRET_FIELDS as $field) {
                    $q->orWhere('input', 'like', '%"' . $field . '"%');
                }
            })
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $input = json_decode($row->input, true);
                    if (!is_array($input)) {
                        continue;
                    }
                    $redacted = json_encode(AdminLogOperation::redact($input));
                    if ($redacted !== $row->input) {
                        DB::table('admin_operation_log')->where('id', $row->id)->update(['input' => $redacted]);
                    }
                }
            });
    }

    public function down()
    {
        // Redaction cannot be undone.
    }
}
