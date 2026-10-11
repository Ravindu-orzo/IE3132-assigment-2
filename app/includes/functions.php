<?php
/**
 * Shared helper functions used across the CTF platform.
 * All DB access here uses prepared statements only.
 */

const TOTAL_CHALLENGES = 7;

/** Points lost on a challenge for EACH hint the player reveals on it. */
const HINT_PENALTY = 5;

/** Every flag on the platform must look like AVALON{...}. Update this in one place if the format ever changes again. */
const FLAG_FORMAT_REGEX = '/^AVALON\{.+\}$/';

/**
 * Username rule: 3-20 characters, letters/numbers/underscore only.
 * Used by both signup (to accept/reject) and login (informational only).
 */
function isValidUsername(string $username): bool
{
    return (bool)preg_match('/^[A-Za-z0-9_]{3,20}$/', $username);
}

/**
 * Password rule: at least 8 characters, at least one letter and one number.
 * Deliberately not overly strict - this is a student CTF, not a bank.
 */
function isValidPassword(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Za-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

/** Quick format check before we even touch the database/hash comparison. */
function isValidFlagFormat(string $flag): bool
{
    return (bool)preg_match(FLAG_FORMAT_REGEX, $flag);
}

/** Points are always derived from difficulty - never stored/trusted from anywhere else. */
function pointsForDifficulty(string $difficulty): int
{
    switch (strtolower($difficulty)) {
        case 'easy':   return 25;
        case 'medium': return 50;
        case 'hard':   return 75;
        default:       return 0;
    }
}

/** Points still available on a challenge after $hintsTaken hints (never below 0). */
function pointsAfterHints(string $difficulty, int $hintsTaken): int
{
    return max(0, pointsForDifficulty($difficulty) - HINT_PENALTY * max(0, $hintsTaken));
}

function difficultyClass(string $difficulty): string
{
    switch (strtolower($difficulty)) {
        case 'easy':   return 'diff-easy';
        case 'medium': return 'diff-medium';
        case 'hard':   return 'diff-hard';
        default:       return '';
    }
}

/**
 * MODULAR LINK RENDERER
 * ---------------------
 * Lets challenge text embed clickable links safely using the syntax:
 *   [[link label|https://example.com]]
 *
 * The whole string is HTML-escaped FIRST, then only well-formed http(s)
 * links matching the pattern are turned into <a> tags. Anything that
 * isn't a valid http/https URL is rendered as plain (safe) text instead
 * of a link, so this can never be used to inject javascript: URLs or
 * markup. Reuse this for description, player_task, or any future field.
 */
function renderTextWithLinks(string $text): string
{
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $escaped = nl2br($escaped, false);

    return preg_replace_callback('/\[\[(.*?)\|(.*?)\]\]/s', function ($m) {
        $label = trim($m[1]);
        $url   = trim($m[2]);

        if ($label === '' || !preg_match('~^https?://[^\s"<>]+$~i', $url)) {
            // Not a safe/valid link -> fall back to plain label text only.
            return $label;
        }

        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
    }, $escaped);
}

/** All challenges, in order, for building the sidebar. */
function getAllChallenges(PDO $pdo): array
{
    return $pdo->query(
        'SELECT id, challenge_number, title, difficulty FROM challenges ORDER BY challenge_number ASC'
    )->fetchAll();
}

function getChallengeByNumber(PDO $pdo, int $number): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM challenges WHERE challenge_number = ? LIMIT 1');
    $stmt->execute([$number]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Challenge numbers this user has already solved. */
function getCompletedChallengeNumbers(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('
        SELECT c.challenge_number
        FROM user_progress up
        JOIN challenges c ON c.id = up.challenge_id
        WHERE up.user_id = ?
    ');
    $stmt->execute([$userId]);
    return array_map('intval', array_column($stmt->fetchAll(), 'challenge_number'));
}

/**
 * Sequential-unlock rule, evaluated SERVER-SIDE everywhere it matters
 * (challenge.php, submit_flag.php, download.php). The client never gets
 * a say in this - it only ever reflects it in the UI.
 */
function isChallengeUnlocked(int $challengeNumber, array $completedNumbers): bool
{
    if ($challengeNumber === 1) {
        return true;
    }
    return in_array($challengeNumber - 1, $completedNumbers, true);
}

function totalPointsForUser(PDO $pdo, int $userId): int
{
    // points_awarded is frozen when a challenge is solved (base points
    // minus the hint penalty at that moment), so it is summed as-is.
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(points_awarded), 0) FROM user_progress WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * MODULAR ATTACHMENT LOOKUP
 * -------------------------
 * Returns the attachment rows for a challenge. Rendering + authorization
 * both live in includes/attachments.php and download.php so this stays
 * a single reusable building block for every challenge.
 */
function getAttachments(PDO $pdo, int $challengeId): array
{
    $stmt = $pdo->prepare('
        SELECT id, display_name, stored_filename
        FROM challenge_attachments
        WHERE challenge_id = ?
        ORDER BY sort_order ASC, id ASC
    ');
    $stmt->execute([$challengeId]);
    return $stmt->fetchAll();
}

/** Points the user actually earned on one challenge (0 if not solved). */
function getAwardedPoints(PDO $pdo, int $userId, int $challengeId): int
{
    $stmt = $pdo->prepare('SELECT points_awarded FROM user_progress WHERE user_id = ? AND challenge_id = ?');
    $stmt->execute([$userId, $challengeId]);
    return (int)$stmt->fetchColumn();
}

/**
 * HINTS
 * -----
 * Returns every hint for a challenge, in reveal order, with a 'revealed'
 * flag for this user. The hint TEXT is only included for hints the user
 * has already revealed - unrevealed text never leaves the server.
 */
function getHintsForChallenge(PDO $pdo, int $challengeId, int $userId): array
{
    $stmt = $pdo->prepare('
        SELECT h.id, h.hint_order, h.hint_text, (uh.id IS NOT NULL) AS revealed
        FROM challenge_hints h
        LEFT JOIN user_hints uh ON uh.hint_id = h.id AND uh.user_id = ?
        WHERE h.challenge_id = ?
        ORDER BY h.hint_order ASC, h.id ASC
    ');
    $stmt->execute([$userId, $challengeId]);

    $hints = [];
    foreach ($stmt->fetchAll() as $row) {
        $revealed = (bool)$row['revealed'];
        $hints[] = [
            'id'       => (int)$row['id'],
            'revealed' => $revealed,
            'text'     => $revealed ? $row['hint_text'] : null,
        ];
    }
    return $hints;
}

function countHintsTaken(PDO $pdo, int $userId, int $challengeId): int
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM user_hints uh
        JOIN challenge_hints h ON h.id = uh.hint_id
        WHERE uh.user_id = ? AND h.challenge_id = ?
    ');
    $stmt->execute([$userId, $challengeId]);
    return (int)$stmt->fetchColumn();
}

/**
 * SIDEBAR DATA
 * One row per challenge (in order) with everything the sidebar shows:
 * number, title, points (earned if solved, otherwise what it is worth
 * right now after any hints taken). Only titles/points are exposed -
 * descriptions, hints and flags stay server-gated.
 */
function getSidebarChallenges(PDO $pdo, int $userId): array
{
    static $cache = [];
    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    $stmt = $pdo->prepare('
        SELECT c.challenge_number, c.title, c.difficulty,
               (SELECT COUNT(*) FROM user_hints uh
                  JOIN challenge_hints h ON h.id = uh.hint_id
                 WHERE uh.user_id = ? AND h.challenge_id = c.id) AS hints_taken,
               (SELECT up.points_awarded FROM user_progress up
                 WHERE up.user_id = ? AND up.challenge_id = c.id) AS awarded
        FROM challenges c
        ORDER BY c.challenge_number ASC
    ');
    $stmt->execute([$userId, $userId]);

    $rows = [];
    foreach ($stmt->fetchAll() as $r) {
        $rows[(int)$r['challenge_number']] = [
            'title'  => trim($r['title']),
            'points' => $r['awarded'] !== null
                ? (int)$r['awarded']
                : pointsAfterHints($r['difficulty'], (int)$r['hints_taken']),
        ];
    }
    return $cache[$userId] = $rows;
}
