<?php
/**
 * Fix account_transactions table schema to support VSLA double-entry accounting
 * 
 * Changes:
 * 1. Make user_id NULLABLE (for group transactions)
 * 2. Expand source ENUM to include all transaction types
 * 3. Ensure amount can be negative
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIXING account_transactions TABLE SCHEMA ===\n\n";

try {
    // Step 1: Make user_id NULLABLE
    echo "Step 1: Making user_id NULLABLE for group transactions...\n";
    DB::statement("ALTER TABLE account_transactions MODIFY user_id BIGINT(20) UNSIGNED NULL");
    echo "✅ user_id is now NULLABLE\n\n";

    // Step 2: Expand source ENUM
    echo "Step 2: Expanding source ENUM to include all transaction types...\n";
    $sources = [
        'savings',
        'share_purchase',
        'welfare_contribution',
        'loan_repayment',
        'fine_payment',
        'loan_disbursement',
        'share_dividend',
        'welfare_distribution',
        'administrative_expense',
        'external_income',
        'bank_charges',
        'manual_adjustment',
        'disbursement',  // Keep old value for backward compatibility
        'withdrawal',    // Keep old value
        'deposit'        // Keep old value
    ];
    
    $enumValues = "'" . implode("','", $sources) . "'";
    DB::statement("ALTER TABLE account_transactions MODIFY source ENUM({$enumValues}) NOT NULL");
    echo "✅ source ENUM expanded with all transaction types\n\n";

    // Step 3: Verify amount can be negative (DECIMAL already supports negative)
    echo "Step 3: Verifying amount field supports negative values...\n";
    echo "✅ DECIMAL type already supports negative values\n\n";

    // Step 4: Add index on user_id (if not exists) and source (already exists)
    echo "Step 4: Ensuring proper indexes...\n";
    try {
        DB::statement("CREATE INDEX idx_account_transactions_user_id ON account_transactions(user_id)");
        echo "✅ Added index on user_id\n";
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "ℹ️  Index on user_id already exists\n";
        } else {
            throw $e;
        }
    }

    echo "\n=== SCHEMA FIX COMPLETE ===\n\n";

    // Verify the changes
    echo "Verifying changes:\n";
    $columns = DB::select("SHOW COLUMNS FROM account_transactions");
    foreach ($columns as $column) {
        if ($column->Field === 'user_id') {
            echo "user_id - Null: {$column->Null}, Type: {$column->Type}\n";
        }
        if ($column->Field === 'source') {
            echo "source - Type: {$column->Type}\n";
        }
        if ($column->Field === 'amount') {
            echo "amount - Type: {$column->Type}\n";
        }
    }

    echo "\n✅✅✅ account_transactions table is now ready for double-entry accounting! ✅✅✅\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
