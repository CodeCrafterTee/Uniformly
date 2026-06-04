<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login with return URL
    $redirect = urlencode('shop.php');
    header("Location: login.php?redirect=$redirect");
    exit();
}

$user_id = $_SESSION['user_id'];
$listing_id = isset($_GET['listing_id']) ? intval($_GET['listing_id']) : 0;

if ($listing_id <= 0) {
    header('Location: shop.php');
    exit();
}

// Check if item exists and is active
$check_sql = "SELECT listing_id, title, price FROM listings WHERE listing_id = ? AND status = 'active'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $listing_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    header('Location: shop.php?error=item_not_available');
    exit();
}
$check_stmt->close();

// Check if item already in cart
$cart_sql = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND listing_id = ?";
$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param("ii", $user_id, $listing_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows > 0) {
    // Update quantity
    $cart_item = $cart_result->fetch_assoc();
    $new_quantity = $cart_item['quantity'] + 1;
    $update_sql = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND listing_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iii", $new_quantity, $user_id, $listing_id);
    $update_stmt->execute();
    $update_stmt->close();
    $message = "Item quantity updated in cart!";
} else {
    // Add to cart
    $insert_sql = "INSERT INTO cart (user_id, listing_id, quantity) VALUES (?, ?, 1)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ii", $user_id, $listing_id);
    $insert_stmt->execute();
    $insert_stmt->close();
    $message = "Item added to cart!";
}
$cart_stmt->close();

// Redirect back to shop or cart
$redirect_to = isset($_GET['redirect']) ? $_GET['redirect'] : 'shop';
if ($redirect_to === 'cart') {
    header("Location: cart.php?success=" . urlencode($message));
} else {
    header("Location: shop.php?success=" . urlencode($message));
}
exit();
?>