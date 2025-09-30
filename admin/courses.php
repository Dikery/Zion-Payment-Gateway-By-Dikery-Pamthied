<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}
require '../includes/db_connect.php';

// Flash messages
$messages = $_SESSION['messages'] ?? [];
unset($_SESSION['messages']);

// Handle add/update/delete course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_course') {
        $name = trim($_POST['course_name'] ?? '');
        $code = trim($_POST['course_code'] ?? '');
        $num_semesters = (int)($_POST['num_semesters'] ?? 6);
        if ($name !== '' && $num_semesters > 0) {
            $stmt = $conn->prepare("INSERT INTO courses (name, code, num_semesters) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE code=VALUES(code), num_semesters=VALUES(num_semesters), is_active=1");
            if ($stmt) { $stmt->bind_param('ssi', $name, $code, $num_semesters); if(!$stmt->execute()){ $messages[] = 'Failed to save course: '.$stmt->error; } $stmt->close(); }
            else { $messages[] = 'Failed to prepare statement: '.$conn->error; }
        } else {
            $messages[] = 'Course name and number of semesters are required.';
        }
    }

    if ($action === 'edit_course') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        $name = trim($_POST['course_name'] ?? '');
        $code = trim($_POST['course_code'] ?? '');
        $num_semesters = (int)($_POST['num_semesters'] ?? 0);
        if ($course_id > 0 && $name !== '' && $num_semesters > 0) {
            $stmt = $conn->prepare("UPDATE courses SET name = ?, code = ?, num_semesters = ? WHERE id = ?");
            if ($stmt) { if(!$stmt->bind_param('ssii', $name, $code, $num_semesters, $course_id)) { $messages[] = 'Bind failed: '.$stmt->error; }
                if(!$stmt->execute()) { $messages[] = 'Failed to update course: '.$stmt->error; }
                $stmt->close();
            } else { $messages[] = 'Failed to prepare update: '.$conn->error; }
        } else {
            $messages[] = 'Invalid data for course update.';
        }
    }

    if ($action === 'delete_course') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        if ($course_id > 0) {
            $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
            if ($stmt) { $stmt->bind_param('i', $course_id); if(!$stmt->execute()) { $messages[] = 'Failed to delete course: '.$stmt->error; } $stmt->close(); }
            else { $messages[] = 'Failed to prepare delete: '.$conn->error; }
        }
    }

    $_SESSION['messages'] = $messages;
    header('Location: courses.php');
    exit();
}

// Load courses
$courses = [];
$res = $conn->query("SELECT id, name, code, num_semesters, is_active, created_at FROM courses ORDER BY name");
if ($res) { while ($r = $res->fetch_assoc()) { $courses[] = $r; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../public/theme.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; }
.admin-layout { display: flex; min-height: 100vh; }

/* Sidebar styles (match other admin pages) */
.sidebar { width: 280px; background: #ffffff; border-right: 1px solid #e2e8f0; padding: 24px 0; position: fixed; height: 100vh; overflow-y: auto; }
.sidebar-header { padding: 0 24px 32px; border-bottom: 1px solid #e2e8f0; margin-bottom: 24px; }
.logo { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
.logo-icon { width: 40px; height: 40px; background: #ff6a00; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px; }
.logo-text { font-size: 18px; font-weight: 700; color: #1e293b; }
.logo-subtitle { font-size: 14px; color: #64748b; margin-left: 52px; }
.nav-menu { list-style: none; padding: 0 16px; }
.nav-item { margin-bottom: 4px; }
.nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #64748b; text-decoration: none; border-radius: 8px; transition: all 0.2s; font-weight: 500; }
.nav-link:hover { background: #f1f5f9; color: #1e293b; }
.nav-link.active { background: #fff3e8; color: #ff6a00; }
.nav-icon { width: 20px; text-align: center; }

/* Content */
.main-content { flex: 1; margin-left: 280px; padding: 24px; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid #e2e8f0; }
.header-left h1 { font-size: 28px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.header-left p { color: #64748b; font-size: 16px; }
.main-panel { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
.breadcrumbs { color: #64748b; font-size: 14px; margin-bottom: 24px; }
.breadcrumbs a { color: #ff6a00; text-decoration: none; }
.breadcrumbs a:hover { text-decoration: underline; }
.section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.section-icon { width: 40px; height: 40px; background: #ff6a00; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; }
.section-title { font-size: 20px; font-weight: 600; color: #1e293b; }
.section-description { color: #64748b; margin-bottom: 24px; margin-left: 52px; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-label { font-weight: 600; color: #374151; font-size: 14px; }
.form-input { height: 48px; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; background: white; }
.form-input:focus { outline: none; border-color: #ff6a00; box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.1); }
.save-button { background: #ff6a00; color: white; border: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
.save-button:hover { background: #e65e00; }
.table-container { margin-top: 32px; }
.table { width: 100%; border-collapse: separate; border-spacing: 0; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.table thead th { background: #f8fafc; padding: 16px; text-align: left; font-weight: 600; color: #374151; font-size: 14px; border-bottom: 1px solid #e2e8f0; }
.table td { padding: 16px; font-size: 14px; color: #374151; }
.badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.badge.active { background: rgba(16, 185, 129, 0.1); color: #10b981; }

/* Actions */
.action-buttons { display: flex; gap: 8px; align-items: center; }
.btn-small { padding: 6px 12px; font-size: 12px; border-radius: 6px; border: 1px solid #d1d5db; background: white; color: #374151; cursor: pointer; transition: all 0.2s; }
.btn-small:hover { background: #f8fafc; }
.btn-small.primary { background: #ff6a00; color: white; border-color: #ff6a00; }
.btn-small.primary:hover { background: #e65e00; }
.btn-small.danger { background: #ef4444; color: white; border-color: #ef4444; }
.btn-small.danger:hover { background: #dc2626; }

/* Edit panel styling */
td.actions-cell { position: relative; }
._actions-inline { display: inline-flex; gap: 8px; align-items: center; }
details.edit-panel { position: relative; }
details.edit-panel summary { list-style: none; }
details.edit-panel summary::-webkit-details-marker { display: none; }
details.edit-panel summary { display: inline-flex; align-items: center; gap: 6px; }
details.edit-panel summary:after { content: '\25BC'; font-size: 10px; color: #64748b; transition: transform .2s ease; }
details.edit-panel[open] summary:after { transform: rotate(180deg); }
.edit-content { margin-top: 8px; width: 380px; max-width: 90vw; padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 20px rgba(0,0,0,0.06); opacity: 0; transform: translateY(-4px) scale(0.98); animation: panelIn .22s ease forwards; }
.edit-grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
.edit-grid label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #374151; }
.edit-grid input { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; }
.edit-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }

@keyframes panelIn {
  to { opacity: 1; transform: translateY(0) scale(1); }
}

@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
  .main-content { margin-left: 0; }
  .form-grid { grid-template-columns: 1fr; }
}
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $active='courses'; include __DIR__.'/partials/admin_sidebar.php'; ?>
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Courses</h1>
                    <p>Add and manage academic courses</p>
                </div>
            </header>

            <div class="main-panel">
                <div class="breadcrumbs"><a href="admin_dashboard.php">Dashboard</a> > Courses</div>

                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $m): ?>
                        <div style="padding:12px 16px; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; border-radius:8px; margin-bottom:16px; font-weight:500;"><?php echo htmlspecialchars($m); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-book"></i></div>
                    <div class="section-title">Add Course</div>
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
                    <button type="submit" class="save-button"><i class="fas fa-plus"></i> Add Course</button>
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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($courses as $c): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                                        <td><?php echo htmlspecialchars($c['code'] ?? ''); ?></td>
                                        <td><?php echo (int)$c['num_semesters']; ?></td>
                                        <td><?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                        <td class="actions-cell">
                                            <div class="_actions-inline">
                                                <details class="edit-panel">
                                                    <summary class="btn-small" style="cursor:pointer;">Edit</summary>
                                                    <div class="edit-content">
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="edit_course" />
                                                            <input type="hidden" name="course_id" value="<?php echo (int)$c['id']; ?>" />
                                                            <div class="edit-grid">
                                                                <div>
                                                                    <label>Name</label>
                                                                    <input type="text" name="course_name" value="<?php echo htmlspecialchars($c['name']); ?>" required />
                                                                </div>
                                                                <div>
                                                                    <label>Code</label>
                                                                    <input type="text" name="course_code" value="<?php echo htmlspecialchars($c['code'] ?? ''); ?>" />
                                                                </div>
                                                                <div>
                                                                    <label>Semesters</label>
                                                                    <input type="number" min="1" max="12" name="num_semesters" value="<?php echo (int)$c['num_semesters']; ?>" required />
                                                                </div>
                                                            </div>
                                                            <div class="edit-footer">
                                                                <button type="submit" class="btn-small primary">Save</button>
                                                                <button type="button" class="btn-small" onclick="this.closest('details').removeAttribute('open')">Close</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </details>
                                                <form method="POST" onsubmit="return confirm('Delete this course? This cannot be undone.');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_course" />
                                                    <input type="hidden" name="course_id" value="<?php echo (int)$c['id']; ?>" />
                                                    <button type="submit" class="btn-small danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align:center; color:#64748b; padding: 20px;">No courses yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>


