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
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
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
    $user = current_user();
    if ($user === null) {
        header('Location: index.php');
        exit;
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Admins only.');
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
