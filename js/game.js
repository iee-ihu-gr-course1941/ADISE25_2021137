// js/game.js

var botThinking = false;        // Για να μην παίζει διπλές φορές το Bot
var pollingInterval = null;     // Το χρονόμετρο για την ανανέωση
var myPlayerSide = 1;           // Ποιος είμαι; (1 ή 2). Default 1.
var currentGameId = null;       // Το ID του παιχνιδιού

$(document).ready(function() {
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
});


// ---------------------------------------------------------
// 1. ΛΟΓΙΚΗ ΕΝΑΡΞΗΣ ΠΑΙΧΝΙΔΙΟΥ (MENU & MATCHMAKING)
// ---------------------------------------------------------
function initGame(mode) {
    console.log("Starting game in mode: " + mode);
    
    if (mode === 'pve') {
        // --- ΛΕΙΤΟΥΡΓΙΑ VS COMPUTER ---
        $.ajax({
            url: 'api/init_game.php',
            type: 'POST',
            data: { mode: 'pve' },
            dataType: 'json',
            success: function(response) {
                $('#main-menu').addClass('hidden');
                currentGameId = response.game_id;
                
                // Στο PvE είμαστε πάντα ο Παίκτης 1
                myPlayerSide = 1; 
                console.log("Είμαι ο Παίκτης: " + myPlayerSide);
                
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
        $('#waiting-screen').css('display', 'flex'); // Εμφάνιση οθόνης αναμονής

        $.ajax({
            url: 'api/find_match.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                currentGameId = response.game_id;
                
                // ΑΠΟΘΗΚΕΥΣΗ ΤΟΥ ΡΟΛΟΥ ΜΟΥ (1 ή 2) - ΠΟΛΥ ΣΗΜΑΝΤΙΚΟ
                myPlayerSide = response.player_side;
                console.log("PvP Joined. Είμαι ο Παίκτης: " + myPlayerSide);

                if (response.status === 'joined') {
                    $('#waiting-screen').hide();
                }
                startPolling();
            },
            error: function() {
                alert("Σφάλμα κατά την αναζήτηση παιχνιδιού.");
                $('#waiting-screen').hide();
                $('#main-menu').removeClass('hidden'); // Επιστροφή στο μενού
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
    if (!currentGameId) return;

    $.ajax({
        url: 'api/get_board.php',
        type: 'GET',
        data: { 
            game_id: currentGameId,
            player_side: myPlayerSide // <--- ΣΤΕΛΝΟΥΜΕ ΤΟ ID ΜΑΣ
        },
        dataType: 'json',
        success: function(data) {
            // A. Έλεγχος για αναμονή αντιπάλου (PvP)
            if (data.status === 'waiting_for_opponent') {
                $('#waiting-screen').show();
                $('#waiting-screen h2').html('Αναζήτηση Αντιπάλου...<br><small>Game ID: ' + currentGameId + '</small>');
                return; 
            }
            
            // B. Έλεγχος για ΤΕΛΟΣ ΠΑΙΧΝΙΔΙΟΥ (Game Over)
            if (data.status === 'finished') {
                $('#waiting-screen').hide();
                $('#game-over-screen').css('display', 'flex'); // Εμφάνιση οθόνης τέλους
                
                // Μήνυμα Νίκης/Ήττας/Ισοπαλίας
                $('#go-title').text(data.final_message); 
                
                if (data.winner === 'me') {
                    $('#go-title').css('color', '#32cd32'); // Πράσινο για νίκη
                } else if (data.winner === 'opponent') {
                    $('#go-title').css('color', '#ff4d4d'); // Κόκκινο για ήττα
                } else {
                    $('#go-title').css('color', 'gold'); // Χρυσό για ισοπαλία
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
            $('#waiting-screen').hide();

            // Ενημέρωση τίτλου
            var sideName = (myPlayerSide === 1) ? " (Εγώ: P1)" : " (Εγώ: P2)";
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
            console.error("Σφάλμα σύνδεσης:", error);
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
        $tableDiv.html('<p style="opacity:0.5">Το τραπέζι είναι άδειο</p>');
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

    cards.forEach(function(cardObj) {
        var html = '<div class="card my-card" data-id="' + cardObj.id + '">' +
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
    
    // Εμφανίζουμε εικόνα αν έχω έστω και 1 κάρτα (χωρίς νούμερο μέσα)
    if (myC > 0) {
        $myPile.addClass('has-cards'); 
    } else {
        $myPile.removeClass('has-cards');
    }
    // Το σκορ ενημερώνεται ΜΟΝΟ στην μπάρα ψηλά
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
            success: function(response) {
                console.log("Το Bot έπαιξε:", response);
                botThinking = false;
                fetchBoardData(); 
            },
            error: function() {
                botThinking = false;
            }
        });
    }, 1500);
}

function playCard(cardId) {
    if ($('body').hasClass('playing')) return;
    if ($('#my-hand').hasClass('disabled')) return;

    $('body').addClass('playing');
    console.log("Παίζω το χαρτί ID: " + cardId);

    $.ajax({
        url: 'api/play_card.php',
        type: 'POST',
        data: { 
            card_id: cardId,
            player_side: myPlayerSide // <--- ΣΤΕΛΝΟΥΜΕ ΤΟ ID ΜΑΣ!
        },
        dataType: 'json',
        success: function(response) {
            $('body').removeClass('playing');

            if (response.error) {
                alert("Σφάλμα: " + response.error);
            } else {
                console.log(response.message);
                if (response.is_xeri) {
                    alert(response.message); 
                }
                fetchBoardData();
            }
        },
        error: function(xhr, status, error) {
            $('body').removeClass('playing');
            console.error("Error playing card:", error);
        }
    });
}