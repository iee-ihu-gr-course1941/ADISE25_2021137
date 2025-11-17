$(document).ready(function() {
    console.log("Game initialized with ID: " + currentGameId);

    // 1. Ξεκινάμε το "Polling" (Ρωτάμε τον server κάθε 2 δευτερόλεπτα)
    setInterval(fetchBoardData, 2000);

    // Καλούμε τη συνάρτηση και μία φορά στην αρχή για να μην περιμένουμε
    fetchBoardData();
});

// ---------------------------------------------------------
// Συνάρτηση που ρωτάει τον Server τι συμβαίνει
// ---------------------------------------------------------
function fetchBoardData() {
    $.ajax({
        url: 'api/get_board.php',
        type: 'GET',
        data: { game_id: currentGameId },
        dataType: 'json',
        success: function(data) {
            // Αν όλα πήγαν καλά, ζωγράφισε το ταμπλό
            renderTable(data.table);
            renderMyHand(data.my_hand);
            renderOpponent(data.opponent_cards_count);
            renderPiles(data.my_pile_count, data.opp_pile_count);
        },
        error: function(xhr, status, error) {
            console.error("Σφάλμα σύνδεσης:", error);
        }
    });
}

// 1. Ζωγραφίζει το Τραπέζι
function renderTable(cards) {
    var $tableDiv = $('#table-area');
    $tableDiv.empty();

    if (cards.length === 0) {
        $tableDiv.html('<p style="opacity:0.5">Το τραπέζι είναι άδειο</p>');
        return;
    }

    cards.forEach(function(cardCode) {
        // Η ΣΩΣΤΗ ΔΟΜΗ: <div> με class="card" και ΜΕΣΑ της το <img>
        var html = '<div class="card"><img src="img/cards/' + cardCode + '.png"></div>';
        $tableDiv.append(html);
    });
}

// 2. Ζωγραφίζει τα χαρτιά ΜΟΥ
function renderMyHand(cards) {
    var $handDiv = $('#my-hand');
    $handDiv.empty();

    cards.forEach(function(cardObj) {
        // Η ΣΩΣΤΗ ΔΟΜΗ: <div> με class="card my-card" και ΜΕΣΑ της το <img>
        var html = '<div class="card my-card" data-id="' + cardObj.id + '"><img src="img/cards/' + cardObj.code + '.png"></div>';
        $handDiv.append(html);
    });

    // Προσθέτουμε το event listener για το ΚΛΙΚ (μόνο στα δικά μου)
    $('.my-card').off('click').on('click', function() {
        var cardId = $(this).data('id');
        playCard(cardId);
    });
}

function renderPiles(myCount, oppCount) {
    // 1. Η δικιά μου στοίβα
    var $myPile = $('#my-pile');
    $myPile.empty();
    
    if (myCount > 0) {
        $myPile.addClass('has-cards');
        // Βάζουμε ΜΟΝΟ το νούμερο. Η εικόνα μπαίνει αυτόματα από το CSS (.has-cards)
        $myPile.html('<span>' + myCount + '</span>');
    } else {
        $myPile.removeClass('has-cards');
    }

    // 2. Η στοίβα του αντιπάλου
    var $oppPile = $('#opponent-pile');
    $oppPile.empty();
    
    if (oppCount > 0) {
        $oppPile.addClass('has-cards');
        // Και εδώ το ίδιο
        $oppPile.html('<span>' + oppCount + '</span>');
    } else {
        $oppPile.removeClass('has-cards');
    }
    // ΝΕΟ: Ενημέρωση του Scoreboard ψηλά στην οθόνη
    $('#score-me').text(myCount);
    $('#score-opp').text(oppCount);
}

// ---------------------------------------------------------
// 3. Ζωγραφίζει τον Αντίπαλο (Πάνω)
// ---------------------------------------------------------
// 3. Ζωγραφίζει τον Αντίπαλο
function renderOpponent(count) {
    var $oppDiv = $('#opponent-hand');
    $oppDiv.empty();

    for (var i = 0; i < count; i++) {
        var backHtml = '<div class="card-back"></div>';
        $oppDiv.append(backHtml);
    }
}

// ---------------------------------------------------------
// 4. Η κίνηση (Όταν πατάω χαρτί) - Θα το φτιάξουμε στο επόμενο βήμα
// ---------------------------------------------------------
// 4. Κίνηση
// js/game.js (Τμήμα)

function playCard(cardId) {
    // 1. Κλείδωμα: Απαγορεύουμε να πατήσεις 2ο κλικ μέχρι να τελειώσει το πρώτο
    if ($('body').hasClass('playing')) return;
    $('body').addClass('playing');

    console.log("Παίζω το χαρτί ID: " + cardId);

    $.ajax({
        url: 'api/play_card.php', // Ο προορισμός
        type: 'POST',             // Στέλνουμε δεδομένα κρυφά
        data: { 
            card_id: cardId       // Ποιο χαρτί παίξαμε
        },
        dataType: 'json',
        success: function(response) {
            $('body').removeClass('playing'); // Ξεκλειδώνουμε

            if (response.error) {
                alert("Σφάλμα: " + response.error);
            } else {
                console.log(response.message); 
                
                // Αν έγινε ΞΕΡΗ, βγάλε ένα μήνυμα!
                if (response.is_xeri) {
                    alert("🔥 ΞΕΡΗ!!! 🔥");
                }

                // Ανανέωσε το τραπέζι αμέσως
                fetchBoardData();
            }
        },
        error: function(xhr, status, error) {
            $('body').removeClass('playing');
            console.error("Error playing card:", error);
        }
    });
}