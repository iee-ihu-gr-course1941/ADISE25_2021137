<?php
// api/bot_play.php - JSON Version
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

// Βρίσκουμε το παιχνίδι όπου είναι η σειρά του Bot (Player 2)
if (isset($_GET['game_id'])) {
    $game_id = intval($_GET['game_id']);
    $sql_game = "SELECT * FROM games WHERE id = $game_id AND status='active' AND current_player=2 AND player2_id IS NULL";
} else {
    $sql_game = "SELECT * FROM games WHERE status='active' AND current_player=2 AND player2_id IS NULL LIMIT 1";
}
$res_game = $mysqli->query($sql_game);

if (!$res_game || $res_game->num_rows == 0) {
    echo json_encode(['status' => 'waiting', 'message' => 'Δεν είναι η σειρά του Bot']);
    exit;
}

$game = $res_game->fetch_assoc();
$game_id = $game['id'];

// Φέρνουμε τα δεδομένα από JSON
$bot_hand = json_decode($game['player2_hand'], true) ?: [];
$table_cards = json_decode($game['table_cards'], true) ?: [];
$bot_collected = json_decode($game['player2_collected'], true) ?: [];

if (empty($bot_hand)) {
    // Αν δεν έχει χαρτιά, έλεγχος για μοίρασμα
    check_and_redeal($mysqli, $game_id);
    echo json_encode(['status' => 'no_cards']);
    exit;
}

// Στρατηγική Επιλογής
$card_index = null;
$last_card = count($table_cards) > 0 ? $table_cards[count($table_cards) - 1] : null;
$last_rank = $last_card ? intval(substr($last_card, 1)) : 0;

// Α. Προσπάθεια για μάζεμα (matching rank)
foreach ($bot_hand as $idx => $card) {
    $rank = intval(substr($card, 1));
    if ($last_card && $rank === $last_rank) {
        $card_index = $idx;
        break;
    }
}

// Β. Βαλές αν υπάρχουν κάρτες στο τραπέζι
if ($card_index === null && count($table_cards) > 0) {
    foreach ($bot_hand as $idx => $card) {
        if (intval(substr($card, 1)) === 11) {
            $card_index = $idx;
            break;
        }
    }
}

// Γ. Τυχαίο χαρτί
if ($card_index === null) {
    $card_index = array_rand($bot_hand);
}

$played_card = $bot_hand[$card_index];
$played_rank = intval(substr($played_card, 1));

// Αφαίρεση χαρτιού από το χέρι
array_splice($bot_hand, $card_index, 1);

// Λογική παιχνιδιού
$action = 'drop';
$is_xeri = false;
$xeri_points = 0;

if ($played_rank === 11) {
    if (count($table_cards) > 0) {
        $action = 'collect';
        if (count($table_cards) === 1 && $last_rank === 11) {
            $is_xeri = true;
            $xeri_points = 20;
        }
    }
} elseif ($last_card && $played_rank === $last_rank) {
    $action = 'collect';
    if (count($table_cards) === 1) {
        $is_xeri = true;
        $xeri_points = 10;
    }
}

// Εκτέλεση ενέργειας
if ($action === 'collect') {
    $bot_collected[] = $played_card;
    $bot_collected = array_merge($bot_collected, $table_cards);
    $table_cards = [];
    $last_to_collect = 2;
} else {
    $table_cards[] = $played_card;
    $last_to_collect = $game['last_to_collect'];
}

// Υπολογισμός νέου σκορ
$new_score = intval($game['player2_score']) + $xeri_points;

// Αλλαγή σειράς
$next_turn = 1;

// Ενημέρωση βάσης
$update_sql = "UPDATE games SET 
    player2_hand = '" . $mysqli->real_escape_string(json_encode($bot_hand)) . "',
    player2_collected = '" . $mysqli->real_escape_string(json_encode($bot_collected)) . "',
    table_cards = '" . $mysqli->real_escape_string(json_encode($table_cards)) . "',
    current_player = $next_turn,
    last_to_collect = " . ($last_to_collect ?: "NULL") . ",
    player2_score = $new_score
    WHERE id = $game_id";

if (!$mysqli->query($update_sql)) {
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    exit;
}

// Έλεγχος για μοίρασμα ή τέλος
check_and_redeal($mysqli, $game_id);

echo json_encode([
    'status' => 'success',
    'action' => $action,
    'is_xeri' => $is_xeri,
    'message' => 'Bot played',
    'played_card' => $played_card  // Προσθέτουμε το χαρτί που έπαιξε το bot
]);
?>
