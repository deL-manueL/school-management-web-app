<?php
if (getenv('ALLOW_SETUP_SCRIPTS') !== 'true') {
    http_response_code(403);
    exit('Forbidden');
}

require_once 'config.php';

echo "<h2>Database Connection Test</h2>";

$conn = getConnection();

// Check if admin_users table exists
$result = $conn->query("SHOW TABLES LIKE 'admin_users'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ admin_users table exists</p>";
    
    // Check admin users
    $adminCheck = $conn->query("SELECT id, username, email FROM admin_users");
    if ($adminCheck->num_rows > 0) {
        $admin = $adminCheck->fetch_assoc();
        echo "<p style='color: green;'>✅ Admin user found:</p>";
        echo "<ul>";
        echo "<li>ID: " . $admin['id'] . "</li>";
        echo "<li>Username: " . $admin['username'] . "</li>";
        echo "<li>Email: " . $admin['email'] . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ No admin users found! Run setup_admin.php</p>";
    }
} else {
    echo "<p style='color: red;'>❌ admin_users table does not exist! Run the database.sql script first.</p>";
}

$conn->close();
?>
