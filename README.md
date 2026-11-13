# Task Tracker

A core PHP, PDO and MySQL task tracker for the RTIH technical assignment. Levels 1, 2 and 3 are implemented, along with dashboard counts.

## Setup with XAMPP

1. Start Apache and MySQL in XAMPP. PHP 8.0+ with `pdo_mysql`, `mbstring` and sessions is required; `curl` is also needed to run the smoke test.
2. Place the project in `C:/xampp/htdocs/task-tracker`.
3. In phpMyAdmin, import `database.sql`, or run in Git Bash:

   ```bash
   /c/xampp/mysql/bin/mysql.exe -u root < database.sql
   ```

   The script creates the `task_tracker` database and tables if absent; it does not replace existing tables. Use a fresh database if existing tables have a different schema. If your MySQL account has a password, use `-p` and enter it when prompted.
4. The default connection uses `127.0.0.1:3306`, database `task_tracker`, and XAMPP's local `root` account with an empty password. For other installations, set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASSWORD` in Apache's environment, then restart Apache. Do not commit real credentials. A dedicated least-privilege database account is preferable outside local development.
5. Open <http://localhost/task-tracker/> and create an account. No pre-created credentials or sample accounts are included.

The SQL script has already been imported into the initially empty local `task_tracker` database for this workspace.

## Roles and permissions

There is one role: a registered user. Each user can create and manage only their own tasks. Every task query uses the authenticated session's user ID; another user's ID returns 404 for viewing, editing or deleting. Login is required for the dashboard and task operations. Passwords are hashed with `password_hash` and verified with `password_verify`; queries with user input are prepared, output is escaped, and state-changing forms have CSRF tokens.

## Testing

With Apache and MySQL running and the database imported, run:

```bash
/c/xampp/php/php.exe tests/smoke.php
```

The script creates two uniquely named temporary accounts and removes them and their tasks afterward. It uses `http://localhost/task-tracker/` by default; set `TEST_BASE_URL` for another local address. Run it only against a test/development installation.

Results on the local XAMPP installation: **19 checks passed**. These covered registration, login/logout, dashboard access, required title validation, task creation/editing/status/deletion, search and status filter, stored password hash and preserved password spaces, escaped output, CSRF rejection, and attempts by another user to view, edit and delete a task. PHP syntax checks also passed for the application files. The browser showed the registration, dashboard and task form screens, and cancelling the delete confirmation kept the task. Screenshots are in `screenshots/` and use temporary sample data.

## Backup and restore

Export from phpMyAdmin, or use the following in Git Bash (add `-p` if needed):

```bash
/c/xampp/mysql/bin/mysqldump.exe -u root --databases task_tracker > task_tracker_backup.sql
/c/xampp/mysql/bin/mysql.exe -u root < task_tracker_backup.sql
```

Store backups securely: they contain user data and password hashes. Restoring can overwrite existing data; verify the target and back it up first. `database.sql` is a schema-only starting point, not a backup of users and tasks.

## Limitations and submission checklist

- The task list is not paginated. Login throttling, password reset and email verification are not implemented; HTTPS and production server hardening are needed before public deployment.
- Delete confirmation requires JavaScript. Forms and backend authorization remain enforced server-side.