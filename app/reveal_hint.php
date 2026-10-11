<?php
/**
 * Reveals the NEXT hint of a challenge for the logged-in user.
 * POST: csrf_token, hint_id
 *
 * Rules, all enforced server-side:
 *  - challenge must be unlocked for the user and not yet solved
 *  - hints must be revealed in order (hint 2 only after hint 1, ...)
 *  - revealing an already-revealed hint is free (returns the text again)
 *  - every newly revealed hint costs HINT_PENALTY points on this challenge
 */
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

function jsonOut(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (empty($_SESSION['user_id'])) {
    jsonOut(401, ['success' => false, 'message' => 'Please log in again.']);
}

require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(405, ['success' => false, 'message' => 'Invalid request method.']);
}
if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    jsonOut(403, ['success' => false, 'message' => 'Your session expired. Please reload the page.']);
}

$userId = currentUserId();
$hintId = filter_input(INPUT_POST, 'hint_id', FILTER_VALIDATE_INT);
if (!$hintId || $hintId < 1) {
    jsonOut(400, ['success' => false, 'message' => 'Invalid request.']);
}

// The challenge is taken from the hint's own row, never from the client.
$stmt = $pdo->prepare('
    SELECT h.id, h.hint_text, c.id AS challenge_id, c.challenge_number, c.difficulty
    FROM challenge_hints h
    JOIN challenges c ON c.id = h.challenge_id
    WHERE h.id = ?
    LIMIT 1
');
$stmt->execute([$hintId]);
$hint = $stmt->fetch();
if (!$hint) {
    jsonOut(404, ['success' => false, 'message' => 'Hint not found.']);
}

$completed = getCompletedChallengeNumbers($pdo, $userId);
$number    = (int)$hint['challenge_number'];

if (!isChallengeUnlocked($number, $completed)) {
    jsonOut(403, ['success' => false, 'message' => 'This challenge is locked.']);
}

$base = pointsForDifficulty($hint['difficulty']);

$hints = getHintsForChallenge($pdo, (int)$hint['challenge_id'], $userId);
$taken = 0;
$nextId = null;
foreach ($hints as $h) {
    if ($h['revealed']) {
        $taken++;
    } elseif ($nextId === null) {
        $nextId = $h['id'];
    }
}

$alreadyRevealed = false;
foreach ($hints as $h) {
    if ($h['id'] === (int)$hint['id'] && $h['revealed']) {
        $alreadyRevealed = true;
    }
}

$response = static function (int $taken) use ($hint, $base): array {
    return [
        'success'         => true,
        'hint_text_html'  => renderTextWithLinks($hint['hint_text']),
        'hints_taken'     => $taken,
        'base_points'     => $base,
        'points'          => pointsAfterHints($hint['difficulty'], $taken),
        'penalty'         => HINT_PENALTY,
    ];
};

if ($alreadyRevealed) {
    jsonOut(200, $response($taken)); // free: already paid for
}

// Solved challenges can't take (and be charged for) hints afterwards.
if (in_array($number, $completed, true)) {
    jsonOut(409, ['success' => false, 'message' => 'You have already solved this challenge.']);
}

// Sequential rule: only the first not-yet-revealed hint can be taken.
if ($nextId !== (int)$hint['id']) {
    jsonOut(409, ['success' => false, 'message' => 'Reveal the previous hint first.']);
}

$ins = $pdo->prepare('INSERT IGNORE INTO user_hints (user_id, hint_id) VALUES (?, ?)');
$ins->execute([$userId, $hint['id']]);
if ($ins->rowCount() > 0) {
    $taken++;
}

jsonOut(200, $response($taken));
