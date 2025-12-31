<?php
// api/cancel_match.php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

// 1. Βασικός έλεγχος Session & Input
// Ο χρήστης πρέπει να είναι συνδεδεμένος και να έχει σταλεί το game_id
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in. User ID: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NONE')]);
    exit;
}

if (!isset($_POST['game_id'])) {
    echo json_encode(['error' => 'Missing game ID.']);
    exit;
}

$game_id = intval($_POST['game_id']);
$quitter_id = intval($_SESSION['user_id']);

// 2. Βρες το παιχνίδι και τον δημιουργό του
$stmt = $mysqli->prepare("SELECT status, player1_id FROM games WHERE id = ?");
if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    exit;
}
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
if ($game_info['status'] !== 'waiting' || intval($game_info['player1_id']) !== $quitter_id) {
    echo json_encode(['error' => 'Cannot cancel. Game is already active or you are not the creator.']);
    exit;
}

// 4. Διαγραφή παιχνιδιού και αφαίρεση από matchmaking_queue
$delete_result = $mysqli->query("DELETE FROM games WHERE id = $game_id");
if (!$delete_result) {
    echo json_encode(['error' => 'Database delete error: ' . $mysqli->error]);
    exit;
}

// Αφαίρεση από matchmaking_queue
$mysqli->query("DELETE FROM matchmaking_queue WHERE user_id = $quitter_id");

// 5. Καθάρισε το session
if (isset($_SESSION['game_id']) && $_SESSION['game_id'] == $game_id) {
    unset($_SESSION['game_id']);
    unset($_SESSION['player_side']);
}

echo json_encode(['status' => 'success', 'message' => 'Η αναζήτηση αντιπάλου ακυρώθηκε.']);
?>