<?php
// api/quit_game.php
require_once '../db.php';

header('Content-Type: application/json');

// 1. Βασικός έλεγχος Session & Input
if (!isset($_SESSION['user_id']) || !isset($_POST['game_id']) || !isset($_POST['player_side'])) {
    echo json_encode(['error' => 'Missing data.']);
    exit;
}

$game_id = intval($_POST['game_id']);
$quitter_id = $_SESSION['user_id'];
$quitter_side = intval($_POST['player_side']); // 1 or 2

// 2. Ενημέρωσε το status του παιχνιδιού σε 'finished'.
$sql_game_info = "SELECT game_mode, player_1_id, player_2_id FROM games WHERE id = $game_id";
$game_res = $mysqli->query($sql_game_info)->fetch_assoc();

if ($game_res && $game_res['game_mode'] === 'pvp') {
    // Αν είναι PvP, δίνουμε τη νίκη στον αντίπαλο (winner_side)
    $winner_side = ($quitter_side == 1) ? 2 : 1;
    
    // Ορίζουμε τον αντίπαλο ως τον "τελευταίο συλλέκτη" για να πάρει τα τυχόν χαρτιά του τραπεζιού
    $mysqli->query("UPDATE games SET game_status = 'finished', last_collector_id = $winner_side WHERE id = $game_id");
    
} else {
    // PvE ή απλό τελείωμα
    $mysqli->query("UPDATE games SET game_status = 'finished' WHERE id = $game_id");
}

// 3. Καθάρισε το session του παιχνιδιού
if (isset($_SESSION['game_id']) && $_SESSION['game_id'] == $game_id) {
    unset($_SESSION['game_id']);
    unset($_SESSION['player_side']);
}

echo json_encode(['status' => 'success', 'message' => 'Το παιχνίδι τερματίστηκε.']);
?>