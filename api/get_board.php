<?php
// api/get_board.php
require_once '../db.php';
header('Content-Type: application/json');

// Έλεγχος αν υπάρχει το game_id
if (!isset($_GET['game_id'])) { 
    echo json_encode(['error' => 'No ID']); 
    exit; 
}
$game_id = intval($_GET['game_id']);

// Βρίσκουμε ποιος είμαι (από το session ή default 1)
$my_side = isset($_GET['player_side']) ? intval($_GET['player_side']) : 1;
$opp_side = ($my_side == 1) ? 2 : 1;

// Ορίζουμε τα πεδία της βάσης για τα χέρια και τις στοίβες
$my_hand_col = "hand_p" . $my_side;
$opp_hand_col = "hand_p" . $opp_side;
$my_pile_col = "score_p" . $my_side;
$opp_pile_col = "score_p" . $opp_side;


// ---------------------------------------------------------
// 1. Φέρνουμε πληροφορίες παιχνιδιού ΚΑΙ ΟΝΟΜΑΤΑ παικτών
// ---------------------------------------------------------
// Κάνουμε JOIN με τον πίνακα users για να πάρουμε τα ονόματα από τα IDs
$sql_game = "
    SELECT g.*, 
           u1.username as p1_name, 
           u2.username as p2_name 
    FROM games g
    LEFT JOIN users u1 ON g.player_1_id = u1.id
    LEFT JOIN users u2 ON g.player_2_id = u2.id
    WHERE g.id = $game_id
";

$res = $mysqli->query($sql_game);
if (!$res) { echo json_encode(['error' => 'DB Error']); exit; }
$game_info = $res->fetch_assoc();

if (!$game_info) { echo json_encode(['error' => 'Game not found']); exit; }

// --- ΚΑΘΟΡΙΣΜΟΣ ΟΝΟΜΑΤΩΝ ΓΙΑ ΤΟ UI ---
$p1_name = $game_info['p1_name'] ? $game_info['p1_name'] : "Παίκτης 1";

// Αν είναι PvE και δεν υπάρχει 2ος παίκτης στη βάση, τον λέμε "Υπολογιστής"
// Αν είναι PvP και δεν έχει μπει ακόμα, θα είναι null (αλλά θα το χειριστεί το waiting status)
$p2_name = ($game_info['game_mode'] === 'pve') ? "Υπολογιστής" : ($game_info['p2_name'] ? $game_info['p2_name'] : "Αντίπαλος");

// Ποιο όνομα είναι δικό μου και ποιο του αντιπάλου;
$my_name_display = ($my_side == 1) ? $p1_name : $p2_name;
$opp_name_display = ($my_side == 1) ? $p2_name : $p1_name;


// --- ΕΛΕΓΧΟΣ STATUS (WAITING) ---
if ($game_info['game_status'] === 'waiting') {
    echo json_encode(['status' => 'waiting_for_opponent']);
    exit;
}

// ---------------------------------------------------------
// ΣΥΝΑΡΤΗΣΗ ΠΟΝΤΩΝ (Βοηθητική) - Κανόνες Ξερής
// ---------------------------------------------------------
function calculate_points($mysqli, $gid, $pos) {
    $res = $mysqli->query("SELECT card_code FROM game_cards WHERE game_id=$gid AND card_position='$pos'");
    $pts = 0;
    $card_count = 0;
    
    while($row = $res->fetch_assoc()) {
        $card_count++;
        $code = $row['card_code']; // π.χ. C10, H1
        $rank = intval(substr($code, 1));  // π.χ. 10, 1
        
        // 1. Άσσοι (1 πόντος)
        if ($rank === 1) {
            $pts += 1;
        }
        // 2. Δύο Σπαθί (C2) (1 πόντος) - Καλό Δύο
        elseif ($code === 'C2') {
            $pts += 1;
        }
        // 3. Δέκα Καρό (D10) (2 πόντοι) - Καλό Δέκα
        elseif ($code === 'D10') {
            $pts += 2;
        }
    }
    
    return ['points' => $pts, 'count' => $card_count];
}


// ---------------------------------------------------------
// 2. ΕΛΕΓΧΟΣ ΓΙΑ ΤΕΛΟΣ ΠΑΙΧΝΙΔΙΟΥ (GAME OVER)
// ---------------------------------------------------------
if ($game_info['game_status'] === 'finished') {
    
    // Υπολογισμός πόντων από κάρτες
    $my_data = calculate_points($mysqli, $game_id, $my_pile_col);
    $opp_data = calculate_points($mysqli, $game_id, $opp_pile_col);
    
    // Πόντοι από Ξερές (Bonus)
    $my_bonus = ($my_side == 1) ? intval($game_info['p1_bonus_points']) : intval($game_info['p2_bonus_points']);
    $opp_bonus = ($opp_side == 1) ? intval($game_info['p1_bonus_points']) : intval($game_info['p2_bonus_points']);
    
    $my_final_score = $my_data['points'] + $my_bonus;
    $opp_final_score = $opp_data['points'] + $opp_bonus;
    
    // Μπόνους για περισσότερα χαρτιά (3 πόντοι)
    if ($my_data['count'] > $opp_data['count']) {
        $my_final_score += 3;
    } elseif ($opp_data['count'] > $my_data['count']) {
        $opp_final_score += 3;
    }
    
    // Καθορισμός Νικητή & Μηνύματος
    $winner = 'draw';
    $winner_name = "Κανείς";
    
    if ($my_final_score > $opp_final_score) {
        $winner = 'me';
        $winner_name = $my_name_display; // Το δικό μου όνομα
    } elseif ($opp_final_score > $my_final_score) {
        $winner = 'opponent';
        $winner_name = $opp_name_display; // Το όνομα του αντιπάλου
    }
    
    // Δημιουργία του τελικού μηνύματος
    if ($winner === 'draw') {
        $final_message = 'ΙΣΟΠΑΛΙΑ!';
    } else {
        // Μετατροπή σε κεφαλαία για έμφαση
        $final_message = "ΝΙΚΗΣΕ Ο/Η " . mb_strtoupper($winner_name, 'UTF-8') . "!";
    }
    
    echo json_encode([
        'status' => 'finished',
        'winner' => $winner,
        'final_message' => $final_message, // Αυτό θα δείξει το UI
        'my_score' => $my_final_score,
        'opp_score' => $opp_final_score,
        'my_cards' => $my_data['count'],
        'opp_cards' => $opp_data['count']
    ]);
    exit;
}


// ---------------------------------------------------------
// 3. ΕΝΕΡΓΟ ΠΑΙΧΝΙΔΙ (ACTIVE STATE)
// ---------------------------------------------------------

// Α. Κάρτες στο Τραπέζι
$res_table = $mysqli->query("SELECT card_code FROM game_cards WHERE game_id = $game_id AND card_position = 'table' ORDER BY card_order ASC");
$table_cards = [];
while ($row = $res_table->fetch_assoc()) { 
    $table_cards[] = $row['card_code']; 
}

// Β. Το Χέρι ΜΟΥ
$res_hand = $mysqli->query("SELECT id, card_code FROM game_cards WHERE game_id = $game_id AND card_position = '$my_hand_col' ORDER BY card_order ASC");
$my_cards = [];
while ($row = $res_hand->fetch_assoc()) { 
    $my_cards[] = ['id' => $row['id'], 'code' => $row['card_code']]; 
}

// Γ. Χέρι Αντιπάλου (Μόνο αριθμός)
$opponent_count = $mysqli->query("SELECT COUNT(*) as c FROM game_cards WHERE game_id = $game_id AND card_position = '$opp_hand_col'")->fetch_assoc()['c'];

// Δ. Σκορ & Στοίβες (Live Score)
$my_bonus = ($my_side == 1) ? $game_info['p1_bonus_points'] : $game_info['p2_bonus_points'];
$opp_bonus = ($opp_side == 1) ? $game_info['p1_bonus_points'] : $game_info['p2_bonus_points'];

$my_data = calculate_points($mysqli, $game_id, $my_pile_col);
$opp_data = calculate_points($mysqli, $game_id, $opp_pile_col);

$my_score = intval($my_bonus) + $my_data['points'];
$opp_score = intval($opp_bonus) + $opp_data['points'];

$my_pile_count = $my_data['count'];
$opp_pile_count = $opp_data['count'];

// Ε. Σειρά & Τράπουλα
$is_my_turn = ($game_info['current_turn_id'] == $my_side);
$deck_count = $mysqli->query("SELECT COUNT(*) as c FROM game_cards WHERE game_id=$game_id AND card_position='deck'")->fetch_assoc()['c'];


// ---------------------------------------------------------
// 4. ΤΕΛΙΚΗ ΑΠΑΝΤΗΣΗ JSON
// ---------------------------------------------------------
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
    
    'is_my_turn' => $is_my_turn,
    'game_mode' => $game_info['game_mode'],
    
    // Τα ονόματα για το UI
    'my_name' => $my_name_display,
    'opp_name' => $opp_name_display
]);
?>