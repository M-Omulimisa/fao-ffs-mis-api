<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Config\PesapalProductionConfig;

class PesapalProductionCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pesapal:production-check';

    /**
     * The console command description.
     */
    protected $description = 'Check if Pesapal integration is ready for production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking Pesapal Production Readiness...');
        $this->newLine();

        // Check current environment
        $isProduction = PesapalProductionConfig::isProduction();
        $environment = $isProduction ? 'Production' : 'Sandbox';
        
        $this->line("Current Environment: <fg=yellow>{$environment}</>");
        $this->newLine();

        // Run readiness checks
        $checks = PesapalProductionConfig::getProductionReadiness();
        $allPassed = true;

        foreach ($checks as $check => $result) {
            $status = $result['status'] ? '✅' : '❌';
            $this->line("{$status} {$result['message']}");
            
            if (!$result['status']) {
                $allPassed = false;
            }
        }

        $this->newLine();

        if ($isProduction) {
            if ($allPassed) {
                $this->info('🎉 Production environment is properly configured!');
            } else {
                $this->error('⚠️  Production environment has configuration issues.');
            }
        } else {
            $this->info('📋 Production Migration Steps:');
            $this->newLine();
            
            $steps = PesapalProductionConfig::getProductionMigrationSteps();
            
            foreach ($steps as $title => $commands) {
                $this->line("<fg=cyan>{$title}</>");
                foreach ($commands as $command) {
                    $this->line("   {$command}");
                }
                $this->newLine();
            }
        }

        // Display current configuration
        $this->info('📊 Current Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Environment', $environment],
                ['Currency', PesapalProductionConfig::getCurrency()],
                ['Country Code', PesapalProductionConfig::getCountryCode()],
                ['Transaction Limit', PesapalProductionConfig::getTransactionLimit() ? PesapalProductionConfig::getCurrency() . ' ' . number_format(PesapalProductionConfig::getTransactionLimit(), 2) : 'No Limit'],
                ['Consumer Key', config('services.pesapal.consumer_key') ? '***' . substr(config('services.pesapal.consumer_key'), -4) : 'Not Set'],
                ['IPN URL', config('services.pesapal.ipn_url') ?? 'Not Set'],
                ['Callback URL', config('services.pesapal.callback_url') ?? 'Not Set'],
            ]
        );

        return Command::SUCCESS;
    }
}
