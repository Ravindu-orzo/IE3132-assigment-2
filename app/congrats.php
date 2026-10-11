<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$userId    = currentUserId();
$completed = getCompletedChallengeNumbers($pdo, $userId);

// SERVER-SIDE ACCESS CONTROL: you cannot reach this page early just by
// typing the URL - it's rechecked from the DB every time.
if (count($completed) < TOTAL_CHALLENGES) {
    header('Location: index.php');
    exit;
}

$totalPoints  = totalPointsForUser($pdo, $userId);
$activeNumber = 'congrats';
$pageTitle    = 'Congratulations! - Avalon CTF';

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main class="content">
    <div class="challenge-window congrats-window">
        <h1>🎉 Congratulations, <?= htmlspecialchars(currentUsername(), ENT_QUOTES, 'UTF-8') ?>!</h1>
        <p class="congrats-text">
            You've completed all <?= TOTAL_CHALLENGES ?> challenges on Avalon CTF.
            Great work thinking like an attacker and a defender at the same time.
        </p>
        <p class="congrats-score">Final score: <strong><?= (int)$totalPoints ?> pts</strong></p>
    </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
