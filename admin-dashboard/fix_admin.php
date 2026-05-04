<?php
if (getenv('ALLOW_SETUP_SCRIPTS') !== 'true') {
    http_response_code(403);
    exit('Forbidden');
}

require_once 'config.php';

$conn = getConnection();
$message = '';

// First, ensure the table exists
$conn->query("CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Clear existing admin users
$conn->query("TRUNCATE TABLE admin_users");

// Set new admin credentials from environment only
$username = 'admin';
$email = getenv('ADMIN_EMAIL') ?: '';
$plain_password = getenv('ADMIN_PASSWORD') ?: '';

if ($email === '' || $plain_password === '') {
    http_response_code(400);
    exit('ADMIN_EMAIL and ADMIN_PASSWORD must be set');
}

// Generate proper password hash
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

// Insert the admin user
$stmt = $conn->prepare("INSERT INTO admin_users (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hashed_password);

if ($stmt->execute()) {
    $message = '<div style="background: #e8f5e9; color: #2e7d32; padding: 20px; border-radius: 10px; margin: 20px 0;">
        <h2>✅ Admin User Created Successfully!</h2>
        <p>Admin credentials were loaded from environment variables.</p>
        <hr>
        <p>You can now login at: <a href="index.php" style="color: #2e7d32;">Admin Login Page</a></p>
    </div>';
} else {
    $message = '<div style="background: #ffebee; color: #c62828; padding: 20px; border-radius: 10px;">
        <h2>❌ Error Creating Admin</h2>
        <p>' . $conn->error . '</p>
    </div>';
}

$stmt->close();

// Verify the password works
$verify_test = $conn->query("SELECT password FROM admin_users WHERE username = 'admin'");
if ($verify_test && $row = $verify_test->fetch_assoc()) {
    $test_verify = password_verify($plain_password, $row['password']);
    if ($test_verify) {
        $message .= '<div style="background: #e3f2fd; color: #1565c0; padding: 15px; border-radius: 10px; margin-top: 20px;">
            <p>✅ Password verification test: <strong>PASSED</strong></p>
            <p>The configured admin password verifies correctly.</p>
        </div>';
    } else {
        $message .= '<div style="background: #fff3e0; color: #f57c00; padding: 15px; border-radius: 10px; margin-top: 20px;">
            <p>⚠️ Password verification test: <strong>FAILED</strong></p>
            <p>There might be an issue with the password hashing.</p>
        </div>';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fix Admin Login - IPMC</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 50px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php echo $message; ?>
        <a href="index.php" class="btn">Go to Admin Login →</a>
    </div>
</body>
</html>
