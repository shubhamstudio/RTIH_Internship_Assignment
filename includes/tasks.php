<?php
require_once __DIR__ . '/bootstrap.php';
const STATUSES = ['Pending', 'In Progress', 'Completed'];
function owned_task(int $id, int $userId): array {
    $stmt = database()->prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?'); $stmt->execute([$id, $userId]); $task = $stmt->fetch();
    if (!$task) { http_response_code(404); exit('Task not found.'); }
    return $task;
}
function task_id(): int {
    $value = $_POST['id'] ?? $_GET['id'] ?? null;
    if (!is_scalar($value) || !preg_match('/^[1-9][0-9]*$/', (string) $value) || (float) $value > PHP_INT_MAX) { http_response_code(404); exit('Task not found.'); }
    return (int) $value;
}
function validate_task(string $title, string $description, string $status): array {
    $errors = [];
    if ($title === '') $errors[] = 'Title is required.';
    elseif (mb_strlen($title) > 200) $errors[] = 'Title must be at most 200 characters.';
    if (mb_strlen($description) > 10000) $errors[] = 'Description must be at most 10,000 characters.';
    if (!in_array($status, STATUSES, true)) $errors[] = 'Choose a valid status.';
    return $errors;
}
