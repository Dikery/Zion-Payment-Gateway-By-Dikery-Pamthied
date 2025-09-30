<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

require '../includes/db_connect.php';

// Get some basic statistics
$user_count_sql = "SELECT COUNT(*) as total_users FROM users WHERE user_type = 'student'";
$user_count_result = $conn->query($user_count_sql);
$user_count = $user_count_result->fetch_assoc()['total_users'];

$recent_users_sql = "SELECT username, first_name, last_name, created_at
                     FROM users
                     WHERE user_type = 'student'
                     ORDER BY created_at DESC
                     LIMIT 5";
$recent_users_result = $conn->query($recent_users_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Zion Fee Payment Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../public/theme.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
/* Reset and base styles */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { 
    font-family: 'Inter', sans-serif; 
    background: #f8fafc; 
    color: #1e293b; 
    line-height: 1.6;
}

/* Main layout */
.admin-layout {
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 280px;
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    padding: 24px 0;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
}
.sidebar-header {
    padding: 0 24px 32px;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 24px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.logo-icon {
    width: 40px;
    height: 40px;
    background: #ff6a00;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 18px;
}

.logo-text {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
}

.logo-subtitle {
    font-size: 14px;
    color: #64748b;
    margin-left: 52px;
}

.nav-menu {
    list-style: none;
    padding: 0 16px;
}

.nav-item {
    margin-bottom: 4px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #64748b;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s;
    font-weight: 500;
}

.nav-link:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.nav-link.active {
    background: #fff3e8;
    color: #ff6a00;
}

.nav-icon {
    width: 20px;
    text-align: center;
}

/* Main content */
.main-content {
    flex: 1;
    margin-left: 280px;
    padding: 24px;
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e2e8f0;
}

.header-left h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
}

.header-left p {
    color: #64748b;
    font-size: 16px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.notification-icon {
    width: 40px;
    height: 40px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}

.notification-icon:hover {
    background: #e2e8f0;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 16px;
    background: #f8fafc;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.user-profile:hover {
    background: #f1f5f9;
}

.user-avatar {
    width: 36px;
    height: 36px;
    background: #ff6a00;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.user-name {
    font-weight: 600;
    color: #1e293b;
}

.logout-btn {
    padding: 10px 20px;
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.logout-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
}

/* Stats grid */
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}

.stat-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #fff3e8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #ff6a00;
    margin-bottom: 16px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

.stat-label {
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 12px;
}

.stat-change {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
}

.stat-change.positive {
    color: #10b981;
}

.stat-change.negative {
    color: #ef4444;
}

/* Management section */
.management-section {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    margin-bottom: 32px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.management-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.management-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 24px;
    text-decoration: none;
    color: #1e293b;
    transition: all 0.2s;
    border: 1px solid #e2e8f0;
}

.management-card:hover {
    background: white;
    border-color: #ff6a00;
    box-shadow: 0 4px 6px rgba(255, 106, 0, 0.1);
}

.card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #fff3e8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #ff6a00;
    margin-bottom: 16px;
}

.card-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1e293b;
}

.card-description {
    font-size: 14px;
    color: #64748b;
    line-height: 1.5;
}

/* Recent registrations */
.recent-registrations {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.registrations-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.registrations-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 12px;
}

.view-all-btn {
    padding: 10px 20px;
    background: #fff3e8;
    color: #ff6a00;
    border: 1px solid #ff6a00;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
}

.view-all-btn:hover {
    background: #ff6a00;
    color: white;
}

/* Table */
.registrations-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.table-header-row {
    background: #f8fafc;
}

.table-header-row th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #64748b;
    font-size: 14px;
    border-bottom: 1px solid #e2e8f0;
}

.registrations-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s;
}

.registrations-table tbody tr:hover {
    background: #f8fafc;
}

.registrations-table td {
    padding: 16px;
    font-size: 14px;
}

.username {
    font-weight: 600;
    color: #ff6a00;
}

.full-name {
    color: #1e293b;
}

.reg-date {
    color: #64748b;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #64748b;
}

.no-data-icon {
    font-size: 48px;
    color: #e2e8f0;
    margin-bottom: 16px;
}

        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }

            .admin-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .stats-overview {
                grid-template-columns: 1fr;
            }

            .management-grid {
                grid-template-columns: 1fr;
            }

            .registrations-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $active='dashboard'; include __DIR__.'/partials/admin_sidebar.php'; ?>
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <h1>Admin Dashboard</h1>
                    <p>Manage your institution's payment system</p>
                </div>
                <div class="header-right">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar">ZA</div>
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                    </div>
                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </header>

            <!-- Stats Overview -->

            <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo number_format($user_count); ?></div>
                <div class="stat-label">Total Students</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +12% this month
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value">₹45,000</div>
                <div class="stat-label">Pending Payments</div>
                <div class="stat-change negative">
                    <i class="fas fa-arrow-down"></i>
                    3 students pending
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value">₹2.5L</div>
                <div class="stat-label">Total Collected</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +25% this semester
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value">98.5%</div>
                <div class="stat-label">Collection Rate</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +2.1% improvement
                </div>
            </div>
            </div>

            <!-- Management Section -->
            <div class="management-section">
            <div class="section-title">
                <i class="fas fa-cogs"></i>
                Management Tools
            </div>
            <div class="management-grid">
                <a href="manage_users.php" class="management-card">
                    <div class="card-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="card-title">User Management</div>
                    <div class="card-description">Add, edit, or remove student accounts and manage user permissions</div>
                </a>

                <a href="payment_reports.php" class="management-card">
                    <div class="card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="card-title">Payment Reports</div>
                    <div class="card-description">View detailed payment analytics and generate comprehensive reports</div>
                </a>

                <a href="fee_management.php" class="management-card">
                    <div class="card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-title">Fee Management</div>
                    <div class="card-description">Configure fee structures and manage payment deadlines</div>
                </a>

                <a href="system_settings.php" class="management-card">
                    <div class="card-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="card-title">System Settings</div>
                    <div class="card-description">Configure system preferences and security settings</div>
                </a>
            </div>
            </div>

            <!-- Recent Registrations -->
            <div class="recent-registrations">
            <div class="registrations-header">
                <div class="registrations-title">
                    <i class="fas fa-user-plus"></i>
                    Recent Student Registrations
                </div>
                <a href="manage_users.php" class="view-all-btn">View All Users</a>
            </div>

            <?php if ($recent_users_result->num_rows > 0): ?>
                <table class="registrations-table">
                    <thead>
                        <tr class="table-header-row">
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $recent_users_result->fetch_assoc()): ?>
                            <tr>
                                <td class="username"><?php echo htmlspecialchars($row['username']); ?></td>
                                <td class="full-name"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td class="reg-date"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>No recent registrations</h3>
                    <p>No new students have registered recently.</p>
                </div>
            <?php endif; ?>
            </div>
        </main>
    </div>


</body>
</html>
