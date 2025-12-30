<?php
// Test που δημιουργεί ένα mock game και υπολογίζει scores
require_once 'db.php';
require_once 'api/functions.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== TESTING GAME SCORING SYSTEM ===\n\n";

// Test 1: Κάρτες με πόντους
$test_cards_1 = ['C1', 'S2', 'D10', 'H11', 'C12', 'S13'];
echo "Test 1 - Cards with points:\n";
echo "Cards: " . json_encode($test_cards_1) . "\n";
$score1 = calculate_card_score($test_cards_1);
echo "Score: $score1 (Expected: 7 = 1+1+2+1+1+1)\n\n";

// Test 2: Μόνο face cards
$test_cards_2 = ['C11', 'D11', 'H11', 'S11', 'C12', 'D12', 'H12', 'S12', 'C13', 'D13', 'H13', 'S13'];
echo "Test 2 - Only face cards (all J, Q, K):\n";
echo "Cards: " . json_encode($test_cards_2) . "\n";
$score2 = calculate_card_score($test_cards_2);
echo "Score: $score2 (Expected: 12 = 12 cards x 1 point)\n\n";

// Test 3: Κάρτες χωρίς πόντους
$test_cards_3 = ['C3', 'D4', 'H5', 'S6', 'C7', 'D8', 'H9'];
echo "Test 3 - Cards without points:\n";
echo "Cards: " . json_encode($test_cards_3) . "\n";
$score3 = calculate_card_score($test_cards_3);
echo "Score: $score3 (Expected: 0)\n\n";

// Test 4: Όλες οι κάρτες που έχουν πόντους
$test_cards_4 = [
    'C1', 'D1', 'H1', 'S1',           // 4 άσσοι = 4
    'S2',                              // S2 = 1
    'C10', 'D10', 'H10', 'S10',       // 4 x 10 = 4, + D10 bonus = 1, total = 5
    'C11', 'D11', 'H11', 'S11',       // 4 x J = 4
    'C12', 'D12', 'H12', 'S12',       // 4 x Q = 4
    'C13', 'D13', 'H13', 'S13'        // 4 x K = 4
];
echo "Test 4 - All scoring cards:\n";
echo "Cards: " . json_encode($test_cards_4) . "\n";
$score4 = calculate_card_score($test_cards_4);
echo "Score: $score4 (Expected: 26 = 4+1+5+4+4+4)\n";
echo "  Breakdown:\n";
echo "  - Aces (4): 4 points\n";
echo "  - S2 (1): 1 point\n";
echo "  - 10s (4): 4 points + D10 bonus (1) = 5 points\n";
echo "  - Jacks (4): 4 points\n";
echo "  - Queens (4): 4 points\n";
echo "  - Kings (4): 4 points\n";
echo "  - TOTAL: 26 points\n\n";

echo "=== ALL TESTS COMPLETED ===\n";
?>
