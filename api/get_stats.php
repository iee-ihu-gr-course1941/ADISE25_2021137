<?php
// api/get_stats.php
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'error' => 'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Χρησιμοποιούμε mysqli prepare
$stmt = $mysqli->prepare("SELECT wins, losses, draws FROM users WHERE id = ?");

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'error' => 'Prepare failed: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();

if ($stats) {
    echo json_encode(['status' => 'success', 'stats' => $stats]);
} else {
    echo json_encode(['status' => 'error', 'error' => 'User not found']);
}
?>