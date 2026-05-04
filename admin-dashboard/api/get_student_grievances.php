<?php
require_once '../config.php';

configureCors('GET, OPTIONS');
requireMethod('GET');
$student_id = requireStudentSession();

$conn = getConnection();

// Get all grievances for this student including admin_response
$stmt = $conn->prepare("SELECT id, student_id, category, title, description, status, admin_response, created_at FROM grievances WHERE student_id = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$grievances = [];
while ($row = $result->fetch_assoc()) {
    $grievances[] = [
        'id' => $row['id'],
        'student_id' => $row['student_id'],
        'category' => $row['category'],
        'title' => $row['title'],
        'description' => $row['description'],
        'status' => $row['status'],
        'admin_response' => $row['admin_response'] ?? '',
        'created_at' => $row['created_at']
    ];
}

apiResponse(true, 'Grievances retrieved successfully', [
    'grievances' => $grievances,
    'count' => count($grievances)
]);

$stmt->close();
$conn->close();
?>
