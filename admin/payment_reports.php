<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

require '../includes/db_connect.php';

// Simple aggregates for now
$totals = [
    'total_payments' => 0,
    'total_amount' => 0,
    'completed_amount' => 0,
];

// Ensure payments table exists
$table_check = $conn->query("SHOW TABLES LIKE 'payments'");
if ($table_check && $table_check->num_rows > 0) {
    $sum_sql = "SELECT COUNT(*) AS total_payments, COALESCE(SUM(amount),0) AS total_amount FROM payments";
    $sum_res = $conn->query($sum_sql);
    if ($sum_res) { $totals = array_merge($totals, $sum_res->fetch_assoc()); }

    $comp_sql = "SELECT COALESCE(SUM(amount),0) AS completed_amount FROM payments WHERE status='completed'";
    $comp_res = $conn->query($comp_sql);
    if ($comp_res) { $row = $comp_res->fetch_assoc(); $totals['completed_amount'] = $row['completed_amount']; }

    // Recent payments
    $recent_sql = "SELECT p.id, p.amount, p.payment_method, p.transaction_id, p.status, p.created_at, u.username
                   FROM payments p JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC LIMIT 10";
    $recent_res = $conn->query($recent_sql);
} else {
    $recent_res = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reports - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

.container {
    max-width: 1200px;
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

.back-btn {
    padding: 10px 20px;
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.back-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
}

/* Stats grid */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat {
    background: white;
    padding: 24px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat .value {
    font-size: 32px;
    font-weight: 700;
    color: #ff6a00;
    margin-bottom: 8px;
}

.stat .label {
    color: #64748b;
    font-weight: 600;
    font-size: 14px;
}

/* Card */
.card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    margin-bottom: 24px;
}

.card h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 24px;
}

/* Table */
.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.table thead th {
    background: #f8fafc;
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    color: #64748b;
    font-size: 14px;
    border-bottom: 1px solid #e2e8f0;
}

.table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s;
}

.table tbody tr:hover {
    background: #f8fafc;
}

.table tbody tr:last-child {
    border-bottom: none;
}

.table td {
    padding: 16px;
    font-size: 14px;
    color: #1e293b;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .header {
        flex-direction: column;
        gap: 16px;
    }
    
    .grid {
        grid-template-columns: 1fr;
    }
}
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $active='reports'; include __DIR__.'/partials/admin_sidebar.php'; ?>
        <div class="main-content">
            <div class="container">
                <div class="header">
                    <div class="header-left">
                        <h1>Payment Reports</h1>
                        <p>View detailed payment analytics and reports</p>
                    </div>
                    <a class="back-btn" href="admin_dashboard.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>

                <div class="grid">
                    <div class="stat card">
                        <div class="value">₹<?php echo number_format((float)$totals['total_amount'], 2); ?></div>
                        <div class="label">Total Amount</div>
                    </div>
                    <div class="stat card">
                        <div class="value"><?php echo (int)$totals['total_payments']; ?></div>
                        <div class="label">Total Payments</div>
                    </div>
                    <div class="stat card">
                        <div class="value">₹<?php echo number_format((float)$totals['completed_amount'], 2); ?></div>
                        <div class="label">Completed Amount</div>
                    </div>
                </div>

                <div class="card">
                    <h3>Most Recent Payments</h3>
                    <?php if ($recent_res && $recent_res->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($r = $recent_res->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo (int)$r['id']; ?></td>
                                    <td><?php echo htmlspecialchars($r['username']); ?></td>
                                    <td>₹<?php echo number_format((float)$r['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($r['payment_method'])); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($r['status'])); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($r['created_at']))); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div style="color:#64748b; text-align: center; padding: 20px;">No payments found yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
