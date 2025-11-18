<?php
session_Start();

$host = 'localhost';
$db   = 'xeri_db';
$user = 'root';
$pass = ''; // Αν έχεις βάλει κωδικό στο MySQL, βάλτον εδώ. Συνήθως στο XAMPP είναι κενό.
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Αποτυχία σύνδεσης: " . $mysqli->connect_error);
}

// Για να διαβάζουμε σωστά ελληνικά αν χρειαστεί
$mysqli->set_charset($charset);
?>