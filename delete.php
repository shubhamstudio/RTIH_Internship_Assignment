<?php
require __DIR__ . '/includes/tasks.php';
$userId = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
check_csrf(); $id = task_id(); owned_task($id, $userId);
$stmt = database()->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?'); $stmt->execute([$id, $userId]);
flash('Task deleted successfully.'); redirect('index.php');
