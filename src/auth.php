<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $config = is_file(dirname(__DIR__) . '/config.php')
            ? require dirname(__DIR__) . '/config.php' : [];
        session_set_cookie_params([
            'httponly' => true,
            'secure' => (bool) ($config['cookie_secure'] ?? false),
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    start_session();
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken)
        || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $submittedToken)) {
        http_response_code(403);
        exit('Invalid form token. Please go back and try again.');
    }
}

function current_user(): ?array
{
    start_session();
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $loginPath = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../index.php' : 'index.php';
    $user = current_user();
    if ($user === null) {
        header('Location: ' . $loginPath);
        exit;
    }
    $statement = db()->prepare('SELECT id, name, role, active FROM users WHERE id = ? LIMIT 1');
    $statement->execute([(int) $user['id']]);
    $current = $statement->fetch();
    if (!$current || !(int) $current['active']) {
        logout_user();
        header('Location: ' . $loginPath);
        exit;
    }
    $_SESSION['user'] = [
        'id' => (int) $current['id'],
        'name' => $current['name'],
        'role' => $current['role'],
    ];
    return $_SESSION['user'];
}

function require_admin(): array
{
    $user = require_login();
    if (!in_array($user['role'], ['owner', 'admin'], true)) {
        http_response_code(403);
        exit('Owner or admin access required.');
    }
    return $user;
}

function require_owner(): array
{
    $user = require_login();
    if ($user['role'] !== 'owner') {
        http_response_code(403);
        exit('Owner access required.');
    }
    return $user;
}

function login(array $user): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'role' => $user['role'],
    ];
}

function logout_user(): void
{
    start_session();
    $_SESSION = [];
    session_destroy();
}
