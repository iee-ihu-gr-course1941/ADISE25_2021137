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
        },
        error: function(xhr, status, error) {
            console.error("Σφάλμα σύνδεσης:", error);
        }
    });
}

// ---------------------------------------------------------
// 1. Ζωγραφίζει το Τραπέζι (Κέντρο)
// ---------------------------------------------------------
function renderTable(cards) {
    var $tableDiv = $('#table-area');
    $tableDiv.empty(); // Καθαρίζουμε τα παλιά

    if (cards.length === 0) {
        $tableDiv.html('<p style="opacity:0.5">Το τραπέζι είναι άδειο</p>');
        return;
    }

    // Για κάθε χαρτί που ήρθε από τη βάση
    cards.forEach(function(cardCode) {
        // Φτιάχνουμε την εικόνα: <img src="img/cards/C10.png" class="card">
        var imgHtml = '<img src="img/cards/' + cardCode + '.png" class="card">';
        $tableDiv.append(imgHtml);
    });
}

// ---------------------------------------------------------
// 2. Ζωγραφίζει τα χαρτιά ΜΟΥ (Κάτω)
// ---------------------------------------------------------
function renderMyHand(cards) {
    var $handDiv = $('#my-hand');
    $handDiv.empty();

cards.forEach(function(cardObj) {
        // Απευθείας χρήση του κωδικού
        var html = '<div class="card my-card" data-id="' + cardObj.id + '">' +
                        '<img src="img/cards/' + cardObj.code + '.png">' +
                   '</div>';
    $handDiv.append(html);
    });

    // Προσθέτουμε το event listener για το ΚΛΙΚ (μόνο στα δικά μου)
    $('.my-card').off('click').on('click', function() {
        var cardId = $(this).data('id');
        playCard(cardId);
    });
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
function playCard(cardId) {
    console.log("Προσπάθεια να παίξω το χαρτί με ID: " + cardId);
    alert("Θα παίξεις το χαρτί με ID: " + cardId);
}