<?php
header('Content-Type: application/json');
require_once 'config/database.php';

// Get count from 'subscribers' table
$sql = "SELECT COUNT(*) as count FROM subscribers WHERE is_active = 1";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(['count' => $row['count'] ?? 0]);
} else {
    echo json_encode(['count' => 0, 'error' => $conn->error]);
}

$conn->close();
?>