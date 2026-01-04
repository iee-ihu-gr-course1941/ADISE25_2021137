// js/game.js

var botThinking = false;        // Για να μην παίζει διπλές φορές το Bot
var pollingInterval = null;     // Το χρονόμετρο για την ανανέωση
var myPlayerSide = 1;           // Ποιος είμαι; (1 ή 2). Default 1.
var currentGameId = null;       // Το ID του παιχνιδιού
var isInPvPGame = false;        // Flag για PvP παιχνίδι

// ---------------------------------------------------------
// DISCONNECT DETECTION - Ανίχνευση κλεισίματος tab/browser
// ---------------------------------------------------------
window.addEventListener('beforeunload', function(e) {
    // Στέλνουμε σήμα αποχώρησης ΜΟΝΟ αν είμαστε σε ενεργό PvP παιχνίδι
    if (currentGameId && isInPvPGame) {
        // Χρησιμοποιούμε sendBeacon γιατί δουλεύει ακόμα και όταν κλείνει η σελίδα
        navigator.sendBeacon('api/player_disconnect.php', '');
    }
});
document.addEventListener('visibilitychange', function() {
});

// ---------------------------------------------------------
// AUTHENTICATION LOGIC (Global Functions used by index.php)
// ---------------------------------------------------------

function togglePass(id) {
    var x = document.getElementById(id);
    var icon = $(`#${id}`).siblings('i.fa-eye, i.fa-eye-slash');
    
    if (x.type === "password") {
        x.type = "text";
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        x.type = "password";
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
}
function showSignup() { 
    $('#login-form').hide(); 
    $('#signup-form').fadeIn(); 
}
function showLogin() { 
    $('#signup-form').hide(); 
    $('#login-form').fadeIn(); 
}
function doLogin() {
    $.post('api/login.php', {
        username: $('#l-user').val(),
        password: $('#l-pass').val()
    }, function(res) {
        if(res.status === 'success') location.reload();
        else alert(res.error);
    }, 'json');
}
function doSignup() {
    $.post('api/signup.php', {
        username: $('#s-user').val(),
        password: $('#s-pass').val(),
        password_confirm: $('#s-pass-conf').val()
    }, function(res) {
        if(res.status === 'success') {
            alert("Επιτυχία εγγραφής! Τώρα συνδέσου.");
            showLogin();
        } else {
            alert(res.error);
        }
    }, 'json');
}

// ΝΕΑ ΣΥΝΑΡΤΗΣΗ: Χειρισμός αποσύνδεσης
function doLogout() {
    if (confirm("Είσαι σίγουρος/η ότι θέλεις να αποσυνδεθείς;")) {
        $.ajax({
            url: 'api/logout.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                // Επαναφόρτωση της σελίδας για να εμφανιστεί η οθόνη σύνδεσης
                location.reload(); 
            },
            error: function() {
                alert("Σφάλμα κατά την αποσύνδεση. Παρακαλώ δοκιμάστε να ανανεώσετε τη σελίδα.");
                location.reload(); 
            }
        });
    }
}


// ---------------------------------------------------------
// NEW: ΛΟΓΙΚΗ ΣΤΑΤΙΣΤΙΚΩΝ ΠΑΙΚΤΗ
// ---------------------------------------------------------

function fetchUserStats() {
    // ΣΗΜΕΙΩΣΗ: Αυτή η λειτουργία απαιτεί το αρχείο api/get_stats.php
    $.ajax({
        url: 'api/get_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                var stats = response.stats;
                // Υπολογισμός συνολικών παιχνιδιών
                var totalGames = parseInt(stats.wins) + parseInt(stats.losses) + parseInt(stats.draws);
                
                // Ενημέρωση HTML
                $('#stat-wins').text(stats.wins);
                $('#stat-losses').text(stats.losses);
                $('#stat-draws').text(stats.draws);
                $('#stat-total').text(totalGames);
                
                $('#stats-box').show(); 
            } else {
                console.error("Could not fetch user stats:", response.error);
                $('#stats-box').hide(); 
            }
        },
        error: function() {
            console.error("Error communicating with stats API.");
            $('#stats-box').hide();
        }
    });
}

// ΝΕΟ: Φόρτωση Leaderboard
function fetchLeaderboard() {
    console.log("Fetching leaderboard...");
    $.ajax({
        url: 'api/get_leaderboard.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log("Leaderboard response:", response);
            if (response.status === 'success' && response.leaderboard && response.leaderboard.length > 0) {
                var html = '';
                response.leaderboard.forEach(function(player) {
                    var rankClass = '';
                    if (player.rank === 1) rankClass = 'first';
                    else if (player.rank === 2) rankClass = 'second';
                    else if (player.rank === 3) rankClass = 'third';
                    
                    html += '<div class="leaderboard-item">';
                    html += '  <div class="leaderboard-rank ' + rankClass + '">' + player.rank + '</div>';
                    html += '  <div class="leaderboard-info">';
                    html += '    <span class="leaderboard-username">' + player.username + '</span>';
                    html += '    <span class="leaderboard-stats">' + player.wins + 'W / ' + player.losses + 'L</span>';
                    html += '  </div>';
                    html += '  <div class="leaderboard-wins">🏆 ' + player.wins + '</div>';
                    html += '</div>';
                });
                $('#leaderboard-list').html(html);
            } else {
                console.log("No leaderboard data or empty");
                $('#leaderboard-list').html('<div class="loading-text">Δεν υπάρχουν παίκτες</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error fetching leaderboard:", error);
            console.error("XHR:", xhr.responseText);
            $('#leaderboard-list').html('<div class="loading-text">Σφάλμα φόρτωσης</div>');
        }
    });
}


$(document).ready(function() {
    
    // Εάν δεν υπάρχει η φόρμα σύνδεσης/εγγραφής, σημαίνει ότι είμαστε ήδη συνδεδεμένοι.
    if ($('#auth-screen').length === 0) {
        
        // ΝΕΟ: Event listener για το κουμπί ΑΚΥΡΩΣΗΣ
        $('#btn-cancel-pvp').on('click', function() {
            cancelMatchmaking();
        });
        
        // --- EVENT LISTENERS ΓΙΑ ΤΟ ΜΕΝΟΥ ---
        // 1. Κλικ στο αρχικό "ΠΑΙΞΕ"
        $('#btn-play-main').on('click', function() {
            $(this).hide(); // Κρύβουμε το κουμπί Play
            $('#mode-selector').fadeIn(); // Εμφάνισε τις επιλογές
        });

        // 2. Κλικ σε επιλογή Mode (PvE ή PvP)
        $('.mode-btn').on('click', function() {
            var mode = $(this).data('mode'); // 'pve' ή 'pvp'
            initGame(mode);
        });
        
        // Απόκρυψη του κουμπιού εξόδου στην αρχή
        $('#btn-quit-game').hide();
        // ΝΕΟ: Απόκρυψη και του κουμπιού ακύρωσης στην αρχή
        $('#btn-cancel-pvp').hide(); 

        // ΝΕΟ: Φόρτωσε τα στατιστικά του παίκτη και το leaderboard
        fetchUserStats(); 
        fetchLeaderboard();
    }
});


// ---------------------------------------------------------
// NEW: ΛΟΓΙΚΗ ΕΞΟΔΟΥ (QUIT GAME)
// ---------------------------------------------------------

function quitGame() {
    if (!currentGameId) return;

    if (!confirm("Είσαι σίγουρος/η ότι θέλεις να τερματίσεις το παιχνίδι;")) {
        return;
    }

    // Σταματάμε το polling αμέσως
    if (pollingInterval) clearInterval(pollingInterval);

    $.ajax({
        url: 'api/quit_game.php',
        type: 'POST',
        data: { 
            game_id: currentGameId,
            player_side: myPlayerSide 
        },
        dataType: 'json',
        success: function(response) {
            alert(response.message || "Έξοδος επιτυχής.");
            // Επαναφόρτωση της σελίδας για να επιστρέψει στο Main Menu
            location.reload(); 
        },
        error: function() {
            alert("Σφάλμα κατά τον τερματισμό του παιχνιδιού. Παρακαλώ δοκιμάστε να ανανεώσετε τη σελίδα.");
            location.reload();
        }
    });
}

// ---------------------------------------------------------
// NEW: ΛΟΓΙΚΗ ΑΚΥΡΩΣΗΣ MATCHMAKING (CANCEL)
// ---------------------------------------------------------
function cancelMatchmaking() {
    if (!currentGameId || myPlayerSide !== 1) return; // Μόνο ο P1 μπορεί να ακυρώσει

    // Σταματάμε το polling αμέσως
    if (pollingInterval) clearInterval(pollingInterval);

    if (!confirm("Είσαι σίγουρος/η ότι θέλεις να ακυρώσεις την αναζήτηση αντιπάλου;")) {
        startPolling(); // Ξεκινάμε ξανά το polling αν ο χρήστης το ακυρώσει
        return;
    }

    $.ajax({
        url: 'api/cancel_match.php', // ΝΕΟ API ENDPOINT
        type: 'POST',
        data: { 
            game_id: currentGameId
        },
        dataType: 'json',
        success: function(response) {
            alert(response.message || "Η αναζήτηση ακυρώθηκε.");
            // Επαναφορά στο Main Menu
            currentGameId = null;
            myPlayerSide = 1;
            isInPvPGame = false; // Απενεργοποίηση disconnect detection
            $('#waiting-screen').hide();
            $('#main-menu').removeClass('hidden'); 
            
            // Επαναφορά αρχικών κουμπιών μενού
            $('#btn-play-main').show(); 
            $('#mode-selector').hide(); 
            $('#btn-cancel-pvp').hide(); 
        },
        error: function(xhr, status, error) {
            // Προσθήκη logging για debugging του PHP σφάλματος
            console.error("AJAX Error Status:", status);
            console.error("AJAX Error XHR response:", xhr.responseText); 
            
            alert("Σφάλμα κατά την ακύρωση. Παρακαλώ δοκιμάστε να ανανεώσετε τη σελίδα.");
            location.reload();
        }
    });
}


// ---------------------------------------------------------
// 1. ΛΟΓΙΚΗ ΕΝΑΡΞΗΣ ΠΑΙΧΝΙΔΙΟΥ (MENU & MATCHMAKING)
// ---------------------------------------------------------
function initGame(mode) {
    console.log("Starting game in mode: " + mode);
    
    if (mode === 'pve') {
        // --- ΛΕΙΤΟΥΡΓΙΑ VS COMPUTER ---
        isInPvPGame = false; // PvE δεν χρειάζεται disconnect detection
        $.ajax({
            url: 'api/init_game.php',
            type: 'POST',
            data: { mode: 'pve' },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    alert("Σφάλμα: " + response.error);
                    return;
                }
                $('#main-menu').addClass('hidden');
                $('#game-board').show(); // Εμφάνιση του game board για PvE
                $('#ui-layer').show(); // Εμφάνιση του UI layer για PvE
                currentGameId = response.game_id;
                
                // Στο PvE είμαστε πάντα ο Παίκτης 1
                myPlayerSide = 1; 
                startPolling();
            },
            error: function() {
                alert("Σφάλμα κατά την έναρξη του Bot.");
            }
        });
    } 
    else if (mode === 'pvp') {
        // --- ΛΕΙΤΟΥΡΓΙΑ VS PLAYER 2 ---
        $('#main-menu').addClass('hidden');
        $('#game-board').hide(); // Κρύψε το game board αρχικά
        $('#ui-layer').hide(); // Κρύψε το UI layer αρχικά
        $('#waiting-screen').css('display', 'flex'); // Εμφάνιση οθόνης αναμονής
        $('#btn-cancel-pvp').hide();
        isInPvPGame = true; // Ενεργοποίηση disconnect detection
        
        $.ajax({
            url: 'api/find_match.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                // ΕΛΕΓΧΟΣ ΓΙΑ ΣΦΑΛΜΑ JOIN Ή ΑΣΤΟΧΙΑ
                if (response.error || response.status === 'error') {
                    alert("Σφάλμα εύρεσης παιχνιδιού: " + response.error);
                    $('#waiting-screen').hide();
                    $('#main-menu').removeClass('hidden'); // Επιστροφή στο μενού
                    isInPvPGame = false; // Απενεργοποίηση disconnect detection
                    return;
                }
                
                currentGameId = response.game_id;
                
                // ΑΠΟΘΗΚΕΥΣΗ ΤΟΥ ΡΟΛΟΥ ΜΟΥ (1 ή 2)
                myPlayerSide = response.player_side;

                // ΚΡΙΣΙΜΗ ΔΙΟΡΘΩΣΗ: Κρύβουμε την οθόνη αναμονής ΜΟΝΟ αν μπήκαμε σε ενεργό game
                if (response.status === 'joined') { 
                    $('#waiting-screen').hide();
                }
                // Αν το status είναι 'waiting', το polling (fetchBoardData) θα διαχειριστεί την εμφάνιση
                
                startPolling();
            },
            error: function(xhr, status, error) {
                console.error("Σφάλμα κατά την αναζήτηση παιχνιδιού:", error);
                alert("Σφάλμα κατά την αναζήτηση παιχνιδιού.");
                $('#waiting-screen').hide();
                $('#main-menu').removeClass('hidden'); // Επιστροφή στο μενού
                isInPvPGame = false; // Απενεργοποίηση disconnect detection
            }
        });
    }
}

function startPolling() {
    // Καλούμε αμέσως
    fetchBoardData();
    // Και μετά κάθε 2 δευτερόλεπτα
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(fetchBoardData, 2000);
}


// ---------------------------------------------------------
// 2. ΚΥΡΙΑ ΛΟΓΙΚΗ ΑΝΑΝΕΩΣΗΣ (POLLING)
// ---------------------------------------------------------
function fetchBoardData() {
    // Αν δεν υπάρχει ενεργό παιχνίδι, κρύψε το κουμπί εξόδου
    if (!currentGameId) {
        $('#btn-quit-game').hide(); 
        $('#btn-cancel-pvp').hide(); // Να είμαστε σίγουροι
        return;
    }

    $.ajax({
        url: 'api/get_board.php',
        type: 'GET',
        data: { 
            game_id: currentGameId,
            player_side: myPlayerSide // <--- ΣΤΕΛΝΟΥΜΕ ΤΟ ID ΜΑΣ
        },
        dataType: 'json',
        success: function(data) {
            
            console.log('Polling response:', data); // Debugging log
            
            if (data.error) { 
                console.error("Game data error:", data.error);
                return;
            }

            // A. Έλεγχος για αναμονή αντιπάλου (PvP)
            if (data.status === 'waiting_for_opponent') {
                console.log('Waiting for opponent... Game ID:', currentGameId);
                $('#waiting-screen').show();
                $('#game-board').hide(); // Κρύψε το game board
                $('#ui-layer').hide(); // Κρύψε το UI layer
                // Ενημέρωση τίτλου
                $('#waiting-screen h2').html('Αναζήτηση Αντιπάλου...');
                $('#btn-quit-game').hide(); // Κρύψε το κουμπί αναμονής
                
                // Εμφάνισε το κουμπί ακύρωσης ΜΟΝΟ αν είμαι ο P1
                if (myPlayerSide === 1) {
                     $('#btn-cancel-pvp').show();
                } else {
                     $('#btn-cancel-pvp').hide();
                }
                
                return; 
            }
            
            // B. Έλεγχος για ΤΕΛΟΣ ΠΑΙΧΝΙΔΙΟΥ (Game Over)
            if (data.status === 'finished') {
                $('#waiting-screen').hide();
                $('#btn-cancel-pvp').hide();
                $('#game-over-screen').css('display', 'flex'); 
                
                // Απενεργοποίηση disconnect detection (το παιχνίδι τελείωσε)
                isInPvPGame = false;
                currentGameId = null;
                
                // Κρύψε το κουμπί στο game over
                $('#btn-quit-game').hide(); 

                // Μήνυμα Νίκης/Ήττας/Ισοπαλίας
                // Χρησιμοποιούμε απευθείας το final_message από τον server
                var finalMessage = data.final_message || '';
                
                // Normalize winner values (be defensive)
                var winnerFlag = String(data.winner || '').toLowerCase();
                var iAmWinner = (winnerFlag === 'me' || winnerFlag === '1' || winnerFlag === 'true');
                var oppIsWinner = (winnerFlag === 'opponent' || winnerFlag === '2' || winnerFlag === 'false');

                if (iAmWinner) {
                    $('#go-title').text(finalMessage || 'Νίκησες!');
                    $('#go-title').css('color', '#32cd32');
                } else if (oppIsWinner) {
                    $('#go-title').text(finalMessage || 'Έχασες!');
                    $('#go-title').css('color', '#ff4d4d');
                } else {
                    // draw or unknown: use provided message
                    $('#go-title').text(finalMessage || 'Ισοπαλία!');
                    $('#go-title').css('color', 'gold');
                }

                // Τελικά Σκορ και Αριθμός Καρτών
                $('#go-my-score').text(data.my_score);
                $('#go-opp-score').text(data.opp_score);
                $('#go-my-cards').text(data.my_cards);
                $('#go-opp-cards').text(data.opp_cards);
                
                // Σταματάμε το polling
                if (pollingInterval) clearInterval(pollingInterval);
                return;
            }

            // Γ. Κανονική Ροή Παιχνιδιού
            console.log('Game is active! Status:', data.status);
            
            // Εμφάνιση μηνύματος "Βρέθηκε αντίπαλος!" πριν ξεκινήσει το game
            if ($('#waiting-screen').is(':visible')) {
                $('#waiting-screen h2').html('Βρέθηκε Αντίπαλος! Το παιχνίδι ξεκινάει...');
                $('#waiting-screen h2').css('color', '#00ff00');
                // Μικρή καθυστέρηση για να δει ο χρήστης το μήνυμα
                setTimeout(function() {
                    $('#waiting-screen').hide();
                    $('#game-board').show();
                    $('#ui-layer').show();
                }, 500);
            } else {
                $('#waiting-screen').hide();
                $('#game-board').show(); // Εμφάνιση του game board
                $('#ui-layer').show(); // Εμφάνιση του UI layer
            }
            
            $('#btn-cancel-pvp').hide(); // Κρύψε το κουμπί μόλις βρεθεί game
            
            // Βεβαίωση ότι το disconnect detection είναι ενεργό για PvP
            if (data.game_mode === 'pvp') {
                isInPvPGame = true;
            }
            
            // Εμφάνιση του κουμπιού εξόδου
            $('#btn-quit-game').show();
            
            // Ενημέρωση Ονομάτων
            if (data.my_name) $('#name-me').text(data.my_name);
            if (data.opp_name) $('#name-opp').text(data.opp_name);
            
            // Ενημέρωση τίτλου
            var sideName = (myPlayerSide === 1) ? " (P1)" : " (P2)";
            $('.game-title').text('ΞΕΡΗ #' + currentGameId + sideName);

            // Ζωγραφίζουμε τα πάντα
            renderTable(data.table);
            renderMyHand(data.my_hand);
            renderOpponent(data.opponent_cards_count);
            renderDeck(data.deck_count);
            renderPiles(data.my_score, data.opp_score, data.my_pile_count, data.opp_pile_count);
            
            // Έλεγχος σειράς
            checkTurn(data.is_my_turn, data.game_mode);
        },
        error: function(xhr, status, error) {
            console.error("Σφάλμα σύνδεσης/Polling:", error);
        }
    });
}


// ---------------------------------------------------------
// 3. RENDERING FUNCTIONS (ΕΜΦΑΝΙΣΗ)
// ---------------------------------------------------------

function renderTable(cards) {
    var $tableDiv = $('#table-area');
    $tableDiv.empty();

    if (cards.length === 0) {
        $tableDiv.html('<p style="opacity:0.5; color:rgba(255,255,255,0.7);">Το τραπέζι είναι άδειο</p>');
        return;
    }

    cards.forEach(function(cardCode) {
        var html = '<div class="card"><img src="img/cards/' + cardCode + '.png"></div>';
        $tableDiv.append(html);
    });
}

function renderMyHand(cards) {
    var $handDiv = $('#my-hand');
    
    // Αν παίζω τώρα, μην ξαναζωγραφίζεις για να μην χαλάσει το κλικ
    if ($('body').hasClass('playing')) return;

    $handDiv.empty();

    cards.forEach(function(cardObj, i) { 
        // Βάλε το 'i' στο data-id αντί για το cardObj.id
        var html = '<div class="card my-card" data-id="' + i + '">' +
                '<img src="img/cards/' + cardObj.code + '.png">' +
                '</div>';
        $handDiv.append(html);
    });

    $('.my-card').off('click').on('click', function() {
        var cardId = $(this).data('id');
        playCard(cardId);
    });
}

function renderOpponent(count) {
    var $oppDiv = $('#opponent-hand');
    $oppDiv.empty();

    for (var i = 0; i < count; i++) {
        var backHtml = '<div class="card-back"></div>';
        $oppDiv.append(backHtml);
    }
}

function renderPiles(myScore, oppScore, myCount, oppCount) {
    // Μετατροπή σε αριθμούς για ασφάλεια
    var myC = parseInt(myCount) || 0;
    var oppC = parseInt(oppCount) || 0;

    // --- Η Δικιά μου Στοίβα ---
    var $myPile = $('#my-pile');
    $myPile.empty();
    
    // Εμφανίζουμε εικόνα αν έχω έστω και 1 κάρτα
    if (myC > 0) {
        $myPile.addClass('has-cards'); 
    } else {
        $myPile.removeClass('has-cards');
    }
    // Το σκορ ενημερώνεται στην μπάρα ψηλά
    $('#score-me').text(myScore);


    // --- Στοίβα Αντιπάλου ---
    var $oppPile = $('#opponent-pile');
    $oppPile.empty();
    
    if (oppC > 0) {
        $oppPile.addClass('has-cards');
    } else {
        $oppPile.removeClass('has-cards');
    }
    $('#score-opp').text(oppScore);
}

function renderDeck(count) {
    var $deck = $('#draw-pile');
    $deck.empty();

    if (count > 0) {
        $deck.addClass('has-cards'); 
        $deck.html('<span>' + count + '</span>');
        $deck.css('border', 'none'); 
    } else {
        $deck.removeClass('has-cards');
        $deck.css('border', '2px dashed rgba(255,255,255,0.2)');
    }
}


// ---------------------------------------------------------
// 4. ΛΟΓΙΚΗ ΣΕΙΡΑΣ (CHECK TURN & BOT)
// ---------------------------------------------------------

function checkTurn(isMyTurn, gameMode) {
    if (isMyTurn) {
        $('#my-hand').removeClass('disabled');
        $('#game-status').text("Σειρά σου!"); 
        $('#game-status').css('color', 'gold');
    } else {
        $('#my-hand').addClass('disabled');
        $('#game-status').css('color', '#ccc');
        
        // ΕΛΕΓΧΟΣ: Αν παίζω με Bot, το καλώ να παίξει
        if (gameMode === 'pve') {
            $('#game-status').text("Παίζει ο υπολογιστής...");
            triggerBotPlay(); 
        } else {
            // Αν παίζω PvP, απλά περιμένω τον άνθρωπο
            $('#game-status').text("Περιμένοντας τον Αντίπαλο...");
        }
    }
}

function triggerBotPlay() {
    if (botThinking) return;
    botThinking = true;

    setTimeout(function() {
        $.ajax({
            url: 'api/bot_play.php',
            type: 'GET',
            data: {
                game_id: currentGameId
            },
            success: function(response) {
                console.log("Το Bot έπαιξε:", response);
                botThinking = false;
                fetchBoardData(); 
            },
            error: function(xhr, status, error) {
                console.error("Bot play error:", error);
                botThinking = false;
                setTimeout(triggerBotPlay, 5000); 
            }
        });
    }, 1500);
}

function playCard(cardId) {
    if ($('body').hasClass('playing')) return;
    if ($('#my-hand').hasClass('disabled')) return;

    $('body').addClass('playing');
    
    // Αφαιρούμε τα click events για όσο παίζει
    $('.my-card').off('click'); 

    $.ajax({
        url: 'api/play_card.php',
        type: 'POST',
        data: { 
            card_id: cardId,
            player_side: myPlayerSide,
            game_id: currentGameId
        },
        dataType: 'json',
        success: function(response) {
            $('body').removeClass('playing');

            if (response.error) {
                alert("Σφάλμα: " + response.error);
            } else {
                console.log(response.message);
                if (response.is_xeri) {
                    setTimeout(function(){
                        alert(response.message);
                        fetchBoardData();
                    }, 500); 
                } else {
                    fetchBoardData();
                }
            }
        },
        error: function(xhr, status, error) {
            $('body').removeClass('playing');
            console.error("Error playing card:", error);
            alert("Σφάλμα κίνησης. Δοκίμασε ξανά.");
            fetchBoardData(); 
        }
    });
}