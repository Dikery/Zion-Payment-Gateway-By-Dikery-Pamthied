<?php
session_start();
require '../includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch payment history for the user
try {
    // First check if payments table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'payments'");
    if ($table_check->num_rows === 0) {
        // Payments table doesn't exist yet
        echo json_encode([
            'success' => true,
            'payments' => [],
            'statistics' => [
                'total_payments' => 0,
                'total_amount' => 0,
                'avg_amount' => 0
            ]
        ]);
        exit();
    }

    $sql = "SELECT id, amount, payment_method, transaction_id, status, created_at
            FROM payments
            WHERE user_id = ?
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt->close();

    // Get summary statistics
    $stats_sql = "SELECT
        COUNT(*) as total_payments,
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(AVG(amount), 0) as avg_amount
        FROM payments
        WHERE user_id = ? AND status = 'completed'";

    $stats_stmt = $conn->prepare($stats_sql);
    if (!$stats_stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $stats_stmt->bind_param("i", $user_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stats_stmt->close();

    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'statistics' => [
            'total_payments' => $stats['total_payments'] ?? 0,
            'total_amount' => $stats['total_amount'] ?? 0,
            'avg_amount' => $stats['avg_amount'] ?? 0
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching payment history: ' . $e->getMessage()]);
}

$conn->close();
?>
