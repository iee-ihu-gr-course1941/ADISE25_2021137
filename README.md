# 🎴 XERI Game - Detailed Documentation

**🎮 Play Now:** [https://users.iee.ihu.gr/~iee2021137/index.php](https://users.iee.ihu.gr/~iee2021137/index.php)

**Project Name:** XERI (Ξερή) - Traditional Greek Card Game  
**Version:** 1.0.0 (Released)  
**Author:** Pantelis Politis  
**Platform:** Web Application (PHP + MySQL + JavaScript)

---

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [Game Rules & Gameplay](#game-rules--gameplay)
3. [Technology Stack](#technology-stack)
4. [Database Structure](#database-structure)
5. [File Structure](#file-structure)
6. [API Endpoints Analysis](#api-endpoints-analysis)
7. [Frontend Components](#frontend-components)
8. [Game Flow Diagrams](#game-flow-diagrams)
9. [Installation Guide](#installation-guide)
10. [Known Issues & Troubleshooting](#known-issues--troubleshooting)

---

## 📖 Project Overview

XERI (Ξερή) is a web-based implementation of the traditional Greek card game. The application supports:

- **PvE Mode (Player vs Computer):** Play against an AI bot
- **PvP Mode (Player vs Player):** Real-time multiplayer with matchmaking
- **User Authentication:** Registration, Login, Logout system
- **Statistics Tracking:** Wins, Losses, Games Played
- **Leaderboard:** Top 5 players ranking
- **Disconnect Detection:** Automatic win/loss assignment on player disconnect

---

## 🎮 Game Rules & Gameplay

### Objective
The goal of XERI is to collect cards from the table and score more points than your opponent. The player with the highest score at the end of the game wins.

### The Deck
- Standard 52-card deck
- Card encoding: `{Suit}{Rank}` (e.g., C1 = Ace of Clubs, D10 = 10 of Diamonds)
- **Suits:** C (Clubs/Σπαθί), D (Diamonds/Καρό), H (Hearts/Κούπα), S (Spades/Μπαστούνι)
- **Ranks:** 1-13 (1=Ace, 11=Jack, 12=Queen, 13=King)

### Initial Deal
1. **4 cards** are placed face-up on the table
2. **6 cards** are dealt to each player
3. Remaining cards form the draw pile

### Turn Actions
On each turn, a player must play ONE card from their hand. Three outcomes are possible:

#### 1. **Capture by Matching (Μάζεμα)**
- If your card matches the rank of the **top card** on the table
- You collect your card AND all table cards
- Cards go to your scoring pile

#### 2. **Capture by Jack (Σκούπισμα με Βαλέ)**
- Jack (J/11) is a special card
- **When table has cards:** Jack captures ALL cards on the table
- **When table is empty:** Jack is simply placed on the table

#### 3. **Drop (Ρίξιμο)**
- If no match is possible and you don't play a Jack
- Your card stays on the table

### XERI (Ξερή) - Special Scoring
**XERI** occurs when you capture a **single card** from the table:

| Scenario | Bonus Points |
|----------|-------------|
| Capture single card with matching rank | **+10 points** |
| Capture single Jack with Jack | **+20 points** |

### Redeal
- When both players' hands are empty AND cards remain in the deck
- Each player receives 6 new cards
- Table cards remain unchanged

### End Game
When the deck is empty AND both hands are empty:

1. **Remaining table cards** go to the last player who collected
2. **Card points** are calculated:

| Card | Points |
|------|--------|
| Any Ace (1) | 1 point |
| 2 of Spades (S2) | 1 point |
| 10 of Diamonds (D10) | 2 points |
| Any other 10 | 1 point |
| Jack (J/11) | 1 point |
| Queen (Q/12) | 1 point |
| King (K/13) | 1 point |
| **Most cards bonus** | **+3 points** |

3. **Total Points Available:** 25 points (22 from cards + 3 bonus)
4. XERI bonus points are added to the final score
5. **Winner:** Player with highest total score

### PvP Special Rules
- **Disconnect:** Player who disconnects loses automatically
- **Quit:** Player who quits loses, opponent wins
- **Timeout:** 60 seconds of inactivity = auto-loss

---

## 🛠 Technology Stack

### Front-end Technologies
| Technology | Purpose |
|------------|---------|
| **HTML5** | Page structure and semantic markup |
| **CSS3** | Styling, animations, responsive design |
| **JavaScript (ES6)** | Client-side game logic and UI interactions |
| **jQuery 3.6.0** | DOM manipulation and AJAX requests |
| **Font Awesome 6.0** | Icons for UI elements |

### Back-end Technologies
| Technology | Purpose |
|------------|---------|
| **PHP 7.4+** | Server-side logic and API endpoints |
| **MySQL 5.7+** | Database for persistent storage |

### Communication
| Method | Usage |
|--------|-------|
| **AJAX (XMLHttpRequest)** | Asynchronous API calls |
| **JSON** | Data exchange format |
| **Polling (2-second interval)** | Real-time game state updates |
| **SendBeacon API** | Disconnect detection on page close |

---

## 🗄 Database Structure

### Table: `users`
Stores registered user information and statistics.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT | Primary Key - Unique user identifier |
| `username` | VARCHAR(50) UNIQUE | User's display name (must be unique) |
| `password` | VARCHAR(255) | BCrypt hashed password |
| `games_played` | INT DEFAULT 0 | Total number of games played |
| `games_won` | INT DEFAULT 0 | Number of games won |
| `games_lost` | INT DEFAULT 0 | Number of games lost |
| `total_score` | INT DEFAULT 0 | Cumulative score across all games |
| `created_at` | TIMESTAMP | Account creation timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE INDEX on `username`

---

### Table: `games`
Stores all game sessions and their current state.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT | Primary Key - Unique game identifier |
| `player1_id` | INT | Foreign Key → users.id (game creator) |
| `player2_id` | INT DEFAULT NULL | Foreign Key → users.id (NULL for bot games) |
| `current_player` | INT | Whose turn: 1 or 2 |
| `status` | VARCHAR(20) | Game state: 'waiting', 'active', 'finished' |
| `player1_score` | INT DEFAULT 0 | Player 1's XERI bonus points |
| `player2_score` | INT DEFAULT 0 | Player 2's XERI bonus points |
| `deck` | TEXT | JSON array of remaining deck cards |
| `player1_hand` | TEXT | JSON array of Player 1's hand |
| `player2_hand` | TEXT | JSON array of Player 2's hand |
| `table_cards` | TEXT | JSON array of cards on table |
| `player1_collected` | TEXT | JSON array of P1's collected cards |
| `player2_collected` | TEXT | JSON array of P2's collected cards |
| `last_to_collect` | INT DEFAULT NULL | Last player who collected (1 or 2) |
| `last_move_time` | TIMESTAMP | Auto-updates on each move |
| `created_at` | TIMESTAMP | Game creation timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `status` (for matchmaking queries)
- INDEX on `player1_id`
- INDEX on `player2_id`

**Foreign Keys:**
- `player1_id` → `users(id)` ON DELETE CASCADE
- `player2_id` → `users(id)` ON DELETE CASCADE

---

### Table: `matchmaking_queue`
Temporary storage for players searching for PvP matches.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT | Primary Key |
| `user_id` | INT UNIQUE | Foreign Key → users.id |
| `joined_at` | TIMESTAMP | When player joined the queue |

**Purpose:** Ensures only one entry per user in the matchmaking queue.

---

### Table: `game_presence` (Auto-created)
Tracks player activity for disconnect detection in PvP games.

| Column | Type | Description |
|--------|------|-------------|
| `game_id` | INT | Game being monitored |
| `user_id` | INT | Player being tracked |
| `last_seen` | TIMESTAMP | Last heartbeat timestamp |

**Composite Primary Key:** (game_id, user_id)

---

## 📁 File Structure

```
xeri_game/
│
├── 📄 index.php              # Main entry point (game UI)
├── 📄 index.html             # Static HTML template (unused)
├── 📄 db.php                 # Primary database connection (mysqli + sessions)
├── 📄 db_connect.php         # Alternative mysqli connection
├── 📄 db_connect_pdo.php     # PDO connection for utilities
├── 📄 setup_database.sql     # Database schema creation script
├── 📄 test_connection.php    # Database connection tester
├── 📄 view_tables.php        # Admin panel with authentication
├── 📄 cleanup_active_games.php # Utility to reset stuck games
├── 📄 test_scoring.php       # Unit tests for scoring logic
├── 📄 README.md              # Basic documentation
│
├── 📁 api/                   # Backend API endpoints
│   ├── 📄 functions.php      # Core game logic functions
│   ├── 📄 login.php          # User authentication
│   ├── 📄 signup.php         # User registration
│   ├── 📄 logout.php         # Session termination
│   ├── 📄 init_game.php      # PvE game initialization
│   ├── 📄 find_match.php     # PvP matchmaking
│   ├── 📄 get_board.php      # Game state retrieval (polling)
│   ├── 📄 play_card.php      # Player move execution
│   ├── 📄 bot_play.php       # AI move execution
│   ├── 📄 get_stats.php      # User statistics retrieval
│   ├── 📄 get_leaderboard.php# Top players ranking
│   ├── 📄 quit_game.php      # Voluntary game exit
│   ├── 📄 cancel_match.php   # Cancel matchmaking
│   ├── 📄 cancel_game.php    # Legacy cancel endpoint
│   └── 📄 player_disconnect.php # Browser close handler
│
├── 📁 js/                    # Frontend JavaScript
│   └── 📄 game.js            # Main game client logic
│
├── 📁 css/                   # Stylesheets
│   └── 📄 style.css          # Complete application styling
│
├── 📁 img/                   # Image assets
│   └── 📁 cards/             # Card images (52 cards + back)
│       ├── 📄 C1.png         # Ace of Clubs
│       ├── 📄 D10.png        # 10 of Diamonds
│       ├── 📄 back.png       # Card back image
│       └── ...               # All 52 cards
│
└── 📁 sessions/              # PHP session storage
```

---

## 🔌 API Endpoints Analysis

### Authentication Endpoints

---

### 📍 `api/login.php` - User Authentication

**Purpose:** Validates user credentials and establishes a session.

**HTTP Method:** `POST`

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `username` | string |  Yes | The user's login name |
| `password` | string |  Yes | The user's plain-text password |

**Process Flow:**
1. Receives POST request with credentials
2. Trims whitespace from username
3. Queries database for user with matching username
4. Uses `password_verify()` to compare against BCrypt hash
5. On success: stores `user_id` and `username` in `$_SESSION`
6. Returns JSON response

**Success Response:**
```json
{
    "status": "success"
}
```

**Error Responses:**
```json
{
    "error": "Ο χρήστης δεν βρέθηκε"
}
```
```json
{
    "error": "Λάθος κωδικός"
}
```

**Session Variables Set:**
- `$_SESSION['user_id']` - User's database ID
- `$_SESSION['username']` - User's display name

**Security Features:**
- Password is never stored in plain text
- Uses prepared statements to prevent SQL injection
- BCrypt hashing with automatic salt

**Frontend Integration:**
```javascript
$.post('api/login.php', {
    username: $('#l-user').val(),
    password: $('#l-pass').val()
}, function(res) {
    if(res.status === 'success') location.reload();
    else alert(res.error);
}, 'json');
```

---

### 📍 `api/signup.php` - User Registration

**Purpose:** Creates a new user account with hashed password.

**HTTP Method:** `POST`

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `username` | string |  Yes | Desired username |
| `password` | string |  Yes | Desired password |
| `password_confirm` | string |  Yes | Password confirmation |

**Process Flow:**
1. Receives POST request with registration data
2. Validates all fields are filled
3. Checks passwords match
4. Checks username doesn't already exist
5. Hashes password using `password_hash()` with `PASSWORD_DEFAULT`
6. Inserts new user into database
7. Returns JSON response

**Success Response:**
```json
{
    "status": "success"
}
```

**Error Responses:**
```json
{
    "error": "Συμπλήρωσε όλα τα πεδία"
}
```
```json
{
    "error": "Οι κωδικοί δεν ταιριάζουν"
}
```
```json
{
    "error": "Το όνομα χρήστη υπάρχει ήδη"
}
```
```json
{
    "error": "Σφάλμα βάσης"
}
```

**Database Query:**
```sql
INSERT INTO users (username, password) VALUES (?, ?)
```

**Security Features:**
- Uses prepared statements for all queries
- BCrypt hashing (PASSWORD_DEFAULT = currently bcrypt)
- Unique username constraint at database level

---

### 📍 `api/logout.php` - Session Termination

**Purpose:** Logs out the current user by destroying their session.

**HTTP Method:** `POST`

**Request Parameters:** None required

**Process Flow:**
1. Clears all session variables: `$_SESSION = array()`
2. Deletes session cookie from browser
3. Destroys server-side session: `session_destroy()`
4. Returns success response

**Success Response:**
```json
{
    "status": "success",
    "message": "Logged out successfully"
}
```

**Cookie Deletion Code:**
```php
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
```

---

### Game Initialization Endpoints

---

### 📍 `api/init_game.php` - PvE Game Initialization

**Purpose:** Creates a new game session against the computer (bot).

**HTTP Method:** `POST`

**Required Session:** User must be logged in (`$_SESSION['user_id']`)

**Request Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `mode` | string |  No | 'pve' | Game mode (always 'pve' for this endpoint) |

**Process Flow:**
1. Verifies user is logged in
2. Creates new game record:
   - `player1_id` = current user
   - `player2_id` = NULL (indicates bot game)
   - `status` = 'active'
   - `current_player` = 1 (human goes first)
3. Generates shuffled 52-card deck
4. Saves deck to database
5. Deals initial cards:
   - 4 to table
   - 6 to player 1 (human)
   - 6 to player 2 (bot)
6. Returns game details

**Success Response:**
```json
{
    "status": "success",
    "game_id": 42,
    "mode": "pve",
    "player_side": 1,
    "message": "Game initialized!"
}
```

**Error Response:**
```json
{
    "error": "Not logged in"
}
```
```json
{
    "error": "Database error: [MySQL error message]"
}
```

**Database Operations:**
```sql
-- Create game
INSERT INTO games (player1_id, player2_id, status, current_player) 
VALUES ($my_id, NULL, 'active', 1)

-- Save deck
UPDATE games SET deck = '[JSON array]' WHERE id = $game_id

-- Deal cards
UPDATE games SET 
    deck = '[remaining cards]',
    table_cards = '[4 cards]',
    player1_hand = '[6 cards]',
    player2_hand = '[6 cards]',
    player1_collected = '[]',
    player2_collected = '[]'
WHERE id = $game_id
```

**Key Functions Used:**
- `generate_shuffled_deck()` - Creates and shuffles 52 cards
- `save_deck_to_db()` - Stores deck as JSON
- `deal_initial_cards()` - Distributes initial cards

---

### 📍 `api/find_match.php` - PvP Matchmaking

**Purpose:** Finds an existing waiting game or creates a new one for PvP play.

**HTTP Method:** `POST`

**Required Session:** User must be logged in

**Request Parameters:** None (uses session data)

**Process Flow - Two Scenarios:**

#### Scenario A: Found Existing Waiting Game
1. Query for games with `status = 'waiting'` AND `player2_id IS NULL`
2. Uses `FOR UPDATE` lock to prevent race conditions
3. **If I am Player 1 (creator):**
   - Return status 'waiting' (still waiting for opponent)
4. **If I am a different user:**
   - Update game: set `player2_id` to my ID, `status` to 'active'
   - Remove Player 1 from matchmaking_queue
   - Return status 'joined'

#### Scenario B: No Waiting Game Found
1. Create new game with `status = 'waiting'`
2. Generate and save shuffled deck
3. Deal initial cards
4. Add myself to matchmaking_queue
5. Return status 'waiting'

**Success Responses:**

*Waiting for opponent:*
```json
{
    "status": "waiting",
    "game_id": 42,
    "player_side": 1,
    "message": "Περιμένουμε αντίπαλο..."
}
```

*Joined existing game:*
```json
{
    "status": "joined",
    "game_id": 42,
    "player_side": 2,
    "message": "Βρέθηκε αντίπαλος! Το παιχνίδι ξεκινάει."
}
```

*Still waiting (for creator):*
```json
{
    "status": "waiting",
    "game_id": 42,
    "player_side": 1,
    "message": "Περιμένουμε ακόμα..."
}
```

**Error Response:**
```json
{
    "error": "Not logged in"
}
```
```json
{
    "error": "Το παιχνίδι βρέθηκε, αλλά είναι ήδη γεμάτο ή υπήρξε σφάλμα.",
    "status": "error"
}
```

**Race Condition Prevention:**
```sql
SELECT id, player1_id FROM games 
WHERE status = 'waiting' AND player2_id IS NULL 
LIMIT 1 FOR UPDATE
```

**Database Operations:**
```sql
-- Join existing game
UPDATE games SET status = 'active', player2_id = $my_id
WHERE id = $game_id AND player2_id IS NULL

-- Create new game
INSERT INTO games (player1_id, status, current_player) 
VALUES ($my_id, 'waiting', 1)

-- Add to queue
INSERT INTO matchmaking_queue (user_id) VALUES ($my_id) 
ON DUPLICATE KEY UPDATE joined_at = NOW()
```

---

### Game State Endpoints

---

### 📍 `api/get_board.php` - Game State Retrieval (Polling)

**Purpose:** Returns the current state of the game. Called every 2 seconds by the client.

**HTTP Method:** `GET`

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `game_id` | int |  Yes | ID of the game to query |
| `player_side` | int |  Yes | Which player I am (1 or 2) |

**Process Flow:**
1. Validate game_id exists
2. Fetch game data with player names (JOIN with users table)
3. **Check game status:**

#### Status: 'waiting'
Returns waiting state for matchmaking:
```json
{
    "status": "waiting_for_opponent",
    "game_id": 42,
    "player_side": 1
}
```

#### Status: 'active' (PvP Heartbeat Check)
For PvP games, updates presence tracking:
1. Creates `game_presence` table if not exists
2. Updates my heartbeat timestamp
3. Checks opponent's last heartbeat
4. If opponent inactive >60 seconds → auto-finish game (I win)

#### Status: 'finished'
Returns game over data:
```json
{
    "status": "finished",
    "winner": "me|opponent|draw",
    "final_message": "Νίκησες!",
    "my_score": 18,
    "opp_score": 12,
    "my_cards": 28,
    "opp_cards": 24
}
```

#### Status: 'active' (Normal)
Returns full game state:
```json
{
    "status": "active",
    "table": ["C5", "D10", "H3"],
    "my_hand": [
        {"id": 0, "code": "H7"},
        {"id": 1, "code": "S11"},
        {"id": 2, "code": "C1"}
    ],
    "opponent_cards_count": 4,
    "deck_count": 20,
    "my_score": 10,
    "my_pile_count": 15,
    "opp_score": 0,
    "opp_pile_count": 8,
    "is_my_turn": true,
    "game_mode": "pve",
    "my_name": "Player1",
    "opp_name": "Bot"
}
```

**Response Fields Explained:**
| Field | Description |
|-------|-------------|
| `table` | Array of card codes on the table |
| `my_hand` | Array of objects with id (index) and code |
| `opponent_cards_count` | Number of cards in opponent's hand (hidden) |
| `deck_count` | Cards remaining in draw pile |
| `my_score` | My current score (card points + XERI bonus) |
| `opp_score` | Opponent's current score |
| `my_pile_count` | Cards in my collected pile |
| `opp_pile_count` | Cards in opponent's collected pile |
| `is_my_turn` | Boolean - can I play? |
| `game_mode` | 'pve' or 'pvp' |
| `my_name` | My username |
| `opp_name` | Opponent's username or "Bot" |

**Database Query with JOIN:**
```sql
SELECT g.*, 
       u1.username as p1_name, 
       u2.username as p2_name 
FROM games g
LEFT JOIN users u1 ON g.player1_id = u1.id
LEFT JOIN users u2 ON g.player2_id = u2.id
WHERE g.id = $game_id
```

**Score Calculation (Real-time):**
```php
$my_card_score = calculate_card_score($my_collected);
$my_score = $my_card_score + intval($game['player1_score']); // + XERI bonus
```

---

### 📍 `api/play_card.php` - Player Move Execution

**Purpose:** Executes a player's move (playing a card from hand).

**HTTP Method:** `POST`

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `card_id` | int |  Yes | Index of card in hand (0-5) |
| `player_side` | int |  Yes | Which player (1 or 2) |
| `game_id` | int |  Yes | Current game ID |

**Validation Steps:**
1. Verify it's a POST request
2. Verify all parameters exist
3. Verify game exists in database
4. **Verify it's the player's turn** (critical)
5. Verify card_id exists in player's hand

**Game Logic - Three Cases:**

#### Case 1: Playing a Jack (Rank 11)
```php
if ($played_rank === 11) {
    if (count($table_cards) > 0) {
        $action = 'collect';  // Jack takes all
        if (count($table_cards) === 1 && $last_rank === 11) {
            $is_xeri = true;
            $xeri_points = 20;  // Jack takes single Jack = XERI
        }
    } else {
        $action = 'drop';  // Jack on empty table
    }
}
```

#### Case 2: Matching Rank
```php
elseif ($last_card && $played_rank === $last_rank) {
    $action = 'collect';
    if (count($table_cards) === 1) {
        $is_xeri = true;
        $xeri_points = 10;  // Regular XERI
    }
}
```

#### Case 3: No Match
```php
else {
    $action = 'drop';  // Card stays on table
}
```

**Post-Move Actions:**
1. Remove card from player's hand
2. If collect: add played card + table cards to collected pile
3. If drop: add played card to table
4. Update `last_to_collect` if collection happened
5. Switch turn to other player
6. Update XERI score in database
7. Call `check_and_redeal()` for potential new deal or game end

**Success Response:**
```json
{
    "status": "success",
    "action": "collect",
    "is_xeri": true,
    "message": "🔥 ΞΕΡΗ! 🔥 (+10 πόντοι)"
}
```

```json
{
    "status": "success",
    "action": "drop",
    "is_xeri": false,
    "message": "Το χαρτί έμεινε στο τραπέζι."
}
```

**Error Responses:**
```json
{
    "error": "Only POST requests allowed"
}
```
```json
{
    "error": "Missing required parameters"
}
```
```json
{
    "error": "Game not found"
}
```
```json
{
    "error": "Δεν είναι η σειρά σου!"
}
```
```json
{
    "error": "Το χαρτί δεν βρέθηκε στο χέρι σου!"
}
```

**Database Update Query:**
```sql
UPDATE games SET 
    player1_hand = '[updated hand JSON]',
    player1_collected = '[updated collected JSON]',
    table_cards = '[updated table JSON]',
    current_player = 2,
    last_to_collect = 1,
    player1_score = [new score with XERI bonus]
WHERE id = $game_id
```

---

### 📍 `api/bot_play.php` - AI Move Execution

**Purpose:** Executes the computer player's (bot's) move.

**HTTP Method:** `GET`

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `game_id` | int |  Optional | Specific game ID (finds active game if omitted) |

**Query to Find Bot's Turn:**
```sql
SELECT * FROM games 
WHERE id = $game_id 
  AND status = 'active' 
  AND current_player = 2 
  AND player2_id IS NULL  -- Bot games have NULL player2_id
```

**Bot Strategy (Priority Order):**

#### Strategy A: Capture by Matching
```php
foreach ($bot_hand as $idx => $card) {
    $rank = intval(substr($card, 1));
    if ($last_card && $rank === $last_rank) {
        $card_index = $idx;  // Found matching card!
        break;
    }
}
```

#### Strategy B: Use Jack if Table Has Cards
```php
if ($card_index === null && count($table_cards) > 0) {
    foreach ($bot_hand as $idx => $card) {
        if (intval(substr($card, 1)) === 11) {  // Jack
            $card_index = $idx;
            break;
        }
    }
}
```

#### Strategy C: Random Card (Fallback)
```php
if ($card_index === null) {
    $card_index = array_rand($bot_hand);
}
```

**Process Flow:**
1. Find active game where it's bot's turn
2. Load bot's hand, table, collected cards from JSON
3. Apply strategy to select card
4. Execute same logic as `play_card.php`
5. Switch turn back to player 1
6. Check for redeal or game end

**Success Response:**
```json
{
    "status": "success",
    "action": "collect",
    "is_xeri": false,
    "message": "Bot played",
    "played_card": "H5"
}
```

**Waiting Response:**
```json
{
    "status": "waiting",
    "message": "Δεν είναι η σειρά του Bot"
}
```

**No Cards Response:**
```json
{
    "status": "no_cards"
}
```

---

### Statistics & Leaderboard Endpoints

---

### 📍 `api/get_stats.php` - User Statistics

**Purpose:** Retrieves the logged-in user's game statistics.

**HTTP Method:** `GET`

**Required Session:** User must be logged in

**Request Parameters:** None (uses session)

**Database Query:**
```sql
SELECT games_played, games_won, games_lost 
FROM users 
WHERE id = ?
```

**Success Response:**
```json
{
    "status": "success",
    "stats": {
        "wins": 15,
        "losses": 8,
        "draws": 0,
        "total": 23
    }
}
```

**Error Responses:**
```json
{
    "status": "error",
    "error": "Not logged in"
}
```
```json
{
    "status": "error",
    "error": "User not found"
}
```

**Note:** `draws` is always 0 as the game doesn't currently track draws.

---

### 📍 `api/get_leaderboard.php` - Top Players

**Purpose:** Retrieves the top 5 players ranked by wins.

**HTTP Method:** `GET`

**Request Parameters:** None

**Database Query:**
```sql
SELECT username, games_won, games_lost, games_played 
FROM users 
WHERE username != 'bot'
ORDER BY games_won DESC, games_played DESC 
LIMIT 5
```

**Success Response:**
```json
{
    "status": "success",
    "leaderboard": [
        {
            "rank": 1,
            "username": "champion",
            "wins": 50,
            "losses": 10,
            "total": 60
        },
        {
            "rank": 2,
            "username": "pro_player",
            "wins": 35,
            "losses": 15,
            "total": 50
        }
    ]
}
```

**Error Response:**
```json
{
    "error": "Database error: [MySQL error]"
}
```

---

### Game Exit & Disconnect Endpoints

---

### 📍 `api/quit_game.php` - Voluntary Game Exit

**Purpose:** Allows a player to voluntarily leave a game (counts as loss in PvP).

**HTTP Method:** `POST`

**Required Session:** User must be logged in

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `game_id` | int |  Yes | Game to quit |
| `player_side` | int |  Yes | Which player (1 or 2) |

**Process Flow:**
1. Fetch game information
2. Check if game already finished (prevent double counting)
3. Determine game type (PvE or PvP)
4. **For PvP only:**
   - Update quitter's stats: `games_lost + 1`
   - Update opponent's stats: `games_won + 1`
5. Mark game as finished
6. Set `last_to_collect` to winner (opponent) for proper display
7. Clear session game data

**Response for PvE:**
```json
{
    "status": "success",
    "message": "Έξοδος από την προπόνηση. Το παιχνίδι δεν μετράει στα στατιστικά σου."
}
```

**Response for PvP:**
```json
{
    "status": "success",
    "message": "Το παιχνίδι τερματίστηκε. Μετρήθηκε ήττα."
}
```

**Error Response:**
```json
{
    "status": "error",
    "message": "Το παιχνίδι έχει ήδη τελειώσει."
}
```

**Statistics Update:**
```sql
-- Quitter (loser)
UPDATE users SET 
    games_lost = games_lost + 1, 
    games_played = games_played + 1 
WHERE id = $loser_user_id

-- Opponent (winner)
UPDATE users SET 
    games_won = games_won + 1, 
    games_played = games_played + 1 
WHERE id = $winner_user_id
```

---

### 📍 `api/cancel_match.php` - Cancel Matchmaking

**Purpose:** Cancels a PvP search before an opponent joins.

**HTTP Method:** `POST`

**Required Session:** User must be logged in

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `game_id` | int |  Yes | Game to cancel |

**Validation:**
- Game must be in 'waiting' status
- User must be the game creator (Player 1)

**Process Flow:**
1. Fetch game info
2. Verify status is 'waiting'
3. Verify user is the creator
4. Delete the game record
5. Remove user from matchmaking_queue
6. Clear session game data

**Success Response:**
```json
{
    "status": "success",
    "message": "Η αναζήτηση αντιπάλου ακυρώθηκε."
}
```

**Error Responses:**
```json
{
    "error": "Not logged in."
}
```
```json
{
    "error": "Missing game ID."
}
```
```json
{
    "error": "Game not found."
}
```
```json
{
    "error": "Cannot cancel. Game is already active or you are not the creator."
}
```

---

### 📍 `api/player_disconnect.php` - Browser Close Handler

**Purpose:** Handles when a player closes their browser/tab during a PvP game.

**HTTP Method:** `POST`

**Trigger:** Called via `navigator.sendBeacon()` on `beforeunload` event

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `game_id` | int |  Optional | Uses session if not provided |

**Frontend Trigger:**
```javascript
window.addEventListener('beforeunload', function(e) {
    if (currentGameId && isInPvPGame) {
        navigator.sendBeacon('api/player_disconnect.php', '');
    }
});
```

**Process Flow:**
1. Get game_id from POST or session
2. Verify user is logged in
3. Fetch active game
4. Verify it's a PvP game (player2_id is not NULL)
5. Verify user is a player in this game
6. Determine winner (the opponent who didn't disconnect)
7. **Atomic update:** Update game to finished only if still active
8. Update statistics for both players
9. Clean up matchmaking queue and session

**Success Response:**
```json
{
    "status": "success",
    "message": "Disconnect registered"
}
```

**Error Responses:**
```json
{
    "error": "Not in a game"
}
```
```json
{
    "error": "Game not found or not active"
}
```
```json
{
    "error": "Not a PvP game"
}
```
```json
{
    "error": "Not a player in this game"
}
```

**Atomic Update (prevents double processing):**
```sql
UPDATE games 
SET status = 'finished', last_to_collect = $winner_side 
WHERE id = $game_id AND status = 'active'
-- Only proceeds if affected_rows === 1
```

---

## 🖥 Frontend Components

### Main Entry Point: `index.php`

**Sections:**

1. **Auth Screen** (shown when not logged in)
   - Login form with username/password
   - Signup form with password confirmation
   - Toggle password visibility (eye icon)

2. **Main Menu** (shown when logged in)
   - Welcome message with username
   - "ΠΑΙΞΕ" button → reveals mode selector
   - Mode buttons: "VS COMPUTER" / "VS PLAYER 2"
   - Statistics box (wins/losses/total)
   - Leaderboard (top 5 players)
   - Logout button

3. **Waiting Screen** (PvP matchmaking)
   - Loading spinner
   - Cancel button (for game creator only)

4. **Game Over Screen**
   - Result title (Win/Lose/Draw)
   - Final scores
   - "Play Again" button

5. **Game Board**
   - UI Layer (names & scores)
   - Opponent zone (back of cards)
   - Middle zone (deck + table)
   - Player zone (hand + collected pile)

---

### JavaScript: `game.js`

**Global Variables:**
```javascript
var botThinking = false;      // Prevent double bot moves
var pollingInterval = null;   // Polling timer reference
var myPlayerSide = 1;         // Am I player 1 or 2?
var currentGameId = null;     // Active game ID
var isInPvPGame = false;      // Enable disconnect detection?
```

**Key Functions:**

| Function | Purpose |
|----------|---------|
| `doLogin()` | AJAX POST to login.php |
| `doSignup()` | AJAX POST to signup.php |
| `doLogout()` | AJAX POST to logout.php |
| `fetchUserStats()` | Load user statistics |
| `fetchLeaderboard()` | Load top 5 players |
| `initGame(mode)` | Start PvE or PvP game |
| `startPolling()` | Begin 2-second refresh cycle |
| `fetchBoardData()` | GET game state from server |
| `renderTable(cards)` | Draw table cards |
| `renderMyHand(cards)` | Draw my hand with click handlers |
| `renderOpponent(count)` | Draw opponent's card backs |
| `renderPiles()` | Draw collected card piles |
| `renderDeck(count)` | Draw deck with count |
| `checkTurn()` | Enable/disable hand based on turn |
| `triggerBotPlay()` | Request bot move (with 1.5s delay) |
| `playCard(cardId)` | POST move to server |
| `quitGame()` | Exit game with confirmation |
| `cancelMatchmaking()` | Cancel PvP search |

---

## 📊 Game Flow Diagrams

### PvE Game Flow
```
┌─────────────┐     ┌──────────────┐     ┌───────────────┐
│  Main Menu  │────▶│ init_game.php│────▶│ Game Created  │
│   (PLAY)    │     │   (POST)     │     │ status=active │
└─────────────┘     └──────────────┘     └───────┬───────┘
                                                  │
       ┌──────────────────────────────────────────┘
       ▼
┌──────────────┐     ┌───────────────┐     ┌──────────────┐
│ get_board.php│◀───▶│  Render Game  │◀───▶│ play_card.php│
│   (Polling)  │     │     State     │     │  (My Move)   │
└──────────────┘     └───────────────┘     └──────────────┘
       │                                          │
       │              ┌───────────────┐           │
       └─────────────▶│ bot_play.php  │◀──────────┘
                      │  (Bot Move)   │
                      └───────────────┘
                              │
                              ▼
                      ┌───────────────┐
                      │  check_and_   │
                      │   redeal()    │
                      └───────┬───────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
       ┌──────────────┐                ┌──────────────┐
       │ Deal New 6+6 │                │  Game Over   │
       │   Continue   │                │status=finished│
       └──────────────┘                └──────────────┘
```

### PvP Game Flow
```
┌─────────────┐     ┌───────────────┐     ┌─────────────────┐
│  Main Menu  │────▶│find_match.php │────▶│ Game Created    │
│   (PvP)     │     │   (POST)      │     │ status=waiting  │
└─────────────┘     └───────────────┘     └────────┬────────┘
                                                   │
       ┌───────────────────────────────────────────┘
       ▼
┌────────────────┐     ┌───────────────────┐
│ Waiting Screen │────▶│  Opponent Joins   │
│   (Polling)    │     │ find_match.php(P2)│
└────────────────┘     └─────────┬─────────┘
                                 │
                                 ▼
                      ┌───────────────────┐
                      │   status=active   │
                      │  Game Board Shown │
                      └─────────┬─────────┘
                                │
         ┌──────────────────────┴──────────────────────┐
         ▼                                             ▼
┌─────────────────┐                         ┌─────────────────┐
│ get_board.php   │◀───────────────────────▶│ get_board.php   │
│ (Player 1 Poll) │                         │ (Player 2 Poll) │
└────────┬────────┘                         └────────┬────────┘
         │                                           │
         ▼                                           ▼
┌─────────────────┐                         ┌─────────────────┐
│ play_card.php   │────────────────────────▶│   Turn Switch   │
│ (P1 Move)       │                         │                 │
└─────────────────┘                         └─────────────────┘

         │                                           │
         │     ┌─────────────────────────────┐       │
         └────▶│ Disconnect/Quit Detection   │◀──────┘
               │ player_disconnect.php       │
               │ quit_game.php               │
               └──────────────┬──────────────┘
                              │
                              ▼
                     ┌─────────────────┐
                     │   Game Over     │
                     │ status=finished │
                     └─────────────────┘
```

---

## 🚀 Installation Guide

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Web browser with JavaScript enabled

### Step 1: Upload Files
```bash
# Using SCP
scp -r xeri_game/* user@server:~/public_html/

# Or using FileZilla to ~/public_html/
```

### Step 2: Set Permissions
```bash
chmod 755 ~/public_html
chmod 644 ~/public_html/*.php
chmod 755 ~/public_html/api
chmod 644 ~/public_html/api/*.php
chmod 755 ~/public_html/css
chmod 755 ~/public_html/js
chmod 755 ~/public_html/img

# Create and secure sessions folder
mkdir -p ~/public_html/sessions
chmod 700 ~/public_html/sessions
```

### Step 3: Configure Database
Edit `db.php` with your credentials:
```php
$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "your_database";
$socket = "/path/to/mysql.sock";  // If needed
```

### Step 4: Create Tables
```bash
mysql -u your_username -p your_database < setup_database.sql
```

Or via phpMyAdmin:
1. Open phpMyAdmin
2. Select your database
3. Go to "Import" tab
4. Upload `setup_database.sql`

### Step 5: Verify Installation
1. Open `test_connection.php` in browser
2. Verify "Connection successful" message
3. Verify all tables are listed

### Step 6: Access Game
Navigate to your installation URL:
```
https://your-server.com/xeri_game/
```

---

## 🐛 Known Issues & Troubleshooting

### HTTP 500 Error
**Causes:**
- Wrong database credentials in `db.php`
- Missing session folder or wrong permissions
- PHP syntax errors

**Solution:**
1. Check PHP error logs
2. Verify `db.php` credentials
3. Ensure `sessions/` folder exists with 700 permissions

### 403 Forbidden
**Causes:**
- Wrong folder/file permissions
- .htaccess restrictions

**Solution:**
```bash
chmod 755 ~/public_html
chmod 644 ~/public_html/*.php
```

### Cards Not Displaying
**Causes:**
- Missing card images in `img/cards/`
- Wrong image naming (should be C1.png, D10.png, etc.)

**Solution:**
- Verify all 52 card images exist
- Check image naming matches code format

### Bot Not Playing
**Causes:**
- Wrong `game_mode` detection
- Bot query not finding active games

**Solution:**
1. Check browser console for errors
2. Verify `player2_id IS NULL` condition
3. Check `current_player = 2` in database

### PvP Disconnect Not Detected
**Causes:**
- `sendBeacon` not supported (old browsers)
- `isInPvPGame` flag not set correctly

**Solution:**
1. Use modern browser
2. Check `game_presence` table is created
3. Verify 60-second timeout in `get_board.php`

---

## 📝 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | Dec 2025 | Initial release with PvE and PvP support |

---

## 📄 License

This project is created for educational purposes.

---

**Created by:** Pantelis Politis  
**Institution:** IHU (International Hellenic University)  
**Course:** Development of web systems and applications
