<?php
/**
 * Expects:
 *   $completed        - array of completed challenge numbers (from getCompletedChallengeNumbers)
 *   $activeNumber     - (optional) the challenge number currently being viewed, for highlighting
 *
 * NOTE: This file only controls what the sidebar LOOKS like. The real
 * access control lives server-side in challenge.php / congrats.php /
 * submit_flag.php / download.php. Hiding a link here does not, by
 * itself, protect anything.
 */
$activeNumber = $activeNumber ?? null;
$allDone = count($completed) >= TOTAL_CHALLENGES;
$sidebarRows = getSidebarChallenges($pdo, currentUserId());
?>
<nav class="sidebar">
    <div class="sidebar-head">
        <span>Challenges</span>
        <span class="sidebar-count"><?= count($completed) ?> / <?= TOTAL_CHALLENGES ?></span>
    </div>

    <ul class="challenge-list">
        <?php for ($n = 1; $n <= TOTAL_CHALLENGES; $n++):
            $isDone = in_array($n, $completed, true);
            $isUnlocked = isChallengeUnlocked($n, $completed);
            $isActive = ($activeNumber === $n);
            $classes = ['challenge-item'];
            if ($isDone) $classes[] = 'is-completed';
            if (!$isUnlocked) $classes[] = 'is-locked';
            if ($isActive) $classes[] = 'is-active';
            $row   = $sidebarRows[$n] ?? ['title' => 'Challenge ' . $n, 'points' => 0];
            $mark  = $isDone ? '✓' : ($isActive ? '▸' : ' ');
            $inner = '<span class="ci-check" aria-hidden="true">[' . $mark . ']</span>'
                   . '<span class="ci-id">' . str_pad((string)$n, 2, '0', STR_PAD_LEFT) . '</span>'
                   . '<span class="ci-name">' . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . '</span>'
                   . '<span class="ci-pts">' . (int)$row['points'] . '</span>';
        ?>
            <li class="<?= implode(' ', $classes) ?>">
                <?php if ($isUnlocked): ?>
                    <a class="ci-row" href="challenge.php?id=<?= $n ?>"><?= $inner ?></a>
                <?php else: ?>
                    <div class="ci-row" aria-disabled="true"><?= $inner ?></div>
                <?php endif; ?>
            </li>
        <?php endfor; ?>

        <li class="challenge-item congrats-item <?= $allDone ? '' : 'is-locked' ?> <?= ($activeNumber === 'congrats') ? 'is-active' : '' ?>">
            <?php
            $cInner = '<span class="ci-check" aria-hidden="true">[' . ($allDone ? '★' : ' ') . ']</span>'
                    . '<span class="ci-id">--</span>'
                    . '<span class="ci-name">Congratulations</span>'
                    . '<span class="ci-pts"></span>';
            ?>
            <?php if ($allDone): ?>
                <a class="ci-row" href="congrats.php"><?= $cInner ?></a>
            <?php else: ?>
                <div class="ci-row" aria-disabled="true"><?= $cInner ?></div>
            <?php endif; ?>
        </li>
    </ul>

    <div class="sidebar-footer">
        <button type="button" id="reset-progress" class="btn btn-reset">&#8635; Reset progress</button>
    </div>
</nav>
