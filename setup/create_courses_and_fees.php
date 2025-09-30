<?php
require '../includes/db_connect.php';

// Create courses table
$sqlCourses = "CREATE TABLE IF NOT EXISTS courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  code VARCHAR(20) DEFAULT NULL,
  num_semesters INT UNSIGNED NOT NULL DEFAULT 6,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sqlCourses)) {
  echo "Error creating courses table: " . $conn->error . "\n";
}

// Ensure num_semesters column exists (for upgrades)
$checkCol = $conn->query("SHOW COLUMNS FROM courses LIKE 'num_semesters'");
if ($checkCol && $checkCol->num_rows === 0) {
  $conn->query("ALTER TABLE courses ADD COLUMN num_semesters INT UNSIGNED NOT NULL DEFAULT 6 AFTER code");
}

// Create fee_structures table
$sqlFees = "CREATE TABLE IF NOT EXISTS fee_structures (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) DEFAULT NULL,
  course_name VARCHAR(100) NOT NULL,
  semester VARCHAR(50) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  due_date DATE DEFAULT NULL,
  late_fee DECIMAL(10,2) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_course_semester (course_name, semester)
)";

if (!$conn->query($sqlFees)) {
  echo "Error creating fee_structures table: " . $conn->error . "\n";
}

// Migration: drop unique uk_course_semester if exists
$uniqCheck = $conn->query("SHOW INDEX FROM fee_structures WHERE Key_name='uk_course_semester'");
if ($uniqCheck && $uniqCheck->num_rows > 0) {
  $conn->query("ALTER TABLE fee_structures DROP INDEX uk_course_semester");
}

echo "Courses and fee structures ready.\n";

$conn->close();
?>

