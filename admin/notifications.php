<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require '../includes/db_connect.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';

// Initialize last seen timestamp if not set
if (!isset($_SESSION['notifications_last_seen'])) {
    $_SESSION['notifications_last_seen'] = '1970-01-01 00:00:00';
}

try {
    if ($action === 'mark_all_read') {
        // Set last seen to the latest payment timestamp to avoid counting older items on refresh
        $max_stmt = $conn->prepare("SELECT COALESCE(MAX(p.created_at), NOW()) AS last_time FROM payments p JOIN users u ON u.id = p.user_id WHERE u.user_type='student'");
        if ($max_stmt && $max_stmt->execute()) {
            $res = $max_stmt->get_result();
            $row = $res->fetch_assoc();
            $_SESSION['notifications_last_seen'] = $row['last_time'] ?? date('Y-m-d H:i:s');
        } else {
            $_SESSION['notifications_last_seen'] = date('Y-m-d H:i:s');
        }
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'count') {
        $last_seen = $_SESSION['notifications_last_seen'];
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM payments p JOIN users u ON u.id = p.user_id WHERE u.user_type='student' AND p.created_at > ?");
        $stmt->bind_param('s', $last_seen);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        echo json_encode(['success' => true, 'unread' => (int)($row['cnt'] ?? 0)]);
        exit();
    }

    // Default: list
    $limit = 10;
    $last_seen = $_SESSION['notifications_last_seen'];
    $sql = "SELECT p.id, p.amount, p.status, p.created_at, u.first_name, u.last_name, u.username
            FROM payments p
            JOIN users u ON u.id = p.user_id
            WHERE u.user_type = 'student'
            ORDER BY p.created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }

    // Unread count since last seen
    $cnt_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM payments p JOIN users u ON u.id = p.user_id WHERE u.user_type='student' AND p.created_at > ?");
    $cnt_stmt->bind_param('s', $last_seen);
    $cnt_stmt->execute();
    $cnt_res = $cnt_stmt->get_result();
    $cnt_row = $cnt_res->fetch_assoc();
    $unread = (int)($cnt_row['cnt'] ?? 0);

    echo json_encode(['success' => true, 'notifications' => $items, 'unread' => $unread]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>


