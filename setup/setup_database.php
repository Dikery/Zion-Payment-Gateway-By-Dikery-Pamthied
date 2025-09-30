<?php
echo "Setting up database tables...\n";
require 'create_users_table.php';
require 'create_payments_table.php';
require 'create_courses_and_fees.php';
echo "\nDatabase setup completed!\n";
echo "You can now:\n";
echo "1. Create new user accounts via auth/register.php\n";
echo "2. Login with admin/admin123 (default admin account)\n";
echo "3. Or create a new student account through registration\n";
?>
