<?php
if (getenv('ALLOW_SETUP_SCRIPTS') !== 'true') {
    http_response_code(403);
    exit('Forbidden');
}

require_once 'config.php';

$conn = getConnection();

echo "<h1>System Debug Report</h1>";

// Check Students
echo "<h2>1. Students Table</h2>";
$students = $conn->query("SELECT student_id, first_name, last_name, email FROM students");
echo "<p>Total Students: " . $students->num_rows . "</p>";
while($s = $students->fetch_assoc()) {
    echo "✓ {$s['student_id']} - {$s['first_name']} {$s['last_name']}<br>";
}

// Check Results
echo "<h2>2. Results Table</h2>";
$results = $conn->query("SELECT * FROM results");
echo "<p>Total Results: " . $results->num_rows . "</p>";
if($results->num_rows > 0) {
    while($r = $results->fetch_assoc()) {
        echo "✓ {$r['student_id']} - {$r['course_code']} - {$r['course_name']} - Grade: {$r['grade']}<br>";
    }
} else {
    echo "<p style='color: red'>❌ NO RESULTS FOUND! Please upload some results from admin panel.</p>";
}

// Check Grievances
echo "<h2>3. Grievances Table</h2>";
$grievances = $conn->query("SELECT * FROM grievances");
echo "<p>Total Grievances: " . $grievances->num_rows . "</p>";
if($grievances->num_rows > 0) {
    while($g = $grievances->fetch_assoc()) {
        echo "✓ #{$g['id']} - {$g['student_id']} - {$g['title']} - Status: {$g['status']}<br>";
    }
} else {
    echo "<p style='color: red'>❌ NO GRIEVANCES FOUND! Submit one from student portal.</p>";
}

// API Test
echo "<h2>4. API Test</h2>";
echo "<p>Test with a specific Student ID:</p>";
echo "<form method='GET'>";
echo "<input type='text' name='test_student' placeholder='Enter Student ID'>";
echo "<button type='submit'>Test API</button>";
echo "</form>";

if(isset($_GET['test_student'])) {
    $test_id = $_GET['test_student'];
    echo "<h3>Testing API for: $test_id</h3>";
    
    // Test get_student_data API
    $url = "http://localhost/FINAL%20PROJECT/admin-dashboard/api/get_student_data.php?student_id=" . urlencode($test_id);
    $data = file_get_contents($url);
    echo "<p><strong>get_student_data.php response:</strong></p>";
    echo "<pre>" . print_r(json_decode($data, true), true) . "</pre>";
    
    // Test get_student_results API
    $url2 = "http://localhost/FINAL%20PROJECT/admin-dashboard/api/get_student_results.php?student_id=" . urlencode($test_id);
    $data2 = file_get_contents($url2);
    echo "<p><strong>get_student_results.php response:</strong></p>";
    echo "<pre>" . print_r(json_decode($data2, true), true) . "</pre>";
}

$conn->close();
?>
