<?php
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Please enter both a username and password.';
        } else {
            $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Always run password_verify, even on a missing user, so login
            // takes the same time either way (mitigates username enumeration
            // via timing).
            $hashToCheck = $user['password_hash'] ?? '$2y$10$invalidinvalidinvalidueinvalidinvalidinvalidinvalidin';
            $ok = password_verify($password, $hashToCheck);

            if ($user && $ok) {
                session_regenerate_id(true); // prevent session fixation
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: index.php');
                exit;
            }
            $error = 'Invalid username or password.';
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Avalon CTF</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<?php include __DIR__ . '/includes/blueprint.php'; ?>
<div class="login-box">
    <h1>Avalon CTF</h1>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post" action="login.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn btn-primary">Log In</button>
    </form>
    <p class="auth-switch">New here? <a href="signup.php">Create an account</a></p>
</div>
</body>
</html>
