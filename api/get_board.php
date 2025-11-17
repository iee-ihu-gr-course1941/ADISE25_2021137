<?php
// api/get_board.php

require_once '../db.php';

header('Content-Type: application/json'); // Λέμε στον browser ότι στέλνουμε δεδομένα JSON

// 1. Έλεγχος: Μας έδωσε η JS το ID του παιχνιδιού;
if (!isset($_GET['game_id'])) {
    echo json_encode(['error' => 'No game_id provided']);
    exit;
}

$game_id = intval($_GET['game_id']); // Καθαρισμός για ασφάλεια (να είναι σίγουρα νούμερο)
$my_player_id = 1; // Προσωρινά λέμε ότι είμαστε ο Παίκτης 1

// ------------------------------------------------
// Α. Χαρτιά στο Τραπέζι
// ------------------------------------------------
$sql_table = "SELECT card_code FROM game_cards WHERE game_id = $game_id AND card_position = 'table' ORDER BY card_order ASC";
$result_table = $mysqli->query($sql_table);

$table_cards = [];
while ($row = $result_table->fetch_assoc()) {
    $table_cards[] = $row['card_code']; // Π.χ. ['C10', 'H5', 'S2']
}

// ------------------------------------------------
// Β. Τα Χαρτιά ΜΟΥ (Player 1)
// ------------------------------------------------
// Χρειαζόμαστε και το ID της εγγραφής για να ξέρουμε ποιο χαρτί θα παίξουμε
$sql_hand = "SELECT id, card_code FROM game_cards WHERE game_id = $game_id AND card_position = 'hand_p1' ORDER BY card_order ASC";
$result_hand = $mysqli->query($sql_hand);

$my_cards = [];
while ($row = $result_hand->fetch_assoc()) {
    $my_cards[] = [
        'id' => $row['id'],        // Το μοναδικό ID στη βάση (χρήσιμο για το κλικ)
        'code' => $row['card_code'] // Ο κωδικός της εικόνας
    ];
}

// ------------------------------------------------
// Γ. Τα Χαρτιά του ΑΝΤΙΠΑΛΟΥ (Player 2)
// ------------------------------------------------
// ΠΡΟΣΟΧΗ: Εδώ μετράμε ΜΟΝΟ πόσα είναι (COUNT). Δεν στέλνουμε τα χαρτιά!
$sql_opp = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND card_position = 'hand_p2'";
$result_opp = $mysqli->query($sql_opp);
$row_opp = $result_opp->fetch_assoc();
$opponent_cards_count = $row_opp['count'];


// ------------------------------------------------
// Δ. Αποστολή Απάντησης
// ------------------------------------------------
$response = [
    'table' => $table_cards,
    'my_hand' => $my_cards,
    'opponent_cards_count' => $opponent_cards_count
];

echo json_encode($response);
?>