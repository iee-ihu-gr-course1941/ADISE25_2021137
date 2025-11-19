<?php
// api/cancel_match.php
require_once '../db.php';

header('Content-Type: application/json');

// 1. Βασικός έλεγχος Session & Input
// Ο χρήστης πρέπει να είναι συνδεδεμένος και να έχει σταλεί το game_id
if (!isset($_SESSION['user_id']) || !isset($_POST['game_id'])) {
    echo json_encode(['error' => 'Missing user ID or game ID.']);
    exit;
}

$game_id = intval($_POST['game_id']);
$quitter_id = $_SESSION['user_id'];

// 2. Βρες το παιχνίδι και τον δημιουργό του
$stmt = $mysqli->prepare("SELECT game_status, player_1_id FROM games WHERE id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Game not found.']);
    exit;
}
$game_info = $result->fetch_assoc();
$stmt->close();

// 3. Έλεγχος: Πρέπει να είναι 'waiting' και ο χρήστης να είναι ο P1 (ο δημιουργός)
if ($game_info['game_status'] !== 'waiting' || $game_info['player_1_id'] != $quitter_id) {
    echo json_encode(['error' => 'Cannot cancel. Game is already active or you are not the creator.']);
    exit;
}

// 4. Διαγραφή καρτών και παιχνιδιού (για καθαριότητα)
// Διαγράφουμε πρώτα τις κάρτες
$mysqli->query("DELETE FROM game_cards WHERE game_id = $game_id");

// Διαγράφουμε το παιχνίδι
$mysqli->query("DELETE FROM games WHERE id = $game_id");

// 5. Καθάρισε το session
if (isset($_SESSION['game_id']) && $_SESSION['game_id'] == $game_id) {
    unset($_SESSION['game_id']);
    unset($_SESSION['player_side']);
}

echo json_encode(['status' => 'success', 'message' => 'Η αναζήτηση αντιπάλου ακυρώθηκε.']);
?>