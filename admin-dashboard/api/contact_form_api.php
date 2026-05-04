<?php
require_once '../config.php';

configureCors('POST, OPTIONS');

requireMethod('POST');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    apiResponse(false, 'Invalid data received', null, 400);
}

$first_name = sanitizeInput($data['firstName'] ?? '');
$last_name = sanitizeInput($data['lastName'] ?? '');
$email = sanitizeInput($data['email'] ?? '');
$phone = sanitizeInput($data['phone'] ?? '');
$program = sanitizeInput($data['program'] ?? '');
$campus = sanitizeInput($data['campus'] ?? '');
$message = sanitizeInput($data['message'] ?? '');

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($message)) {
    apiResponse(false, 'All required fields must be filled', null, 400);
}

if (!validateEmail($email)) {
    apiResponse(false, 'Invalid email address', null, 400);
}

$conn = getConnection();

$stmt = $conn->prepare("INSERT INTO contacts (first_name, last_name, email, phone, program, campus, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $program, $campus, $message);

if ($stmt->execute()) {
    apiResponse(true, 'Message sent successfully! We will contact you soon.');
} else {
    error_log('Contact form database error: ' . $conn->error);
    apiResponse(false, 'Internal server error', null, 500);
}

$stmt->close();
$conn->close();
?>
