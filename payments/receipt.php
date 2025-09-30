<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

// Check if receipt data exists
if (!isset($_SESSION['last_receipt']) && !isset($_GET['receipt_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

// Get receipt data
$receipt_data = null;
if (isset($_GET['receipt_id'])) {
    // Fetch from database using payment ID
    $payment_id = intval($_GET['receipt_id']);
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT p.*, u.first_name, u.last_name, sd.student_id
            FROM payments p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN student_details sd ON sd.user_id = u.id
            WHERE p.id = ? AND p.user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $payment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $payment = $result->fetch_assoc();
        $receipt_data = [
            'receipt_id' => 'RCP_' . $payment['id'],
            'transaction_id' => $payment['transaction_id'],
            'student_name' => $payment['first_name'] . ' ' . $payment['last_name'],
            'student_id' => $payment['student_id'],
            'amount' => $payment['amount'],
            'payment_method' => $payment['payment_method'],
            'payment_date' => $payment['created_at'],
            'status' => $payment['status']
        ];
    }
} elseif (isset($_SESSION['last_receipt'])) {
    // Use session data from recent payment
    $receipt_data = $_SESSION['last_receipt'];
}

// Generate PDF receipt
if (isset($_GET['download']) && $receipt_data) {
    // Check if TCPDF is available
    if (file_exists('tcpdf/tcpdf.php')) {
        require_once 'tcpdf/tcpdf.php';

        class MYPDF extends TCPDF {
            public function Header() {
                $this->SetFont('helvetica', 'B', 20);
                $this->Cell(0, 15, 'ZION FEE PAYMENT PORTAL', 0, 1, 'C');
                $this->SetFont('helvetica', '', 12);
                $this->Cell(0, 10, 'Payment Receipt', 0, 1, 'C');
                $this->Ln(10);
            }

            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, 'This is a computer generated receipt', 0, 0, 'C');
            }
        }

        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Zion Fee Portal');
        $pdf->SetAuthor('Zion Fee Portal');
        $pdf->SetTitle('Payment Receipt - ' . $receipt_data['receipt_id']);
        $pdf->AddPage();

        // Receipt content
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'PAYMENT RECEIPT', 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('helvetica', '', 12);

        // Receipt details table
        $html = '
        <table border="1" cellpadding="8">
            <tr><td width="30%"><strong>Receipt ID:</strong></td><td width="70%">' . $receipt_data['receipt_id'] . '</td></tr>
            <tr><td><strong>Transaction ID:</strong></td><td>' . $receipt_data['transaction_id'] . '</td></tr>
            <tr><td><strong>Student Name:</strong></td><td>' . $receipt_data['student_name'] . '</td></tr>
            <tr><td><strong>Student ID:</strong></td><td>' . ($receipt_data['student_id'] ?? 'N/A') . '</td></tr>
            <tr><td><strong>Amount Paid:</strong></td><td>₹' . number_format($receipt_data['amount'], 2) . '</td></tr>
            <tr><td><strong>Payment Method:</strong></td><td>' . ucfirst($receipt_data['payment_method']) . '</td></tr>
            <tr><td><strong>Payment Date:</strong></td><td>' . date('F j, Y, g:i a', strtotime($receipt_data['payment_date'])) . '</td></tr>
            <tr><td><strong>Status:</strong></td><td>' . ucfirst($receipt_data['status']) . '</td></tr>
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(15);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Thank you for your payment!', 0, 1, 'C');

        $pdf->Output('Receipt_' . $receipt_data['receipt_id'] . '.pdf', 'D');
    } else {
        // Fallback: Generate HTML receipt for download
        require_once 'generate_pdf.php';
        $html_content = generateReceiptPDF($receipt_data);

        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="Receipt_' . $receipt_data['receipt_id'] . '.html"');
        echo $html_content;
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - Zion Fee Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .receipt-header::before {
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

        .receipt-logo {
            font-size: 3rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .receipt-title {
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .receipt-subtitle {
            color: #666;
            font-size: 0.95rem;
        }

        .success-message {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }

        .success-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .receipt-details {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #e1e8ed;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .receipt-row:last-child {
            border-bottom: none;
        }

        .receipt-label {
            font-weight: 600;
            color: #333;
        }

        .receipt-value {
            color: #666;
            text-align: right;
        }

        .amount-highlight {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2ecc71;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.1);
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

        @media (max-width: 600px) {
            .receipt-container {
                margin: 10px;
                padding: 25px 20px;
            }

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <a href="../dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
    </a>

    <div class="receipt-container">
        <div class="receipt-header">
            <div class="receipt-logo">
                <i class="fas fa-receipt"></i>
            </div>
            <h2 class="receipt-title">Payment Receipt</h2>
            <p class="receipt-subtitle">Your payment has been processed successfully</p>
        </div>

        <?php if ($receipt_data): ?>
            <div class="success-message">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Payment Successful!</h3>
                <p>Your payment of <strong>₹<?php echo number_format($receipt_data['amount'], 2); ?></strong> has been processed successfully.</p>
            </div>

            <div class="receipt-details">
                <div class="receipt-row">
                    <span class="receipt-label">Receipt ID:</span>
                    <span class="receipt-value"><?php echo $receipt_data['receipt_id']; ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Transaction ID:</span>
                    <span class="receipt-value"><?php echo $receipt_data['transaction_id']; ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Student Name:</span>
                    <span class="receipt-value"><?php echo $receipt_data['student_name']; ?></span>
                </div>
                <?php if (isset($receipt_data['student_id'])): ?>
                <div class="receipt-row">
                    <span class="receipt-label">Student ID:</span>
                    <span class="receipt-value"><?php echo $receipt_data['student_id']; ?></span>
                </div>
                <?php endif; ?>
                <div class="receipt-row">
                    <span class="receipt-label">Amount Paid:</span>
                    <span class="receipt-value amount-highlight">₹<?php echo number_format($receipt_data['amount'], 2); ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Payment Method:</span>
                    <span class="receipt-value"><?php echo ucfirst($receipt_data['payment_method']); ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Payment Date:</span>
                    <span class="receipt-value"><?php echo date('F j, Y, g:i a', strtotime($receipt_data['payment_date'])); ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Status:</span>
                    <span class="receipt-value" style="color: #2ecc71; font-weight: 600;"><?php echo ucfirst($receipt_data['status']); ?></span>
                </div>
            </div>

            <div class="btn-group">
                <a href="receipt.php?download=1&receipt_id=<?php echo $_SESSION['last_payment_id'] ?? $payment_id ?? ''; ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i>
                    Download PDF
                </a>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i>
                    Back to Dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="success-message" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.2);">
                <div class="success-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Receipt Not Found</h3>
                <p>No receipt data available. Please make a payment to generate a receipt.</p>
            </div>

            <div class="btn-group">
                <a href="../make_payment.php" class="btn btn-primary">
                    <i class="fas fa-credit-card"></i>
                    Make Payment
                </a>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i>
                    Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add entrance animation
            const container = document.querySelector('.receipt-container');
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
