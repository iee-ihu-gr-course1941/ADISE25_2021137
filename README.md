# Xeri Game — README

**Σκοπός**: Αυτό το repository περιέχει το διαδικτυακό παιχνίδι "ΞΕΡΗ" (Xeri). Το README αυτό περιγράφει πώς να εγκαταστήσεις, να λειτουργήσεις, να δοκιμάσεις και να αναπτύξεις το παιχνίδι σε server (π.χ. users.iee.ihu.gr).

**Περιεχόμενο Αποθετηρίου**
- `index.html`, `index.php` — frontend σελίδα/εισαγωγή
- `css/` — stylesheet
- `js/game.js` — client-side JavaScript (polling, UI, κλήσεις API)
- `api/` — server-side endpoints (PHP) που επιστρέφουν JSON
- `img/cards/` — εικόνες καρτών
- `db.php`, `db_connect.php` — ρυθμίσεις σύνδεσης με MySQL
- `setup_database.sql` — script για δημιουργία πινάκων
- `test_connection.php` — test σύνδεσης DB

**Σημαντικές Σημειώσεις για Δομή Δεδομένων**
- Τα περισσότερα δεδομένα παιχνιδιού (τράπουλα, χέρια, τραπέζι, μαζεμένες κάρτες) αποθηκεύονται ως JSON μέσα στις στήλες του πίνακα `games` (π.χ. `deck`, `player1_hand`, `table_cards`).
- Τα API endpoints επικοινωνούν με το frontend μέσω JSON responses. Τα POST/GET requests από το frontend χρησιμοποιούν form-encoded params (όχι raw JSON body).

**Γρήγορη Εγκατάσταση (server / hosting)**
1. Ανέβασε όλα τα αρχεία στο `~/public_html/` (ή στο document root του host) με FileZilla / scp.
2. Έλεγξε τα δικαιώματα φακέλων/αρχείων (παράδειγμα):
	- `chmod 755 ~/public_html`
	- `chmod 644 ~/public_html/*.php` και `chmod 755` για φακέλους `css/`, `js/`, `img/`.
3. Δημιούργησε τη βάση και τους πίνακες εκτελώντας το `setup_database.sql` (ή με phpMyAdmin):
```
ssh <user>@<host>
mysql -u <db_user> -p -S /path/to/mysql.sock
USE <db_name>;
SOURCE setup_database.sql;
```
4. Ενημέρωσε το `db.php` με τα σωστά credentials / socket path.
5. Δημιούργησε φάκελο sessions και όρισε σωστά δικαιώματα αν χρειάζεται:
```
mkdir -p ~/public_html/sessions
chmod 700 ~/public_html/sessions
```
και στο `db.php` έχει προστεθεί `ini_set('session.save_path', realpath(__DIR__ . '/sessions'));`.

**Εκτέλεση / Έλεγχος**
- Άνοιξε το: `https://<host>/<user>/` (π.χ. `https://users.iee.ihu.gr/~iee2021137/`)
- Έλεγξε `https://.../test_connection.php` για σύνδεση DB και ονόματα πινάκων.
- Χρησιμοποίησε το DevTools (Console + Network) για να δεις αιτήματα προς `api/*.php`.

**Σημαντικά API Endpoints (συνοπτικά)**
- `api/find_match.php` (POST): Ψάχνει/δημιουργεί παιχνίδι PvP. Επιστρέφει JSON: `{ status: 'waiting'|'joined'|'error', game_id, player_side }`.
- `api/init_game.php` (POST): Δημιουργεί παιχνίδι PvE (bot). Επιστρέφει `{ status:'success', game_id, player_side:1 }`.
- `api/get_board.php` (GET): Φέρνει την τρέχουσα κατάσταση του παιχνιδιού. Παράμετροι: `game_id`, `player_side`. Επιστρέφει JSON με fields: `status`, `table`, `my_hand`, `opponent_cards_count`, `deck_count`, `my_score`, `opp_score`, `my_pile_count`, `opp_pile_count`, `is_my_turn`, `game_mode`, `my_name`, `opp_name`.
- `api/play_card.php` (POST): Παίζει κάρτα. Παράμετροι: `card_id` (index στο χέρι), `player_side`, `game_id`. Επιστρέφει `{ status:'success'|'error', action:'drop'|'collect', is_xeri:true|false, message }`.
- `api/bot_play.php` (GET): Καλείται για κίνηση bot. Δέχεται `game_id` (GET) ή βρίσκει ενεργό παιχνίδι. Επιστρέφει `{status:'success'|'waiting'}`.
- `api/get_stats.php` (GET): Επιστρέφει στατιστικά παίκτη (wins/losses/total) ως JSON.

Για πλήρεις λεπτομέρειες του κάθε API δες τα αρχεία στο `api/`.

**Πλαίσια Δεδομένων (DB columns σημαντικά)**
- `users`: `id, username, password, games_played, games_won, games_lost, total_score, created_at`
- `games`: `id, player1_id, player2_id, current_player, status, player1_score, player2_score, deck (JSON), player1_hand (JSON), player2_hand (JSON), table_cards (JSON), player1_collected (JSON), player2_collected (JSON), last_to_collect, created_at`

Σημείωση: Τα ονόματα στηλών έχουν αλλάξει κατά την ανάπτυξη — αν δεις errors αναζήτησε `player_1_id`/`game_status` παλαιά ονόματα και αντικατάστησέ τα με `player1_id`/`status`.

**Συχνά Προβλήματα & Troubleshooting**
- HTTP 500: Δες τα PHP error logs ή άνοιξε `test_connection.php` και έλεγξε κλήσεις στο browser Network. Συνηθισμένα σφάλματα: λάθος DB credentials, απουσία `session.save_path`, λάθος ονόματα στηλών.
- Forbidden (403): Έλεγξε δικαιώματα φακέλων/αρχείων και `.htaccess` αν υπάρχει. Φάκελοι πρέπει να είναι `755`, αρχεία `644`.
- Τα deck/χέρια δεν εμφανίζονται: Βεβαιώσου ότι οι λειτουργίες `generate_shuffled_deck()`, `save_deck_to_db()`, `deal_initial_cards()` εκτελούνται και αποθηκεύουν JSON στη στήλη `deck` / `player1_hand` κλπ.
- Bot δεν παίζει: Έλεγξε ότι `game_mode` είναι `pve` (το `get_board.php` επιστρέφει `game_mode`), ότι `triggerBotPlay()` στέλνει `game_id` και ότι `bot_play.php` δέχεται σωστά `game_id`.

**Ασφάλεια / Production Tips**
- Μην αφήνεις τους DB credentials μέσα σε δημόσιο repo.
- Αλλάζεις τα passwords, απενεργοποίησε debug, χρήση HTTPS.
- Περιορισμός access στο `db.php` (π.χ. με `.htaccess`) αν χρειάζεται.

**Αν έρθεις εδώ για πρώτη φορά — τα βήματα που συνιστώ**
1. Βεβαιώσου ότι έχεις ανεβάσει όλα τα αρχεία στον server και ότι `db.php` έχει τα σωστά credentials.
2. Τρέξε `setup_database.sql` για να δημιουργήσεις τους πίνακες.
3. Άνοιξε `test_connection.php` για να επιβεβαιώσεις σύνδεση DB και πίνακες.
4. Δοκίμασε `index.html` / `index.php` και άνοιξε DevTools -> Network για να παρακολουθήσεις τα JSON responses από `api/`.

---

Αν θέλεις, μπορώ να:
- Παραθέσω λεπτομερή περιγραφή κάθε API με παραδείγματα request/response JSON.
- Προσθέσω οδηγίες για unit tests ή μικρό script που επαληθεύει τα endpoints (curl examples).

Πες μου τι προτιμάς ως επόμενο βήμα — θέλεις να προσθέσω αναλυτικά παραδείγματα API; 
