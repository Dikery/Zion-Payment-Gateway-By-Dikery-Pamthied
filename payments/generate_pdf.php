<?php
// Simple PDF generation helper
// In a production environment, you would install TCPDF properly

function generateReceiptPDF($receipt_data) {
    // For now, we'll create a simple HTML-to-PDF solution
    // In production, install TCPDF: composer require tecnickcom/tcpdf

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Payment Receipt - ' . $receipt_data['receipt_id'] . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .receipt-details { margin: 20px 0; }
            .row { display: flex; justify-content: space-between; margin: 10px 0; padding: 5px 0; border-bottom: 1px solid #eee; }
            .label { font-weight: bold; }
            .value { color: #666; }
            .amount { font-size: 1.2em; font-weight: bold; color: #2ecc71; }
            .footer { text-align: center; margin-top: 40px; font-size: 0.9em; color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>ZION FEE PAYMENT PORTAL</h1>
            <h2>Payment Receipt</h2>
        </div>

        <div class="receipt-details">
            <div class="row">
                <span class="label">Receipt ID:</span>
                <span class="value">' . $receipt_data['receipt_id'] . '</span>
            </div>
            <div class="row">
                <span class="label">Transaction ID:</span>
                <span class="value">' . $receipt_data['transaction_id'] . '</span>
            </div>
            <div class="row">
                <span class="label">Student Name:</span>
                <span class="value">' . $receipt_data['student_name'] . '</span>
            </div>
            <div class="row">
                <span class="label">Amount Paid:</span>
                <span class="value amount">₹' . number_format($receipt_data['amount'], 2) . '</span>
            </div>
            <div class="row">
                <span class="label">Payment Method:</span>
                <span class="value">' . ucfirst($receipt_data['payment_method']) . '</span>
            </div>
            <div class="row">
                <span class="label">Payment Date:</span>
                <span class="value">' . date('F j, Y, g:i a', strtotime($receipt_data['payment_date'])) . '</span>
            </div>
            <div class="row">
                <span class="label">Status:</span>
                <span class="value" style="color: #2ecc71;">' . ucfirst($receipt_data['status']) . '</span>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for your payment!</p>
            <p>This is a computer generated receipt</p>
            <p>Generated on: ' . date('F j, Y') . '</p>
        </div>
    </body>
    </html>';

    return $html;
}

// Alternative: Simple text-based receipt
function generateTextReceipt($receipt_data) {
    $receipt = "
=====================================
         ZION FEE PAYMENT PORTAL
              PAYMENT RECEIPT
=====================================

Receipt ID: {$receipt_data['receipt_id']}
Transaction ID: {$receipt_data['transaction_id']}
Student Name: {$receipt_data['student_name']}
Amount Paid: ₹" . number_format($receipt_data['amount'], 2) . "
Payment Method: " . ucfirst($receipt_data['payment_method']) . "
Payment Date: " . date('F j, Y, g:i a', strtotime($receipt_data['payment_date'])) . "
Status: " . ucfirst($receipt_data['status']) . "

Thank you for your payment!

This is a computer generated receipt
Generated on: " . date('F j, Y') . "

=====================================
";

    return $receipt;
}
?>
