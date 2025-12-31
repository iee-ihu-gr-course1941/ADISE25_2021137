<?php
// api/player_disconnect.php
// Καλείται όταν ο παίκτης κλείνει το tab/browser
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

// Παίρνουμε το game_id από POST ή session
$game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : (isset($_SESSION['game_id']) ? intval($_SESSION['game_id']) : 0);

// Έλεγχος αν είναι συνδεδεμένος
if (!isset($_SESSION['user_id']) || $game_id === 0) {
    echo json_encode(['error' => 'Not in a game']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$player_side = isset($_SESSION['player_side']) ? intval($_SESSION['player_side']) : 0;

// Βρες το παιχνίδι
$result = $mysqli->query("SELECT * FROM games WHERE id = $game_id AND status = 'active'");
if (!$result || $result->num_rows === 0) {
    echo json_encode(['error' => 'Game not found or not active']);
    exit;
}

$game = $result->fetch_assoc();

// Βεβαιώσου ότι είναι PvP παιχνίδι
if ($game['player2_id'] === null) {
    echo json_encode(['error' => 'Not a PvP game']);
    exit;
}

// Βεβαιώσου ότι ο χρήστης είναι σε αυτό το παιχνίδι
$is_p1 = intval($game['player1_id']) === $user_id;
$is_p2 = intval($game['player2_id']) === $user_id;

if (!$is_p1 && !$is_p2) {
    echo json_encode(['error' => 'Not a player in this game']);
    exit;
}

// Ο νικητής είναι ο αντίπαλος
$winner_side = $is_p1 ? 2 : 1;
$winner_user_id = $is_p1 ? intval($game['player2_id']) : intval($game['player1_id']);
$loser_user_id = $user_id;

// Ενημέρωση του παιχνιδιού ως finished
$update_result = $mysqli->query(
    "UPDATE games SET status = 'finished', last_to_collect = $winner_side 
     WHERE id = $game_id AND status = 'active'"
);

if ($update_result && $mysqli->affected_rows === 1) {
    // Ενημέρωση στατιστικών
    $mysqli->query("UPDATE users SET games_lost = games_lost + 1, games_played = games_played + 1 WHERE id = $loser_user_id");
    $mysqli->query("UPDATE users SET games_won = games_won + 1, games_played = games_played + 1 WHERE id = $winner_user_id");
    
    // Αφαίρεση από matchmaking queue αν υπάρχει
    $mysqli->query("DELETE FROM matchmaking_queue WHERE user_id = $user_id");
    
    // Καθαρισμός session
    unset($_SESSION['game_id']);
    unset($_SESSION['player_side']);
    
    echo json_encode(['status' => 'success', 'message' => 'Disconnect registered']);
} else {
    echo json_encode(['error' => 'Could not update game']);
}
?>
