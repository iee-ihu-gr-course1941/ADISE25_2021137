<?php
$servername = "localhost";
$username = "iee2021137";
$password = "pantelis2003";
$dbname = "iee2021137";
$socket = "/home/student/iee/2021/iee2021137/mysql/run/mysql.sock";

try {
    // PDO connection
    $dsn = "mysql:unix_socket=$socket;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
