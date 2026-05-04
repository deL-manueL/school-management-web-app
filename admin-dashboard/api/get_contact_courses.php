<?php
require_once '../config.php';

configureCors('GET, OPTIONS');
requireMethod('GET');

$conn = getConnection();

// Fetch active courses that should show in contact dropdown
$query = "SELECT id, course_code, course_name, category 
          FROM courses 
          WHERE status = 'active' AND show_in_contact_dropdown = 1 
          ORDER BY display_order ASC, course_name ASC";

$result = $conn->query($query);
$courses = [];

while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Also add "Other" option
apiResponse(true, 'Courses retrieved', ['courses' => $courses]);

$conn->close();
?>
