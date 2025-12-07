<?php
// api/init_game.php
require_once '../db.php';
require_once 'functions.php';  

// Διαβάζουμε το mode που έστειλε η JavaScript (αν δεν έστειλε, default 'pve')
$mode = isset($_POST['mode']) ? $_POST['mode'] : 'pve';
$my_id = $_SESSION['user_id']; // Παίρνουμε το ID από το Login

// 1. Φτιάξε νέο παιχνίδι με το συγκεκριμένο Mode και το σωστό player ID
$game_id = create_game($mysqli, $my_id, $mode);
// 2. Φτιάξε τράπουλα
$my_deck = generate_shuffled_deck();

// 3. Αποθήκευσε
save_deck_to_db($mysqli, $game_id, $my_deck);

// 4. Μοίρασε
deal_initial_cards($mysqli, $game_id);

header('Content-Type: application/json');
echo json_encode([
    "status" => "success", 
    "game_id" => $game_id, 
    "mode" => $mode,
    "player_side" => 1,
    "message" => "Game initialized!"
]);
?>