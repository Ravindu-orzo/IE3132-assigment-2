<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$userId = currentUserId();
$completed = getCompletedChallengeNumbers($pdo, $userId);

if (count($completed) >= TOTAL_CHALLENGES) {
    header('Location: congrats.php');
    exit;
}

for ($n = 1; $n <= TOTAL_CHALLENGES; $n++) {
    if (!in_array($n, $completed, true)) {
        header('Location: challenge.php?id=' . $n);
        exit;
    }
}

// Fallback (should not normally be reached)
header('Location: challenge.php?id=1');
exit;
