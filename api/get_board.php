<?php
// api/get_board.php
require_once '../db.php';
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
    echo json_encode(['status' => 'waiting_for_opponent']);
    exit;
}

// Έλεγχος αν το παιχνίδι τελείωσε
if ($game_info['status'] === 'finished') {
    $my_score = ($my_side == 1) ? $game_info['player1_score'] : $game_info['player2_score'];
    $opp_score = ($opp_side == 1) ? $game_info['player1_score'] : $game_info['player2_score'];
    
    $my_collected = json_decode(($my_side == 1) ? $game_info['player1_collected'] : $game_info['player2_collected'], true) ?: [];
    $opp_collected = json_decode(($opp_side == 1) ? $game_info['player1_collected'] : $game_info['player2_collected'], true) ?: [];
    
    // Καθορισμός νικητή
    $winner = 'draw';
    $final_message = "Ισοπαλία!";
    
    if ($my_score > $opp_score) {
        $winner = 'me';
        $final_message = "Νίκησες!";
    } elseif ($opp_score > $my_score) {
        $winner = 'opponent';
        $final_message = "Έχασες!";
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

// Υπολογισμός πόντων από τις μαζεμένες κάρτες
function calculate_score($cards) {
    $score = 0;
    foreach ($cards as $card) {
        $rank = intval(substr($card, 1));
        if ($rank === 1) $score += 1; // Άσσοι
        elseif ($card === 'C2') $score += 1; // Καλό δύο
        elseif ($card === 'D10') $score += 2; // Καλό δέκα
    }
    return $score;
}

// Υπολογισμός πόντων από κάρτες
$my_card_score = calculate_score($my_collected);
$opp_card_score = calculate_score($opp_collected);

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

echo json_encode([
    'status' => 'active',
    'table' => $table_cards,
    'my_hand' => $my_hand_formatted,
    'opponent_cards_count' => count($opp_hand),
    'deck_count' => count($deck),
    
    'my_score' => $my_score,
    'my_pile_count' => count($my_collected),
    'opp_score' => $opp_score,
    'opp_pile_count' => count($opp_collected),
    
    'is_my_turn' => $is_my_turn,
    'game_mode' => $game_mode,
    
    'my_name' => $my_name_display,
    'opp_name' => $opp_name_display
]);
?>
