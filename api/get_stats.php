<?php
// api/get_stats.php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'error' => 'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Χρησιμοποιούμε mysqli prepare
$stmt = $mysqli->prepare("SELECT games_played, games_won, games_lost FROM users WHERE id = ?");

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'error' => 'Prepare failed: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_stats = $result->fetch_assoc();
$stmt->close();

if ($user_stats) {
    // Μετατροπή σε format που περιμένει το frontend
    $stats = [
        'wins' => $user_stats['games_won'],
        'losses' => $user_stats['games_lost'],
        'draws' => 0, // Δεν έχουμε draws ακόμα
        'total' => $user_stats['games_played']
    ];
    echo json_encode(['status' => 'success', 'stats' => $stats]);
} else {
    echo json_encode(['status' => 'error', 'error' => 'User not found']);
}
?>