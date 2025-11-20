<?php

namespace App\Console\Commands;

use App\Config\PesapalConfig;
use App\Exceptions\PesapalException;
use App\Services\PesapalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PesapalTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pesapal:test {--detailed : Show detailed information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Pesapal payment gateway connectivity and configuration';

    protected $pesapalService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PesapalService $pesapalService)
    {
        parent::__construct();
        $this->pesapalService = $pesapalService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧪 Testing Pesapal Payment Gateway Integration...');
        $this->newLine();

        $detailed = $this->option('detailed');
        $allTestsPassed = true;

        // Test 1: Configuration
        $this->info('1️⃣ Testing Configuration...');
        $configTest = $this->testConfiguration($detailed);
        $allTestsPassed = $allTestsPassed && $configTest;

        // Test 2: Authentication
        $this->info('2️⃣ Testing Authentication...');
        $authTest = $this->testAuthentication($detailed);
        $allTestsPassed = $allTestsPassed && $authTest;

        // Test 3: IPN Registration
        $this->info('3️⃣ Testing IPN Registration...');
        $ipnTest = $this->testIpnRegistration($detailed);
        $allTestsPassed = $allTestsPassed && $ipnTest;

        // Test 4: Database Connection
        $this->info('4️⃣ Testing Database Models...');
        $dbTest = $this->testDatabaseModels($detailed);
        $allTestsPassed = $allTestsPassed && $dbTest;

        $this->newLine();
        
        if ($allTestsPassed) {
            $this->info('✅ All tests passed! Pesapal integration is ready.');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Some tests failed. Please check the configuration.');
            return Command::FAILURE;
        }
    }

    private function testConfiguration(bool $detailed): bool
    {
        try {
            $config = PesapalConfig::toArray();
            
            if (empty($config['consumer_key']) || strpos($config['consumer_key'], '***') === 0) {
                $this->error('   ❌ Consumer key not configured');
                return false;
            }

            if (empty($config['consumer_secret']) || strpos($config['consumer_secret'], '***') === 0) {
                $this->error('   ❌ Consumer secret not configured');
                return false;
            }

            $this->line('   ✅ Configuration loaded successfully');
            
            if ($detailed) {
                $this->table(['Setting', 'Value'], [
                    ['Environment', $config['environment']],
                    ['Base URL', $config['base_url']],
                    ['IPN URL', $config['ipn_url']],
                    ['Callback URL', $config['callback_url']],
                    ['Default Currency', $config['default_currency']],
                ]);
            }

            return true;
        } catch (\Exception $e) {
            $this->error('   ❌ Configuration error: ' . $e->getMessage());
            return false;
        }
    }

    private function testAuthentication(bool $detailed): bool
    {
        try {
            $token = $this->pesapalService->getAuthToken();
            
            if (empty($token)) {
                $this->error('   ❌ Failed to get authentication token');
                return false;
            }

            $this->line('   ✅ Authentication successful');
            
            if ($detailed) {
                $this->line('   Token: ' . substr($token, 0, 20) . '...');
            }

            return true;
        } catch (PesapalException $e) {
            $this->error('   ❌ Authentication failed: ' . $e->getMessage());
            if ($detailed) {
                $this->error('   Details: ' . $e->getPesapalCode());
            }
            return false;
        } catch (\Exception $e) {
            $this->error('   ❌ Authentication error: ' . $e->getMessage());
            return false;
        }
    }

    private function testIpnRegistration(bool $detailed): bool
    {
        try {
            $response = $this->pesapalService->registerIpnUrl();
            
            if (empty($response['ipn_id'])) {
                $this->error('   ❌ Failed to register IPN URL');
                return false;
            }

            $this->line('   ✅ IPN registration successful');
            
            if ($detailed) {
                $this->table(['Field', 'Value'], [
                    ['IPN ID', $response['ipn_id']],
                    ['URL', $response['url']],
                    ['Notification Type', $response['ipn_notification_type']],
                    ['Status', $response['ipn_status']],
                ]);
            }

            return true;
        } catch (PesapalException $e) {
            $this->error('   ❌ IPN registration failed: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->error('   ❌ IPN registration error: ' . $e->getMessage());
            return false;
        }
    }

    private function testDatabaseModels(bool $detailed): bool
    {
        try {
            // Test PesapalTransaction model
            $transactionCount = \App\Models\PesapalTransaction::count();
            
            // Test PesapalIpnLog model
            $ipnCount = \App\Models\PesapalIpnLog::count();
            
            // Test Order model with Pesapal relationship
            $orderCount = \App\Models\Order::count();

            $this->line('   ✅ Database models working correctly');
            
            if ($detailed) {
                $this->table(['Model', 'Count'], [
                    ['PesapalTransaction', $transactionCount],
                    ['PesapalIpnLog', $ipnCount],
                    ['Orders', $orderCount],
                ]);
            }

            return true;
        } catch (\Exception $e) {
            $this->error('   ❌ Database model error: ' . $e->getMessage());
            return false;
        }
    }
}
