<?php
/**
 * Wipes the logged-in user's progress: solved challenges and revealed
 * hints. Afterwards only Challenge 1 is unlocked again and every
 * challenge is worth its full points. Only ever touches the current
 * user's own rows (the id comes from the session, not the request).
 * POST: csrf_token
 */
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}
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

$userId = currentUserId();

try {
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM user_progress WHERE user_id = ?')->execute([$userId]);
    $pdo->prepare('DELETE FROM user_hints WHERE user_id = ?')->execute([$userId]);
    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Reset failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not reset progress. Please try again.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Progress reset.']);
