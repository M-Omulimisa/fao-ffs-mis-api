<?php
// Test JWT extraction

$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTAuMC4yLjI6ODg4OC9mYW8tZmZzLW1pcy1hcGkvYXBpL3ZzbGEtb25ib2FyZGluZy9yZWdpc3Rlci1hZG1pbiIsImlhdCI6MTc2NTMxNTUwMSwiZXhwIjoyNzExMzk1NTAxLCJuYmYiOjE3NjUzMTU1MDEsImp0aSI6IjNhZ29DcFNXNDQ1dXgxTlUiLCJzdWIiOiIyMTQiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.brw1ielmM0W5xExQC1xfFYBTqKoa4eCY3uVV65orJPY";

echo "=== JWT TOKEN EXTRACTION TEST ===\n\n";

$parts = explode('.', $token);
echo "Token parts: " . count($parts) . "\n\n";

if (count($parts) === 3) {
    echo "Header: " . $parts[0] . "\n";
    echo "Payload: " . $parts[1] . "\n";
    echo "Signature: " . substr($parts[2], 0, 20) . "...\n\n";
    
    // Decode payload
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
    
    echo "Decoded Payload:\n";
    print_r($payload);
    
    echo "\nUser ID (sub): " . $payload['sub'] . "\n";
    echo "\n✅ JWT extraction works!\n";
    echo "Middleware will extract user_id = " . $payload['sub'] . " from the token\n";
}
