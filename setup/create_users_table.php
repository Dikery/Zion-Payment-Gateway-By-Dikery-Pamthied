<?php
require '../includes/db_connect.php';

// Create users table with proper structure
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    contact VARCHAR(15) NOT NULL,
    user_type ENUM('admin', 'student') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully!\n";

    // Insert default admin user (password: admin123)
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_sql = "INSERT IGNORE INTO users (username, email, password, first_name, last_name, contact, user_type)
                  VALUES ('admin', 'admin@zion.edu', '$admin_password', 'System', 'Administrator', '0000000000', 'admin')";

    if ($conn->query($admin_sql) === TRUE) {
        echo "Default admin user created successfully!\n";
        echo "Admin credentials:\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "Error creating admin user: " . $conn->error . "\n";
    }

} else {
    echo "Error creating users table: " . $conn->error . "\n";
}

// Create student details table
$student_sql = "CREATE TABLE IF NOT EXISTS student_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    course VARCHAR(100),
    semester VARCHAR(20),
    fee_amount DECIMAL(10,2) DEFAULT 0.00,
    outstanding_amount DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($student_sql) === TRUE) {
    echo "Student details table created successfully!\n";
} else {
    echo "Error creating student details table: " . $conn->error . "\n";
}

$conn->close();
?>
