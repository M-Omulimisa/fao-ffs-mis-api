<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PesapalConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds sample Pesapal data for testing and development
     *
     * @return void
     */
    public function run()
    {
        // Create sample orders for Pesapal testing
        $sampleOrders = [
            [
                'id' => 9999,
                'user' => 1,
                'order_state' => 0,
                'temporary_id' => 0,
                'amount' => 25000.00,
                'order_total' => 25000.00,
                'payment_confirmation' => '',
                'payment_gateway' => 'pesapal',
                'payment_status' => 'PENDING_PAYMENT',
                'description' => 'Sample Pesapal test order',
                'mail' => 'test@blitxpress.com',
                'customer_name' => 'Test User',
                'customer_phone_number_1' => '+256700000000',
                'customer_address' => 'Test Address, Kampala',
                'delivery_district' => 'Kampala',
                'order_details' => json_encode([
                    'phone_number' => '+256700000000',
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'current_address' => 'Test Address, Kampala'
                ]),
                'date_created' => now(),
                'date_updated' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($sampleOrders as $order) {
            try {
                DB::table('orders')->insertOrIgnore($order);
                $this->command->info("Created sample order #{$order['id']} for Pesapal testing");
            } catch (\Exception $e) {
                $this->command->warn("Order #{$order['id']} already exists or failed to create");
            }
        }

        // Create sample Pesapal transactions
        $sampleTransactions = [
            [
                'order_id' => '9999',
                'order_tracking_id' => 'sample-tracking-' . uniqid(),
                'merchant_reference' => 'BLX-TEST-9999-' . date('Ymd'),
                'amount' => '25000.00',
                'currency' => 'UGX',
                'status' => 'PENDING',
                'payment_method' => null,
                'payment_account' => null,
                'confirmation_code' => null,
                'description' => 'Sample Pesapal transaction for testing',
                'callback_url' => url('/api/pesapal/callback'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($sampleTransactions as $transaction) {
            try {
                DB::table('pesapal_transactions')->insertOrIgnore($transaction);
                $this->command->info("Created sample Pesapal transaction for order #{$transaction['order_id']}");
            } catch (\Exception $e) {
                $this->command->warn("Sample transaction for order #{$transaction['order_id']} already exists");
            }
        }

        // Create sample IPN logs
        $sampleIpnLogs = [
            [
                'order_tracking_id' => 'sample-tracking-ipn-' . uniqid(),
                'merchant_reference' => 'BLX-TEST-IPN-' . date('Ymd'),
                'notification_type' => 'IPNCHANGE',
                'request_method' => 'POST',
                'payload' => json_encode([
                    'OrderTrackingId' => 'sample-tracking-ipn',
                    'OrderMerchantReference' => 'BLX-TEST-IPN',
                    'OrderNotificationType' => 'IPNCHANGE'
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Pesapal/Test',
                'status' => 'PROCESSED',
                'response_sent' => json_encode([
                    'orderNotificationType' => 'IPNCHANGE',
                    'orderTrackingId' => 'sample-tracking-ipn',
                    'status' => 200
                ]),
                'error_message' => null,
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($sampleIpnLogs as $ipnLog) {
            try {
                DB::table('pesapal_ipn_logs')->insertOrIgnore($ipnLog);
                $this->command->info("Created sample IPN log entry");
            } catch (\Exception $e) {
                $this->command->warn("Sample IPN log already exists");
            }
        }

        $this->command->info('Pesapal sample data seeded successfully!');
        $this->command->line('');
        $this->command->line('You can now test with:');
        $this->command->line('- Order ID: 9999');
        $this->command->line('- php artisan pesapal:test');
        $this->command->line('- GET /api/pesapal/status/9999');
    }
}
