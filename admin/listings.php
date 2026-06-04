<?php
session_start();
require_once '../config/database.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';

$pending_refunds_sql = "SELECT COUNT(*) as count FROM refunds WHERE status = 'pending'";
$pending_refunds_result = $conn->query($pending_refunds_sql);
$pending_refunds_count = $pending_refunds_result->fetch_assoc()['count'];


// Delete listing
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $listing_id = intval($_GET['delete']);
    
    // Get images to delete from server
    $img_sql = "SELECT image_url FROM listing_images WHERE listing_id = ?";
    $img_stmt = $conn->prepare($img_sql);
    $img_stmt->bind_param("i", $listing_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    while ($img = $img_result->fetch_assoc()) {
        $file_path = '../' . $img['image_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    $img_stmt->close();
    
    $delete_sql = "DELETE FROM listings WHERE listing_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $listing_id);
    if ($delete_stmt->execute()) {
        $success_message = "Listing deleted successfully!";
    } else {
        $error_message = "Failed to delete listing.";
    }
    $delete_stmt->close();
}

// Change listing status
if (isset($_GET['status']) && isset($_GET['id'])) {
    $listing_id = intval($_GET['id']);
    $new_status = $_GET['status'];
    $allowed_statuses = ['active', 'pending', 'sold', 'expired'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $update_sql = "UPDATE listings SET status = ? WHERE listing_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_status, $listing_id);
        if ($update_stmt->execute()) {
            $success_message = "Listing status updated to " . ucfirst($new_status) . "!";
        }
        $update_stmt->close();
    }
}

// Get filter parameters
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_conditions = [];
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $where_conditions[] = "l.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(l.title LIKE ? OR s.school_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$listings_sql = "SELECT l.*, s.school_name, 
                 u.first_name, u.last_name, u.email,
                 (SELECT image_url FROM listing_images WHERE listing_id = l.listing_id AND is_primary = 1 LIMIT 1) as primary_image
                 FROM listings l
                 JOIN schools s ON l.school_id = s.school_id
                 JOIN users u ON l.seller_id = u.user_id
                 $where_clause
                 ORDER BY l.created_at DESC";

if (!empty($params)) {
    $listings_stmt = $conn->prepare($listings_sql);
    $listings_stmt->bind_param($types, ...$params);
    $listings_stmt->execute();
    $listings_result = $listings_stmt->get_result();
} else {
    $listings_result = $conn->query($listings_sql);
}

// Get counts for filter badges
$count_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
              FROM listings";
$count_result = $conn->query($count_sql);
$counts = $count_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; }
        .sidebar { background: white; border-radius: 24px; padding: 1.5rem; position: sticky; top: 2rem; }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #4a4a4a; text-decoration: none; border-radius: 16px; transition: all 0.2s; }
        .sidebar-nav a:hover { background: #FEF9E6; color: #000; }
        .sidebar-nav a.active { background: #000; color: white; }
        .admin-avatar { width: 50px; height: 50px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem; margin: 0 auto 1rem; }
        .btn-black { background-color: #000000; color: white; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .content-card { background: white; border-radius: 24px; padding: 1.5rem; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        .listing-image { width: 60px; height: 60px; object-fit: cover; border-radius: 12px; }
        .filter-badge { padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.8rem; }
        .status-active { background: #2e7d32; color: white; }
        .status-sold { background: #9e9e9e; color: white; }
        .status-pending { background: #ff9800; color: white; }
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
                    <li><a href="profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
                    <li><a href="users.php"><i class="bi bi-people"></i> Users</a></li>
                    <li><a href="listings.php" class="active"><i class="bi bi-grid-3x3-gap-fill"></i> Listings</a></li>
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
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">Listing Management</h2>
                        <p class="text-muted">Manage all uniform listings on the platform</p>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert-message alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert-message alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <a href="listings.php?status_filter=all" class="btn btn-outline-black rounded-pill me-2 <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All (<?php echo $counts['total']; ?>)</a>
                            <a href="listings.php?status_filter=active" class="btn btn-outline-black rounded-pill me-2 <?php echo $status_filter == 'active' ? 'active' : ''; ?>">Active (<?php echo $counts['active']; ?>)</a>
                            <a href="listings.php?status_filter=pending" class="btn btn-outline-black rounded-pill me-2 <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending (<?php echo $counts['pending']; ?>)</a>
                            <a href="listings.php?status_filter=sold" class="btn btn-outline-black rounded-pill <?php echo $status_filter == 'sold' ? 'active' : ''; ?>">Sold (<?php echo $counts['sold']; ?>)</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" action="listings.php" class="d-flex gap-2">
                            <input type="hidden" name="status_filter" value="<?php echo $status_filter; ?>">
                            <input type="text" name="search" class="form-control rounded-pill" placeholder="Search by title or school..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-black rounded-pill">Search</button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title / School</th>
                                <th>Seller</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Listed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($listing = $listings_result->fetch_assoc()): ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars($listing['primary_image'] ?? '../images/placeholder.jpg'); ?>" class="listing-image"></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($listing['title']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($listing['school_name']); ?></small>
                                 </td>
                                <td><?php echo htmlspecialchars($listing['first_name'] . ' ' . $listing['last_name']); ?><br><small><?php echo $listing['email']; ?></small></td>
                                <td>R<?php echo number_format($listing['price'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $listing['status']; ?>"><?php echo ucfirst($listing['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($listing['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group-vertical">
                                        <select class="form-select form-select-sm mb-1" onchange="window.location.href='listings.php?status=' + this.value + '&id=<?php echo $listing['listing_id']; ?>&status_filter=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>'">
                                            <option value="">Change Status</option>
                                            <option value="active">Active</option>
                                            <option value="pending">Pending</option>
                                            <option value="sold">Sold</option>
                                            <option value="expired">Expired</option>
                                        </select>
                                        <a href="listings.php?delete=<?php echo $listing['listing_id']; ?>&status_filter=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete this listing?')">Delete</a>
                                    </div>
                                 </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>