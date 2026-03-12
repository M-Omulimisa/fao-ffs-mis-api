<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddProcessingColumnsToVslaOpeningBalances extends Migration
{
    public function up()
    {
        Schema::table('vsla_opening_balances', function (Blueprint $table) {
            // Track whether the opening balance has been fanned out into the
            // operational tables (project_shares, vsla_loans, social_fund_transactions).
            $table->boolean('is_processed')->default(false)->after('notes');
            $table->timestamp('processed_at')->nullable()->after('is_processed');
            $table->text('processing_notes')->nullable()->after('processed_at');
        });

        // Extend the status enum to include 'processed'
        DB::statement("ALTER TABLE vsla_opening_balances
            MODIFY COLUMN status ENUM('draft','submitted','processed') NOT NULL DEFAULT 'draft'");
    }

    public function down()
    {
        // Revert enum before dropping columns
        DB::statement("ALTER TABLE vsla_opening_balances
            MODIFY COLUMN status ENUM('draft','submitted') NOT NULL DEFAULT 'draft'");

        Schema::table('vsla_opening_balances', function (Blueprint $table) {
            $table->dropColumn(['is_processed', 'processed_at', 'processing_notes']);
        });
    }
}
