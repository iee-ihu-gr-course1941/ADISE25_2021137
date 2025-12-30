<?php
// Test για το scoring system

function calculate_card_score($cards) {
    $score = 0;
    echo "Testing cards: " . json_encode($cards) . "\n";
    
    foreach ($cards as $card) {
        $suit = substr($card, 0, 1);
        $rank = intval(substr($card, 1));
        
        echo "Card: $card, Suit: $suit, Rank: $rank\n";
        
        // Όλοι οι άσσοι: 1 πόντος
        if ($rank === 1) {
            $score += 1;
            echo "  -> Ace detected: +1 (total: $score)\n";
        }
        
        // S2 (2 Σπαθί): 1 πόντος
        if ($card === 'S2') {
            $score += 1;
            echo "  -> S2 detected: +1 (total: $score)\n";
        }
        
        // Όλα τα Q (12), J (11), K (13): 1 πόντος
        if ($rank === 11 || $rank === 12 || $rank === 13) {
            $score += 1;
            echo "  -> Face card (J/Q/K) detected: +1 (total: $score)\n";
        }
        
        // Όλα τα 10άρια: 1 πόντος
        if ($rank === 10) {
            $score += 1;
            echo "  -> 10 detected: +1 (total: $score)\n";
            // D10 (10 Καρό) παίρνει επιπλέον 1 πόντο (σύνολο 2)
            if ($card === 'D10') {
                $score += 1;
                echo "  -> D10 bonus: +1 (total: $score)\n";
            }
        }
    }
    echo "Final score: $score\n\n";
    return $score;
}

// Test με διάφορες κάρτες
echo "=== Test 1: Άσσοι ===\n";
calculate_card_score(['C1', 'D1', 'H1', 'S1']);

echo "\n=== Test 2: Face cards (J, Q, K) ===\n";
calculate_card_score(['C11', 'D12', 'H13', 'S11']);

echo "\n=== Test 3: 10άρια ===\n";
calculate_card_score(['C10', 'D10', 'H10', 'S10']);

echo "\n=== Test 4: Μικτές κάρτες ===\n";
calculate_card_score(['C1', 'S2', 'D10', 'H11', 'C12', 'S13', 'D5']);

echo "\n=== Test 5: Κάρτες χωρίς πόντους ===\n";
calculate_card_score(['C3', 'D4', 'H5', 'S6', 'C7', 'D8', 'H9']);
?>
