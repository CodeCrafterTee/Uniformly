<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'];

$pending_refunds_sql = "SELECT COUNT(*) as count FROM refunds WHERE status = 'pending'";
$pending_refunds_result = $conn->query($pending_refunds_sql);
$pending_refunds_count = $pending_refunds_result->fetch_assoc()['count'];

// Platform Statistics
$stats = [];

// Total users
$users_sql = "SELECT COUNT(*) as total FROM users WHERE user_type != 'admin'";
$users_result = $conn->query($users_sql);
$stats['total_users'] = $users_result->fetch_assoc()['total'];

// Total listings
$listings_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                 FROM listings";
$listings_result = $conn->query($listings_sql);
$listings_data = $listings_result->fetch_assoc();
$stats['total_listings'] = $listings_data['total'];
$stats['active_listings'] = $listings_data['active'];
$stats['sold_listings'] = $listings_data['sold'];
$stats['pending_listings'] = $listings_data['pending'];

// Total transactions and revenue
$transactions_sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_transactions,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_transactions
                     FROM transactions";
$transactions_result = $conn->query($transactions_sql);
$transactions_data = $transactions_result->fetch_assoc();
$stats['total_transactions'] = $transactions_data['total'];
$stats['revenue'] = $transactions_data['revenue'] ?? 0;
$stats['pending_transactions'] = $transactions_data['pending_transactions'];
$stats['completed_transactions'] = $transactions_data['completed_transactions'];

// Total schools
$schools_sql = "SELECT COUNT(*) as total FROM schools";
$schools_result = $conn->query($schools_sql);
$stats['total_schools'] = $schools_result->fetch_assoc()['total'];

// Initialize filter value (default to 'all')
$user_filter = isset($_GET['user_filter']) ? $_GET['user_filter'] : 'all';

// Recent users (last 5) with filter
$recent_users_sql = "SELECT user_id, first_name, last_name, email, created_at, user_type 
                     FROM users WHERE user_type != 'admin'";
if ($user_filter != 'all') {
    $recent_users_sql .= " AND user_type = '" . $conn->real_escape_string($user_filter) . "'";
}
$recent_users_sql .= " ORDER BY created_at DESC LIMIT 5";
$recent_users = $conn->query($recent_users_sql);

// Recent listings (last 5)
$recent_listings_sql = "SELECT l.listing_id, l.title, l.price, l.status, l.created_at, 
                        s.school_name, u.first_name, u.last_name
                        FROM listings l
                        JOIN schools s ON l.school_id = s.school_id
                        JOIN users u ON l.seller_id = u.user_id
                        ORDER BY l.created_at DESC LIMIT 5";
$recent_listings = $conn->query($recent_listings_sql);

// Recent transactions (last 5)
$recent_transactions_sql = "SELECT t.transaction_id, t.amount, t.status, t.transaction_date,
                            l.title as listing_title,
                            buyer.first_name as buyer_first, buyer.last_name as buyer_last,
                            seller.first_name as seller_first, seller.last_name as seller_last
                            FROM transactions t
                            JOIN listings l ON t.listing_id = l.listing_id
                            JOIN users buyer ON t.buyer_id = buyer.user_id
                            JOIN users seller ON t.seller_id = seller.user_id
                            ORDER BY t.transaction_date DESC LIMIT 5";
$recent_transactions = $conn->query($recent_transactions_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin Dashboard - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
        .sidebar { background: white; border-radius: 24px; padding: 1.5rem; position: sticky; top: 2rem; }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #4a4a4a; text-decoration: none; border-radius: 16px; transition: all 0.2s; }
        .sidebar-nav a:hover { background: #FEF9E6; color: #000; }
        .sidebar-nav a.active { background: #000; color: white; }
        .stat-card { background: white; border-radius: 20px; padding: 1.25rem; transition: all 0.2s; border: 1px solid rgba(0, 0, 0, 0.05); height: 100%; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }
        .stat-icon { width: 48px; height: 48px; background: #FEF9E6; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.6rem 1.5rem; border-radius: 40px; transition: all 0.25s ease; }
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .admin-avatar { width: 50px; height: 50px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem; }
        @media (max-width: 768px) { .sidebar { position: relative; top: 0; margin-bottom: 1.5rem; } }
        .filter-select { border-radius: 40px; padding: 0.5rem 1rem; border: 1.5px solid #dee2e6; background-color: white; font-size: 0.9rem; }
        .filter-select:focus { border-color: #000000; outline: none; box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 col-md-3 my-4">
            <div class="sidebar">
                <div class="text-center mb-4">
                    <div class="admin-avatar mx-auto mb-3"><?php echo strtoupper(substr($admin_name, 0, 2)); ?></div>
                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($admin_name); ?></h6>
                    <small class="text-muted">Administrator</small>
                </div>
                <ul class="sidebar-nav">
                    <li><a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a href="profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
                    <li><a href="users.php"><i class="bi bi-people"></i> Users</a></li>
                    <li><a href="listings.php"><i class="bi bi-grid-3x3-gap-fill"></i> Listings</a></li>
                    <li><a href="transactions.php"><i class="bi bi-currency-rand"></i> Transactions</a></li>
                    <li><a href="refunds.php"><i class="bi bi-cash-stack"></i> Refunds </a></li>
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

        <!-- Main Content -->
        <div class="col-lg-10 col-md-9 my-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="fw-bold">Dashboard</h1>
                    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</p>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-black rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-calendar"></i> Last 30 days
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Last 7 days</a></li>
                        <li><a class="dropdown-item" href="#">Last 30 days</a></li>
                        <li><a class="dropdown-item" href="#">Last 90 days</a></li>
                        <li><a class="dropdown-item" href="#">This year</a></li>
                    </ul>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon mb-2"><i class="bi bi-people"></i></div>
                        <h3 class="fw-bold mb-0"><?php echo $stats['total_users']; ?></h3>
                        <p class="text-muted mb-0">Total Users</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon mb-2"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                        <h3 class="fw-bold mb-0"><?php echo $stats['total_listings']; ?></h3>
                        <p class="text-muted mb-0">Total Listings</p>
                        <small class="text-muted"><?php echo $stats['active_listings']; ?> active</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon mb-2"><i class="bi bi-currency-rand"></i></div>
                        <h3 class="fw-bold mb-0">R<?php echo number_format($stats['revenue'], 0); ?></h3>
                        <p class="text-muted mb-0">Total Revenue</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon mb-2"><i class="bi bi-building"></i></div>
                        <h3 class="fw-bold mb-0"><?php echo $stats['total_schools']; ?></h3>
                        <p class="text-muted mb-0">Schools</p>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="stat-card">
                        <h6 class="fw-bold mb-3">Listings Status</h6>
                        <canvas id="listingsChart" height="200"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <h6 class="fw-bold mb-3">Transaction Status</h6>
                        <canvas id="transactionsChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Tabs with Filter -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <ul class="nav nav-tabs mb-0 border-0 gap-2">
                    <li class="nav-item">
                        <a class="nav-link active btn-outline-black rounded-pill px-4 py-2" data-bs-toggle="tab" href="#recentUsers">Recent Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-outline-black rounded-pill px-4 py-2" data-bs-toggle="tab" href="#recentListings">Recent Listings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-outline-black rounded-pill px-4 py-2" data-bs-toggle="tab" href="#recentTransactions">Recent Transactions</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <label for="userTypeFilter" class="text-muted small">Filter by user type:</label>
                    <select id="userTypeFilter" class="filter-select" onchange="applyUserFilter()">
                        <option value="all" <?php echo $user_filter == 'all' ? 'selected' : ''; ?>>All Users</option>
                        <option value="buyer" <?php echo $user_filter == 'buyer' ? 'selected' : ''; ?>>Buyers</option>
                        <option value="seller" <?php echo $user_filter == 'seller' ? 'selected' : ''; ?>>Sellers</option>
                    </select>
                </div>
            </div>

            <div class="tab-content">
                <!-- Recent Users -->
                <div class="tab-pane fade show active" id="recentUsers">
                    <div class="stat-card">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Type</th>
                                        <th>Joined</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $recent_users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo ucfirst($user['user_type']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td><a href="users.php?view=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-black rounded-pill">View</a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if ($recent_users->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No users found for this filter.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="users.php" class="btn btn-outline-black rounded-pill mt-3">View All Users →</a>
                    </div>
                </div>

                <!-- Recent Listings -->
                <div class="tab-pane fade" id="recentListings">
                    <div class="stat-card">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>School</th>
                                        <th>Seller</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($listing = $recent_listings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($listing['title']); ?></td>
                                        <td><?php echo htmlspecialchars($listing['school_name']); ?></td>
                                        <td><?php echo htmlspecialchars($listing['first_name'] . ' ' . $listing['last_name']); ?></td>
                                        <td>R<?php echo number_format($listing['price'], 2); ?></td>
                                        <td><span class="badge <?php echo $listing['status'] === 'active' ? 'bg-success' : ($listing['status'] === 'sold' ? 'bg-secondary' : 'bg-warning'); ?>"><?php echo ucfirst($listing['status']); ?></span></td>
                                        <td><a href="listings.php?view=<?php echo $listing['listing_id']; ?>" class="btn btn-sm btn-outline-black rounded-pill">View</a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="listings.php" class="btn btn-outline-black rounded-pill mt-3">View All Listings →</a>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="tab-pane fade" id="recentTransactions">
                    <div class="stat-card">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Buyer</th>
                                        <th>Seller</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction['listing_title']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['buyer_first'] . ' ' . $transaction['buyer_last']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['seller_first'] . ' ' . $transaction['seller_last']); ?></td>
                                        <td>R<?php echo number_format($transaction['amount'], 2); ?></td>
                                        <td><span class="badge <?php echo $transaction['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?>"><?php echo ucfirst($transaction['status']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="transactions.php" class="btn btn-outline-black rounded-pill mt-3">View All Transactions →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Listings Chart - Different shades of blue
    const listingsCtx = document.getElementById('listingsChart').getContext('2d');
    new Chart(listingsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Sold', 'Pending'],
            datasets: [{
                data: [<?php echo $stats['active_listings']; ?>, <?php echo $stats['sold_listings']; ?>, <?php echo $stats['pending_listings']; ?>],
                backgroundColor: ['#1E88E5', '#64B5F6', '#90CAF9'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Transactions Chart - Different shades of blue
    const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
    new Chart(transactionsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending'],
            datasets: [{
                data: [<?php echo $stats['completed_transactions']; ?>, <?php echo $stats['pending_transactions']; ?>],
                backgroundColor: ['#0D47A1', '#42A5F5'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Function to apply filter via GET parameter
    function applyUserFilter() {
        const filterValue = document.getElementById('userTypeFilter').value;
        const currentUrl = new URL(window.location.href);
        if (filterValue === 'all') {
            currentUrl.searchParams.delete('user_filter');
        } else {
            currentUrl.searchParams.set('user_filter', filterValue);
        }
        window.location.href = currentUrl.toString();
    }
</script>
</body>
</html>