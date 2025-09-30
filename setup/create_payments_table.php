<?php
require '../includes/db_connect.php';

try {
    // Create payments table
    $sql = "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        transaction_id VARCHAR(100) UNIQUE NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if ($conn->query($sql) === TRUE) {
        echo "✅ Payments table created successfully!<br>";

        // Add total_paid column to users table if it doesn't exist
        $check_column = "SHOW COLUMNS FROM users LIKE 'total_paid'";
        $result = $conn->query($check_column);

        if ($result->num_rows == 0) {
            $alter_sql = "ALTER TABLE users ADD COLUMN total_paid DECIMAL(10, 2) DEFAULT 0";
            $conn->query($alter_sql);
            echo "✅ Added total_paid column to users table<br>";
        }

        // Add last_payment_date column to users table if it doesn't exist
        $check_column = "SHOW COLUMNS FROM users LIKE 'last_payment_date'";
        $result = $conn->query($check_column);

        if ($result->num_rows == 0) {
            $alter_sql = "ALTER TABLE users ADD COLUMN last_payment_date TIMESTAMP NULL";
            $conn->query($alter_sql);
            echo "✅ Added last_payment_date column to users table<br>";
        }

        echo "🎉 Database setup completed successfully!";
        echo "<br><br><a href='../index.html' style='color: #667eea; text-decoration: none; font-weight: 600;'>← Back to Home</a>";

    } else {
        throw new Exception("Error creating payments table: " . $conn->error);
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
    echo "<br><br><a href='../index.html' style='color: #667eea; text-decoration: none; font-weight: 600;'>← Back to Home</a>";
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .setup-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .logo {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        .title {
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .message {
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .success {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }
        .error {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="logo">
            <i class="fas fa-database"></i>
        </div>
        <h2 class="title">Database Setup</h2>
        <div class="message">
            Setting up the payments table and updating the database structure...
        </div>
    </div>
</body>
</html>
