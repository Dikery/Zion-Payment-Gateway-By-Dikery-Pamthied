<?php
session_start();
require '../includes/db_connect.php';

// Load available courses for dropdown
$courses = [];
$cRes = $conn->query("SHOW TABLES LIKE 'courses'");
if ($cRes && $cRes->num_rows > 0) {
    $cList = $conn->query("SELECT name FROM courses WHERE is_active = 1 ORDER BY name");
    if ($cList) { while ($r = $cList->fetch_assoc()) { $courses[] = $r['name']; } }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($conn->real_escape_string($_POST['username']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($conn->real_escape_string($_POST['first_name']));
    $last_name = trim($conn->real_escape_string($_POST['last_name']));
    $contact = trim($conn->real_escape_string($_POST['contact']));
    $student_id = trim($conn->real_escape_string($_POST['student_id']));
    $course = trim($conn->real_escape_string($_POST['course']));
    $semester = trim($conn->real_escape_string($_POST['semester']));

    $errors = [];

    // Validation
    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($first_name) || empty($last_name)) {
        $errors[] = "First and last names are required";
    }

    if (empty($contact) || !preg_match('/^[0-9]{10,15}$/', $contact)) {
        $errors[] = "Valid contact number is required (10-15 digits)";
    }

    if (empty($student_id)) {
        $errors[] = "Student ID is required";
    }

    // Check if username or email already exists
    $check_sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        $errors[] = "Username or email already exists";
    }

    // Check if student ID already exists
    $check_student_sql = "SELECT id FROM student_details WHERE student_id = '$student_id'";
    $check_student_result = $conn->query($check_student_sql);

    if ($check_student_result->num_rows > 0) {
        $errors[] = "Student ID already exists";
    }

    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Insert user
            $user_sql = "INSERT INTO users (username, email, password, first_name, last_name, contact, user_type)
                        VALUES ('$username', '$email', '$hashed_password', '$first_name', '$last_name', '$contact', 'student')";

            if ($conn->query($user_sql) === TRUE) {
                $user_id = $conn->insert_id;

                // Insert student details
                $student_sql = "INSERT INTO student_details (user_id, student_id, course, semester)
                               VALUES ('$user_id', '$student_id', '$course', '$semester')";

                if ($conn->query($student_sql) === TRUE) {
                    $conn->commit();
                    $success = "Registration successful! You can now login with your credentials.";
                } else {
                    throw new Exception("Error creating student profile: " . $conn->error);
                }
            } else {
                throw new Exception("Error creating user account: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Zion</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter', sans-serif; background: var(--bg, #f7f7f8); min-height:100vh; display:flex; }
        .layout { display:grid; grid-template-columns: 1fr 1fr; width:100%; max-width:1100px; margin:auto; background:#fff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; box-shadow:0 12px 36px rgba(15,23,42,.08); }
        .left { padding:40px; }
        .brand { display:flex; align-items:center; gap:10px; margin-bottom: 18px; }
        .brand-icon { width:36px; height:36px; border-radius:10px; background: linear-gradient(135deg, #667eea, #764ba2); color:#fff; display:flex; align-items:center; justify-content:center; box-shadow: 0 6px 14px rgba(102,126,234,.35); }
        .brand-name { font-weight:800; color:#111827; }
        .title { font-size:1.8rem; font-weight:800; color:#111827; margin-bottom:6px; }
        .sub { color:#6b7280; margin-bottom: 14px; }

        .alert { padding: 12px 14px; border-radius:10px; margin-bottom: 16px; font-weight:600; }
        .alert-error { background: rgba(231,76,60,.08); color:#e74c3c; border:1px solid rgba(231,76,60,.2); }
        .alert-success { background: rgba(46,204,113,.08); color:#2ecc71; border:1px solid rgba(46,204,113,.2); }

        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display:block; margin-bottom:8px; color:#374151; font-weight:600; font-size:.9rem; }
        .input-container { position:relative; }
        .input-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9ca3af; }
        input[type=text], input[type=email], input[type=password], select {
            width:100%; padding:14px 14px 14px 42px; border:1.5px solid #e5e7eb; border-radius:10px; background:#fff; transition: all .2s ease; font-size:.98rem;
        }
        input[type=text]:focus, input[type=email]:focus, input[type=password]:focus, select:focus { border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.12); outline:none; }

        .password-strength { margin-top:6px; display:flex; gap:5px; align-items:center; }
        .strength-bar { height:3px; flex:1; background:#eee; border-radius:2px; overflow:hidden; }
        .strength-fill { height:100%; width:0%; transition: all .3s ease; }
        .strength-weak { background:#e74c3c; } .strength-medium{ background:#f59e0b; } .strength-strong{ background:#2ecc71; }

        .btn-row { display:flex; gap:12px; margin-top: 8px; }
        .btn { flex:1; padding:14px 16px; border:none; border-radius:10px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:8px; position:relative; overflow:hidden; text-decoration:none; }
        .btn::before { content:''; position:absolute; left:-100%; top:0; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,.25), transparent); transition:left .5s; }
        .btn:hover::before { left:100%; }
        .btn-primary { background:#6366f1; color:#fff; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow:0 10px 18px rgba(99,102,241,.25); }
        .btn-secondary { background:#fff; color:#667eea; border:1.5px solid #667eea; }
        .btn-secondary:hover { background:#f5f7ff; }

        .meta { text-align:center; margin-top: 12px; color:#6b7280; }
        .meta a { color:#667eea; font-weight:600; text-decoration:none; }
        .meta a:hover { text-decoration: underline; }

        .right { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; padding:44px 46px; position:relative; }
        .right::after { content:''; position:absolute; width:240px; height:240px; right:-60px; top:-60px; border-radius:50%; background: rgba(255,255,255,.08); }
        .promo-icon { width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; background: rgba(255,255,255,.15); margin-bottom:22px; }
        .promo-title { font-size: 2rem; font-weight:800; line-height:1.2; margin-bottom: 14px; }
        .promo-sub { color: rgba(255,255,255,.92); line-height:1.6; margin-bottom: 18px; }
        .bullets { display:flex; flex-direction:column; gap:12px; }
        .bullets li { list-style:none; display:flex; align-items:center; gap:10px; }
        .bullets i { color:#a5b4fc; }

        @media (max-width: 900px) { .layout { grid-template-columns:1fr; max-width:640px; } .right { display:none; } .grid-2 { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <div class="layout">
        <div class="left">
            <div class="brand"><div class="brand-icon"><i class="fas fa-graduation-cap"></i></div><div class="brand-name">Zion</div></div>
            <div class="title">Create your student account</div>
            <div class="sub">Register to pay fees, track dues, and download receipts.</div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm">
            <div class="grid-2">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <div class="input-container">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="first_name" name="first_name" required
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <div class="input-container">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-container">
                    <i class="fas fa-at input-icon"></i>
                    <input type="text" id="username" name="username" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-container">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <div class="input-container">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="text" id="contact" name="contact" required
                               value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <div class="input-container">
                        <i class="fas fa-id-card input-icon"></i>
                        <input type="text" id="student_id" name="student_id" required
                               value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="course">Course</label>
                    <div class="input-container">
                        <i class="fas fa-graduation-cap input-icon"></i>
                        <select id="course" name="course" required>
                            <option value="">Select Course</option>
                            <?php if (!empty($courses)): ?>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c); ?>" <?php echo (isset($_POST['course']) && $_POST['course'] == $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="Computer Science" <?php echo (isset($_POST['course']) && $_POST['course'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                <option value="Information Technology" <?php echo (isset($_POST['course']) && $_POST['course'] == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                                <option value="Business Administration" <?php echo (isset($_POST['course']) && $_POST['course'] == 'Business Administration') ? 'selected' : ''; ?>>Business Administration</option>
                                <option value="Engineering" <?php echo (isset($_POST['course']) && $_POST['course'] == 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                                <option value="Arts" <?php echo (isset($_POST['course']) && $_POST['course'] == 'Arts') ? 'selected' : ''; ?>>Arts</option>
                                <option value="Science" <?php echo (isset($_POST['course']) && $_POST['course'] == 'Science') ? 'selected' : ''; ?>>Science</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="semester">Semester</label>
                    <div class="input-container">
                        <i class="fas fa-calendar input-icon"></i>
                        <select id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create account</button>
                <a href="../login.html" class="btn btn-secondary"><i class="fas fa-sign-in-alt"></i> Back to login</a>
            </div>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                Creating your account...
            </div>
        </form>

        <div class="meta">
            Already have an account? <a href="../login.html">Sign in here</a>
            <div style="margin-top:10px;"><a href="../index.html"><i class="fas fa-arrow-left"></i> Back to Home</a></div>
        </div>
        </div>

        <div class="right">
            <div class="promo-icon"><i class="fas fa-user-plus"></i></div>
            <div class="promo-title">Join the Zion community</div>
            <div class="promo-sub">Create your account in minutes and access a smooth, secure fee experience.</div>
            <ul class="bullets">
                <li><i class="fas fa-check-circle"></i> Quick registration</li>
                <li><i class="fas fa-check-circle"></i> Real-time payment history</li>
                <li><i class="fas fa-check-circle"></i> Smart semester mapping</li>
            </ul>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthFill = document.getElementById('strengthFill');
            const registerForm = document.getElementById('registerForm');

            // Password strength indicator
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;

                if (password.length >= 6) strength += 25;
                if (password.match(/[a-z]/)) strength += 25;
                if (password.match(/[A-Z]/)) strength += 25;
                if (password.match(/[0-9]/)) strength += 25;

                strengthFill.style.width = strength + '%';

                if (strength < 50) {
                    strengthFill.className = 'strength-fill strength-weak';
                } else if (strength < 75) {
                    strengthFill.className = 'strength-fill strength-medium';
                } else {
                    strengthFill.className = 'strength-fill strength-strong';
                }
            });

            // Password confirmation validation
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.style.borderColor = '#e74c3c';
                } else {
                    this.style.borderColor = '#2ecc71';
                }
            });

            // Form submission
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const submitBtn = document.querySelector('.btn-primary');
                const loading = document.getElementById('loading');

                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.7';
                loading.style.display = 'block';

                setTimeout(() => {
                    registerForm.submit();
                }, 1500);
            });

            // Populate semesters dynamically when course changes
            const courseSelect = document.getElementById('course');
            const semesterSelect = document.getElementById('semester');
            function ordinal(n){ const s=["th","st","nd","rd"], v=n%100; return n+(s[(v-20)%10]||s[v]||s[0]); }
            async function loadSemesters(){
                const c = courseSelect.value.trim();
                semesterSelect.innerHTML = '<option value="">Select Semester</option>';
                if(!c) return;
                try{
                    const r = await fetch('../auth/get_course_semesters.php?course=' + encodeURIComponent(c), { credentials: 'same-origin' });
                    const data = await r.json();
                    if(data && data.success){
                        for(let i=1;i<=data.num_semesters;i++){
                            const label = ordinal(i) + ' Semester';
                            const opt = document.createElement('option');
                            opt.value = label;
                            opt.textContent = label;
                            semesterSelect.appendChild(opt);
                        }
                    }
                }catch(e){ /* ignore */ }
            }
            courseSelect.addEventListener('change', loadSemesters);

            // Prefill semesters if a course was already selected (postback)
            if (courseSelect.value) { loadSemesters(); }

            // Entrance animation
            const layout = document.querySelector('.layout');
            layout.style.opacity = '0';
            layout.style.transform = 'translateY(24px)';
            setTimeout(() => { layout.style.transition = 'all .6s ease'; layout.style.opacity = '1'; layout.style.transform = 'translateY(0)'; }, 120);
        });
    </script>
</body>
</html>
