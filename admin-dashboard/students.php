<?php
require_once 'config.php';
requireAdminLogin();

$conn = getConnection();
$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $student_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    if ($stmt->execute()) {
        $message = "Student deleted successfully";
    } else {
        $error = "Error deleting student";
    }
    $stmt->close();
}

// Search
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM students";
$params = [];
$types = '';
if ($search) {
    $searchLike = '%' . $search . '%';
    $query .= " WHERE student_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?";
    $params = [$searchLike, $searchLike, $searchLike, $searchLike];
    $types = 'ssss';
}
$query .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - IPMC Admin</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Manage Students</h1>
                    <p>View, search, and manage student records</p>
                </div>
            </div>

            <div class="form-container">
                <div class="search-bar">
                    <form method="GET" style="flex: 1; display: flex; gap: 10px;">
                        <input type="text" name="search" placeholder="Search by ID, Name, or Email..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-primary">Search</button>
                        <?php if($search): ?>
                            <a href="students.php" class="btn-primary" style="background: #666;">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Program</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($student = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                <td><?php echo htmlspecialchars($student['program']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                <td>
                                    <button class="btn-edit" onclick="viewStudent('<?php echo $student['student_id']; ?>')">View</button>
                                    <button class="btn-danger" onclick="deleteStudent('<?php echo $student['student_id']; ?>')">Delete</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($students->num_rows == 0): ?>
                                <tr><td colspan="7" class="text-center">No students found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="studentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Student Details</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="studentDetails">
                Loading...
            </div>
        </div>
    </div>

    <script>
        function viewStudent(studentId) {
            const modal = document.getElementById('studentModal');
            const details = document.getElementById('studentDetails');
            details.innerHTML = 'Loading...';
            modal.style.display = 'flex';

            fetch(`api/get_student_data_admin.php?student_id=${encodeURIComponent(studentId)}`, { credentials: 'include' })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.student) {
                        const s = data.data.student;
                        const results = data.data.results || [];
                        const gpa = data.data.gpa;
                        details.innerHTML = `
                            <p><strong>Student ID:</strong> ${s.student_id}</p>
                            <p><strong>Name:</strong> ${s.first_name} ${s.last_name}</p>
                            <p><strong>Email:</strong> ${s.email}</p>
                            <p><strong>Phone:</strong> ${s.phone}</p>
                            <p><strong>Program:</strong> ${s.program}</p>
                            <p><strong>Registered:</strong> ${new Date(s.created_at).toLocaleDateString()}</p>
                            <hr>
                            <h4>Academic Summary</h4>
                            <p><strong>Courses Taken:</strong> ${results.length}</p>
                            <p><strong>GPA:</strong> ${gpa !== undefined && gpa !== null ? gpa : 'Not available'}</p>
                        `;
                    } else {
                        details.innerHTML = `<p style="color:#c33;">${data.message || 'Failed to load student details.'}</p>`;
                    }
                })
                .catch(err => {
                    console.error('viewStudent error:', err);
                    details.innerHTML = '<p style="color:#c33;">Error loading student details. Please try again.</p>';
                });
        }

        function deleteStudent(studentId) {
            if(confirm('Are you sure you want to delete this student? This will also delete all associated results and grievances.')) {
                window.location.href = `students.php?delete=${studentId}`;
            }
        }

        document.querySelector('.close').onclick = function() {
            document.getElementById('studentModal').style.display = 'none';
        }
    </script>
</body>
</html>
