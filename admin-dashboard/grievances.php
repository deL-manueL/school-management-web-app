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

// Handle Reply to Grievance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_grievance'])) {
    $id = intval($_POST['grievance_id']);
    $status = $_POST['status'];
    $admin_response = sanitizeInput($_POST['admin_response']);
    
    if (empty($admin_response)) {
        $error = "Please enter a response message before replying.";
    } else {
        $stmt = $conn->prepare("UPDATE grievances SET status = ?, admin_response = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $admin_response, $id);
        
        if ($stmt->execute()) {
            $message = "Response sent to student successfully!";
        } else {
            $error = "Error sending response: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get all grievances with student info
$grievances = $conn->query("
    SELECT g.*, s.first_name, s.last_name, s.email, s.student_id 
    FROM grievances g 
    JOIN students s ON g.student_id = s.student_id 
    ORDER BY 
        CASE g.status 
            WHEN 'pending' THEN 1 
            WHEN 'replied' THEN 2 
            WHEN 'in_progress' THEN 3
            WHEN 'resolved' THEN 4
            ELSE 5
        END,
        g.created_at DESC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grievances - IPMC Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        
        .sidebar {
            width: 280px;
            background: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1;
        }
        .sidebar-header { padding: 25px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header img { height: 50px; margin-bottom: 10px; }
        .sidebar-nav { padding: 20px 0; }
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        .nav-item i { width: 25px; margin-right: 15px; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.logout { margin-top: 50px; color: #e74c3c; }
        
        .main-content { margin-left: 280px; padding: 20px; }
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-title h1 { font-size: 24px; color: #333; }
        .page-title p { color: #666; margin-top: 5px; }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
        }
        .form-group textarea:focus, .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary:hover { background: linear-gradient(135deg, #1a2a3a, #2980b9); }
        .btn-reply { 
            background: #3498db; 
            color: white; 
            padding: 8px 15px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer;
            font-size: 14px;
        }
        .btn-reply:hover { background: #2980b9; }
        .btn-cancel { 
            background: #95a5a6; 
            color: white; 
            padding: 12px 30px; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            font-size: 16px;
        }
        .btn-cancel:hover { background: #7f8c8d; }
        .alert {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending { background: #fff3e0; color: #e67e22; }
        .status-replied { background: #e3f2fd; color: #1976d2; }
        .status-in_progress { background: #fff8e1; color: #f57c00; }
        .status-resolved { background: #e8f5e9; color: #2e7d32; }
        
        .table-responsive { overflow-x: auto; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th { background: #f8f9fa; font-weight: 600; }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 99999;
            overflow-y: auto;
        }
        .modal-content {
            position: relative;
            background: white;
            margin: 30px auto;
            width: 90%;
            max-width: 650px;
            border-radius: 15px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            animation: modalopen 0.3s;
        }
        @keyframes modalopen {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #ddd;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { margin: 0; font-size: 20px; }
        .modal-header .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: white;
            background: none;
            border: none;
        }
        .modal-header .close:hover { color: #ddd; }
        .modal-body { 
            padding: 25px; 
            max-height: 60vh;
            overflow-y: auto;
        }
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #ddd;
            text-align: right;
            background: #f8f9fa;
            border-radius: 0 0 15px 15px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        .grievance-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }
        .grievance-info p { margin: 8px 0; }
        .previous-response {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            border-left: 4px solid #27ae60;
        }
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h3, .nav-item span { display: none; }
            .main-content { margin-left: 70px; }
            .modal-content { margin: 20px auto; width: 95%; }
            .modal-footer { flex-direction: column; }
            .modal-footer button { width: 100%; }
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
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="students.php" class="nav-item"><i class="fas fa-users"></i><span>Manage Students</span></a>
                <a href="courses.php" class="nav-item"><i class="fas fa-book"></i><span>Manage Courses</span></a>
                <a href="contacts.php" class="nav-item"><i class="fas fa-envelope"></i><span>Contact Messages</span></a>
                <a href="results.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Upload Results</span></a>
                <a href="grievances.php" class="nav-item active"><i class="fas fa-exclamation-triangle"></i><span>Manage Grievances</span></a>
                <a href="api/logout_api.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Manage Grievances</h1>
                    <p>Review, respond to, and resolve student grievances</p>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-container">
                <h3><i class="fas fa-list"></i> All Student Grievances</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($grievances && $grievances->num_rows > 0): ?>
                                <?php while($g = $grievances->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $g['id']; ?></td>
                                    <td><?php echo htmlspecialchars($g['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($g['first_name'] . ' ' . $g['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($g['title'], 0, 40)); ?>
                                    <td><?php echo ucfirst($g['category']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $g['status']; ?>">
                                            <?php 
                                                $status_labels = [
                                                    'pending' => '📋 Pending',
                                                    'replied' => '💬 Replied',
                                                    'in_progress' => '⚙️ In Progress',
                                                    'resolved' => '✅ Resolved'
                                                ];
                                                echo $status_labels[$g['status']] ?? ucfirst($g['status']);
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($g['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-reply" data-id="<?php echo $g['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($g['title']); ?>"
                                                data-description="<?php echo htmlspecialchars($g['description']); ?>"
                                                data-status="<?php echo $g['status']; ?>"
                                                data-response="<?php echo htmlspecialchars($g['admin_response']); ?>"
                                                data-name="<?php echo htmlspecialchars($g['first_name'] . ' ' . $g['last_name']); ?>"
                                                data-email="<?php echo htmlspecialchars($g['email']); ?>"
                                                data-category="<?php echo $g['category']; ?>">
                                            <i class="fas fa-reply"></i> Reply
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No grievances submitted yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-reply-all"></i> Reply to Grievance</h3>
                <button class="close">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="grievance_id" id="grievance_id">
                    <input type="hidden" name="reply_grievance" value="1">
                    
                    <div id="grievanceInfo" class="grievance-info"></div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Update Status</label>
                        <select name="status" id="grievanceStatus" required>
                            <option value="pending">📋 Pending - Awaiting response</option>
                            <option value="replied">💬 Replied - Response sent to student</option>
                            <option value="in_progress">⚙️ In Progress - Under investigation</option>
                            <option value="resolved">✅ Resolved - Issue closed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Your Response *</label>
                        <textarea name="admin_response" id="adminResponse" rows="6" placeholder="Type your response to the student here... This will be visible in the student's portal." required></textarea>
                        <small style="color: #666; display: block; margin-top: 5px;">This response will be shown to the student immediately.</small>
                    </div>
                    
                    <div id="previousResponseDiv" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn-primary">✉️ Send Response to Student</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Wait for DOM to fully load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing reply buttons...');
            
            // Get modal elements
            var modal = document.getElementById('replyModal');
            var closeBtn = document.querySelector('.close');
            var cancelBtn = document.getElementById('cancelBtn');
            
            // Get all reply buttons
            var replyButtons = document.querySelectorAll('.btn-reply');
            console.log('Found ' + replyButtons.length + ' reply buttons');
            
            // Function to open modal
            window.openModal = function(id, title, description, status, adminResponse, studentName, studentEmail, category) {
                console.log('Opening modal for grievance ID:', id);
                
                // Set form values
                document.getElementById('grievance_id').value = id;
                document.getElementById('grievanceStatus').value = status;
                document.getElementById('adminResponse').value = adminResponse || '';
                
                // Build grievance info HTML
                var infoHtml = 
                    '<p><strong><i class="fas fa-user"></i> Student:</strong> ' + escapeHtml(studentName) + '</p>' +
                    '<p><strong><i class="fas fa-envelope"></i> Email:</strong> ' + escapeHtml(studentEmail) + '</p>' +
                    '<p><strong><i class="fas fa-folder"></i> Category:</strong> ' + escapeHtml(category.charAt(0).toUpperCase() + category.slice(1)) + '</p>' +
                    '<p><strong><i class="fas fa-heading"></i> Title:</strong> ' + escapeHtml(title) + '</p>' +
                    '<hr>' +
                    '<p><strong><i class="fas fa-align-left"></i> Student\'s Message:</strong></p>' +
                    '<p style="background: white; padding: 10px; border-radius: 8px; margin-top: 5px;">' + escapeHtml(description) + '</p>';
                
                document.getElementById('grievanceInfo').innerHTML = infoHtml;
                
                // Show previous response if exists
                var prevDiv = document.getElementById('previousResponseDiv');
                if (adminResponse && adminResponse !== '') {
                    prevDiv.style.display = 'block';
                    prevDiv.innerHTML = 
                        '<div class="previous-response">' +
                            '<strong><i class="fas fa-history"></i> Previous Response:</strong>' +
                            '<p style="margin-top: 8px;">' + escapeHtml(adminResponse) + '</p>' +
                        '</div>';
                } else {
                    prevDiv.style.display = 'none';
                }
                
                // Show modal
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            };
            
            // Add click event to each reply button
            replyButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    var id = this.getAttribute('data-id');
                    var title = this.getAttribute('data-title');
                    var description = this.getAttribute('data-description');
                    var status = this.getAttribute('data-status');
                    var adminResponse = this.getAttribute('data-response');
                    var studentName = this.getAttribute('data-name');
                    var studentEmail = this.getAttribute('data-email');
                    var category = this.getAttribute('data-category');
                    
                    console.log('Reply button clicked - ID:', id);
                    
                    openModal(id, title, description, status, adminResponse, studentName, studentEmail, category);
                });
            });
            
            // Close modal function
            function closeModal() {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            
            // Close modal when clicking X
            if (closeBtn) {
                closeBtn.onclick = function() {
                    closeModal();
                }
            }
            
            // Close modal when clicking Cancel button
            if (cancelBtn) {
                cancelBtn.onclick = function() {
                    closeModal();
                }
            }
            
            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target == modal) {
                    closeModal();
                }
            }
            
            // Escape HTML function
            function escapeHtml(text) {
                if (!text) return '';
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Form validation
            var replyForm = document.querySelector('#replyModal form');
            if (replyForm) {
                replyForm.addEventListener('submit', function(e) {
                    var response = document.getElementById('adminResponse').value.trim();
                    if (response === '') {
                        e.preventDefault();
                        alert('Please enter a response message before sending.');
                        return false;
                    }
                    console.log('Submitting response for grievance ID:', document.getElementById('grievance_id').value);
                    return true;
                });
            }
        });
    </script>
</body>
</html>
