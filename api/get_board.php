<?php
// api/get_board.php
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['game_id'])) {
    echo json_encode(['error' => 'No game_id provided']); exit;
}
$game_id = intval($_GET['game_id']);

// --- ΣΥΝΑΡΤΗΣΗ ΥΠΟΛΟΓΙΣΜΟΥ ΠΟΝΤΩΝ ΑΠΟ ΚΑΡΤΕΣ ---
function calculate_card_points($mysqli, $game_id, $player_pile) {
    // Παίρνουμε όλα τα χαρτιά από τη στοίβα του παίκτη
    $sql = "SELECT card_code FROM game_cards WHERE game_id = $game_id AND card_position = '$player_pile'";
    $result = $mysqli->query($sql);
    
    $points = 0;
    while($row = $result->fetch_assoc()) {
        $code = $row['card_code']; // π.χ. C2, D10, H5
        
        // Κανόνες Πόντων:
        // 1. Άσσοι (Το νούμερο είναι 1) -> 1 πόντος
        if (substr($code, 1) === '1') { 
            $points += 1; 
        }
        // 2. Το 2 Σπαθί (C2) -> 1 πόντος
        elseif ($code === 'C2') { 
            $points += 1; 
        }
        // 3. Το 10 Καρρό (D10) -> 2 πόντοι
        elseif ($code === 'D10') { 
            $points += 2; 
        }
        // Τα υπόλοιπα 0 πόντοι (εκτός αν θες να μετράς και το "πλήθος" καρτών στο τέλος)
    }
    return $points;
}

// 1. Τραπέζι
$sql_table = "SELECT card_code FROM game_cards WHERE game_id = $game_id AND card_position = 'table' ORDER BY card_order ASC";
$res_table = $mysqli->query($sql_table);
$table_cards = [];
while ($row = $res_table->fetch_assoc()) { $table_cards[] = $row['card_code']; }

// 2. Χέρι Μου
$sql_hand = "SELECT id, card_code FROM game_cards WHERE game_id = $game_id AND card_position = 'hand_p1' ORDER BY card_order ASC";
$res_hand = $mysqli->query($sql_hand);
$my_cards = [];
while ($row = $res_hand->fetch_assoc()) { $my_cards[] = ['id' => $row['id'], 'code' => $row['card_code']]; }

// 3. Αντίπαλος
$res_opp = $mysqli->query("SELECT COUNT(*) as count FROM game_cards WHERE game_id = $game_id AND card_position = 'hand_p2'");
$opponent_cards_count = $res_opp->fetch_assoc()['count'];


// ------------------------------------------------
// 4. ΥΠΟΛΟΓΙΣΜΟΣ ΣΚΟΡ (Bonus + Κάρτες)
// ------------------------------------------------

// Α. Παίρνουμε τους πόντους Ξερής από τη βάση
$sql_bonus = "SELECT p1_bonus_points, p2_bonus_points FROM games WHERE id = $game_id";
$res_bonus = $mysqli->query($sql_bonus);
$bonus = $res_bonus->fetch_assoc();

// Β. Υπολογίζουμε τους πόντους από τα φύλλα που έχουμε μαζέψει
$my_card_points = calculate_card_points($mysqli, $game_id, 'score_p1');
$opp_card_points = calculate_card_points($mysqli, $game_id, 'score_p2');

// Γ. Τελικό Σκορ
$my_total_score = intval($bonus['p1_bonus_points']) + $my_card_points;
$opp_total_score = intval($bonus['p2_bonus_points']) + $opp_card_points;


echo json_encode([
    'table' => $table_cards,
    'my_hand' => $my_cards,
    'opponent_cards_count' => $opponent_cards_count,
    // Στέλνουμε ΤΟ ΣΚΟΡ αντί για το πλήθος καρτών
    'my_pile_count' => $my_total_score, 
    'opp_pile_count' => $opp_total_score
]);
?>