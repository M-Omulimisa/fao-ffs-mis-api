<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$meeting = App\Models\VslaMeeting::find(1);
$service = new App\Services\MeetingProcessingService();

try {
    $result = $service->processMeeting($meeting);
    
    echo "Success: " . ($result['success'] ? 'Yes' : 'No') . PHP_EOL;
    echo "Errors: " . count($result['errors']) . PHP_EOL;
    echo "Warnings: " . count($result['warnings']) . PHP_EOL;
    
    if (!empty($result['errors'])) {
        echo "\nFirst Error:\n";
        echo "  Type: " . $result['errors'][0]['type'] . PHP_EOL;
        echo "  Message: " . $result['errors'][0]['message'] . PHP_EOL;
    }
    
    echo "\nLoans created for meeting 1: " . App\Models\VslaLoan::where('meeting_id', 1)->count() . PHP_EOL;
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}
