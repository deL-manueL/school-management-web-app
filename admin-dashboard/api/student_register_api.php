<?php
require_once '../config.php';

configureCors('POST, OPTIONS');

requireMethod('POST');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    apiResponse(false, 'Invalid data received', null, 400);
}

$first_name = sanitizeInput($data['first_name'] ?? '');
$last_name = sanitizeInput($data['last_name'] ?? '');
$email = sanitizeInput($data['email'] ?? '');
$phone = sanitizeInput($data['phone'] ?? '');
$program = sanitizeInput($data['program'] ?? '');
$password = $data['password'] ?? '';

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($program) || empty($password)) {
    apiResponse(false, 'All fields are required', null, 400);
}

if (!validateEmail($email)) {
    apiResponse(false, 'Invalid email address', null, 400);
}

if (strlen($password) < 6) {
    apiResponse(false, 'Password must be at least 6 characters', null, 400);
}

$conn = getConnection();

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    apiResponse(false, 'Email already registered', null, 400);
}
$stmt->close();

// Generate Student ID
$student_id = generateStudentId($conn);

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert student
$stmt = $conn->prepare("INSERT INTO students (student_id, first_name, last_name, email, phone, program, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $student_id, $first_name, $last_name, $email, $phone, $program, $hashed_password);

if ($stmt->execute()) {
    apiResponse(true, 'Registration successful!', [
        'student_id' => $student_id,
        'student_name' => $first_name . ' ' . $last_name
    ]);
} else {
    error_log('Student registration database error: ' . $conn->error);
    apiResponse(false, 'Internal server error', null, 500);
}

$stmt->close();
$conn->close();
?>
