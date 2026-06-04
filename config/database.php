<?php
// Database configuration for UniformMarket
// South African School Uniform Marketplace

define('DB_HOST', 'sql100.ezyro.com'); // Your database host
define('DB_USER', 'ezyro_41534324'); // Your database username
define('DB_PASS', '4c3a0db192e2'); // Your database password
define('DB_NAME', 'ezyro_41534324_uniformly'); // Your database name

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Set timezone to South Africa
date_default_timezone_set('Africa/Johannesburg');

// Function to escape strings for safe database queries
function escape_string($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

// Function to get user by ID
function get_user_by_id($user_id) {
    global $conn;
    $sql = "SELECT user_id, email, first_name, last_name, phone, user_type, is_verified, is_active, created_at 
            FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to check if email exists
function email_exists($email) {
    global $conn;
    $sql = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}
?>