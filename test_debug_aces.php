<?php
// Debug συγκεκριμένα τους άσσους

$test_aces = ['C1', 'D1', 'H1', 'S1'];

echo "Testing Aces specifically:\n";
foreach ($test_aces as $card) {
    $suit = substr($card, 0, 1);
    $rank_str = substr($card, 1);
    $rank = intval(substr($card, 1));
    
    echo "Card: $card | Suit: '$suit' | Rank String: '$rank_str' | Rank Int: $rank | ";
    echo "rank === 1? " . ($rank === 1 ? 'YES' : 'NO') . "\n";
}

// Τώρα με όλες τις κάρτες
echo "\n\nTesting all cards from Test 4:\n";
$all_cards = [
    'C1', 'D1', 'H1', 'S1',
    'S2',
    'C10', 'D10', 'H10', 'S10',
    'C11', 'D11', 'H11', 'S11',
    'C12', 'D12', 'H12', 'S12',
    'C13', 'D13', 'H13', 'S13'
];

$score = 0;
foreach ($all_cards as $card) {
    $suit = substr($card, 0, 1);
    $rank = intval(substr($card, 1));
    $points_this_card = 0;
    
    // Άσσοι
    if ($rank === 1) {
        $points_this_card += 1;
    }
    
    // S2
    if ($card === 'S2') {
        $points_this_card += 1;
    }
    
    // J, Q, K
    if ($rank === 11 || $rank === 12 || $rank === 13) {
        $points_this_card += 1;
    }
    
    // 10s
    if ($rank === 10) {
        $points_this_card += 1;
        if ($card === 'D10') {
            $points_this_card += 1;
        }
    }
    
    $score += $points_this_card;
    echo "Card: $card | Rank: $rank | Points: $points_this_card | Running Total: $score\n";
}

echo "\n\nFinal Score: $score\n";
?>
