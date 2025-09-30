<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}
require '../includes/db_connect.php';

$messages = $_SESSION['messages'] ?? [];
unset($_SESSION['messages']);

$colCheck = $conn->query("SHOW COLUMNS FROM fee_structures LIKE 'title'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE fee_structures ADD COLUMN title VARCHAR(150) NULL AFTER id");
}

// Ensure no unique constraints exist that would prevent multiple fees per course-semester
$uniqueCheck = $conn->query("SHOW INDEX FROM fee_structures WHERE Key_name='uk_course_semester'");
if ($uniqueCheck && $uniqueCheck->num_rows > 0) {
    $conn->query("ALTER TABLE fee_structures DROP INDEX uk_course_semester");
}

// Also check for any other unique constraints on course_name and semester combination
$constraintCheck = $conn->query("SHOW INDEX FROM fee_structures WHERE Column_name IN ('course_name', 'semester') AND Non_unique = 0");
if ($constraintCheck && $constraintCheck->num_rows > 0) {
    while ($row = $constraintCheck->fetch_assoc()) {
        if ($row['Key_name'] !== 'PRIMARY') {
            $conn->query("ALTER TABLE fee_structures DROP INDEX " . $row['Key_name']);
        }
    }
}

// Handle fee actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_fee' && isset($_POST['fee_id'])) {
        $fee_id = (int)$_POST['fee_id'];
        $stmt = $conn->prepare("UPDATE fee_structures SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id = ?");
        if ($stmt) { $stmt->bind_param('i', $fee_id); if(!$stmt->execute()){ $messages[] = 'Failed to toggle fee: ' . $stmt->error; } $stmt->close(); } else { $messages[] = 'Failed to prepare toggle statement: ' . $conn->error; }
    }
    if ($_POST['action'] === 'delete_fee' && isset($_POST['fee_id'])) {
        $fee_id = (int)$_POST['fee_id'];
        $stmt = $conn->prepare("DELETE FROM fee_structures WHERE id = ?");
        if ($stmt) { $stmt->bind_param('i', $fee_id); if(!$stmt->execute()){ $messages[] = 'Failed to delete fee: ' . $stmt->error; } $stmt->close(); } else { $messages[] = 'Failed to prepare delete statement: ' . $conn->error; }
    }
    if ($_POST['action'] === 'edit_fee' && isset($_POST['fee_id'])) {
        $fee_id = (int)$_POST['fee_id'];
        $course_name = trim($_POST['fee_course'] ?? '');
        $semester = trim($_POST['fee_semester'] ?? '');
        $title = trim($_POST['fee_title'] ?? '');
        $amount = isset($_POST['fee_amount']) ? (float)$_POST['fee_amount'] : 0;
        $due_date = $_POST['fee_due_date'] !== '' ? $_POST['fee_due_date'] : null;
        $late_fee = $_POST['late_fee'] !== '' ? $_POST['late_fee'] : null;
        if ($course_name !== '' && $semester !== '' && $amount > 0) {
            $stmt = $conn->prepare("UPDATE fee_structures SET title = ?, course_name = ?, semester = ?, amount = ?, due_date = ?, late_fee = ? WHERE id = ?");
            if ($stmt) { if(!$stmt->bind_param('sssdssi', $title, $course_name, $semester, $amount, $due_date, $late_fee, $fee_id)) { $messages[] = 'Bind failed (edit): ' . $stmt->error; }
                if(!$stmt->execute()){ $messages[] = 'Failed to update fee: ' . $stmt->error; }
                $stmt->close();
            } else { $messages[] = 'Failed to prepare update statement: ' . $conn->error; }
        }
    }
}

// Handle add course
if (isset($_POST['action']) && $_POST['action'] === 'add_course') {
    $name = trim($_POST['course_name'] ?? '');
    $code = trim($_POST['course_code'] ?? '');
    $num_semesters = (int)($_POST['num_semesters'] ?? 6);
    if ($name !== '' && $num_semesters > 0) {
        $stmt = $conn->prepare("INSERT INTO courses (name, code, num_semesters) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE code=VALUES(code), num_semesters=VALUES(num_semesters), is_active=1");
        if ($stmt) { $stmt->bind_param('ssi', $name, $code, $num_semesters); $stmt->execute(); $stmt->close(); }
    }
}

// Handle add fee structure
if (isset($_POST['action']) && $_POST['action'] === 'add_fee') {
    $course_name = trim($_POST['fee_course'] ?? '');
    $semester = trim($_POST['fee_semester'] ?? '');
    $title = trim($_POST['fee_title'] ?? '');
    $amount = (float)($_POST['fee_amount'] ?? 0);
    $due_date = $_POST['fee_due_date'] ?? null;
    $late_fee = $_POST['late_fee'] !== '' ? $_POST['late_fee'] : null;
    if ($course_name !== '' && $semester !== '' && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO fee_structures (title, course_name, semester, amount, due_date, late_fee) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            if(!$stmt->bind_param('sssdss', $title, $course_name, $semester, $amount, $due_date, $late_fee)) { $messages[] = 'Bind failed (insert): ' . $stmt->error; }
            if(!$stmt->execute()) { $messages[] = 'Failed to add fee: ' . $stmt->error; }
            $stmt->close();
        } else { $messages[] = 'Failed to prepare insert statement: ' . $conn->error; }
    }
}

// After handling any POST, redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['messages'] = $messages;
    header('Location: fee_management.php');
    exit();
}

// Load courses and fee structures
$courses = [];
$res = $conn->query("SELECT id, name, code, num_semesters, is_active, created_at FROM courses ORDER BY name");
if ($res) { while ($r = $res->fetch_assoc()) { $courses[] = $r; } }

$fees = [];
$res2 = $conn->query("SELECT id, title, course_name, semester, amount, due_date, late_fee, is_active, created_at FROM fee_structures ORDER BY course_name, semester");
if ($res2) { while ($r = $res2->fetch_assoc()) { $fees[] = $r; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Management - Admin</title>
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

/* Content layout */
.content-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 32px;
}

/* Main panel */
.main-panel {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.breadcrumbs {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 24px;
}

.breadcrumbs a {
    color: #ff6a00;
    text-decoration: none;
}

.breadcrumbs a:hover {
    text-decoration: underline;
}

/* Fee structure form */
.fee-form-section {
    margin-bottom: 32px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.section-icon {
    width: 40px;
    height: 40px;
    background: #ff6a00;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
}

.section-description {
    color: #64748b;
    margin-bottom: 24px;
    margin-left: 52px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.form-input {
    height: 48px;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
    background: white;
}

.form-input:focus {
    outline: none;
    border-color: #ff6a00;
    box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.1);
}

.form-select {
    height: 48px;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px;
    padding-right: 40px;
    appearance: none;
}

.form-select:focus {
    outline: none;
    border-color: #ff6a00;
    box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.1);
}

.save-button {
    background: #ff6a00;
    color: white;
    border: none;
    padding: 14px 32px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    width: 100%;
    justify-content: center;
}

.save-button:hover {
    background: #e65e00;
}

/* Right sidebar */
.sidebar-panel {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.info-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.info-card h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 16px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    color: #64748b;
    font-size: 14px;
}

.stat-value {
    font-weight: 600;
    color: #1e293b;
}

.stat-value.highlight {
    color: #ff6a00;
}

.recent-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.recent-item:last-child {
    border-bottom: none;
}

.recent-name {
    font-weight: 500;
    color: #1e293b;
    font-size: 14px;
}

.recent-amount {
    font-weight: 600;
    color: #1e293b;
}

.recent-edit {
    color: #64748b;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s;
}

.recent-edit:hover {
    background: #f1f5f9;
    color: #ff6a00;
}

.help-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.help-icon {
    width: 40px;
    height: 40px;
    background: #ff6a00;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    margin: 0 auto 12px;
}

.help-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
}

.help-description {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 16px;
}

.help-button {
    background: white;
    color: #ff6a00;
    border: 1px solid #e2e8f0;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.help-button:hover {
    background: #f8fafc;
}

/* Tables */
.table-container {
    margin-top: 32px;
}

.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table thead th {
    background: #f8fafc;
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
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
    color: #374151;
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge.active {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.badge.inactive {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
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

/* Flash messages */
.flash {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-weight: 500;
}

.flash.error {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

/* Responsive */
@media (max-width: 1024px) {
    .content-layout {
        grid-template-columns: 1fr;
    }
    
    .sidebar-panel {
        order: -1;
    }
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s;
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <?php $active='fees'; include __DIR__.'/partials/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <h1>Fee Management</h1>
                    <p>Create and manage fee structures</p>
                </div>
                <div class="header-right">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar">ZA</div>
                        <div class="user-name">Zion Admin</div>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $m): ?>
                    <div class="flash error"><?php echo htmlspecialchars($m); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Content Layout -->
            <div class="content-layout">
                <!-- Main Panel -->
                <div class="main-panel">
                    <div class="breadcrumbs">
                        <a href="admin_dashboard.php">Dashboard</a> > Fee Management
                    </div>

                    <!-- Courses Section -->
                    <div id="courses" class="fee-form-section" style="margin-bottom:32px;">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="section-title">Courses</div>
                        </div>
                        <div class="section-description">Add new courses and define the number of semesters.</div>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_course" />
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Course Name</label>
                                    <input type="text" name="course_name" class="form-input" placeholder="e.g., B.Sc. Computer Science" required />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Course Code (optional)</label>
                                    <input type="text" name="course_code" class="form-input" placeholder="e.g., BSC-CS" />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Number of Semesters</label>
                                    <input type="number" name="num_semesters" class="form-input" min="1" max="12" value="6" required />
                                </div>
                            </div>
                            <button type="submit" class="save-button" style="width:auto; padding:12px 20px;">
                                <i class="fas fa-plus"></i>
                                Add Course
                            </button>
                        </form>

                        <div class="table-container">
                            <?php if (!empty($courses)): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Code</th>
                                            <th>Semesters</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($courses as $c): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($c['name']); ?></td>
                                                <td><?php echo htmlspecialchars($c['code'] ?? ''); ?></td>
                                                <td><?php echo (int)$c['num_semesters']; ?></td>
                                                <td><?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div style="text-align:center; color:#64748b; padding: 20px;">No courses yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Fee Structure Form -->
                    <div class="fee-form-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="section-title">Create Fee Structure</div>
                        </div>
                        <div class="section-description">Define fee details for courses and semesters.</div>

                        <form method="POST">
                            <input type="hidden" name="action" value="add_fee" />
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Title (optional)</label>
                                    <input type="text" name="fee_title" class="form-input" placeholder="e.g., Semester 1 Tuition" />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Course</label>
                                    <select name="fee_course" id="fee_course" class="form-select" required>
                                        <option value="">Select Course</option>
                                        <?php foreach ($courses as $c): ?>
                                            <option value="<?php echo htmlspecialchars($c['name']); ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Semester</label>
                                    <select name="fee_semester" id="fee_semester" class="form-select" required>
                                        <option value="">Select Semester</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Amount (₹)</label>
                                    <input type="number" step="0.01" name="fee_amount" class="form-input" placeholder="e.g., 12000" value="12000" required />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Payment Last Date</label>
                                    <input type="date" name="fee_due_date" class="form-input" />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Late Fee (₹)</label>
                                    <input type="number" step="0.01" name="late_fee" class="form-input" placeholder="e.g., 200" value="200" />
                                </div>
                            </div>
                            <button type="submit" class="save-button">
                                <i class="fas fa-save"></i>
                                Save Fee
                            </button>
                        </form>
                    </div>

                    <!-- Fee Structures Table -->
                    <div class="table-container">
                        <?php if (!empty($fees)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Course</th>
                                        <th>Semester</th>
                                        <th>Amount</th>
                                        <th>Due</th>
                                        <th>Late Fee</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($fees as $f): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($f['title'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($f['course_name']); ?></td>
                                            <td><?php echo htmlspecialchars($f['semester']); ?></td>
                                            <td>₹<?php echo number_format((float)$f['amount'], 2); ?></td>
                                            <td><?php echo $f['due_date'] ? htmlspecialchars($f['due_date']) : '—'; ?></td>
                                            <td><?php echo $f['late_fee'] !== null ? '₹'.number_format((float)$f['late_fee'], 2) : '—'; ?></td>
                                            <td>
                                                <span class="badge <?php echo $f['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $f['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="toggle_fee" />
                                                        <input type="hidden" name="fee_id" value="<?php echo (int)$f['id']; ?>" />
                                                        <button class="btn-small <?php echo $f['is_active'] ? '' : 'primary'; ?>" type="submit">
                                                            <?php echo $f['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                        </button>
                                                    </form>
                                                    <details>
                                                        <summary class="btn-small" style="cursor:pointer;">Edit</summary>
                                                        <form method="POST" style="margin-top: 8px; padding: 12px; background: #f8fafc; border-radius: 8px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                                                            <input type="hidden" name="action" value="edit_fee" />
                                                            <input type="hidden" name="fee_id" value="<?php echo (int)$f['id']; ?>" />
                                                            <div>
                                                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Title</label>
                                                                <input type="text" name="fee_title" value="<?php echo htmlspecialchars($f['title'] ?? ''); ?>" style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;" />
                                                            </div>
                                                            <div>
                                                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Course</label>
                                                                <select name="fee_course" required style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
                                                                    <?php foreach ($courses as $c): ?>
                                                                        <option value="<?php echo htmlspecialchars($c['name']); ?>" <?php echo $c['name'] === $f['course_name'] ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($c['name']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Semester</label>
                                                                <input type="text" name="fee_semester" value="<?php echo htmlspecialchars($f['semester']); ?>" style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;" />
                                                            </div>
                                                            <div>
                                                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Amount</label>
                                                                <input type="number" step="0.01" name="fee_amount" value="<?php echo htmlspecialchars((string)$f['amount']); ?>" required style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;" />
                                                            </div>
                                                            <div>
                                                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Due Date</label>
                                                                <input type="date" name="fee_due_date" value="<?php echo $f['due_date'] ? htmlspecialchars($f['due_date']) : ''; ?>" style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;" />
                                                            </div>
                                                            <div>
                                                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Late Fee</label>
                                                                <input type="number" step="0.01" name="late_fee" value="<?php echo $f['late_fee'] !== null ? htmlspecialchars((string)$f['late_fee']) : ''; ?>" style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;" />
                                                            </div>
                                                            <div style="grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px;">
                                                                <button class="btn-small primary" type="submit">Save</button>
                                                            </div>
                                                        </form>
                                                    </details>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this fee? This cannot be undone.');">
                                                        <input type="hidden" name="action" value="delete_fee" />
                                                        <input type="hidden" name="fee_id" value="<?php echo (int)$f['id']; ?>" />
                                                        <button class="btn-small danger" type="submit">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div style="text-align: center; color: #64748b; padding: 40px;">No fee structures yet.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <aside class="sidebar-panel">
                    <!-- Quick Stats -->
                    <div class="info-card">
                        <h3>Quick Stats</h3>
                        <div class="stat-item">
                            <span class="stat-label">Total Fee Structures</span>
                            <span class="stat-value"><?php echo count($fees); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Active Courses</span>
                            <span class="stat-value"><?php echo count(array_filter($courses, function($c) { return $c['is_active']; })); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Pending Payments</span>
                            <span class="stat-value highlight">156</span>
                        </div>
                    </div>

                    <!-- Recent Fee Structures -->
                    <div class="info-card">
                        <h3>Recent Fee Structures</h3>
                        <?php 
                        $recent_fees = array_slice($fees, 0, 3);
                        foreach($recent_fees as $f): 
                        ?>
                            <div class="recent-item">
                                <div>
                                    <div class="recent-name"><?php echo htmlspecialchars($f['title'] ?: $f['course_name'] . ' ' . $f['semester']); ?></div>
                                    <div class="recent-amount">₹<?php echo number_format((float)$f['amount'], 0); ?></div>
                                </div>
                                <i class="fas fa-edit recent-edit"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Help Section
                    <div class="help-section">
                        <div class="help-icon">
                            <i class="fas fa-exclamation"></i>
                        </div>
                        <div class="help-title">Need Help?</div>
                        <div class="help-description">Check our documentation for detailed guides on fee management.</div>
                        <button class="help-button">View Docs</button>
                    </div> -->
                </aside>
            </div>
        </main>
    </div>
    <script>
        (function(){
            const courseSel = document.getElementById('fee_course');
            const semSel = document.getElementById('fee_semester');
            function ordinal(n){ const s=["th","st","nd","rd"], v=n%100; return n+(s[(v-20)%10]||s[v]||s[0]); }
            async function loadSemesters(){
                semSel.innerHTML = '<option value="">Select Semester</option>';
                const c = courseSel.value.trim();
                if(!c) return;
                try {
                    const r = await fetch('../auth/get_course_semesters.php?course=' + encodeURIComponent(c), { credentials: 'same-origin' });
                    const data = await r.json();
                    if (data && data.success) {
                        for (let i=1;i<=data.num_semesters;i++){
                            const label = ordinal(i) + ' Semester';
                            const opt = document.createElement('option');
                            opt.value = label;
                            opt.textContent = label;
                            semSel.appendChild(opt);
                        }
                    }
                } catch(e) { /* noop */ }
            }
            if (courseSel) { courseSel.addEventListener('change', loadSemesters); }
        })();
    </script>
</body>
</html>
