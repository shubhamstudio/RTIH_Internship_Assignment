<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
require_once __DIR__ . '/../config/database.php';

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }
function user_id(): ?int { return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null; }
function require_login(): int { if (!user_id()) redirect('login.php'); return user_id(); }
function token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(token()) . '">'; }
function check_csrf(): void {
    if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(token(), $_POST['csrf'])) {
        http_response_code(403); exit('Your session expired or the request was invalid. Please go back and try again.');
    }
}
function input(string $key): string { return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : ''; }
function flash(string $message): void { $_SESSION['flash'] = $message; }
function take_flash(): string { $message = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']); return $message; }
function page_start(string $title): void {
    $message = take_flash();
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> | Task Tracker</title><link rel="stylesheet" href="assets/style.css"></head><body><header class="topbar"><div class="wrap nav"><a class="brand" href="index.php"><span class="brand-icon">✓</span> Task Tracker</a><?php if (user_id()): ?><form action="logout.php" method="post"><?= csrf_field() ?><button class="link-button" type="submit">Sign out</button></form><?php else: ?><a class="nav-link" href="login.php">Sign in</a><?php endif; ?></div></header><main class="wrap"><?php if ($message): ?><div class="notice" role="status"><?= e($message) ?></div><?php endif;
}
function page_end(): void { ?></main><footer class="wrap footer">A little progress, every day.</footer><script src="assets/app.js" defer></script></body></html><?php }
function show_errors(array $errors): void { if ($errors): ?><div class="errors" role="alert"><strong>Please check the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; }
