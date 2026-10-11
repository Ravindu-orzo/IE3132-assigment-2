-- =====================================================================
-- Avalon CTF - 02_data.sql  (population / seed data only, MySQL 8.x)
-- Built from the latest database dump (dump-avalon_db-202610081046).
-- Must run AFTER 01_schema.sql. Runs automatically on the FIRST start of
-- the db container (empty data volume), so after
--   docker compose down -v && docker compose up -d --build
-- all of the data below is loaded again.
-- Manual use:  mysql -u USER -p DATABASE < 02_data.sql
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Users (password_hash = password_hash() of the password).
-- admin = demo account (password: admin123) - CHANGE IT before real use.
-- ---------------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `password_hash`) VALUES
(1, 'admin', '$2b$10$PT0QUFoQ2bZr6ova8UErQOzIwyULC.9oZt6TASVAC3Xd9ao3bGg0y'),
(2, 'Ravindu', '$2y$10$h/8gTB01Rkp0ljw5azxF9O9S5MrGi4QqL4IDs0ZlmGaNjs3XYB4IC');

-- ---------------------------------------------------------------------
-- Challenges (flag_hash = password_hash() of the real flag)
-- ---------------------------------------------------------------------
INSERT INTO `challenges` (`id`, `challenge_number`, `title`, `difficulty`, `description`, `player_task`, `flag_hash`) VALUES
(1, 1, 'Hidden in the plainsight', 'easy', 'After Avalon disappears, investigators discover an old image showing a mysterious magical door believed to have been one of the entrances to the city. The image appears normal, but the investigators suspect that whoever left it behind concealed a message inside the image file itself.', 'Inspect the image and find the flag', '$2y$10$So8DDNG3QUyYp/35qQHeF.Jmff/z5ZFJNUM68J55u9GkqYuAt/fp.'),
(2, 2, 'Can\'t fake permissions', 'easy', 'While searching for anyone who might have known about Avalon before it vanished, investigators identify the Custodian Order a secretive group long rumored to guard the city\'s location. A forensic image of one of their servers shows malware planted thirty decoy binaries in the home directory, almost certainly by an insider trying to bury the truth. Twenty-nine exist only to waste an investigator\'s time; only one actually runs, and it\'s the one worth reading.', 'Log into the remote CTF box and find the flag\r\n\r\nbox IP: 20.205.124.213\r\nusername: ctf-player\r\npassword: 69a3$x9Y2*@4P99#CXEVKtVJ', '$2y$10$3iWWpckiR2zoZgKtjuEA4eNz14X0v1mRhy9cfrpxE2n0WG/OONNsG'),
(3, 3, 'The Archivist\'s Secret ', 'easy', 'Beyond the magical door, investigators recover a damaged document believed to have belonged to Avalon\'s royal archivist. Several lines appear to have been permanently blacked out before the city\'s disappearance. However, the redaction was performed incorrectly: the original text remains underneath the black rectangles and can still be copied from the PDF. A strange poem beside the redacted section hints at how the recovered message should be decoded. ', 'Inspect the pdf and find the flag!', '$2y$10$O1w/rNVcz4ES4KvuF7dXKOfDEmZdc21xuQOexfPCd10wXqn6yw5qG'),
(4, 4, 'Crack in the armor', 'medium', 'Digging into the Custodian Order\'s internal communications, the response team learns one employee is suspected of leaking information about Avalon to an outsider. A password-protected archive recovered from her workstation is believed to hold what she sent. Chat logs show she compressed it using some unknown person’s surname as the password. The same carelessness, investigators note, that may have let something slip out of Avalon\'s care in the first place.', 'Extract the zip to obtain the flag!', '$2y$10$K0jE8Kh9gV3zxnObyYMbCOxngLX8j0XiEV93LjxzN0.tCZjtpBNLe'),
(5, 5, 'Captured login', 'medium', 'Someone impersonating a Custodian Order member logged into the Order\'s internal site and altered records related to the Avalon investigation before anyone noticed. A laptop seized from the suspected intruder holds a packet capture of the very session used to steal those credentials. Recovering the login from the capture and using it to access the site may reveal what was changed and what the intruder was trying to hide.', 'Analyse their pcapng file and identify which account was compromised. Log in to the website using those account credentials and see what changes the threat actor has made.', '$2y$10$tMLTP3M/GTm2mqFDrtnW1ejPkj3vNOSaGPbgINYfLGGNqou.BLZhW'),
(6, 6, 'Encrypted Secrets', 'hard', 'A program named Encrypter is found on the same compromised Custodian Order system, apparently used to protect the Order\'s most sensitive messages about Avalon before they were shared. One such message that is believed to describe what really happened to the city was seized. However it was already encrypted. The key was never written down in the open, but the program that encrypted it may still be carrying the secret inside itself.', 'Encrypted text : 1265223E2A3A241C070D3C3B2B10435D06385A0D153A166B0F6C482B202B15015E032E', '$2y$10$EnqHp7ILMq/rfATv7yuOceRR6BUwbDprW.7bQnrVuTvoCKy4QJXUS'),
(7, 7, 'The Unwritten Trail ', 'hard', 'The record contains a name.\nThe name contains nothing.\nWhat remains does not explain itself.\n\neveyne.archive@gmail.com', 'Find the flag', '$2y$10$XHi7fQObEvyyXm1gnoNSZ.Kgu6mLKNAtIR/VJjWHtOkSsdzAxdo52');

-- ---------------------------------------------------------------------
-- Attachments (stored_filename must match the file in
-- attachments/challengeN/, where N = challenge number)
-- ---------------------------------------------------------------------
INSERT INTO `challenge_attachments` (`id`, `challenge_id`, `display_name`, `stored_filename`, `sort_order`) VALUES
(1, 1, 'avalon.png', 'avalon.png', 0),
(2, 5, 'login-captured.pcapng', 'login-captured.pcapng', 0),
(3, 3, 'THE LAST DECREE OF AVALON.pdf', 'THE LAST DECREE OF AVALON.pdf', 0),
(4, 4, 'protected.zip', 'protected.zip', 0),
(5, 4, 'wordlist-surnames-SL.txt', 'wordlist-surnames-SL.txt', 1),
(6, 6, 'encrypter binary', 'encrypter', 0),
(7, 3, 'poem.txt', 'poem.txt', 1);

-- ---------------------------------------------------------------------
-- Hints. hint_order = reveal sequence; each hint a player takes costs
-- them 5 points on that challenge only. Add/remove rows freely: the
-- page shows exactly as many hint lines as exist per challenge.
-- ---------------------------------------------------------------------
INSERT INTO `challenge_hints` (`id`, `challenge_id`, `hint_order`, `hint_text`) VALUES
(1, 1, 1, 'Images can carry information in their metadata. Try inspecting the file rather than only viewing it.'),
(2, 2, 1, 'The first group of rwx represents permissions for the owner.'),
(3, 3, 1, 'The poem keeps returning to the same number. Could that number tell you how far the letters have been shifted?'),
(4, 4, 1, 'Protected zip files store the password as a hash inside it.'),
(5, 4, 2, 'John the ripper can be used to compare a wordlist against a password hash'),
(6, 5, 1, 'The key here is filtering for http traffic'),
(7, 5, 2, 'Login attempts are done through http POST requests'),
(8, 6, 1, 'The strings utility can reveal useful information embedded in binaries.'),
(9, 6, 2, 'If you need more context, use Ghidra to identify where the interesting string is referenced.'),
(10, 6, 3, 'The encryption operation is reversible when the same XOR key is known'),
(11, 7, 1, 'The trail may be partly hidden. Pay attention to fragments of the email address and other identifying details.');
