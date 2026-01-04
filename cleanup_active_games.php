<?php
require_once 'db_connect_pdo.php';

try {
    // Βρες πόσα active games υπάρχουν
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM games WHERE status = 'active'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = $result['count'];
    
    echo "Βρέθηκαν $count ενεργά παιχνίδια.\n";
    
    if ($count > 0) {
        // Άλλαξε τα σε finished
        $stmt = $pdo->prepare("UPDATE games SET status = 'finished' WHERE status = 'active'");
        $stmt->execute();
        
        echo "Όλα τα ενεργά παιχνίδια έγιναν 'finished'.\n";
    } else {
        echo "Δεν υπάρχουν ενεργά παιχνίδια προς καθαρισμό.\n";
    }
    
} catch (Exception $e) {
    echo "Σφάλμα: " . $e->getMessage() . "\n";
}
?>
