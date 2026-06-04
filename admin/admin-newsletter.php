<?php
session_start();
require_once 'config/database.php';

// Check if user is admin (you'll need to adjust this based on your admin system)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php?redirect=admin-newsletter.php');
    exit();
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_action']) && isset($_POST['subscribers'])) {
        $subscriber_ids = $_POST['subscribers'];
        $action = $_POST['bulk_action'];
        
        if ($action === 'activate') {
            $placeholders = implode(',', array_fill(0, count($subscriber_ids), '?'));
            $sql = "UPDATE subscribers SET is_active = 1 WHERE subscriber_id IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(str_repeat('i', count($subscriber_ids)), ...$subscriber_ids);
            $stmt->execute();
            $_SESSION['admin_message'] = count($subscriber_ids) . ' subscribers activated successfully.';
        } 
        elseif ($action === 'deactivate') {
            $placeholders = implode(',', array_fill(0, count($subscriber_ids), '?'));
            $sql = "UPDATE subscribers SET is_active = 0 WHERE subscriber_id IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(str_repeat('i', count($subscriber_ids)), ...$subscriber_ids);
            $stmt->execute();
            $_SESSION['admin_message'] = count($subscriber_ids) . ' subscribers deactivated successfully.';
        }
        elseif ($action === 'delete') {
            $placeholders = implode(',', array_fill(0, count($subscriber_ids), '?'));
            $sql = "DELETE FROM subscribers WHERE subscriber_id IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(str_repeat('i', count($subscriber_ids)), ...$subscriber_ids);
            $stmt->execute();
            $_SESSION['admin_message'] = count($subscriber_ids) . ' subscribers deleted successfully.';
        }
        header('Location: admin-newsletter.php');
        exit();
    }
    
    // Handle single subscriber edit
    if (isset($_POST['edit_subscriber'])) {
        $subscriber_id = $_POST['subscriber_id'];
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($email) {
            $sql = "UPDATE subscribers SET email = ?, is_active = ? WHERE subscriber_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $email, $is_active, $subscriber_id);
            $stmt->execute();
            $_SESSION['admin_message'] = 'Subscriber updated successfully.';
        } else {
            $_SESSION['admin_error'] = 'Invalid email address.';
        }
        header('Location: admin-newsletter.php');
        exit();
    }
    
    // Handle send newsletter
    if (isset($_POST['send_newsletter'])) {
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        
        if (empty($subject) || empty($message)) {
            $_SESSION['admin_error'] = 'Please enter both subject and message.';
        } else {
            // Get all active subscribers
            $sql = "SELECT email FROM subscribers WHERE is_active = 1";
            $result = $conn->query($sql);
            
            $sent_count = 0;
            $failed_count = 0;
            
            while ($subscriber = $result->fetch_assoc()) {
                if (sendNewsletterEmail($subscriber['email'], $subject, $message)) {
                    $sent_count++;
                } else {
                    $failed_count++;
                }
            }
            
            // Log the newsletter campaign
            $log_sql = "INSERT INTO newsletter_campaigns (subject, sent_count, failed_count, sent_by, sent_at) VALUES (?, ?, ?, ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("siii", $subject, $sent_count, $failed_count, $_SESSION['user_id']);
            $log_stmt->execute();
            
            $_SESSION['admin_message'] = "Newsletter sent to $sent_count subscribers. Failed: $failed_count";
        }
        header('Location: admin-newsletter.php');
        exit();
    }
}

// Handle export
if (isset($_GET['export'])) {
    $format = $_GET['export'];
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    
    $sql = "SELECT subscriber_id, email, subscribed_at, ip_address, is_active FROM subscribers";
    if ($status === 'active') {
        $sql .= " WHERE is_active = 1";
    } elseif ($status === 'inactive') {
        $sql .= " WHERE is_active = 0";
    }
    $sql .= " ORDER BY subscribed_at DESC";
    
    $result = $conn->query($sql);
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Email', 'Subscribed Date', 'IP Address', 'Status']);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['subscriber_id'],
                $row['email'],
                $row['subscribed_at'],
                $row['ip_address'],
                $row['is_active'] ? 'Active' : 'Inactive'
            ]);
        }
        fclose($output);
        exit();
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "email LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($status_filter === 'active') {
    $where_conditions[] = "is_active = 1";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "is_active = 0";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM subscribers $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_subscribers = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_subscribers / $limit);

// Get subscribers for current page
$sql = "SELECT subscriber_id, email, subscribed_at, ip_address, is_active 
        FROM subscribers 
        $where_clause 
        ORDER BY subscribed_at DESC 
        LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
                COUNT(DISTINCT DATE(subscribed_at)) as days_with_signups,
                MAX(subscribed_at) as latest_signup
              FROM subscribers";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get recent campaigns
$campaigns_sql = "SELECT * FROM newsletter_campaigns ORDER BY sent_at DESC LIMIT 10";
$campaigns_result = $conn->query($campaigns_sql);

// Function to send email
function sendNewsletterEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Uniformly <newsletter@uniformly.com>" . "\r\n";
    
    $html_message = "
    <html>
    <head>
        <title>$subject</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #F5EFE0; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .unsubscribe { color: #007bff; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Uniformly Newsletter</h2>
            </div>
            <div class='content'>
                " . nl2br(htmlspecialchars($message)) . "
            </div>
            <div class='footer'>
                <p>You're receiving this because you subscribed to Uniformly newsletter.</p>
                <p><a href='https://yourdomain.com/unsubscribe.php?email=" . urlencode($to) . "&token=" . md5($to . 'YOUR_SECRET_KEY_HERE') . "' class='unsubscribe'>Unsubscribe</a></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return mail($to, $subject, $html_message, $headers);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Newsletter Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .stat-card { background: white; border-radius: 15px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: 700; color: #000; }
        .stat-label { color: #6c757d; font-size: 0.875rem; margin-top: 0.5rem; }
        .table-container { background: white; border-radius: 15px; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .btn-black { background-color: #000; color: white; border-radius: 40px; padding: 0.5rem 1.5rem; transition: all 0.2s; }
        .btn-black:hover { background-color: #333; transform: translateY(-1px); }
        .btn-outline-black { border: 1.5px solid #000; color: #000; border-radius: 40px; padding: 0.5rem 1.5rem; }
        .btn-outline-black:hover { background-color: #000; color: white; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .sidebar { background: white; border-radius: 15px; padding: 1.5rem; position: sticky; top: 20px; }
        .nav-link-custom { color: #333; padding: 0.75rem 1rem; border-radius: 10px; transition: all 0.2s; }
        .nav-link-custom:hover { background: #f8f9fa; color: #000; }
        .nav-link-custom.active { background: #000; color: white; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 px-0">
            <div class="sidebar min-vh-100">
                <div class="text-center mb-4">
                    <i class="bi bi-backpack fs-1"></i>
                    <h5 class="mt-2 fw-bold">Uniformly Admin</h5>
                </div>
                <nav class="nav flex-column">
                    <a href="admin-dashboard.php" class="nav-link-custom mb-2">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="admin-newsletter.php" class="nav-link-custom active mb-2">
                        <i class="bi bi-envelope-paper me-2"></i> Newsletter
                    </a>
                    <a href="admin-subscribers.php" class="nav-link-custom mb-2">
                        <i class="bi bi-people me-2"></i> Subscribers
                    </a>
                    <a href="admin-listings.php" class="nav-link-custom mb-2">
                        <i class="bi bi-grid me-2"></i> Listings
                    </a>
                    <a href="logout.php" class="nav-link-custom text-danger mt-5">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">Newsletter Management</h2>
                <button class="btn btn-black" data-bs-toggle="modal" data-bs-target="#sendNewsletterModal">
                    <i class="bi bi-send"></i> Send Newsletter
                </button>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['admin_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['admin_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['admin_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['admin_error']); ?>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="bi bi-people fs-2"></i>
                        <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                        <div class="stat-label">Total Subscribers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="bi bi-check-circle fs-2 text-success"></i>
                        <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                        <div class="stat-label">Active Subscribers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="bi bi-x-circle fs-2 text-danger"></i>
                        <div class="stat-number"><?php echo number_format($stats['inactive']); ?></div>
                        <div class="stat-label">Inactive Subscribers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="bi bi-calendar fs-2"></i>
                        <div class="stat-number"><?php echo $stats['days_with_signups']; ?></div>
                        <div class="stat-label">Days with Signups</div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="table-container mb-4">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="GET" action="" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" placeholder="Search by email..." value="<?php echo htmlspecialchars($search); ?>" style="border-radius: 40px;">
                            <button type="submit" class="btn btn-black">Search</button>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group">
                            <a href="?status=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline-black <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
                            <a href="?status=active<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline-black <?php echo $status_filter == 'active' ? 'active' : ''; ?>">Active</a>
                            <a href="?status=inactive<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline-black <?php echo $status_filter == 'inactive' ? 'active' : ''; ?>">Inactive</a>
                        </div>
                        <div class="btn-group ms-2">
                            <button class="btn btn-outline-black dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-download"></i> Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?export=csv&status=<?php echo $status_filter; ?>">CSV - All</a></li>
                                <li><a class="dropdown-item" href="?export=csv&status=active">CSV - Active Only</a></li>
                                <li><a class="dropdown-item" href="?export=csv&status=inactive">CSV - Inactive Only</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions Form -->
                <form method="POST" action="" id="bulkForm">
                    <div class="mb-3">
                        <select name="bulk_action" class="form-select w-auto d-inline-block" style="width: auto;">
                            <option value="">Bulk Actions</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-black ms-2">Apply</button>
                    </div>

                    <!-- Subscribers Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Subscribed Date</th>
                                    <th>IP Address</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($subscriber = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><input type="checkbox" name="subscribers[]" value="<?php echo $subscriber['subscriber_id']; ?>"></td>
                                        <td><?php echo $subscriber['subscriber_id']; ?></td>
                                        <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($subscriber['subscribed_at'])); ?></td>
                                        <td><?php echo $subscriber['ip_address'] ?? 'N/A'; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $subscriber['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $subscriber['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-black" onclick="editSubscriber(<?php echo $subscriber['subscriber_id']; ?>, '<?php echo htmlspecialchars($subscriber['email']); ?>', <?php echo $subscriber['is_active']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteSubscriber(<?php echo $subscriber['subscriber_id']; ?>, '<?php echo htmlspecialchars($subscriber['email']); ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if ($result->num_rows == 0): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-inbox fs-1 text-muted"></i>
                                            <p class="mt-2 text-muted">No subscribers found</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">Previous</a></li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <!-- Recent Campaigns -->
            <div class="table-container">
                <h5 class="fw-bold mb-3">Recent Newsletter Campaigns</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Sent</th>
                                <th>Failed</th>
                                <th>Sent By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($campaign = $campaigns_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($campaign['sent_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($campaign['subject']); ?></td>
                                    <td><?php echo $campaign['sent_count']; ?></td>
                                    <td><?php echo $campaign['failed_count']; ?></td>
                                    <td>Admin #<?php echo $campaign['sent_by']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($campaigns_result->num_rows == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">No campaigns sent yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Newsletter Modal -->
<div class="modal fade" id="sendNewsletterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Send Newsletter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> This will send an email to all <strong><?php echo number_format($stats['active']); ?></strong> active subscribers.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subject</label>
                        <input type="text" name="subject" class="form-control" required placeholder="Enter email subject">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Message</label>
                        <textarea name="message" class="form-control" rows="8" required placeholder="Write your newsletter content here..."></textarea>
                        <small class="text-muted">HTML formatting is supported. Line breaks will be preserved.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-black" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="send_newsletter" class="btn btn-black">Send Newsletter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subscriber Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Subscriber</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="subscriber_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="edit_active" class="form-check-input" value="1">
                        <label class="form-check-label fw-bold">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-black" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_subscriber" class="btn btn-black">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Delete Subscriber</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="delete_email"></strong>?</p>
                <p class="text-danger small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-black" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="">
                    <input type="hidden" name="subscribers[]" id="delete_id">
                    <input type="hidden" name="bulk_action" value="delete">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Select all checkboxes
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input[name="subscribers[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
});

// Edit subscriber function
function editSubscriber(id, email, isActive) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_active').checked = isActive == 1;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Delete subscriber function
function deleteSubscriber(id, email) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_email').innerText = email;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Confirm before sending newsletter
document.querySelector('form[action=""]')?.addEventListener('submit', function(e) {
    if (this.querySelector('textarea[name="message"]')?.value) {
        return confirm('Are you sure you want to send this newsletter to all active subscribers?');
    }
});
</script>
</body>
</html>