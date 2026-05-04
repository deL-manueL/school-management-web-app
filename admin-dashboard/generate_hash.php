<?php
if (getenv('ALLOW_SETUP_SCRIPTS') !== 'true') {
    http_response_code(403);
    exit('Forbidden');
}

require_once 'config.php';

// Run this file: http://localhost/admin-dashboard/api/generate_hash.php
// Then copy the output to your database

$password = getenv('ADMIN_PASSWORD') ?: '';
if ($password === '') {
    http_response_code(400);
    exit('ADMIN_PASSWORD must be set');
}

$hash = password_hash($password, PASSWORD_DEFAULT);

echo 'Password hash generated successfully.';
?>
