<?php
session_start();
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin already exists
$admin_check_sql = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
$admin_check_result = $conn->query($admin_check_sql);

$admin_exists = $admin_check_result->num_rows > 0;

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $admin_secret = trim($_POST['admin_secret'] ?? '');
    
    $errors = [];
    
    // Secret key validation (CHANGE THIS TO YOUR OWN SECRET!)
    $secret_key = 'UniformMarketAdmin2024!';
    
    if ($admin_secret !== $secret_key) {
        $errors[] = "Invalid admin registration key.";
    }
    
    if (empty($first_name)) {
        $errors[] = "First name is required";
    } elseif (strlen($first_name) < 2) {
        $errors[] = "First name must be at least 2 characters";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    } elseif (strlen($last_name) < 2) {
        $errors[] = "Last name must be at least 2 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    } else {
        // Check if email already exists
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $errors[] = "This email is already registered.";
        }
        $check_stmt->close();
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $user_type = 'admin';
        $is_verified = 1;
        
        // Insert admin user
        $insert_sql = "INSERT INTO users (email, password_hash, first_name, last_name, phone, user_type, is_verified, is_active, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssssssi", $email, $password_hash, $first_name, $last_name, $phone, $user_type, $is_verified);
        
        if ($insert_stmt->execute()) {
            $user_id = $insert_stmt->insert_id;
            
            // Insert user preferences
            $pref_sql = "INSERT INTO user_preferences (user_id, dark_mode, email_notifications, marketing_emails, message_notifications) 
                         VALUES (?, 0, 1, 0, 1)";
            $pref_stmt = $conn->prepare($pref_sql);
            $pref_stmt->bind_param("i", $user_id);
            $pref_stmt->execute();
            $pref_stmt->close();
            
            // Create welcome notification
            $notif_sql = "INSERT INTO notifications (user_id, type, title, message, link) 
                          VALUES (?, 'system', 'Admin Account Created', 
                          'Welcome to UniformMarket Admin Panel! You have full access to manage the platform.', 
                          '/admin/dashboard.php')";
            $notif_stmt = $conn->prepare($notif_sql);
            $notif_stmt->bind_param("i", $user_id);
            $notif_stmt->execute();
            $notif_stmt->close();
            
            $success_message = "Admin account created successfully! You can now login.";
            $insert_stmt->close();
        } else {
            $error_message = "Registration failed: " . $insert_stmt->error;
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin Registration - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #FEF9E6 0%, #F5EFE0 100%);
            min-height: 100vh;
        }
        .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.75rem 1.8rem; border-radius: 40px; transition: all 0.25s ease; }
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08); }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .register-container { max-width: 550px; margin: 0 auto; padding: 2rem 0; }
        .register-card { background: white; border-radius: 32px; padding: 2.5rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1); border: 1px solid rgba(0, 0, 0, 0.05); }
        .form-control { border: 1.5px solid #e0d8cc; border-radius: 16px; padding: 0.75rem 1rem; font-size: 1rem; transition: all 0.2s; }
        .form-control:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1); outline: none; }
        .form-label { font-weight: 600; margin-bottom: 0.5rem; color: #2c2c2c; }
        .input-group-icon { position: relative; }
        .input-group-icon i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9b8e7a; font-size: 1.1rem; }
        .input-group-icon .form-control { padding-left: 2.5rem; }
        .password-strength { margin-top: 0.5rem; font-size: 0.75rem; }
        .strength-bar { height: 4px; background: #e0d8cc; border-radius: 2px; margin-top: 0.5rem; overflow: hidden; }
        .strength-progress { height: 100%; width: 0%; transition: width 0.3s; }
        .strength-weak { background: #c62828; width: 25%; }
        .strength-fair { background: #ff9800; width: 50%; }
        .strength-good { background: #ffc107; width: 75%; }
        .strength-strong { background: #2e7d32; width: 100%; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        .admin-badge { background: #000; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600; display: inline-block; margin-bottom: 1rem; }
        .info-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; }
        @media (max-width: 768px) { .register-card { padding: 1.5rem; margin: 1rem; } }
    </style>
</head>
<body>

<main>
    <div class="container">
        <div class="register-container">
            <div class="register-card">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock-fill fs-1" style="color: #000;"></i>
                    <div class="admin-badge"><i class="bi bi-person-badge"></i> Admin Registration</div>
                    <h1 class="h2 fw-bold mt-2">Create Admin Account</h1>
                    <p class="text-muted">One-time setup for platform administrator</p>
                </div>

                <?php if ($admin_exists && !$success_message): ?>
                    <div class="alert-message alert-error">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                        Admin account already exists! Please <a href="login.php" class="text-dark fw-bold">login here</a>.
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert-message alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-black rounded-pill">Go to Login</a>
                    </div>
                <?php endif; ?>

                <?php if ($error_message && !$admin_exists): ?>
                    <div class="alert-message alert-error">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$admin_exists && !$success_message): ?>
                    <div class="info-box small">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>First-time setup:</strong> This registration can only be used once to create the administrator account.
                    </div>

                    <form method="POST" action="register.php" id="registerForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <div class="input-group-icon">
                                    <i class="bi bi-person"></i>
                                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="John" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <div class="input-group-icon">
                                    <i class="bi bi-person"></i>
                                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Doe" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group-icon">
                                <i class="bi bi-envelope"></i>
                                <input type="email" class="form-control" id="email" name="email" placeholder="admin@uniformmarket.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number (Optional)</label>
                            <div class="input-group-icon">
                                <i class="bi bi-telephone"></i>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+27 12 345 6789">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="admin_secret" class="form-label">Admin Registration Key</label>
                            <div class="input-group-icon">
                                <i class="bi bi-key"></i>
                                <input type="password" class="form-control" id="admin_secret" name="admin_secret" placeholder="Enter the admin registration key" required>
                            </div>
                            <small class="text-muted">Default key: <code>UniformMarketAdmin2024!</code> (Change this after setup)</small>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group-icon">
                                <i class="bi bi-lock"></i>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create a strong password" required>
                            </div>
                            <div class="password-strength">
                                <small id="passwordHelp" class="text-muted">Use 8+ characters with uppercase, lowercase, and numbers</small>
                                <div class="strength-bar">
                                    <div class="strength-progress" id="strengthProgress"></div>
                                </div>
                                <small id="strengthText" class="text-muted"></small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group-icon">
                                <i class="bi bi-check-circle"></i>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-black w-100 mb-3">Create Admin Account</button>
                    </form>
                <?php endif; ?>

                <hr class="my-4">

                <div class="text-center">
                    <p class="mb-0"><a href="login.php" class="text-dark fw-bold text-decoration-none">← Back to Login</a></p>
                </div>
            </div>

            <div class="text-center mt-4">
                <div class="d-flex justify-content-center gap-4 flex-wrap">
                    <small class="text-muted"><i class="bi bi-shield-check"></i> Secure Admin Registration</small>
                    <small class="text-muted"><i class="bi bi-lock-fill"></i> One-time Setup</small>
                    <small class="text-muted"><i class="bi bi-person-badge"></i> Full Platform Access</small>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const strengthProgress = document.getElementById('strengthProgress');
    const strengthText = document.getElementById('strengthText');

    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        const strengthLevels = ['Weak', 'Fair', 'Good', 'Strong'];
        const strengthClasses = ['strength-weak', 'strength-fair', 'strength-good', 'strength-strong'];
        
        if (password.length === 0) {
            strengthProgress.className = 'strength-progress';
            strengthText.textContent = '';
            return;
        }
        
        const index = Math.min(strength, 3);
        strengthProgress.className = `strength-progress ${strengthClasses[index]}`;
        strengthText.textContent = `${strengthLevels[index]} password`;
        strengthText.style.color = index === 0 ? '#c62828' : index === 1 ? '#ff9800' : index === 2 ? '#ffc107' : '#2e7d32';
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    }

    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
</script>
</body>
</html>