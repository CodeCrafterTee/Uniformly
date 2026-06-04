<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . $_SERVER['HTTP_REFERER']);
    exit();
}

$user_id = $_SESSION['user_id'];
$listing_id = intval($_GET['listing_id'] ?? 0);

if ($listing_id > 0) {
    // Check if already saved
    $check_sql = "SELECT saved_id FROM saved_items WHERE user_id = ? AND listing_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $listing_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows == 0) {
        $insert_sql = "INSERT INTO saved_items (user_id, listing_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ii", $user_id, $listing_id);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_stmt->close();
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>