<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.html");
  exit();
}
require 'includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Student');
$student_id = $_SESSION['student_id'] ?? 'N/A';
$class_level = $_SESSION['class_level'] ?? null;

// Pull latest totals
$total_paid = 0.00;
$outstanding_amount = 0.00;
$due_date_label = '—';
$days_left_label = '';
$sum_paid_for_fee = 0.00;

$sql = "SELECT u.total_paid, COALESCE(sd.outstanding_amount, 0) AS outstanding_amount,
               sd.class_level
        FROM users u
        LEFT JOIN student_details sd ON sd.user_id = u.id
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $total_paid = (float)($row['total_paid'] ?? 0);
    $outstanding_amount = (float)($row['outstanding_amount'] ?? $outstanding_amount);
    if (empty($class_level) && !empty($row['class_level'])) { $class_level = $row['class_level']; }
  }
  $stmt->close();
}

// Find the most urgent unpaid fee for this student's class level
$applicable_fees = [];
$earliest_due_date = null;
$most_urgent_fee = null;
$total_outstanding = 0.0;

if (!empty($class_level)) {
  // Get all active fees for this class level, ordered by due date
  $fee_stmt = $conn->prepare("SELECT id, title, amount, due_date FROM fee_structures WHERE class_level = ? AND is_active = 1 ORDER BY due_date IS NULL, due_date ASC");
  if ($fee_stmt) {
    $fee_stmt->bind_param("s", $class_level);
    $fee_stmt->execute();
    $fee_res = $fee_stmt->get_result();
    
    while ($f = $fee_res->fetch_assoc()) {
      // Calculate outstanding amount for this specific fee
      $sum_sql = "SELECT COALESCE(SUM(amount), 0) AS sum_paid FROM payments WHERE user_id = ? AND fee_id = ? AND status IN ('completed','success','SUCCESS','Completed')";
      $sum_stmt = $conn->prepare($sum_sql);
      $fee_outstanding = 0.0;
      if ($sum_stmt) {
        $sum_stmt->bind_param("ii", $user_id, $f['id']);
        $sum_stmt->execute();
        $sum_res = $sum_stmt->get_result();
        $sum_paid = 0.0;
        if ($srow = $sum_res->fetch_assoc()) { 
          $sum_paid = (float)$srow['sum_paid']; 
        }
        $sum_stmt->close();
        
        // Calculate outstanding for this fee (never below zero)
        $fee_outstanding = max(0.0, (float)$f['amount'] - $sum_paid);
        $total_outstanding += $fee_outstanding;
      }
      
      // Only consider fees that have outstanding amounts
      if ($fee_outstanding > 0) {
        $applicable_fees[] = array_merge($f, ['outstanding' => $fee_outstanding]);
        
        // Track earliest due date from unpaid fees only
        if (!empty($f['due_date'])) {
          $due_ts = strtotime($f['due_date']);
          if ($earliest_due_date === null || $due_ts < $earliest_due_date) {
            $earliest_due_date = $due_ts;
            $most_urgent_fee = $f;
          }
        }
      }
    }
    $fee_stmt->close();
    
    // Set the outstanding amount to the most urgent fee's amount only
    if ($most_urgent_fee) {
      // Find the outstanding amount for the most urgent fee
      $urgent_sum_sql = "SELECT COALESCE(SUM(amount), 0) AS sum_paid FROM payments WHERE user_id = ? AND fee_id = ? AND status IN ('completed','success','SUCCESS','Completed')";
      $urgent_sum_stmt = $conn->prepare($urgent_sum_sql);
      if ($urgent_sum_stmt) {
        $urgent_sum_stmt->bind_param("ii", $user_id, $most_urgent_fee['id']);
        $urgent_sum_stmt->execute();
        $urgent_sum_res = $urgent_sum_stmt->get_result();
        $urgent_sum_paid = 0.0;
        if ($urgent_srow = $urgent_sum_res->fetch_assoc()) { 
          $urgent_sum_paid = (float)$urgent_srow['sum_paid']; 
        }
        $urgent_sum_stmt->close();
        
        // Set outstanding to only the most urgent fee's amount
        $outstanding_amount = max(0.0, (float)$most_urgent_fee['amount'] - $urgent_sum_paid);
      }
    } else {
      $outstanding_amount = 0.0;
    }
    
    // Set due date information based on earliest due from unpaid fees
    if ($earliest_due_date !== null && $most_urgent_fee) {
      $due_date_label = date('M d, Y', $earliest_due_date);
      $days_diff = floor(($earliest_due_date - time()) / 86400);
      $days_left_label = $days_diff >= 0 ? $days_diff . ' days left' : abs($days_diff) . ' days overdue';
    }
  }
}

// Recent payments (activity)
$recent_payments = [];
$recent_sql = "SELECT id, amount, status, created_at FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$recent_stmt = $conn->prepare($recent_sql);
if ($recent_stmt) {
  $recent_stmt->bind_param("i", $user_id);
  $recent_stmt->execute();
  $recent_res = $recent_stmt->get_result();
  while ($r = $recent_res->fetch_assoc()) { $recent_payments[] = $r; }
  $recent_stmt->close();
}

// Latest payment id for download receipt
$latest_payment_id = null;
if (!empty($recent_payments)) {
  $latest_payment_id = $recent_payments[0]['id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - Zion Fee Portal</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="public/theme.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script defer src="public/ui.js"></script>
  <style>
    :root {
      --accent: #ff6a00;
      --accent-600: #7c3aed;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      padding: 24px;
    }

    .dashboard-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr; gap: 24px; }

    .header { background:var(--card); border:1px solid var(--border); border-radius:12px; padding: 20px 24px; display:flex; justify-content:space-between; align-items:center; position:relative; overflow:hidden; }

    .header::before { content:''; position:absolute; inset:0; height:1px; top:0; background: linear-gradient(90deg, rgba(99,102,241,.35), rgba(139,92,246,.35)); }

    @keyframes gradient {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }

    .welcome-section {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .welcome-icon { font-size: 2rem; color: var(--accent); }

    .welcome-text h2 { color:var(--text); font-size: 1.5rem; font-weight:700; margin-bottom: 6px; letter-spacing:-.01em; }

    .welcome-subtitle { color:var(--muted); font-size:.9rem; }

    .nav-menu {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    .nav-btn { padding: 10px 16px; border: 1px solid var(--border); border-radius: 8px; text-decoration:none; font-weight:600; font-size:.9rem; cursor:pointer; transition: all .2s ease; display:flex; align-items:center; gap:8px; background: transparent; color:var(--text); }

    .nav-btn:hover { background: rgba(0,0,0,.05); }

    .nav-btn.primary { background:var(--accent); border-color:var(--accent); color:#fff; }
    .nav-btn.primary:hover { background:var(--accent-600); border-color:var(--accent-600); }

    .nav-btn.secondary { background: transparent; color:var(--text); border-color:var(--border); }
    .nav-btn.secondary:hover { background: rgba(0,0,0,.05); }

    .nav-btn.danger { background:var(--danger); border-color:var(--danger); color:#fff; }
    .nav-btn.danger:hover { background:#dc2626; border-color:#dc2626; }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card { background:var(--card); border:1px solid var(--border); border-radius: 12px; padding: 20px; position:relative; overflow:hidden; transition: all .2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

    .stat-card:hover { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

    .stat-card::before { content:''; position:absolute; left:0; right:0; top:0; height:2px; background:var(--accent); opacity:.6; }

    .stat-icon { font-size: 2.2rem; margin-bottom: 12px; color:var(--accent); }

    .stat-value { font-size: 2rem; font-weight:700; color:var(--text); margin-bottom:6px; }

    .stat-label { color:var(--muted); font-size:.9rem; font-weight:500; }

    .stat-trend {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin-top: 10px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .stat-trend.up {
      color: var(--success);
    }

    .stat-trend.down {
      color: var(--danger);
    }

    .quick-actions { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

    .quick-actions-title { font-size:1.2rem; font-weight:700; color:var(--text); margin-bottom:16px; display:flex; align-items:center; gap:10px; }

    .actions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }

    .action-card { background:#f8f9fa; border-radius: 10px; padding:16px; text-decoration:none; color:var(--text); transition: all .2s ease; border:1px solid var(--border); position:relative; overflow:hidden; }
    .action-card:hover { background:var(--card); border-color: var(--accent); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

    .action-icon { font-size: 1.8rem; color:var(--accent); margin-bottom: 10px; }

    .action-title { font-weight:700; margin-bottom: 4px; }

    .action-desc { font-size:.85rem; color:var(--muted); }

    .recent-activity { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

    .activity-title { font-size:1.2rem; font-weight:700; color:var(--text); margin-bottom:16px; display:flex; align-items:center; gap:10px; }

    .activity-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .activity-item { display:flex; align-items:center; gap:15px; padding:14px; background:#f8f9fa; border-radius:10px; border:1px solid var(--border); transition: all .2s ease; }

    .activity-item:hover { background:var(--card); }

    .activity-icon { width:40px; height:40px; border-radius:10px; background: rgba(99,102,241,0.08); display:flex; align-items:center; justify-content:center; color:var(--accent); font-size:1.1rem; }

    .activity-content {
      flex: 1;
    }

    .activity-text { font-weight:600; color:var(--text); margin-bottom:3px; }

    .activity-date { font-size:.85rem; color:var(--muted); }

    .activity-amount { font-weight:700; color:var(--success); }

    .loading {
      text-align: center;
      padding: 40px;
      color: var(--muted);
    }

    .spinner {
      border: 3px solid #f3f3f3;
      border-top: 3px solid var(--accent);
      border-radius: 50%;
      width: 30px;
      height: 30px;
      animation: spin 1s linear infinite;
      display: inline-block;
      margin-right: 15px;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
      .dashboard-container {
        padding: 10px;
        gap: 20px;
      }

      .header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
      }

      .nav-menu {
        flex-wrap: wrap;
        justify-content: center;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .actions-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <div class="header">
      <div class="welcome-section">
        <div class="welcome-icon">
          <i class="fas fa-user-graduate"></i>
        </div>
        <div class="welcome-text">
          <h2>Welcome back, <span id="studentName"><?php echo htmlspecialchars($full_name); ?></span></h2>
          <div class="welcome-subtitle">
            Student ID: <strong><?php echo htmlspecialchars($student_id); ?></strong>
            <?php if (!empty($class_level)): ?>
              · Class: <strong><?php echo htmlspecialchars($class_level); ?></strong>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="nav-menu">
        <!-- <a href="make_payment.php" class="nav-btn primary">
          <i class="fas fa-credit-card"></i>
          Make Payment
        </a>
        <a href="payment_history.php" class="nav-btn secondary">
          <i class="fas fa-history"></i>
          History
        </a> -->
        <a href="auth/logout.php" class="nav-btn danger">
          <i class="fas fa-sign-out-alt"></i>
          Logout
        </a>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" onclick="window.location.href='payments/due_fees.php'" style="cursor:pointer;">
        <div class="stat-icon">
          <i class="fas fa-wallet"></i>
        </div>
        <div class="stat-value">₹<span id="outstandingAmount"><?php echo number_format((float)$outstanding_amount, 0); ?></span></div>
        <div class="stat-label"><?php echo $outstanding_amount > 0 ? 'Outstanding Dues (click to view)' : 'All dues cleared'; ?></div>
        <div class="stat-trend up">
          <i class="fas fa-arrow-up"></i>
          <?php echo $outstanding_amount > 0 ? 'Due soon' : 'Up to date'; ?>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-value">₹<span id="paidAmount"><?php echo number_format($total_paid, 0); ?></span></div>
        <div class="stat-label">Total Paid</div>
        <div class="stat-trend up">
          <i class="fas fa-arrow-up"></i>
          +15% this semester
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-value" id="nextDueDate"><?php echo $due_date_label; ?></div>
        <div class="stat-label">Next Due Date</div>
        <?php if (!empty($days_left_label)): ?>
          <div class="stat-trend down">
            <i class="fas fa-clock"></i>
            <?php echo htmlspecialchars($days_left_label); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="quick-actions">
      <div class="quick-actions-title">
        <i class="fas fa-bolt"></i>
        Quick Actions
      </div>
      <div class="actions-grid">
        <a href="make_payment.php" class="action-card">
          <div class="action-icon">
            <i class="fas fa-credit-card"></i>
          </div>
          <div class="action-title">Make Payment</div>
          <div class="action-desc">Pay your outstanding fees securely</div>
        </a>

        <a href="payment_history.php" class="action-card">
          <div class="action-icon">
            <i class="fas fa-receipt"></i>
          </div>
          <div class="action-title">Payment History</div>
          <div class="action-desc">View all your past transactions</div>
        </a>

        <a href="<?php echo $latest_payment_id ? 'payments/receipt.php?download=1&receipt_id=' . urlencode($latest_payment_id) : '#'; ?>" class="action-card" <?php echo $latest_payment_id ? '' : 'onclick="alert(\'No receipts available yet.\'); return false;"'; ?>>
          <div class="action-icon">
            <i class="fas fa-download"></i>
          </div>
          <div class="action-title">Download Receipt</div>
          <div class="action-desc">Get your fee receipts</div>
        </a>
      </div>
    </div>

    <div class="recent-activity">
      <div class="activity-title">
        <i class="fas fa-clock"></i>
        Recent Activity
      </div>
      <div class="activity-list">
        <?php if (!empty($recent_payments)): ?>
          <?php foreach ($recent_payments as $p): ?>
            <div class="activity-item">
              <div class="activity-icon">
                <i class="fas fa-receipt"></i>
              </div>
              <div class="activity-content">
                <div class="activity-text">Payment of <span class="activity-amount">₹<?php echo number_format((float)$p['amount'], 2); ?></span> <?php echo htmlspecialchars($p['status']); ?></div>
                <div class="activity-date"><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($p['created_at']))); ?></div>
              </div>
              <div>
                <a href="payments/receipt.php?download=1&receipt_id=<?php echo (int)$p['id']; ?>" class="nav-btn secondary" style="padding:8px 12px;">Download</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="text-align:center; color:#666; padding:20px;">No recent activity yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Add entrance animations
      const cards = document.querySelectorAll('.stat-card, .quick-actions, .recent-activity');

      cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';

        setTimeout(() => {
          card.style.transition = 'all 0.6s ease';
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, index * 150);
      });

      // Subtle pulse animation on outstanding amount on load
      const outstandingEl = document.getElementById('outstandingAmount');
      if (outstandingEl) {
        outstandingEl.style.transform = 'scale(1.05)';
        setTimeout(() => { outstandingEl.style.transform = 'scale(1)'; }, 200);
      }

      // Add floating particles effect
      function createParticles() {
        const container = document.body;
        const particle = document.createElement('div');
        particle.style.position = 'fixed';
        particle.style.width = '4px';
        particle.style.height = '4px';
        particle.style.background = 'rgba(255, 255, 255, 0.3)';
        particle.style.borderRadius = '50%';
        particle.style.pointerEvents = 'none';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = '100vh';
        particle.style.animation = `particle-float ${Math.random() * 3 + 2}s linear infinite`;
        particle.style.zIndex = '-1';

        container.appendChild(particle);

        setTimeout(() => {
          particle.remove();
        }, 5000);
      }

      // Create particles periodically
      setInterval(createParticles, 2000);
    });
  </script>
</body>
</html>
