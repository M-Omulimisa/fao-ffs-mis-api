<?php
/**
 * Simple test script for membership API
 * Run: php test_membership_api.php
 */

// Test user ID (John John)
$userId = 240;

echo "\n=====================================\n";
echo "Testing Membership API for User ID: $userId\n";
echo "=====================================\n\n";

// Make HTTP request to API
$url = "http://10.0.2.2:8888/dtehm-insurance-api/api/membership-status?user_id=$userId";

echo "URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'User-Id: ' . $userId
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
echo "\n\n";

// Decode and display key info
$data = json_decode($response, true);
if ($data && isset($data['data'])) {
    echo "Key Information:\n";
    echo "- User Name: " . ($data['data']['user_name'] ?? 'N/A') . "\n";
    echo "- User Type: " . ($data['data']['user_type'] ?? 'N/A') . "\n";
    echo "- Is Admin: " . (($data['data']['is_admin'] ?? false) ? 'YES' : 'NO') . "\n";
    echo "- Has Valid Membership: " . (($data['data']['has_valid_membership'] ?? false) ? 'YES' : 'NO') . "\n";
    echo "- Is Membership Paid: " . (($data['data']['is_membership_paid'] ?? false) ? 'YES' : 'NO') . "\n";
    echo "- Requires Payment: " . (($data['data']['requires_payment'] ?? true) ? 'YES' : 'NO') . "\n";
}

echo "\n=====================================\n";
echo "✅ EXPECTED: User should be BLOCKED\n";
echo "   (not admin, no membership paid)\n";
echo "=====================================\n\n";
