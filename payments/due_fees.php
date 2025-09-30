<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.html'); exit(); }
require '../includes/db_connect.php';

$course = $_SESSION['course'] ?? null;
$semester = $_SESSION['semester'] ?? null;

// Normalize semester to a canonical label like "6th Semester"
$normalized = null;
if ($semester) {
  if (preg_match('/(\d{1,2})/', $semester, $m)) {
    $n = (int)$m[1];
    $sfx = 'th'; if ($n % 100 < 11 || $n % 100 > 13) { $sfx = [1=>'st',2=>'nd',3=>'rd'][$n % 10] ?? 'th'; }
    $normalized = $n . $sfx . ' Semester';
  } else {
    $normalized = $semester;
  }
}
$fees = [];
if ($course) {
  // Pull all active fees for the course; filter by semester match if possible
  $stmt = $conn->prepare("SELECT id, title, amount, due_date, is_active, semester FROM fee_structures WHERE course_name = ? AND is_active = 1 ORDER BY due_date IS NULL, due_date ASC, id DESC");
  if ($stmt) { $stmt->bind_param('s', $course); $stmt->execute(); $res = $stmt->get_result(); $all = []; while($r=$res->fetch_assoc()){ $all[]=$r; } $stmt->close(); }
  if (!empty($all)) {
    if ($normalized) {
      $num = null; if (preg_match('/(\d{1,2})/', $normalized, $m)) { $num = (int)$m[1]; }
      foreach ($all as $r) {
        // Try exact label first
        if (strcasecmp($r['semester'], $normalized) === 0) { $fees[] = $r; continue; }
        // Then numeric match like "6" in "6th Semester"
        if ($num !== null && preg_match('/(\d{1,2})/', $r['semester'], $mm) && (int)$mm[1] === $num) { $fees[] = $r; }
      }
      if (empty($fees)) { $fees = $all; }
    } else {
      $fees = $all;
    }
  }
}
// Helper: ensure payments table has fee_id column (one-time lightweight migration)
if ($conn) {
  @$conn->query("ALTER TABLE payments ADD COLUMN fee_id INT NULL AFTER user_id");
}

// Build a map of paid fee_ids for this user to avoid per-row queries
$paidFeeIds = [];
$paidMap = [];// fee_id => latest payment id
if ($conn && isset($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];
  $pstmt = $conn->prepare("SELECT fee_id, MAX(id) AS pid FROM payments WHERE user_id = ? AND status = 'completed' AND fee_id IS NOT NULL GROUP BY fee_id");
  if ($pstmt) {
    $pstmt->bind_param('i', $uid);
    $pstmt->execute();
    $pres = $pstmt->get_result();
    while ($prow = $pres->fetch_assoc()) { $paidFeeIds[] = (int)$prow['fee_id']; $paidMap[(int)$prow['fee_id']] = (int)$prow['pid']; }
    $pstmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Due Fees</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="../public/theme.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #f8fafc; min-height:100vh; padding:24px; }
    .container { max-width: 1000px; margin: 0 auto; }
    .card { background: #ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:24px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    .title { color: #1e293b; font-size:1.4rem; font-weight:700; margin-bottom:18px; display:flex; align-items:center; gap:10px; }

    .table { width: 100%; border-collapse: separate; border-spacing: 0; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); border: 1px solid #e2e8f0; }
    .table thead th { background: #f8fafc; padding: 14px 16px; text-align: left; font-weight: 600; color: #374151; font-size: 14px; border-bottom: 1px solid #e2e8f0; }
    .table td { padding: 14px 16px; font-size: 14px; color: #374151; border-bottom: 1px solid #f1f5f9; }
    .table tbody tr:hover { background: #f8fafc; }

    .badge { padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; display:inline-block; }
    .badge.success { background: rgba(16,185,129,.12); color:#10b981; border:1px solid rgba(16,185,129,.25); }
    .badge.active { background: rgba(255,106,0,.12); color:#e65e00; border:1px solid rgba(255,106,0,.25); }
    .badge.inactive { background: rgba(107,114,128,.12); color:#6b7280; border:1px solid rgba(107,114,128,.2); }

    .btn-pay { background:#6366f1; color:#fff; border:none; border-radius:8px; padding:8px 12px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .btn-pay:hover { filter: brightness(.95); }
    .link-receipt { text-decoration:none; color:#667eea; font-weight:600; }
    .link-receipt:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="title"><i class="fas fa-file-invoice-dollar"></i> Your Due Fees</div>
      <?php if (!empty($fees)): ?>
        <table class="table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Amount</th>
              <th>Due Date</th>
              <th>Status</th>
              <th>Receipt</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($fees as $f): ?>
            <?php $paid = in_array((int)$f['id'], $paidFeeIds, true); ?>
            <tr>
              <td><?php echo htmlspecialchars($f['title'] ?? 'Fee'); ?></td>
              <td>₹<?php echo number_format((float)$f['amount'], 2); ?></td>
              <td><?php echo $f['due_date'] ? htmlspecialchars(date('M d, Y', strtotime($f['due_date']))) : '—'; ?></td>
              <td>
                <?php if ($paid): ?>
                  <span class="badge success">Paid</span>
                <?php else: ?>
                  <span class="badge <?php echo $f['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $f['is_active'] ? 'Unpaid' : 'Closed'; ?></span>
                  <?php if ($f['is_active']): ?>
                    <a class="btn-pay" href="../make_payment.php?fee_id=<?php echo (int)$f['id']; ?>&amount=<?php echo urlencode($f['amount']); ?><?php echo $f['due_date'] ? '&due='.urlencode($f['due_date']) : ''; ?>">
                      <i class="fas fa-credit-card"></i> Pay
                    </a>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($paid && !empty($paidMap[(int)$f['id']])): ?>
                  <a href="receipt.php?download=1&receipt_id=<?php echo (int)$paidMap[(int)$f['id']]; ?>" class="link-receipt"><i class="fas fa-download"></i> Download</a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div style="text-align:center; color:#64748b; padding: 24px;">No fees found for your course/semester.</div>
      <?php endif; ?>
      <div style="margin-top:16px;">
        <a class="btn btn-outline" href="../dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
      </div>
    </div>
  </div>
</body>
</html>


