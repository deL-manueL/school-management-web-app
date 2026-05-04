<?php
if (getenv('ALLOW_SETUP_SCRIPTS') !== 'true') {
    http_response_code(403);
    exit('Forbidden');
}

require_once 'config.php';

echo "<h2>Admin Database Check</h2>";

$conn = getConnection();

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'admin_users'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ admin_users table does NOT exist! Creating now...</p>";
    
    // Create the table
    $conn->query("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p style='color: green;'>✅ admin_users table created!</p>";
}

// Check existing admins
$result = $conn->query("SELECT id, username, email, password FROM admin_users");
echo "<h3>Current Admin Users:</h3>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<p>";
        echo "ID: " . $row['id'] . "<br>";
        echo "Username: " . $row['username'] . "<br>";
        echo "Email: " . $row['email'] . "<br>";
        echo "Password Hash: " . substr($row['password'], 0, 30) . "...<br>";
        echo "</p><hr>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No admin users found!</p>";
}

$conn->close();
?>

<h3>Fix Admin Login:</h3>
<a href="fix_admin.php" style="background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Click Here to Fix Admin Login</a>
