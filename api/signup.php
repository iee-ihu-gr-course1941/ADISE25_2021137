<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$user = trim($_POST['username']);
$pass = $_POST['password'];
$pass_conf = $_POST['password_confirm'];

if (empty($user) || empty($pass)) {
    echo json_encode(['error' => 'Συμπλήρωσε όλα τα πεδία']);
    exit;
}

if ($pass !== $pass_conf) {
    echo json_encode(['error' => 'Οι κωδικοί δεν ταιριάζουν']);
    exit;
}

// Έλεγχος αν υπάρχει ήδη
$stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['error' => 'Το όνομα χρήστη υπάρχει ήδη']);
    exit;
}

// Εγγραφή με κρυπτογράφηση
$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $user, $hash);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['error' => 'Σφάλμα βάσης']);
}
?>