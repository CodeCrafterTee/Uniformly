<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=saved');
    exit();
}

// Get user details from session
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$email = $_SESSION['user_email'];
$user_name = $first_name . ' ' . $last_name;
$user_avatar = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// Get saved items from database - FIXED the alias conflict
$saved_sql = "SELECT sv.saved_id, sv.created_at as saved_date,
              l.listing_id, l.title, l.description, l.category, l.size, l.item_condition, l.price, l.status,
              sc.school_name,
              (SELECT image_url FROM listing_images WHERE listing_id = l.listing_id AND is_primary = 1 LIMIT 1) as primary_image
              FROM saved_items sv
              JOIN listings l ON sv.listing_id = l.listing_id
              JOIN schools sc ON l.school_id = sc.school_id
              WHERE sv.user_id = ?
              ORDER BY sv.created_at DESC";
$saved_stmt = $conn->prepare($saved_sql);
$saved_stmt->bind_param("i", $user_id);
$saved_stmt->execute();
$saved_result = $saved_stmt->get_result();

$saved_items = [];
while ($row = $saved_result->fetch_assoc()) {
    $saved_items[] = $row;
}
$saved_stmt->close();

// Process remove saved item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_saved'])) {
    $listing_id = intval($_POST['listing_id'] ?? 0);
    
    $delete_sql = "DELETE FROM saved_items WHERE user_id = ? AND listing_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $user_id, $listing_id);
    
    if ($delete_stmt->execute()) {
        header("Location: account-saved.php?removed=1");
        exit();
    }
    $delete_stmt->close();
}

// Process buy now
if (isset($_GET['buy']) && is_numeric($_GET['buy'])) {
    $listing_id = intval($_GET['buy']);
    header("Location: buy-now.php?listing_id=" . $listing_id);
    exit();
}

// Check for success messages
$success_message = '';
if (isset($_GET['removed'])) {
    $success_message = "Item removed from saved list successfully!";
}
if (isset($_GET['added'])) {
    $success_message = "Item added to saved list!";
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
    <title>Saved Items - UniformMarket</title>
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
        .saved-item-card { background: white; border-radius: 20px; overflow: hidden; transition: all 0.2s; border: 1px solid rgba(0, 0, 0, 0.05); margin-bottom: 1rem; display: flex; }
        .saved-item-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }
        .saved-image { width: 120px; height: 120px; object-fit: cover; }
        .user-avatar-large { width: 80px; height: 80px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 2rem; margin: 0 auto 1rem; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .status-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.7rem; font-weight: 600; }
        .status-active { background: #2e7d32; color: white; }
        .status-sold { background: #9e9e9e; color: white; }
        @media (max-width: 768px) { 
            .dashboard-sidebar { position: relative; top: 0; margin-bottom: 1.5rem; } 
            .saved-item-card { flex-direction: column; } 
            .saved-image { width: 100%; height: 150px; } 
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
                        <li><a href="account-orders.php"><i class="bi bi-bag-check"></i> My Purchases</a></li>
                        <li><a href="account-saved.php" class="active"><i class="bi bi-heart"></i> Saved Items</a></li>
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
                        <h2 class="fw-bold">Saved Items</h2>
                        <p class="text-muted">Items you've saved for later</p>
                    </div>
                    <a href="shop.php" class="btn btn-outline-black rounded-pill">
                        <i class="bi bi-shop"></i> Browse More
                    </a>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert-message alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <div id="savedContainer">
                    <?php if (empty($saved_items)): ?>
                        <div class="text-center py-5 bg-white rounded-4">
                            <i class="bi bi-heart fs-1 text-muted"></i>
                            <h5 class="mt-3 fw-bold">No saved items yet</h5>
                            <p class="text-muted">Save uniforms you like to come back to them later</p>
                            <a href="shop.php" class="btn btn-black rounded-pill mt-2">Browse Uniforms</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($saved_items as $item): ?>
                            <div class="saved-item-card p-3">
                                <img src="<?php echo htmlspecialchars($item['primary_image'] ?? 'https://placehold.co/300x200/F5EFE0/2c2c2c?text=Uniform'); ?>" class="saved-image rounded-3">
                                <div class="p-3 flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></h6>
                                            <p class="small text-muted mb-1">
                                                <i class="bi bi-building"></i> <?php echo htmlspecialchars($item['school_name']); ?>
                                            </p>
                                            <p class="small text-muted mb-1">
                                                <i class="bi bi-tag"></i> Size: <?php echo htmlspecialchars($item['size']); ?> | Condition: <?php echo ucfirst($item['item_condition']); ?>
                                            </p>
                                            <p class="small text-muted mb-2">
                                                <i class="bi bi-calendar"></i> Saved on: <?php echo date('M d, Y', strtotime($item['saved_date'])); ?>
                                            </p>
                                            <?php if ($item['status'] !== 'active'): ?>
                                                <span class="status-badge status-sold"><?php echo strtoupper($item['status']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <h5 class="fw-bold text-dark">R<?php echo number_format($item['price'], 2); ?></h5>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <?php if ($item['status'] === 'active'): ?>
                                            <a href="account-saved.php?buy=<?php echo $item['listing_id']; ?>" class="btn btn-sm btn-black rounded-pill me-2">
                                                <i class="bi bi-cart"></i> Buy Now
                                            </a>
                                        <?php endif; ?>
                                        <form method="POST" action="account-saved.php" class="d-inline">
                                            <input type="hidden" name="listing_id" value="<?php echo $item['listing_id']; ?>">
                                            <input type="hidden" name="remove_saved" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Remove this item from your saved list?');">
                                                <i class="bi bi-trash"></i> Remove
                                            </button>
                                        </form>
                                        <a href="message-seller.php?listing_id=<?php echo $item['listing_id']; ?>" class="btn btn-sm btn-outline-black rounded-pill ms-2">
                                            <i class="bi bi-chat"></i> Ask Seller
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="row g-3 mt-4">
                    <div class="col-md-6">
                        <div class="bg-white rounded-4 p-3 text-center">
                            <i class="bi bi-search fs-2"></i>
                            <h6 class="fw-bold mt-2">Find More Uniforms</h6>
                            <p class="small text-muted">Discover new listings from your favorite schools</p>
                            <a href="shop-by-school.php" class="btn btn-sm btn-outline-black rounded-pill">Shop by School →</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-white rounded-4 p-3 text-center">
                            <i class="bi bi-bell fs-2"></i>
                            <h6 class="fw-bold mt-2">Get Notifications</h6>
                            <p class="small text-muted">Be the first to know when new uniforms are listed</p>
                            <a href="account-settings.php" class="btn btn-sm btn-outline-black rounded-pill">Manage Alerts →</a>
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