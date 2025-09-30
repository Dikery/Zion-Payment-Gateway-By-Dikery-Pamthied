<?php
// Return num_semesters for a given course name as JSON
if (ob_get_level() === 0) { ob_start(); }
@ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json');

require '../includes/db_connect.php';

$course = $_GET['course'] ?? '';
$course = trim($course);

if ($course === '') {
  http_response_code(400);
  if (ob_get_length() !== false) { ob_clean(); }
  echo json_encode(['success' => false, 'message' => 'Missing course']);
  exit();
}

$stmt = $conn->prepare("SELECT num_semesters FROM courses WHERE name = ? AND is_active = 1 LIMIT 1");
if (!$stmt) {
  http_response_code(500);
  if (ob_get_length() !== false) { ob_clean(); }
  echo json_encode(['success' => false, 'message' => 'DB error']);
  exit();
}
$stmt->bind_param('s', $course);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
  if (ob_get_length() !== false) { ob_clean(); }
  echo json_encode(['success' => true, 'num_semesters' => (int)$row['num_semesters']]);
} else {
  if (ob_get_length() !== false) { ob_clean(); }
  echo json_encode(['success' => false, 'message' => 'Course not found or inactive']);
}
$stmt->close();
$conn->close();
if (ob_get_length() !== false) { ob_end_flush(); }
?>

