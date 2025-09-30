<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

require '../includes/db_connect.php';

// Handle user actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

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

// Get all users with their details
$users_sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.contact,
                     u.created_at, u.user_type, u.last_payment_date, u.total_paid,
                     sd.student_id, sd.course, sd.semester
              FROM users u
              LEFT JOIN student_details sd ON u.id = sd.user_id
              ORDER BY u.created_at DESC";
$users_result = $conn->query($users_sql);

// Get statistics
$stats_sql = "SELECT
    COUNT(*) as total_users,
    COUNT(CASE WHEN user_type = 'student' THEN 1 END) as total_students,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
    AVG(total_paid) as avg_payment
    FROM users";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
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
                    <a href="#" class="add-user-btn">
                        <i class="fas fa-user-plus"></i>
                        Add New User
                    </a>
                </div>

                <div class="search-filter-section">
                    <div class="search-grid">
                        <div class="search-group">
                            <label class="search-label">Search Users</label>
                            <input type="text" class="search-input" id="searchInput" placeholder="Search by name, email, or student ID">
                        </div>
                        <div class="search-group">
                            <label class="search-label">Filter by Course</label>
                            <select class="search-input" id="courseFilter">
                                <option value="">All Courses</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Arts">Arts</option>
                                <option value="Science">Science</option>
                            </select>
                        </div>
                        <div class="search-group">
                            <label class="search-label">Filter by Type</label>
                            <select class="search-input" id="typeFilter">
                                <option value="">All Types</option>
                                <option value="student">Students</option>
                                <option value="admin">Admins</option>
                            </select>
                        </div>
                        <div class="search-group">
                            <button class="search-btn" onclick="applyFilters()">
                                <i class="fas fa-search"></i>
                                Apply Filters
                            </button>
                        </div>
                    </div>
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
                        <?php if ($users_result->num_rows > 0): ?>
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
                                        <div><strong>Course:</strong> <?php echo htmlspecialchars($user['course'] ?? 'N/A'); ?></div>
                                        <div><strong>Semester:</strong> <?php echo htmlspecialchars($user['semester'] ?? 'N/A'); ?></div>
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
                                            <button class="btn-small" onclick="editUser(<?php echo $user['id']; ?>)">
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

        function editUser(userId) {
            // In a real application, this would open an edit modal or redirect to edit page
            alert('Edit user functionality would be implemented here. User ID: ' + userId);
        }
    </script>
</body>
</html>
