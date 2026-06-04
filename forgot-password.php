<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$email = '';

// Process password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if email exists
        $sql = "SELECT user_id, first_name, last_name FROM users WHERE email = ? AND is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $update_sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $reset_token, $token_expiry, $user['user_id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // In production, send email with reset link
            // For now, just show success message
            $success = "Password reset link has been sent to your email address. Please check your inbox.";
            
            // Log the reset request (for demo purposes)
            error_log("Password reset requested for: " . $email . " Token: " . $reset_token);
            
        } else {
            // Don't reveal if email exists or not for security
            $success = "If an account exists with this email, a password reset link has been sent.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Forgot Password - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
        .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.75rem 1.8rem; border-radius: 40px; transition: all 0.25s ease; }
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .forgot-container { max-width: 500px; margin: 0 auto; }
        .forgot-card { background: white; border-radius: 32px; padding: 2.5rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08); border: 1px solid rgba(0, 0, 0, 0.05); }
        .form-control { border: 1.5px solid #e0d8cc; border-radius: 16px; padding: 0.75rem 1rem; }
        .form-control:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1); outline: none; }
        .input-group-icon { position: relative; }
        .input-group-icon i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9b8e7a; }
        .input-group-icon .form-control { padding-left: 2.5rem; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        @media (max-width: 768px) { .forgot-card { padding: 1.5rem; margin: 1rem; } }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container my-5 py-4">
        <div class="forgot-container">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-dark text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="login.php" class="text-dark text-decoration-none">Login</a></li>
                    <li class="breadcrumb-item active text-dark" aria-current="page">Forgot Password</li>
                </ol>
            </nav>

            <div class="forgot-card">
                <div class="text-center mb-4">
                    <i class="bi bi-key fs-1" style="color: #000;"></i>
                    <h1 class="h2 fw-bold mt-2">Forgot Password?</h1>
                    <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert-message alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert-message alert-error">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="forgot-password.php">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group-icon">
                            <i class="bi bi-envelope"></i>
                            <input type="email" class="form-control" id="email" name="email" placeholder="parent@example.com" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-black w-100 mb-3">Send Reset Link</button>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <p class="mb-0">Remember your password? <a href="login.php" class="text-dark fw-bold text-decoration-none">Back to Login</a></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>