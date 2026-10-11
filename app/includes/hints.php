<?php
/**
 * MODULAR HINT SECTION
 * --------------------
 * Expects: $hints (from getHintsForChallenge), $isCompleted, $points.
 * Renders one line per hint that exists in the DB for this challenge -
 * nothing at all if the challenge has no hints. Text of hints the user
 * hasn't revealed is never sent to the browser.
 *
 * States per hint:
 *   revealed  -> text shown
 *   next      -> "Click to reveal this hint" (asks for confirmation)
 *   locked    -> disabled until the previous hint is revealed
 * On a solved challenge only already-revealed hints are listed.
 */
if (empty($hints)) { return; }

$nextAssigned = false;
$visible = [];
foreach ($hints as $i => $h) {
    if ($h['revealed']) {
        $state = 'revealed';
    } elseif ($isCompleted) {
        continue; // solved: no point showing hints that were never taken
    } elseif (!$nextAssigned) {
        $state = 'next';
        $nextAssigned = true;
    } else {
        $state = 'locked';
    }
    $visible[] = [$i + 1, $h, $state];
}
if (empty($visible)) { return; }
?>
<section class="challenge-section hints-section" id="hints-section">
    <h3>Hints <span class="hint-cost">(each hint costs <?= HINT_PENALTY ?> pts on this challenge)</span></h3>
    <div class="hint-list">
        <?php foreach ($visible as [$n, $h, $state]): ?>
            <?php if ($state === 'revealed'): ?>
                <div class="hint-item is-revealed" data-hint-index="<?= $n ?>">
                    <span class="hint-label">Hint <?= $n ?></span>
                    <div class="hint-text"><?= renderTextWithLinks($h['text']) ?></div>
                </div>
            <?php else: ?>
                <button type="button"
                        class="hint-item <?= $state === 'next' ? 'hint-reveal' : 'hint-reveal is-locked' ?>"
                        data-hint-id="<?= (int)$h['id'] ?>"
                        data-hint-index="<?= $n ?>"
                        <?= $state === 'locked' ? 'disabled title="Reveal the previous hint first"' : '' ?>>
                    <span class="hint-label">Hint <?= $n ?></span>
                    <span class="hint-cta"><?= $state === 'locked' ? 'Locked &ndash; reveal the previous hint first' : 'Click to reveal this hint' ?></span>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
