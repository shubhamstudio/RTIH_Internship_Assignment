<?php
// Run against a local instance: php tests/smoke.php. Creates and removes two test accounts.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$base = getenv('TEST_BASE_URL') ?: 'http://localhost/task-tracker/';
$suffix = bin2hex(random_bytes(6));
$emails = ["test-$suffix-a@example.test", "test-$suffix-b@example.test"];
$cookies = [tempnam(sys_get_temp_dir(), 'tracker'), tempnam(sys_get_temp_dir(), 'tracker')];
function request(string $path, string $cookie, ?array $data = null): array {
    global $base;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIEFILE => $cookie, CURLOPT_COOKIEJAR => $cookie]);
    if ($data !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); }
    $response = curl_exec($ch);
    if ($response === false) throw new RuntimeException(curl_error($ch));
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); $size = curl_getinfo($ch, CURLINFO_HEADER_SIZE); curl_close($ch);
    return [$status, substr($response, $size)];
}
function csrf(string $html): string {
    if (!preg_match('/name="csrf" value="([a-f0-9]+)"/', $html, $match)) throw new RuntimeException('CSRF token missing');
    return $match[1];
}
function check(bool $condition, string $message): void { if (!$condition) throw new RuntimeException('FAIL: ' . $message); echo "PASS: $message\n"; }
try {
    check(request('index.php', $cookies[0])[0] === 302, 'Dashboard requires login');
    foreach ($emails as $i => $email) {
        $token = csrf(request('register.php', $cookies[$i])[1]);
        check(request('register.php', $cookies[$i], ['csrf'=>$token, 'name'=>'Test User', 'email'=>$email, 'password'=>' test-password ', 'confirm_password'=>' test-password '])[0] === 302, "Register user $i");
        $token = csrf(request('login.php', $cookies[$i])[1]);
        check(request('login.php', $cookies[$i], ['csrf'=>$token, 'email'=>$email, 'password'=>' test-password '])[0] === 302, "Sign in user $i");
    }
    $token = csrf(request('task.php', $cookies[0])[1]);
    check(str_contains(request('task.php', $cookies[0], ['csrf'=>$token, 'title'=>' ', 'description'=>'', 'status'=>'Pending'])[1], 'Title is required'), 'Reject empty title');
    check(request('task.php', $cookies[0], ['csrf'=>$token, 'title'=>'<script>Test</script>', 'description'=>'Example', 'status'=>'Pending'])[0] === 302, 'Create task');
    $dashboard = request('index.php', $cookies[0])[1];
    check(str_contains($dashboard, '&lt;script&gt;Test&lt;/script&gt;') && !str_contains($dashboard, '<script>Test</script>'), 'Escape task title');
    preg_match('/task\.php\?id=(\d+)/', $dashboard, $match); $id = $match[1] ?? throw new RuntimeException('Task ID missing');
    check(request('task.php?id='.$id, $cookies[1])[0] === 404, 'Other user cannot view task');
    $otherToken = csrf(request('index.php', $cookies[1])[1]);
    check(request('delete.php', $cookies[1], ['csrf'=>$otherToken, 'id'=>$id])[0] === 404, 'Other user cannot delete task');
    check(request('task.php', $cookies[1], ['csrf'=>$otherToken, 'id'=>$id, 'title'=>'stolen', 'status'=>'Completed'])[0] === 404, 'Other user cannot edit task');
    check(request('task.php', $cookies[0], ['csrf'=>'invalid', 'id'=>$id, 'title'=>'changed', 'status'=>'Completed'])[0] === 403, 'Reject invalid CSRF token');
    check(request('task.php', $cookies[0], ['csrf'=>$token, 'id'=>$id, 'title'=>'Updated title', 'description'=>'Example', 'status'=>'Completed'])[0] === 302, 'Edit task and change status');
    check(str_contains(request('index.php?search=Updated&status=Completed', $cookies[0])[1], 'Updated title'), 'Search and filter matching task');
    check(!str_contains(request('index.php?search=Updated&status=Pending', $cookies[0])[1], 'Updated title'), 'Filter excludes nonmatching status');
    check(request('delete.php', $cookies[0], ['csrf'=>$token, 'id'=>$id])[0] === 302, 'Delete task');
    check(request('task.php?id='.$id, $cookies[0])[0] === 404, 'Deleted task is unavailable');
    require __DIR__ . '/../config/database.php';
    $stmt = database()->prepare('SELECT password_hash FROM users WHERE email = ?'); $stmt->execute([$emails[0]]); $hash = $stmt->fetchColumn();
    check(is_string($hash) && password_verify(' test-password ', $hash) && !password_verify('test-password', $hash), 'Password stored as a hash with spaces preserved');
    check(request('logout.php', $cookies[0], ['csrf'=>$token])[0] === 302 && request('index.php', $cookies[0])[0] === 302, 'Logout ends dashboard access');
} finally {
    require_once __DIR__ . '/../config/database.php';
    $stmt = database()->prepare('DELETE FROM users WHERE email = ?'); foreach ($emails as $email) $stmt->execute([$email]);
    foreach ($cookies as $cookie) if (is_string($cookie) && is_file($cookie)) unlink($cookie);
}
