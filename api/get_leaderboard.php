<?php
// api/get_leaderboard.php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

// Παίρνουμε τους top 5 παίκτες με τις περισσότερες νίκες
$sql = "SELECT username, games_won, games_lost, games_played 
        FROM users 
        WHERE username != 'bot'
        ORDER BY games_won DESC, games_played DESC 
        LIMIT 5";

$result = $mysqli->query($sql);

if (!$result) {
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    exit;
}

$leaderboard = [];
$rank = 1;

while ($row = $result->fetch_assoc()) {
    $leaderboard[] = [
        'rank' => $rank++,
        'username' => $row['username'],
        'wins' => intval($row['games_won']),
        'losses' => intval($row['games_lost']),
        'total' => intval($row['games_played'])
    ];
}

echo json_encode([
    'status' => 'success',
    'leaderboard' => $leaderboard
]);
?>
