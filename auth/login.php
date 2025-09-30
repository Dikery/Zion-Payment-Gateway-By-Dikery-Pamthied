<?php
session_start();
require '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($conn->real_escape_string($_POST['username']));
    $password = $_POST['password'];

    $error = "";

    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Check users table for authentication
        $sql = "SELECT u.id, u.username, u.password, u.first_name, u.last_name, u.user_type, u.email,
                       sd.student_id, sd.course, sd.semester, sd.fee_amount, sd.outstanding_amount
                FROM users u
                LEFT JOIN student_details sd ON u.id = sd.user_id
                WHERE u.username = '$username' OR u.email = '$username'";

        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['first_name'] . " " . $user['last_name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['email'] = $user['email'];

                // Set student-specific session variables if user is a student
                if ($user['user_type'] === 'student') {
                    $_SESSION['student_id'] = $user['student_id'];
                    $_SESSION['course'] = $user['course'];
                    $_SESSION['semester'] = $user['semester'];
                    $_SESSION['fee_amount'] = $user['fee_amount'] ?? 0.00;
                    $_SESSION['outstanding_amount'] = $user['outstanding_amount'] ?? 0.00;
                }

                // Redirect based on user type
                if ($user['user_type'] === 'admin') {
                    header("Location: ../admin/admin_dashboard.php");
                } else {
                    header("Location: ../dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<!-- Simple error display -->
<!DOCTYPE html>
<html>
<head><title>Login Error</title></head>
<body>
<?php if (!empty($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
<a href="../login.html">Back to login</a>
</body>
</html>
