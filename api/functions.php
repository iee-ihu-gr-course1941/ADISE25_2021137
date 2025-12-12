<?php
// api/functions.php - JSON Version

// Δημιουργία ανακατεμένης τράπουλας
function generate_shuffled_deck() {
    $suits = ['C', 'D', 'H', 'S']; // Clubs, Diamonds, Hearts, Spades
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

// Αποθήκευση της τράπουλας στη βάση (JSON format)
function save_deck_to_db($mysqli, $game_id, $deck) {
    $deck_json = json_encode($deck);
    $mysqli->query("UPDATE games SET deck = '$deck_json' WHERE id = $game_id");
}

// Αρχικό μοίρασμα: 4 στο τραπέζι, 6 σε κάθε παίκτη
function deal_initial_cards($mysqli, $game_id) {
    // Φέρνουμε την τράπουλα
    $result = $mysqli->query("SELECT deck FROM games WHERE id = $game_id");
    $row = $result->fetch_assoc();
    $deck = json_decode($row['deck'], true);
    
    if (!$deck || count($deck) < 16) {
        return false; // Δεν έχουμε αρκετά χαρτιά
    }
    
    // Μοιράζουμε
    $table_cards = array_splice($deck, 0, 4);      // Πρώτα 4 στο τραπέζι
    $player1_hand = array_splice($deck, 0, 6);     // Επόμενα 6 στον παίκτη 1
    $player2_hand = array_splice($deck, 0, 6);     // Επόμενα 6 στον παίκτη 2
    
    // Κενές στοίβες μαζεμένων
    $player1_collected = [];
    $player2_collected = [];
    
    // Ενημέρωση βάσης
    $mysqli->query("UPDATE games SET 
        deck = '" . json_encode($deck) . "',
        table_cards = '" . json_encode($table_cards) . "',
        player1_hand = '" . json_encode($player1_hand) . "',
        player2_hand = '" . json_encode($player2_hand) . "',
        player1_collected = '" . json_encode($player1_collected) . "',
        player2_collected = '" . json_encode($player2_collected) . "'
        WHERE id = $game_id");
    
    return true;
}

// Έλεγχος αν τελείωσαν τα χαρτιά και μοίρασμα νέων
function check_and_redeal($mysqli, $game_id) {
    $result = $mysqli->query("SELECT * FROM games WHERE id = $game_id");
    $game = $result->fetch_assoc();
    
    $deck = json_decode($game['deck'], true) ?: [];
    $player1_hand = json_decode($game['player1_hand'], true) ?: [];
    $player2_hand = json_decode($game['player2_hand'], true) ?: [];
    $table_cards = json_decode($game['table_cards'], true) ?: [];
    
    // Αν έχουν ακόμα χαρτιά, δεν κάνουμε τίποτα
    if (count($player1_hand) > 0 || count($player2_hand) > 0) {
        return false;
    }
    
    // Αν τελείωσε η τράπουλα, τέλος παιχνιδιού
    if (count($deck) == 0) {
        // Δίνουμε τα χαρτιά του τραπεζιού στον τελευταίο που μάζεψε
        if ($game['last_to_collect']) {
            $last_collector = $game['last_to_collect'];
            $collected_field = ($last_collector == 1) ? 'player1_collected' : 'player2_collected';
            $collected = json_decode($game[$collected_field], true) ?: [];
            $collected = array_merge($collected, $table_cards);
            
            // Υπολογισμός τελικών σκορ
            $player1_collected = json_decode($game['player1_collected'], true) ?: [];
            $player2_collected = json_decode($game['player2_collected'], true) ?: [];
            
            if ($last_collector == 1) {
                $player1_collected = $collected;
            } else {
                $player2_collected = $collected;
            }
            
            // Υπολογισμός πόντων από κάρτες
            $player1_card_score = calculate_card_score($player1_collected);
            $player2_card_score = calculate_card_score($player2_collected);
            
            // Bonus για περισσότερες κάρτες
            if (count($player1_collected) > count($player2_collected)) {
                $player1_card_score += 3;
            } elseif (count($player2_collected) > count($player1_collected)) {
                $player2_card_score += 3;
            }
            
            // Προσθήκη των υπαρχόντων πόντων ξερής
            $player1_final_score = $player1_card_score + intval($game['player1_score']);
            $player2_final_score = $player2_card_score + intval($game['player2_score']);
            
            $mysqli->query("UPDATE games SET 
                status = 'finished',
                table_cards = '[]',
                $collected_field = '" . json_encode($collected) . "',
                player1_collected = '" . json_encode($player1_collected) . "',
                player2_collected = '" . json_encode($player2_collected) . "',
                player1_score = $player1_final_score,
                player2_score = $player2_final_score
                WHERE id = $game_id");
            
            // Ενημέρωση στατιστικών (μόνο αν υπάρχουν 2 παίκτες - PvP)
            if ($game['player1_id'] && $game['player2_id']) {
                if ($player1_final_score > $player2_final_score) {
                    // Player 1 νικητής
                    $mysqli->query("UPDATE users SET games_won = games_won + 1, games_played = games_played + 1 WHERE id = " . $game['player1_id']);
                    $mysqli->query("UPDATE users SET games_lost = games_lost + 1, games_played = games_played + 1 WHERE id = " . $game['player2_id']);
                } elseif ($player2_final_score > $player1_final_score) {
                    // Player 2 νικητής
                    $mysqli->query("UPDATE users SET games_won = games_won + 1, games_played = games_played + 1 WHERE id = " . $game['player2_id']);
                    $mysqli->query("UPDATE users SET games_lost = games_lost + 1, games_played = games_played + 1 WHERE id = " . $game['player1_id']);
                } else {
                    // Ισοπαλία
                    $mysqli->query("UPDATE users SET games_played = games_played + 1 WHERE id = " . $game['player1_id']);
                    $mysqli->query("UPDATE users SET games_played = games_played + 1 WHERE id = " . $game['player2_id']);
                }
            }
        } else {
            // Αν δεν υπάρχει last_to_collect, απλά τελειώνουμε
            $player1_collected = json_decode($game['player1_collected'], true) ?: [];
            $player2_collected = json_decode($game['player2_collected'], true) ?: [];
            
            // Υπολογισμός πόντων από κάρτες
            $player1_card_score = calculate_card_score($player1_collected);
            $player2_card_score = calculate_card_score($player2_collected);
            
            // Bonus για περισσότερες κάρτες
            if (count($player1_collected) > count($player2_collected)) {
                $player1_card_score += 3;
            } elseif (count($player2_collected) > count($player1_collected)) {
                $player2_card_score += 3;
            }
            
            // Προσθήκη των υπαρχόντων πόντων ξερής
            $player1_final_score = $player1_card_score + intval($game['player1_score']);
            $player2_final_score = $player2_card_score + intval($game['player2_score']);
            
            $mysqli->query("UPDATE games SET 
                status = 'finished',
                player1_score = $player1_final_score,
                player2_score = $player2_final_score
                WHERE id = $game_id");
            
            // Ενημέρωση στατιστικών (μόνο αν υπάρχουν 2 παίκτες - PvP)
            if ($game['player1_id'] && $game['player2_id']) {
                if ($player1_final_score > $player2_final_score) {
                    // Player 1 νικητής
                    $mysqli->query("UPDATE users SET games_won = games_won + 1, games_played = games_played + 1 WHERE id = " . $game['player1_id']);
                    $mysqli->query("UPDATE users SET games_lost = games_lost + 1, games_played = games_played + 1 WHERE id = " . $game['player2_id']);
                } elseif ($player2_final_score > $player1_final_score) {
                    // Player 2 νικητής
                    $mysqli->query("UPDATE users SET games_won = games_won + 1, games_played = games_played + 1 WHERE id = " . $game['player2_id']);
                    $mysqli->query("UPDATE users SET games_lost = games_lost + 1, games_played = games_played + 1 WHERE id = " . $game['player1_id']);
                } else {
                    // Ισοπαλία
                    $mysqli->query("UPDATE users SET games_played = games_played + 1 WHERE id = " . $game['player1_id']);
                    $mysqli->query("UPDATE users SET games_played = games_played + 1 WHERE id = " . $game['player2_id']);
                }
            }
        }
        
        return 'finished';
    }
    
    // Μοιράζουμε νέα χαρτιά (6 σε κάθε παίκτη)
    $player1_hand = array_splice($deck, 0, min(6, count($deck)));
    $player2_hand = array_splice($deck, 0, min(6, count($deck)));
    
    $mysqli->query("UPDATE games SET 
        deck = '" . json_encode($deck) . "',
        player1_hand = '" . json_encode($player1_hand) . "',
        player2_hand = '" . json_encode($player2_hand) . "'
        WHERE id = $game_id");
    
    return true;
}

// Υπολογισμός πόντων από κάρτες
function calculate_card_score($cards) {
    $score = 0;
    foreach ($cards as $card) {
        $rank = intval(substr($card, 1));
        if ($rank === 1) {
            $score += 1; // Άσσοι
        } elseif ($card === 'C2') {
            $score += 1; // Καλό δύο (Δύο Σπαθί)
        } elseif ($card === 'D10') {
            $score += 2; // Καλό δέκα (Δέκα Καρό)
        }
    }
    return $score;
}

// Ενημέρωση στατιστικών χρήστη
function update_user_stats($mysqli, $winner_id, $loser_id) {
    if ($winner_id) {
        $mysqli->query("UPDATE users SET 
            games_won = games_won + 1,
            games_played = games_played + 1
            WHERE id = $winner_id");
    }
    
    if ($loser_id) {
        $mysqli->query("UPDATE users SET 
            games_lost = games_lost + 1,
            games_played = games_played + 1
            WHERE id = $loser_id");
    }
}
?>
