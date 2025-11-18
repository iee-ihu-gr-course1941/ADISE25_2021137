<?php
// api/play_card.php
require_once '../db.php';

header('Content-Type: application/json');

// ---------------------------------------------------------
// 1. ΒΑΣΙΚΟΙ ΕΛΕΓΧΟΙ & INPUT
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST requests allowed']);
    exit;
}

if (!isset($_POST['card_id'])) {
    echo json_encode(['error' => 'Missing card_id']);
    exit;
}

$card_id = intval($_POST['card_id']);


// ---------------------------------------------------------
// 2. ΤΑΥΤΟΠΟΙΗΣΗ ΠΑΙΚΤΗ (Session Awareness)
// ---------------------------------------------------------
// Διαβάζουμε ποιος παίζει από τα δεδομένα που έστειλε η JS
$my_side = isset($_POST['player_side']) ? intval($_POST['player_side']) : 1;

// Ορίζουμε τα ονόματα των στηλών
$my_hand_col = "hand_p" . $my_side;
$my_score_col = "score_p" . $my_side;
$my_bonus_col = "p" . $my_side . "_bonus_points";


// ---------------------------------------------------------
// 3. ΤΑΥΤΟΠΟΙΗΣΗ ΚΑΡΤΑΣ
// ---------------------------------------------------------
// Ψάχνουμε ΜΟΝΟ στο δικό μου χέρι ($my_hand_col)
$sql = "SELECT * FROM game_cards WHERE id = $card_id AND card_position = '$my_hand_col'";
$result = $mysqli->query($sql);

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Το χαρτί δεν βρέθηκε στο χέρι σου ή έχει παιχτεί ήδη!']);
    exit;
}

$played_card_row = $result->fetch_assoc();
$game_id = $played_card_row['game_id'];
$played_code = $played_card_row['card_code']; // Π.χ. 'C10' (10 Σπαθί) ή 'S11' (Βαλές)


// ---------------------------------------------------------
// 4. ΕΛΕΓΧΟΣ ΣΕΙΡΑΣ (Turn Protection)
// ---------------------------------------------------------
$sql_turn = "SELECT current_turn_id, game_mode FROM games WHERE id = $game_id";
$res_turn = $mysqli->query($sql_turn);
$game_info = $res_turn->fetch_assoc();
$current_turn = $game_info['current_turn_id'];

// Ελέγχουμε αν είναι το δικό μου νούμερο
if ($current_turn != $my_side) {
    echo json_encode(['error' => 'Δεν είναι η σειρά σου!']);
    exit;
}


// ---------------------------------------------------------
// 5. ΑΝΑΛΥΣΗ ΤΡΑΠΕΖΙΟΥ (Τι υπάρχει κάτω;)
// ---------------------------------------------------------
$sql_table = "SELECT * FROM game_cards WHERE game_id = $game_id AND card_position = 'table' ORDER BY card_order DESC";
$result_table = $mysqli->query($sql_table);

$table_cards = [];
while($row = $result_table->fetch_assoc()) {
    $table_cards[] = $row;
}

// Αναλύουμε τα νούμερα
$played_rank = intval(substr($played_code, 1)); 

$last_table_card = null;
$last_table_rank = 0;

if (count($table_cards) > 0) {
    $last_table_card = $table_cards[0]; 
    $last_table_rank = intval(substr($last_table_card['card_code'], 1));
}


// ---------------------------------------------------------
// 6. Η ΛΟΓΙΚΗ ΤΗΣ ΞΕΡΗΣ (Game Rules)
// ---------------------------------------------------------

$action = 'drop';    // drop = ρίξιμο, collect = μάζεμα
$is_xeri = false;
$xeri_points = 0;
$message = "";

// ΠΕΡΙΠΤΩΣΗ 1: Βαλές (J - Rank 11)
if ($played_rank === 11) {
    if (count($table_cards) > 0) {
        $action = 'collect';
        $message = "Ο Βαλές τα σκούπισε όλα!";
        
        // ΞΕΡΗ ΜΕ ΒΑΛΕ: Μόνο αν πάρει μοναχό Βαλέ
        if (count($table_cards) === 1 && $last_table_rank === 11) {
            $is_xeri = true;
            $xeri_points = 20;
            $message = "🔥 ΞΕΡΗ ΜΕ ΒΑΛΕ! 🔥 (+20)";
        }
    } else {
        $action = 'drop';
        $message = "Έριξες Βαλέ σε άδειο τραπέζι.";
    }
}

// ΠΕΡΙΠΤΩΣΗ 2: Ίδιο Νούμερο (Matching Rank)
elseif ($last_table_card && $played_rank === $last_table_rank) {
    $action = 'collect';
    $message = "Μάζεψες τα χαρτιά!";
    
    // ΞΕΡΗ: Αν υπήρχε ΑΚΡΙΒΩΣ 1 κάρτα κάτω
    if (count($table_cards) === 1) {
        $is_xeri = true;
        $xeri_points = 10;
        $message = "🔥 ΞΕΡΗ! 🔥 (+10)";
    }
}

// ΠΕΡΙΠΤΩΣΗ 3: Απλό Ρίξιμο
else {
    $action = 'drop';
    $message = "Το χαρτί έμεινε στο τραπέζι.";
}


// ---------------------------------------------------------
// 7. ΕΚΤΕΛΕΣΗ ΣΤΗ ΒΑΣΗ (Updates)
// ---------------------------------------------------------

if ($action === 'collect') {
    // Α. ΜΑΖΕΜΑ...
    $mysqli->query("UPDATE game_cards SET card_position = '$my_score_col' WHERE game_id = $game_id AND card_position = 'table'");
    $mysqli->query("UPDATE game_cards SET card_position = '$my_score_col' WHERE id = $card_id");

    // Γ. Ξερή...
    if ($is_xeri) {
        $mysqli->query("UPDATE games SET $my_bonus_col = $my_bonus_col + $xeri_points WHERE id = $game_id");
    }

    // Δ. ΝΕΟ: Καταγράφουμε ότι εγώ μάζεψα τελευταίος
    $mysqli->query("UPDATE games SET last_collector_id = $my_side WHERE id = $game_id");
    
} else {
    // Δ. ΡΙΞΙΜΟ: Το χαρτί πάει στο τραπέζι
    $new_order = 1;
    if ($last_table_card) {
        $new_order = $last_table_card['card_order'] + 1;
    }
    
    $mysqli->query("UPDATE game_cards SET card_position = 'table', card_order = $new_order WHERE id = $card_id");
}


// ---------------------------------------------------------
// 8. ΑΛΛΑΓΗ ΣΕΙΡΑΣ (Switch Turn)
// ---------------------------------------------------------
// Αν είμαι ο 1, παίζει ο 2. Αν είμαι ο 2, παίζει ο 1.
$next_turn = ($my_side == 1) ? 2 : 1;
$mysqli->query("UPDATE games SET current_turn_id = $next_turn WHERE id = $game_id");


// ---------------------------------------------------------
// 9. ΕΛΕΓΧΟΣ ΓΙΑ ΜΟΙΡΑΣΜΑ (Ή ΤΕΛΟΣ ΠΑΙΧΝΙΔΙΟΥ)
// ---------------------------------------------------------
require_once 'functions.php';
check_and_redeal($mysqli, $game_id);


// ---------------------------------------------------------
// 10. ΤΕΛΙΚΗ ΑΠΑΝΤΗΣΗ
// ---------------------------------------------------------
echo json_encode([
    'status' => 'success',
    'action' => $action,
    'is_xeri' => $is_xeri,
    'message' => $message
]);
?>