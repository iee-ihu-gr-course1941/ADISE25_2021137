<?php
session_start();

// ========================================
// ADMIN LOGIN SYSTEM
// ========================================
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', '12345');

// Έλεγχος αν ζητήθηκε logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header("Location: view_tables.php");
    exit;
}

// Έλεγχος login
$login_error = '';
if (isset($_POST['admin_login'])) {
    if ($_POST['username'] === ADMIN_USERNAME && $_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: view_tables.php");
        exit;
    } else {
        $login_error = 'Λάθος όνομα χρήστη ή κωδικός!';
    }
}

// Αν δεν είναι συνδεδεμένος, δείξε login form
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - XERI GAME</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 350px;
            text-align: center;
        }
        .login-container h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .login-container p {
            color: #666;
            margin-bottom: 30px;
        }
        .login-container input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .login-container input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            margin-top: 15px;
        }
        .login-container button:hover {
            background: #45a049;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .lock-icon {
            font-size: 50px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="lock-icon">🔒</div>
        <h1>Admin Panel</h1>
        <p>Μόνο για διαχειριστές</p>
        
        <?php if ($login_error): ?>
            <div class="error"><?php echo $login_error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Όνομα χρήστη" required>
            <input type="password" name="password" placeholder="Κωδικός" required>
            <button type="submit" name="admin_login">ΕΙΣΟΔΟΣ</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// ========================================
// ADMIN PANEL (μόνο για συνδεδεμένους)
// ========================================
require_once 'db_connect_pdo.php';
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - XERI GAME</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin: 0;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout-btn:hover {
            background-color: #da190b;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        th {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .empty {
            color: #999;
            font-style: italic;
            padding: 20px;
        }
        .info {
            background-color: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196F3;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        .truncate {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
            font-size: 14px;
        }
        .delete-btn:hover {
            background-color: #da190b;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-left: 4px solid #28a745;
            margin-bottom: 20px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-left: 4px solid #f44336;
            margin-bottom: 20px;
        }
        .stat-box {
            display: inline-block;
            background: white;
            padding: 15px 25px;
            margin: 5px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #4CAF50;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
        }
    </style>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            const successMsg = document.querySelector('.success');
            if (successMsg) {
                setTimeout(function() {
                    successMsg.style.transition = 'opacity 0.5s';
                    successMsg.style.opacity = '0';
                    setTimeout(function() { successMsg.remove(); }, 500);
                }, 3000);
            }
        });
    </script>
</head>
<body>
    <div class="header">
        <h1>🛡️ Admin Panel - XERI GAME</h1>
        <a href="?logout=1" class="logout-btn">🚪 Αποσύνδεση</a>
    </div>
    
<?php
// Εμφάνιση μηνύματος επιτυχίας από session
if (isset($_SESSION['delete_success']) && $_SESSION['delete_success'] === true) {
    echo "<div class='success'>Ο χρήστης διαγράφηκε επιτυχώς!</div>";
    unset($_SESSION['delete_success']); // Αφαίρεση μετά την εμφάνιση
}

// Διαγραφή χρήστη
if (isset($_POST['delete_user'])) {
    $userId = intval($_POST['user_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['delete_success'] = true;
        // Redirect για να αποφύγουμε το resubmit στο refresh
        header("Location: view_tables.php");
        exit;
    } catch (Exception $e) {
        echo "<div class='error'>Σφάλμα διαγραφής: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

try {
    // Προβολή πίνακα users
    echo "<h2>Πίνακας: users</h2>";
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table>";
        echo "<tr>";
        foreach (array_keys($users[0]) as $column) {
            if ($column !== 'password') {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
        }
        echo "<th>Ενέργειες</th>";
        echo "</tr>";
        
        foreach ($users as $row) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                if ($key !== 'password') {
                    echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                }
            }
            echo "<td>";
            echo "<form method='POST' style='display:inline;' onsubmit='return confirm(\"Είστε σίγουροι ότι θέλετε να διαγράψετε τον χρήστη " . htmlspecialchars($row['username']) . "?\");'>";
            echo "<input type='hidden' name='user_id' value='" . $row['id'] . "'>";
            echo "<button type='submit' name='delete_user' class='delete-btn'>✕ Διαγραφή</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='empty'>Δεν υπάρχουν εγγραφές</div>";
    }
    
    // Προβολή πίνακα games
    echo "<h2>Πίνακας: games</h2>";
    $stmt = $pdo->query("SELECT * FROM games ORDER BY id DESC");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($games) > 0) {
        echo "<table>";
        echo "<tr>";
        foreach (array_keys($games[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        foreach ($games as $row) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                // Truncate μεγάλα JSON fields
                if (in_array($key, ['deck', 'player1_hand', 'player2_hand', 'table_cards', 'player1_collected', 'player2_collected'])) {
                    $displayValue = $value ? substr($value, 0, 50) . '...' : '';
                    echo "<td class='truncate' title='" . htmlspecialchars($value ?? '') . "'>" . htmlspecialchars($displayValue) . "</td>";
                } else {
                    echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='empty'>Δεν υπάρχουν εγγραφές</div>";
    }
    
    // Προβολή πίνακα matchmaking_queue
    echo "<h2>Πίνακας: matchmaking_queue</h2>";
    $stmt = $pdo->query("
        SELECT mq.id, mq.user_id, u.username, mq.joined_at 
        FROM matchmaking_queue mq
        LEFT JOIN users u ON mq.user_id = u.id
        ORDER BY mq.joined_at
    ");
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($queue) > 0) {
        echo "<table>";
        echo "<tr>";
        foreach (array_keys($queue[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        foreach ($queue as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='empty'>Δεν υπάρχουν εγγραφές</div>";
    }
    
    // Στατιστικά - με κουτάκια
    echo "<h2>📊 Στατιστικά</h2>";
    
    $activeCount = 0;
    $waitingCount = 0;
    $finishedCount = 0;
    
    foreach ($games as $g) {
        if (isset($g['status'])) {
            if ($g['status'] === 'active') $activeCount++;
            elseif ($g['status'] === 'waiting') $waitingCount++;
            elseif ($g['status'] === 'finished') $finishedCount++;
        }
    }
    
    echo "<div style='margin: 20px 0;'>";
    echo "<div class='stat-box'><div class='stat-number'>" . count($users) . "</div><div class='stat-label'>Χρήστες</div></div>";
    echo "<div class='stat-box'><div class='stat-number'>" . count($games) . "</div><div class='stat-label'>Συνολικά Παιχνίδια</div></div>";
    echo "<div class='stat-box'><div class='stat-number' style='color: #4CAF50;'>" . $activeCount . "</div><div class='stat-label'>Ενεργά</div></div>";
    echo "<div class='stat-box'><div class='stat-number' style='color: #FF9800;'>" . $waitingCount . "</div><div class='stat-label'>Σε Αναμονή</div></div>";
    echo "<div class='stat-box'><div class='stat-number' style='color: #9E9E9E;'>" . $finishedCount . "</div><div class='stat-label'>Ολοκληρωμένα</div></div>";
    echo "<div class='stat-box'><div class='stat-number' style='color: #2196F3;'>" . count($queue) . "</div><div class='stat-label'>Στην Ουρά</div></div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; background-color: #ffebee;'>";
    echo "<strong>Σφάλμα:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

</body>
</html>
