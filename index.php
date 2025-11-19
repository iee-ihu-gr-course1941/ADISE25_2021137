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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php if (!isset($_SESSION['user_id'])): ?>
    
    <div id="auth-screen" style="position:fixed; top:0; left:0; width:100%; height:100%; background:#204030; z-index:5000; display:flex; justify-content:center; align-items:center; flex-direction:column;">
        <h1 class="menu-title">ΞΕΡΗ</h1>

        <div id="login-form" class="menu-content auth-form-container">
            <h2>Σύνδεση</h2>
            <input type="text" id="l-user" placeholder="Username">
            <div class="pass-wrapper">
                <input type="password" id="l-pass" placeholder="Password">
                <i class="fas fa-eye" onclick="togglePass('l-pass')"></i>
            </div>
            <button class="menu-btn" onclick="doLogin()">ΕΙΣΟΔΟΣ</button>
            <p>Δεν έχεις λογαριασμό; <button class="link-btn" onclick="showSignup()">Εγγραφή</button></p>
        </div>

        <div id="signup-form" class="menu-content auth-form-container" style="display:none;">
            <h2>Εγγραφή</h2>
            <input type="text" id="s-user" placeholder="Username">
            <div class="pass-wrapper">
                <input type="password" id="s-pass" placeholder="Password">
                <i class="fas fa-eye" onclick="togglePass('s-pass')"></i>
            </div>
            <div class="pass-wrapper">
                <input type="password" id="s-pass-conf" placeholder="Confirm Password">
                <i class="fas fa-eye" onclick="togglePass('s-pass-conf')"></i>
            </div>
            <button class="menu-btn" onclick="doSignup()">ΕΓΓΡΑΦΗ</button>
            <p>Έχεις λογαριασμό; <button class="link-btn" onclick="showLogin()">Σύνδεση</button></p>
        </div>
    </div>
    
    <?php endif; ?>

    <div id="main-menu" <?php if (!isset($_SESSION['user_id'])) echo 'class="hidden"'; ?>>
        <div class="menu-container"> <div class="menu-content">
                <h1 class="menu-title">ΞΕΡΗ</h1>
                <p class="menu-subtitle">Καλωσήρθες, <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Παίκτη'; ?></p>
                
                <button id="btn-play-main" class="menu-btn">ΠΑΙΞΕ</button>
                
                <div id="mode-selector" style="display: none;">
                    <button class="menu-btn mode-btn" data-mode="pve">VS COMPUTER</button>
                    <button class="menu-btn mode-btn" data-mode="pvp">VS PLAYER 2</button>
                </div>
                
                <button id="btn-logout" class="menu-btn logout-btn" onclick="doLogout()">ΕΞΟΔΟΣ ΑΠΟ ΛΟΓΑΡΙΑΣΜΟ</button>
            </div>

            <div id="stats-box">
                <h2>Στατιστικά</h2>
                <div class="stat-item">
                    <span class="stat-label">Νίκες:</span>
                    <span id="stat-wins" class="stat-value win">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Ήττες:</span>
                    <span id="stat-losses" class="stat-value loss">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Ισοπαλίες:</span>
                    <span id="stat-draws" class="stat-value draw">0</span>
                </div>
                <div class="stat-item total">
                    <span class="stat-label">Σύνολο Παιχνιδιών:</span>
                    <span id="stat-total" class="stat-value total">0</span>
                </div>
            </div>

        </div>
    </div>

    <div id="waiting-screen" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.9); z-index: 1500; color: white; flex-direction: column; justify-content: center; align-items: center;">
        <h2 style="color: #00ffea; text-align: center;">Αναζήτηση Αντιπάλου...</h2>
        <div style="font-size: 40px; margin-top: 20px;">⏳</div>
        <p>Μην κλείσεις τη σελίδα.</p>
        <button id="btn-cancel-pvp" class="menu-btn logout-btn" style="margin-top: 30px; display: none;">ΑΚΥΡΩΣΗ & ΕΠΙΣΤΡΟΦΗ</button>
    </div>

    <div id="game-over-screen" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.95); z-index: 3000; color: white; flex-direction: column; justify-content: center; align-items: center;">
        
        <h1 id="go-title" style="font-size: 70px; margin-bottom: 30px; text-shadow: 0 0 30px currentColor; animation: fadeIn 1s;"></h1>
        
        <div style="font-size: 32px; margin-bottom: 20px; text-align: center; background: rgba(255,255,255,0.1); padding: 30px 50px; border-radius: 15px; animation: fadeIn 1.5s;">
            <div style="margin-bottom: 15px; font-size: 24px; color: #aaa;">ΤΕΛΙΚΑ ΣΚΟΡ</div>
            <div style="margin-bottom: 15px;">Εσύ: <span id="go-my-score" style="color: #00ffea; font-weight: bold; font-size: 40px;">0</span> (<span id="go-my-cards">0</span> χαρτιά)</div>
            <div>Αντίπαλος: <span id="go-opp-score" style="color: #ff4d4d; font-weight: bold; font-size: 40px;">0</span> (<span id="go-opp-cards">0</span> χαρτιά)</div>
        </div>

        <button onclick="location.reload()" class="menu-btn" style="background-color: #28a745; animation: fadeIn 2s; font-size: 24px; padding: 20px 40px;">ΠΑΙΞΕ ΞΑΝΑ</button>
    </div>
    
    <button class="exit-btn" id="btn-quit-game" onclick="quitGame()" style="display:none;">ΕΞΟΔΟΣ</button>

    <div id="ui-layer">
        
        <div class="score-box opponent-score">
            <span id="name-opp" class="player-name-text">Αντίπαλος</span>
            <span class="score-text">Σκορ: <span id="score-opp">0</span></span>
        </div>
        
        <div class="game-title-container">
            <div class="game-title"></div> </div>

        <div class="score-box my-score">
            <span id="name-me" class="player-name-text">Εγώ</span>
            <span class="score-text">Σκορ: <span id="score-me">0</span></span>
        </div>
    </div>

    <div id="game-board">
        
        <div class="player-zone">
            <div id="opponent-pile" class="score-pile"></div>
            
            <div id="opponent-hand">
                </div>
        </div>

        <div id="middle-zone" style="display: flex; justify-content: center; align-items: center; gap: 30px;">
            
            <div id="draw-pile" class="score-pile">
                </div>

            <div id="table-area">
                <p>Φόρτωση τραπεζιού...</p>
            </div>
            
        </div>

        <div class="player-zone">
            <div id="my-hand">
                 </div>

            <div id="my-pile" class="score-pile">
                </div>
        </div>
        
        <div id="game-status" style="text-align: center; margin-top: 10px; font-weight: bold; color: gold;"></div>

    </div>

    <script>
        var currentGameId = null; 

        <?php if (isset($_SESSION['user_id'])): ?>
            $(document).ready(function() {
                if (!currentGameId) {
                    $('#main-menu').removeClass('hidden');
                }
            });
        <?php else: ?>
            $('#ui-layer, #game-board').hide();
        <?php endif; ?>
    </script>
    <script src="js/game.js"></script>

</body>
</html>