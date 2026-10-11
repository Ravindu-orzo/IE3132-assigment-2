<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$userId = currentUserId();
$completed = getCompletedChallengeNumbers($pdo, $userId);

$requestedNumber = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$requestedNumber || $requestedNumber < 1 || $requestedNumber > TOTAL_CHALLENGES) {
    header('Location: index.php');
    exit;
}

/**
 * SERVER-SIDE ACCESS CONTROL (anti-IDOR)
 * A user cannot view a challenge by simply changing ?id= in the URL -
 * this check is done fresh, from the DB, on every request. The sidebar
 * hiding the link is only a UI convenience, never the real gate.
 */
if (!isChallengeUnlocked($requestedNumber, $completed)) {
    header('Location: index.php');
    exit;
}

$challenge = getChallengeByNumber($pdo, $requestedNumber);
if (!$challenge) {
    header('Location: index.php');
    exit;
}

$attachments   = getAttachments($pdo, $challenge['id']);
$basePoints    = pointsForDifficulty($challenge['difficulty']);
$isCompleted   = in_array($requestedNumber, $completed, true);
$hints         = getHintsForChallenge($pdo, (int)$challenge['id'], $userId);
$hintsTaken    = 0;
foreach ($hints as $h) { if ($h['revealed']) { $hintsTaken++; } }
// Solved: show what was actually earned. Unsolved: what it's worth right now.
$points        = $isCompleted
    ? getAwardedPoints($pdo, $userId, (int)$challenge['id'])
    : pointsAfterHints($challenge['difficulty'], $hintsTaken);
$totalPoints   = totalPointsForUser($pdo, $userId);
$csrfToken     = generateCsrfToken();
$activeNumber  = $requestedNumber;
$pageTitle     = 'Challenge ' . $requestedNumber . ' - Avalon CTF';

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main class="content">
    <div class="challenge-window" data-challenge-number="<?= (int)$requestedNumber ?>" data-hint-penalty="<?= HINT_PENALTY ?>">
        <div class="challenge-header">
            <h1 class="challenge-title"><?= htmlspecialchars($challenge['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="challenge-meta">
                <span class="challenge-id">ID: CH-<?= str_pad((string)$requestedNumber, 2, '0', STR_PAD_LEFT) ?></span>
                <span class="badge <?= difficultyClass($challenge['difficulty']) ?>">
                    <?= htmlspecialchars(ucfirst($challenge['difficulty']), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="badge badge-points" id="points-badge" data-base="<?= $basePoints ?>">
                    <?php if ($points !== $basePoints): ?><s class="points-old"><?= $basePoints ?></s> <?php endif; ?><?= $points ?> pts
                </span>
                <?php if ($isCompleted): ?>
                    <span class="badge badge-solved">Solved ✓</span>
                <?php endif; ?>
            </div>
        </div>

        <section class="challenge-section">
            <h3>Scenario</h3>
            <div class="challenge-text">
                <?= renderTextWithLinks($challenge['description']) ?>
            </div>
        </section>

        <section class="challenge-section">
            <h3>Your Task</h3>
            <div class="challenge-text">
                <?= renderTextWithLinks($challenge['player_task']) ?>
            </div>
        </section>

        <?php include __DIR__ . '/includes/attachments.php'; ?>

        <?php include __DIR__ . '/includes/hints.php'; ?>

        <section class="flag-section">
            <h3>Submit Flag</h3>
            <form id="flag-form" autocomplete="off">
                <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="challenge_number" value="<?= (int)$requestedNumber ?>">
                <div class="flag-row">
                    <input type="text" id="flag-input" name="flag" placeholder="AVALON{...}" <?= $isCompleted ? 'disabled' : '' ?>>
                    <button type="submit" id="flag-submit" class="btn btn-primary" <?= $isCompleted ? 'disabled' : '' ?>>Submit</button>
                </div>
                <p id="flag-error" class="flag-error" hidden></p>
                <p id="flag-success" class="flag-success" hidden></p>
            </form>
        </section>
    </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
