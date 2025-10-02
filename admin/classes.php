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

// AJAX handlers for edit panel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && (isset($_POST['ajax']) && $_POST['ajax'] === '1')) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    if ($action === 'get_class') {
        $class_id = (int)($_POST['class_id'] ?? 0);
        $stmt = $conn->prepare("SELECT id, name, code, is_active FROM classes WHERE id = ?");
        if ($stmt) { $stmt->bind_param('i', $class_id); $stmt->execute(); $res = $stmt->get_result(); $row = $res->fetch_assoc(); $stmt->close(); }
        echo json_encode(['success' => (bool)$row, 'class' => $row]);
        exit();
    }
    if ($action === 'update_class') {
        $class_id = (int)($_POST['class_id'] ?? 0);
        $name = trim($_POST['class_name'] ?? '');
        $code = trim($_POST['class_code'] ?? '');
        if ($class_id > 0 && $name !== '') {
            $stmt = $conn->prepare("UPDATE classes SET name = ?, code = ? WHERE id = ?");
            if ($stmt) { $stmt->bind_param('ssi', $name, $code, $class_id); $ok = $stmt->execute(); $stmt->close(); }
            echo json_encode(['success' => isset($ok) && $ok]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
        }
        exit();
    }
}

// Handle add/update/delete class
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_class') {
        $name = trim($_POST['class_name'] ?? '');
        $code = trim($_POST['class_code'] ?? '');
        if ($name !== '') {
            $stmt = $conn->prepare("INSERT INTO classes (name, code) VALUES (?, ?) ON DUPLICATE KEY UPDATE code=VALUES(code), is_active=1");
            if ($stmt) { $stmt->bind_param('ss', $name, $code); if(!$stmt->execute()){ $messages[] = 'Failed to save class: '.$stmt->error; } $stmt->close(); }
            else { $messages[] = 'Failed to prepare statement: '.$conn->error; }
        } else {
            $messages[] = 'Class name is required.';
        }
    }

    if ($action === 'edit_class') {
        $class_id = (int)($_POST['class_id'] ?? 0);
        $name = trim($_POST['class_name'] ?? '');
        $code = trim($_POST['class_code'] ?? '');
        if ($class_id > 0 && $name !== '') {
            $stmt = $conn->prepare("UPDATE classes SET name = ?, code = ? WHERE id = ?");
            if ($stmt) { if(!$stmt->bind_param('ssi', $name, $code, $class_id)) { $messages[] = 'Bind failed: '.$stmt->error; }
                if(!$stmt->execute()) { $messages[] = 'Failed to update class: '.$stmt->error; }
                $stmt->close();
            } else { $messages[] = 'Failed to prepare update: '.$conn->error; }
        } else {
            $messages[] = 'Invalid data for class update.';
        }
    }

    if ($action === 'delete_class') {
        $class_id = (int)($_POST['class_id'] ?? 0);
        if ($class_id > 0) {
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
            if ($stmt) { $stmt->bind_param('i', $class_id); if(!$stmt->execute()) { $messages[] = 'Failed to delete class: '.$stmt->error; } $stmt->close(); }
            else { $messages[] = 'Failed to prepare delete: '.$conn->error; }
        }
    }

    $_SESSION['messages'] = $messages;
    header('Location: classes.php');
    exit();
}

// Load classes
$classes = [];
$res = $conn->query("SELECT id, name, code, is_active, created_at FROM classes ORDER BY CAST(SUBSTRING(name, 7) AS UNSIGNED)");
if ($res) { while ($r = $res->fetch_assoc()) { $classes[] = $r; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../public/theme.css" rel="stylesheet">
    <script defer src="../public/ui.js"></script>
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
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 24px; }
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

/* Edit panel */
.edit-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.3); display: none; align-items: stretch; justify-content: flex-end; z-index: 2000; }
.edit-overlay.open { display: flex; }
.edit-panel { width: 420px; max-width: 90vw; background: #ffffff; height: 100vh; border-left: 1px solid #e2e8f0; box-shadow: -10px 0 20px rgba(0,0,0,0.08); transform: translateX(100%); transition: transform .35s ease; display: flex; flex-direction: column; }
.edit-overlay.open .edit-panel { transform: translateX(0); }
.edit-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
.edit-title { font-weight:700; color:#1e293b; }
.edit-body { padding: 16px 20px; overflow-y: auto; }
.form-group { display:flex; flex-direction:column; gap:6px; margin-bottom:12px; }
.form-group label { font-weight:600; color:#1e293b; font-size:14px; }
.form-control { padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-family:'Inter', sans-serif; font-size:14px; }
.form-actions { padding: 12px 20px; border-top:1px solid #f1f5f9; display:flex; gap:10px; justify-content:flex-end; }
.btn { padding: 10px 16px; border-radius:8px; border:1px solid #e2e8f0; background:#f8fafc; color:#374151; cursor:pointer; font-weight:600; }
.btn.primary { background:#ff6a00; color:#fff; border-color:#ff6a00; }
.btn.primary:hover { background:#e65e00; }
.btn.secondary:hover { background:#eef2f7; }

/* Actions */
.action-buttons { display: flex; gap: 8px; align-items: center; }
.btn-small { padding: 6px 12px; font-size: 12px; border-radius: 6px; border: 1px solid #d1d5db; background: white; color: #374151; cursor: pointer; transition: all 0.2s; }
.btn-small:hover { background: #f8fafc; }
.btn-small.primary { background: #ff6a00; color: white; border-color: #ff6a00; }
.btn-small.primary:hover { background: #e65e00; }
.btn-small.danger { background: #ef4444; color: white; border-color: #ef4444; }
.btn-small.danger:hover { background: #dc2626; }

@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
  .main-content { margin-left: 0; }
  .form-grid { grid-template-columns: 1fr; }
}
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php $active='classes'; include __DIR__.'/partials/admin_sidebar.php'; ?>
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1>Class Management</h1>
                    <p>Manage school classes and grade levels</p>
                </div>
            </header>

            <div class="main-panel">
                <div class="breadcrumbs"><a href="admin_dashboard.php">Dashboard</a> > Classes</div>

                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $m): ?>
                        <div style="padding:12px 16px; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; border-radius:8px; margin-bottom:16px; font-weight:500;"><?php echo htmlspecialchars($m); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="section-title">Add Class</div>
                </div>
                <div class="section-description">Add new classes for your school (e.g., Class 1, Class 2, etc.)</div>

                <form method="POST">
                    <input type="hidden" name="action" value="add_class" />
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Class Name</label>
                            <input type="text" name="class_name" class="form-input" placeholder="e.g., Class 1, Class 2" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Class Code (optional)</label>
                            <input type="text" name="class_code" class="form-input" placeholder="e.g., I, II, III" />
                        </div>
                    </div>
                    <button type="submit" class="save-button"><i class="fas fa-plus"></i> Add Class</button>
                </form>

                <div class="table-container">
                    <?php if (!empty($classes)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Class Code</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($classes as $c): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($c['code'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge <?php echo $c['is_active'] ? 'active' : ''; ?>">
                                                <?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                                        <td class="actions-cell">
                                            <div class="action-buttons">
                                                <button class="btn-small" onclick="openEditClassPanel(<?php echo (int)$c['id']; ?>)" type="button">Edit</button>
                                                <form method="POST" onsubmit="return confirm('Delete this class? This cannot be undone.');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_class" />
                                                    <input type="hidden" name="class_id" value="<?php echo (int)$c['id']; ?>" />
                                                    <button type="submit" class="btn-small danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align:center; color:#64748b; padding: 40px;">
                            <div style="font-size: 48px; color: #e2e8f0; margin-bottom: 16px;">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h3>No classes yet</h3>
                            <p>Add your first class to get started.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Edit Panel UI -->
    <div class="edit-overlay" id="editOverlay" aria-hidden="true">
        <div class="edit-panel" role="dialog" aria-modal="true">
            <div class="edit-header">
                <div class="edit-title">Edit Class</div>
                <button class="btn" id="btnCloseEdit">Close</button>
            </div>
            <div class="edit-body">
                <form id="editForm">
                    <input type="hidden" name="class_id" id="edit_class_id" />
                    <div class="form-group">
                        <label>Class Name</label>
                        <input type="text" class="form-control" name="class_name" id="edit_class_name" required />
                    </div>
                    <div class="form-group">
                        <label>Class Code</label>
                        <input type="text" class="form-control" name="class_code" id="edit_class_code" />
                    </div>
                </form>
            </div>
            <div class="form-actions">
                <button class="btn secondary" id="btnCancelEdit" type="button">Cancel</button>
                <button class="btn primary" id="btnSaveEdit" type="button">Save Changes</button>
            </div>
        </div>
    </div>

    <script>
    (function(){
        const overlay = document.getElementById('editOverlay');
        const btnClose = document.getElementById('btnCloseEdit');
        const btnCancel = document.getElementById('btnCancelEdit');
        const btnSave = document.getElementById('btnSaveEdit');
        const form = document.getElementById('editForm');
        const fields = {
            id: document.getElementById('edit_class_id'),
            name: document.getElementById('edit_class_name'),
            code: document.getElementById('edit_class_code')
        };

        window.openEditClassPanel = async function(classId){
            overlay.classList.add('open');
            overlay.setAttribute('aria-hidden','false');
            const fd = new FormData();
            fd.append('action','get_class'); fd.append('ajax','1'); fd.append('class_id', classId);
            try {
                const res = await fetch('classes.php', { method:'POST', body: fd });
                const data = await res.json();
                if (data && data.success) {
                    const c = data.class;
                    fields.id.value = c.id;
                    fields.name.value = c.name || '';
                    fields.code.value = c.code || '';
                }
            } catch(e) {}
        }

        function close(){ overlay.classList.remove('open'); overlay.setAttribute('aria-hidden','true'); }
        btnClose.addEventListener('click', close); btnCancel.addEventListener('click', close);

        btnSave.addEventListener('click', async function(){
            const fd = new FormData(form);
            fd.append('action','update_class'); fd.append('ajax','1');
            try {
                const res = await fetch('classes.php', { method:'POST', body: fd });
                const data = await res.json();
                if (data && data.success) {
                    const rowBtn = document.querySelector(`button[onclick="openEditClassPanel(${fields.id.value})"]`);
                    const row = rowBtn ? rowBtn.closest('tr') : null;
                    if (row) {
                        row.cells[0].innerHTML = '<strong>' + fields.name.value + '</strong>';
                        row.cells[1].textContent = fields.code.value;
                    }
                    close();
                } else {
                    alert(data && data.message ? data.message : 'Failed to update class');
                }
            } catch(e) { alert('Failed to update class'); }
        });
    })();
    </script>
</body>
</html>
