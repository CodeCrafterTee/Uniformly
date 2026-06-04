<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$user_id = null;

// Verify token
if (!empty($token)) {
    $sql = "SELECT user_id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
    } else {
        $error = "Invalid or expired reset link. Please request a new password reset.";
    }
    $stmt->close();
} else {
    $error = "No reset token provided.";
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $update_sql = "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $password_hash, $user_id);
        
        if ($update_stmt->execute()) {
            $success = "Password has been reset successfully! You can now login with your new password.";
            // Clear token for redirect
            $user_id = null;
        } else {
            $error = "Failed to reset password. Please try again.";
        }
        $update_stmt->close();
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Reset Password - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
        .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.75rem 1.8rem; border-radius: 40px; }
        .reset-container { max-width: 500px; margin: 0 auto; }
        .reset-card { background: white; border-radius: 32px; padding: 2.5rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08); }
        .form-control { border: 1.5px solid #e0d8cc; border-radius: 16px; padding: 0.75rem 1rem; }
        .form-control:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1); outline: none; }
        .input-group-icon { position: relative; }
        .input-group-icon i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9b8e7a; }
        .input-group-icon .form-control { padding-left: 2.5rem; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        @media (max-width: 768px) { .reset-card { padding: 1.5rem; margin: 1rem; } }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container my-5 py-4">
        <div class="reset-container">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-dark text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="login.php" class="text-dark text-decoration-none">Login</a></li>
                    <li class="breadcrumb-item active text-dark" aria-current="page">Reset Password</li>
                </ol>
            </nav>

            <div class="reset-card">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock fs-1" style="color: #000;"></i>
                    <h1 class="h2 fw-bold mt-2">Reset Password</h1>
                    <p class="text-muted">Create a new password for your account</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert-message alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-black">Login Now</a>
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert-message alert-error">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="forgot-password.php" class="btn btn-outline-black">Request New Reset Link</a>
                    </div>
                <?php elseif ($user_id): ?>
                    <form method="POST" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <div class="input-group-icon">
                                <i class="bi bi-lock"></i>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create a strong password" required>
                            </div>
                            <small class="text-muted">Use at least 8 characters with letters, numbers, and symbols</small>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group-icon">
                                <i class="bi bi-check-circle"></i>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-black w-100">Reset Password</button>
                    </form>
                <?php endif; ?>

                <hr class="my-4">

                <div class="text-center">
                    <a href="login.php" class="text-dark text-decoration-none">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password confirmation validation
    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');
    
    if (password && confirm) {
        confirm.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
</script>
</body>
</html>