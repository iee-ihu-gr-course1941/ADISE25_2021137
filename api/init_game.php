<?php
// api/init_game.php

require_once '../db.php';      // Σύνδεση στη βάση
require_once 'functions.php';  // Φόρτωση των εργαλείων μας

// --- ΕΚΤΕΛΕΣΗ ---

// 1. Φτιάξε νέο παιχνίδι
$game_id = create_game($mysqli);

// 2. Φτιάξε τράπουλα στη μνήμη
$my_deck = generate_shuffled_deck();

// 3. Αποθήκευσέ την στη βάση
save_deck_to_db($mysqli, $game_id, $my_deck);

// 4. Μοίρασε
deal_initial_cards($mysqli, $game_id);

// Μήνυμα επιτυχίας (JSON για να το διαβάζει αργότερα η Javascript)
header('Content-Type: application/json');
echo json_encode(["status" => "success", "game_id" => $game_id, "message" => "Game initialized and dealt!"]);
?>