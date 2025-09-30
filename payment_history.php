<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.html");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment History - Zion Fee Portal</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="public/theme.css" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body { font-family:'Inter', sans-serif; background: var(--bg); min-height:100vh; padding:20px; }

    .history-container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .history-header { background: var(--card); border:1px solid var(--border); border-radius:16px; padding:24px; margin-bottom:24px; box-shadow: 0 8px 24px rgba(15,23,42,0.06); text-align:center; position: relative; overflow: hidden; }

    .history-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
      background-size: 200% 100%;
      animation: gradient 3s linear infinite;
    }

    @keyframes gradient {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }

    .history-logo { font-size:2.4rem; color: var(--accent); margin-bottom: 10px; }

    .history-title {
      color: #333;
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .history-subtitle {
      color: #666;
      font-size: 0.95rem;
    }

    .summary-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .summary-card { background: var(--card); border:1px solid var(--border); border-radius: 12px; padding: 18px; text-align:center; box-shadow: 0 8px 24px rgba(15,23,42,0.06); }

    .summary-value { font-size:1.6rem; font-weight:700; color: var(--accent); margin-bottom:5px; }

    .summary-label {
      color: #666;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .filters-section { background: var(--card); border:1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(15,23,42,0.06); }

    .filters-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .filters-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      align-items: end;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .filter-label {
      font-weight: 500;
      color: #333;
      font-size: 0.9rem;
    }

    .filter-input {
      padding: 10px 15px;
      border: 2px solid #e1e8ed;
      border-radius: 8px;
      font-size: 0.9rem;
      font-family: 'Inter', sans-serif;
      transition: all 0.3s ease;
      outline: none;
    }

    .filter-input:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .filter-btn {
      padding: 10px 20px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border: none;
      color: white;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .filter-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    .payments-table-section { background: var(--card); border:1px solid var(--border); border-radius: 16px; padding: 24px; box-shadow: 0 8px 24px rgba(15,23,42,0.06); }

    .table-header {
      font-size: 1.2rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .payments-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .payments-table thead { background: #fafafa; color: var(--text); }

    .payments-table th {
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .payments-table th:first-child {
      border-top-left-radius: 10px;
    }

    .payments-table th:last-child {
      border-top-right-radius: 10px;
    }

    .payments-table tbody tr {
      border-bottom: 1px solid #eee;
      transition: all 0.3s ease;
    }

    .payments-table tbody tr:hover {
      background: rgba(102, 126, 234, 0.05);
      transform: scale(1.01);
    }

    .payments-table td {
      padding: 15px;
      font-size: 0.9rem;
    }

    .payment-date {
      font-weight: 600;
      color: #333;
    }

    .payment-amount {
      font-weight: 700;
      color: #2ecc71;
      font-size: 1rem;
    }

    .payment-method { display:inline-flex; align-items:center; gap:8px; padding:5px 10px; background: var(--accent-50); color: var(--accent-600); border:1px solid rgba(255,106,0,0.25); border-radius: 999px; font-size:0.8rem; font-weight:600; }

    .payment-method.upi {
      background: rgba(52, 152, 219, 0.1);
      color: #3498db;
    }

    .payment-method.card {
      background: rgba(46, 204, 113, 0.1);
      color: #2ecc71;
    }

    .payment-method.netbanking {
      background: rgba(155, 89, 182, 0.1);
      color: #9b59b6;
    }

    .payment-status {
      padding: 5px 12px;
      border-radius: 15px;
      font-size: 0.8rem;
      font-weight: 600;
      text-align: center;
    }

    .status-success {
      background: rgba(46, 204, 113, 0.1);
      color: #2ecc71;
    }

    .status-pending {
      background: rgba(156, 163, 175, 0.15);
      color: #6b7280;
    }

    .status-failed {
      background: rgba(231, 76, 60, 0.1);
      color: #e74c3c;
    }

    .receipt-btn { padding:8px 15px; border-radius: 999px; text-decoration:none; font-size:0.8rem; font-weight:600; display:inline-flex; align-items:center; gap:5px; }

    .receipt-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    .view-receipt-btn {
      padding: 8px 15px;
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
      border: 2px solid #667eea;
      border-radius: 8px;
      text-decoration: none;
      font-size: 0.8rem;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .view-receipt-btn:hover {
      background: #667eea;
      color: white;
      transform: translateY(-2px);
    }

    .no-payments {
      text-align: center;
      padding: 40px;
      color: #666;
    }

    .no-payments-icon {
      font-size: 3rem;
      color: #ddd;
      margin-bottom: 15px;
    }

    .loading {
      text-align: center;
      padding: 40px;
      color: #666;
    }

    .spinner {
      border: 3px solid #f3f3f3;
      border-top: 3px solid #667eea;
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

    .back-btn {
      position: fixed;
      top: 20px;
      left: 20px;
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
      padding: 10px 15px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
      z-index: 100;
    }

    .back-btn:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    @media (max-width: 768px) {
      .history-container {
        padding: 10px;
      }

      .filters-grid {
        grid-template-columns: 1fr;
      }

      .payments-table {
        font-size: 0.8rem;
      }

      .payments-table th,
      .payments-table td {
        padding: 10px 8px;
      }
    }
  </style>
</head>
<body>
  <a href="dashboard.php" class="back-btn">
    <i class="fas fa-arrow-left"></i>
    Back to Dashboard
  </a>

  <div class="history-container">
    <div class="history-header">
      <div class="history-logo">
        <i class="fas fa-history"></i>
      </div>
      <h2 class="history-title">Payment History</h2>
      <p class="history-subtitle">View and download all your payment records</p>
    </div>

    <div class="summary-stats">
      <div class="summary-card">
        <div class="summary-value">₹62,000</div>
        <div class="summary-label">Total Paid</div>
      </div>
      <div class="summary-card">
        <div class="summary-value">12</div>
        <div class="summary-label">Total Transactions</div>
      </div>
      <div class="summary-card">
        <div class="summary-value">₹5,167</div>
        <div class="summary-label">Average Payment</div>
      </div>
    </div>

    <!-- <div class="filters-section">
      <div class="filters-title">
        <i class="fas fa-filter"></i>
        Filter Payments
      </div>
      <div class="filters-grid">
        <div class="filter-group">
          <label class="filter-label">Start Date</label>
          <input type="date" class="filter-input" id="startDate">
        </div>
        <div class="filter-group">
          <label class="filter-label">End Date</label>
          <input type="date" class="filter-input" id="endDate">
        </div>
        <div class="filter-group">
          <label class="filter-label">Payment Method</label>
          <select class="filter-input" id="methodFilter">
            <option value="">All Methods</option>
            <option value="upi">UPI</option>
            <option value="card">Card</option>
            <option value="netbanking">Net Banking</option>
          </select>
        </div>
        <div class="filter-group">
          <button class="filter-btn" onclick="applyFilters()">
            <i class="fas fa-search"></i>
            Apply Filters
          </button>
        </div>
      </div>
    </div> -->

    <div class="payments-table-section">
      <div class="table-header">
        <i class="fas fa-list"></i>
        Payment Records
      </div>

      <div id="loading" class="loading">
        <div class="spinner"></div>
        Loading payment history...
      </div>

      <table class="payments-table" id="paymentsTable">
        <thead>
          <tr>
            <th>Payment Date</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
            <th>Receipt</th>
          </tr>
        </thead>
        <tbody id="paymentsTableBody">
          <!-- Payment records will be loaded here -->
        </tbody>
      </table>

      <div id="noPayments" class="no-payments" style="display: none;">
        <div class="no-payments-icon">
          <i class="fas fa-inbox"></i>
        </div>
        <h3>No payments found</h3>
        <p>No payments match your current filters.</p>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const table = document.getElementById('paymentsTable');
      const tbody = document.getElementById('paymentsTableBody');
      const loading = document.getElementById('loading');
      const noPayments = document.getElementById('noPayments');

      // Load payment history from database
      loadPaymentHistory();

      // Add entrance animations
      const cards = document.querySelectorAll('.summary-card, .filters-section, .payments-table-section');

      cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';

        setTimeout(() => {
          card.style.transition = 'all 0.6s ease';
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, index * 200);
      });

      // Set default dates
      const today = new Date();
      const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

      document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
      document.getElementById('endDate').value = today.toISOString().split('T')[0];
    });

    function loadPaymentHistory() {
      const table = document.getElementById('paymentsTable');
      const tbody = document.getElementById('paymentsTableBody');
      const loading = document.getElementById('loading');
      const noPayments = document.getElementById('noPayments');

      // Show loading
      loading.style.display = 'block';
      table.style.display = 'none';
      noPayments.style.display = 'none';

      // Fetch payment history from server
      fetch('payments/get_payment_history.php', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        }
      })
      .then(response => response.json())
      .then(data => {
        loading.style.display = 'none';

        if (data.success && data.payments.length > 0) {
          displayPayments(data.payments);
          updateSummaryStats(data.statistics);
          table.style.display = 'table';
        } else {
          noPayments.style.display = 'block';
        }
      })
      .catch(error => {
        console.error('Error loading payment history:', error);
        loading.style.display = 'none';
        noPayments.style.display = 'block';
      });
    }

    function displayPayments(payments) {
      const tbody = document.getElementById('paymentsTableBody');
      tbody.innerHTML = '';

      if (payments.length === 0) {
        document.getElementById('noPayments').style.display = 'block';
        document.getElementById('paymentsTable').style.display = 'none';
        return;
      }

      payments.forEach(payment => {
        const row = document.createElement('tr');

        const paymentDate = new Date(payment.created_at).toLocaleDateString('en-IN', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });

        const methodClass = payment.payment_method.toLowerCase();
        const statusClass = payment.status.toLowerCase();

        row.innerHTML = `
          <td class="payment-date">${paymentDate}</td>
          <td class="payment-amount">₹${parseFloat(payment.amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
          <td><span class="payment-method ${methodClass}">${payment.payment_method.toUpperCase()}</span></td>
          <td><span class="payment-status status-${statusClass}">${payment.status}</span></td>
          <td>
            <a href="payments/receipt.php?receipt_id=${payment.id}" class="view-receipt-btn">
              <i class="fas fa-eye"></i>
              View
            </a>
          </td>
        `;

        tbody.appendChild(row);
      });
    }

    function updateSummaryStats(statistics) {
      // Update summary cards with real data
      const totalAmountEl = document.querySelector('.summary-stats .summary-card:nth-child(1) .summary-value');
      const totalTransactionsEl = document.querySelector('.summary-stats .summary-card:nth-child(2) .summary-value');
      const avgAmountEl = document.querySelector('.summary-stats .summary-card:nth-child(3) .summary-value');

      if (totalAmountEl) {
        totalAmountEl.textContent = '₹' + parseFloat(statistics.total_amount || 0).toLocaleString('en-IN');
      }
      if (totalTransactionsEl) {
        totalTransactionsEl.textContent = statistics.total_payments || 0;
      }
      if (avgAmountEl && statistics.total_payments > 0) {
        const avg = (statistics.total_amount || 0) / statistics.total_payments;
        avgAmountEl.textContent = '₹' + Math.round(avg).toLocaleString('en-IN');
      }
    }

    function applyFilters() {
      const startDate = document.getElementById('startDate').value;
      const endDate = document.getElementById('endDate').value;
      const methodFilter = document.getElementById('methodFilter').value;

      // In a real application, this would make an AJAX call to filter the data
      console.log('Applying filters:', { startDate, endDate, methodFilter });

      // Show loading animation
      const table = document.getElementById('paymentsTable');
      const loading = document.getElementById('loading');

      loading.style.display = 'block';
      table.style.display = 'none';

      setTimeout(() => {
        loading.style.display = 'none';
        table.style.display = 'table';

        // Add a subtle success animation
        const filterBtn = document.querySelector('.filter-btn');
        filterBtn.style.transform = 'scale(0.95)';
        setTimeout(() => {
          filterBtn.style.transform = 'scale(1)';
        }, 150);
      }, 800);
    }
  </script>
</body>
</html>
