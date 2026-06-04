<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=settings');
    exit();
}

// Get user details from session
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$email = $_SESSION['user_email'];
$user_name = $first_name . ' ' . $last_name;
$user_avatar = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// Get user preferences from database
$pref_sql = "SELECT email_notifications, marketing_emails, message_notifications 
             FROM user_preferences WHERE user_id = ?";
$pref_stmt = $conn->prepare($pref_sql);
$pref_stmt->bind_param("i", $user_id);
$pref_stmt->execute();
$pref_result = $pref_stmt->get_result();
$preferences = $pref_result->fetch_assoc();
$pref_stmt->close();

// Default preferences if not set
$email_notifications = $preferences['email_notifications'] ?? 1;
$marketing_emails = $preferences['marketing_emails'] ?? 0;
$message_notifications = $preferences['message_notifications'] ?? 1;

// Get saved payment methods
$payment_sql = "SELECT payment_method_id, card_last4, card_type, expiry_date, cardholder_name, is_default 
                FROM payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC";
$payment_stmt = $conn->prepare($payment_sql);
$payment_stmt->bind_param("i", $user_id);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();
$payment_methods = [];
while ($row = $payment_result->fetch_assoc()) {
    $payment_methods[] = $row;
}
$payment_stmt->close();

// Process settings updates
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Update Preferences
    if (isset($_POST['update_preferences'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $marketing_emails = isset($_POST['marketing_emails']) ? 1 : 0;
        $message_notifications = isset($_POST['message_notifications']) ? 1 : 0;
        
        // Check if preferences exist
        $check_sql = "SELECT preference_id FROM user_preferences WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            // Update existing
            $update_sql = "UPDATE user_preferences SET email_notifications = ?, marketing_emails = ?, message_notifications = ? 
                           WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("iiii", $email_notifications, $marketing_emails, $message_notifications, $user_id);
        } else {
            // Insert new
            $update_sql = "INSERT INTO user_preferences (user_id, email_notifications, marketing_emails, message_notifications) 
                           VALUES (?, ?, ?, ?)";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("iiii", $user_id, $email_notifications, $marketing_emails, $message_notifications);
        }
        
        if ($update_stmt->execute()) {
            $success_message = "Preferences updated successfully!";
        } else {
            $error_message = "Failed to update preferences.";
        }
        $update_stmt->close();
        $check_stmt->close();
    }
    
    // Add Payment Method
    if (isset($_POST['add_payment'])) {
        $card_number = preg_replace('/[^0-9]/', '', $_POST['card_number'] ?? '');
        $expiry_date = trim($_POST['expiry_date'] ?? '');
        $cvv = trim($_POST['cvv'] ?? '');
        $cardholder_name = trim($_POST['cardholder_name'] ?? '');
        
        $errors = [];
        
        if (strlen($card_number) < 13 || strlen($card_number) > 19) {
            $errors[] = "Please enter a valid card number (13-19 digits).";
        }
        if (empty($expiry_date)) {
            $errors[] = "Expiry date is required.";
        }
        if (empty($cvv)) {
            $errors[] = "CVV is required.";
        }
        if (empty($cardholder_name)) {
            $errors[] = "Cardholder name is required.";
        }
        
        // Determine card type
        $card_type = 'unknown';
        if (preg_match('/^4/', $card_number)) $card_type = 'visa';
        elseif (preg_match('/^5[1-5]/', $card_number)) $card_type = 'mastercard';
        elseif (preg_match('/^3[47]/', $card_number)) $card_type = 'amex';
        elseif (preg_match('/^6(?:011|5)/', $card_number)) $card_type = 'discover';
        
        $last4 = substr($card_number, -4);
        
        if (empty($errors)) {
            // Check if this is the first card (make it default)
            $count_sql = "SELECT COUNT(*) as count FROM payment_methods WHERE user_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param("i", $user_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count = $count_result->fetch_assoc()['count'];
            $count_stmt->close();
            
            $is_default = ($count == 0) ? 1 : 0;
            
            $insert_sql = "INSERT INTO payment_methods (user_id, card_last4, card_type, expiry_date, cardholder_name, is_default) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("issssi", $user_id, $last4, $card_type, $expiry_date, $cardholder_name, $is_default);
            
            if ($insert_stmt->execute()) {
                $success_message = "Payment method added successfully!";
                header("Location: account-settings.php?added=1");
                exit();
            } else {
                $error_message = "Failed to add payment method.";
            }
            $insert_stmt->close();
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
    
    // Remove Payment Method
    if (isset($_POST['remove_payment'])) {
        $payment_id = intval($_POST['payment_id'] ?? 0);
        
        $delete_sql = "DELETE FROM payment_methods WHERE payment_method_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $payment_id, $user_id);
        
        if ($delete_stmt->execute()) {
            $success_message = "Payment method removed successfully!";
            header("Location: account-settings.php?removed=1");
            exit();
        }
        $delete_stmt->close();
    }
    
    // Set Default Payment Method
    if (isset($_POST['set_default_payment'])) {
        $payment_id = intval($_POST['payment_id'] ?? 0);
        
        // Reset all to non-default
        $reset_sql = "UPDATE payment_methods SET is_default = 0 WHERE user_id = ?";
        $reset_stmt = $conn->prepare($reset_sql);
        $reset_stmt->bind_param("i", $user_id);
        $reset_stmt->execute();
        $reset_stmt->close();
        
        // Set new default
        $update_sql = "UPDATE payment_methods SET is_default = 1 WHERE payment_method_id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $payment_id, $user_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Default payment method updated!";
            header("Location: account-settings.php?default=1");
            exit();
        }
        $update_stmt->close();
    }
    
    // Delete Account
    if (isset($_POST['delete_account'])) {
        $confirm_text = $_POST['confirm_delete'] ?? '';
        
        if ($confirm_text === 'DELETE') {
            // Delete user data (cascade will handle related tables)
            $delete_sql = "DELETE FROM users WHERE user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $user_id);
            
            if ($delete_stmt->execute()) {
                session_destroy();
                $success_message = "Account deleted successfully. Redirecting...";
                header("refresh:2;url=index.php");
                exit();
            } else {
                $error_message = "Failed to delete account. Please try again.";
            }
            $delete_stmt->close();
        } else {
            $error_message = "Please type 'DELETE' to confirm account deletion.";
        }
    }
}

// Check for success messages from redirects
if (isset($_GET['added'])) {
    $success_message = "Payment method added successfully!";
}
if (isset($_GET['removed'])) {
    $success_message = "Payment method removed successfully!";
}
if (isset($_GET['default'])) {
    $success_message = "Default payment method updated!";
}

// Get unread messages count
$unread_sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['count'] ?? 0;
$unread_stmt->close();

// Get member since date
$member_since_sql = "SELECT created_at FROM users WHERE user_id = ?";
$member_stmt = $conn->prepare($member_since_sql);
$member_stmt->bind_param("i", $user_id);
$member_stmt->execute();
$member_result = $member_stmt->get_result();
$member_data = $member_result->fetch_assoc();
$member_since = date('F Y', strtotime($member_data['created_at'] ?? 'now'));
$member_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Settings - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
        .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.6rem 1.5rem; border-radius: 40px; transition: all 0.25s ease; }
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; transition: all 0.2s; }
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .btn-danger-custom { background-color: transparent; border: 1.5px solid #dc3545; color: #dc3545; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; transition: all 0.2s; }
        .btn-danger-custom:hover { background-color: #dc3545; color: white; }
        .dashboard-sidebar { background: white; border-radius: 24px; padding: 1.5rem; position: sticky; top: 2rem; }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #4a4a4a; text-decoration: none; border-radius: 16px; transition: all 0.2s; }
        .sidebar-nav a:hover { background: #FEF9E6; color: #000; }
        .sidebar-nav a.active { background: #000; color: white; }
        .settings-card { background: white; border-radius: 24px; padding: 2rem; border: 1px solid rgba(0, 0, 0, 0.05); margin-bottom: 1.5rem; }
        .form-control, .form-select { border: 1.5px solid #e0d8cc; border-radius: 16px; padding: 0.75rem 1rem; transition: all 0.2s; }
        .form-control:focus, .form-select:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1); outline: none; }
        .toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: 0.3s; border-radius: 34px; }
        .toggle-slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: 0.3s; border-radius: 50%; }
        input:checked + .toggle-slider { background-color: #000; }
        input:checked + .toggle-slider:before { transform: translateX(26px); }
        .user-avatar-large { width: 80px; height: 80px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 2rem; margin: 0 auto 1rem; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        .payment-card { background: #FEF9E6; border-radius: 16px; padding: 1rem; margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e0d8cc; }
        @media (max-width: 768px) { .dashboard-sidebar { position: relative; top: 0; margin-bottom: 1.5rem; } .settings-card { padding: 1.5rem; } }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container my-5">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="dashboard-sidebar">
                    <div class="text-center mb-4">
                        <div class="user-avatar-large mx-auto mb-3"><?php echo $user_avatar; ?></div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user_name); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($email); ?></p>
                        <span class="badge bg-dark rounded-pill">Member since <?php echo $member_since; ?></span>
                    </div>
                    <ul class="sidebar-nav">
                        <li><a href="index.php"><i class="bi bi-house"></i> Home</a></li>
                        <li><a href="account-dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li><a href="account-profile.php"><i class="bi bi-person"></i> My Profile</a></li>
                        <li><a href="account-listings.php"><i class="bi bi-grid-3x3-gap-fill"></i> My Listings</a></li>
                        <li><a href="account-orders.php"><i class="bi bi-bag-check"></i> My Purchases</a></li>
                        <li><a href="account-saved.php"><i class="bi bi-heart"></i> Saved Items</a></li>
                        <li><a href="account-messages.php"><i class="bi bi-chat-dots"></i> Messages <?php if ($unread_count > 0): ?><span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                        <li><a href="account-settings.php" class="active"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="my-2"></li>
                        <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="settings-card">
                    <h2 class="fw-bold mb-4">Settings</h2>
                    
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

                    <!-- Notification Preferences -->
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="fw-bold mb-3"><i class="bi bi-bell me-2"></i> Notifications</h5>
                        <form method="POST" action="account-settings.php">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <p class="mb-0 fw-semibold">Email Notifications</p>
                                    <small class="text-muted">Receive updates about your listings and messages</small>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="email_notifications" <?php echo $email_notifications ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <p class="mb-0 fw-semibold">Marketing Emails</p>
                                    <small class="text-muted">Receive tips, offers, and community updates</small>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="marketing_emails" <?php echo $marketing_emails ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <p class="mb-0 fw-semibold">Message Notifications</p>
                                    <small class="text-muted">Get notified when you receive new messages</small>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="message_notifications" <?php echo $message_notifications ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <input type="hidden" name="update_preferences" value="1">
                            <button type="submit" class="btn btn-outline-black rounded-pill mt-2">Save Notification Settings</button>
                        </form>
                    </div>

                    <!-- Payment Methods -->
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="fw-bold mb-3"><i class="bi bi-credit-card me-2"></i> Payment Methods</h5>
                        <div id="savedCards">
                            <?php if (empty($payment_methods)): ?>
                                <p class="text-muted small">No saved payment methods</p>
                            <?php else: ?>
                                <?php foreach ($payment_methods as $card): ?>
                                    <div class="payment-card">
                                        <div>
                                            <i class="bi bi-credit-card me-2"></i>
                                            <strong><?php echo strtoupper($card['card_type']); ?></strong> •••• <?php echo $card['card_last4']; ?>
                                            <br><small><?php echo htmlspecialchars($card['cardholder_name']); ?> | Expires: <?php echo $card['expiry_date']; ?></small>
                                            <?php if ($card['is_default']): ?>
                                                <span class="badge bg-dark ms-2">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if (!$card['is_default']): ?>
                                                <form method="POST" action="account-settings.php" class="d-inline">
                                                    <input type="hidden" name="payment_id" value="<?php echo $card['payment_method_id']; ?>">
                                                    <input type="hidden" name="set_default_payment" value="1">
                                                    <button type="submit" class="btn btn-sm btn-outline-black rounded-pill">Set Default</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" action="account-settings.php" class="d-inline">
                                                <input type="hidden" name="payment_id" value="<?php echo $card['payment_method_id']; ?>">
                                                <input type="hidden" name="remove_payment" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Remove this payment method?');">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-outline-black rounded-pill mt-2" data-bs-toggle="modal" data-bs-target="#addCardModal">
                            <i class="bi bi-plus-lg"></i> Add Payment Method
                        </button>
                    </div>

                    <!-- Delete Account -->
                    <div class="mb-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-trash3 me-2"></i> Account Management</h5>
                        <div class="bg-light rounded-3 p-3">
                            <p class="mb-2 fw-semibold text-danger">Delete Account</p>
                            <p class="small text-muted">Permanently delete your account and all associated data. This action cannot be undone.</p>
                            <button class="btn btn-danger-custom" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                <i class="bi bi-exclamation-triangle me-2"></i> Delete My Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add Card Modal -->
<div class="modal fade" id="addCardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Add Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="account-settings.php">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Card Number</label>
                        <input type="text" class="form-control" name="card_number" placeholder="**** **** **** ****" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Expiry Date</label>
                            <input type="text" class="form-control" name="expiry_date" placeholder="MM/YY" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">CVV</label>
                            <input type="text" class="form-control" name="cvv" placeholder="***" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cardholder Name</label>
                        <input type="text" class="form-control" name="cardholder_name" placeholder="Thabo Nzo" required>
                    </div>
                    <input type="hidden" name="add_payment" value="1">
                    <button type="submit" class="btn btn-black w-100 rounded-pill">Add Card</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger">Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete your account? This action is <strong>permanent</strong> and cannot be undone.</p>
                <p>All your data will be removed including:</p>
                <ul>
                    <li>Your profile information</li>
                    <li>All listings you've created</li>
                    <li>Purchase history</li>
                    <li>Saved items</li>
                    <li>Messages</li>
                </ul>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Type "DELETE" to confirm</label>
                    <input type="text" class="form-control" id="confirmDelete" placeholder="DELETE">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-black rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-pill" id="confirmDeleteBtn">Delete Account</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Delete account confirmation
    document.getElementById('confirmDeleteBtn')?.addEventListener('click', function() {
        const confirmText = document.getElementById('confirmDelete').value;
        if (confirmText === 'DELETE') {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'account-settings.php';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_account';
            input.value = '1';
            const confirmInput = document.createElement('input');
            confirmInput.type = 'hidden';
            confirmInput.name = 'confirm_delete';
            confirmInput.value = 'DELETE';
            form.appendChild(input);
            form.appendChild(confirmInput);
            document.body.appendChild(form);
            form.submit();
        } else {
            alert('Please type "DELETE" to confirm account deletion');
        }
    });
</script>
</body>
</html>