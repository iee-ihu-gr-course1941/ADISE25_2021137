<?php
// api/bot_play.php
require_once '../db.php';

header('Content-Type: application/json');

// 1. Βρίσκουμε το παιχνίδι (Bot = Player 2)
$sql_game = "SELECT id FROM games WHERE game_status='active' AND current_turn_id=2 LIMIT 1";
$res_game = $mysqli->query($sql_game);

if ($res_game->num_rows == 0) {
    echo json_encode(['status' => 'waiting', 'message' => 'Δεν είναι η σειρά του Bot']);
    exit;
}

$game_id = $res_game->fetch_assoc()['id'];

// 2. Ανάλυση Τραπεζιού
$result_table = $mysqli->query("SELECT * FROM game_cards WHERE game_id = $game_id AND card_position = 'table' ORDER BY card_order DESC");
$table_cards = [];
while($row = $result_table->fetch_assoc()) { $table_cards[] = $row; }

$last_table_card = (count($table_cards) > 0) ? $table_cards[0] : null;
$last_rank = ($last_table_card) ? intval(substr($last_table_card['card_code'], 1)) : 0;

// 3. Bot Hand
$res_hand = $mysqli->query("SELECT * FROM game_cards WHERE game_id=$game_id AND card_position='hand_p2'");
$bot_hand = [];
while($row = $res_hand->fetch_assoc()) { $bot_hand[] = $row; }

if (empty($bot_hand)) {
    // Αν δεν έχει χαρτιά, ίσως πρέπει να γίνει redeal ή να τελειώσει
    require_once 'functions.php';
    check_and_redeal($mysqli, $game_id);
    echo json_encode(['status' => 'no_cards']);
    exit;
}

// 4. Στρατηγική Επιλογής
$card_to_play = null;

// Α. Μάζεμα
foreach ($bot_hand as $card) {
    $rank = intval(substr($card['card_code'], 1));
    if ($last_table_card && $rank === $last_rank) {
        $card_to_play = $card;
        break; 
    }
}
// Β. Βαλές
if (!$card_to_play && count($table_cards) > 0) {
    foreach ($bot_hand as $card) {
        if (intval(substr($card['card_code'], 1)) === 11) {
            $card_to_play = $card;
            break;
        }
    }
}
// Γ. Τυχαίο / Smart Discard
if (!$card_to_play) {
    $card_to_play = $bot_hand[array_rand($bot_hand)];
}

// 5. Εκτέλεση Κίνησης
$card_id = $card_to_play['id'];
$played_rank = intval(substr($card_to_play['card_code'], 1));
$action = 'drop';
$is_xeri = false;
$xeri_points = 0;

if ($played_rank === 11) { 
    if (count($table_cards) > 0) {
        $action = 'collect';
        if (count($table_cards) === 1 && $last_rank === 11) { $is_xeri = true; $xeri_points = 20; }
    }
} elseif ($last_table_card && $played_rank === $last_rank) { 
    $action = 'collect';
    if (count($table_cards) === 1) { $is_xeri = true; $xeri_points = 10; }
}

if ($action === 'collect') {
    $mysqli->query("UPDATE game_cards SET card_position = 'score_p2' WHERE game_id = $game_id AND card_position = 'table'");
    $mysqli->query("UPDATE game_cards SET card_position = 'score_p2' WHERE id = $card_id");
    if ($is_xeri) {
        $mysqli->query("UPDATE games SET p2_bonus_points = p2_bonus_points + $xeri_points WHERE id = $game_id");
    }
    // ΝΕΟ: Το Bot μάζεψε τελευταίο
    $mysqli->query("UPDATE games SET last_collector_id = 2 WHERE id = $game_id");
} else {
    $new_order = 1;
    if ($last_table_card) $new_order = $last_table_card['card_order'] + 1;
    $mysqli->query("UPDATE game_cards SET card_position = 'table', card_order = $new_order WHERE id = $card_id");
}

// 6. Αλλαγή Σειράς -> P1
$mysqli->query("UPDATE games SET current_turn_id = 1 WHERE id = $game_id");

// 7. ΕΛΕΓΧΟΣ ΓΙΑ ΤΕΛΟΣ / ΜΟΙΡΑΣΜΑ (ΠΟΛΥ ΣΗΜΑΝΤΙΚΟ!)
require_once 'functions.php';
check_and_redeal($mysqli, $game_id);

echo json_encode(['status' => 'played', 'action' => $action]);
?>