<?php
require_once 'config.php';

startSession();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$conn = getConnection();
$message = '';
$error = '';

// Handle Add/Edit Course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course'])) {
        $course_code = sanitizeInput($_POST['course_code']);
        $course_name = sanitizeInput($_POST['course_name']);
        $category = sanitizeInput($_POST['category']);
        $description = sanitizeInput($_POST['description']);
        $duration = sanitizeInput($_POST['duration']);
        $fee = !empty($_POST['fee']) ? floatval($_POST['fee']) : null;
        $display_order = intval($_POST['display_order']);
        $show_in_contact = isset($_POST['show_in_contact']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, category, description, duration, fee, display_order, show_in_contact_dropdown) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssdii", $course_code, $course_name, $category, $description, $duration, $fee, $display_order, $show_in_contact);
        
        if ($stmt->execute()) {
            $message = "Course added successfully!";
        } else {
            $error = "Error adding course: " . $conn->error;
        }
        $stmt->close();
    }
    
    // Handle Update Course
    if (isset($_POST['update_course'])) {
        $id = intval($_POST['course_id']);
        $course_code = sanitizeInput($_POST['course_code']);
        $course_name = sanitizeInput($_POST['course_name']);
        $category = sanitizeInput($_POST['category']);
        $description = sanitizeInput($_POST['description']);
        $duration = sanitizeInput($_POST['duration']);
        $fee = !empty($_POST['fee']) ? floatval($_POST['fee']) : null;
        $status = sanitizeInput($_POST['status']);
        $display_order = intval($_POST['display_order']);
        $show_in_contact = isset($_POST['show_in_contact']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE courses SET course_code=?, course_name=?, category=?, description=?, duration=?, fee=?, status=?, display_order=?, show_in_contact_dropdown=? WHERE id=?");
        $stmt->bind_param("sssssdsiii", $course_code, $course_name, $category, $description, $duration, $fee, $status, $display_order, $show_in_contact, $id);
        
        if ($stmt->execute()) {
            $message = "Course updated successfully!";
        } else {
            $error = "Error updating course: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Delete Course
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Course deleted successfully!";
    } else {
        $error = "Error deleting course";
    }
    $stmt->close();
}

// Handle Toggle Contact Dropdown
if (isset($_GET['toggle_contact'])) {
    $id = intval($_GET['toggle_contact']);
    $current = $_GET['current'];
    $new = $current == '1' ? 0 : 1;
    $stmt = $conn->prepare("UPDATE courses SET show_in_contact_dropdown = ? WHERE id = ?");
    $stmt->bind_param("ii", $new, $id);
    $stmt->execute();
    $stmt->close();
    $message = "Course visibility in contact form updated!";
    header('Location: courses.php');
    exit;
}

// Get all courses
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$allowedCategories = ['career-dome', 'university', 'certification', 'short-courses'];
if ($category_filter && !in_array($category_filter, $allowedCategories, true)) {
    $category_filter = '';
}

$query = "SELECT * FROM courses WHERE 1=1";
$params = [];
$types = '';

if ($search) {
    $searchLike = '%' . $search . '%';
    $query .= " AND (course_name LIKE ? OR course_code LIKE ?)";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= 'ss';
}
if ($category_filter) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
    $types .= 's';
}
$query .= " ORDER BY display_order ASC, created_at DESC";
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$courses = $stmt->get_result();

// Get unique categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM courses ORDER BY category");

// Get course for editing
$edit_course = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_course = $result->fetch_assoc();
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - IPMC Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .sidebar {
            width: 280px;
            background: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 25px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header img {
            height: 50px;
            margin-bottom: 10px;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-item i {
            width: 25px;
            margin-right: 15px;
        }
        
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-item.logout {
            margin-top: 50px;
            color: #e74c3c;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 20px;
        }
        
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-title h1 {
            font-size: 24px;
            color: #333;
        }
        
        .page-title p {
            color: #666;
            margin-top: 5px;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
        }
        
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input {
            width: auto;
            margin: 0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-bar input, .search-bar select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .status-active {
            color: #27ae60;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .contact-badge-yes {
            background: #27ae60;
            color: white;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 11px;
            display: inline-block;
        }
        
        .contact-badge-no {
            background: #e74c3c;
            color: white;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 11px;
            display: inline-block;
        }
        
        .text-center {
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header h3,
            .nav-item span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/University College Logo.png" alt="IPMC Logo">
                <h3>IPMC Admin</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="students.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Manage Students</span>
                </a>
                <a href="courses.php" class="nav-item active">
                    <i class="fas fa-book"></i>
                    <span>Manage Courses</span>
                </a>
                <a href="contacts.php" class="nav-item">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Messages</span>
                </a>
                <a href="results.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Upload Results</span>
                </a>
                <a href="grievances.php" class="nav-item">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Manage Grievances</span>
                </a>
                <a href="api/logout_api.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Manage Courses</h1>
                    <p>Add, edit, or remove courses. Control which courses appear on the contact form.</p>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add/Edit Course Form -->
            <div class="form-container">
                <h3><?php echo $edit_course ? 'Edit Course' : 'Add New Course'; ?></h3>
                <form method="POST">
                    <?php if($edit_course): ?>
                        <input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Course Code *</label>
                            <input type="text" name="course_code" required value="<?php echo $edit_course['course_code'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Course Name *</label>
                            <input type="text" name="course_name" required value="<?php echo $edit_course['course_name'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category" required>
                                <option value="">Select Category</option>
                                <option value="career-dome" <?php echo (isset($edit_course) && $edit_course['category'] == 'career-dome') ? 'selected' : ''; ?>>Career Dome Programs</option>
                                <option value="university" <?php echo (isset($edit_course) && $edit_course['category'] == 'university') ? 'selected' : ''; ?>>University Programmes</option>
                                <option value="certification" <?php echo (isset($edit_course) && $edit_course['category'] == 'certification') ? 'selected' : ''; ?>>Certification Programs</option>
                                <option value="short-courses" <?php echo (isset($edit_course) && $edit_course['category'] == 'short-courses') ? 'selected' : ''; ?>>Short Courses</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Duration</label>
                            <input type="text" name="duration" placeholder="e.g., 6 months, 4 years" value="<?php echo $edit_course['duration'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Fee (GHS)</label>
                            <input type="number" name="fee" step="0.01" value="<?php echo $edit_course['fee'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Display Order</label>
                            <input type="number" name="display_order" value="<?php echo $edit_course['display_order'] ?? 0; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group checkbox-group">
                            <input type="checkbox" name="show_in_contact" id="show_in_contact" <?php echo (isset($edit_course) && $edit_course['show_in_contact_dropdown']) ? 'checked' : (isset($edit_course) ? '' : 'checked'); ?>>
                            <label for="show_in_contact" style="margin: 0;">Show in Contact Form Dropdown</label>
                        </div>
                        
                        <?php if($edit_course): ?>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" <?php echo $edit_course['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $edit_course['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Course description"><?php echo $edit_course['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <button type="submit" name="<?php echo $edit_course ? 'update_course' : 'add_course'; ?>" class="btn-primary">
                        <?php echo $edit_course ? 'Update Course' : 'Add Course'; ?>
                    </button>
                    <?php if($edit_course): ?>
                        <a href="courses.php" class="btn-primary" style="background: #666; text-decoration: none; margin-left: 10px;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Search and Filter -->
            <div class="form-container">
                <div class="search-bar">
                    <form method="GET" style="flex: 1; display: flex; gap: 10px; flex-wrap: wrap;">
                        <input type="text" name="search" placeholder="Search by course name or code..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php 
                            $categories_result = $conn->query("SELECT DISTINCT category FROM courses ORDER BY category");
                            while($cat = $categories_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $cat['category']; ?>" <?php echo $category_filter == $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('-', ' ', $cat['category'])); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" class="btn-primary">Search</button>
                        <?php if($search || $category_filter): ?>
                            <a href="courses.php" class="btn-primary" style="background: #666; text-decoration: none;">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Category</th>
                                <th>Duration</th>
                                <th>Fee (GHS)</th>
                                <th>Status</th>
                                <th>In Contact</th>
                                <th>Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($courses && $courses->num_rows > 0): ?>
                                <?php while($course = $courses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $course['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><?php echo ucfirst(str_replace('-', ' ', $course['category'])); ?></td>
                                    <td><?php echo $course['duration'] ?: '-'; ?></td>
                                    <td><?php echo $course['fee'] ? 'GHS ' . number_format($course['fee'], 2) : '-'; ?></td>
                                    <td>
                                        <span class="<?php echo $course['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo ucfirst($course['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($course['show_in_contact_dropdown']): ?>
                                            <span class="contact-badge-yes">Shown</span>
                                            <br>
                                            <a href="?toggle_contact=<?php echo $course['id']; ?>&current=1" class="btn-danger" style="font-size: 10px; margin-top: 5px; display: inline-block;">Hide</a>
                                        <?php else: ?>
                                            <span class="contact-badge-no">Hidden</span>
                                            <br>
                                            <a href="?toggle_contact=<?php echo $course['id']; ?>&current=0" class="btn-success" style="font-size: 10px; margin-top: 5px; display: inline-block;">Show</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $course['display_order']; ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $course['id']; ?>" class="btn-edit">Edit</a>
                                        <a href="?delete=<?php echo $course['id']; ?>" class="btn-danger" onclick="return confirm('Delete this course? This will remove it from the website and contact form.')">Delete</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="10" class="text-center">No courses found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 10px;">
                    <h4><i class="fas fa-info-circle"></i> About Course Management:</h4>
                    <ul style="margin-left: 20px; color: #666;">
                        <li>Courses marked as <strong>"Shown"</strong> will appear in the Contact Form dropdown on the website</li>
                        <li>You can quickly <strong>Show/Hide</strong> courses from the contact form using the buttons</li>
                        <li>Inactive courses won't be displayed on the website</li>
                        <li>Delete a course to permanently remove it from all systems</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
