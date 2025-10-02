<?php
/**
 * Zion Fee Payment Portal - Complete Database Setup
 * Creates all necessary tables for the school fee management system
 */

echo "<h2>🏫 Zion Fee Payment Portal - Database Setup</h2>\n";
echo "<p>Setting up all required tables for the school fee management system...</p>\n";

require '../includes/db_connect.php';

$errors = [];
$success_messages = [];

try {
    // Step 1: Create users table
    echo "<h3>Step 1: Creating Users Table</h3>\n";
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        contact VARCHAR(15) NOT NULL,
        user_type ENUM('admin','student') NOT NULL DEFAULT 'student',
        total_paid DECIMAL(10,2) NOT NULL DEFAULT 0,
        last_payment_date TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_users_username (username),
        KEY idx_users_email (email),
        KEY idx_users_type (user_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql_users)) {
        $success_messages[] = "✅ Users table created successfully";
        
        // Create default admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $admin_sql = "INSERT IGNORE INTO users (username, email, password, first_name, last_name, contact, user_type)
                      VALUES ('admin', 'admin@zion.edu', ?, 'System', 'Administrator', '0000000000', 'admin')";
        $stmt = $conn->prepare($admin_sql);
        $stmt->bind_param('s', $admin_password);
        
        if ($stmt->execute()) {
            $success_messages[] = "✅ Default admin user created (admin/admin123)";
        }
        $stmt->close();
    } else {
        $errors[] = "❌ Failed to create users table: " . $conn->error;
    }

    // Step 2: Create classes table
    echo "<h3>Step 2: Creating Classes Table</h3>\n";
    $sql_classes = "CREATE TABLE IF NOT EXISTS classes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL UNIQUE,
        code VARCHAR(10) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_classes_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql_classes)) {
        $success_messages[] = "✅ Classes table created successfully";
        
        // Insert standard classes (1-10)
        $classes_data = [
            ['Class 1', 'I'], ['Class 2', 'II'], ['Class 3', 'III'], ['Class 4', 'IV'], ['Class 5', 'V'],
            ['Class 6', 'VI'], ['Class 7', 'VII'], ['Class 8', 'VIII'], ['Class 9', 'IX'], ['Class 10', 'X']
        ];
        
        $stmt = $conn->prepare("INSERT IGNORE INTO classes (name, code) VALUES (?, ?)");
        foreach ($classes_data as $class) {
            $stmt->bind_param("ss", $class[0], $class[1]);
            $stmt->execute();
        }
        $stmt->close();
        $success_messages[] = "✅ Standard classes (1-10) inserted";
    } else {
        $errors[] = "❌ Failed to create classes table: " . $conn->error;
    }

    // Step 3: Create student_details table
    echo "<h3>Step 3: Creating Student Details Table</h3>\n";
    $sql_student_details = "CREATE TABLE IF NOT EXISTS student_details (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        student_id VARCHAR(20) NOT NULL UNIQUE,
        course VARCHAR(100) DEFAULT NULL,
        semester VARCHAR(20) DEFAULT NULL,
        class_level VARCHAR(20) DEFAULT NULL,
        fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        outstanding_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_student_id (student_id),
        KEY idx_student_details_user_id (user_id),
        KEY idx_student_class_level (class_level),
        CONSTRAINT fk_student_details_user
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql_student_details)) {
        $success_messages[] = "✅ Student details table created successfully";
    } else {
        $errors[] = "❌ Failed to create student_details table: " . $conn->error;
    }

    // Step 4: Create fee_structures table
    echo "<h3>Step 4: Creating Fee Structures Table</h3>\n";
    $sql_fee_structures = "CREATE TABLE IF NOT EXISTS fee_structures (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(150) DEFAULT NULL,
        course_name VARCHAR(100) DEFAULT NULL,
        semester VARCHAR(50) DEFAULT NULL,
        class_level VARCHAR(20) DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        due_date DATE DEFAULT NULL,
        late_fee DECIMAL(10,2) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_fee_class_level (class_level),
        KEY idx_fee_active (is_active),
        KEY idx_fee_due_date (due_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql_fee_structures)) {
        $success_messages[] = "✅ Fee structures table created successfully";
        
        // Add sample fee structures for each class
        $sample_fees = [
            ['Class 1 Annual Fees', 'Class 1', 8000.00, '2024-04-30'],
            ['Class 2 Annual Fees', 'Class 2', 8500.00, '2024-04-30'],
            ['Class 3 Annual Fees', 'Class 3', 9000.00, '2024-04-30'],
            ['Class 4 Annual Fees', 'Class 4', 9500.00, '2024-04-30'],
            ['Class 5 Annual Fees', 'Class 5', 10000.00, '2024-04-30'],
            ['Class 6 Annual Fees', 'Class 6', 11000.00, '2024-04-30'],
            ['Class 7 Annual Fees', 'Class 7', 12000.00, '2024-04-30'],
            ['Class 8 Annual Fees', 'Class 8', 13000.00, '2024-04-30'],
            ['Class 9 Annual Fees', 'Class 9', 14000.00, '2024-04-30'],
            ['Class 10 Annual Fees', 'Class 10', 15000.00, '2024-04-30']
        ];
        
        $stmt = $conn->prepare("INSERT IGNORE INTO fee_structures (title, class_level, amount, due_date) VALUES (?, ?, ?, ?)");
        foreach ($sample_fees as $fee) {
            $stmt->bind_param('ssds', $fee[0], $fee[1], $fee[2], $fee[3]);
            $stmt->execute();
        }
        $stmt->close();
        $success_messages[] = "✅ Sample fee structures added";
    } else {
        $errors[] = "❌ Failed to create fee_structures table: " . $conn->error;
    }

    // Step 5: Create payments table
    echo "<h3>Step 5: Creating Payments Table</h3>\n";
    $sql_payments = "CREATE TABLE IF NOT EXISTS payments (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        fee_id INT UNSIGNED DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        transaction_id VARCHAR(100) NOT NULL UNIQUE,
        status VARCHAR(20) NOT NULL DEFAULT 'completed',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_transaction_id (transaction_id),
        KEY idx_payments_user_id (user_id),
        KEY idx_payments_fee_id (fee_id),
        KEY idx_payments_status (status),
        KEY idx_payments_created_at (created_at),
        CONSTRAINT fk_payments_user
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_payments_fee
            FOREIGN KEY (fee_id) REFERENCES fee_structures(id)
            ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql_payments)) {
        $success_messages[] = "✅ Payments table created successfully";
    } else {
        $errors[] = "❌ Failed to create payments table: " . $conn->error;
    }

    // Final success message
    if (empty($errors)) {
        echo "<div style='background: #d1fae5; border: 1px solid #10b981; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3 style='color: #065f46; margin: 0 0 15px 0;'>🎉 Database Setup Completed Successfully!</h3>";
        echo "<div style='color: #047857;'>";
        foreach ($success_messages as $msg) {
            echo "<p style='margin: 5px 0;'>$msg</p>";
        }
        echo "</div>";
        echo "</div>";
        
        echo "<div style='background: #eff6ff; border: 1px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h4 style='color: #1e40af; margin: 0 0 10px 0;'>📋 Database Tables Created:</h4>";
        echo "<div style='color: #1e40af;'>";
        echo "<p>✅ <strong>users</strong> - Authentication & user data</p>";
        echo "<p>✅ <strong>classes</strong> - School class management (Class 1-10)</p>";
        echo "<p>✅ <strong>student_details</strong> - Extended student information</p>";
        echo "<p>✅ <strong>fee_structures</strong> - Configurable fees by class</p>";
        echo "<p>✅ <strong>payments</strong> - Transaction records with fee linking</p>";
        echo "<p>🔗 All foreign key relationships established</p>";
        echo "<p>📊 Sample data inserted for classes and fees</p>";
        echo "</div>";
        echo "</div>";
    }

} catch (Exception $e) {
    $errors[] = "❌ Critical error: " . $e->getMessage();
}

// Display errors if any
if (!empty($errors)) {
    echo "<div style='background: #fee2e2; border: 1px solid #ef4444; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #b91c1c; margin: 0 0 15px 0;'>❌ Setup Errors:</h3>";
    echo "<div style='color: #b91c1c;'>";
    foreach ($errors as $error) {
        echo "<p style='margin: 5px 0;'>$error</p>";
    }
    echo "</div>";
    echo "</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Zion Fee Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .logo {
            text-align: center;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        h3 {
            color: #667eea;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .back-links {
            text-align: center;
            margin-top: 30px;
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .back-link {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🏫 Zion Setup</div>
        
        <div class="back-links">
            <a href="../login.html" class="back-link">
                🔐 Go to Login
            </a>
            <a href="../auth/register.php" class="back-link">
                👥 Student Registration
            </a>
            <a href="../admin/admin_dashboard.php" class="back-link">
                ⚙️ Admin Dashboard
            </a>
        </div>
    </div>
</body>
</html>
