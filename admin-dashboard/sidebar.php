<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../images/University College Logo.png" alt="IPMC Logo">
        <h3>IPMC Admin</h3>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="students.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Manage Students</span>
        </a>
        <a href="courses.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>">
            <i class="fas fa-book"></i>
            <span>Manage Courses</span>
        </a>
        <a href="contacts.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i>
            <span>Contact Messages</span>
        </a>
        <a href="results.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>Upload Results</span>
        </a>
        <a href="grievances.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'grievances.php' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Manage Grievances</span>
        </a>
        <a href="api/logout_api.php" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>
