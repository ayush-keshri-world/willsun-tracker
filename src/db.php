<?php

declare(strict_types=1);

/** Creates one reusable PDO connection. */
function db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $configFile = dirname(__DIR__) . '/config.php';
    if (!is_file($configFile)) {
        http_response_code(500);
        exit('Server setup is incomplete. Create config.php from config.example.php.');
    }
    $config = require $configFile;
    $dsn = 'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name']
        . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
