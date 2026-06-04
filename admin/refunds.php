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

// Handle refund action (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $refund_id = intval($_POST['refund_id']);
    $action = $_POST['action'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    if ($action === 'approve') {
        // Get refund details
        $refund_sql = "SELECT r.*, t.transaction_id, t.status as transaction_status 
                       FROM refunds r 
                       JOIN transactions t ON r.transaction_id = t.transaction_id 
                       WHERE r.refund_id = ?";
        $refund_stmt = $conn->prepare($refund_sql);
        $refund_stmt->bind_param("i", $refund_id);
        $refund_stmt->execute();
        $refund = $refund_stmt->get_result()->fetch_assoc();
        $refund_stmt->close();
        
        if ($refund && $refund['transaction_status'] === 'completed') {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update refund status
                $update_refund = "UPDATE refunds SET status = 'approved', admin_notes = ?, refund_date = NOW() WHERE refund_id = ?";
                $update_stmt = $conn->prepare($update_refund);
                $update_stmt->bind_param("si", $admin_notes, $refund_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Update transaction status to refunded
                $update_trans = "UPDATE transactions SET status = 'refunded' WHERE transaction_id = ?";
                $trans_stmt = $conn->prepare($update_trans);
                $trans_stmt->bind_param("i", $refund['transaction_id']);
                $trans_stmt->execute();
                $trans_stmt->close();
                
                // Update listing status back to active
                $update_listing = "UPDATE listings SET status = 'active' WHERE listing_id = ?";
                $listing_stmt = $conn->prepare($update_listing);
                $listing_stmt->bind_param("i", $refund['listing_id']);
                $listing_stmt->execute();
                $listing_stmt->close();
                
                $conn->commit();
                $success_message = "Refund approved successfully! The buyer has been refunded.";
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Failed to process refund: " . $e->getMessage();
            }
        } else {
            $error_message = "Invalid refund request or transaction already processed.";
        }
    } elseif ($action === 'reject') {
        $update_sql = "UPDATE refunds SET status = 'rejected', admin_notes = ? WHERE refund_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $admin_notes, $refund_id);
        if ($update_stmt->execute()) {
            $success_message = "Refund request rejected.";
        } else {
            $error_message = "Failed to reject refund.";
        }
        $update_stmt->close();
    }
}

// Get all refund requests
$refunds_sql = "SELECT r.*, 
                u.first_name as buyer_first, u.last_name as buyer_last, u.email as buyer_email,
                s.first_name as seller_first, s.last_name as seller_last,
                l.title as listing_title,
                t.transaction_id, t.amount
                FROM refunds r
                JOIN users u ON r.user_id = u.user_id
                JOIN users s ON r.seller_id = s.user_id
                JOIN listings l ON r.listing_id = l.listing_id
                JOIN transactions t ON r.transaction_id = t.transaction_id
                ORDER BY 
                    CASE WHEN r.status = 'pending' THEN 1 ELSE 2 END,
                    r.created_at DESC";
$refunds_result = $conn->query($refunds_sql);

// Get counts
$pending_count_sql = "SELECT COUNT(*) as count FROM refunds WHERE status = 'pending'";
$pending_result = $conn->query($pending_count_sql);
$pending_count = $pending_result->fetch_assoc()['count'];

$approved_count_sql = "SELECT COUNT(*) as count FROM refunds WHERE status = 'approved'";
$approved_result = $conn->query($approved_count_sql);
$approved_count = $approved_result->fetch_assoc()['count'];

$rejected_count_sql = "SELECT COUNT(*) as count FROM refunds WHERE status = 'rejected'";
$rejected_result = $conn->query($rejected_count_sql);
$rejected_count = $rejected_result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .content-card { background: white; border-radius: 24px; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid rgba(0, 0, 0, 0.05); }
        .stat-card { background: white; border-radius: 20px; padding: 1rem; text-align: center; border: 1px solid rgba(0,0,0,0.05); }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 40px; font-size: 0.75rem; font-weight: 500; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .modal-image { max-width: 100%; border-radius: 16px; }
        .refund-detail { background: #FEF9E6; border-radius: 16px; padding: 1rem; margin-bottom: 1rem; }
        @media (max-width: 768px) { .sidebar { position: relative; top: 0; margin-bottom: 1.5rem; } }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 col-md-3 my-4">
            <div class="sidebar">
                <div class="text-center mb-4">
                    <div class="admin-avatar mx-auto"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'AD', 0, 2)); ?></div>
                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></h6>
                    <small class="text-muted">Administrator</small>
                </div>
                <ul class="sidebar-nav">
                    <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a href="profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
                    <li><a href="users.php"><i class="bi bi-people"></i> Users</a></li>
                    <li><a href="listings.php"><i class="bi bi-grid-3x3-gap-fill"></i> Listings</a></li>
                    <li><a href="transactions.php"><i class="bi bi-currency-rand"></i> Transactions</a></li>
                    <li><a href="admin-newsletter.php"><i class="bi bi-envelope"></i> Subscribers</a></li>
                    <li><a href="refunds.php" class="active"><i class="bi bi-cash-stack"></i> Refunds 
                        <?php if ($pending_count > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="reports.php"><i class="bi bi-file-text"></i> Reports</a></li>
                    <li><hr class="my-2"></li>
                    <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <div class="col-lg-10 col-md-9 my-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="fw-bold">Refund Management</h1>
                    <p class="text-muted">Review and process customer refund requests</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Pending</h6>
                                <h2 class="fw-bold mb-0"><?php echo $pending_count; ?></h2>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-clock-history" style="font-size: 1.5rem; color: #856404;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Approved</h6>
                                <h2 class="fw-bold mb-0"><?php echo $approved_count; ?></h2>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-check-circle" style="font-size: 1.5rem; color: #155724;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Rejected</h6>
                                <h2 class="fw-bold mb-0"><?php echo $rejected_count; ?></h2>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-x-circle" style="font-size: 1.5rem; color: #721c24;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Refunds List -->
            <div class="content-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Buyer</th>
                                <th>Item</th>
                                <th>Seller</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($refund = $refunds_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $refund['refund_id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($refund['buyer_first'] . ' ' . $refund['buyer_last']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($refund['buyer_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($refund['listing_title']); ?></td>
                                <td><?php echo htmlspecialchars($refund['seller_first'] . ' ' . $refund['seller_last']); ?></td>
                                <td>R<?php echo number_format($refund['amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo str_replace('_', ' ', ucfirst($refund['reason'])); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $refund['status']; ?>">
                                        <?php echo ucfirst($refund['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($refund['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-black rounded-pill mb-1" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $refund['refund_id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <?php if ($refund['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-sm btn-success rounded-pill mb-1" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $refund['refund_id']; ?>">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger rounded-pill mb-1" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $refund['refund_id']; ?>">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal<?php echo $refund['refund_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Refund Request #<?php echo $refund['refund_id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="refund-detail">
                                                <h6>Buyer Information</h6>
                                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($refund['buyer_first'] . ' ' . $refund['buyer_last']); ?></p>
                                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($refund['buyer_email']); ?></p>
                                            </div>
                                            <div class="refund-detail">
                                                <h6>Transaction Details</h6>
                                                <p class="mb-1"><strong>Item:</strong> <?php echo htmlspecialchars($refund['listing_title']); ?></p>
                                                <p class="mb-1"><strong>Seller:</strong> <?php echo htmlspecialchars($refund['seller_first'] . ' ' . $refund['seller_last']); ?></p>
                                                <p class="mb-1"><strong>Amount:</strong> R<?php echo number_format($refund['amount'], 2); ?></p>
                                                <p class="mb-1"><strong>Reason:</strong> <?php echo str_replace('_', ' ', ucfirst($refund['reason'])); ?></p>
                                            </div>
                                            <div class="refund-detail">
                                                <h6>Buyer's Message</h6>
                                                <p><?php echo nl2br(htmlspecialchars($refund['message'])); ?></p>
                                            </div>
                                            <?php if ($refund['image_path'] && file_exists('../' . $refund['image_path'])): ?>
                                                <div class="refund-detail">
                                                    <h6>Supporting Image</h6>
                                                    <img src="../<?php echo $refund['image_path']; ?>" class="modal-image" alt="Refund evidence" style="max-width: 100%; border-radius: 16px;">
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($refund['admin_notes']): ?>
                                                <div class="refund-detail">
                                                    <h6>Admin Notes</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($refund['admin_notes'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-black rounded-pill" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Approve Modal -->
                            <?php if ($refund['status'] === 'pending'): ?>
                            <div class="modal fade" id="approveModal<?php echo $refund['refund_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="refunds.php">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Approve Refund</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to approve this refund request?</p>
                                                <p><strong>Buyer:</strong> <?php echo htmlspecialchars($refund['buyer_first'] . ' ' . $refund['buyer_last']); ?></p>
                                                <p><strong>Amount:</strong> R<?php echo number_format($refund['amount'], 2); ?></p>
                                                <div class="mb-3">
                                                    <label class="form-label">Admin Notes (Optional)</label>
                                                    <textarea class="form-control" name="admin_notes" rows="3" placeholder="Add any notes about this refund..."></textarea>
                                                </div>
                                                <input type="hidden" name="refund_id" value="<?php echo $refund['refund_id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-black rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success rounded-pill">Approve Refund</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal<?php echo $refund['refund_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="refunds.php">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reject Refund</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to reject this refund request?</p>
                                                <p><strong>Buyer:</strong> <?php echo htmlspecialchars($refund['buyer_first'] . ' ' . $refund['buyer_last']); ?></p>
                                                <div class="mb-3">
                                                    <label class="form-label">Reason for Rejection</label>
                                                    <textarea class="form-control" name="admin_notes" rows="3" required placeholder="Please provide a reason for rejecting this refund..."></textarea>
                                                </div>
                                                <input type="hidden" name="refund_id" value="<?php echo $refund['refund_id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-black rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger rounded-pill">Reject Refund</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endwhile; ?>
                            <?php if ($refunds_result->num_rows === 0): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">No refund requests found.</td>
                                </tr>
                            <?php endif; ?>
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