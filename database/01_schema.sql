-- =====================================================================
-- Avalon CTF - 01_schema.sql  (table creation only, MySQL 8.x)
-- Runs automatically on first start of the db container
-- (files in /docker-entrypoint-initdb.d run in alphabetical order).
-- Manual use:  mysql -u USER -p DATABASE < 01_schema.sql
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS challenges (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenge_number TINYINT UNSIGNED NOT NULL UNIQUE, -- 1..7, sequential unlock order
    title            VARCHAR(150) NOT NULL,
    difficulty       ENUM('easy','medium','hard') NOT NULL,
    description      TEXT NOT NULL,     -- scenario text; supports [[label|https://url]] links
    player_task      TEXT NOT NULL,     -- task text; supports [[label|https://url]] links
    flag_hash        VARCHAR(255) NOT NULL, -- password_hash() of the exact flag string, never plaintext
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS challenge_attachments (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenge_id     INT UNSIGNED NOT NULL,
    display_name     VARCHAR(150) NOT NULL, -- name shown to the player, e.g. "capture.pcap"
    stored_filename  VARCHAR(150) NOT NULL, -- actual filename inside attachments/challengeN/
    sort_order       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hints: any number (0, 1, 2, ...) per challenge. hint_order decides the
-- sequence they must be revealed in (1 first, then 2, ...). Each hint
-- taken costs HINT_PENALTY points (5, set in includes/functions.php).
CREATE TABLE IF NOT EXISTS challenge_hints (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenge_id  INT UNSIGNED NOT NULL,
    hint_order    SMALLINT UNSIGNED NOT NULL, -- 1 = first hint, 2 = second, ...
    hint_text     TEXT NOT NULL,              -- supports [[label|https://url]] links
    UNIQUE KEY uniq_challenge_hint_order (challenge_id, hint_order),
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Which hints each user has revealed (one row per user+hint).
CREATE TABLE IF NOT EXISTS user_hints (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    hint_id      INT UNSIGNED NOT NULL,
    revealed_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_hint (user_id, hint_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hint_id) REFERENCES challenge_hints(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- points_awarded is frozen at the moment a challenge is solved
-- (base points for the difficulty minus 5 per hint taken, never below 0).
CREATE TABLE IF NOT EXISTS user_progress (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NOT NULL,
    challenge_id   INT UNSIGNED NOT NULL,
    points_awarded SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    completed_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_challenge (user_id, challenge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
