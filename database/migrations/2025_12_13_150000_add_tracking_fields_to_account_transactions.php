<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Comprehensive Tracking Fields to account_transactions
 * 
 * PURPOSE: Enable complete transaction tracking for VSLA accounting
 * 
 * NEW FIELDS:
 * -----------
 * 1. owner_type (ENUM) - Identifies if transaction belongs to 'group' or 'member'
 * 2. group_id (BIGINT) - Links to VSLA group (ALWAYS required for VSLA transactions)
 * 3. meeting_id (BIGINT) - Links to specific meeting (NULL for non-meeting transactions)
 * 4. cycle_id (BIGINT) - Links to savings cycle/project (NULL for non-cycle transactions)
 * 5. account_type (VARCHAR) - Transaction category (savings, loan, fine, welfare, share)
 * 6. contra_entry_id (BIGINT) - Links to opposite entry in double-entry bookkeeping
 * 7. is_contra_entry (BOOLEAN) - Marks if this is the main or contra entry
 * 
 * RATIONALE:
 * ----------
 * - owner_type: Distinguishes group vs member transactions clearly
 * - group_id: Essential for filtering and reporting by VSLA group
 * - meeting_id: Tracks which meeting generated the transaction
 * - cycle_id: Links transactions to specific savings cycles for period reporting
 * - account_type: Categorizes transactions (replaces inconsistent 'source' usage)
 * - contra_entry_id: Enables proper double-entry accounting
 * - is_contra_entry: Identifies primary vs offsetting entries
 * 
 * USAGE EXAMPLES:
 * ---------------
 * Member savings contribution:
 *   - owner_type: 'member', user_id: 123, group_id: 5, meeting_id: 10, 
 *     cycle_id: 2, account_type: 'savings'
 * 
 * Group loan disbursement:
 *   - owner_type: 'group', user_id: NULL, group_id: 5, meeting_id: 10,
 *     cycle_id: 2, account_type: 'loan'
 * 
 * Member fine payment:
 *   - owner_type: 'member', user_id: 456, group_id: 5, meeting_id: 10,
 *     cycle_id: 2, account_type: 'fine'
 */
class AddTrackingFieldsToAccountTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('account_transactions')) {
            Schema::table('account_transactions', function (Blueprint $table) {
            // Owner Type - identifies if transaction belongs to group or individual member
            if (!Schema::hasColumn('account_transactions', 'owner_type')) {
                $table->enum('owner_type', ['group', 'member'])
                    ->nullable()
                    ->after('user_id')
                    ->comment('Identifies if transaction belongs to group or member');
            }
            
            // Group ID - ALWAYS required for VSLA transactions
            if (!Schema::hasColumn('account_transactions', 'group_id')) {
                $table->bigInteger('group_id')
                    ->unsigned()
                    ->nullable()
                    ->after('owner_type')
                    ->comment('VSLA Group this transaction belongs to');
            }
            
            // Meeting ID - links transaction to specific meeting (nullable for non-meeting txns)
            if (!Schema::hasColumn('account_transactions', 'meeting_id')) {
                $table->bigInteger('meeting_id')
                    ->unsigned()
                    ->nullable()
                    ->after('group_id')
                    ->comment('VSLA Meeting that generated this transaction');
            }
            
            // Cycle ID - links transaction to savings cycle/project
            if (!Schema::hasColumn('account_transactions', 'cycle_id')) {
                $table->bigInteger('cycle_id')
                    ->unsigned()
                    ->nullable()
                    ->after('meeting_id')
                    ->comment('Savings cycle/project this transaction belongs to');
            }
            
            // Account Type - transaction category (replaces ambiguous 'source' field)
            if (!Schema::hasColumn('account_transactions', 'account_type')) {
                $table->string('account_type', 50)
                    ->nullable()
                    ->after('source')
                    ->comment('Transaction category: savings, loan, fine, welfare, share, etc.');
            }
            
            // Contra Entry ID - for double-entry bookkeeping
            if (!Schema::hasColumn('account_transactions', 'contra_entry_id')) {
                $table->bigInteger('contra_entry_id')
                    ->unsigned()
                    ->nullable()
                    ->after('account_type')
                    ->comment('Links to opposite entry in double-entry bookkeeping');
            }
            
            // Is Contra Entry - identifies primary vs offsetting entries
            if (!Schema::hasColumn('account_transactions', 'is_contra_entry')) {
                $table->boolean('is_contra_entry')
                    ->default(false)
                    ->after('contra_entry_id')
                    ->comment('True if this is the offsetting entry in double-entry pair');
            }
            });
            
            // Add indexes for performance (check if they don't already exist)
            $indexes = \DB::select("SHOW INDEX FROM account_transactions");
            $existingIndexes = collect($indexes)->pluck('Key_name')->toArray();
            
            if (!in_array('idx_owner_type', $existingIndexes)) {
                \DB::statement('ALTER TABLE account_transactions ADD INDEX idx_owner_type(owner_type)');
            }
            if (!in_array('idx_group_id', $existingIndexes)) {
                \DB::statement('ALTER TABLE account_transactions ADD INDEX idx_group_id(group_id)');
            }
            if (!in_array('idx_meeting_id', $existingIndexes)) {
                \DB::statement('ALTER TABLE account_transactions ADD INDEX idx_meeting_id(meeting_id)');
            }
            if (!in_array('idx_cycle_id', $existingIndexes)) {
                \DB::statement('ALTER TABLE account_transactions ADD INDEX idx_cycle_id(cycle_id)');
            }
            if (!in_array('idx_account_type', $existingIndexes)) {
                \DB::statement('ALTER TABLE account_transactions ADD INDEX idx_account_type(account_type)');
            }
            if (!in_array('idx_contra_entry', $existingIndexes)) {
                \DB::statement('ALTER TABLE account_transactions ADD INDEX idx_contra_entry(contra_entry_id)');
            }
            if (!in_array('idx_group_cycle', $existingIndexes)) {
                \DB::statement('ALTER TABLE account_transactions ADD INDEX idx_group_cycle(group_id, cycle_id)');
            }
            if (!in_array('idx_group_meeting', $existingIndexes)) {
                \DB::statement('ALTER TABLE account_transactions ADD INDEX idx_group_meeting(group_id, meeting_id)');
            }
            if (!in_array('idx_owner_group', $existingIndexes)) {
                \DB::statement('ALTER TABLE account_transactions ADD INDEX idx_owner_group(owner_type, group_id)');
            }
            
            // Foreign keys (if tables exist and not already set)
            try {
                Schema::table('account_transactions', function (Blueprint $table) {
                    if (Schema::hasTable('ffs_groups')) {
                        $table->foreign('group_id', 'fk_account_transactions_group')
                            ->references('id')
                            ->on('ffs_groups')
                            ->onDelete('cascade');
                    }
                        
                    if (Schema::hasTable('vsla_meetings')) {
                        $table->foreign('meeting_id', 'fk_account_transactions_meeting')
                            ->references('id')
                            ->on('vsla_meetings')
                            ->onDelete('set null');
                    }
                        
                    if (Schema::hasTable('projects')) {
                        $table->foreign('cycle_id', 'fk_account_transactions_cycle')
                            ->references('id')
                            ->on('projects')
                            ->onDelete('set null');
                    }
                        
                    $table->foreign('contra_entry_id', 'fk_account_transactions_contra')
                        ->references('id')
                        ->on('account_transactions')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Silently skip foreign keys if they already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            // Drop foreign keys first
            try {
                $table->dropForeign('fk_account_transactions_contra');
                $table->dropForeign('fk_account_transactions_cycle');
                $table->dropForeign('fk_account_transactions_meeting');
                $table->dropForeign('fk_account_transactions_group');
            } catch (\Exception $e) {
                // Constraints might not exist
            }
            
            // Drop indexes
            $table->dropIndex('idx_owner_group');
            $table->dropIndex('idx_group_meeting');
            $table->dropIndex('idx_group_cycle');
            $table->dropIndex('idx_contra_entry');
            $table->dropIndex('idx_account_type');
            $table->dropIndex('idx_cycle_id');
            $table->dropIndex('idx_meeting_id');
            $table->dropIndex('idx_group_id');
            $table->dropIndex('idx_owner_type');
            
            // Drop columns
            $table->dropColumn([
                'owner_type',
                'group_id',
                'meeting_id',
                'cycle_id',
                'account_type',
                'contra_entry_id',
                'is_contra_entry',
            ]);
        });
    }
}
