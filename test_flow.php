<?php
// Test file to verify authentication flow
echo "<h2>Zion Portal File Path Test</h2>";

echo "<h3>Testing File Existence:</h3>";
$files_to_check = [
    'index.html',
    'login.html', 
    'dashboard.php',
    'make_payment.php',
    'payment_history.php',
    'auth/login.php',
    'auth/register.php',
    'auth/logout.php',
    'admin/admin_dashboard.php',
    'admin/manage_users.php',
    'payments/receipt.php',
    'payments/process_payment.php',
    'payments/get_payment_history.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file NOT FOUND<br>";
    }
}

echo "<h3>Testing URLs:</h3>";
echo "<a href='index.html'>Go to Home Page</a><br>";
echo "<a href='login.html'>Go to Login Page</a><br>";
echo "<a href='auth/register.php'>Go to Register Page</a><br>";

echo "<p><strong>Navigation Flow:</strong></p>";
echo "1. Start at index.html<br>";
echo "2. Click 'Student Login' → login.html<br>";
echo "3. Submit form → auth/login.php<br>";
echo "4. After login → dashboard.php<br>";
echo "5. From dashboard → make_payment.php or payment_history.php<br>";
?>