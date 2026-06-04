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

// Get pending refunds count for badge
$pending_refunds_sql = "SELECT COUNT(*) as count FROM refunds WHERE status = 'pending'";
$pending_refunds_result = $conn->query($pending_refunds_sql);
$pending_refunds_count = $pending_refunds_result->fetch_assoc()['count'];

// Update transaction status
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $transaction_id = intval($_GET['id']);
    $new_status = $_GET['update_status'];
    $allowed_statuses = ['pending', 'completed', 'cancelled', 'refunded'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $update_sql = "UPDATE transactions SET status = ? WHERE transaction_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_status, $transaction_id);
        if ($update_stmt->execute()) {
            $success_message = "Transaction status updated to " . ucfirst($new_status) . "!";
        }
        $update_stmt->close();
    }
}

// Get filter parameters
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$where_conditions = [];
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(t.transaction_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(t.transaction_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$transactions_sql = "SELECT t.*, 
                    l.title as listing_title,
                    buyer.first_name as buyer_first, buyer.last_name as buyer_last, buyer.email as buyer_email,
                    seller.first_name as seller_first, seller.last_name as seller_last, seller.email as seller_email
                    FROM transactions t
                    JOIN listings l ON t.listing_id = l.listing_id
                    JOIN users buyer ON t.buyer_id = buyer.user_id
                    JOIN users seller ON t.seller_id = seller.user_id
                    $where_clause
                    ORDER BY t.transaction_date DESC";

if (!empty($params)) {
    $transactions_stmt = $conn->prepare($transactions_sql);
    $transactions_stmt->bind_param($types, ...$params);
    $transactions_stmt->execute();
    $transactions_result = $transactions_stmt->get_result();
} else {
    $transactions_result = $conn->query($transactions_sql);
}

// Get summary stats
$summary_sql = "SELECT 
                  COUNT(*) as total,
                  SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
                FROM transactions";
$summary_result = $conn->query($summary_sql);
$summary = $summary_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management - Admin Panel</title>
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
        .content-card { background: white; border-radius: 24px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .stat-card { background: #FEF9E6; border-radius: 16px; padding: 1rem; text-align: center; }
        .status-completed { background: #2e7d32; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; display: inline-block; }
        .status-pending { background: #ff9800; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; display: inline-block; }
        .status-cancelled { background: #c62828; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; display: inline-block; }
        .status-refunded { background: #9e9e9e; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; display: inline-block; }
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
                    <li><a href="listings.php"><i class="bi bi-grid-3x3-gap-fill"></i> Listings</a></li>
                    <li><a href="transactions.php" class="active"><i class="bi bi-currency-rand"></i> Transactions</a></li>
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
            <!-- Summary Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h4 class="fw-bold mb-0"><?php echo $summary['total']; ?></h4>
                        <small class="text-muted">Total Transactions</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h4 class="fw-bold mb-0">R<?php echo number_format($summary['total_revenue'], 0); ?></h4>
                        <small class="text-muted">Total Revenue</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h4 class="fw-bold mb-0"><?php echo $summary['completed_count']; ?></h4>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h4 class="fw-bold mb-0"><?php echo $summary['pending_count']; ?></h4>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">Transaction Management</h2>
                        <p class="text-muted">View and manage all platform transactions</p>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert-message alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <form method="GET" action="transactions.php" class="d-flex gap-2">
                            <select name="status_filter" class="form-select rounded-pill" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="refunded" <?php echo $status_filter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-8">
                        <form method="GET" action="transactions.php" class="d-flex gap-2">
                            <input type="hidden" name="status_filter" value="<?php echo $status_filter; ?>">
                            <input type="date" name="date_from" class="form-control rounded-pill" value="<?php echo $date_from; ?>" placeholder="From">
                            <input type="date" name="date_to" class="form-control rounded-pill" value="<?php echo $date_to; ?>" placeholder="To">
                            <button type="submit" class="btn btn-black rounded-pill">Filter</button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item</th>
                                <th>Buyer</th>
                                <th>Seller</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($trans = $transactions_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $trans['transaction_id']; ?></td>
                                <td><?php echo htmlspecialchars($trans['listing_title']); ?></td>
                                <td><?php echo htmlspecialchars($trans['buyer_first'] . ' ' . $trans['buyer_last']); ?><br><small><?php echo $trans['buyer_email']; ?></small></td>
                                <td><?php echo htmlspecialchars($trans['seller_first'] . ' ' . $trans['seller_last']); ?><br><small><?php echo $trans['seller_email']; ?></small></td>
                                <td><strong>R<?php echo number_format($trans['amount'], 2); ?></strong></td>
                                <td><span class="status-<?php echo $trans['status']; ?>"><?php echo ucfirst($trans['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($trans['transaction_date'])); ?></td>
                                <td>
                                    <select class="form-select form-select-sm" onchange="window.location.href='transactions.php?update_status=' + this.value + '&id=<?php echo $trans['transaction_id']; ?>&status_filter=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>'">
                                        <option value="">Update</option>
                                        <option value="pending">Pending</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="refunded">Refunded</option>
                                    </select>
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