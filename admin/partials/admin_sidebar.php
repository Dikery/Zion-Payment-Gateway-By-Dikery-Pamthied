<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$active = $active ?? '';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">Z</div>
            <div class="logo-text">Zion Admin</div>
        </div>
        <div class="logo-subtitle">Admin Panel</div>
    </div>
    <nav>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link <?php echo $active==='dashboard'?'active':''; ?>">
                    <i class="fas fa-tachometer-alt nav-icon"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="fee_management.php" class="nav-link <?php echo $active==='fees'?'active':''; ?>">
                    <i class="fas fa-rupee-sign nav-icon"></i>
                    Fee Management
                </a>
            </li>
            <li class="nav-item">
                <a href="courses.php" class="nav-link <?php echo $active==='courses'?'active':''; ?>">
                    <i class="fas fa-book nav-icon"></i>
                    Courses
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_users.php" class="nav-link <?php echo $active==='users'?'active':''; ?>">
                    <i class="fas fa-users nav-icon"></i>
                    Students
                </a>
            </li>
            <li class="nav-item">
                <a href="payment_reports.php" class="nav-link <?php echo $active==='reports'?'active':''; ?>">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    Reports
                </a>
            </li>
            
        </ul>
    </nav>
</aside>


