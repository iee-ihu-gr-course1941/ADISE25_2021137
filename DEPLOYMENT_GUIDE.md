# Οδηγίες Εγκατάστασης Xeri Game στον Server

## Βήματα που έχεις κάνει ήδη ✓
1. ✓ Ανέβασες τα αρχεία μέσω FileZilla
2. ✓ Δημιούργησες το db_connect.php με τα στοιχεία του server

## Βήματα που πρέπει να κάνεις τώρα:

### 1. Δημιουργία Βάσης Δεδομένων

Συνδέσου στον MySQL server του πανεπιστημίου με ένα από τους παρακάτω τρόπους:

**Α. Μέσω SSH και MySQL CLI:**
```bash
ssh iee2021137@users.iee.ihu.gr
mysql -u iee2021137 -p -S /home/student/iee/2021/iee2021137/mysql/run/mysql.sock
# Κωδικός: pantelis2003
```

**Β. Μέσω phpMyAdmin (αν υπάρχει):**
Πήγαινε στο: `http://users.iee.ihu.gr/~iee2021137/phpmyadmin/`

### 2. Εκτέλεση του SQL Script

Αφού συνδεθείς στη MySQL:
```sql
USE iee2021137;
SOURCE setup_database.sql;
```

Ή αντίγραψε το περιεχόμενο του `setup_database.sql` και εκτέλεσέ το.

### 3. Έλεγχος Δικαιωμάτων Αρχείων

Βεβαιώσου ότι τα αρχεία έχουν τα σωστά δικαιώματα:
```bash
chmod 755 ~/public_html
chmod 644 ~/public_html/*.php ~/public_html/*.html
chmod 755 ~/public_html/api
chmod 644 ~/public_html/api/*.php
```

### 4. Δοκιμή της Σύνδεσης

Δημιούργησε ένα αρχείο `test_connection.php`:
```php
<?php
require_once 'db.php';
echo "Σύνδεση επιτυχής!<br>";
echo "MySQL version: " . $mysqli->server_info;
?>
```

Πήγαινε στο: `http://users.iee.ihu.gr/~iee2021137/test_connection.php`

### 5. Πρόσβαση στο Παιχνίδι

Το παιχνίδι θα είναι διαθέσιμο στο:
```
http://users.iee.ihu.gr/~iee2021137/
```

## Αρχεία που Ενημερώθηκαν:

### db.php
Ενημερώθηκε με τις ρυθμίσεις του server:
- Host: localhost
- Username: iee2021137
- Password: pantelis2003
- Database: iee2021137
- Socket: /home/student/iee/2021/iee2021137/mysql/run/mysql.sock

## Troubleshooting

### Αν δεις σφάλμα σύνδεσης:
1. Βεβαιώσου ότι η MySQL service τρέχει στον account σου
2. Έλεγξε ότι το socket path είναι σωστό
3. Δοκίμασε να συνδεθείς από terminal για να επιβεβαιώσεις τα credentials

### Αν δεις σφάλματα με sessions:
```bash
mkdir ~/public_html/sessions
chmod 700 ~/public_html/sessions
```

Και πρόσθεσε στο `db.php` πριν το `session_start()`:
```php
ini_set('session.save_path', realpath(__DIR__ . '/sessions'));
```

### Αν τα ελληνικά δεν εμφανίζονται σωστά:
Βεβαιώσου ότι όλα τα αρχεία έχουν encoding UTF-8.

## Επόμενα Βήματα

1. Δοκίμασε το registration
2. Δοκίμασε το login
3. Ξεκίνησε ένα παιχνίδι με bot
4. Δοκίμασε matchmaking με δεύτερο browser/tab

## Σημειώσεις Ασφαλείας

⚠️ **ΠΡΟΣΟΧΗ:** Μην κοινοποιήσεις δημόσια το αρχείο `db.php` με τους κωδικούς!

Για production, σκέψου:
- Να αλλάξεις τον κωδικό της MySQL
- Να προσθέσεις `.htaccess` για προστασία
- Να κάνεις backup τη βάση τακτικά
