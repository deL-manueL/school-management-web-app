<?php
require_once 'config.php';

// Start session safely
startSession();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$conn = getConnection();

// Get statistics with error handling
$stats = [];

// Total students
$result = $conn->query("SELECT COUNT(*) as total FROM students");
$stats['students'] = $result ? $result->fetch_assoc()['total'] : 0;

// Total contacts
$result = $conn->query("SELECT COUNT(*) as total FROM contacts");
$stats['contacts'] = $result ? $result->fetch_assoc()['total'] : 0;

// Total results
$result = $conn->query("SELECT COUNT(*) as total FROM results");
$stats['results'] = $result ? $result->fetch_assoc()['total'] : 0;

// Total grievances
$result = $conn->query("SELECT COUNT(*) as total FROM grievances");
$stats['grievances'] = $result ? $result->fetch_assoc()['total'] : 0;

// Pending grievances
$result = $conn->query("SELECT COUNT(*) as total FROM grievances WHERE status = 'pending'");
$stats['pending_grievances'] = $result ? $result->fetch_assoc()['total'] : 0;

// Recent contacts
$recentContacts = $conn->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5");

// Recent grievances
$recentGrievances = $conn->query("
    SELECT g.*, s.first_name, s.last_name 
    FROM grievances g 
    JOIN students s ON g.student_id = s.student_id 
    ORDER BY g.created_at DESC 
    LIMIT 5
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IPMC</title>
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
        
        /* Sidebar */
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
        
        /* Main Content */
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        .stat-info h3 {
            font-size: 28px;
            color: #333;
        }
        
        .stat-info p {
            color: #666;
        }
        
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
        }
        
        .activity-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ddd;
        }
        
        .btn-link {
            color: #3498db;
            text-decoration: none;
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
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3e0;
            color: #f39c12;
        }
        
        .status-under_review {
            background: #e3f2fd;
            color: #3498db;
        }
        
        .status-resolved {
            background: #e8f5e9;
            color: #27ae60;
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .activity-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/University College Logo.png" alt="IPMC Logo" onerror="this.src='https://via.placeholder.com/50'">
                <h3>IPMC Admin</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="students.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Manage Students</span>
                </a>
                <a href="courses.php" class="nav-item">
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

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></p>
                </div>
                <div class="date-time">
                    <i class="far fa-calendar-alt"></i>
                    <span id="currentDateTime"></span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['students']; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['contacts']; ?></h3>
                        <p>Contact Messages</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['results']; ?></h3>
                        <p>Results Uploaded</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['grievances']; ?></h3>
                        <p>Total Grievances</p>
                        <small><?php echo $stats['pending_grievances']; ?> Pending</small>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="activity-grid">
                <div class="activity-card">
                    <div class="card-header">
                        <h3><i class="fas fa-envelope-open-text"></i> Recent Contact Messages</h3>
                        <a href="contacts.php" class="btn-link">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Name</th><th>Email</th><th>Program</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php if($recentContacts && $recentContacts->num_rows > 0): ?>
                                    <?php while($contact = $recentContacts->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                        <td><?php echo htmlspecialchars($contact['program'] ?: 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($contact['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">No messages yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="activity-card">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-circle"></i> Recent Grievances</h3>
                        <a href="grievances.php" class="btn-link">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Student</th><th>Title</th><th>Status</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php if($recentGrievances && $recentGrievances->num_rows > 0): ?>
                                    <?php while($grievance = $recentGrievances->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($grievance['first_name'] . ' ' . $grievance['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($grievance['title'], 0, 30)); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $grievance['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $grievance['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($grievance['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">No grievances yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateDateTime() {
            const now = new Date();
            document.getElementById('currentDateTime').textContent = now.toLocaleString();
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>
</body>
</html>
