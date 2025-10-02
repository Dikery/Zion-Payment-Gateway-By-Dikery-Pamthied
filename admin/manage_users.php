<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

require '../includes/db_connect.php';

// Filters (GET)
$filter_search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_class = isset($_GET['class']) ? trim($_GET['class']) : '';
$filter_type = isset($_GET['type']) ? trim($_GET['type']) : '';

// Handle user actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    // AJAX: Fetch user details
    if ($action === 'get_user' && isset($_POST['user_id']) && ($_POST['ajax'] ?? '') === '1') {
        header('Content-Type: application/json');
        $user_id = intval($_POST['user_id']);
        $stmt = $conn->prepare("SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.contact, u.user_type,
                                       sd.student_id, sd.class_level
                                FROM users u
                                LEFT JOIN student_details sd ON sd.user_id = u.id
                                WHERE u.id = ?");
        if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Failed to prepare']); exit(); }
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            echo json_encode(['success'=>true,'user'=>$row]);
        } else {
            echo json_encode(['success'=>false,'message'=>'User not found']);
        }
        exit();
    }

    // AJAX: Update user details
    if ($action === 'update_user' && ($_POST['ajax'] ?? '') === '1') {
        header('Content-Type: application/json');
        $user_id = intval($_POST['user_id'] ?? 0);
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $user_type = trim($_POST['user_type'] ?? 'student');
        $student_id_u = trim($_POST['student_id'] ?? '');
        $class_level = trim($_POST['class_level'] ?? '');

        if ($user_id <= 0 || $first_name === '' || $last_name === '' || $email === '') {
            echo json_encode(['success'=>false,'message'=>'Missing required fields']);
            exit();
        }

        $conn->begin_transaction();
        try {
            $u_stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, contact=?, user_type=? WHERE id=?");
            if (!$u_stmt) { throw new Exception('Failed to prepare users update'); }
            $u_stmt->bind_param('sssssi', $first_name, $last_name, $email, $contact, $user_type, $user_id);
            $u_stmt->execute();
            $u_stmt->close();

            // Ensure student_details row exists for students
            if ($user_type === 'student') {
                // Try update first
                $sd_stmt = $conn->prepare("UPDATE student_details SET student_id=?, class_level=? WHERE user_id=?");
                if (!$sd_stmt) { throw new Exception('Failed to prepare student_details update'); }
                $sd_stmt->bind_param('ssi', $student_id_u, $class_level, $user_id);
                $sd_stmt->execute();
                if ($sd_stmt->affected_rows === 0) {
                    $sd_stmt->close();
                    $ins_stmt = $conn->prepare("INSERT INTO student_details (user_id, student_id, class_level) VALUES (?,?,?)");
                    if (!$ins_stmt) { throw new Exception('Failed to prepare student_details insert'); }
                    $ins_stmt->bind_param('iss', $user_id, $student_id_u, $class_level);
                    $ins_stmt->execute();
                    $ins_stmt->close();
                } else {
                    $sd_stmt->close();
                }
            }

            $conn->commit();
            echo json_encode(['success'=>true]);
        } catch (Throwable $e) {
            $conn->rollback();
            echo json_encode(['success'=>false,'message'=>'Update failed']);
        }
        exit();
    }

    // AJAX: Add new user
    if ($action === 'add_user' && ($_POST['ajax'] ?? '') === '1') {
        header('Content-Type: application/json');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $user_type = trim($_POST['user_type'] ?? 'student');
        $student_id_u = trim($_POST['student_id'] ?? '');
        $class_level = trim($_POST['class_level'] ?? '');

        if ($username === '' || $email === '' || $password === '' || $first_name === '' || $last_name === '') {
            echo json_encode(['success'=>false,'message'=>'All required fields must be filled']);
            exit();
        }

        if (strlen($password) < 6) {
            echo json_encode(['success'=>false,'message'=>'Password must be at least 6 characters']);
            exit();
        }

        $conn->begin_transaction();
        try {
            // Check if username or email already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            if (!$check_stmt) { throw new Exception('Failed to prepare check statement'); }
            $check_stmt->bind_param('ss', $username, $email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $check_stmt->close();
                throw new Exception('Username or email already exists');
            }
            $check_stmt->close();

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $u_stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, contact, user_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$u_stmt) { throw new Exception('Failed to prepare user insert'); }
            $u_stmt->bind_param('sssssss', $username, $email, $hashed_password, $first_name, $last_name, $contact, $user_type);
            $u_stmt->execute();
            $new_user_id = $conn->insert_id;
            $u_stmt->close();

            // Insert student details if user is a student
            if ($user_type === 'student' && $student_id_u !== '') {
                $sd_stmt = $conn->prepare("INSERT INTO student_details (user_id, student_id, class_level) VALUES (?, ?, ?)");
                if (!$sd_stmt) { throw new Exception('Failed to prepare student_details insert'); }
                $sd_stmt->bind_param('iss', $new_user_id, $student_id_u, $class_level);
                $sd_stmt->execute();
                $sd_stmt->close();
            }

            $conn->commit();
            echo json_encode(['success'=>true,'message'=>'User created successfully']);
        } catch (Throwable $e) {
            $conn->rollback();
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit();
    }

    if ($action === 'delete_user' && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Delete from student_details first (foreign key constraint)
            $conn->query("DELETE FROM student_details WHERE user_id = '$user_id'");
            // Delete from users table
            $conn->query("DELETE FROM users WHERE id = '$user_id' AND user_type = 'student'");

            $conn->commit();
            $success_message = "User deleted successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error deleting user: " . $e->getMessage();
        }
    }
}

// Build filtered users query with prepared statement
$base_sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.contact,
                    u.created_at, u.user_type, u.last_payment_date, u.total_paid,
                    sd.student_id, sd.class_level
             FROM users u
             LEFT JOIN student_details sd ON u.id = sd.user_id";

$conditions = [];
$types = '';
$params = [];

if ($filter_search !== '') {
    $conditions[] = "(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) LIKE ? OR u.email LIKE ? OR sd.student_id LIKE ?)";
    $searchLike = '%' . $filter_search . '%';
    $types .= 'sss';
    $params[] = $searchLike; $params[] = $searchLike; $params[] = $searchLike;
}

if ($filter_class !== '') {
    $conditions[] = "sd.class_level = ?";
    $types .= 's';
    $params[] = $filter_class;
}

if ($filter_type !== '') {
    $conditions[] = "u.user_type = ?";
    $types .= 's';
    $params[] = $filter_type;
}

$users_sql = $base_sql;
if (!empty($conditions)) {
    $users_sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$users_sql .= ' ORDER BY u.created_at DESC';

$users_stmt = $conn->prepare($users_sql);
if (!$users_stmt) {
    $error_message = 'Failed to prepare users query.';
}
if ($users_stmt && $types !== '') {
    $users_stmt->bind_param($types, ...$params);
}
if ($users_stmt) {
    $users_stmt->execute();
    $users_result = $users_stmt->get_result();
}

// Get statistics
$stats_sql = "SELECT
    COUNT(*) as total_users,
    COUNT(CASE WHEN user_type = 'student' THEN 1 END) as total_students,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
    AVG(total_paid) as avg_payment
    FROM users";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Distinct options for filters
$class_options = [];
$class_res = $conn->query("SELECT DISTINCT class_level FROM student_details WHERE class_level IS NOT NULL AND class_level <> '' ORDER BY CAST(SUBSTRING(class_level, 7) AS UNSIGNED)");
if ($class_res) { while ($r = $class_res->fetch_assoc()) { $class_options[] = $r['class_level']; } }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="../public/ui.js"></script>
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
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #ff6a00;
    margin-bottom: 8px;
}

.stat-label {
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
}

/* Management section */
.management-section {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 12px;
}

.add-user-btn {
    padding: 10px 20px;
    background: #ff6a00;
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.add-user-btn:hover {
    background: #e65e00;
}

/* Search section */
.search-filter-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    border: 1px solid #e2e8f0;
}

.search-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}

.search-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.search-label {
    font-weight: 600;
    color: #1e293b;
    font-size: 14px;
}

.search-input {
    padding: 10px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    transition: all 0.2s;
    background: white;
}

.search-input:focus {
    outline: none;
    border-color: #ff6a00;
    box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.1);
}

.search-btn {
    padding: 10px 20px;
    background: #ff6a00;
    border: none;
    color: white;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.search-btn:hover {
    background: #e65e00;
}

/* Table */
.users-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.users-table thead {
    background: #f8fafc;
}

.users-table th {
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    color: #64748b;
    font-size: 14px;
    border-bottom: 1px solid #e2e8f0;
}

.users-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s;
}

.users-table tbody tr:hover {
    background: #f8fafc;
}

.users-table td {
    padding: 16px;
    font-size: 14px;
}

/* Edit panel */
.edit-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.3);
    display: none;
    align-items: stretch;
    justify-content: flex-end;
    z-index: 2000;
}
.edit-overlay.open { display: flex; }
.edit-panel {
    width: 420px;
    max-width: 90vw;
    background: #ffffff;
    height: 100vh;
    border-left: 1px solid #e2e8f0;
    box-shadow: -10px 0 20px rgba(0,0,0,0.08);
    transform: translateX(100%);
    transition: transform .35s ease;
    display: flex;
    flex-direction: column;
}
.edit-overlay.open .edit-panel { transform: translateX(0); }
.edit-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
.edit-title { font-weight:700; color:#1e293b; }
.edit-body { padding: 16px 20px; overflow-y: auto; }
.form-group { display:flex; flex-direction:column; gap:6px; margin-bottom:12px; }
.form-group label { font-weight:600; color:#1e293b; font-size:14px; }
.form-control { padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-family:'Inter', sans-serif; font-size:14px; }
.form-actions { padding: 12px 20px; border-top:1px solid #f1f5f9; display:flex; gap:10px; justify-content:flex-end; }
.btn {
    padding: 10px 16px; border-radius:8px; border:1px solid #e2e8f0; background:#f8fafc; color:#374151; cursor:pointer; font-weight:600;
}
.btn.primary { background:#ff6a00; color:#fff; border-color:#ff6a00; }
.btn.primary:hover { background:#e65e00; }
.btn.secondary:hover { background:#eef2f7; }

.user-name {
    font-weight: 600;
    color: #1e293b;
}

.user-email {
    color: #ff6a00;
}

.user-type {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    display: inline-block;
}

.type-student {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.type-admin {
    background: rgba(255, 106, 0, 0.1);
    color: #ff6a00;
}

/* Action buttons */
.user-actions {
    display: flex;
    gap: 8px;
}

.btn-small {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    background: white;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-small:hover {
    background: #f8fafc;
}

.btn-small.primary {
    background: #ff6a00;
    color: white;
    border-color: #ff6a00;
}

.btn-small.primary:hover {
    background: #e65e00;
}

.btn-small.danger {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
}

.btn-small.danger:hover {
    background: #dc2626;
}

/* Alerts */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.no-users {
    text-align: center;
    padding: 40px;
    color: #64748b;
}

.no-users-icon {
    font-size: 48px;
    color: #e2e8f0;
    margin-bottom: 16px;
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
    
    .search-grid {
        grid-template-columns: 1fr;
    }
    
    .user-actions {
        flex-direction: column;
    }
}
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $active='users'; include __DIR__.'/partials/admin_sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>User Management</h1>
                    <p>Manage student accounts and permissions</p>
                </div>
                <a href="admin_dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['new_today']); ?></div>
                    <div class="stat-label">New Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">₹<?php echo number_format($stats['avg_payment'] ?? 0, 0); ?></div>
                    <div class="stat-label">Avg Payment</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="management-section">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-users"></i>
                        All Users
                    </div>
                    <button class="add-user-btn" onclick="openAddUserPanel()">
                        <i class="fas fa-user-plus"></i>
                        Add New User
                    </button>
                </div>

                <div class="search-filter-section">
                    <form method="get">
                        <div class="search-grid">
                            <div class="search-group">
                                <label class="search-label">Search Users</label>
                                <input type="text" class="search-input" name="q" placeholder="Search by name, email, or student ID" value="<?php echo htmlspecialchars($filter_search); ?>">
                            </div>
                            <div class="search-group">
                                <label class="search-label">Filter by Class</label>
                                <select class="search-input" name="class">
                                    <option value="">All Classes</option>
                                    <?php foreach ($class_options as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($filter_class === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="search-group">
                                <label class="search-label">Filter by Type</label>
                                <select class="search-input" name="type">
                                    <option value="" <?php echo ($filter_type==='')?'selected':''; ?>>All Types</option>
                                    <option value="student" <?php echo ($filter_type==='student')?'selected':''; ?>>Students</option>
                                    <option value="admin" <?php echo ($filter_type==='admin')?'selected':''; ?>>Admins</option>
                                </select>
                            </div>
                            <div class="search-group">
                                <button class="search-btn" type="submit">
                                    <i class="fas fa-search"></i>
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User Details</th>
                            <th>Academic Info</th>
                            <th>Payment Info</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php if (isset($users_result) && $users_result->num_rows > 0): ?>
                            <?php while($user = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                        <div style="margin-top: 4px;">
                                            <span class="user-type type-<?php echo $user['user_type']; ?>">
                                                <?php echo ucfirst($user['user_type']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div><strong>Student ID:</strong> <?php echo htmlspecialchars($user['student_id'] ?? 'N/A'); ?></div>
                                        <div><strong>Class:</strong> <?php echo htmlspecialchars($user['class_level'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td>
                                        <div><strong>Total Paid:</strong> ₹<?php echo number_format($user['total_paid'] ?? 0, 2); ?></div>
                                        <div><strong>Last Payment:</strong>
                                            <?php echo $user['last_payment_date'] ? date('M d, Y', strtotime($user['last_payment_date'])) : 'Never'; ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <button class="btn-small" onclick="openEditPanel(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                                Edit
                                            </button>
                                            <?php if ($user['user_type'] === 'student'): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn-small danger">
                                                        <i class="fas fa-trash"></i>
                                                        Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-users">
                                    <div class="no-users-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <h3>No users found</h3>
                                    <p>No users match your current criteria.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Panel UI -->
    <div class="edit-overlay" id="editOverlay" aria-hidden="true">
        <div class="edit-panel" role="dialog" aria-modal="true">
            <div class="edit-header">
                <div class="edit-title">Edit User</div>
                <button class="btn" id="btnCloseEdit">Close</button>
            </div>
            <div class="edit-body">
                <form id="editForm">
                    <input type="hidden" name="user_id" id="edit_user_id" />
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" class="form-control" name="first_name" id="edit_first_name" required />
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" class="form-control" name="last_name" id="edit_last_name" required />
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required />
                    </div>
                    <div class="form-group">
                        <label>Contact</label>
                        <input type="text" class="form-control" name="contact" id="edit_contact" />
                    </div>
                    <div class="form-group">
                        <label>User Type</label>
                        <select class="form-control" name="user_type" id="edit_user_type">
                            <option value="student">Student</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" class="form-control" name="student_id" id="edit_student_id" />
                    </div>
                    <div class="form-group">
                        <label>Class Level</label>
                        <select class="form-control" name="class_level" id="edit_class_level">
                            <option value="">Select Class</option>
                            <option value="Class 1">Class 1</option>
                            <option value="Class 2">Class 2</option>
                            <option value="Class 3">Class 3</option>
                            <option value="Class 4">Class 4</option>
                            <option value="Class 5">Class 5</option>
                            <option value="Class 6">Class 6</option>
                            <option value="Class 7">Class 7</option>
                            <option value="Class 8">Class 8</option>
                            <option value="Class 9">Class 9</option>
                            <option value="Class 10">Class 10</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="form-actions">
                <button class="btn secondary" id="btnCancelEdit" type="button">Cancel</button>
                <button class="btn primary" id="btnSaveEdit" type="button">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Add User Panel UI -->
    <div class="edit-overlay" id="addUserOverlay" aria-hidden="true">
        <div class="edit-panel" role="dialog" aria-modal="true">
            <div class="edit-header">
                <div class="edit-title">Add New User</div>
                <button class="btn" id="btnCloseAdd">Close</button>
            </div>
            <div class="edit-body">
                <form id="addUserForm">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" class="form-control" name="username" id="add_username" required />
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" class="form-control" name="email" id="add_email" required />
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" class="form-control" name="password" id="add_password" required minlength="6" />
                        <small style="color: #64748b; font-size: 12px;">Minimum 6 characters</small>
                    </div>
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" class="form-control" name="first_name" id="add_first_name" required />
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" class="form-control" name="last_name" id="add_last_name" required />
                    </div>
                    <div class="form-group">
                        <label>Contact</label>
                        <input type="text" class="form-control" name="contact" id="add_contact" />
                    </div>
                    <div class="form-group">
                        <label>User Type</label>
                        <select class="form-control" name="user_type" id="add_user_type" onchange="toggleStudentFields()">
                            <option value="student">Student</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group" id="student_fields">
                        <label>Student ID</label>
                        <input type="text" class="form-control" name="student_id" id="add_student_id" />
                    </div>
                    <div class="form-group" id="class_fields">
                        <label>Class Level</label>
                        <select class="form-control" name="class_level" id="add_class_level">
                            <option value="">Select Class</option>
                            <option value="Class 1">Class 1</option>
                            <option value="Class 2">Class 2</option>
                            <option value="Class 3">Class 3</option>
                            <option value="Class 4">Class 4</option>
                            <option value="Class 5">Class 5</option>
                            <option value="Class 6">Class 6</option>
                            <option value="Class 7">Class 7</option>
                            <option value="Class 8">Class 8</option>
                            <option value="Class 9">Class 9</option>
                            <option value="Class 10">Class 10</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="form-actions">
                <button class="btn secondary" id="btnCancelAdd" type="button">Cancel</button>
                <button class="btn primary" id="btnSaveAdd" type="button">Create User</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const courseFilter = document.getElementById('courseFilter');
            const typeFilter = document.getElementById('typeFilter');
            const usersTableBody = document.getElementById('usersTableBody');

            // Add entrance animations
            const cards = document.querySelectorAll('.stat-card, .management-section');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });

            // Search functionality
            function applyFilters() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedCourse = courseFilter.value;
                const selectedType = typeFilter.value;

                const rows = usersTableBody.querySelectorAll('tr');

                rows.forEach(row => {
                    if (row.cells.length === 1) { // No data row
                        row.style.display = 'table-row';
                        return;
                    }

                    const name = row.cells[0].textContent.toLowerCase();
                    const email = row.cells[0].querySelector('.user-email').textContent.toLowerCase();
                    const course = row.cells[1].textContent.toLowerCase();
                    const type = row.querySelector('.user-type').textContent.toLowerCase();

                    const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                    const matchesCourse = !selectedCourse || course.includes(selectedCourse.toLowerCase());
                    const matchesType = !selectedType || type.includes(selectedType.toLowerCase());

                    if (matchesSearch && matchesCourse && matchesType) {
                        row.style.display = 'table-row';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Real-time search
            searchInput.addEventListener('input', applyFilters);
            courseFilter.addEventListener('change', applyFilters);
            typeFilter.addEventListener('change', applyFilters);
        });

        // Edit panel logic
        const overlay = document.getElementById('editOverlay');
        const btnCloseEdit = document.getElementById('btnCloseEdit');
        const btnCancelEdit = document.getElementById('btnCancelEdit');
        const btnSaveEdit = document.getElementById('btnSaveEdit');
        const editForm = document.getElementById('editForm');
        const fields = {
            user_id: document.getElementById('edit_user_id'),
            first_name: document.getElementById('edit_first_name'),
            last_name: document.getElementById('edit_last_name'),
            email: document.getElementById('edit_email'),
            contact: document.getElementById('edit_contact'),
            user_type: document.getElementById('edit_user_type'),
            student_id: document.getElementById('edit_student_id'),
            class_level: document.getElementById('edit_class_level')
        };

        window.openEditPanel = async function(userId){
            overlay.classList.add('open');
            overlay.setAttribute('aria-hidden','false');
            // Load data
            const formData = new FormData();
            formData.append('action','get_user');
            formData.append('ajax','1');
            formData.append('user_id', userId);
            try {
                const res = await fetch('manage_users.php', { method:'POST', body: formData });
                const data = await res.json();
                if (data && data.success) {
                    const u = data.user;
                    fields.user_id.value = u.id;
                    fields.first_name.value = u.first_name || '';
                    fields.last_name.value = u.last_name || '';
                    fields.email.value = u.email || '';
                    fields.contact.value = u.contact || '';
                    fields.user_type.value = u.user_type || 'student';
                    fields.student_id.value = u.student_id || '';
                    fields.class_level.value = u.class_level || '';
                }
            } catch(e) {}
        }

        function closeEditPanel(){
            overlay.classList.remove('open');
            overlay.setAttribute('aria-hidden','true');
        }

        btnCloseEdit.addEventListener('click', closeEditPanel);
        btnCancelEdit.addEventListener('click', closeEditPanel);

        btnSaveEdit.addEventListener('click', async function(){
            const formData = new FormData(editForm);
            formData.append('action','update_user');
            formData.append('ajax','1');
            try {
                const res = await fetch('manage_users.php', { method:'POST', body: formData });
                const data = await res.json();
                if (data && data.success) {
                    // Update the row UI optimistically
                    const userId = fields.user_id.value;
                    const row = document.querySelector(`#usersTableBody tr button[onclick="openEditPanel(${userId})"]`)?.closest('tr');
                    if (row) {
                        row.cells[0].querySelector('.user-name').textContent = `${fields.first_name.value} ${fields.last_name.value}`;
                        row.cells[0].querySelector('.user-email').textContent = fields.email.value;
                        const typeBadge = row.querySelector('.user-type');
                        if (typeBadge) {
                            typeBadge.textContent = fields.user_type.value.charAt(0).toUpperCase()+fields.user_type.value.slice(1);
                            typeBadge.className = `user-type type-${fields.user_type.value}`;
                        }
                        row.cells[1].children[0].innerHTML = `<strong>Student ID:</strong> ${fields.student_id.value || 'N/A'}`;
                        row.cells[1].children[1].innerHTML = `<strong>Class:</strong> ${fields.class_level.value || 'N/A'}`;
                    }
                    closeEditPanel();
                } else {
                    alert(data && data.message ? data.message : 'Failed to update user');
                }
            } catch(e) {
                alert('Failed to update user');
            }
        });

        // Add User Panel Logic
        const addOverlay = document.getElementById('addUserOverlay');
        const btnCloseAdd = document.getElementById('btnCloseAdd');
        const btnCancelAdd = document.getElementById('btnCancelAdd');
        const btnSaveAdd = document.getElementById('btnSaveAdd');
        const addForm = document.getElementById('addUserForm');

        window.openAddUserPanel = function(){
            addOverlay.classList.add('open');
            addOverlay.setAttribute('aria-hidden','false');
            // Clear form
            addForm.reset();
            // Focus first input
            document.getElementById('add_username').focus();
        }

        function closeAddPanel(){
            addOverlay.classList.remove('open');
            addOverlay.setAttribute('aria-hidden','true');
        }

        btnCloseAdd.addEventListener('click', closeAddPanel);
        btnCancelAdd.addEventListener('click', closeAddPanel);

        btnSaveAdd.addEventListener('click', async function(){
            const formData = new FormData(addForm);
            formData.append('action','add_user');
            formData.append('ajax','1');

            // Basic client-side validation
            const username = document.getElementById('add_username').value.trim();
            const email = document.getElementById('add_email').value.trim();
            const password = document.getElementById('add_password').value;
            const firstName = document.getElementById('add_first_name').value.trim();
            const lastName = document.getElementById('add_last_name').value.trim();

            if (!username || !email || !password || !firstName || !lastName) {
                alert('Please fill in all required fields');
                return;
            }

            if (password.length < 6) {
                alert('Password must be at least 6 characters');
                return;
            }

            try {
                const res = await fetch('manage_users.php', { method:'POST', body: formData });
                const data = await res.json();
                if (data && data.success) {
                    alert('User created successfully!');
                    closeAddPanel();
                    // Reload page to show new user
                    window.location.reload();
                } else {
                    alert(data && data.message ? data.message : 'Failed to create user');
                }
            } catch(e) {
                alert('Failed to create user: ' + e.message);
            }
        });

        // Toggle student fields based on user type
        window.toggleStudentFields = function(){
            const userType = document.getElementById('add_user_type').value;
            const studentFields = document.getElementById('student_fields');
            const classFields = document.getElementById('class_fields');
            
            if (userType === 'student') {
                studentFields.style.display = 'block';
                classFields.style.display = 'block';
            } else {
                studentFields.style.display = 'none';
                classFields.style.display = 'none';
            }
        }
        
    </script>
</body>
</html>
