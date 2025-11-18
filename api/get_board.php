<?php
// api/get_board.php
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_GET['game_id'])) { echo json_encode(['error' => 'No ID']); exit; }
$game_id = intval($_GET['game_id']);

// Βρίσκουμε ποιος είμαι (από το session). Αν δεν υπάρχει, υποθέτουμε P1 (για PvE)
$my_side = isset($_GET['player_side']) ? intval($_GET['player_side']) : 1;
$opp_side = ($my_side == 1) ? 2 : 1;

// Ορίζουμε τα πεδία της βάσης
$my_hand_col = "hand_p" . $my_side;
$opp_hand_col = "hand_p" . $opp_side;
$my_pile_col = "score_p" . $my_side;
$opp_pile_col = "score_p" . $opp_side;

// --- ΕΛΕΓΧΟΣ STATUS ΠΑΙΧΝΙΔΙΟΥ ---
$sql_game = "SELECT game_status, current_turn_id, p1_bonus_points, p2_bonus_points FROM games WHERE id = $game_id";
$game_info = $mysqli->query($sql_game)->fetch_assoc();

if ($game_info['game_status'] === 'waiting') {
    // Αν περιμένουμε ακόμα, στέλνουμε ειδικό μήνυμα
    echo json_encode(['status' => 'waiting_for_opponent']);
    exit;
}

// --- ΣΥΝΑΡΤΗΣΗ ΠΟΝΤΩΝ (Χρησιμοποιεί τις μεταβλητές μας) ---
function calculate_points($mysqli, $gid, $pos) {
    $res = $mysqli->query("SELECT card_code FROM game_cards WHERE game_id=$gid AND card_position='$pos'");
    $pts = 0;
    while($row = $res->fetch_assoc()) {
        $r = substr($row['card_code'], 1);
        $c = $row['card_code'];
        if ($r === '1') $pts += 1;
        elseif ($c === 'C2') $pts += 1;
        elseif ($c === 'D10') $pts += 2;
    }
    return $pts;
}

// 1. Τραπέζι
$res_table = $mysqli->query("SELECT card_code FROM game_cards WHERE game_id = $game_id AND card_position = 'table' ORDER BY card_order ASC");
$table_cards = [];
while ($row = $res_table->fetch_assoc()) { $table_cards[] = $row['card_code']; }

// 2. Το Χέρι ΜΟΥ (Δυναμικό)
$res_hand = $mysqli->query("SELECT id, card_code FROM game_cards WHERE game_id = $game_id AND card_position = '$my_hand_col' ORDER BY card_order ASC");
$my_cards = [];
while ($row = $res_hand->fetch_assoc()) { $my_cards[] = ['id' => $row['id'], 'code' => $row['card_code']]; }

// 3. Αντίπαλος
$opponent_count = $mysqli->query("SELECT COUNT(*) as c FROM game_cards WHERE game_id = $game_id AND card_position = '$opp_hand_col'")->fetch_assoc()['c'];

// 4. Σκορ & Στοίβες
$my_bonus = ($my_side == 1) ? $game_info['p1_bonus_points'] : $game_info['p2_bonus_points'];
$opp_bonus = ($opp_side == 1) ? $game_info['p1_bonus_points'] : $game_info['p2_bonus_points'];

$my_score = intval($my_bonus) + calculate_points($mysqli, $game_id, $my_pile_col);
$opp_score = intval($opp_bonus) + calculate_points($mysqli, $game_id, $opp_pile_col);

$my_pile_count = $mysqli->query("SELECT COUNT(*) as c FROM game_cards WHERE game_id=$game_id AND card_position='$my_pile_col'")->fetch_assoc()['c'];
$opp_pile_count = $mysqli->query("SELECT COUNT(*) as c FROM game_cards WHERE game_id=$game_id AND card_position='$opp_pile_col'")->fetch_assoc()['c'];

// 5. Σειρά & Deck
$is_my_turn = ($game_info['current_turn_id'] == $my_side); // Ελέγχουμε αν το νούμερο ταιριάζει με το δικό μας Side
$deck_count = $mysqli->query("SELECT COUNT(*) as c FROM game_cards WHERE game_id=$game_id AND card_position='deck'")->fetch_assoc()['c'];

echo json_encode([
    'status' => 'active',
    'table' => $table_cards,
    'my_hand' => $my_cards,
    'opponent_cards_count' => $opponent_count,
    'deck_count' => $deck_count,
    'my_score' => $my_score,
    'my_pile_count' => $my_pile_count,
    'opp_score' => $opp_score,
    'opp_pile_count' => $opp_pile_count,
    'is_my_turn' => $is_my_turn
]);
?>