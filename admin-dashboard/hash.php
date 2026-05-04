<?php
if (getenv('ALLOW_SETUP_SCRIPTS') !== 'true') {
    http_response_code(403);
    exit('Forbidden');
}

require_once 'config.php';

$password = getenv('ADMIN_PASSWORD') ?: '';
if ($password === '') {
    http_response_code(400);
    exit('ADMIN_PASSWORD must be set');
}

password_hash($password, PASSWORD_DEFAULT);
echo 'Password hash generated successfully.';
?>
