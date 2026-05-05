<?php
if (getenv('ALLOW_SETUP_SCRIPTS') !== 'true') {
    http_response_code(403);
    exit('Forbidden');
}

// Run this file: http://localhost/admin-dashboard/setup_admin.php
// This will fix the admin login credentials

require_once 'config.php';

$conn = getConnection();

// New credentials
$username = 'admin';
$email = getenv('ADMIN_EMAIL') ?: '';
$password = getenv('ADMIN_PASSWORD') ?: '';

if ($email === '' || $password === '') {
    http_response_code(400);
    exit('ADMIN_EMAIL and ADMIN_PASSWORD must be set');
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// First, delete existing admin users
$conn->query("DELETE FROM admin_users");

// Insert new admin
$stmt = $conn->prepare("INSERT INTO admin_users (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hashed_password);

if ($stmt->execute()) {
    echo "<div style='background: #e8f5e9; color: #2e7d32; padding: 20px; border-radius: 10px; margin: 20px;'>";
    echo "<h2>✅ Admin Setup Complete!</h2>";
    echo "<p>Admin credentials were loaded from environment variables.</p>";
    echo "<hr>";
    echo "<p>You can now login at: <a href='index.php'>Admin Login Page</a></p>";
    echo "</div>";
} else {
    error_log('Setup admin DB error: ' . $conn->error);
    echo "<div style='background: #ffebee; color: #c62828; padding: 20px; border-radius: 10px; margin: 20px;'>";
    echo "<h2>❌ Error:</h2>";
    echo "<p>Database error occurred. Check server logs.</p>";
    echo "</div>";
}

$stmt->close();
$conn->close();
?>
