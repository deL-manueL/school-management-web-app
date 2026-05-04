<?php
require_once 'config.php';
requireAdminLogin();

$conn = getConnection();

// Delete message
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: contacts.php?deleted=1');
    exit;
}

$contacts = $conn->query("SELECT * FROM contacts ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - IPMC Admin</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Contact Messages</h1>
                    <p>Messages submitted through the website contact form</p>
                </div>
            </div>

            <?php if(isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Message deleted successfully</div>
            <?php endif; ?>

            <div class="form-container">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Program</th>
                                <th>Campus</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($contact = $contacts->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $contact['id']; ?></td>
                                <td><?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                                <td><?php echo htmlspecialchars($contact['program'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($contact['campus'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(substr($contact['message'], 0, 50)) . '...'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($contact['created_at'])); ?></td>
                                <td>
                                    <button class="btn-edit" onclick="viewMessage(<?php echo $contact['id']; ?>, '<?php echo addslashes($contact['first_name']); ?>', '<?php echo addslashes($contact['last_name']); ?>', '<?php echo addslashes($contact['email']); ?>', '<?php echo addslashes($contact['phone']); ?>', '<?php echo addslashes($contact['program']); ?>', '<?php echo addslashes($contact['campus']); ?>', '<?php echo addslashes($contact['message']); ?>')">View</button>
                                    <a href="?delete=<?php echo $contact['id']; ?>" class="btn-danger" onclick="return confirm('Delete this message?')">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="messageModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Contact Message Details</h3>
                <span class="closeMessage">&times;</span>
            </div>
            <div class="modal-body" id="messageDetails"></div>
            <div class="modal-footer">
                <button onclick="document.getElementById('messageModal').style.display = 'none'" class="btn-primary">Close</button>
            </div>
        </div>
    </div>

    <script>
        function viewMessage(id, firstName, lastName, email, phone, program, campus, message) {
            document.getElementById('messageDetails').innerHTML = `
                <p><strong>From:</strong> ${firstName} ${lastName}</p>
                <p><strong>Email:</strong> ${email}</p>
                <p><strong>Phone:</strong> ${phone}</p>
                <p><strong>Program of Interest:</strong> ${program || 'N/A'}</p>
                <p><strong>Preferred Campus:</strong> ${campus || 'N/A'}</p>
                <hr>
                <p><strong>Message:</strong></p>
                <p style="background: #f8f9fa; padding: 15px; border-radius: 10px;">${message}</p>
                <hr>
                <p><small>Received: ${new Date().toLocaleString()}</small></p>
            `;
            document.getElementById('messageModal').style.display = 'flex';
        }

        document.querySelector('.closeMessage').onclick = function() {
            document.getElementById('messageModal').style.display = 'none';
        }
    </script>
</body>
</html>