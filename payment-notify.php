<?php
// payment-notify.php - This receives ITN (Instant Transaction Notification) from PayFast
session_start();
require_once 'config/database.php';

// Log the incoming data for debugging
file_put_contents('payfast_log.txt', date('Y-m-d H:i:s') . ' - ITN received: ' . print_r($_POST, true) . "\n", FILE_APPEND);

// Verify the signature (implement proper verification)
// For now, process the payment notification

if (isset($_POST['m_payment_id']) && isset($_POST['payment_status'])) {
    $order_id = $_POST['m_payment_id'];
    $payment_status = $_POST['payment_status'];
    $amount = $_POST['amount'] ?? 0;
    $transaction_id = $_POST['pf_payment_id'] ?? '';
    
    // Update transaction status based on payment_status
    if ($payment_status == 'COMPLETE') {
        // Payment successful - update order status
        // Update your database here
        
        file_put_contents('payfast_log.txt', date('Y-m-d H:i:s') . " - Payment COMPLETE for order: $order_id\n", FILE_APPEND);
        
        // Send email confirmation to user
        // Clear cart for user
    } elseif ($payment_status == 'FAILED') {
        file_put_contents('payfast_log.txt', date('Y-m-d H:i:s') . " - Payment FAILED for order: $order_id\n", FILE_APPEND);
    }
}

http_response_code(200);
echo "OK";
?>