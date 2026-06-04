<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=orders');
    exit();
}

// Get user details from session
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$email = $_SESSION['user_email'];
$user_name = $first_name . ' ' . $last_name;
$user_avatar = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// Get user's purchases from transactions
$purchases_sql = "SELECT t.*, 
                  l.title as listing_title, l.category, l.size, l.item_condition, l.price as listing_price,
                  s.school_name,
                  u.first_name as seller_first_name, u.last_name as seller_last_name, u.email as seller_email,
                  (SELECT image_url FROM listing_images WHERE listing_id = l.listing_id AND is_primary = 1 LIMIT 1) as item_image
                  FROM transactions t
                  JOIN listings l ON t.listing_id = l.listing_id
                  JOIN schools s ON l.school_id = s.school_id
                  JOIN users u ON l.seller_id = u.user_id
                  WHERE t.buyer_id = ?
                  ORDER BY t.transaction_date DESC";
$purchases_stmt = $conn->prepare($purchases_sql);
$purchases_stmt->bind_param("i", $user_id);
$purchases_stmt->execute();
$purchases_result = $purchases_stmt->get_result();

$purchases = [];
while ($row = $purchases_result->fetch_assoc()) {
    $purchases[] = $row;
}
$purchases_stmt->close();

// Get purchase statistics
$stats = [];

// Total spent
$total_sql = "SELECT SUM(amount) as total FROM transactions WHERE buyer_id = ? AND status = 'completed'";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param("i", $user_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$stats['total_spent'] = $total_result->fetch_assoc()['total'] ?? 0;
$total_stmt->close();

// Completed purchases count
$completed_sql = "SELECT COUNT(*) as count FROM transactions WHERE buyer_id = ? AND status = 'completed'";
$completed_stmt = $conn->prepare($completed_sql);
$completed_stmt->bind_param("i", $user_id);
$completed_stmt->execute();
$completed_result = $completed_stmt->get_result();
$stats['completed_purchases'] = $completed_result->fetch_assoc()['count'] ?? 0;
$completed_stmt->close();

// Pending purchases count
$pending_sql = "SELECT COUNT(*) as count FROM transactions WHERE buyer_id = ? AND status = 'pending'";
$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("i", $user_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$stats['pending_purchases'] = $pending_result->fetch_assoc()['count'] ?? 0;
$pending_stmt->close();

// Get refunded purchases count
$refunded_sql = "SELECT COUNT(*) as count FROM transactions WHERE buyer_id = ? AND status = 'refunded'";
$refunded_stmt = $conn->prepare($refunded_sql);
$refunded_stmt->bind_param("i", $user_id);
$refunded_stmt->execute();
$refunded_result = $refunded_stmt->get_result();
$stats['refunded_purchases'] = $refunded_result->fetch_assoc()['count'] ?? 0;
$refunded_stmt->close();

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

// Process order actions
$success_message = '';
$error_message = '';

// Mark as received
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_received') {
    $transaction_id = intval($_POST['transaction_id'] ?? 0);
    
    $update_sql = "UPDATE transactions SET status = 'completed', completion_date = NOW() WHERE transaction_id = ? AND buyer_id = ? AND status = 'pending'";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $transaction_id, $user_id);
    
    if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
        $success_message = "Order marked as received! Thank you for your purchase.";
        
        // Get listing ID for notification
        $listing_sql = "SELECT listing_id, seller_id, amount FROM transactions WHERE transaction_id = ?";
        $listing_stmt = $conn->prepare($listing_sql);
        $listing_stmt->bind_param("i", $transaction_id);
        $listing_stmt->execute();
        $listing_result = $listing_stmt->get_result();
        $transaction_data = $listing_result->fetch_assoc();
        $listing_stmt->close();
        
        // Notify seller that buyer confirmed receipt
        $notif_sql = "INSERT INTO notifications (user_id, type, title, message, link) 
                      VALUES (?, 'purchase', 'Order Completed by Buyer', 'The buyer has confirmed receipt of the item. Funds will be released to your account.', '/account-orders.php')";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("i", $transaction_data['seller_id']);
        $notif_stmt->execute();
        $notif_stmt->close();
        
        header("Location: account-orders.php?success=1");
        exit();
    } else {
        $error_message = "Failed to update order status.";
    }
    $update_stmt->close();
}

// Check for success messages from redirects
if (isset($_GET['success'])) {
    $success_message = "Order status updated successfully!";
}
if (isset($_GET['refund_requested'])) {
    $success_message = "Refund request submitted successfully! Our team will review your request.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>My Purchases - UniformMarket</title>
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
        .btn-danger-custom { background-color: transparent; border: 1.5px solid #dc3545; color: #dc3545; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; transition: all 0.2s; }
        .btn-danger-custom:hover { background-color: #dc3545; color: white; }
        .dashboard-sidebar { background: white; border-radius: 24px; padding: 1.5rem; position: sticky; top: 2rem; }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #4a4a4a; text-decoration: none; border-radius: 16px; transition: all 0.2s; }
        .sidebar-nav a:hover { background: #FEF9E6; color: #000; }
        .sidebar-nav a.active { background: #000; color: white; }
        .order-card { background: white; border-radius: 20px; padding: 1.5rem; margin-bottom: 1rem; border: 1px solid rgba(0, 0, 0, 0.05); transition: all 0.2s; }
        .order-card:hover { transform: translateX(4px); border-color: rgba(0, 0, 0, 0.15); }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-completed { background: #2e7d32; color: white; }
        .status-pending { background: #ff9800; color: white; }
        .status-cancelled { background: #c62828; color: white; }
        .status-refunded { background: #9e9e9e; color: white; }
        .status-refund_requested { background: #ff9800; color: white; }
        .stat-card { background: white; border-radius: 20px; padding: 1rem; text-align: center; border: 1px solid rgba(0, 0, 0, 0.05); }
        .user-avatar-large { width: 80px; height: 80px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 2rem; margin: 0 auto 1rem; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        .alert-warning { background: #fff3e0; border-left: 4px solid #ff9800; color: #e65100; }
        .order-image { width: 100px; height: 100px; object-fit: cover; border-radius: 12px; }
        .refund-info { background: #fff3e0; border-radius: 12px; padding: 0.5rem 1rem; margin-top: 0.5rem; font-size: 0.8rem; }
        @media (max-width: 768px) { 
            .dashboard-sidebar { position: relative; top: 0; margin-bottom: 1.5rem; }
            .order-image { width: 80px; height: 80px; }
        }
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
                        <li><a href="account-orders.php" class="active"><i class="bi bi-bag-check"></i> My Purchases</a></li>
                        <li><a href="account-saved.php"><i class="bi bi-heart"></i> Saved Items</a></li>
                        <li><a href="account-messages.php"><i class="bi bi-chat-dots"></i> Messages <?php if ($unread_count > 0): ?><span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                        <li><a href="account-settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="my-2"></li>
                        <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">My Purchases</h2>
                        <p class="text-muted">Track your orders and purchases</p>
                    </div>
                    <a href="shop.php" class="btn btn-black rounded-pill"><i class="bi bi-shop"></i> Continue Shopping</a>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-cart-check fs-2 text-dark"></i>
                            <h3 class="fw-bold mb-0 mt-2"><?php echo $stats['completed_purchases']; ?></h3>
                            <p class="text-muted small mb-0">Completed</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-clock-history fs-2 text-dark"></i>
                            <h3 class="fw-bold mb-0 mt-2"><?php echo $stats['pending_purchases']; ?></h3>
                            <p class="text-muted small mb-0">Pending</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-arrow-repeat fs-2 text-dark"></i>
                            <h3 class="fw-bold mb-0 mt-2"><?php echo $stats['refunded_purchases']; ?></h3>
                            <p class="text-muted small mb-0">Refunded</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-currency-rand fs-2 text-dark"></i>
                            <h3 class="fw-bold mb-0 mt-2">R<?php echo number_format($stats['total_spent'], 2); ?></h3>
                            <p class="text-muted small mb-0">Total Spent</p>
                        </div>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert-message alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert-message alert-error">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Refund Policy Info -->
                <div class="alert-message alert-warning mb-3">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Refund Policy:</strong> You can request a refund within 7 days of receiving your order if the item is damaged, not as described, or didn't arrive. Refunds are processed within 5-7 business days after approval.
                </div>

                <!-- Orders List -->
                <div id="ordersContainer">
                    <?php if (empty($purchases)): ?>
                        <div class="text-center py-5 bg-white rounded-4">
                            <i class="bi bi-bag-x fs-1 text-muted"></i>
                            <h5 class="mt-3 fw-bold">No purchases yet</h5>
                            <p class="text-muted">Start shopping for pre-loved uniforms today!</p>
                            <a href="shop.php" class="btn btn-black rounded-pill mt-2">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($purchases as $order): ?>
                            <div class="order-card">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="<?php echo htmlspecialchars($order['item_image'] ?? 'https://placehold.co/300x200/F5EFE0/2c2c2c?text=Uniform'); ?>" class="order-image w-100" alt="Item image">
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($order['listing_title']); ?></h6>
                                        <p class="small text-muted mb-1">
                                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($order['school_name']); ?><br>
                                            <i class="bi bi-tag"></i> <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($order['category']))); ?> | Size: <?php echo htmlspecialchars($order['size']); ?>
                                        </p>
                                        <p class="small mb-0 text-muted">
                                            <i class="bi bi-person"></i> Seller: <?php echo htmlspecialchars($order['seller_first_name'] . ' ' . $order['seller_last_name']); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-3">
                                        <h5 class="fw-bold text-dark">R<?php echo number_format($order['amount'], 2); ?></h5>
                                        <p class="small text-muted mb-0">
                                            <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($order['transaction_date'])); ?>
                                        </p>
                                        <p class="small mb-0">
                                            <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo strtoupper(str_replace('_', ' ', $order['status'])); ?></span>
                                        </p>
                                        <?php if ($order['status'] === 'refund_requested'): ?>
                                            <div class="refund-info">
                                                <i class="bi bi-clock-history"></i> Refund request pending review
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <form method="POST" action="account-orders.php" class="d-inline">
                                                <input type="hidden" name="transaction_id" value="<?php echo $order['transaction_id']; ?>">
                                                <input type="hidden" name="action" value="mark_received">
                                                <button type="submit" class="btn btn-sm btn-outline-black rounded-pill mb-2 w-100" onclick="return confirm('Have you received this item? Marking as received will complete this order.');">
                                                    <i class="bi bi-check2-circle"></i> Mark Received
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] === 'completed'): ?>
                                            <a href="refund_request.php?transaction_id=<?php echo $order['transaction_id']; ?>&listing_id=<?php echo $order['listing_id']; ?>" class="btn btn-sm btn-danger-custom rounded-pill mb-2 w-100">
                                                <i class="bi bi-arrow-return-left"></i> Request Refund
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="message-seller.php?listing_id=<?php echo $order['listing_id']; ?>&seller_id=<?php echo $order['seller_id']; ?>" class="btn btn-sm btn-outline-black rounded-pill w-100">
                                            <i class="bi bi-chat"></i> Contact Seller
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Quick Shopping Links -->
                <div class="mt-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bg-white rounded-4 p-3 text-center">
                                <i class="bi bi-backpack fs-2"></i>
                                <h6 class="fw-bold mt-2">Shop by School</h6>
                                <p class="small text-muted">Find uniforms from specific schools</p>
                                <a href="shop-by-school.php" class="btn btn-sm btn-outline-black rounded-pill">Browse Schools →</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-white rounded-4 p-3 text-center">
                                <i class="bi bi-tag fs-2"></i>
                                <h6 class="fw-bold mt-2">Latest Listings</h6>
                                <p class="small text-muted">See what's new in the marketplace</p>
                                <a href="shop.php?sort=newest" class="btn btn-sm btn-outline-black rounded-pill">View New Arrivals →</a>
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
</body>
</html>