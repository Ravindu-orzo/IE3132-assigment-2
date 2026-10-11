<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$userId       = currentUserId();
$attachmentId = filter_input(INPUT_GET, 'aid', FILTER_VALIDATE_INT);

if (!$attachmentId || $attachmentId < 1) {
    http_response_code(400);
    exit('Invalid request.');
}

// Look up the attachment together with its parent challenge's number.
// The challenge is never taken from the query string - only from the
// attachment's own DB row - so a request can't be pointed at a
// different challenge than the file actually belongs to.
$stmt = $pdo->prepare('
    SELECT ca.stored_filename, ca.display_name, c.challenge_number
    FROM challenge_attachments ca
    JOIN challenges c ON c.id = ca.challenge_id
    WHERE ca.id = ?
    LIMIT 1
');
$stmt->execute([$attachmentId]);
$attachment = $stmt->fetch();

if (!$attachment) {
    http_response_code(404);
    exit('File not found.');
}

$completed = getCompletedChallengeNumbers($pdo, $userId);

/**
 * SERVER-SIDE ACCESS CONTROL (anti-IDOR)
 * A file can only be downloaded if its parent challenge is currently
 * unlocked for the logged-in user - independent of which challenge
 * page the browser happens to be on, and independent of any value
 * submitted by the client other than the attachment id itself.
 */
if (!isChallengeUnlocked((int)$attachment['challenge_number'], $completed)) {
    http_response_code(403);
    exit('Access denied.');
}

// Defence in depth against path traversal: strip any directory
// components and confirm the resolved path is still inside the
// expected challenge folder before ever touching the filesystem.
$safeFilename = basename($attachment['stored_filename']);
$challengeDir = __DIR__ . '/attachments/challenge' . (int)$attachment['challenge_number'];
$filePath     = $challengeDir . '/' . $safeFilename;

$realBase = realpath($challengeDir);
$realFile = realpath($filePath);

if ($realBase === false || $realFile === false || strpos($realFile, $realBase) !== 0 || !is_file($realFile)) {
    http_response_code(404);
    exit('File not found.');
}

$mime = function_exists('mime_content_type') ? (mime_content_type($realFile) ?: 'application/octet-stream') : 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($attachment['display_name']) . '"');
header('Content-Length: ' . filesize($realFile));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

readfile($realFile);
exit;
