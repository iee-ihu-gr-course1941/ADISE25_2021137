-- Δημιουργία πίνακα χρηστών
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    games_played INT DEFAULT 0,
    games_won INT DEFAULT 0,
    games_lost INT DEFAULT 0,
    total_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Δημιουργία πίνακα παιχνιδιών
CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player1_id INT,
    player2_id INT DEFAULT NULL,
    current_player INT,
    status VARCHAR(20) DEFAULT 'waiting', -- waiting, active, finished
    player1_score INT DEFAULT 0,
    player2_score INT DEFAULT 0,
    deck TEXT, -- JSON με τις κάρτες που απομένουν
    player1_hand TEXT, -- JSON με τα χαρτιά του παίκτη 1
    player2_hand TEXT, -- JSON με τα χαρτιά του παίκτη 2
    table_cards TEXT, -- JSON με τις κάρτες στο τραπέζι
    player1_collected TEXT, -- JSON με τις κάρτες που μάζεψε ο παίκτης 1
    player2_collected TEXT, -- JSON με τις κάρτες που μάζεψε ο παίκτης 2
    last_to_collect INT DEFAULT NULL,
    last_move_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (player2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Δημιουργία πίνακα για matchmaking
CREATE TABLE IF NOT EXISTS matchmaking_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Δημιουργία indexes για καλύτερη απόδοση
CREATE INDEX idx_games_status ON games(status);
CREATE INDEX idx_games_player1 ON games(player1_id);
CREATE INDEX idx_games_player2 ON games(player2_id);
CREATE INDEX idx_users_username ON users(username);

INSERT INTO users (username, password, games_played, games_won, games_lost) 
VALUES ('bot', '$2y$10$dummyHashForBotAccount', 0, 0, 0)
ON DUPLICATE KEY UPDATE username=username;

-- Προβολή όλων των πινάκων
SHOW TABLES;
