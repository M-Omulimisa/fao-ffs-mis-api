<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Testing Notification Grid Display\n\n";

try {
    // Test accessing the notification grid
    echo "📝 Testing if we can create a notification grid...\n";
    
    $controller = new \App\Admin\Controllers\NotificationController();
    
    echo "✅ Controller created successfully\n";
    
    // Try to call the grid method directly
    echo "📊 Testing grid creation...\n";
    
    // This should trigger the error if it still exists
    $grid = $controller->grid();
    
    echo "✅ Grid created successfully without errors!\n";
    echo "🎉 The notification dashboard should now work properly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    
    if (strpos($e->getMessage(), 'Too few arguments') !== false) {
        echo "\n🔧 This confirms the closure argument count issue.\n";
        echo "💡 The problem is in the display() function parameters.\n";
    }
}
