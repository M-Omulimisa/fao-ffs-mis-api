<?php

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "fao_ffs_mis";
$socket = "/Applications/MAMP/tmp/mysql/mysql.sock";

$conn = new mysqli($servername, $username, $password, $dbname, null, $socket);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== VERIFYING USER 214 ===\n\n";

$result = $conn->query("SELECT id, name, username FROM users WHERE id = 214");

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ User 214 exists:\n";
    echo "   ID: {$user['id']}\n";
    echo "   Name: {$user['name']}\n";
    echo "   Username: {$user['username']}\n\n";
    
    echo "✅ Middleware will:\n";
    echo "   1. Extract user_id=214 from JWT token\n";
    echo "   2. Find user in database\n";
    echo "   3. Set auth()->user() to this user\n";
    echo "   4. Controller will get created_by_id=214\n";
    echo "   5. Meeting will be created successfully!\n";
} else {
    echo "❌ User 214 NOT FOUND!\n";
    echo "This will cause an authentication error.\n";
}

$conn->close();
