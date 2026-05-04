<?php
require_once '../config.php';

configureCors('GET, OPTIONS');
requireMethod('GET');
$student_id = requireStudentSession();

$conn = getConnection();

// Get student info
$stmt = $conn->prepare("SELECT student_id, first_name, last_name, email, phone, program, created_at FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$studentResult = $stmt->get_result();
$student = $studentResult->fetch_assoc();

if (!$student) {
    apiResponse(false, 'Student not found', [
        'student' => null,
        'results' => [],
        'gpa' => 0
    ], 404);
}

// Get results
$stmt2 = $conn->prepare("SELECT * FROM results WHERE student_id = ? ORDER BY academic_year DESC, semester ASC");
$stmt2->bind_param("s", $student_id);
$stmt2->execute();
$results = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate GPA
$gpa = calculateGPA($results);

apiResponse(true, 'Student data retrieved', [
    'student' => $student,
    'results' => $results,
    'gpa' => $gpa
]);

$conn->close();
?>
