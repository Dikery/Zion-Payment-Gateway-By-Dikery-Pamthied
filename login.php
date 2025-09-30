<?php
// Redirect users who try to access login.php directly
// The correct login page is login.html
header("Location: login.html");
exit();
?>