<?php
require_once '../config.php';

configureCors('GET, OPTIONS');
requireMethod('GET');
requireAdminSession();

$student_id = trim($_GET['student_id'] ?? '');

if (empty($student_id)) {
    apiResponse(false, 'Student ID is required', null, 400);
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT student_id, first_name, last_name, email, phone, program, created_at FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    apiResponse(false, 'Student not found', ['student' => null, 'results' => [], 'gpa' => 0], 404);
}

$stmt2 = $conn->prepare("SELECT * FROM results WHERE student_id = ? ORDER BY academic_year DESC, semester ASC");
$stmt2->bind_param("s", $student_id);
$stmt2->execute();
$results = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

$conn->close();

apiResponse(true, 'Student data retrieved', [
    'student' => $student,
    'results' => $results,
    'gpa' => calculateGPA($results)
]);
?>
