<?php
// api/find_match.php
require_once '../db.php';
require_once 'functions.php';

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");

// Πρέπει να είναι συνδεδεμένος
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
$my_id = $_SESSION['user_id']; 

// 1. Ψάχνουμε για παιχνίδι που είναι 'waiting' και 'pvp'
// Χρησιμοποιούμε FOR UPDATE για να κλειδώσουμε τη γραμμή (αποφυγή race condition)
$sql_search = "SELECT id, player_1_id FROM games WHERE game_status = 'waiting' AND game_mode = 'pvp' LIMIT 1 FOR UPDATE";
$result = $mysqli->query($sql_search);

if ($result->num_rows > 0) {
    // --- ΣΕΝΑΡΙΟ Α: ΒΡΕΘΗΚΕ ΠΑΙΧΝΙΔΙ ---
    $row = $result->fetch_assoc();
    $game_id = $row['id'];
    $p1_id = $row['player_1_id'];

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
    // Ενημερώνουμε το player_2_id και το status σε 'active' ΜΟΝΟ αν το player_2_id είναι NULL
    $sql_update = "UPDATE games 
                   SET game_status = 'active', 
                       player_2_id = $my_id 
                   WHERE id = $game_id AND player_2_id IS NULL";

    if ($mysqli->query($sql_update) && $mysqli->affected_rows > 0) {
        // Επιτυχία Join
        $_SESSION['player_side'] = 2;
        $_SESSION['game_id'] = $game_id;

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
    
    $sql_new = "INSERT INTO games (player_1_id, game_status, current_turn_id, game_mode) 
                VALUES ($my_id, 'waiting', 1, 'pvp')";
    
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