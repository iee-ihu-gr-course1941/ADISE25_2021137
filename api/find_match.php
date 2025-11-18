<?php
// api/find_match.php
require_once '../db.php';
require_once 'functions.php';

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");

// 1. Ψάχνουμε για παιχνίδι που είναι 'waiting' και 'pvp'
// ΠΡΟΣΟΧΗ: Βάζουμε FOR UPDATE για να κλειδώσουμε τη γραμμή και να μην μπουν 2 άτομα ταυτόχρονα
$sql_search = "SELECT id FROM games WHERE game_status = 'waiting' AND game_mode = 'pvp' LIMIT 1 FOR UPDATE";
$result = $mysqli->query($sql_search);

if ($result->num_rows > 0) {
    // --- ΣΕΝΑΡΙΟ Α: ΒΡΕΘΗΚΕ ΠΑΙΧΝΙΔΙ (JOIN) ---
    $row = $result->fetch_assoc();
    $game_id = $row['id'];

    // Μπαίνουμε ως Παίκτης 2 και το κάνουμε 'active'
    $mysqli->query("UPDATE games SET game_status = 'active' WHERE id = $game_id");

    // Session: Είμαστε ο Παίκτης 2
    $_SESSION['player_side'] = 2;
    $_SESSION['game_id'] = $game_id;

    // Δεν μοιράζουμε χαρτιά (έχουν μοιραστεί ήδη κατά τη δημιουργία)

    echo json_encode([
        'status' => 'joined',
        'game_id' => $game_id,
        'player_side' => 2,
        'message' => 'Βρέθηκε αντίπαλος! Το παιχνίδι ξεκινάει.'
    ]);

} else {
    // --- ΣΕΝΑΡΙΟ Β: ΔΕΝ ΒΡΕΘΗΚΕ (ΔΗΜΙΟΥΡΓΙΑ ΝΕΟΥ) ---
    
    // ΕΔΩ ΗΤΑΝ ΤΟ ΛΑΘΟΣ: Χρησιμοποιούσαμε την create_game που έβαζε 'active'.
    // Τώρα γράφουμε το INSERT χειροκίνητα για να βάλουμε 'waiting'.
    
    $sql_new = "INSERT INTO games (player_1_id, game_status, current_turn_id, game_mode) 
                VALUES (1, 'waiting', 1, 'pvp')";
    
    if ($mysqli->query($sql_new)) {
        $game_id = $mysqli->insert_id;

        // Φτιάχνουμε τράπουλα
        $deck = generate_shuffled_deck();
        save_deck_to_db($mysqli, $game_id, $deck);

        // Μοιράζουμε τα χαρτιά ΤΩΡΑ (ώστε να είναι έτοιμα και για τους δύο)
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