<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ProcessOrderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:process-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending order email jobs in background';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $jobsDir = storage_path('app/email_jobs');
            
            // Create directory if it doesn't exist
            if (!is_dir($jobsDir)) {
                mkdir($jobsDir, 0755, true);
                return 0;
            }
            
            // Get all job files
            $jobFiles = glob($jobsDir . '/*.json');
            
            foreach ($jobFiles as $jobFile) {
                try {
                    // Read job data
                    $jobData = json_decode(file_get_contents($jobFile), true);
                    
                    if (!$jobData || !isset($jobData['order_id'])) {
                        // Invalid job file, delete it
                        unlink($jobFile);
                        continue;
                    }
                    
                    $orderId = $jobData['order_id'];
                    $attempts = $jobData['attempts'] ?? 0;
                    
                    // Skip if too many attempts
                    if ($attempts >= 3) {
                        Log::warning('Skipping email job for order ' . $orderId . ' - too many attempts');
                        unlink($jobFile);
                        continue;
                    }
                    
                    // Find the order
                    $order = Order::find($orderId);
                    if (!$order) {
                        Log::warning('Order not found for email job: ' . $orderId);
                        unlink($jobFile);
                        continue;
                    }
                    
                    // Process the email
                    Log::info('Processing email job for order: ' . $orderId);
                    Order::send_mails($order);
                    
                    // Delete successful job
                    unlink($jobFile);
                    Log::info('Email job completed for order: ' . $orderId);
                    
                } catch (\Throwable $th) {
                    Log::error('Error processing email job ' . basename($jobFile) . ': ' . $th->getMessage());
                    
                    // Increment attempt count and update job file
                    try {
                        $jobData['attempts'] = ($jobData['attempts'] ?? 0) + 1;
                        $jobData['last_error'] = $th->getMessage();
                        $jobData['last_attempt'] = time();
                        
                        if ($jobData['attempts'] < 3) {
                            file_put_contents($jobFile, json_encode($jobData));
                        } else {
                            // Too many attempts, delete the job
                            unlink($jobFile);
                            Log::error('Deleting failed email job for order ' . ($jobData['order_id'] ?? 'unknown') . ' after 3 attempts');
                        }
                    } catch (\Throwable $deleteError) {
                        Log::error('Failed to update job file: ' . $deleteError->getMessage());
                    }
                }
                
                // Small delay to prevent overwhelming the system
                usleep(100000); // 0.1 second delay
            }
            
        } catch (\Throwable $th) {
            Log::error('Error in ProcessOrderEmails command: ' . $th->getMessage());
        }
        
        return 0;
    }
}
