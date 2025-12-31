<?php
// api/find_match.php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");

// Πρέπει να είναι συνδεδεμένος
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
$my_id = $_SESSION['user_id']; 

// Δημιουργία πίνακα matchmaking_queue αν δεν υπάρχει
$mysqli->query("
    CREATE TABLE IF NOT EXISTS matchmaking_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// 1. Ψάχνουμε για παιχνίδι που είναι 'waiting' (PvP matchmaking)
// Χρησιμοποιούμε FOR UPDATE για να κλειδώσουμε τη γραμμή (αποφυγή race condition)
$sql_search = "SELECT id, player1_id FROM games WHERE status = 'waiting' AND player2_id IS NULL LIMIT 1 FOR UPDATE";
$result = $mysqli->query($sql_search);

if ($result->num_rows > 0) {
    // --- ΣΕΝΑΡΙΟ Α: ΒΡΕΘΗΚΕ ΠΑΙΧΝΙΔΙ ---
    $row = $result->fetch_assoc();
    $game_id = $row['id'];
    $p1_id = $row['player1_id'];

    // 1. Εάν είμαι ο P1 (ο δημιουργός), απλά επιβεβαιώνω ότι περιμένω
    if ($p1_id == $my_id) {
        // Ενημερώνουμε το session του P1 (δεν αλλάζει τίποτα)
        $_SESSION['player_side'] = 1;
        $_SESSION['game_id'] = $game_id;
        
        echo json_encode([
            'status' => 'waiting',
            'game_id' => $game_id,
            'player_side' => 1,
            'message' => 'Περιμένουμε ακόμα...'
        ]);
        exit;
    }

    // 2. Είμαι P2: Κάνω join
    // Ενημερώνουμε το player2_id και το status σε 'active' ΜΟΝΟ αν το player2_id είναι NULL
    $sql_update = "UPDATE games 
                   SET status = 'active', 
                       player2_id = $my_id
                   WHERE id = $game_id AND player2_id IS NULL";

    if ($mysqli->query($sql_update) && $mysqli->affected_rows > 0) {
        // Επιτυχία Join
        $_SESSION['player_side'] = 2;
        $_SESSION['game_id'] = $game_id;

        // Αφαίρεση του P1 από το matchmaking_queue
        $mysqli->query("DELETE FROM matchmaking_queue WHERE user_id = $p1_id");

        echo json_encode([
            'status' => 'joined',
            'game_id' => $game_id,
            'player_side' => 2,
            'message' => 'Βρέθηκε αντίπαλος! Το παιχνίδι ξεκινάει.'
        ]);
    } else {
        // Το παιχνίδι βρέθηκε, αλλά δεν μπορούμε να μπούμε (π.χ. κάποιος άλλος πρόλαβε)
        echo json_encode([
            'error' => 'Το παιχνίδι βρέθηκε, αλλά είναι ήδη γεμάτο ή υπήρξε σφάλμα. Δοκιμάστε ξανά.',
            'status' => 'error'
        ]);
    }

} else {
    // --- ΣΕΝΑΡΙΟ Β: ΔΕΝ ΒΡΕΘΗΚΕ (ΔΗΜΙΟΥΡΓΙΑ ΝΕΟΥ) ---
    
    $sql_new = "INSERT INTO games (player1_id, status, current_player) 
                VALUES ($my_id, 'waiting', 1)";
    
    if ($mysqli->query($sql_new)) {
        $game_id = $mysqli->insert_id;

        // Φτιάχνουμε τράπουλα
        $deck = generate_shuffled_deck();
        save_deck_to_db($mysqli, $game_id, $deck);

        // Μοιράζουμε τα χαρτιά
        deal_initial_cards($mysqli, $game_id);

        // Session: Είμαστε ο Παίκτης 1
        $_SESSION['player_side'] = 1;
        $_SESSION['game_id'] = $game_id;

        // Προσθήκη στο matchmaking_queue
        $mysqli->query("INSERT INTO matchmaking_queue (user_id) VALUES ($my_id) ON DUPLICATE KEY UPDATE joined_at = NOW()");

        echo json_encode([
            'status' => 'waiting',
            'game_id' => $game_id,
            'player_side' => 1,
            'message' => 'Περιμένουμε αντίπαλο...'
        ]);
    } else {
        echo json_encode(['error' => 'Database Error: ' . $mysqli->error]);
    }
}
?>