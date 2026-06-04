<?php
session_start();
require_once 'config/database.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Get and validate email
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$email) {
    // Invalid email - redirect back with error
    $_SESSION['subscribe_error'] = 'Please enter a valid email address.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit();
}

// Get IP address
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

// Check if email already exists in 'subscribers' table
$check_sql = "SELECT subscriber_id, is_active FROM subscribers WHERE email = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $subscriber = $check_result->fetch_assoc();
    
    if ($subscriber['is_active'] == 1) {
        // Already subscribed
        $_SESSION['subscribe_error'] = 'This email is already subscribed to our newsletter.';
    } else {
        // Reactivate subscription
        $update_sql = "UPDATE subscribers SET is_active = 1, subscribed_at = CURRENT_TIMESTAMP, ip_address = ? WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $ip_address, $email);
        
        if ($update_stmt->execute()) {
            $_SESSION['subscribe_success'] = 'Welcome back! You have been re-subscribed to our newsletter.';
        } else {
            $_SESSION['subscribe_error'] = 'An error occurred. Please try again later.';
        }
        $update_stmt->close();
    }
    $check_stmt->close();
} else {
    // Insert new subscriber into 'subscribers' table
    $insert_sql = "INSERT INTO subscribers (email, ip_address, subscribed_at, is_active) VALUES (?, ?, NOW(), 1)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ss", $email, $ip_address);
    
    if ($insert_stmt->execute()) {
        $_SESSION['subscribe_success'] = 'Thank you for subscribing! You\'ll receive updates about new uniform listings.';
    } else {
        $_SESSION['subscribe_error'] = 'Database error: ' . $conn->error;
    }
    $insert_stmt->close();
}

$conn->close();

// Redirect back to previous page
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit();
?>