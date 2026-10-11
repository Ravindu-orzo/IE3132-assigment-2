<?php
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your session expired. Please reload the page.']);
    exit;
}

$userId          = currentUserId();
$challengeNumber = filter_input(INPUT_POST, 'challenge_number', FILTER_VALIDATE_INT);
$flagInput       = trim((string)($_POST['flag'] ?? ''));

if (!$challengeNumber || $challengeNumber < 1 || $challengeNumber > TOTAL_CHALLENGES || $flagInput === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid submission.']);
    exit;
}

// Very small per-session throttle to slow down automated flag guessing.
if (!empty($_SESSION['last_attempt_time']) && (microtime(true) - $_SESSION['last_attempt_time']) < 1.0) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Please slow down and try again.']);
    exit;
}
$_SESSION['last_attempt_time'] = microtime(true);

$completed = getCompletedChallengeNumbers($pdo, $userId);

/**
 * SERVER-SIDE ACCESS CONTROL (anti-IDOR)
 * Re-checked here independently of the page the request came from - a
 * user cannot POST directly to this endpoint to solve/skip a locked
 * challenge, regardless of what the browser sent.
 */
if (!isChallengeUnlocked($challengeNumber, $completed)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'This challenge is locked.']);
    exit;
}

$challenge = getChallengeByNumber($pdo, $challengeNumber);
if (!$challenge) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Challenge not found.']);
    exit;
}

$alreadyCompleted = in_array($challengeNumber, $completed, true);

// Quick format check first - every valid flag looks like AVALON{...}.
// This gives a clearer error for obviously-wrong input without needing
// a (slightly more expensive) hash comparison.
if (!isValidFlagFormat($flagInput)) {
    echo json_encode(['success' => false, 'message' => 'Flags must be in the format AVALON{...}.']);
    exit;
}

// Flags are stored as password_hash() hashes, never in plaintext, and
// never sent to the browser at any point.
$isCorrect = password_verify($flagInput, $challenge['flag_hash']);

if (!$isCorrect) {
    echo json_encode(['success' => false, 'message' => 'Incorrect flag. Try again.']);
    exit;
}

$pointsAwarded = 0;
if (!$alreadyCompleted) {
    // Points are frozen now: base points for the difficulty minus
    // HINT_PENALTY for every hint this user revealed on THIS challenge.
    $hintsTaken    = countHintsTaken($pdo, $userId, (int)$challenge['id']);
    $pointsAwarded = pointsAfterHints($challenge['difficulty'], $hintsTaken);

    $stmt = $pdo->prepare('INSERT IGNORE INTO user_progress (user_id, challenge_id, points_awarded, completed_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$userId, $challenge['id'], $pointsAwarded]);
    if ($stmt->rowCount() === 0) {
        $pointsAwarded = 0; // lost a race with a duplicate submission; already counted
    }
}

$newCompleted = getCompletedChallengeNumbers($pdo, $userId);

echo json_encode([
    'success'        => true,
    'message'        => $alreadyCompleted ? 'Correct! (already solved)' : 'Correct! Challenge solved. +' . $pointsAwarded . ' pts',
    'points_awarded' => $pointsAwarded,
    'total_points'   => totalPointsForUser($pdo, $userId),
    'next_unlocked'  => ($challengeNumber < TOTAL_CHALLENGES) ? $challengeNumber + 1 : null,
    'all_completed'  => count($newCompleted) >= TOTAL_CHALLENGES,
]);
