<?php
/**
 * Expects (optionally) $totalPoints to already be set by the including page.
 * Falls back to computing it if not, so this is always safe to include.
 */
if (!isset($totalPoints)) {
    $totalPoints = currentUserId() ? totalPointsForUser($pdo, currentUserId()) : 0;
}
$pageTitle = $pageTitle ?? 'Avalon CTF';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/blueprint.php'; ?>
<header class="navbar">
    <div class="navbar-left">
        <span class="site-name">Avalon CTF</span>
    </div>
    <div class="navbar-right">
        <span class="nav-username"><?= htmlspecialchars(currentUsername(), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="nav-points"><?= (int)$totalPoints ?> pts</span>
        <a href="logout.php" class="btn btn-logout">Logout</a>
    </div>
</header>
<div class="layout">
