-- Προσθήκη πεδίων για την τελευταία κάρτα που έπαιξε κάθε παίκτης
ALTER TABLE games 
ADD COLUMN player1_last_played VARCHAR(10) DEFAULT NULL AFTER player1_collected,
ADD COLUMN player2_last_played VARCHAR(10) DEFAULT NULL AFTER player2_collected;
