<?php
require_once '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$user = trim($_POST['username']);
$pass = $_POST['password'];

$stmt = $mysqli->prepare("SELECT id, username, password FROM users WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    if (password_verify($pass, $row['password'])) {
        // Login Success - Αποθήκευση στο Session
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['error' => 'Λάθος κωδικός']);
    }
} else {
    echo json_encode(['error' => 'Ο χρήστης δεν βρέθηκε']);
}
?>