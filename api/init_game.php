<?php
// api/init_game.php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';  

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$my_id = $_SESSION['user_id'];
$mode = isset($_POST['mode']) ? $_POST['mode'] : 'pve';

// 1. Δημιουργία παιχνιδιού (με bot ως player2)
$sql = "INSERT INTO games (player1_id, player2_id, status, current_player) 
        VALUES ($my_id, NULL, 'active', 1)";

if (!$mysqli->query($sql)) {
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    exit;
}

$game_id = $mysqli->insert_id;

// 2. Φτιάξε τράπουλα
$deck = generate_shuffled_deck();

// 3. Αποθήκευσε
save_deck_to_db($mysqli, $game_id, $deck);

// 4. Μοίρασε
deal_initial_cards($mysqli, $game_id);

echo json_encode([
    "status" => "success", 
    "game_id" => $game_id, 
    "mode" => $mode,
    "player_side" => 1,
    "message" => "Game initialized!"
]);
?>