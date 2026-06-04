<?php
session_start();
require_once 'config/database.php'; // Changed from '../config/database.php'

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get transaction ID from URL if passed
$preselected_transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;
$preselected_listing_id = isset($_GET['listing_id']) ? intval($_GET['listing_id']) : 0;
$preselected_amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
$preselected_seller_id = isset($_GET['seller_id']) ? intval($_GET['seller_id']) : 0;

// Get completed transactions for this user
$transactions_sql = "SELECT t.transaction_id, t.amount, t.status, t.transaction_date,
                     l.listing_id, l.title, l.seller_id,
                     u.first_name as seller_first, u.last_name as seller_last
                     FROM transactions t
                     JOIN listings l ON t.listing_id = l.listing_id
                     JOIN users u ON l.seller_id = u.user_id
                     WHERE t.buyer_id = ? AND t.status = 'completed'
                     AND NOT EXISTS (SELECT 1 FROM refunds r WHERE r.transaction_id = t.transaction_id AND r.status != 'rejected')
                     ORDER BY t.transaction_date DESC";
$transactions_stmt = $conn->prepare($transactions_sql);
$transactions_stmt->bind_param("i", $user_id);
$transactions_stmt->execute();
$transactions = $transactions_stmt->get_result();

// Check if user has existing pending refund
$pending_sql = "SELECT COUNT(*) as count FROM refunds WHERE user_id = ? AND status = 'pending'";
$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("i", $user_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$has_pending = $pending_result->fetch_assoc()['count'] > 0;
$pending_stmt->close();

// Handle refund request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_refund'])) {
    $transaction_id = intval($_POST['transaction_id'] ?? 0);
    $listing_id = intval($_POST['listing_id'] ?? 0);
    $seller_id = intval($_POST['seller_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate
    $errors = [];
    if ($transaction_id <= 0) {
        $errors[] = "Please select a valid transaction.";
    }
    if ($listing_id <= 0) {
        $errors[] = "Invalid listing selected.";
    }
    if ($seller_id <= 0) {
        $errors[] = "Invalid seller information.";
    }
    if ($amount <= 0) {
        $errors[] = "Invalid amount.";
    }
    if (empty($reason)) {
        $errors[] = "Please select a reason for refund.";
    }
    if (empty($message)) {
        $errors[] = "Please provide details about your refund request.";
    }
    
    // Verify transaction belongs to user
    if (empty($errors)) {
        $verify_sql = "SELECT transaction_id FROM transactions WHERE transaction_id = ? AND buyer_id = ? AND status = 'completed'";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("ii", $transaction_id, $user_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 0) {
            $errors[] = "Invalid transaction selected.";
        }
        $verify_stmt->close();
    }
    
    // Handle file upload
    $image_path = null;
    if (isset($_FILES['refund_image']) && $_FILES['refund_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['refund_image']['type'], $allowed)) {
            $errors[] = "Only JPG, PNG, and WebP images are allowed.";
        }
        if ($_FILES['refund_image']['size'] > $max_size) {
            $errors[] = "Image must be less than 5MB.";
        }
        
        if (empty($errors)) {
            $upload_dir = 'uploads/refunds/'; // Changed from '../uploads/refunds/'
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $extension = pathinfo($_FILES['refund_image']['name'], PATHINFO_EXTENSION);
            $filename = 'refund_' . $user_id . '_' . time() . '.' . $extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['refund_image']['tmp_name'], $upload_path)) {
                $image_path = 'uploads/refunds/' . $filename;
            } else {
                $errors[] = "Failed to upload image. Please check folder permissions.";
            }
        }
    }
    
    // Check for duplicate pending refund
    if (empty($errors)) {
        $dup_check_sql = "SELECT refund_id FROM refunds WHERE transaction_id = ? AND status = 'pending'";
        $dup_check_stmt = $conn->prepare($dup_check_sql);
        $dup_check_stmt->bind_param("i", $transaction_id);
        $dup_check_stmt->execute();
        $dup_check_result = $dup_check_stmt->get_result();
        
        if ($dup_check_result->num_rows > 0) {
            $errors[] = "A refund request for this transaction is already pending.";
        }
        $dup_check_stmt->close();
    }
    
    if (empty($errors)) {
        $insert_sql = "INSERT INTO refunds (transaction_id, listing_id, user_id, seller_id, amount, reason, message, image_path, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iiiidsss", $transaction_id, $listing_id, $user_id, $seller_id, $amount, $reason, $message, $image_path);
        
        if ($insert_stmt->execute()) {
            $success_message = "Your refund request has been submitted successfully. The admin will review it shortly.";
            // Clear form data by redirecting to prevent resubmission
            header("Location: refund_request.php?success=1");
            exit();
        } else {
            $error_message = "Failed to submit refund request. Please try again. Error: " . $conn->error;
        }
        $insert_stmt->close();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Check for success parameter
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Your refund request has been submitted successfully. The admin will review it shortly.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Refund - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .content-card { background: white; border-radius: 24px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .btn-black { background: #000; color: white; border-radius: 40px; padding: 0.6rem 1.5rem; border: none; transition: all 0.2s; }
        .btn-black:hover { background: #333; transform: translateY(-1px); }
        .btn-outline-black { border: 1.5px solid #000; color: #000; border-radius: 40px; padding: 0.5rem 1.5rem; background: transparent; transition: all 0.2s; }
        .btn-outline-black:hover { background: #000; color: white; }
        .form-control, .form-select { border-radius: 16px; border: 1.5px solid #e0d8cc; padding: 0.75rem 1rem; }
        .form-control:focus, .form-select:focus { border-color: #000; box-shadow: 0 0 0 3px rgba(0,0,0,0.1); }
        .alert-message { padding: 1rem; border-radius: 16px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Uniformly</a>
        <div class="ms-auto">
            <a href="account-dashboard.php" class="btn btn-outline-black btn-sm me-2">Dashboard</a>
            <a href="logout.php" class="btn btn-black btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="content-card">
                <h2 class="fw-bold mb-2"><i class="bi bi-cash-stack"></i> Request a Refund</h2>
                <p class="text-muted mb-4">Submit a refund request for a completed purchase</p>
                
                <?php if ($has_pending): ?>
                    <div class="alert-message alert-warning">
                        <i class="bi bi-clock-history me-2"></i> 
                        <strong>Pending Request:</strong> You already have a pending refund request. Please wait for admin approval before submitting another.
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert-message alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i> 
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert-message alert-error">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!$has_pending && $transactions->num_rows > 0): ?>
                    <form method="POST" action="refund_request.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Transaction</label>
                            <select class="form-select" name="transaction_id" id="transactionSelect" required>
                                <option value="">Choose a transaction...</option>
                                <?php 
                                $transactions->data_seek(0); // Reset pointer
                                while ($trans = $transactions->fetch_assoc()): 
                                    $selected = ($preselected_transaction_id == $trans['transaction_id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $trans['transaction_id']; ?>" 
                                            data-listing-id="<?php echo $trans['listing_id']; ?>"
                                            data-seller-id="<?php echo $trans['seller_id']; ?>"
                                            data-seller-name="<?php echo htmlspecialchars($trans['seller_first'] . ' ' . $trans['seller_last']); ?>"
                                            data-amount="<?php echo $trans['amount']; ?>"
                                            data-title="<?php echo htmlspecialchars($trans['title']); ?>"
                                            <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($trans['title']); ?> - R<?php echo number_format($trans['amount'], 2); ?> (<?php echo date('M d, Y', strtotime($trans['transaction_date'])); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div id="transactionDetails" class="mb-3" style="display: <?php echo ($preselected_transaction_id > 0) ? 'block' : 'none'; ?>;">
                            <div class="bg-light rounded-3 p-3 mb-3">
                                <p class="mb-1"><strong>Item:</strong> <span id="itemTitle"></span></p>
                                <p class="mb-1"><strong>Seller:</strong> <span id="sellerName"></span></p>
                                <p class="mb-0"><strong>Amount:</strong> R<span id="amount"></span></p>
                            </div>
                            <input type="hidden" name="listing_id" id="listingId" value="<?php echo $preselected_listing_id; ?>">
                            <input type="hidden" name="seller_id" id="sellerId" value="<?php echo $preselected_seller_id; ?>">
                            <input type="hidden" name="amount" id="amountInput" value="<?php echo $preselected_amount; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason for Refund</label>
                            <select class="form-select" name="reason" required>
                                <option value="">Select a reason...</option>
                                <option value="item_not_as_described">Item not as described</option>
                                <option value="damaged">Item arrived damaged</option>
                                <option value="wrong_item">Wrong item received</option>
                                <option value="never_received">Never received item</option>
                                <option value="defective">Item is defective</option>
                                <option value="other">Other reason</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Detailed Message</label>
                            <textarea class="form-control" name="message" rows="5" placeholder="Please provide detailed information about why you need a refund. Include any relevant details about the condition, delivery, or communication with the seller." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Supporting Image (Optional)</label>
                            <input type="file" class="form-control" name="refund_image" accept="image/jpeg,image/png,image/jpg,image/webp">
                            <small class="text-muted">Upload a photo to support your claim (Max 5MB). Recommended for damaged or incorrect items.</small>
                        </div>
                        
                        <button type="submit" name="submit_refund" class="btn btn-black w-100">Submit Refund Request</button>
                    </form>
                <?php elseif (!$has_pending && $transactions->num_rows == 0): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">You don't have any completed transactions eligible for refund.</p>
                        <a href="shop.php" class="btn btn-black">Browse Marketplace</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const transactionSelect = document.getElementById('transactionSelect');
        const transactionDetails = document.getElementById('transactionDetails');
        
        if (transactionSelect) {
            // If there's a preselected value, trigger the change event to populate details
            if (transactionSelect.value) {
                const event = new Event('change');
                transactionSelect.dispatchEvent(event);
            }
            
            transactionSelect.addEventListener('change', function() {
                const selected = this.options[this.selectedIndex];
                if (this.value && selected.dataset.title) {
                    document.getElementById('itemTitle').textContent = selected.dataset.title || 'N/A';
                    document.getElementById('sellerName').textContent = selected.dataset.sellerName || 'N/A';
                    document.getElementById('amount').textContent = parseFloat(selected.dataset.amount || 0).toFixed(2);
                    document.getElementById('listingId').value = selected.dataset.listingId || '';
                    document.getElementById('sellerId').value = selected.dataset.sellerId || '';
                    document.getElementById('amountInput').value = selected.dataset.amount || 0;
                    transactionDetails.style.display = 'block';
                } else {
                    transactionDetails.style.display = 'none';
                }
            });
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>