<?php
// api/play_card.php
require_once '../db.php';

header('Content-Type: application/json');

// 1. Βασικοί Έλεγχοι
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST allowed']); exit;
}
if (!isset($_POST['card_id'])) {
    echo json_encode(['error' => 'Missing card_id']); exit;
}

$card_id = intval($_POST['card_id']);
$game_id = 0; // Θα το βρούμε παρακάτω

// 2. Βρίσκουμε το χαρτί που έπαιξε ο παίκτης και το Game ID
$sql = "SELECT * FROM game_cards WHERE id = $card_id AND card_position = 'hand_p1'";
$result = $mysqli->query($sql);

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Το χαρτί δεν βρέθηκε στο χέρι σου!']); exit;
}

$played_card_row = $result->fetch_assoc();
$game_id = $played_card_row['game_id'];
$played_code = $played_card_row['card_code']; // Π.χ. 'C10' ή 'S11' (Βαλές)

// Βοηθητική: Ποιο είναι το νούμερο του χαρτιού; (Χωρίς το γράμμα)
// substr('C10', 1) -> επιστρέφει '10'
$played_rank = intval(substr($played_code, 1)); 


// 3. Βλέπουμε τι υπάρχει ΚΑΤΩ στο τραπέζι
$sql_table = "SELECT * FROM game_cards WHERE game_id = $game_id AND card_position = 'table' ORDER BY card_order DESC";
$result_table = $mysqli->query($sql_table);

$table_cards = [];
while($row = $result_table->fetch_assoc()) {
    $table_cards[] = $row;
}

// Τι υπάρχει πάνω-πάνω; (Το τελευταίο που παίχτηκε)
$last_table_card = count($table_cards) > 0 ? $table_cards[0] : null;
$last_table_rank = 0;

if ($last_table_card) {
    $last_table_rank = intval(substr($last_table_card['card_code'], 1));
}


// ---------------------------------------------------------
// 4. Η ΛΟΓΙΚΗ ΤΗΣ ΞΕΡΗΣ (Rules Engine)
// ---------------------------------------------------------

$action = 'drop'; // drop = το αφήνω κάτω, collect = τα μαζεύω
$is_xeri = false;

// ΚΑΝΟΝΑΣ 1: Αν είναι Βαλές (11), τα παίρνω όλα!
if ($played_rank === 11) {
    if (count($table_cards) > 0) {
        $action = 'collect';
    } else {
        $action = 'drop'; // Αν το τραπέζι είναι άδειο, ο Βαλές κάθεται κάτω
    }
}
// ΚΑΝΟΝΑΣ 2: Αν το νούμερο είναι ίδιο με το τελευταίο κάτω
elseif ($last_table_card && $played_rank === $last_table_rank) {
    $action = 'collect';
    
    // ΚΑΝΟΝΑΣ 3: ΞΕΡΗ! (Αν υπήρχε μόνο 1 χαρτί κάτω)
    if (count($table_cards) === 1) {
        $is_xeri = true;
    // Υπολογισμός πόντων Ξερής
        // Αν είναι Βαλές (11) παίρνει 20, αλλιώς 10
        $xeri_points = ($played_rank === 11) ? 20 : 10;

        // Αποθήκευση πόντων στη βάση (Bonus points)
        $mysqli->query("UPDATE games SET p1_bonus_points = p1_bonus_points + $xeri_points WHERE id = $game_id");
    }
}


// ---------------------------------------------------------
// 5. ΕΚΤΕΛΕΣΗ (Database Updates)
// ---------------------------------------------------------

if ($action === 'collect') {
    // Α. ΜΑΖΕΜΑ: Όλα τα χαρτιά του τραπεζιού πάνε στα "κερδισμένα" του P1
    $mysqli->query("UPDATE game_cards SET card_position = 'score_p1' WHERE game_id = $game_id AND card_position = 'table'");
    
    // Β. Και το χαρτί που παίξαμε πάει στα κερδισμένα
    $mysqli->query("UPDATE game_cards SET card_position = 'score_p1' WHERE id = $card_id");

    $msg = "Μάζεψες τα χαρτιά!";
} 
else {
    // Γ. ΡΙΞΙΜΟ: Το χαρτί πάει στο τραπέζι
    // Βρίσκουμε τη νέα σειρά (order)
    $new_order = 1;
    if ($last_table_card) {
        $new_order = $last_table_card['card_order'] + 1;
    }
    
    $mysqli->query("UPDATE game_cards SET card_position = 'table', card_order = $new_order WHERE id = $card_id");
    
    $msg = "Έπαιξες χαρτί στο τραπέζι.";
}

// 6. Απάντηση στην Javascript
echo json_encode([
    'status' => 'success',
    'action' => $action,
    'is_xeri' => $is_xeri,
    'message' => $msg
]);
?>