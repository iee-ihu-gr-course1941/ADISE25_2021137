<?php
require_once 'db.php';
echo "<h2>Δοκιμή Σύνδεσης MySQL</h2>";
echo "<p>✓ Σύνδεση επιτυχής!</p>";
echo "<p>MySQL version: " . $mysqli->server_info . "</p>";
echo "<p>Database: iee2021137</p>";

// Έλεγχος πινάκων
$result = $mysqli->query("SHOW TABLES");
echo "<h3>Πίνακες στη βάση:</h3><ul>";
while ($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// Έλεγχος χρηστών
$result = $mysqli->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
echo "<p>Σύνολο χρηστών: " . $row['count'] . "</p>";

echo "<p><a href='index.html'>Πήγαινε στο παιχνίδι</a></p>";
?>
