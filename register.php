<?php
require __DIR__ . '/includes/bootstrap.php';
if (user_id()) redirect('index.php');
$errors = []; $name = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf(); $name = input('name'); $email = strtolower(input('email')); $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Enter your name (up to 100 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8 || strlen($password) > 72) $errors[] = 'Password must be between 8 and 72 bytes.';
    if ($password !== (isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '')) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        try {
            $stmt = database()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('Account created. Sign in to get started.'); redirect('login.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') $errors[] = 'An account with that email already exists.';
            else { error_log('Registration failed: ' . $exception->getMessage()); $errors[] = 'Unable to create your account right now.'; }
        }
    }
}
page_start('Create account');
?><section class="auth-card"><p class="eyebrow">GET STARTED</p><h1>Create your account</h1><p class="muted">Keep your tasks together, in one calm space.</p><?php show_errors($errors); ?><form method="post" class="form"><?= csrf_field() ?><label>Name<input name="name" required maxlength="100" autocomplete="name" value="<?= e($name) ?>"></label><label>Email<input type="email" name="email" required maxlength="254" autocomplete="email" value="<?= e($email) ?>"></label><label>Password<input type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"><small>At least 8 characters.</small></label><label>Confirm password<input type="password" name="confirm_password" required minlength="8" maxlength="72" autocomplete="new-password"></label><button class="button" type="submit">Create account</button></form><p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p></section><?php page_end();
