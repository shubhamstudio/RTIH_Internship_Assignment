<?php
require __DIR__ . '/includes/tasks.php';
$userId = require_login(); $editing = isset($_GET['id']) || isset($_POST['id']);
$task = $editing ? owned_task(task_id(), $userId) : ['title' => '', 'description' => '', 'status' => 'Pending'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf(); $task['title'] = input('title'); $task['description'] = input('description'); $task['status'] = input('status');
    $errors = validate_task($task['title'], $task['description'], $task['status']);
    if (!$errors) {
        if ($editing) {
            $stmt = database()->prepare('UPDATE tasks SET title = ?, description = ?, status = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$task['title'], $task['description'], $task['status'], task_id(), $userId]);
        } else {
            $stmt = database()->prepare('INSERT INTO tasks (user_id, title, description, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $task['title'], $task['description'], $task['status']]);
        }
        flash($editing ? 'Task updated successfully.' : 'Task added successfully.'); redirect('index.php');
    }
}
page_start($editing ? 'Edit task' : 'New task');
?><div class="form-page"><a class="back" href="index.php">← Back to tasks</a><section class="panel"><p class="eyebrow"><?= $editing ? 'MAKE A CHANGE' : 'MAKE A PLAN' ?></p><h1><?= $editing ? 'Edit task' : 'Create a task' ?></h1><p class="muted">Give your task a clear name and add any useful details.</p><?php show_errors($errors); ?><form method="post" class="form"><?= csrf_field() ?><?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $task['id'] ?>"><?php endif; ?><label>Title <span class="required">*</span><input name="title" required maxlength="200" value="<?= e($task['title']) ?>" placeholder="What needs to get done?"></label><label>Description <span class="optional">Optional</span><textarea name="description" rows="6" maxlength="10000" placeholder="Add a little more detail..."><?= e($task['description']) ?></textarea></label><label>Status<select name="status" required><?php foreach (STATUSES as $status): ?><option value="<?= e($status) ?>" <?= $task['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></label><div class="actions"><button class="button" type="submit"><?= $editing ? 'Save changes' : 'Create task' ?></button><a class="button secondary" href="index.php">Cancel</a></div></form></section></div><?php page_end();
