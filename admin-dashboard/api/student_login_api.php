<?php
require_once '../config.php';

configureCors('POST, OPTIONS');
startSession();

requireMethod('POST');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    apiResponse(false, 'Invalid data received', null, 400);
}

$student_id = sanitizeInput($data['student_id'] ?? '');
$password = $data['password'] ?? '';

if (empty($student_id) || empty($password)) {
    apiResponse(false, 'Student ID and password are required', null, 400);
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT student_id, first_name, last_name, password FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        $_SESSION['student_logged_in'] = true;
        $_SESSION['student_id'] = $row['student_id'];
        $_SESSION['student_name'] = $row['first_name'] . ' ' . $row['last_name'];
        
        apiResponse(true, 'Login successful', [
            'student_id' => $row['student_id'],
            'student_name' => $row['first_name'] . ' ' . $row['last_name']
        ]);
    } else {
        apiResponse(false, 'Invalid credentials', null, 401);
    }
} else {
    apiResponse(false, 'Invalid credentials', null, 401);
}

$stmt->close();
$conn->close();
?>
