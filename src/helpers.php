<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_valid_mobile(string $value): bool
{
    return (bool) preg_match('/^\d{10}$/', $value);
}

function is_valid_pincode(string $value): bool
{
    return (bool) preg_match('/^\d{6}$/', $value);
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function page_start(string $title, ?array $user = null): void
{
    $user ??= current_user();
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . '</title><link rel="stylesheet" href="assets/style.css">';
    echo '</head><body><main class="page"><header><h1>' . e($title) . '</h1>';
    if ($user) {
        echo '<p class="muted">Signed in as ' . e($user['name']) . '</p>';
    }
    echo '</header>';
}

function page_end(?array $user = null, string $active = ''): void
{
    $user ??= current_user();
    if ($user && $user['role'] === 'employee') {
        echo '<nav class="bottom-nav"><a class="' . ($active === 'today' ? 'active' : '')
            . '" href="today.php">Today</a><a class="' . ($active === 'parties' ? 'active' : '')
            . '" href="parties.php">Parties</a><a href="logout.php">Logout</a></nav>';
    }
    echo '</main></body></html>';
}

function party_for_user(int $partyId, array $user): ?array
{
    $sql = 'SELECT p.*, u.name AS mr_name FROM parties p LEFT JOIN users u ON u.id = p.mr_id WHERE p.id = ?';
    if ($user['role'] !== 'admin') {
        $sql .= ' AND p.mr_id = ' . (int) $user['id'];
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$partyId]);
    return $stmt->fetch() ?: null;
}
