<?php
require_once '../config.php';

configureCors('POST, OPTIONS');
requireMethod('POST');
requireAdminSession();

$conn = getConnection();

// Get the data (supports both JSON and form-data)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if (empty($input)) {
    apiResponse(false, 'No data received', null, 400);
}

// Extract fields
$student_id = isset($input['student_id']) ? trim($input['student_id']) : '';
$course_code = isset($input['course_code']) ? trim($input['course_code']) : '';
$course_name = isset($input['course_name']) ? trim($input['course_name']) : '';
$semester = isset($input['semester']) ? trim($input['semester']) : '';
$academic_year = isset($input['academic_year']) ? trim($input['academic_year']) : '';
$grade = isset($input['grade']) ? strtoupper(trim($input['grade'])) : '';
$credits = isset($input['credits']) ? intval($input['credits']) : 0;
$score = isset($input['score']) && $input['score'] !== '' ? floatval($input['score']) : null;

// Validate
if (empty($student_id)) {
    apiResponse(false, 'Student ID is required', null, 400);
}

if (empty($course_code)) {
    apiResponse(false, 'Course code is required', null, 400);
}

if (empty($course_name)) {
    apiResponse(false, 'Course name is required', null, 400);
}

if (empty($grade)) {
    apiResponse(false, 'Grade is required', null, 400);
}

if ($credits <= 0) {
    apiResponse(false, 'Valid credits are required', null, 400);
}

// Check if student exists
$checkStudent = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
$checkStudent->bind_param("s", $student_id);
$checkStudent->execute();
$studentResult = $checkStudent->get_result();

if ($studentResult->num_rows === 0) {
    apiResponse(false, 'Student not found', null, 404);
}
$checkStudent->close();

// Check if result already exists for this student, course, semester, year
$checkStmt = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND course_code = ? AND semester = ? AND academic_year = ?");
$checkStmt->bind_param("ssss", $student_id, $course_code, $semester, $academic_year);
$checkStmt->execute();
$existingResult = $checkStmt->get_result();

if ($existingResult->num_rows > 0) {
    // Update existing result
    $row = $existingResult->fetch_assoc();
    $stmt = $conn->prepare("UPDATE results SET course_name = ?, grade = ?, credits = ?, score = ? WHERE id = ?");
    $stmt->bind_param("ssidi", $course_name, $grade, $credits, $score, $row['id']);
    $action = "updated";
} else {
    // Insert new result
    $stmt = $conn->prepare("INSERT INTO results (student_id, course_code, course_name, semester, academic_year, grade, credits, score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssid", $student_id, $course_code, $course_name, $semester, $academic_year, $grade, $credits, $score);
    $action = "added";
}

if ($stmt->execute()) {
    apiResponse(true, "Result $action successfully", [
        'student_id' => $student_id,
        'course_code' => $course_code
    ]);
} else {
    error_log('Upload result database error: ' . $conn->error);
    apiResponse(false, 'Internal server error', null, 500);
}

$stmt->close();
$conn->close();
?>
