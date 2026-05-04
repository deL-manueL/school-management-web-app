<?php
require_once '../config.php';

configureCors('POST, OPTIONS');
requireMethod('POST');
$student_id = requireStudentSession();

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

if (!$input || empty($input)) {
    apiResponse(false, 'No data received', null, 400);
}

// Extract and sanitize data
$category = isset($input['category']) ? trim($input['category']) : '';
$title = isset($input['title']) ? trim($input['title']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';

// Validate
if (empty($category)) {
    apiResponse(false, 'Category is required', null, 400);
}

if (empty($title)) {
    apiResponse(false, 'Title is required', null, 400);
}

if (empty($description)) {
    apiResponse(false, 'Description is required', null, 400);
}

$conn = getConnection();

// First verify student exists
$checkStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
$checkStmt->bind_param("s", $student_id);
$checkStmt->execute();
$studentCheck = $checkStmt->get_result();

if ($studentCheck->num_rows === 0) {
    apiResponse(false, 'Student not found', null, 404);
}
$checkStmt->close();

// Insert grievance
$stmt = $conn->prepare("INSERT INTO grievances (student_id, category, title, description, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
$stmt->bind_param("ssss", $student_id, $category, $title, $description);

if ($stmt->execute()) {
    $grievance_id = $conn->insert_id;
    apiResponse(true, 'Grievance submitted successfully! You will receive a response soon.', [
        'grievance_id' => $grievance_id
    ]);
} else {
    error_log('Submit grievance database error: ' . $conn->error);
    apiResponse(false, 'Internal server error', null, 500);
}

$stmt->close();
$conn->close();
?>
