<?php
session_start();
require_once '../config/database.php';

// If already logged in, redirect to admin dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $sql = "SELECT user_id, email, password_hash, first_name, last_name, user_type, is_active 
                FROM users WHERE email = ? AND user_type = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['is_active'] == 0) {
                $error = "Your account has been deactivated. Contact support.";
            } elseif (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Update last login
                $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $user['user_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
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
    <title>Admin Login - UniformMarket</title>
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
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
        .login-container { max-width: 450px; margin: 0 auto; padding: 4rem 0; }
        .login-card { background: white; border-radius: 32px; padding: 2.5rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1); border: 1px solid rgba(0, 0, 0, 0.05); }
        .form-control { border: 1.5px solid #e0d8cc; border-radius: 16px; padding: 0.75rem 1rem; }
        .form-control:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1); outline: none; }
        .form-label { font-weight: 600; margin-bottom: 0.5rem; color: #2c2c2c; }
        .input-group-icon { position: relative; }
        .input-group-icon i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9b8e7a; }
        .input-group-icon .form-control { padding-left: 2.5rem; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        .admin-badge { background: #000; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600; display: inline-block; margin-bottom: 1rem; }
        .main-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            margin-bottom: 0.5rem;
        }
        @media (max-width: 768px) { 
            .login-card { padding: 1.5rem; margin: 1rem; }
            .main-logo { width: 48px; height: 48px; }
        }
    </style>
</head>
<body>

<main>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="text-center mb-4">
                    <img src="../logo.png" alt="Uniformly Logo" class="main-logo" onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'bi bi-shield-lock-fill fs-1\' style=\'color: #000;\'></i>'">
                    <div class="admin-badge"><i class="bi bi-person-badge"></i> Admin Portal</div>
                    <h1 class="h2 fw-bold mt-2">Admin Login</h1>
                    <p class="text-muted">Access the administration dashboard</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert-message">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group-icon">
                            <i class="bi bi-envelope"></i>
                            <input type="email" class="form-control" id="email" name="email" placeholder="admin@uniformmarket.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group-icon">
                            <i class="bi bi-lock"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-black w-100 mb-3">Login to Admin Panel</button>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <p class="mb-0 small text-muted">
                        <i class="bi bi-shield-check"></i> Secure Admin Access<br>
                        <a href="../index.php" class="text-dark">← Back to Main Site</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>