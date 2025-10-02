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
