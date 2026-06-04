<?php
session_start();
require_once 'config/database.php';

$email = isset($_GET['email']) ? filter_var(trim($_GET['email']), FILTER_VALIDATE_EMAIL) : null;
$token = isset($_GET['token']) ? $_GET['token'] : null;

// If email and token are provided (from email link)
if ($email && $token) {
    // Verify token (simple hash of email + secret key)
    $expected_token = md5($email . 'YOUR_SECRET_KEY_HERE');
    
    if ($token === $expected_token) {
        $update_sql = "UPDATE subscribers SET is_active = 0 WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("s", $email);
        
        if ($update_stmt->execute()) {
            $_SESSION['subscribe_message'] = 'You have been unsubscribed from our newsletter.';
        } else {
            $_SESSION['subscribe_error'] = 'An error occurred. Please try again.';
        }
        $update_stmt->close();
    } else {
        $_SESSION['subscribe_error'] = 'Invalid unsubscribe token.';
    }
} 
// If POST request from unsubscribe form
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        $update_sql = "UPDATE subscribers SET is_active = 0 WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("s", $email);
        
        if ($update_stmt->execute()) {
            $_SESSION['subscribe_message'] = 'You have been unsubscribed from our newsletter.';
        } else {
            $_SESSION['subscribe_error'] = 'An error occurred. Please try again.';
        }
        $update_stmt->close();
    } else {
        $_SESSION['subscribe_error'] = 'Please enter a valid email address.';
    }
}

$conn->close();

// Redirect to home page
header('Location: index.php');
exit();
?>