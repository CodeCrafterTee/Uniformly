<?php
session_start();
require_once 'config/database.php';
require_once 'config/payfast.php';

// Verify the payment
$pfData = $_GET;

// Get stored order data
if (!isset($_SESSION['pending_order'])) {
    header('Location: index.php');
    exit();
}

$order = $_SESSION['pending_order'];

// Update transaction status
$update_sql = "UPDATE transactions SET status = 'completed', completion_date = NOW() WHERE transaction_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $order['transaction_id']);
$update_stmt->execute();
$update_stmt->close();

// Clear cart
$clear_sql = "DELETE FROM cart WHERE user_id = ?";
$clear_stmt = $conn->prepare($clear_sql);
$clear_stmt->bind_param("i", $order['user_id']);
$clear_stmt->execute();
$clear_stmt->close();

// Clear sessions
unset($_SESSION['pending_order']);
unset($_SESSION['payfast_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #FEF9E6; font-family: 'Inter', sans-serif; }
        .success-card { background: white; border-radius: 24px; padding: 2rem; text-align: center; max-width: 500px; margin: 2rem auto; }
        .success-icon { font-size: 4rem; color: #2e7d32; }
        .btn-black { background: #000; color: white; padding: 0.75rem 1.5rem; border-radius: 40px; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <i class="bi bi-check-circle-fill success-icon"></i>
            <h2 class="mt-3">Payment Successful!</h2>
            <p>Thank you for your purchase. Your order has been confirmed.</p>
            <p>Order Reference: <strong><?php echo $order['order_id']; ?></strong></p>
            <p>Amount Paid: <strong>R<?php echo number_format($order['total_amount'], 2); ?></strong></p>
            <a href="account-orders.php" class="btn-black rounded-pill px-4 py-2 text-decoration-none">View My Orders</a>
            <a href="shop.php" class="btn-outline-black rounded-pill px-4 py-2 text-decoration-none ms-2">Continue Shopping</a>
        </div>
    </div>
</body>
</html>