<?php
$servername = "localhost";
$username = "iee2021137";
$password = "pantelis2003";
$dbname = "iee2021137"; // Το όνομα της βάσης είναι ίδιο με το username
$socket = "/home/student/iee/2021/iee2021137/mysql/run/mysql.sock";

// Δημιουργία σύνδεσης (Πρόσεξε το null πριν το $socket)
$conn = new mysqli($servername, $username, $password, $dbname, null, $socket);

// Έλεγχος σύνδεσης
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully"; // Αυτό το βγάζουμε σε σχόλιο όταν δουλέψει
?>