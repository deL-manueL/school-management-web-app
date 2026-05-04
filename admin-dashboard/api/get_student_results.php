<?php
require_once '../config.php';

configureCors('GET, OPTIONS');
requireMethod('GET');
$student_id = requireStudentSession();

$conn = getConnection();

// First, verify the student exists
$checkStmt = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE student_id = ?");
$checkStmt->bind_param("s", $student_id);
$checkStmt->execute();
$studentResult = $checkStmt->get_result();

if ($studentResult->num_rows === 0) {
    apiResponse(false, 'Student not found', ['results' => []], 404);
}

$student = $studentResult->fetch_assoc();

// Get results for the student
$stmt = $conn->prepare("SELECT * FROM results WHERE student_id = ? ORDER BY academic_year DESC, semester ASC, course_code");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$results = $stmt->get_result();

$resultsArray = [];
$totalPoints = 0;
$totalCredits = 0;
$gradePoints = ['A' => 4.0, 'A-' => 3.7, 'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7, 'C+' => 2.3, 'C' => 2.0, 'D' => 1.0, 'F' => 0.0];

while ($row = $results->fetch_assoc()) {
    $resultsArray[] = $row;
    $points = $gradePoints[$row['grade']] ?? 0;
    $totalPoints += $points * $row['credits'];
    $totalCredits += $row['credits'];
}

$gpa = $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0;

apiResponse(true, 'Results retrieved successfully', [
    'student' => $student,
    'results' => $resultsArray,
    'gpa' => $gpa,
    'total_credits' => $totalCredits,
    'total_courses' => count($resultsArray)
]);

$stmt->close();
$checkStmt->close();
$conn->close();
?>
