<?php
// api/play_card.php - JSON Version
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

// Έλεγχοι input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST requests allowed']);
    exit;
}

if (!isset($_POST['card_id']) || !isset($_POST['player_side']) || !isset($_POST['game_id'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$card_index = intval($_POST['card_id']); // Το index του χαρτιού στο hand
$my_side = intval($_POST['player_side']);
$game_id = intval($_POST['game_id']);

// Φέρνουμε τα δεδομένα του παιχνιδιού
$result = $mysqli->query("SELECT * FROM games WHERE id = $game_id");
if (!$result || !($game = $result->fetch_assoc())) {
    echo json_encode(['error' => 'Game not found']);
    exit;
}

// Έλεγχος σειράς
if ($game['current_player'] != $my_side) {
    echo json_encode(['error' => 'Δεν είναι η σειρά σου!']);
    exit;
}

// Φέρνουμε τα δεδομένα από JSON
$my_hand = json_decode(($my_side == 1) ? $game['player1_hand'] : $game['player2_hand'], true) ?: [];
$table_cards = json_decode($game['table_cards'], true) ?: [];
$my_collected = json_decode(($my_side == 1) ? $game['player1_collected'] : $game['player2_collected'], true) ?: [];

// Έλεγχος αν το card_index υπάρχει
if (!isset($my_hand[$card_index])) {
    echo json_encode(['error' => 'Το χαρτί δεν βρέθηκε στο χέρι σου!']);
    exit;
}

$played_card = $my_hand[$card_index];
$played_rank = intval(substr($played_card, 1));

// Αφαιρούμε το χαρτί από το χέρι
array_splice($my_hand, $card_index, 1);

// Λογική του παιχνιδιού
$action = 'drop';
$is_xeri = false;
$xeri_points = 0;
$message = "";

$last_card = count($table_cards) > 0 ? $table_cards[count($table_cards) - 1] : null;
$last_rank = $last_card ? intval(substr($last_card, 1)) : 0;

// ΠΕΡΙΠΤΩΣΗ 1: Βαλές (J - Rank 11)
if ($played_rank === 11) {
    if (count($table_cards) > 0) {
        $action = 'collect';
        $message = "Ο Βαλές τα σκούπισε όλα!";
        
        // ΞΕΡΗ ΜΕ ΒΑΛΕ: Μόνο αν πάρει μοναχό Βαλέ
        if (count($table_cards) === 1 && $last_rank === 11) {
            $is_xeri = true;
            $xeri_points = 20;
            $message = "🔥 ΞΕΡΗ ΜΕ ΒΑΛΕ! 🔥 (+20)";
        }
    } else {
        $action = 'drop';
        $message = "Έριξες Βαλέ σε άδειο τραπέζι.";
    }
}
// ΠΕΡΙΠΤΩΣΗ 2: Ίδιο Νούμερο
elseif ($last_card && $played_rank === $last_rank) {
    $action = 'collect';
    $message = "Μάζεψες τα χαρτιά!";
    
    // ΞΕΡΗ: Αν υπήρχε ΑΚΡΙΒΩΣ 1 κάρτα κάτω
    if (count($table_cards) === 1) {
        $is_xeri = true;
        
        // ΝΕΟΣ ΚΑΝΟΝΑΣ: Ξερή δίνει πάντα 10 πόντους (ανεξάρτητα από το φύλλο)
        // Τα πόντα των φύλλων θα μετρηθούν ξεχωριστά στο τέλος
        $xeri_points = 10;
        $message = "🔥 ΞΕΡΗ! 🔥 (+10 πόντοι)";
    }
}
// ΠΕΡΙΠΤΩΣΗ 3: Απλό Ρίξιμο
else {
    $action = 'drop';
    $message = "Το χαρτί έμεινε στο τραπέζι.";
}

// Εκτέλεση ενέργειας
if ($action === 'collect') {
    // Μάζεμα: Προσθέτουμε το παιγμένο χαρτί και όλα τα χαρτιά του τραπεζιού
    $my_collected[] = $played_card;
    $my_collected = array_merge($my_collected, $table_cards);
    $table_cards = [];
    
    // Καταγραφή ότι μάζεψα τελευταίος
    $last_to_collect = $my_side;
} else {
    // Ρίξιμο: Προσθέτουμε το χαρτί στο τραπέζι
    $table_cards[] = $played_card;
    $last_to_collect = $game['last_to_collect'];
}

// Αλλαγή σειράς
$next_turn = ($my_side == 1) ? 2 : 1;

// Ενημέρωση βάσης
$my_hand_field = ($my_side == 1) ? 'player1_hand' : 'player2_hand';
$my_collected_field = ($my_side == 1) ? 'player1_collected' : 'player2_collected';
$my_score_field = ($my_side == 1) ? 'player1_score' : 'player2_score';

// Υπολογισμός νέου σκορ (προσθήκη πόντων ξερής αν υπάρχουν)
$new_score = intval($game[$my_score_field]) + $xeri_points;

$update_sql = "UPDATE games SET 
    $my_hand_field = '" . $mysqli->real_escape_string(json_encode($my_hand)) . "',
    $my_collected_field = '" . $mysqli->real_escape_string(json_encode($my_collected)) . "',
    table_cards = '" . $mysqli->real_escape_string(json_encode($table_cards)) . "',
    current_player = $next_turn,
    last_to_collect = " . ($last_to_collect ?: "NULL") . ",
    $my_score_field = $new_score
    WHERE id = $game_id";

if (!$mysqli->query($update_sql)) {
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    exit;
}

// Έλεγχος για μοίρασμα ή τέλος παιχνιδιού
check_and_redeal($mysqli, $game_id);

echo json_encode([
    'status' => 'success',
    'action' => $action,
    'is_xeri' => $is_xeri,
    'message' => $message
]);
?>
