# Zion Fee Payment Portal

A complete fee payment portal system with user registration, authentication, and admin dashboard.

## Features

✅ **User Registration System** - Students can create new accounts
✅ **Secure Authentication** - Password hashing and verification
✅ **Admin Dashboard** - Complete admin interface for user management
✅ **Student Dashboard** - Student interface for fee management
✅ **Database Integration** - MySQL database with proper user tables
✅ **Session Management** - Secure user sessions

## Setup Instructions

### 1. Database Setup
Run the database setup script to create all necessary tables:
```bash
php setup_database.php
```
Or using XAMPP PHP:
```powershell
C:\xampp\php\php.exe setup_database.php
```

### 2. Default Admin Account
After running the setup, you can login with:
- **Username:** admin
- **Password:** admin123

### 3. Start Using the System

#### For Students:
1. Go to `register.php` to create a new account
2. Fill in all required information (username, email, password, personal details, student ID, course, semester)
3. Login using your credentials at `login.html`

#### For Admins:
1. Login with admin/admin123 at `login.html`
2. Access the admin dashboard with user statistics and management options

## File Structure

- `login.html` - Login page with registration link
- `register.php` - User registration system
- `login.php` - Authentication processing
- `admin_dashboard.php` - Admin interface
- `dashboard.php` - Student dashboard
- `db_connect.php` - Database connection
- `create_users_table.php` - Database setup script
- `setup_database.php` - Complete setup script

## Security Features

- **Password Hashing** - Uses PHP's `password_hash()` for secure storage
- **Input Validation** - Server-side validation for all user inputs
- **SQL Injection Prevention** - Uses prepared statements and escaping
- **Session Security** - Proper session management and validation
- **User Type Management** - Separate admin and student access levels

## Database Tables

### Users Table
- Stores user account information
- Includes username, email, password hash, personal details
- User type classification (admin/student)

### Student Details Table
- Extended student information
- Links to users table via foreign key
- Stores course, semester, fee information

## Usage

1. **New Student Registration:**
   - Visit `register.php`
   - Fill all required fields
   - Create secure password (minimum 6 characters)
   - Login with new credentials

2. **Admin Access:**
   - Use default admin/admin123 or create new admin accounts
   - Access comprehensive dashboard
   - Manage users and view statistics

3. **Student Features:**
   - View personal dashboard
   - Make payments
   - View payment history
   - Update profile information

## Development

The system is built with:
- **PHP** - Server-side scripting
- **MySQL** - Database management
- **HTML/CSS** - Frontend interface
- **Responsive Design** - Mobile-friendly layouts

## Security Notes

- All passwords are hashed using PHP's built-in password hashing
- Input validation prevents common security vulnerabilities
- Session management ensures secure user authentication
- Admin functions are protected by user type verification

## Troubleshooting

If you encounter database connection issues:
1. Ensure XAMPP/MySQL is running
2. Check database credentials in `db_connect.php`
3. Verify the database "zion" exists
4. Run the setup script again if needed

For PHP execution issues:
- Use `C:\xampp\php\php.exe` to run PHP files
- Ensure PHP is properly installed via XAMPP

please fix the login design. use the reference image that i have provided. the copy can be changed to out own copy (use this project and come up with good copy) the nexus logo can be a school logo with the the text can be Zion. and the below google or github login can be a admin login button.