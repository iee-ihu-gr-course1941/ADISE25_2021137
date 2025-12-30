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

// 1. Φέρνουμε τα IDs και το status των παικτών
$sql_game_info = "SELECT id, player1_id, player2_id, status FROM games WHERE id = $game_id";
$game_info = $mysqli->query($sql_game_info)->fetch_assoc();

// Αν το παιχνίδι έχει ήδη τελειώσει, μην ενημερώσεις στατιστικά ξανά
if ($game_info && $game_info['status'] === 'finished') {
    echo json_encode(['status' => 'error', 'message' => 'Το παιχνίδι έχει ήδη τελειώσει.']);
    exit;
}

// 2. Έλεγχος αν παίζει με Bot (PvE) ή με άνθρωπο (PvP)
$is_pve = ($game_info && $game_info['player2_id'] === null);

// 3. Ενημέρωση στατιστικών (μόνο αν υπάρχουν και οι 2 παίκτες και το παιχνίδι είναι ενεργό - PvP)
if ($game_info && $game_info['player2_id'] && $game_info['status'] === 'active') {
    
    $winner_user_id = ($winner_side == 1) ? $game_info['player1_id'] : $game_info['player2_id'];
    $loser_user_id = ($loser_side == 1) ? $game_info['player1_id'] : $game_info['player2_id'];

    if ($winner_user_id && $loser_user_id) {
        
        // Ενημέρωση Quitter (Loss)
        $mysqli->query("UPDATE users SET games_lost = games_lost + 1, games_played = games_played + 1 WHERE id = $loser_user_id");

        // Ενημέρωση Winner (Win)
        $mysqli->query("UPDATE users SET games_won = games_won + 1, games_played = games_played + 1 WHERE id = $winner_user_id");
    }
}

// 4. Τερματίζουμε το παιχνίδι. Ορίζουμε τον νικητή (από εγκατάλειψη) ως last_to_collect
$mysqli->query("UPDATE games SET status = 'finished', last_to_collect = $winner_side WHERE id = $game_id");

// 5. Καθάρισε το session του παιχνιδιού
if (isset($_SESSION['game_id']) && $_SESSION['game_id'] == $game_id) {
    unset($_SESSION['game_id']);
    unset($_SESSION['player_side']);
}

// 6. Μήνυμα ανάλογα με το mode
if ($is_pve) {
    echo json_encode(['status' => 'success', 'message' => 'Έξοδος από την προπόνηση. Το παιχνίδι δεν μετράει στα στατιστικά σου.']);
} else {
    echo json_encode(['status' => 'success', 'message' => 'Το παιχνίδι τερματίστηκε. Μετρήθηκε ήττα.']);
}
?>