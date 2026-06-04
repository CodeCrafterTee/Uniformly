<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=profile');
    exit();
}

// Get user details from session
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$email = $_SESSION['user_email'];
$user_name = $first_name . ' ' . $last_name;
$user_avatar = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// Get additional user data from database
$user_sql = "SELECT phone, created_at FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

$phone = $user_data['phone'] ?? '';
$member_since = date('F Y', strtotime($user_data['created_at'] ?? 'now'));

// Get user's address/location
$address_sql = "SELECT suburb, city, postal_code FROM addresses WHERE user_id = ? AND is_default = 1 LIMIT 1";
$address_stmt = $conn->prepare($address_sql);
$address_stmt->bind_param("i", $user_id);
$address_stmt->execute();
$address_result = $address_stmt->get_result();
$address = $address_result->fetch_assoc();
$address_stmt->close();

$location = '';
if ($address) {
    $location_parts = [];
    if (!empty($address['suburb'])) $location_parts[] = $address['suburb'];
    if (!empty($address['city'])) $location_parts[] = $address['city'];
    if (!empty($address['postal_code'])) $location_parts[] = $address['postal_code'];
    $location = implode(', ', $location_parts);
}

// Get user statistics
$stats = [];

// Get listings count
$listings_sql = "SELECT COUNT(*) as count FROM listings WHERE seller_id = ?";
$listings_stmt = $conn->prepare($listings_sql);
$listings_stmt->bind_param("i", $user_id);
$listings_stmt->execute();
$listings_result = $listings_stmt->get_result();
$stats['listings_count'] = $listings_result->fetch_assoc()['count'] ?? 0;
$listings_stmt->close();

// Get reviews count and average rating
$reviews_sql = "SELECT COUNT(*) as count, AVG(rating) as avg_rating FROM reviews WHERE reviewee_id = ?";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $user_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
$reviews_data = $reviews_result->fetch_assoc();
$stats['reviews_count'] = $reviews_data['count'] ?? 0;
$stats['avg_rating'] = $reviews_data['avg_rating'] ? number_format($reviews_data['avg_rating'], 1) : '0.0';
$reviews_stmt->close();

// Process profile update
$success_message = '';
$error_message = '';
$password_success = '';
$password_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $new_first_name = trim($_POST['first_name'] ?? '');
        $new_last_name = trim($_POST['last_name'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_phone = trim($_POST['phone'] ?? '');
        $new_location = trim($_POST['location'] ?? '');
        
        $errors = [];
        
        // Validate
        if (empty($new_first_name)) {
            $errors[] = "First name is required";
        }
        if (empty($new_last_name)) {
            $errors[] = "Last name is required";
        }
        if (empty($new_email)) {
            $errors[] = "Email address is required";
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address";
        }
        
        // Check if email is being changed and already exists
        if ($new_email !== $email) {
            $check_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $new_email, $user_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0) {
                $errors[] = "This email is already registered to another account.";
            }
            $check_stmt->close();
        }
        
        // Format phone number for South Africa
        if (!empty($new_phone)) {
            // Remove any non-numeric characters
            $new_phone = preg_replace('/[^0-9]/', '', $new_phone);
            // Check if it's a valid SA number
            if (strlen($new_phone) == 9) {
                $new_phone = '+27' . $new_phone;
            } elseif (strlen($new_phone) == 10 && substr($new_phone, 0, 1) == '0') {
                $new_phone = '+27' . substr($new_phone, 1);
            } elseif (strlen($new_phone) == 11 && substr($new_phone, 0, 2) == '27') {
                $new_phone = '+' . $new_phone;
            } elseif (strlen($new_phone) == 12 && substr($new_phone, 0, 3) == '027') {
                $new_phone = '+27' . substr($new_phone, 3);
            }
        }
        
        if (empty($errors)) {
            // Update users table
            $update_sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssi", $new_first_name, $new_last_name, $new_email, $new_phone, $user_id);
            
            if ($update_stmt->execute()) {
                // Update session variables
                $_SESSION['first_name'] = $new_first_name;
                $_SESSION['last_name'] = $new_last_name;
                $_SESSION['user_email'] = $new_email;
                $_SESSION['user_name'] = $new_first_name . ' ' . $new_last_name;
                
                // Update address
                if (!empty($new_location)) {
                    $location_parts = explode(',', $new_location);
                    $suburb = trim($location_parts[0]);
                    $city = isset($location_parts[1]) ? trim($location_parts[1]) : 'Johannesburg';
                    $postal_code = isset($location_parts[2]) ? trim($location_parts[2]) : '';
                    
                    // Check if address exists
                    $check_addr_sql = "SELECT address_id FROM addresses WHERE user_id = ? AND is_default = 1";
                    $check_addr_stmt = $conn->prepare($check_addr_sql);
                    $check_addr_stmt->bind_param("i", $user_id);
                    $check_addr_stmt->execute();
                    $check_addr_result = $check_addr_stmt->get_result();
                    
                    if ($check_addr_result->num_rows > 0) {
                        // Update existing address
                        $addr_update_sql = "UPDATE addresses SET suburb = ?, city = ?, postal_code = ? WHERE user_id = ? AND is_default = 1";
                        $addr_update_stmt = $conn->prepare($addr_update_sql);
                        $addr_update_stmt->bind_param("sssi", $suburb, $city, $postal_code, $user_id);
                        $addr_update_stmt->execute();
                        $addr_update_stmt->close();
                    } else {
                        // Insert new address
                        $addr_insert_sql = "INSERT INTO addresses (user_id, street_address, suburb, city, province, postal_code, is_default) 
                                           VALUES (?, '', ?, ?, 'Gauteng', ?, 1)";
                        $addr_insert_stmt = $conn->prepare($addr_insert_sql);
                        $addr_insert_stmt->bind_param("isss", $user_id, $suburb, $city, $postal_code);
                        $addr_insert_stmt->execute();
                        $addr_insert_stmt->close();
                    }
                    $check_addr_stmt->close();
                }
                
                $success_message = "Profile updated successfully!";
                
                // Refresh data
                $first_name = $new_first_name;
                $last_name = $new_last_name;
                $email = $new_email;
                $phone = $new_phone;
                $location = $new_location;
                $user_avatar = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
                
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
            $update_stmt->close();
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = "Current password is required";
        }
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation do not match";
        }
        
        if (empty($errors)) {
            // Verify current password
            $pass_sql = "SELECT password FROM users WHERE user_id = ?";
            $pass_stmt = $conn->prepare($pass_sql);
            $pass_stmt->bind_param("i", $user_id);
            $pass_stmt->execute();
            $pass_result = $pass_stmt->get_result();
            $user_pass = $pass_result->fetch_assoc();
            $pass_stmt->close();
            
            if (password_verify($current_password, $user_pass['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pass_sql = "UPDATE users SET password = ? WHERE user_id = ?";
                $update_pass_stmt = $conn->prepare($update_pass_sql);
                $update_pass_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_pass_stmt->execute()) {
                    $password_success = "Password changed successfully!";
                } else {
                    $password_error = "Failed to update password. Please try again.";
                }
                $update_pass_stmt->close();
            } else {
                $password_error = "Current password is incorrect.";
            }
        } else {
            $password_error = implode("<br>", $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>My Profile - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
        .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.6rem 1.5rem; border-radius: 40px; transition: all 0.25s ease; }
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .dashboard-sidebar { background: white; border-radius: 24px; padding: 1.5rem; position: sticky; top: 2rem; }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #4a4a4a; text-decoration: none; border-radius: 16px; transition: all 0.2s; }
        .sidebar-nav a:hover { background: #FEF9E6; color: #000; }
        .sidebar-nav a.active { background: #000; color: white; }
        .profile-card { background: white; border-radius: 24px; padding: 2rem; }
        .form-control { border: 1.5px solid #e0d8cc; border-radius: 16px; padding: 0.75rem 1rem; }
        .form-control:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1); }
        .user-avatar-large { width: 120px; height: 120px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 3rem; margin: 0 auto 1rem; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        @media (max-width: 768px) { .dashboard-sidebar { position: relative; top: 0; margin-bottom: 1.5rem; } }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container my-5">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="dashboard-sidebar">
                    <div class="text-center mb-4">
                        <div class="user-avatar-large mx-auto mb-3"><?php echo $user_avatar; ?></div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user_name); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($email); ?></p>
                    </div>
                    <ul class="sidebar-nav">
                         <li><a href="index.php"><i class="bi bi-house"></i> Home</a></li>
                        <li><a href="account-dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li><a href="account-profile.php" class="active"><i class="bi bi-person"></i> My Profile</a></li>
                        <li><a href="account-listings.php"><i class="bi bi-grid-3x3-gap-fill"></i> My Listings</a></li>
                        <li><a href="account-orders.php"><i class="bi bi-bag-check"></i> My Purchases</a></li>
                        <li><a href="account-saved.php"><i class="bi bi-heart"></i> Saved Items</a></li>
                        <li><a href="account-messages.php"><i class="bi bi-chat-dots"></i> Messages</a></li>
                        <li><a href="account-settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="my-2"></li>
                        <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="profile-card">
                    <h2 class="fw-bold mb-4">Profile Information</h2>
                    
                    <?php if ($success_message): ?>
                        <div class="alert-message alert-success">
                            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert-message alert-error">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="account-profile.php" id="profileForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone Number </label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="+27 12 345 6789" value="<?php echo htmlspecialchars($phone); ?>">
                            <small class="text-muted">Format: 0821234567 or +27821234567</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Location (Suburb, City, Postal Code)</label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Bryanston, Johannesburg, 2191" value="<?php echo htmlspecialchars($location); ?>" required>
                            <small class="text-muted">Helps connect you with local buyers and sellers in Gauteng</small>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="submit" name="update_profile" class="btn btn-black rounded-pill px-4">Save Changes</button>
                            <a href="account-profile.php" class="btn btn-outline-black rounded-pill px-4">Cancel</a>
                        </div>
                    </form>

                    <hr class="my-4">

                    <h5 class="fw-bold mb-3">Change Password</h5>
                    
                    <?php if ($password_success): ?>
                        <div class="alert-message alert-success">
                            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($password_success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($password_error): ?>
                        <div class="alert-message alert-error">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $password_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="account-profile.php" id="passwordForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                            <small class="text-muted">Password must be at least 6 characters long</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-outline-black rounded-pill px-4">Change Password</button>
                    </form>

                    <hr class="my-4">

                    <h5 class="fw-bold mb-3">Account Statistics</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <small class="text-muted">Member Since</small>
                                <p class="fw-bold mb-0"><?php echo $member_since; ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <small class="text-muted">Listings Posted</small>
                                <p class="fw-bold mb-0"><?php echo $stats['listings_count']; ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <small class="text-muted">Reviews Received</small>
                                <p class="fw-bold mb-0"><?php echo $stats['reviews_count']; ?></p>
                                <?php if ($stats['avg_rating'] > 0): ?>
                                    <small class="text-muted">★ <?php echo $stats['avg_rating']; ?> average</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Phone number formatting hint
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0 && value.length <= 9) {
                // Local format
                this.style.borderColor = '#e0d8cc';
            } else if (value.length === 10 && value.startsWith('0')) {
                this.style.borderColor = '#2e7d32';
            } else if (value.length === 11 && value.startsWith('27')) {
                this.style.borderColor = '#2e7d32';
            } else if (value.length === 12 && value.startsWith('027')) {
                this.style.borderColor = '#2e7d32';
            } else if (value.length > 0) {
                this.style.borderColor = '#ff9800';
            }
        });
    }
</script>
</body>
</html>