<?php
require_once 'db.php'; // Μόνο η σύνδεση χρειάζεται (για το session_start)
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ξερή - Ελληνικό Παιχνίδι</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <!-- ΚΕΝΤΡΙΚΟ ΜΕΝΟΥ (Overlay) -->
    <div id="main-menu">
        <div class="menu-content">
            <h1 class="menu-title">ΞΕΡΗ</h1>
            <p class="menu-subtitle">Το κλασικό ελληνικό παιχνίδι</p>
            
            <!-- Αρχικό Κουμπί -->
            <button id="btn-play-main" class="menu-btn">ΠΑΙΞΕ</button>
            
            <!-- Επιλογή Mode (Αρχικά κρυφό) -->
            <div id="mode-selector" style="display: none;">
                <button class="menu-btn mode-btn" data-mode="pve">VS COMPUTER</button>
                <button class="menu-btn mode-btn" data-mode="pvp">VS PLAYER 2</button>
            </div>
        </div>
    </div>

    <!-- WAITING SCREEN (Μπαίνει κάτω από το Main Menu) -->
    <div id="waiting-screen" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.9); z-index: 1500; color: white; flex-direction: column; justify-content: center; align-items: center;">
        <h2 style="color: #00ffea; text-align: center;">Αναζήτηση Αντιπάλου...</h2>
        <div style="font-size: 40px; margin-top: 20px;">⏳</div>
        <p>Μην κλείσεις τη σελίδα.</p>
    </div>

        <!-- GAME OVER SCREEN -->
    <div id="game-over-screen" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.9); z-index: 3000; color: white; flex-direction: column; justify-content: center; align-items: center;">
        
        <h1 id="go-title" style="font-size: 60px; margin-bottom: 10px; text-shadow: 0 0 20px gold;">ΤΕΛΟΣ ΠΑΙΧΝΙΔΙΟΥ</h1>
        
        <div style="font-size: 30px; margin-bottom: 40px; text-align: center;">
            <div style="margin-bottom: 10px;">Εσύ: <span id="go-my-score" style="color: #00ffea; font-weight: bold;">0</span></div>
            <div>Αντίπαλος: <span id="go-opp-score" style="color: #ff4d4d; font-weight: bold;">0</span></div>
        </div>

        <button onclick="location.reload()" class="menu-btn" style="background-color: #28a745;">ΠΑΙΞΕ ΞΑΝΑ</button>
    </div>

    <!-- UI LAYER (Σκορ & Τίτλος) -->
    <div id="ui-layer">
        <div class="score-box opponent-score">
            Αντίπαλος: <span id="score-opp">0</span>
        </div>
        
        <div class="game-title">ΞΕΡΗ</div>

        <div class="score-box my-score">
            Εγώ: <span id="score-me">0</span>
        </div>
    </div>

    <!-- ΤΟ ΤΡΑΠΕΖΙ ΤΟΥ ΠΑΙΧΝΙΔΙΟΥ -->
    <div id="game-board">
        
        <!-- Περιοχή Αντιπάλου (Πάνω) -->
        <div class="player-zone">
            <!-- Η Στοίβα του Αντιπάλου (Αριστερά) -->
            <div id="opponent-pile" class="score-pile"></div>
            
            <div id="opponent-hand">
                <!-- Κάρτες αντιπάλου... -->
            </div>
        </div>

        <!-- ΜΕΣΑΙΑ ΖΩΝΗ -->
        <div id="middle-zone" style="display: flex; justify-content: center; align-items: center; gap: 30px;">
            
            <!-- Η Τράπουλα (Αριστερά) -->
            <div id="draw-pile" class="score-pile">
                <!-- Εδώ θα μπει η πλάτη -->
            </div>

            <!-- Το Τραπέζι (Δεξιά) -->
            <div id="table-area">
                <p>Φόρτωση τραπεζιού...</p>
            </div>
            
        </div>

        <!-- Τα Χαρτιά Μου (Κάτω) -->
        <div class="player-zone">
            <div id="my-hand">
                 <!-- Κάρτες μου... -->
            </div>

            <!-- Η Στοίβα Μου (Δεξιά) -->
            <div id="my-pile" class="score-pile">
                <!-- Εδώ θα εμφανιστεί η εικόνα της πλάτης αν έχω χαρτιά -->
            </div>
        </div>
        
        <div id="game-status" style="text-align: center; margin-top: 10px; font-weight: bold; color: gold;"></div>

    </div>

    <!-- Αρχικοποίηση μεταβλητής (Κενή στην αρχή) -->
    <script>
        var currentGameId = null;
    </script>

    <script src="js/game.js"></script>

</body>
</html>