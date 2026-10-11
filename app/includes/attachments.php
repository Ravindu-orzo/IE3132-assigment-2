<?php
/**
 * MODULAR ATTACHMENT SECTION
 * --------------------------
 * Include this file after setting $attachments = getAttachments($pdo, $challenge['id']);
 * It renders a "Files" block with one download icon/button per attachment.
 *
 * Every download goes through download.php?aid=<id>, which re-checks on the
 * server that the attachment's parent challenge is unlocked for the logged
 * in user before it streams any bytes - the attachment id alone is never
 * enough to get a file (no IDOR).
 *
 * To add a real file to a challenge:
 *  1. Upload the file into attachments/challengeN/ via the InfinityFree
 *     file manager (or FTP).
 *  2. Add a row to challenge_attachments: challenge_id, display_name
 *     (what the user sees, e.g. "capture.pcap"), stored_filename (the
 *     actual filename on disk in that folder).
 * No code changes needed - this block will pick it up automatically.
 */
?>
<?php if (!empty($attachments)): ?>
<div class="attachments-section">
    <h3>Attachments</h3>
    <div class="attachment-list">
        <?php foreach ($attachments as $file): ?>
            <a class="attachment-chip"
               href="download.php?aid=<?= (int)$file['id'] ?>"
               title="Download <?= htmlspecialchars($file['display_name'], ENT_QUOTES, 'UTF-8') ?>">
                <span class="attachment-icon" aria-hidden="true">📎</span>
                <span class="attachment-name"><?= htmlspecialchars($file['display_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
