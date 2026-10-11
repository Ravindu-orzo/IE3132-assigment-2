<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? '');

        if ($username === '' || $password === '' || $confirm === '') {
            $error = 'Please fill in every field.';
        } elseif (!isValidUsername($username)) {
            $error = 'Username must be 3-20 characters, letters, numbers, or underscores only.';
        } elseif (!isValidPassword($password)) {
            $error = 'Password must be at least 8 characters and include a letter and a number.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            // Uniqueness check - kept as a separate, clearly-labelled step
            // (not a security boundary by itself; the UNIQUE constraint on
            // users.username is the real guarantee against a race between
            // two simultaneous signups with the same name).
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);

            if ($stmt->fetch()) {
                $error = 'That username is already taken.';
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $insert = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
                    $insert->execute([$username, $hash]);

                    $newUserId = (int)$pdo->lastInsertId();

                    session_regenerate_id(true);
                    $_SESSION['user_id']  = $newUserId;
                    $_SESSION['username'] = $username;
                    header('Location: index.php');
                    exit;
                } catch (PDOException $e) {
                    // Most likely a duplicate-username race that slipped past
                    // the check above - the UNIQUE constraint caught it.
                    $error = 'That username is already taken.';
                }
            }
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
<title>Sign Up - Avalon CTF</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<?php include __DIR__ . '/includes/blueprint.php'; ?>
<div class="login-box">
    <h1>Avalon CTF</h1>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post" action="signup.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" required autofocus>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <p class="field-hint">3-20 characters for username. Password needs 8+ characters with a letter and a number.</p>

        <button type="submit" class="btn btn-primary">Sign Up</button>
    </form>
    <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
</div>
</body>
</html>
