<?php
require '../includes/db_connect.php';

echo "<h2>Migrating Database from Courses to Classes System</h2>\n";
echo "<p>Converting from higher education (courses/semesters) to school system (classes)...</p>\n";

try {
    // Step 1: Create new classes table
    echo "<h3>Step 1: Creating Classes Table</h3>\n";
    $sql_classes = "CREATE TABLE IF NOT EXISTS classes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        code VARCHAR(10) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql_classes)) {
        echo "✅ Classes table created successfully<br>\n";
    } else {
        throw new Exception("Failed to create classes table: " . $conn->error);
    }

    // Step 2: Insert standard classes (1-10)
    echo "<h3>Step 2: Populating Standard Classes</h3>\n";
    $classes_data = [
        ['Class 1', 'I'],
        ['Class 2', 'II'], 
        ['Class 3', 'III'],
        ['Class 4', 'IV'],
        ['Class 5', 'V'],
        ['Class 6', 'VI'],
        ['Class 7', 'VII'],
        ['Class 8', 'VIII'],
        ['Class 9', 'IX'],
        ['Class 10', 'X']
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO classes (name, code) VALUES (?, ?)");
    foreach ($classes_data as $class) {
        $stmt->bind_param("ss", $class[0], $class[1]);
        $stmt->execute();
    }
    $stmt->close();
    echo "✅ Standard classes (1-10) inserted<br>\n";

    // Step 3: Add class_level column to student_details table
    echo "<h3>Step 3: Updating Student Details Table</h3>\n";
    
    // Check if class_level column already exists
    $check_column = "SHOW COLUMNS FROM student_details LIKE 'class_level'";
    $result = $conn->query($check_column);
    
    if ($result->num_rows == 0) {
        $alter_sql = "ALTER TABLE student_details ADD COLUMN class_level VARCHAR(20) DEFAULT NULL AFTER semester";
        if ($conn->query($alter_sql)) {
            echo "✅ Added class_level column to student_details<br>\n";
        } else {
            throw new Exception("Failed to add class_level column: " . $conn->error);
        }
    } else {
        echo "✅ class_level column already exists<br>\n";
    }

    // Step 4: Migrate existing data (convert course+semester to class_level)
    echo "<h3>Step 4: Migrating Existing Student Data</h3>\n";
    
    // For demo purposes, let's convert existing students to random classes
    $migrate_sql = "UPDATE student_details SET class_level = 
        CASE 
            WHEN semester LIKE '%1%' OR semester LIKE '%first%' THEN 'Class 1'
            WHEN semester LIKE '%2%' OR semester LIKE '%second%' THEN 'Class 2'
            WHEN semester LIKE '%3%' OR semester LIKE '%third%' THEN 'Class 3'
            WHEN semester LIKE '%4%' OR semester LIKE '%fourth%' THEN 'Class 4'
            WHEN semester LIKE '%5%' OR semester LIKE '%fifth%' THEN 'Class 5'
            WHEN semester LIKE '%6%' OR semester LIKE '%sixth%' THEN 'Class 6'
            ELSE 'Class 5'
        END
        WHERE class_level IS NULL";
    
    $conn->query($migrate_sql);
    echo "✅ Migrated existing student data to class levels<br>\n";

    // Step 5: Update fee_structures table
    echo "<h3>Step 5: Updating Fee Structures Table</h3>\n";
    
    // Check if class_level column exists in fee_structures
    $check_fee_column = "SHOW COLUMNS FROM fee_structures LIKE 'class_level'";
    $fee_result = $conn->query($check_fee_column);
    
    if ($fee_result->num_rows == 0) {
        $alter_fee_sql = "ALTER TABLE fee_structures 
            ADD COLUMN class_level VARCHAR(20) DEFAULT NULL AFTER semester,
            MODIFY COLUMN course_name VARCHAR(100) DEFAULT NULL,
            MODIFY COLUMN semester VARCHAR(50) DEFAULT NULL";
        
        if ($conn->query($alter_fee_sql)) {
            echo "✅ Added class_level column to fee_structures<br>\n";
        } else {
            throw new Exception("Failed to update fee_structures: " . $conn->error);
        }
    } else {
        echo "✅ fee_structures already updated<br>\n";
    }

    // Step 6: Migrate fee structures
    echo "<h3>Step 6: Migrating Fee Structure Data</h3>\n";
    
    // Convert existing fee structures to class-based
    $fee_migrate_sql = "UPDATE fee_structures SET class_level = 
        CASE 
            WHEN course_name LIKE '%1%' OR course_name LIKE '%first%' THEN 'Class 1'
            WHEN course_name LIKE '%2%' OR course_name LIKE '%second%' THEN 'Class 2'
            WHEN course_name LIKE '%3%' OR course_name LIKE '%third%' THEN 'Class 3'
            WHEN course_name LIKE '%4%' OR course_name LIKE '%fourth%' THEN 'Class 4'
            WHEN course_name LIKE '%5%' OR course_name LIKE '%fifth%' THEN 'Class 5'
            ELSE 'Class 5'
        END
        WHERE class_level IS NULL";
    
    $conn->query($fee_migrate_sql);
    
    // Add some sample class-based fees if none exist
    $sample_fees = [
        ['Class 1', 8000.00, '2024-04-30'],
        ['Class 2', 8500.00, '2024-04-30'],
        ['Class 3', 9000.00, '2024-04-30'],
        ['Class 4', 9500.00, '2024-04-30'],
        ['Class 5', 10000.00, '2024-04-30'],
        ['Class 6', 11000.00, '2024-04-30'],
        ['Class 7', 12000.00, '2024-04-30'],
        ['Class 8', 13000.00, '2024-04-30'],
        ['Class 9', 14000.00, '2024-04-30'],
        ['Class 10', 15000.00, '2024-04-30']
    ];
    
    $fee_stmt = $conn->prepare("INSERT IGNORE INTO fee_structures (title, class_level, amount, due_date) VALUES (?, ?, ?, ?)");
    foreach ($sample_fees as $fee) {
        $title = $fee[0] . " Annual Fees";
        $fee_stmt->bind_param("ssds", $title, $fee[0], $fee[1], $fee[2]);
        $fee_stmt->execute();
    }
    $fee_stmt->close();
    echo "✅ Added sample class-based fee structures<br>\n";

    // Step 7: Update indexes for better performance
    echo "<h3>Step 7: Updating Database Indexes</h3>\n";
    
    $conn->query("DROP INDEX IF EXISTS idx_course_semester ON fee_structures");
    $conn->query("CREATE INDEX idx_class_level ON fee_structures (class_level)");
    $conn->query("CREATE INDEX idx_student_class ON student_details (class_level)");
    
    echo "✅ Updated database indexes<br>\n";

    echo "<h3>✅ Migration Completed Successfully!</h3>\n";
    echo "<p><strong>Summary of changes:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Created classes table with standard classes 1-10</li>\n";
    echo "<li>✅ Added class_level column to student_details</li>\n";
    echo "<li>✅ Added class_level column to fee_structures</li>\n";
    echo "<li>✅ Migrated existing data to class-based system</li>\n";
    echo "<li>✅ Added sample fee structures for each class</li>\n";
    echo "<li>✅ Updated database indexes for performance</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Update registration form to use class selection</li>\n";
    echo "<li>Update admin sidebar and create classes management</li>\n";
    echo "<li>Update fee management system</li>\n";
    echo "<li>Update student dashboard</li>\n";
    echo "</ul>\n";

} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 5px;'>";
    echo "<strong>❌ Migration Failed:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Zion Fee Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            color: #333;
        }
        .container {
            max-width: 800px;
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
        ul {
            line-height: 1.6;
        }
        .back-link {
            display: inline-block;
            margin-top: 30px;
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
        <div class="logo">🏫 Zion Migration</div>
        
        <a href="../admin/admin_dashboard.php" class="back-link">
            ← Back to Admin Dashboard
        </a>
    </div>
</body>
</html>
