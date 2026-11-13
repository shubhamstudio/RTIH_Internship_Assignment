<?php
require __DIR__ . '/includes/bootstrap.php';
if (user_id()) redirect('index.php');
$errors = []; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf(); $email = strtolower(input('email')); $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') $errors[] = 'Enter your email and password.';
    if (!$errors) {
        $stmt = database()->prepare('SELECT id, name, password_hash FROM users WHERE email = ?'); $stmt->execute([$email]); $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true); $_SESSION['user_id'] = (int) $user['id']; $_SESSION['name'] = $user['name']; unset($_SESSION['csrf']); redirect('index.php');
        }
        $errors[] = 'Email or password is incorrect.';
    }
}
page_start('Sign in');
?><section class="auth-card"><p class="eyebrow">WELCOME BACK</p><h1>Sign in</h1><p class="muted">Pick up right where you left off.</p><?php show_errors($errors); ?><form method="post" class="form"><?= csrf_field() ?><label>Email<input type="email" name="email" required maxlength="254" autocomplete="email" value="<?= e($email) ?>"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="button" type="submit">Sign in</button></form><p class="auth-switch">New here? <a href="register.php">Create an account</a></p></section><?php page_end();
