<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVslaDoubleEntryFieldsToProjectTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('project_transactions')) {
            return;
        }

        Schema::table('project_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('project_transactions', 'owner_type')) {
                $table->string('owner_type', 10)->nullable()->after('source')
                    ->comment('Owner type: user or group');
            }
            if (!Schema::hasColumn('project_transactions', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable()->after('owner_type')
                    ->comment('User ID or Group ID based on owner_type');
            }
            if (!Schema::hasColumn('project_transactions', 'contra_entry_id')) {
                $table->unsignedBigInteger('contra_entry_id')->nullable()->after('owner_id')
                    ->comment('Links to paired contra transaction');
            }
            if (!Schema::hasColumn('project_transactions', 'account_type')) {
                $table->string('account_type', 20)->nullable()->after('contra_entry_id')
                    ->comment('Account type: savings, loan, cash, fine, interest, penalty');
            }
            if (!Schema::hasColumn('project_transactions', 'is_contra_entry')) {
                $table->boolean('is_contra_entry')->default(false)->after('account_type')
                    ->comment('Flag indicating if this is a contra entry');
            }
            if (!Schema::hasColumn('project_transactions', 'amount_signed')) {
                $table->decimal('amount_signed', 15, 2)->nullable()->after('is_contra_entry')
                    ->comment('Signed amount for balance calculations (+/-)');
            }

            if (Schema::hasColumn('project_transactions', 'owner_type') && Schema::hasColumn('project_transactions', 'owner_id')) {
                $table->index(['owner_type', 'owner_id'], 'idx_owner');
            }
            if (Schema::hasColumn('project_transactions', 'contra_entry_id')) {
                $table->index('contra_entry_id', 'idx_contra');
            }
            if (Schema::hasColumn('project_transactions', 'account_type')) {
                $table->index('account_type', 'idx_account_type');
            }
            if (
                Schema::hasColumn('project_transactions', 'project_id') &&
                Schema::hasColumn('project_transactions', 'owner_type') &&
                Schema::hasColumn('project_transactions', 'owner_id')
            ) {
                $table->index(['project_id', 'owner_type', 'owner_id'], 'idx_project_owner');
            }

            if (Schema::hasColumn('project_transactions', 'contra_entry_id')) {
                $table->foreign('contra_entry_id', 'fk_contra_entry')
                    ->references('id')->on('project_transactions')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('project_transactions')) {
            return;
        }

        Schema::table('project_transactions', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_contra_entry');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('idx_owner');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('idx_contra');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('idx_account_type');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('idx_project_owner');
            } catch (\Throwable $e) {
            }

            $dropColumns = [];
            foreach (['owner_type', 'owner_id', 'contra_entry_id', 'account_type', 'is_contra_entry', 'amount_signed'] as $column) {
                if (Schema::hasColumn('project_transactions', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
}
