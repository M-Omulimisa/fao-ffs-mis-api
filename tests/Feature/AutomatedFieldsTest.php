<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Project;
use App\Models\ProjectTransaction;
use App\Models\ProjectShare;
use App\Models\User;
use App\Models\Disbursement;
use App\Models\AccountTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AutomatedFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        // Create test project
        $this->project = Project::create([
            'title' => 'Test Investment Project',
            'description' => 'Test project for automated fields',
            'start_date' => now(),
            'end_date' => now()->addMonths(12),
            'status' => 'ongoing',
            'share_price' => 10000,
            'total_shares' => 1000,
            'created_by_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function project_computed_fields_initialize_to_zero()
    {
        $this->assertEquals(0, $this->project->shares_sold);
        $this->assertEquals(0, $this->project->total_investment);
        $this->assertEquals(0, $this->project->total_returns);
        $this->assertEquals(0, $this->project->total_expenses);
        $this->assertEquals(0, $this->project->total_profits);
    }

    /** @test */
    public function project_share_purchase_updates_investment_and_shares_sold()
    {
        // Create a share purchase
        $share = ProjectShare::create([
            'project_id' => $this->project->id,
            'investor_id' => $this->user->id,
            'purchase_date' => now(),
            'number_of_shares' => 100,
            'share_price_at_purchase' => 10000,
            'total_amount_paid' => 1000000,
        ]);

        // Create corresponding transaction
        $transaction = ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 1000000,
            'transaction_date' => now(),
            'type' => 'income',
            'source' => 'share_purchase',
            'related_share_id' => $share->id,
            'created_by_id' => $this->user->id,
            'description' => 'Share purchase',
        ]);

        // Refresh project
        $this->project->refresh();

        // Assert shares_sold updated
        $this->assertEquals(100, $this->project->shares_sold);
        
        // Assert total_investment updated
        $this->assertEquals(1000000, $this->project->total_investment);
    }

    /** @test */
    public function project_expense_transaction_updates_total_expenses()
    {
        // Create expense transaction
        ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 50000,
            'transaction_date' => now(),
            'type' => 'expense',
            'source' => 'project_expense',
            'created_by_id' => $this->user->id,
            'description' => 'Project operating expense',
        ]);

        // Refresh project
        $this->project->refresh();

        // Assert total_expenses updated
        $this->assertEquals(50000, abs($this->project->total_expenses));
    }

    /** @test */
    public function project_profit_transaction_updates_total_profits()
    {
        // Create profit transaction
        ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 200000,
            'transaction_date' => now(),
            'type' => 'income',
            'source' => 'project_profit',
            'created_by_id' => $this->user->id,
            'description' => 'Project profit from sale',
        ]);

        // Refresh project
        $this->project->refresh();

        // Assert total_profits updated
        $this->assertEquals(200000, $this->project->total_profits);
    }

    /** @test */
    public function disbursement_creates_returns_transaction_and_updates_total_returns()
    {
        // First, create some investment
        ProjectShare::create([
            'project_id' => $this->project->id,
            'investor_id' => $this->user->id,
            'purchase_date' => now(),
            'number_of_shares' => 100,
            'share_price_at_purchase' => 10000,
            'total_amount_paid' => 1000000,
        ]);

        ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 1000000,
            'transaction_date' => now(),
            'type' => 'income',
            'source' => 'share_purchase',
            'created_by_id' => $this->user->id,
            'description' => 'Share purchase',
        ]);

        // Create disbursement
        $disbursement = Disbursement::create([
            'project_id' => $this->project->id,
            'amount' => 100000,
            'disbursement_date' => now(),
            'description' => 'Profit distribution',
            'created_by_id' => $this->user->id,
        ]);

        // Create returns distribution transaction
        ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => -100000,
            'transaction_date' => now(),
            'type' => 'expense',
            'source' => 'returns_distribution',
            'created_by_id' => $this->user->id,
            'description' => 'Returns distributed to investors',
        ]);

        // Refresh project
        $this->project->refresh();

        // Assert total_returns updated
        $this->assertEquals(100000, abs($this->project->total_returns));
    }

    /** @test */
    public function deleting_transaction_updates_project_totals()
    {
        // Create transaction
        $transaction = ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 50000,
            'transaction_date' => now(),
            'type' => 'income',
            'source' => 'project_profit',
            'created_by_id' => $this->user->id,
            'description' => 'Profit',
        ]);

        $this->project->refresh();
        $this->assertEquals(50000, $this->project->total_profits);

        // Delete transaction
        $transaction->delete();

        // Refresh project
        $this->project->refresh();

        // Assert total_profits back to zero
        $this->assertEquals(0, $this->project->total_profits);
    }

    /** @test */
    public function user_account_balance_updates_with_transactions()
    {
        // Initial balance should be 0
        $this->assertEquals(0, $this->user->account_balance);

        // Create credit transaction
        AccountTransaction::create([
            'user_id' => $this->user->id,
            'amount' => 50000,
            'transaction_date' => now(),
            'source' => 'disbursement',
            'description' => 'Profit disbursement',
            'created_by_id' => $this->user->id,
        ]);

        // Refresh user
        $this->user->refresh();

        // Assert balance increased
        $this->assertEquals(50000, $this->user->account_balance);

        // Create debit transaction
        AccountTransaction::create([
            'user_id' => $this->user->id,
            'amount' => -10000,
            'transaction_date' => now(),
            'source' => 'withdrawal',
            'description' => 'Withdrawal',
            'created_by_id' => $this->user->id,
        ]);

        // Refresh user
        $this->user->refresh();

        // Assert balance decreased
        $this->assertEquals(40000, $this->user->account_balance);
    }

    /** @test */
    public function multiple_transactions_compound_correctly()
    {
        // Create multiple transactions of different types
        ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 1000000,
            'transaction_date' => now(),
            'type' => 'income',
            'source' => 'share_purchase',
            'created_by_id' => $this->user->id,
            'description' => 'Investment 1',
        ]);

        ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 500000,
            'transaction_date' => now(),
            'type' => 'income',
            'source' => 'share_purchase',
            'created_by_id' => $this->user->id,
            'description' => 'Investment 2',
        ]);

        ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 200000,
            'transaction_date' => now(),
            'type' => 'income',
            'source' => 'project_profit',
            'created_by_id' => $this->user->id,
            'description' => 'Profit 1',
        ]);

        ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 150000,
            'transaction_date' => now(),
            'type' => 'income',
            'source' => 'project_profit',
            'created_by_id' => $this->user->id,
            'description' => 'Profit 2',
        ]);

        ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 50000,
            'transaction_date' => now(),
            'type' => 'expense',
            'source' => 'project_expense',
            'created_by_id' => $this->user->id,
            'description' => 'Expense 1',
        ]);

        ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 30000,
            'transaction_date' => now(),
            'type' => 'expense',
            'source' => 'project_expense',
            'created_by_id' => $this->user->id,
            'description' => 'Expense 2',
        ]);

        // Refresh project
        $this->project->refresh();

        // Assert all totals are correct
        $this->assertEquals(1500000, $this->project->total_investment); // 1M + 500K
        $this->assertEquals(350000, $this->project->total_profits);     // 200K + 150K
        $this->assertEquals(80000, abs($this->project->total_expenses)); // 50K + 30K
    }

    /** @test */
    public function recalculate_from_transactions_produces_accurate_results()
    {
        // Create transactions without triggering events (simulate corrupted data)
        DB::table('project_transactions')->insert([
            [
                'project_id' => $this->project->id,
                'amount' => 1000000,
                'transaction_date' => now(),
                'type' => 'income',
                'source' => 'share_purchase',
                'created_by_id' => $this->user->id,
                'description' => 'Investment',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_id' => $this->project->id,
                'amount' => 200000,
                'transaction_date' => now(),
                'type' => 'income',
                'source' => 'project_profit',
                'created_by_id' => $this->user->id,
                'description' => 'Profit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'project_id' => $this->project->id,
                'amount' => 50000,
                'transaction_date' => now(),
                'type' => 'expense',
                'source' => 'project_expense',
                'created_by_id' => $this->user->id,
                'description' => 'Expense',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Manually recalculate
        $this->project->recalculateFromTransactions();
        $this->project->refresh();

        // Assert all totals are correct
        $this->assertEquals(1000000, $this->project->total_investment);
        $this->assertEquals(200000, $this->project->total_profits);
        $this->assertEquals(50000, abs($this->project->total_expenses));
    }

    /** @test */
    public function transaction_updates_trigger_recalculation()
    {
        // Create transaction
        $transaction = ProjectTransaction::create([
            'project_id' => $this->project->id,
            'amount' => 100000,
            'transaction_date' => now(),
            'type' => 'income',
            'source' => 'project_profit',
            'created_by_id' => $this->user->id,
            'description' => 'Profit',
        ]);

        $this->project->refresh();
        $this->assertEquals(100000, $this->project->total_profits);

        // Update transaction amount
        $transaction->amount = 150000;
        $transaction->save();

        // Refresh project
        $this->project->refresh();

        // Assert updated amount reflected
        $this->assertEquals(150000, $this->project->total_profits);
    }
}
