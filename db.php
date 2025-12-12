<?php
// Ορισμός φακέλου για sessions
ini_set('session.save_path', realpath(__DIR__ . '/sessions'));

// Έλεγχος: Ξεκινάμε session ΜΟΝΟ αν δεν έχει ξεκινήσει ήδη
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "iee2021137";
$password = "pantelis2003";
$dbname = "iee2021137";
$socket = "/home/student/iee/2021/iee2021137/mysql/run/mysql.sock";

// Δημιουργία σύνδεσης
$mysqli = new mysqli($servername, $username, $password, $dbname, null, $socket);

// Έλεγχος σύνδεσης
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Ρύθμιση ελληνικών
$mysqli->set_charset("utf8mb4");