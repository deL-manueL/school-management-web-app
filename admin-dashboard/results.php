<?php
require_once 'config.php';
requireAdminLogin();

$conn = getConnection();
$message = '';
$error = '';

// Get students for dropdown
$students = $conn->query("SELECT student_id, first_name, last_name FROM students ORDER BY first_name");

// Handle Result Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_result'])) {
    $student_id = $_POST['student_id'];
    $course_code = sanitizeInput($_POST['course_code']);
    $course_name = sanitizeInput($_POST['course_name']);
    $semester = sanitizeInput($_POST['semester']);
    $academic_year = sanitizeInput($_POST['academic_year']);
    $grade = $_POST['grade'];
    $credits = intval($_POST['credits']);
    $score = !empty($_POST['score']) ? floatval($_POST['score']) : null;
    
    $stmt = $conn->prepare("INSERT INTO results (student_id, course_code, course_name, semester, academic_year, grade, credits, score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssid", $student_id, $course_code, $course_name, $semester, $academic_year, $grade, $credits, $score);
    
    if ($stmt->execute()) {
        $message = "Result uploaded successfully!";
    } else {
        $error = "Error uploading result: " . $conn->error;
    }
    $stmt->close();
}

// Handle Bulk Upload via CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $csvFile = $_FILES['csv_file']['tmp_name'];
    if (($handle = fopen($csvFile, "r")) !== false) {
        $header = fgetcsv($handle);
        $successCount = 0;
        $errorCount = 0;
        
        while (($data = fgetcsv($handle)) !== false) {
            $stmt = $conn->prepare("INSERT INTO results (student_id, course_code, course_name, semester, academic_year, grade, credits, score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssid", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7]);
            if ($stmt->execute()) {
                $successCount++;
            } else {
                $errorCount++;
            }
            $stmt->close();
        }
        fclose($handle);
        $message = "$successCount results uploaded successfully. $errorCount failed.";
    }
}

// Get all results
$search = $_GET['search'] ?? '';
$query = "SELECT r.*, s.first_name, s.last_name FROM results r JOIN students s ON r.student_id = s.student_id";
$params = [];
$types = '';
if ($search) {
    $searchLike = '%' . $search . '%';
    $query .= " WHERE r.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR r.course_code LIKE ?";
    $params = [$searchLike, $searchLike, $searchLike, $searchLike];
    $types = 'ssss';
}
$query .= " ORDER BY r.created_at DESC";
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Results - IPMC Admin</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Upload Student Results</h1>
                    <p>Manage academic results for students</p>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <h3><i class="fas fa-plus-circle"></i> Upload Single Result</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Select Student *</label>
                            <select name="student_id" required>
                                <option value="">Select Student</option>
                                <?php while($student = $students->fetch_assoc()): ?>
                                    <option value="<?php echo $student['student_id']; ?>">
                                        <?php echo $student['student_id'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Course Code *</label>
                            <input type="text" name="course_code" placeholder="e.g., CS101" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Course Name *</label>
                            <input type="text" name="course_name" placeholder="e.g., Introduction to Programming" required>
                        </div>
                        <div class="form-group">
                            <label>Semester *</label>
                            <select name="semester" required>
                                <option value="Semester 1">Semester 1</option>
                                <option value="Semester 2">Semester 2</option>
                                <option value="Semester 3">Semester 3</option>
                                <option value="Semester 4">Semester 4</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Academic Year *</label>
                            <input type="text" name="academic_year" placeholder="e.g., 2024/2025" required>
                        </div>
                        <div class="form-group">
                            <label>Grade *</label>
                            <select name="grade" required>
                                <option value="A">A (Excellent)</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B">B (Good)</option>
                                <option value="B-">B-</option>
                                <option value="C+">C+</option>
                                <option value="C">C (Average)</option>
                                <option value="D">D (Poor)</option>
                                <option value="F">F (Fail)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Credits *</label>
                            <input type="number" name="credits" min="1" max="6" required>
                        </div>
                        <div class="form-group">
                            <label>Score (%)</label>
                            <input type="number" name="score" step="0.01" min="0" max="100" placeholder="Optional">
                        </div>
                    </div>
                    <button type="submit" name="upload_result" class="btn-primary">Upload Result</button>
                </form>
            </div>

            <div class="form-container">
                <h3><i class="fas fa-file-csv"></i> Bulk Upload (CSV)</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>CSV File</label>
                        <input type="file" name="csv_file" accept=".csv" required>
                        <small>Format: student_id, course_code, course_name, semester, academic_year, grade, credits, score</small>
                    </div>
                    <button type="submit" class="btn-primary">Upload CSV</button>
                    <a href="sample_results.csv" class="btn-primary" style="background: #666;">Download Sample CSV</a>
                </form>
            </div>

            <div class="form-container">
                <h3><i class="fas fa-list"></i> All Results</h3>
                <div class="search-bar">
                    <form method="GET" style="flex: 1; display: flex; gap: 10px;">
                        <input type="text" name="search" placeholder="Search by Student ID, Name, or Course Code..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-primary">Search</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Semester</th>
                                <th>Grade</th>
                                <th>Credits</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($result = $results->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($result['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['semester']); ?></td>
                                <td class="grade-<?php echo strtolower($result['grade']); ?>"><?php echo $result['grade']; ?></td>
                                <td><?php echo $result['credits']; ?></td>
                                <td><?php echo $result['score'] ? $result['score'] . '%' : '-'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($result['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
