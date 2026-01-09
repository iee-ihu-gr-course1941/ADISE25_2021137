<?php
// api/get_board.php
require_once '../db.php';
require_once 'functions.php';
header('Content-Type: application/json');

if (!isset($_GET['game_id'])) { 
    echo json_encode(['error' => 'No game_id provided']); 
    exit; 
}
$game_id = intval($_GET['game_id']);
$my_side = isset($_GET['player_side']) ? intval($_GET['player_side']) : 1;
$opp_side = ($my_side == 1) ? 2 : 1;

// Φέρνουμε πληροφορίες παιχνιδιού με ονόματα παικτών
$sql_game = "
    SELECT g.*, 
           u1.username as p1_name, 
           u2.username as p2_name 
    FROM games g
    LEFT JOIN users u1 ON g.player1_id = u1.id
    LEFT JOIN users u2 ON g.player2_id = u2.id
    WHERE g.id = $game_id
";

$res = $mysqli->query($sql_game);
if (!$res || !($game_info = $res->fetch_assoc())) { 
    echo json_encode(['error' => 'Game not found']); 
    exit; 
}

// Ονόματα παικτών
$p1_name = $game_info['p1_name'] ?: "Παίκτης 1";
$p2_name = $game_info['p2_name'] ?: "Αντίπαλος";
$my_name_display = ($my_side == 1) ? $p1_name : $p2_name;
$opp_name_display = ($my_side == 1) ? $p2_name : $p1_name;

// Έλεγχος αν περιμένουμε αντίπαλο
if ($game_info['status'] === 'waiting') {
    echo json_encode([
        'status' => 'waiting_for_opponent',
        'game_id' => $game_id,
        'player_side' => $my_side
    ]);
    exit;
}

if ($game_info['status'] === 'active' && $game_info['player2_id'] !== null) {
}

if ($game_info['status'] === 'active' && $game_info['player2_id'] !== null) {
    $my_user_id = ($my_side == 1) ? intval($game_info['player1_id']) : intval($game_info['player2_id']);
    $opp_user_id = ($opp_side == 1) ? intval($game_info['player1_id']) : intval($game_info['player2_id']);

    $mysqli->query(
        "CREATE TABLE IF NOT EXISTS game_presence (\n"
        . "  game_id INT NOT NULL,\n"
        . "  user_id INT NOT NULL,\n"
        . "  last_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n"
        . "  PRIMARY KEY (game_id, user_id),\n"
        . "  INDEX idx_game_presence_game (game_id)\n"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if ($my_user_id > 0 && $opp_user_id > 0) {
        $mysqli->query(
            "INSERT INTO game_presence (game_id, user_id, last_seen) VALUES ($game_id, $my_user_id, NOW()) "
            . "ON DUPLICATE KEY UPDATE last_seen = NOW()"
        );

        // Check opponent heartbeat
        $opp_seen_res = $mysqli->query("SELECT last_seen FROM game_presence WHERE game_id = $game_id AND user_id = $opp_user_id");
        $opp_last_seen = ($opp_seen_res && ($row = $opp_seen_res->fetch_assoc())) ? $row['last_seen'] : null;

        // Αν ο αντίπαλος είχε κάνει έστω ένα poll και μετά εξαφανίστηκε για >60s -> θεωρείται disconnect (backup)
        // Κύρια ανίχνευση γίνεται με beforeunload στο frontend
        if ($opp_last_seen !== null) {
            $inactive_seconds = time() - strtotime($opp_last_seen);
            if ($inactive_seconds > 60) {
                $winner_side = $my_side;
                $winner_user_id = ($winner_side == 1) ? intval($game_info['player1_id']) : intval($game_info['player2_id']);
                $loser_user_id = ($winner_side == 1) ? intval($game_info['player2_id']) : intval($game_info['player1_id']);

                // Atomic finish: ενημερώνουμε μία φορά (αποφεύγουμε διπλό μέτρημα στατιστικών)
                $finish_res = $mysqli->query(
                    "UPDATE games SET status = 'finished', last_to_collect = $winner_side "
                    . "WHERE id = $game_id AND status = 'active'"
                );

                if ($finish_res && $mysqli->affected_rows === 1) {
                    $mysqli->query("UPDATE users SET games_lost = games_lost + 1, games_played = games_played + 1 WHERE id = $loser_user_id");
                    $mysqli->query("UPDATE users SET games_won = games_won + 1, games_played = games_played + 1 WHERE id = $winner_user_id");
                }

                $game_info['status'] = 'finished';
                $game_info['last_to_collect'] = $winner_side;
            }
        }
    }
}

// Έλεγχος αν το παιχνίδι τελείωσε
if ($game_info['status'] === 'finished') {
    $my_collected = json_decode(($my_side == 1) ? $game_info['player1_collected'] : $game_info['player2_collected'], true) ?: [];
    $opp_collected = json_decode(($opp_side == 1) ? $game_info['player1_collected'] : $game_info['player2_collected'], true) ?: [];

    // Υπολογισμός πλήρους σκορ: πόντοι από κάρτες + πόντοι ξερής (αποθηκευμένοι στη βάση)
    $my_card_score = calculate_card_score($my_collected);
    $opp_card_score = calculate_card_score($opp_collected);
    $my_xeri_score = intval($game_info[($my_side == 1) ? 'player1_score' : 'player2_score']);
    $opp_xeri_score = intval($game_info[($opp_side == 1) ? 'player1_score' : 'player2_score']);
    
    $my_score = $my_card_score + $my_xeri_score;
    $opp_score = $opp_card_score + $opp_xeri_score;

    // Καθορισμός νικητή
    $winner = 'draw';
    $final_message = "Ισοπαλία!";

    // Έλεγχος αν τελείωσε φυσιολογικά ή από εγκατάλειψη:
    $deck = json_decode($game_info['deck'], true) ?: [];
    $p1_hand = json_decode($game_info['player1_hand'], true) ?: [];
    $p2_hand = json_decode($game_info['player2_hand'], true) ?: [];
    
    // Κανονική λήξη: δεν υπάρχει τράπουλα ΚΑΙ δεν υπάρχουν χαρτιά στα χέρια
    $is_normal_end = (count($deck) == 0 && count($p1_hand) == 0 && count($p2_hand) == 0);
    
    // Αν είναι PvP και ΔΕΝ τελείωσε φυσιολογικά (quit/disconnect)
    if (!$is_normal_end && $game_info['player2_id'] !== null && $game_info['last_to_collect'] !== null) {
        if (intval($game_info['last_to_collect']) == $my_side) {
            $winner = 'me';
            $final_message = "🎉 Νίκησες! Ο αντίπαλος αποχώρησε από το παιχνίδι.";
        } else {
            $winner = 'opponent';
            $final_message = "Έχασες. Αποχώρησες από το παιχνίδι.";
        }
    } else {
        // Κανονική λήξη με βάση το σκορ
        if ($my_score > $opp_score) {
            $winner = 'me';
            $final_message = "Νίκησες!";
        } elseif ($opp_score > $my_score) {
            $winner = 'opponent';
            $final_message = "Έχασες!";
        }
    }
    
    echo json_encode([
        'status' => 'finished',
        'winner' => $winner,
        'final_message' => $final_message,
        'my_score' => $my_score,
        'opp_score' => $opp_score,
        'my_cards' => count($my_collected),
        'opp_cards' => count($opp_collected)
    ]);
    exit;
}

// Παιχνίδι σε εξέλιξη - φέρνουμε τα δεδομένα
$deck = json_decode($game_info['deck'], true) ?: [];
$my_hand = json_decode(($my_side == 1) ? $game_info['player1_hand'] : $game_info['player2_hand'], true) ?: [];
$opp_hand = json_decode(($opp_side == 1) ? $game_info['player1_hand'] : $game_info['player2_hand'], true) ?: [];
$table_cards = json_decode($game_info['table_cards'], true) ?: [];
$my_collected = json_decode(($my_side == 1) ? $game_info['player1_collected'] : $game_info['player2_collected'], true) ?: [];
$opp_collected = json_decode(($opp_side == 1) ? $game_info['player1_collected'] : $game_info['player2_collected'], true) ?: [];

// Υπολογισμός πόντων από κάρτες (ίδιοι κανόνες για PvP/PvE)
$my_card_score = calculate_card_score($my_collected);
$opp_card_score = calculate_card_score($opp_collected);

// Προσθήκη bonus πόντων από ξερές (αποθηκευμένοι στη βάση)
$my_score = $my_card_score + intval($game_info[($my_side == 1) ? 'player1_score' : 'player2_score']);
$opp_score = $opp_card_score + intval($game_info[($opp_side == 1) ? 'player1_score' : 'player2_score']);

// Έλεγχος σειράς
$is_my_turn = ($game_info['current_player'] == $my_side);

// Καθορισμός game_mode (pve αν δεν υπάρχει player2_id, αλλιώς pvp)
$game_mode = ($game_info['player2_id'] === null) ? 'pve' : 'pvp';

// Μετατροπή χαρτιών σε format με id για το frontend
$my_hand_formatted = [];
foreach ($my_hand as $idx => $card) {
    $my_hand_formatted[] = ['id' => $idx, 'code' => $card];
}

// Τελευταία κάρτα που έπαιξε κάθε παίκτης (για εμφάνιση στη στοίβα)
$my_last_played = ($my_side == 1) ? $game_info['player1_last_played'] : $game_info['player2_last_played'];
$opp_last_played = ($opp_side == 1) ? $game_info['player1_last_played'] : $game_info['player2_last_played'];

echo json_encode([
    'status' => 'active',
    'table' => $table_cards,
    'my_hand' => $my_hand_formatted,
    'opponent_cards_count' => count($opp_hand),
    'deck_count' => count($deck),
    
    'my_score' => $my_score,
    'my_pile_count' => count($my_collected),
    'my_last_card' => $my_last_played,
    'opp_score' => $opp_score,
    'opp_pile_count' => count($opp_collected),
    'opp_last_card' => $opp_last_played,
    
    'is_my_turn' => $is_my_turn,
    'game_mode' => $game_mode,
    
    'my_name' => $my_name_display,
    'opp_name' => $opp_name_display
]);
?>
