<?php
if (getenv('ALLOW_SETUP_SCRIPTS') !== 'true') {
    http_response_code(403);
    exit('Forbidden');
}

require_once 'config.php';

$conn = getConnection();

echo "<h2>Results Database Check</h2>";

// Check all results
$result = $conn->query("SELECT * FROM results");
echo "<h3>Total Results in Database: " . $result->num_rows . "</h3>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Course Code</th><th>Course Name</th><th>Grade</th><th>Credits</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['student_id']}</td>";
        echo "<td>{$row['course_code']}</td>";
        echo "<td>{$row['course_name']}</td>";
        echo "<td>{$row['grade']}</td>";
        echo "<td>{$row['credits']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No results found in database! Please upload some results first.</p>";
}

// Check specific student
echo "<h3>Check Specific Student:</h3>";
echo "<form method='GET'>";
echo "<input type='text' name='student_id' placeholder='Enter Student ID (e.g., IPMC2024001)'>";
echo "<button type='submit'>Check</button>";
echo "</form>";

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    $stmt = $conn->prepare("SELECT * FROM results WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $results = $stmt->get_result();
    
    echo "<h4>Results for $student_id: " . $results->num_rows . " records</h4>";
    if ($results->num_rows > 0) {
        while ($row = $results->fetch_assoc()) {
            echo "<p>✓ {$row['course_code']} - {$row['course_name']} - Grade: {$row['grade']}</p>";
        }
    } else {
        echo "<p style='color: red;'>No results found for this student ID. Make sure you're using the exact Student ID format.</p>";
    }
}

$conn->close();
?>
