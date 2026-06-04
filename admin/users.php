<?php
session_start();
require_once '../config/database.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle user actions
$success_message = '';
$error_message = '';

// Get pending refunds count for badge
$pending_refunds_sql = "SELECT COUNT(*) as count FROM refunds WHERE status = 'pending'";
$pending_refunds_result = $conn->query($pending_refunds_sql);
$pending_refunds_count = $pending_refunds_result->fetch_assoc()['count'];

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM users WHERE user_id = ? AND user_type != 'admin'";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $user_id);
    if ($delete_stmt->execute()) {
        $success_message = "User deleted successfully!";
    } else {
        $error_message = "Failed to delete user.";
    }
    $delete_stmt->close();
}

// Toggle user status (activate/deactivate)
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $user_id = intval($_GET['toggle']);
    $status_sql = "SELECT is_active FROM users WHERE user_id = ?";
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("i", $user_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    $current = $status_result->fetch_assoc();
    $new_status = $current['is_active'] ? 0 : 1;
    $status_stmt->close();
    
    $update_sql = "UPDATE users SET is_active = ? WHERE user_id = ? AND user_type != 'admin'";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_status, $user_id);
    if ($update_stmt->execute()) {
        $success_message = $new_status ? "User activated!" : "User deactivated!";
    }
    $update_stmt->close();
}

// Get all users
$users_sql = "SELECT user_id, email, first_name, last_name, phone, user_type, is_verified, is_active, created_at, last_login 
              FROM users WHERE user_type != 'admin' 
              ORDER BY created_at DESC";
$users_result = $conn->query($users_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
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
                    <li><a href="users.php" class="active"><i class="bi bi-people"></i> Users</a></li>
                    <li><a href="listings.php"><i class="bi bi-grid-3x3-gap-fill"></i> Listings</a></li>
                    <li><a href="admin-newsletter.php"><i class="bi bi-building"></i> Subscribers</a></li>
                    <li><a href="transactions.php"><i class="bi bi-currency-rand"></i> Transactions</a></li>
                      <li><a href="subscribers.php"><i class="bi bi-envelope"></i> Subscribers</a></li>
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
                        <h2 class="fw-bold">User Management</h2>
                        <p class="text-muted">Manage all platform users</p>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert-message alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert-message alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="users.php?toggle=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-black rounded-pill" onclick="return confirm('Toggle user status?')">
                                        <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <a href="users.php?delete=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete this user? This action cannot be undone!')">Delete</a>
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