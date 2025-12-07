<?php
// api/functions.php

// 1. Συνάρτηση που φτιάχνει το παιχνίδι στη βάση
function create_game($mysqli, $player_id, $mode = 'pve') {
    // Εισάγουμε και το game_mode στη βάση. Ξεκινάει πάντα ο παίκτης 1.
    $sql = "INSERT INTO games (player_1_id, game_status, current_turn_id, game_mode) VALUES ($player_id, 'active', 1, '$mode')";
    
    if ($mysqli->query($sql)) {
        return $mysqli->insert_id; 
    } else {
        die("Error creating game: " . $mysqli->error);
    }
}

// 2. Συνάρτηση που δημιουργεί την τράπουλα (PHP Array)
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
    return $deck; 
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

// 4. Συνάρτηση για το ΠΡΩΤΟ μοίρασμα (Αρχή παιχνιδιού)
function deal_initial_cards($mysqli, $game_id) {
    // 4 χαρτιά στο τραπέζι (ΜΟΝΟ ΣΤΗΝ ΑΡΧΗ)
    $mysqli->query("UPDATE game_cards SET card_position = 'table' WHERE game_id = $game_id AND card_position = 'deck' ORDER BY card_order ASC LIMIT 4");
    
    // 6 χαρτιά στον Παίκτη 1
    $mysqli->query("UPDATE game_cards SET card_position = 'hand_p1' WHERE game_id = $game_id AND card_position = 'deck' ORDER BY card_order ASC LIMIT 6");
    
    // 6 χαρτιά στον Παίκτη 2
    $mysqli->query("UPDATE game_cards SET card_position = 'hand_p2' WHERE game_id = $game_id AND card_position = 'deck' ORDER BY card_order ASC LIMIT 6");
}

// ---------------------------------------------------------
// 5. ΣΥΝΑΡΤΗΣΗ ΕΛΕΓΧΟΥ & ΕΠΑΝΑΛΗΠΤΙΚΟΥ ΜΟΙΡΑΣΜΑΤΟΣ
// ---------------------------------------------------------
function check_and_redeal($mysqli, $game_id) {
    
    // Α. Ελέγχουμε: Έχουν μείνει χαρτιά στα χέρια των παικτών;
    $sql_check = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND (card_position = 'hand_p1' OR card_position = 'hand_p2')";
    $result = $mysqli->query($sql_check);
    $cards_in_hands = $result->fetch_assoc()['count'];

    // Αν υπάρχουν ακόμα χαρτιά στα χέρια έστω και ενός παίκτη, δεν κάνουμε τίποτα.
    if ($cards_in_hands > 0) {
        return false;
    }

    // Β. Αν τα χέρια είναι άδεια, ελέγχουμε αν έχει μείνει τράπουλα (deck)
    $sql_deck = "SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND card_position = 'deck'";
    $deck_count = $mysqli->query($sql_deck)->fetch_assoc()['count'];

    // -----------------------------------------------------
    // Γ. GAME OVER (Τέλος Παιχνιδιού - Τελείωσε η τράπουλα)
    // -----------------------------------------------------
    if ($deck_count == 0) {
        
        // 1. Βρες ποιος μάζεψε τελευταίος (από τη βάση)
        $res = $mysqli->query("SELECT last_collector_id FROM games WHERE id = $game_id");
        $row = $res->fetch_assoc();
        
        if ($row && $row['last_collector_id']) {
            $last_collector = $row['last_collector_id'];
            $score_col = "score_p" . $last_collector; // score_p1 ή score_p2
            
            // 2. Δώσε του ό,τι έμεινε στο τραπέζι
            $mysqli->query("UPDATE game_cards SET card_position = '$score_col' WHERE game_id = $game_id AND card_position = 'table'");
        }

        // 3. Κλείσε το παιχνίδι (Status = finished)
        $mysqli->query("UPDATE games SET game_status = 'finished' WHERE id = $game_id");
        
        return "finished"; 
    }

    // -----------------------------------------------------
    // Δ. ΜΟΙΡΑΖΟΥΜΕ ΝΕΑ ΧΑΡΤΙΑ (Αφού υπάρχει τράπουλα)
    // -----------------------------------------------------
    // Προσοχή: ΔΕΝ βάζουμε χαρτιά στο 'table' εδώ!
    
    // Στον Παίκτη 1
    $mysqli->query("UPDATE game_cards SET card_position = 'hand_p1' WHERE game_id = $game_id AND card_position = 'deck' ORDER BY card_order ASC LIMIT 6");
    
    // Στον Παίκτη 2
    $mysqli->query("UPDATE game_cards SET card_position = 'hand_p2' WHERE game_id = $game_id AND card_position = 'deck' ORDER BY card_order ASC LIMIT 6");

    return true; // Έγινε μοίρασμα
}

// 6. Συνάρτηση για ενημέρωση των στατιστικών του χρήστη (Καλείται μία φορά στο game over)
function update_user_stats($mysqli, $game_info, $winner) {
    
    // 1. Ελέγχουμε αν τα στατιστικά έχουν ήδη ενημερωθεί για αυτό το παιχνίδι
    if ($game_info['stats_updated'] == 1) {
        return; 
    }

    // 2. ΝΕΟΣ ΕΛΕΓΧΟΣ: Ενημέρωση μόνο για PvP παιχνίδια με δύο παίκτες
    // Αν δεν είναι PvP Ή ο player_2_id είναι κενός (δηλαδή Bot ή Waiting),
    // σημειώνουμε το παιχνίδι ως 'ενημερωμένο' και επιστρέφουμε.
    if ($game_info['game_mode'] !== 'pvp' || $game_info['player_2_id'] === null) {
        $mysqli->query("UPDATE games SET stats_updated = 1 WHERE id = " . $game_info['id']);
        return;
    }

    $p1_user_id = $game_info['player_1_id'];
    $p2_user_id = $game_info['player_2_id']; 
    
    // Καθορίζουμε το αποτέλεσμα για τον P1 και P2
    $p1_result_col = 'draws';
    $p2_result_col = 'draws';
    
    if ($winner === 'player_1') {
        $p1_result_col = 'wins';
        $p2_result_col = 'losses';
    } elseif ($winner === 'player_2') {
        $p1_result_col = 'losses';
        $p2_result_col = 'wins';
    }

    // 3. Ενημέρωση P1
    if ($p1_user_id) {
        $mysqli->query("UPDATE users SET $p1_result_col = $p1_result_col + 1 WHERE id = $p1_user_id");
    }

    // 4. Ενημέρωση P2 
    if ($p2_user_id && $p2_user_id != 0) {
        $mysqli->query("UPDATE users SET $p2_result_col = $p2_result_col + 1 WHERE id = $p2_user_id");
    }

    // 5. Σημειώνουμε το παιχνίδι ως "ενημερωμένο"
    $mysqli->query("UPDATE games SET stats_updated = 1 WHERE id = " . $game_info['id']);
}
?>