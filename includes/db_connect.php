<?php
// db_connect.php
$servername = "localhost"; // Change if different
$username = "root"; // Replace with your DB username
$password = ""; // Replace with your DB password
$dbname = "zion"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection without echoing output that could corrupt JSON responses
if ($conn->connect_error) {
    http_response_code(500);
    // Return JSON if headers indicate JSON, otherwise plain text
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isJsonPreferred = (stripos($accept, 'application/json') !== false) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    if (!headers_sent()) {
        header('Content-Type: ' . ($isJsonPreferred ? 'application/json' : 'text/plain'));
    }
    $message = 'Database connection failed';
    if ($isJsonPreferred) {
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        echo $message;
    }
    exit();
}

// Set charset to utf8
$conn->set_charset("utf8");
?>
