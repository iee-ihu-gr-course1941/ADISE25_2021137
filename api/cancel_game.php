<?php
// api/cancel_game.php
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_POST['game_id']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Missing data or session.']);
    exit;
}

$game_id = intval($_POST['game_id']);
$user_id = intval($_SESSION['user_id']);

// Διαγράφουμε το παιχνίδι ΜΟΝΟ αν ο χρήστης είναι ο P1 (ο δημιουργός) και το status είναι 'waiting'.
$mysqli->query("DELETE FROM games WHERE id = $game_id AND player_1_id = $user_id AND game_status = 'waiting'");

// Διαγράφουμε και τυχόν κάρτες (Ασφάλεια)
$mysqli->query("DELETE FROM game_cards WHERE game_id = $game_id");

echo json_encode(['status' => 'success', 'message' => 'Search cancelled.']);
?>