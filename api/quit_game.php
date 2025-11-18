<?php
// api/quit_game.php
require_once '../db.php';
require_once 'functions.php'; // Χρειαζόμαστε την update_user_stats

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['game_id']) || !isset($_POST['player_side'])) {
    echo json_encode(['error' => 'Missing data or session.']);
    exit;
}

$game_id = intval($_POST['game_id']);
$quitter_id = $_SESSION['user_id'];
$quitter_side = intval($_POST['player_side']); // 1 or 2
$winner_side = ($quitter_side == 1) ? 2 : 1;
$loser_side = $quitter_side;

// 1. Φέρνουμε τα IDs των παικτών και το game_mode
$sql_game_info = "SELECT id, player_1_id, player_2_id, game_mode, stats_updated FROM games WHERE id = $game_id";
$game_info = $mysqli->query($sql_game_info)->fetch_assoc();

// 2. Ελέγχουμε αν είναι PvP και αν δεν έχει ήδη ενημερωθεί.
if ($game_info && $game_info['game_mode'] === 'pvp' && $game_info['stats_updated'] == 0) {
    
    // Δίνουμε απώλεια στον quitter και νίκη στον αντίπαλο (μόνο αν ο αντίπαλος είναι human player)
    $winner_user_id = ($winner_side == 1) ? $game_info['player_1_id'] : $game_info['player_2_id'];
    $loser_user_id = ($loser_side == 1) ? $game_info['player_1_id'] : $game_info['player_2_id'];

    if ($winner_user_id && $loser_user_id) { // Ελέγχουμε ότι και οι δύο είναι human users
        
        // Ενημέρωση Quitter (Loss)
        $mysqli->query("UPDATE users SET losses = losses + 1 WHERE id = $loser_user_id");

        // Ενημέρωση Winner (Win)
        $mysqli->query("UPDATE users SET wins = wins + 1 WHERE id = $winner_user_id");

        // Σημειώνουμε το παιχνίδι ως ενημερωμένο
        $mysqli->query("UPDATE games SET stats_updated = 1 WHERE id = $game_id");
    }
}

// 3. Τερματίζουμε το παιχνίδι. Ορίζουμε τον νικητή (από εγκατάλειψη) ως last_collector
$mysqli->query("UPDATE games SET game_status = 'finished', last_collector_id = $winner_side WHERE id = $game_id");

// 4. Καθάρισε το session του παιχνιδιού
if (isset($_SESSION['game_id']) && $_SESSION['game_id'] == $game_id) {
    unset($_SESSION['game_id']);
    unset($_SESSION['player_side']);
}

echo json_encode(['status' => 'success', 'message' => 'Το παιχνίδι τερματίστηκε. Μετρήθηκε ήττα.']);
?>