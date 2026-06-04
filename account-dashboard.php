<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=dashboard');
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$email = $_SESSION['user_email'];
$user_name = $first_name . ' ' . $last_name;
$user_avatar = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// Get user statistics from database
$stats = [];

// Get active listings count
$listings_sql = "SELECT COUNT(*) as count FROM listings WHERE seller_id = ? AND status = 'active'";
$listings_stmt = $conn->prepare($listings_sql);
$listings_stmt->bind_param("i", $user_id);
$listings_stmt->execute();
$listings_result = $listings_stmt->get_result();
$stats['active_listings'] = $listings_result->fetch_assoc()['count'] ?? 0;
$listings_stmt->close();

// Get items sold count (completed transactions where user is seller)
$sold_sql = "SELECT COUNT(*) as count FROM transactions WHERE seller_id = ? AND status = 'completed'";
$sold_stmt = $conn->prepare($sold_sql);
$sold_stmt->bind_param("i", $user_id);
$sold_stmt->execute();
$sold_result = $sold_stmt->get_result();
$stats['items_sold'] = $sold_result->fetch_assoc()['count'] ?? 0;
$sold_stmt->close();

// Get items purchased count (completed transactions where user is buyer)
$purchased_sql = "SELECT COUNT(*) as count FROM transactions WHERE buyer_id = ? AND status = 'completed'";
$purchased_stmt = $conn->prepare($purchased_sql);
$purchased_stmt->bind_param("i", $user_id);
$purchased_stmt->execute();
$purchased_result = $purchased_stmt->get_result();
$stats['items_purchased'] = $purchased_result->fetch_assoc()['count'] ?? 0;
$purchased_stmt->close();

// Get average rating
$rating_sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE reviewee_id = ?";
$rating_stmt = $conn->prepare($rating_sql);
$rating_stmt->bind_param("i", $user_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result();
$rating_row = $rating_result->fetch_assoc();
$stats['rating'] = $rating_row['avg_rating'] ? number_format($rating_row['avg_rating'], 1) : '0.0';
$rating_stmt->close();

// Get saved items count
$saved_sql = "SELECT COUNT(*) as count FROM saved_items WHERE user_id = ?";
$saved_stmt = $conn->prepare($saved_sql);
$saved_stmt->bind_param("i", $user_id);
$saved_stmt->execute();
$saved_result = $saved_stmt->get_result();
$stats['saved_items'] = $saved_result->fetch_assoc()['count'] ?? 0;
$saved_stmt->close();

// Get recent activity (last 5 notifications)
$activity_sql = "SELECT title, message, link, created_at, type FROM notifications 
                  WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$activity_stmt = $conn->prepare($activity_sql);
$activity_stmt->bind_param("i", $user_id);
$activity_stmt->execute();
$activity_result = $activity_stmt->get_result();
$recent_activities = [];
while ($row = $activity_result->fetch_assoc()) {
    $icon = 'bi-bell';
    switch ($row['type']) {
        case 'message':
            $icon = 'bi-chat-dots';
            break;
        case 'sale':
            $icon = 'bi-cash-stack';
            break;
        case 'purchase':
            $icon = 'bi-bag-check';
            break;
        case 'system':
            $icon = 'bi-gear';
            break;
    }
    $recent_activities[] = [
        'icon' => $icon,
        'title' => $row['title'],
        'description' => $row['message'],
        'time' => date('M d, H:i', strtotime($row['created_at'])),
        'link' => $row['link']
    ];
}
$activity_stmt->close();

// Get unread messages count
$unread_sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['count'] ?? 0;
$unread_stmt->close();

// Get member since date
$member_since = date('F Y', strtotime($_SESSION['created_at'] ?? 'now'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Dashboard - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
        .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.6rem 1.5rem; border-radius: 40px; transition: all 0.25s ease; }
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08); }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .dashboard-sidebar { background: white; border-radius: 24px; padding: 1.5rem; position: sticky; top: 2rem; }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #4a4a4a; text-decoration: none; border-radius: 16px; transition: all 0.2s; }
        .sidebar-nav a:hover { background: #FEF9E6; color: #000; }
        .sidebar-nav a.active { background: #000; color: white; }
        .sidebar-nav a i { width: 1.5rem; font-size: 1.2rem; }
        .stat-card { background: white; border-radius: 20px; padding: 1.5rem; transition: all 0.2s; border: 1px solid rgba(0, 0, 0, 0.05); }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05); }
        .stat-icon { width: 48px; height: 48px; background: #FEF9E6; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .recent-item { background: white; border-radius: 16px; padding: 1rem; margin-bottom: 0.75rem; transition: all 0.2s; border: 1px solid rgba(0, 0, 0, 0.05); }
        .recent-item:hover { transform: translateX(4px); border-color: rgba(0, 0, 0, 0.15); }
        .user-avatar-large { width: 80px; height: 80px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 2rem; margin: 0 auto 1rem; }
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
                        <span class="badge bg-dark rounded-pill">Member since <?php echo $member_since; ?></span>
                    </div>
                    <ul class="sidebar-nav">
                        <li><a href="index.php"><i class="bi bi-house"></i> Home</a></li>
                        <li><a href="account-dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li><a href="account-profile.php"><i class="bi bi-person"></i> My Profile</a></li>
                        <li><a href="account-listings.php"><i class="bi bi-grid-3x3-gap-fill"></i> My Listings</a></li>
                        <li><a href="account-orders.php"><i class="bi bi-bag-check"></i> My Purchases</a></li>
                        <li><a href="account-saved.php"><i class="bi bi-heart"></i> Saved Items</a></li>
                        <li><a href="account-messages.php"><i class="bi bi-chat-dots"></i> Messages <?php if ($unread_count > 0): ?><span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                        <li><a href="account-settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="my-2"></li>
                        <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">Dashboard</h2>
                        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($first_name); ?>! Here's what's happening with your account.</p>
                    </div>
                    <a href="sell-uniform.php" class="btn btn-black rounded-pill"><i class="bi bi-plus-lg"></i> List New Item</a>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon mb-3"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['active_listings']; ?></h3>
                            <p class="text-muted mb-0">Active Listings</p>
                            <a href="account-listings.php" class="small text-dark text-decoration-none">View all →</a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon mb-3"><i class="bi bi-bag-check"></i></div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['items_sold']; ?></h3>
                            <p class="text-muted mb-0">Items Sold</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon mb-3"><i class="bi bi-cart"></i></div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['items_purchased']; ?></h3>
                            <p class="text-muted mb-0">Items Purchased</p>
                            <a href="account-orders.php" class="small text-dark text-decoration-none">View orders →</a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon mb-3"><i class="bi bi-star-fill"></i></div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['rating']; ?></h3>
                            <p class="text-muted mb-0">Seller Rating</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-4 p-4 mb-4">
                    <h5 class="fw-bold mb-3">Recent Activity</h5>
                    <div id="recentActivity">
                        <?php if (empty($recent_activities)): ?>
                            <div class="recent-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-eye me-2"></i>
                                        <strong>No recent activity yet</strong>
                                        <p class="text-muted small mb-0">Your recent listings and purchases will appear here</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="recent-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi <?php echo $activity['icon']; ?> me-2"></i>
                                            <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($activity['description']); ?></p>
                                        </div>
                                        <small class="text-muted"><?php echo $activity['time']; ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="bg-white rounded-4 p-4 text-center">
                            <i class="bi bi-plus-circle fs-1 mb-3 d-block"></i>
                            <h6 class="fw-bold">List Your Uniforms</h6>
                            <p class="small text-muted">Sell outgrown uniforms to other parents</p>
                            <a href="account-listings.php" class="btn btn-outline-black rounded-pill mt-2">Start Selling</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-white rounded-4 p-4 text-center">
                            <i class="bi bi-search fs-1 mb-3 d-block"></i>
                            <h6 class="fw-bold">Find Uniforms</h6>
                            <p class="small text-muted">Shop by school, size, or category</p>
                            <a href="shop.php" class="btn btn-outline-black rounded-pill mt-2">Start Shopping</a>
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