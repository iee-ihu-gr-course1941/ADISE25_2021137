<?php
// api/functions.php

// 1. Συνάρτηση που φτιάχνει το παιχνίδι στη βάση
function create_game($mysqli) {
    $sql = "INSERT INTO games (player_1_id, game_status) VALUES (1, 'active')";
    if ($mysqli->query($sql)) {
        return $mysqli->insert_id; // Επιστρέφει το ID του νέου παιχνιδιού (π.χ. 5)
    } else {
        die("Error creating game: " . $mysqli->error);
    }
}

// 2. Συνάρτηση που δημιουργεί την τράπουλα (Array) και την ανακατεύει
function generate_shuffled_deck() {
    $suits = ['C', 'D', 'H', 'S'];
    $numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];
    $deck = [];

    foreach ($suits as $suit) {
        foreach ($numbers as $number) {
            $deck[] = $suit . $number;
        }
    }
    shuffle($deck);
    return $deck; // Επιστρέφει τον ανακατεμένο πίνακα
}

// 3. Συνάρτηση που αποθηκεύει την τράπουλα στη βάση
function save_deck_to_db($mysqli, $game_id, $deck) {
    $values = [];
    for ($i = 0; $i < count($deck); $i++) {
        $card_code = $deck[$i];
        $values[] = "($game_id, '$card_code', 'deck', $i)";
    }
    
    $sql_cards = "INSERT INTO game_cards (game_id, card_code, card_position, card_order) VALUES " . implode(', ', $values);
    
    if (!$mysqli->query($sql_cards)) {
        die("Error saving cards: " . $mysqli->error);
    }
}

// 4. Συνάρτηση που μοιράζει τα αρχικά χαρτιά
function deal_initial_cards($mysqli, $game_id) {
    // 4 χαρτιά στο τραπέζι
    $mysqli->query("UPDATE game_cards SET card_position = 'table' WHERE game_id = $game_id AND card_position = 'deck' ORDER BY card_order ASC LIMIT 4");
    
    // 6 χαρτιά στον Παίκτη 1
    $mysqli->query("UPDATE game_cards SET card_position = 'hand_p1' WHERE game_id = $game_id AND card_position = 'deck' ORDER BY card_order ASC LIMIT 6");
    
    // 6 χαρτιά στον Παίκτη 2
    $mysqli->query("UPDATE game_cards SET card_position = 'hand_p2' WHERE game_id = $game_id AND card_position = 'deck' ORDER BY card_order ASC LIMIT 6");
}
?>