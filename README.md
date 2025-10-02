# Zion Fee Payment Portal

A comprehensive school fee management system with student registration, secure payment processing, and administrative controls.

## 🎯 Features

### **Student Management**
✅ **Student Registration System** - Complete registration with class selection  
✅ **Secure Authentication** - Password hashing and verification  
✅ **Class-Based Organization** - Support for Class 1-10 system  
✅ **Student Profiles** - Detailed student information management  

### **Fee Management**
✅ **Dynamic Fee Structures** - Configurable fees by class level  
✅ **Payment Processing** - Secure payment handling with receipts  
✅ **Outstanding Dues Tracking** - Real-time balance calculations  
✅ **Payment History** - Complete transaction records  
✅ **Due Date Management** - Automated due date tracking  

### **Administrative Tools**
✅ **Admin Dashboard** - Comprehensive management interface  
✅ **User Management** - Add, edit, and manage student accounts  
✅ **Fee Configuration** - Create and manage fee structures  
✅ **Class Management** - Manage school classes and codes  
✅ **Payment Reports** - Detailed payment analytics  
✅ **Real-time Notifications** - Payment alerts and updates  

### **Technical Features**
✅ **Database Integration** - MySQL with proper relationships  
✅ **Session Management** - Secure user sessions  
✅ **Responsive Design** - Mobile-friendly interface  
✅ **PDF Receipts** - Downloadable payment receipts  

## 🚀 Quick Setup

### 1. Database Setup
Run the comprehensive database setup:
```bash
# Navigate to setup directory
cd setup

# Run setup script
php setup_database.php
```
Or using XAMPP:
```powershell
C:\xampp\php\php.exe setup\setup_database.php
```

### 2. Default Admin Account
Login credentials after setup:
- **Username:** `admin`
- **Password:** `admin123`

### 3. Access the System
- **Login Page:** `login.html`
- **Student Registration:** `auth/register.php`
- **Admin Dashboard:** `admin/admin_dashboard.php`

## 📁 Project Structure

```
zion/
├── auth/                          # Authentication system
│   ├── login.php                  # Login processing
│   ├── register.php               # Student registration
│   └── logout.php                 # Session termination
├── admin/                         # Administrative interface
│   ├── admin_dashboard.php        # Main admin dashboard
│   ├── manage_users.php           # User management
│   ├── fee_management.php         # Fee structure management
│   ├── classes.php                # Class management
│   ├── payment_reports.php        # Payment analytics
│   └── partials/                  # Admin UI components
├── payments/                      # Payment processing
│   ├── due_fees.php               # Outstanding fees display
│   ├── process_payment.php        # Payment handler
│   ├── receipt.php                # Receipt generation
│   └── payment_history.php        # Transaction history
├── includes/                      # Core components
│   └── db_connect.php             # Database connection
├── setup/                         # Database setup
│   ├── setup_database.php         # Complete setup script
│   ├── database.txt               # SQL schema
│   └── migrate_to_classes.php     # Migration utilities
├── public/                        # Static assets
│   ├── theme.css                  # Styling system
│   └── ui.js                      # UI interactions
├── dashboard.php                  # Student dashboard
├── login.html                     # Login interface
└── make_payment.php               # Payment interface
```

## 🗄️ Database Schema

The system uses **5 core tables**:

### 1. **users** - Authentication & User Data
- User credentials and basic information
- Role management (admin/student)
- Payment totals and timestamps

### 2. **classes** - School Class Management
- Class definitions (Class 1-10)
- Class codes and status

### 3. **student_details** - Extended Student Info
- Student IDs and class assignments
- Links to users table
- Fee tracking information

### 4. **fee_structures** - Configurable Fee System
- Class-based fee definitions
- Due dates and late fees
- Multiple fees per class support

### 5. **payments** - Transaction Records
- Payment processing records
- Links to users and fee structures
- Transaction status tracking

## 👥 User Roles & Access

### **Students**
- Register with class selection
- View outstanding dues by class
- Make secure payments
- Download receipts
- Track payment history
- View personalized dashboard

### **Administrators**
- Manage all student accounts
- Configure fee structures
- Process payments and refunds
- Generate detailed reports
- Manage class definitions
- View system analytics

## 🔧 System Requirements

- **PHP 7.4+** with MySQLi extension
- **MySQL 5.7+** or MariaDB 10.2+
- **Web Server** (Apache/Nginx)
- **Modern Browser** with JavaScript enabled

## 🔒 Security Features

- **Password Hashing** - bcrypt with salt
- **SQL Injection Prevention** - Prepared statements
- **Session Security** - Secure session handling
- **Input Validation** - Server-side validation
- **CSRF Protection** - Form token validation
- **Role-Based Access** - Admin/student separation

## 🚀 Getting Started

### For New Students:
1. Visit `auth/register.php`
2. Select your class from dropdown
3. Complete registration form
4. Login at `login.html`
5. View dues and make payments

### For Administrators:
1. Login with `admin`/`admin123`
2. Access admin dashboard
3. Manage students and fees
4. Configure class structures
5. Monitor payment activity

## 📊 Key Features in Detail

### **Class-Based System**
- Supports standard school classes (1-10)
- Each class can have multiple fee types
- Automatic fee calculation by class level

### **Payment Processing**
- Secure transaction handling
- Multiple payment methods support
- Automatic receipt generation
- Real-time balance updates

### **Administrative Control**
- Complete user management
- Dynamic fee configuration
- Payment tracking and reporting
- Class and student organization

## 🛠️ Development Notes

Built with modern web technologies:
- **Backend:** PHP with MySQLi
- **Frontend:** HTML5, CSS3, JavaScript
- **Database:** MySQL with InnoDB engine
- **UI Framework:** Custom responsive design
- **Icons:** Font Awesome 6

## 📞 Troubleshooting

### Database Issues:
1. Verify MySQL service is running
2. Check `includes/db_connect.php` credentials
3. Ensure `zion` database exists
4. Re-run `setup/setup_database.php`

### Permission Issues:
1. Verify PHP file permissions
2. Check web server configuration
3. Ensure MySQL user has proper privileges

### Login Problems:
1. Clear browser cache and cookies
2. Verify admin credentials: `admin`/`admin123`
3. Check session configuration in PHP