<?php
// Ensure no prior output corrupts JSON
if (ob_get_level() === 0) {
    ob_start();
}

// Do not display PHP warnings/notices to client (prevents HTML in responses)
@ini_set('display_errors', '0');
error_reporting(E_ALL);

// Force JSON header early
header('Content-Type: application/json');

session_start();
require '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

// Get POST data
$payment_method = $_POST['payment_method'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);
$fee_id = isset($_POST['fee_id']) ? intval($_POST['fee_id']) : null;
$user_id = $_SESSION['user_id'] ?? 0;

// Validate input
if (empty($payment_method) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payment method or amount']);
    exit();
}

// Validate user exists
$user_check_sql = "SELECT u.id, u.first_name, u.last_name, sd.student_id
                   FROM users u
                   LEFT JOIN student_details sd ON sd.user_id = u.id
                   WHERE u.id = ?";
$user_check_stmt = $conn->prepare($user_check_sql);
if (!$user_check_stmt) {
    http_response_code(500);
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Database error: failed to prepare user lookup']);
    exit();
}
$user_check_stmt->bind_param("i", $user_id);
$user_check_stmt->execute();
$user_result = $user_check_stmt->get_result();

if ($user_result->num_rows === 0) {
    http_response_code(404);
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user = $user_result->fetch_assoc();

// Simulate payment processing
$payment_status = 'completed'; // In real app, this would integrate with payment gateway
$transaction_id = 'TXN_' . date('YmdHis') . '_' . rand(1000, 9999);

// Begin transaction
$conn->begin_transaction();

try {
    // Insert payment record using prepared statement
    // Ensure schema has fee_id column, harmless if already exists
    $conn->query("ALTER TABLE payments ADD COLUMN fee_id INT NULL AFTER user_id");

    $payment_sql = "INSERT INTO payments (user_id, fee_id, amount, payment_method, transaction_id, status, created_at)
                   VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $payment_stmt = $conn->prepare($payment_sql);
    if (!$payment_stmt) {
        throw new Exception('Database error: failed to prepare payment insert');
    }
    $payment_stmt->bind_param("iidsss", $user_id, $fee_id, $amount, $payment_method, $transaction_id, $payment_status);

    if ($payment_stmt->execute()) {
        $payment_id = $conn->insert_id;
        $payment_stmt->close();

        // Update user balance using prepared statement
        $update_balance_sql = "UPDATE users SET last_payment_date = NOW(), total_paid = COALESCE(total_paid, 0) + ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_balance_sql);
        if (!$update_stmt) {
            throw new Exception('Database error: failed to prepare user update');
        }
        $update_stmt->bind_param("di", $amount, $user_id);
        $update_stmt->execute();
        $update_stmt->close();

        $conn->commit();

        // Generate receipt data
        $receipt_data = [
            'receipt_id' => 'RCP_' . $payment_id,
            'transaction_id' => $transaction_id,
            'student_name' => $user['first_name'] . ' ' . $user['last_name'],
            'student_id' => $user['student_id'] ?? null,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'payment_date' => date('Y-m-d H:i:s'),
            'status' => 'Success'
        ];

        // Store receipt data in session for download
        $_SESSION['last_receipt'] = $receipt_data;
        $_SESSION['last_payment_id'] = $payment_id;

        // Return success response
        if (ob_get_length() !== false) { ob_clean(); }
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully!',
            'transaction_id' => $transaction_id,
            'receipt_id' => $receipt_data['receipt_id'],
            'payment_id' => $payment_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'fee_id' => $fee_id
        ]);
        exit();

    } else {
        throw new Exception("Error recording payment: " . $conn->error);
    }

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    if (ob_get_length() !== false) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()]);
    exit();
}

$conn->close();

// Flush any buffered output after JSON has been sent
if (ob_get_length() !== false) {
    ob_end_flush();
}
?>
