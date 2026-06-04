<?php
session_start();
require_once '../config/database.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

$pending_refunds_sql = "SELECT COUNT(*) as count FROM refunds WHERE status = 'pending'";
$pending_refunds_result = $conn->query($pending_refunds_sql);
$pending_refunds_count = $pending_refunds_result->fetch_assoc()['count'];

// Get admin current info
$admin_sql = "SELECT user_id, email, first_name, last_name, phone, bio, profile_image, created_at, last_login 
              FROM users WHERE user_id = ?";
$admin_stmt = $conn->prepare($admin_sql);
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin = $admin_result->fetch_assoc();
$admin_stmt->close();

// Create upload directory if not exists
$upload_dir = '../uploads/admins/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Update basic info
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        $errors = [];
        
        if (empty($first_name)) {
            $errors[] = "First name is required";
        }
        if (empty($last_name)) {
            $errors[] = "Last name is required";
        }
        
        if (empty($errors)) {
            $update_sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssi", $first_name, $last_name, $phone, $admin_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $success_message = "Profile updated successfully!";
                
                // Refresh admin data
                $admin['first_name'] = $first_name;
                $admin['last_name'] = $last_name;
                $admin['phone'] = $phone;
            } else {
                $error_message = "Failed to update profile.";
            }
            $update_stmt->close();
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
    
    // Upload profile image
    if (isset($_POST['upload_image']) && isset($_FILES['profile_image'])) {
        $file = $_FILES['profile_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            if (!in_array($file['type'], $allowed_types)) {
                $error_message = "Only JPG, PNG, and WebP images are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error_message = "Image size must be less than 2MB.";
            } else {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old image if exists
                    if (!empty($admin['profile_image']) && file_exists('../' . $admin['profile_image'])) {
                        unlink('../' . $admin['profile_image']);
                    }
                    
                    $db_path = 'uploads/admins/' . $new_filename;
                    $update_sql = "UPDATE users SET profile_image = ? WHERE user_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("si", $db_path, $admin_id);
                    
                    if ($update_stmt->execute()) {
                        $success_message = "Profile picture updated!";
                        $admin['profile_image'] = $db_path;
                    } else {
                        $error_message = "Failed to save image path.";
                    }
                    $update_stmt->close();
                } else {
                    $error_message = "Failed to upload image.";
                }
            }
        } else {
            $error_message = "Please select an image to upload.";
        }
    }
    
    // Remove profile image
    if (isset($_POST['remove_image'])) {
        if (!empty($admin['profile_image']) && file_exists('../' . $admin['profile_image'])) {
            unlink('../' . $admin['profile_image']);
        }
        
        $update_sql = "UPDATE users SET profile_image = NULL WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $admin_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Profile picture removed.";
            $admin['profile_image'] = null;
        } else {
            $error_message = "Failed to remove image.";
        }
        $update_stmt->close();
    }
    
    // Change password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        // Verify current password
        $pass_sql = "SELECT password_hash FROM users WHERE user_id = ?";
        $pass_stmt = $conn->prepare($pass_sql);
        $pass_stmt->bind_param("i", $admin_id);
        $pass_stmt->execute();
        $pass_result = $pass_stmt->get_result();
        $user_pass = $pass_result->fetch_assoc();
        $pass_stmt->close();
        
        if (!password_verify($current_password, $user_pass['password_hash'])) {
            $errors[] = "Current password is incorrect.";
        }
        
        if (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters.";
        }
        if (!preg_match('/[A-Z]/', $new_password)) {
            $errors[] = "Password must contain at least one uppercase letter.";
        }
        if (!preg_match('/[0-9]/', $new_password)) {
            $errors[] = "Password must contain at least one number.";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match.";
        }
        
        if (empty($errors)) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_hash, $admin_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Failed to change password.";
            }
            $update_stmt->close();
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; transition: background-color 0.3s; }
        body.dark-mode { background-color: #1a1a1a; color: #f0f0f0; }
        body.dark-mode .sidebar { background: #2c2c2c; color: #f0f0f0; border-color: #404040; }
        body.dark-mode .content-card { background: #2c2c2c; color: #f0f0f0; border-color: #404040; }
        body.dark-mode .form-control, body.dark-mode .form-select { background: #3a3a3a; border-color: #555; color: #f0f0f0; }
        body.dark-mode .btn-outline-black { border-color: #f0f0f0; color: #f0f0f0; }
        body.dark-mode .btn-outline-black:hover { background: #f0f0f0; color: #000; }
        body.dark-mode .text-muted { color: #aaa !important; }
        body.dark-mode hr { border-color: #404040; }
        .sidebar { background: white; border-radius: 24px; padding: 1.5rem; position: sticky; top: 2rem; transition: background 0.3s; }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #4a4a4a; text-decoration: none; border-radius: 16px; transition: all 0.2s; }
        .sidebar-nav a:hover { background: #FEF9E6; color: #000; }
        .sidebar-nav a.active { background: #000; color: white; }
        body.dark-mode .sidebar-nav a { color: #ccc; }
        body.dark-mode .sidebar-nav a:hover { background: #404040; color: #fff; }
        body.dark-mode .sidebar-nav a.active { background: #fff; color: #000; }
        .admin-avatar { width: 50px; height: 50px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem; margin: 0 auto 1rem; }
        .btn-black { background-color: #000000; color: white; font-weight: 500; padding: 0.6rem 1.5rem; border-radius: 40px; transition: all 0.25s ease; }
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; transition: all 0.2s; }
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .content-card { background: white; border-radius: 24px; padding: 1.5rem; margin-bottom: 1.5rem; transition: background 0.3s; border: 1px solid rgba(0, 0, 0, 0.05); }
        .form-control, .form-select { border: 1.5px solid #e0d8cc; border-radius: 16px; padding: 0.75rem 1rem; transition: all 0.2s; }
        .form-control:focus, .form-select:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1); outline: none; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        body.dark-mode .alert-success { background: #1e3a2e; color: #a5d6a5; }
        body.dark-mode .alert-error { background: #3a1e1e; color: #ffaaaa; }
        .profile-image { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #000; }
        @media (max-width: 768px) { .sidebar { position: relative; top: 0; margin-bottom: 1.5rem; } }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 col-md-3 my-4">
            <div class="sidebar">
                <div class="text-center mb-4">
                    <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?></div>
                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h6>
                    <small class="text-muted">Administrator</small>
                </div>
                <ul class="sidebar-nav">
                    <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                     <li><a href="profile.php" class="active"><i class="bi bi-person-circle"></i> Profile</a></li>
                    <li><a href="users.php"><i class="bi bi-people"></i> Users</a></li>
                    <li><a href="listings.php"><i class="bi bi-grid-3x3-gap-fill"></i> Listings</a></li>
                    <li><a href="transactions.php"><i class="bi bi-currency-rand"></i> Transactions</a></li>
                      <li><a href="admin-newsletter.php"><i class="bi bi-envelope"></i> Subscribers</a></li>
                      <li><a href="refunds.php"><i class="bi bi-cash-stack"></i> Refunds 
                        <?php if ($pending_refunds_count > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?php echo $pending_refunds_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="reports.php"><i class="bi bi-file-text"></i> Reports</a></li>
                    <li><hr class="my-2"></li>
                    <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <div class="col-lg-10 col-md-9 my-4">
            <!-- Profile Header -->
            <div class="content-card">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <?php if (!empty($admin['profile_image']) && file_exists('../' . $admin['profile_image'])): ?>
                            <img src="../<?php echo $admin['profile_image']; ?>" class="profile-image" alt="Profile">
                        <?php else: ?>
                            <div class="profile-image bg-dark d-flex align-items-center justify-content-center mx-auto" style="background: #000; color: white; font-size: 3rem;">
                                <?php echo strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-9">
                        <h2 class="fw-bold"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h2>
                        <p class="text-muted mb-1"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($admin['email']); ?></p>
                        <?php if (!empty($admin['phone'])): ?>
                            <p class="text-muted mb-1"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($admin['phone']); ?></p>
                        <?php endif; ?>
                        <p class="text-muted"><i class="bi bi-calendar"></i> Member since <?php echo date('F Y', strtotime($admin['created_at'])); ?></p>
                        <?php if ($admin['last_login']): ?>
                            <p class="small text-muted">Last login: <?php echo date('M d, Y H:i', strtotime($admin['last_login'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert-message alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert-message alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Profile Information -->
            <div class="content-card">
                <h4 class="fw-bold mb-3"><i class="bi bi-person"></i> Profile Information</h4>
                <form method="POST" action="profile.php" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" disabled>
                        <small class="text-muted">Email cannot be changed</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>" placeholder="+27 12 345 6789">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="update_profile" class="btn btn-black rounded-pill">Update Profile</button>
                    </div>
                </form>
            </div>

            <!-- Profile Picture -->
            <div class="content-card">
                <h4 class="fw-bold mb-3"><i class="bi bi-image"></i> Profile Picture</h4>
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <form method="POST" action="profile.php" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" class="form-control" name="profile_image" accept="image/jpeg,image/png,image/jpg,image/webp" required>
                                <small class="text-muted">Max 2MB. Allowed: JPG, PNG, WebP</small>
                            </div>
                            <button type="submit" name="upload_image" class="btn btn-black rounded-pill">Upload Picture</button>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if (!empty($admin['profile_image']) && file_exists('../' . $admin['profile_image'])): ?>
                            <form method="POST" action="profile.php">
                                <button type="submit" name="remove_image" class="btn btn-outline-danger rounded-pill" onclick="return confirm('Remove profile picture?')">Remove Picture</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="content-card">
                <h4 class="fw-bold mb-3"><i class="bi bi-lock"></i> Change Password</h4>
                <form method="POST" action="profile.php">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                        <small class="text-muted">At least 8 characters, 1 uppercase letter, 1 number</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-black rounded-pill">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>