<?php
// index.php

require_once 'db.php'; // Σύνδεση στη βάση

// Βρίσκουμε το τελευταίο ενεργό παιχνίδι (το μεγαλύτερο ID)
$sql = "SELECT id FROM games WHERE game_status = 'active' ORDER BY id DESC LIMIT 1";
$result = $mysqli->query($sql);

if ($row = $result->fetch_assoc()) {
    $current_game_id = $row['id'];
} else {
    // Αν δεν υπάρχει παιχνίδι, εμφάνισε μήνυμα
    die("Δεν υπάρχει ενεργό παιχνίδι! Τρέξε πρώτα το api/init_game.php");
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Ξερή - Ελληνικό Παιχνίδι</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/game.js"></script>
</head>
<body>

    <div id="ui-layer">
        <div class="score-box opponent-score">
            Αντίπαλος: <span id="score-opp">0</span>
        </div>
        
        <div class="game-title">ΞΕΡΗ #<?php echo $current_game_id; ?></div>

        <div class="score-box my-score">
            Εγώ: <span id="score-me">0</span>
        </div>
    </div>

    <div id="game-board">
        
        <div class="player-zone">
            <div id="opponent-pile" class="score-pile"></div>
            
            <div id="opponent-hand">
                </div>
        </div>

        <div id="table-area">
            <p>Φόρτωση τραπεζιού...</p>
        </div>

        <div class="player-zone">
            <div id="my-hand">
                 </div>

            <div id="my-pile" class="score-pile">
                </div>
        </div>

    </div>

    <button onclick="startNewGame()">Νέα Παρτίδα</button>

    <script src="js/game.js"></script>
    
    <script>
        // Προσωρινό script για να δουλέψει το κουμπί
        function startNewGame() {
            $.ajax({
                url: 'api/init_game.php',
                success: function(data) {
                    alert("Νέο παιχνίδι δημιουργήθηκε: " + data.game_id);
                    location.reload(); // Ανανέωση για να δούμε το νέο ID
                }
            });
        }
    </script>

</body>
<script>
    var currentGameId = <?php echo $current_game_id; ?>;
</script>
</html>