<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/Validator.php';

session_start();

if (isLoggedIn()) redirect('/dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();

    $v = new Validator();
    $username = $v->sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $v->required('username', $username)->required('password', $password);

    if ($v->fails()) {
        $error = $v->firstError();
    } elseif (!Auth::login($username, $password)) {
        $error = 'Invalid username or password.';
    } else {
        redirect('/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — PRIME POS</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-page">
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="fa-solid fa-store"></i>
            <h1>PRIME POS</h1>
            <p>Sari-Sari Store</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/auth/login.php" autocomplete="off">
            <?= CSRF::input() ?>
            <div class="form-group">
                <label><i class="fa-solid fa-user"></i> Username</label>
                <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-lock"></i> Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </button>
        </form>
    </div>
</div>
</body>
</html>
