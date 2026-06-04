<?php
session_start();
require_once '../config/database.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$pending_refunds_sql = "SELECT COUNT(*) as count FROM refunds WHERE status = 'pending'";
$pending_refunds_result = $conn->query($pending_refunds_sql);
$pending_refunds_count = $pending_refunds_result->fetch_assoc()['count'];

// Get date filters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'monthly';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

// Monthly Revenue Data
$monthly_sql = "SELECT 
                  DATE_FORMAT(transaction_date, '%Y-%m') as month,
                  COUNT(*) as count,
                  SUM(amount) as revenue
                FROM transactions 
                WHERE status = 'completed'
                GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                ORDER BY month DESC
                LIMIT 12";
$monthly_result = $conn->query($monthly_sql);
$monthly_data = [];
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_data[] = $row;
}

// Transaction Status Distribution
$status_distribution_sql = "SELECT 
                              status,
                              COUNT(*) as count,
                              SUM(amount) as total_amount
                            FROM transactions
                            GROUP BY status";
$status_distribution = $conn->query($status_distribution_sql);

// Top Sellers by Revenue
$top_sellers_sql = "SELECT 
                      CONCAT(u.first_name, ' ', u.last_name) as seller_name,
                      COUNT(t.transaction_id) as sales_count,
                      SUM(t.amount) as total_revenue
                    FROM transactions t
                    JOIN users u ON t.seller_id = u.user_id
                    WHERE t.status = 'completed'
                    GROUP BY t.seller_id
                    ORDER BY total_revenue DESC
                    LIMIT 10";
$top_sellers = $conn->query($top_sellers_sql);

// User Growth
$user_growth_sql = "SELECT 
                      DATE_FORMAT(created_at, '%Y-%m') as month,
                      COUNT(*) as new_users
                    FROM users
                    WHERE user_type != 'admin'
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY month DESC
                    LIMIT 12";
$user_growth = $conn->query($user_growth_sql);

// Category Distribution
$category_sql = "SELECT 
                   category,
                   COUNT(*) as count
                 FROM listings
                 GROUP BY category
                 ORDER BY count DESC";
$category_result = $conn->query($category_sql);

// Refund statistics
$refund_stats_sql = "SELECT 
                       COUNT(*) as total_refunds,
                       SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_refunds,
                       SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_refunds,
                       SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_refunds
                     FROM refunds";
$refund_stats_result = $conn->query($refund_stats_sql);
$refund_stats = $refund_stats_result->fetch_assoc();

// Export functionality
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Transaction ID', 'Item', 'Buyer', 'Seller', 'Amount', 'Status', 'Date']);
    
    $export_sql = "SELECT t.transaction_id, l.title, 
                   CONCAT(buyer.first_name, ' ', buyer.last_name) as buyer_name,
                   CONCAT(seller.first_name, ' ', seller.last_name) as seller_name,
                   t.amount, t.status, t.transaction_date
                   FROM transactions t
                   JOIN listings l ON t.listing_id = l.listing_id
                   JOIN users buyer ON t.buyer_id = buyer.user_id
                   JOIN users seller ON t.seller_id = seller.user_id
                   ORDER BY t.transaction_date DESC";
    $export_result = $conn->query($export_sql);
    
    while ($row = $export_result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// PDF generation handler
if (isset($_GET['download_pdf'])) {
    require_once('../vendor/autoload.php'); // Make sure dompdf is installed via composer
    
    // Capture the report content
    ob_start();
    include('report_pdf_content.php');
    $html_content = ob_get_clean();
    
    // Get logo as base64
    $logo_path = 'logo.png';
    $logo_base64 = '';
    if (file_exists($logo_path)) {
        $logo_data = file_get_contents($logo_path);
        $logo_base64 = 'data:image/png;base64,' . base64_encode($logo_data);
    }
    
    // Prepare data for PDF
    $pdf_data = [
        'logo' => $logo_base64,
        'report_date' => date('Y-m-d H:i:s'),
        'monthly_data' => $monthly_data,
        'status_data' => $status_distribution->fetch_all(MYSQLI_ASSOC),
        'top_sellers' => $top_sellers->fetch_all(MYSQLI_ASSOC),
        'user_growth' => $user_growth->fetch_all(MYSQLI_ASSOC),
        'category_data' => $category_result->fetch_all(MYSQLI_ASSOC),
        'refund_stats' => $refund_stats
    ];
    
    // Re-fetch data that was consumed
    $status_distribution = $conn->query($status_distribution_sql);
    $top_sellers = $conn->query($top_sellers_sql);
    $user_growth = $conn->query($user_growth_sql);
    $category_result = $conn->query($category_sql);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Financial Report</title>
        <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                margin: 20px;
                color: #333;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #000;
                padding-bottom: 20px;
            }
            .logo {
                max-width: 150px;
                margin-bottom: 15px;
            }
            h1 {
                font-size: 24px;
                margin: 10px 0;
            }
            .report-date {
                color: #666;
                font-size: 12px;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                margin-bottom: 30px;
            }
            .stat-card {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 15px;
                text-align: center;
                background: #f9f9f9;
            }
            .stat-number {
                font-size: 24px;
                font-weight: bold;
                margin: 5px 0;
            }
            .stat-label {
                color: #666;
                font-size: 12px;
            }
            .section {
                margin-bottom: 30px;
                page-break-inside: avoid;
            }
            .section-title {
                font-size: 18px;
                font-weight: bold;
                border-left: 4px solid #000;
                padding-left: 10px;
                margin-bottom: 15px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            .chart-container {
                margin: 20px 0;
                text-align: center;
            }
            .chart-img {
                max-width: 100%;
                height: auto;
                border: 1px solid #eee;
            }
            .footer {
                text-align: center;
                font-size: 10px;
                color: #999;
                margin-top: 50px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }
        </style>
    </head>
    <body>
        <div class="header">
            ' . ($logo_base64 ? '<img src="' . $logo_base64 . '" class="logo">' : '') . '
            <h1>Platform Financial Report</h1>
            <div class="report-date">Generated: ' . date('F d, Y H:i:s') . '</div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number">R' . number_format(array_sum(array_column($monthly_data, 'revenue')), 2) . '</div><div class="stat-label">Total Revenue</div></div>
            <div class="stat-card"><div class="stat-number">' . array_sum(array_column($pdf_data['status_data'], 'count')) . '</div><div class="stat-label">Total Transactions</div></div>
            <div class="stat-card"><div class="stat-number">' . $pdf_data['refund_stats']['total_refunds'] . '</div><div class="stat-label">Total Refunds</div></div>
            <div class="stat-card"><div class="stat-number">' . array_sum(array_column($pdf_data['category_data'], 'count')) . '</div><div class="stat-label">Total Listings</div></div>
        </div>
        
        <div class="section">
            <div class="section-title">Monthly Revenue Breakdown</div>
            <table>
                <thead><tr><th>Month</th><th>Transactions</th><th>Revenue (R)</th></tr></thead>
                <tbody>';
                    foreach (array_reverse($monthly_data) as $m) {
                        $html .= '<tr><td>' . date('M Y', strtotime($m['month'] . '-01')) . '</td><td>' . $m['count'] . '</td><td>R' . number_format($m['revenue'], 2) . '</td></tr>';
                    }
    $html .= '</tbody>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">Transaction Status Distribution</div>
            <table>
                <thead><tr><th>Status</th><th>Count</th><th>Total Amount (R)</th></tr></thead>
                <tbody>';
                    foreach ($pdf_data['status_data'] as $s) {
                        $html .= '<tr><td>' . ucfirst($s['status']) . '</td><td>' . $s['count'] . '</td><td>R' . number_format($s['total_amount'], 2) . '</td></tr>';
                    }
    $html .= '</tbody>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">Top Sellers by Revenue</div>
            <table>
                <thead><tr><th>Seller Name</th><th>Sales Count</th><th>Total Revenue (R)</th></tr></thead>
                <tbody>';
                    foreach ($pdf_data['top_sellers'] as $seller) {
                        $html .= '<tr><td>' . htmlspecialchars($seller['seller_name']) . '</td><td>' . $seller['sales_count'] . '</td><td>R' . number_format($seller['total_revenue'], 2) . '</td></tr>';
                    }
    $html .= '</tbody>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">User Growth (Last 12 Months)</div>
            <table>
                <thead><tr><th>Month</th><th>New Users</th></tr></thead>
                <tbody>';
                    $user_data_reversed = array_reverse($pdf_data['user_growth']);
                    foreach ($user_data_reversed as $u) {
                        $html .= '<tr><td>' . date('M Y', strtotime($u['month'] . '-01')) . '</td><td>' . $u['new_users'] . '</td></tr>';
                    }
    $html .= '</tbody>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">Category Distribution</div>
            <table>
                <thead><tr><th>Category</th><th>Number of Listings</th></tr></thead>
                <tbody>';
                    foreach ($pdf_data['category_data'] as $cat) {
                        $html .= '<tr><td>' . ucfirst(str_replace('_', ' ', $cat['category'])) . '</td><td>' . $cat['count'] . '</td></tr>';
                    }
    $html .= '</tbody>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">Refund Statistics</div>
            <table>
                <thead><tr><th>Status</th><th>Count</th></tr></thead>
                <tbody>
                    <tr><td>Total Refunds</td><td>' . $pdf_data['refund_stats']['total_refunds'] . '</td></tr>
                    <tr><td>Pending Refunds</td><td>' . $pdf_data['refund_stats']['pending_refunds'] . '</td></tr>
                    <tr><td>Approved Refunds</td><td>' . $pdf_data['refund_stats']['approved_refunds'] . '</td></tr>
                    <tr><td>Rejected Refunds</td><td>' . $pdf_data['refund_stats']['rejected_refunds'] . '</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Generated automatically from the admin panel. This report includes all platform transactions and user data up to the generation date.</p>
        </div>
    </body>
    </html>';
    
    $options = new \Dompdf\Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', true);
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("financial_report_" . date('Y-m-d') . ".pdf", array("Attachment" => true));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .btn-black { background-color: #000000; color: white; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; border: none; }
        .btn-black:hover { background-color: #2C2C2C; }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
        .content-card { background: white; border-radius: 24px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .stat-box { background: #FEF9E6; border-radius: 16px; padding: 1rem; text-align: center; }
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
                    <li><a href="transactions.php"><i class="bi bi-currency-rand"></i> Transactions</a></li>
                    <li><a href="admin-newsletter.php"><i class="bi bi-envelope"></i> Subscribers</a></li>
                    <li><a href="refunds.php"><i class="bi bi-cash-stack"></i> Refunds 
                        <?php if ($pending_refunds_count > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?php echo $pending_refunds_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="reports.php" class="active"><i class="bi bi-file-text"></i> Reports</a></li>
                    <li><hr class="my-2"></li>
                    <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <div class="col-lg-10 col-md-9 my-4">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">Reports & Analytics</h2>
                        <p class="text-muted">Platform insights and data analysis</p>
                    </div>
                    <div>
                        <a href="reports.php?download_pdf=1" class="btn btn-black rounded-pill me-2"><i class="bi bi-file-pdf"></i> Download PDF</a>
                        <a href="reports.php?export=csv" class="btn btn-outline-black rounded-pill"><i class="bi bi-download"></i> Export CSV</a>
                    </div>
                </div>

                <!-- Refund Stats Summary -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h4 class="fw-bold mb-0 text-primary"><?php echo $refund_stats['total_refunds'] ?? 0; ?></h4>
                            <small class="text-muted">Total Refunds</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h4 class="fw-bold mb-0 text-warning"><?php echo $refund_stats['pending_refunds'] ?? 0; ?></h4>
                            <small class="text-muted">Pending Refunds</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h4 class="fw-bold mb-0 text-success"><?php echo $refund_stats['approved_refunds'] ?? 0; ?></h4>
                            <small class="text-muted">Approved Refunds</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <h4 class="fw-bold mb-0 text-danger"><?php echo $refund_stats['rejected_refunds'] ?? 0; ?></h4>
                            <small class="text-muted">Rejected Refunds</small>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue Chart (Bar Chart instead of Line) -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="fw-bold mb-3">Monthly Revenue</h5>
                        <canvas id="revenueChart" height="300"></canvas>
                    </div>
                </div>

                <div class="row">
                    <!-- Transaction Status Distribution (Doughnut Chart - replacing line graph) -->
                    <div class="col-md-6 mb-4">
                        <h5 class="fw-bold mb-3">Transaction Status Distribution</h5>
                        <canvas id="statusChart" height="250"></canvas>
                    </div>

                    <!-- Category Distribution -->
                    <div class="col-md-6 mb-4">
                        <h5 class="fw-bold mb-3">Category Distribution</h5>
                        <canvas id="categoryChart" height="250"></canvas>
                    </div>
                </div>

                <div class="row">
                    <!-- Top Sellers by Revenue -->
                    <div class="col-md-6 mb-4">
                        <h5 class="fw-bold mb-3">Top Sellers by Revenue</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>Seller</th><th>Sales</th><th>Revenue</th></tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $top_sellers->data_seek(0);
                                    while ($seller = $top_sellers->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($seller['seller_name']); ?></td>
                                        <td><?php echo $seller['sales_count']; ?> sales</td>
                                        <td>R<?php echo number_format($seller['total_revenue'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- User Growth (Bar Chart) -->
                    <div class="col-md-6 mb-4">
                        <h5 class="fw-bold mb-3">User Growth (Last 12 Months)</h5>
                        <canvas id="userGrowthChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Revenue Chart - Bar chart (replacing line graph)
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueLabels = [<?php 
        $months = array_reverse($monthly_data);
        foreach ($months as $m) echo "'" . date('M Y', strtotime($m['month'] . '-01')) . "',";
    ?>];
    const revenueData = [<?php foreach (array_reverse($monthly_data) as $m) echo $m['revenue'] . ','; ?>];
    
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Revenue (R)',
                data: revenueData,
                backgroundColor: '#1E88E5',
                borderRadius: 8,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    title: { display: true, text: 'Amount (R)' }
                },
                x: {
                    grid: { display: false },
                    title: { display: true, text: 'Month' }
                }
            }
        }
    });

    // Transaction Status Distribution - Doughnut chart (replacing line graph)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusLabels = [<?php 
        $status_data = [];
        $status_distribution->data_seek(0);
        while ($s = $status_distribution->fetch_assoc()) {
            $status_data[] = $s;
        }
        foreach ($status_data as $s) echo "'" . ucfirst($s['status']) . "',";
    ?>];
    const statusCounts = [<?php foreach ($status_data as $s) echo $s['count'] . ','; ?>];
    const statusColors = {
        'completed': '#2e7d32',
        'pending': '#ff9800',
        'cancelled': '#c62828',
        'refunded': '#9e9e9e'
    };
    const statusColorArray = [];
    for (let i = 0; i < statusLabels.length; i++) {
        const label = statusLabels[i].toLowerCase();
        statusColorArray.push(statusColors[label] || '#1E88E5');
    }
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: statusColorArray,
                borderWidth: 0
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: function(context) { return context.label + ': ' + context.raw + ' transactions'; } } }
            }
        }
    });

    // Category Chart - Pie chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categories = [<?php 
        $cat_data = [];
        $category_result->data_seek(0);
        while ($cat = $category_result->fetch_assoc()) {
            $cat_data[] = $cat;
        }
        foreach ($cat_data as $c) echo "'" . ucfirst(str_replace('_', ' ', $c['category'])) . "',";
    ?>];
    const catCounts = [<?php foreach ($cat_data as $c) echo $c['count'] . ','; ?>];
    
    const blueShades = ['#0D47A1', '#1565C0', '#1976D2', '#1E88E5', '#42A5F5', '#64B5F6', '#90CAF9', '#BBDEFB'];
    
    new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: categories,
            datasets: [{
                data: catCounts,
                backgroundColor: blueShades.slice(0, categories.length),
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });

    // User Growth Chart - Bar chart
    const userCtx = document.getElementById('userGrowthChart').getContext('2d');
    const userData = [<?php 
        $user_data = [];
        $user_growth->data_seek(0);
        while ($u = $user_growth->fetch_assoc()) {
            $user_data[] = $u;
        }
        $user_data_reversed = array_reverse($user_data);
        foreach ($user_data_reversed as $u) echo $u['new_users'] . ',';
    ?>];
    const userMonths = [<?php 
        foreach ($user_data_reversed as $u) echo "'" . date('M Y', strtotime($u['month'] . '-01')) . "',";
    ?>];
    
    new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: userMonths,
            datasets: [{
                label: 'New Users',
                data: userData,
                backgroundColor: '#0D47A1',
                borderRadius: 8,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    title: { display: true, text: 'Number of Users' }
                },
                x: {
                    grid: { display: false },
                    title: { display: true, text: 'Month' }
                }
            }
        }
    });
</script>
</body>
</html>