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
  <title>Make Payment - Zion Fee Portal</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="public/theme.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body { font-family: 'Inter', sans-serif; background: var(--bg); min-height: 100vh; padding: 20px; position: relative; overflow-x: hidden; }

    .payment-container { max-width: 520px; margin: 0 auto; background: var(--card); border:1px solid var(--border); border-radius:16px; padding: 32px; box-shadow: 0 8px 24px rgba(15,23,42,0.06); position: relative; }

    .payment-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .payment-logo { font-size:2.2rem; color: var(--accent); margin-bottom:10px; }

    .payment-title {
      color: #333;
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .payment-subtitle {
      color: #666;
      font-size: 0.95rem;
    }

    .amount-display { background: var(--accent); color:#fff; padding: 22px; border-radius: 14px; text-align:center; margin-bottom: 24px; position: relative; overflow: hidden; }

    .amount-display::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
      background-size: 20px 20px;
      animation: sparkle 3s linear infinite;
    }

    @keyframes sparkle {
      0% { transform: translate(0, 0) rotate(0deg); }
      100% { transform: translate(-20px, -20px) rotate(360deg); }
    }

    .amount-label {
      font-size: 0.9rem;
      opacity: 0.9;
      margin-bottom: 8px;
    }

    .amount-value {
      font-size: 2.5rem;
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .payment-methods {
      display: grid;
      gap: 15px;
      margin-bottom: 30px;
    }

    .payment-method { background:#fff; border:1.5px solid var(--border); border-radius: 12px; padding: 16px; cursor:pointer; transition: all 0.2s ease; position: relative; overflow: hidden; }

    .payment-method:hover { border-color: var(--accent); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(255,106,0,0.12); }

    .payment-method.selected { border-color: var(--accent); background: var(--accent-50); }

    .payment-method-icon { font-size:1.5rem; color: var(--accent); margin-bottom:10px; }

    .payment-method-name {
      font-weight: 600;
      color: #333;
      margin-bottom: 5px;
    }

    .payment-method-desc {
      font-size: 0.85rem;
      color: #666;
    }

    /* Card details collapsible */
    .card-extra { max-height: 0; overflow: hidden; transition: max-height .35s ease, opacity .25s ease; opacity: 0; }
    .card-extra.show { max-height: 300px; opacity: 1; }
    .card-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px; }
    .form-field label { display:block; font-size:.85rem; color:#374151; font-weight:600; margin-bottom:6px; }
    .form-field input { width:100%; padding:12px 14px; border:1.5px solid #e5e7eb; border-radius:10px; font-family:'Inter', sans-serif; transition:border .2s ease; }
    .form-field input:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.12); }

    .amount-input-section {
      background: white;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 30px;
      border: 2px solid #e1e8ed;
    }

    .input-group {
      position: relative;
    }

    .input-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 500;
      font-size: 0.9rem;
    }

    .currency-symbol {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #667eea;
      font-weight: 600;
      font-size: 1.1rem;
    }

    .amount-input {
      width: 100%;
      padding: 15px 15px 15px 35px;
      border: 2px solid #e1e8ed;
      border-radius: 10px;
      font-size: 1.1rem;
      font-family: 'Inter', sans-serif;
      transition: all 0.3s ease;
      outline: none;
    }

    .amount-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(255,106,0,0.15); }

    .quick-amounts {
      display: flex;
      gap: 10px;
      margin-top: 15px;
      flex-wrap: wrap;
    }

    .quick-amount-btn { padding:8px 16px; background: var(--accent-50); border:1px solid var(--accent); border-radius: 20px; color: var(--accent); font-size:0.85rem; font-weight:500; cursor:pointer; transition: all .2s ease; }

    .quick-amount-btn:hover { background: var(--accent); color:#fff; transform: translateY(-1px); }

    /* Swipe to pay */
    .swipe-wrap { position: relative; width:100%; height: 54px; border-radius: 14px; background: #eef2ff; border:1px solid #c7d2fe; overflow:hidden; user-select:none; }
    .swipe-progress { position:absolute; left:0; top:0; bottom:0; width:100%; background:#6366f1; transform: scaleX(0); transform-origin: left center; will-change: transform; }
    .swipe-label { position:absolute; left:50%; top:50%; transform:translate(-50%, -50%); color:#4f46e5; font-weight:700; letter-spacing:.2px; display:flex; align-items:center; gap:10px; pointer-events:none; }
    .swipe-label.done { color:#fff; }
    .swipe-knob { position:absolute; left:6px; top:6px; width:42px; height:42px; border-radius:12px; background:#fff; box-shadow: 0 6px 18px rgba(79,70,229,.25); display:flex; align-items:center; justify-content:center; color:#4f46e5; font-size:18px; cursor:grab; transition: box-shadow .2s ease, transform .1s ease; }
    .swipe-knob:active { cursor:grabbing; transform: scale(.98); }
    .swipe-wrap.success .swipe-knob { background:#22c55e; color:#fff; box-shadow: 0 6px 18px rgba(34,197,94,.35); }

    .payment-animation {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(102, 126, 234, 0.95);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      flex-direction: column;
      color: white;
    }

    .success-animation {
      text-align: center;
    }

    .success-icon {
      font-size: 4rem;
      margin-bottom: 20px;
      animation: bounce 1s ease infinite;
    }

    .success-text {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 10px;
    }

    .success-subtext {
      font-size: 0.9rem;
      opacity: 0.8;
    }

    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
      40% { transform: translateY(-10px); }
      60% { transform: translateY(-5px); }
    }

    .particles {
      position: absolute;
      width: 100%;
      height: 100%;
      pointer-events: none;
    }

    .particle {
      position: absolute;
      width: 10px;
      height: 10px;
      background: rgba(255, 255, 255, 0.6);
      border-radius: 50%;
      animation: particle-float 3s ease-in-out infinite;
    }

    @keyframes particle-float {
      0% { transform: translateY(100vh) scale(0); opacity: 0; }
      10% { opacity: 1; }
      90% { opacity: 1; }
      100% { transform: translateY(-100px) scale(1); opacity: 0; }
    }

    .back-btn {
      position: absolute;
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
    }

    .back-btn:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    .setup-notice {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 15px 20px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(102, 126, 234, 0.2);
      display: none;
      z-index: 1000;
      max-width: 300px;
    }

    .setup-notice.show {
      display: block;
    }

    .setup-notice-title {
      font-weight: 600;
      color: #333;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .setup-notice-text {
      color: #666;
      font-size: 0.9rem;
      margin-bottom: 15px;
      line-height: 1.4;
    }

    .setup-notice-btn {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .setup-notice-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    @media (max-width: 600px) {
      .payment-container {
        margin: 10px;
        padding: 25px 20px;
      }

      .amount-value {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
  <a href="dashboard.php" class="back-btn">
    <i class="fas fa-arrow-left"></i>
    Back to Dashboard
  </a>

  <div class="payment-container">
    <div class="payment-header">
      <div class="payment-logo">
        <i class="fas fa-credit-card"></i>
      </div>
      <h2 class="payment-title">Make Payment</h2>
      <p class="payment-subtitle">Choose your payment method and amount</p>
    </div>

    <?php
      // Determine applicable fee for logged-in student or from query string selection
      $appAmount = null; $appDue = null; $feeId = isset($_GET['fee_id']) ? (int)$_GET['fee_id'] : null;
      // Prefer explicit selection from due_fees.php if provided
      if (isset($_GET['amount'])) {
        $appAmount = (float)$_GET['amount'];
      }
      if (isset($_GET['due'])) {
        $appDue = $_GET['due'];
      }
      if (isset($_SESSION['user_id'])) {
        require_once 'includes/db_connect.php';
        $course = $_SESSION['course'] ?? null;
        $semester = $_SESSION['semester'] ?? null;
        if ($feeId) {
          // If a specific fee is selected, load its details
          $stmt = $conn->prepare("SELECT amount, due_date FROM fee_structures WHERE id = ? AND is_active = 1 LIMIT 1");
          if ($stmt) {
            $stmt->bind_param('i', $feeId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) { if ($appAmount === null) { $appAmount = (float)$row['amount']; } if ($appDue === null) { $appDue = $row['due_date']; } }
            $stmt->close();
          }
        } elseif ($course && $semester && $appAmount === null) {
          $stmt = $conn->prepare("SELECT id, amount, due_date FROM fee_structures WHERE course_name = ? AND semester = ? AND is_active = 1 ORDER BY due_date IS NULL, due_date ASC, id DESC LIMIT 1");
          if ($stmt) {
            $stmt->bind_param('ss', $course, $semester);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) { if (!$feeId) { $feeId = (int)$row['id']; } if ($appAmount === null) { $appAmount = (float)$row['amount']; } if ($appDue === null) { $appDue = $row['due_date']; } }
            $stmt->close();
          }
          $conn->close();
        }
      }
    ?>
    <div class="amount-display">
      <div class="amount-label">Applicable Fee <?php echo $appDue ? '(Due: '.htmlspecialchars(date('M d, Y', strtotime($appDue))).')' : ''; ?></div>
      <div class="amount-value">₹<?php echo number_format($appAmount ?? 0, 2); ?></div>
    </div>

    <form id="paymentForm" action="payments/process_payment.php" method="POST">
      <div class="payment-methods">
        <div class="payment-method" data-method="upi">
          <div class="payment-method-icon">
            <i class="fas fa-mobile-alt"></i>
          </div>
          <div class="payment-method-name">UPI Payment</div>
          <div class="payment-method-desc">Pay using UPI apps like GPay, PhonePe, Paytm</div>
        </div>

      <div class="payment-method" data-method="card">
          <div class="payment-method-icon">
            <i class="fas fa-credit-card"></i>
          </div>
          <div class="payment-method-name">Credit/Debit Card</div>
          <div class="payment-method-desc">Visa, MasterCard, RuPay cards accepted</div>
        <div class="card-extra" id="cardExtra">
          <div class="form-field">
            <label>Cardholder Name</label>
            <input type="text" id="cardName" placeholder="e.g., John Doe" autocomplete="cc-name">
          </div>
          <div class="form-field">
            <label>Card Number</label>
            <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" inputmode="numeric" maxlength="19" autocomplete="cc-number">
          </div>
          <div class="card-grid">
            <div class="form-field">
              <label>Expiry (MM/YY)</label>
              <input type="text" id="cardExpiry" placeholder="MM/YY" inputmode="numeric" maxlength="5" autocomplete="cc-exp">
            </div>
            <div class="form-field">
              <label>CVV</label>
              <input type="password" id="cardCvv" placeholder="123" inputmode="numeric" maxlength="4" autocomplete="cc-csc">
            </div>
          </div>
        </div>
        </div>
      
      </div>

      <input type="hidden" name="payment_method" id="selectedMethod" required>
      <input type="hidden" name="fee_id" value="<?php echo $feeId ? (int)$feeId : ''; ?>">

      <div class="amount-input-section">
        <div class="input-group">
          <label for="amount">Amount to Pay (₹)</label>
          <!-- <div class="currency-symbol">₹</div> -->
          <input type="number" id="amount" name="amount" class="amount-input"
                 placeholder="0.00" step="0.01" min="1" value="<?php echo $appAmount !== null ? htmlspecialchars($appAmount) : ''; ?>" required>
        </div>

        <div class="quick-amounts">
          <button type="button" class="quick-amount-btn" data-amount="1000">₹1,000</button>
          <button type="button" class="quick-amount-btn" data-amount="5000">₹5,000</button>
          <button type="button" class="quick-amount-btn" data-amount="12000">₹12,000</button>
          <button type="button" class="quick-amount-btn" data-amount="25000">₹25,000</button>
        </div>
      </div>

      <div class="swipe-wrap" id="swipePay">
        <div class="swipe-progress" id="swipeProgress"></div>
        <div class="swipe-knob" id="swipeKnob"><i class="fas fa-arrow-right"></i></div>
        <div class="swipe-label" id="swipeLabel"><i class="fas fa-lock"></i> Swipe to pay</div>
      </div>
    </form>
  </div>

  <div class="payment-animation" id="paymentAnimation">
    <div class="particles" id="particles"></div>
    <div class="success-animation">
      <div class="success-icon">
        <i class="fas fa-check-circle"></i>
      </div>
      <div class="success-text">Payment Successful!</div>
      <div class="success-subtext">Processing your transaction...</div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const paymentMethods = document.querySelectorAll('.payment-method');
      const selectedMethodInput = document.getElementById('selectedMethod');
      const amountInput = document.getElementById('amount');
      const quickAmountBtns = document.querySelectorAll('.quick-amount-btn');
      const paymentForm = document.getElementById('paymentForm');
      const paymentAnimation = document.getElementById('paymentAnimation');
      const particlesContainer = document.getElementById('particles');
      const swipe = document.getElementById('swipePay');
      const knob = document.getElementById('swipeKnob');
      const progress = document.getElementById('swipeProgress');
      const label = document.getElementById('swipeLabel');

      // Payment method selection + card panel toggle
      const cardExtra = document.getElementById('cardExtra');
      paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
          paymentMethods.forEach(m => m.classList.remove('selected'));
          this.classList.add('selected');
          selectedMethodInput.value = this.dataset.method;
          if (this.dataset.method === 'card') {
            cardExtra.classList.add('show');
          } else {
            if (cardExtra) cardExtra.classList.remove('show');
          }
        });
      });

      // Quick amount selection
      quickAmountBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          amountInput.value = this.dataset.amount;
          amountInput.focus();
        });
      });

      // Form submission with animation
      function submitPayment(){

        if (!selectedMethodInput.value) {
          showNotification('Please select a payment method', 'error');
          return;
        }

        // If card is selected, perform minimal client-side validation
        if (selectedMethodInput.value === 'card') {
          const nn = (document.getElementById('cardNumber').value || '').replace(/\s+/g,'');
          const nm = (document.getElementById('cardName').value || '').trim();
          const ex = (document.getElementById('cardExpiry').value || '').trim();
          const cv = (document.getElementById('cardCvv').value || '').trim();
          if (!nm || nn.length < 12 || !/^[0-9]{3,4}$/.test(cv) || !/^[0-9]{2}\/[0-9]{2}$/.test(ex)) {
            showNotification('Enter valid card details (test data allowed).', 'error');
            return;
          }
        }

        if (!amountInput.value || amountInput.value <= 0) {
          showNotification('Please enter a valid amount', 'error');
          return;
        }

        // Show payment animation
        showPaymentAnimation();
      }

      // Swipe to pay interaction
      (function initSwipe(){
        if (!swipe) return;
        let isDown = false, startX = 0, knobX = 0, maxX = 0;
        function clamp(n, min, max){ return Math.max(min, Math.min(max, n)); }
        let prog = 0, targetProg = 0;
        function raf(){ prog += (targetProg - prog) * 0.25; progress.style.transform = 'scaleX(' + prog + ')'; if (Math.abs(targetProg - prog) > 0.001) requestAnimationFrame(raf); }
        function setKnob(x){ knob.style.left = x + 'px'; targetProg = Math.max(0, Math.min(1, (x + knob.offsetWidth/2) / swipe.clientWidth)); requestAnimationFrame(raf); }
        function complete(){
          swipe.classList.add('success');
          label.classList.add('done');
          label.innerHTML = '<i class="fas fa-check"></i> Releasing…';
          setTimeout(submitPayment, 150);
        }
        function reset(){
          // hard reset progress and knob
          prog = 0; targetProg = 0; progress.style.transform = 'scaleX(0)';
          knob.style.left = '6px';
          label.classList.remove('done');
          label.innerHTML = '<i class="fas fa-lock"></i> Swipe to pay';
          swipe.classList.remove('success');
        }
        function updateMax(){ maxX = swipe.clientWidth - knob.offsetWidth - 6; }
        updateMax(); setKnob(6);
        window.addEventListener('resize', updateMax);
        knob.addEventListener('mousedown', e => { isDown = true; knobX = parseInt(knob.style.left||'6'); startX = e.clientX; });
        window.addEventListener('mouseup', () => { if(!isDown) return; isDown=false; if (parseInt(knob.style.left||'6') >= maxX - 6) complete(); else reset(); });
        window.addEventListener('mousemove', e => {
          if (!isDown) return; e.preventDefault();
          const dx = e.clientX - startX; const nx = clamp(knobX + dx, 6, maxX);
          setKnob(nx);
          if (nx >= maxX - 6) { label.innerHTML = '<i class="fas fa-unlock"></i> Release to pay'; }
        });
        // Touch
        knob.addEventListener('touchstart', e => { isDown = true; knobX = parseInt(knob.style.left||'6'); startX = e.touches[0].clientX; }, {passive:true});
        window.addEventListener('touchend', () => { if(!isDown) return; isDown=false; if (parseInt(knob.style.left||'6') >= maxX - 6) complete(); else reset(); }, {passive:true});
        window.addEventListener('touchmove', e => { if(!isDown) return; const dx = e.touches[0].clientX - startX; const nx = clamp(knobX + dx, 6, maxX); setKnob(nx); if (nx >= maxX - 6) { label.innerHTML = '<i class="fas fa-unlock"></i> Release to pay'; } }, {passive:true});
      })();

      function showPaymentAnimation() {
        // Create particles
        createParticles();

        paymentAnimation.style.display = 'flex';

        // Submit form data via AJAX
        const formData = new FormData(paymentForm);

        fetch('payments/process_payment.php', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        })
        .then(async (response) => {
          const rawText = await response.text();
          if (!response.ok) {
            throw new Error(rawText || 'Network response was not ok');
          }
          try {
            return JSON.parse(rawText);
          } catch (e) {
            throw new Error(rawText || 'Invalid JSON response');
          }
        })
        .then(data => {
          setTimeout(() => {
            if (data.success) {
              showNotification('Payment processed successfully!', 'success');
              setTimeout(() => {
                window.location.href = 'payments/receipt.php';
              }, 1500);
            } else {
              throw new Error(data.message || 'Payment failed');
            }
          }, 2000);
        })
        .catch(error => {
          console.error('Payment error:', error);

          let errorMessage = 'Payment failed: ';
          if (error.message.includes('Network response was not ok')) {
            errorMessage += 'Server error. Please run: php setup_database.php and php create_payments_table.php';
          } else if (error.message.includes('500')) {
            errorMessage += 'Database tables missing. Please run the database setup scripts first.';
          } else {
            errorMessage += error.message;
          }

          showNotification(errorMessage, 'error');
          setTimeout(() => {
            paymentAnimation.style.display = 'none';
          }, 3000);
        });
      }

      function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
          <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
          </div>
        `;

        // Add notification styles
        notification.style.cssText = `
          position: fixed;
          top: 20px;
          right: 20px;
          background: ${type === 'success' ? 'rgba(46, 204, 113, 0.95)' : 'rgba(231, 76, 60, 0.95)'};
          color: white;
          padding: 15px 20px;
          border-radius: 12px;
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
          z-index: 2000;
          backdrop-filter: blur(10px);
          transform: translateX(400px);
          transition: all 0.3s ease;
          max-width: 300px;
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
          notification.style.transform = 'translateX(0)';
        }, 100);

        // Remove after delay
        setTimeout(() => {
          notification.style.transform = 'translateX(400px)';
          setTimeout(() => {
            notification.remove();
          }, 300);
        }, type === 'success' ? 2000 : 4000);
      }

      function createParticles() {
        particlesContainer.innerHTML = '';

        for (let i = 0; i < 50; i++) {
          setTimeout(() => {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 3 + 's';
            particle.style.animationDuration = (Math.random() * 3 + 2) + 's';
            particlesContainer.appendChild(particle);
          }, i * 100);
        }
      }

      // Initialize with first payment method selected
      paymentMethods[0].click();

      // Prefill amount from URL if provided (e.g., due fees Pay link)
      const url = new URL(window.location.href);
      const amt = url.searchParams.get('amount');
      if (amt) { amountInput.value = amt; }

      // Add entrance animation
      const container = document.querySelector('.payment-container');
      container.style.opacity = '0';
      container.style.transform = 'translateY(30px)';

      setTimeout(() => {
        container.style.transition = 'all 0.6s ease';
        container.style.opacity = '1';
        container.style.transform = 'translateY(0)';
      }, 100);
    });
  </script>
</body>
</html>
