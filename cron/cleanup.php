<?php

declare(strict_types=1);

// Run hourly from cPanel. It removes only photo files; visits remain intact.
require_once dirname(__DIR__) . '/src/db.php';

$stmt = db()->query('SELECT id, photo_path FROM visits WHERE photo_path IS NOT NULL AND photo_expires_at <= NOW()');
$expired = $stmt->fetchAll();
$clear = db()->prepare('UPDATE visits SET photo_path=NULL WHERE id=?');
$deleted = 0;
foreach ($expired as $visit) {
    $file = dirname(__DIR__) . '/storage/photos/' . basename($visit['photo_path']);
    if (is_file($file) && @unlink($file)) {
        $deleted++;
    }
    // Clear the path even if a previous manual cleanup already removed the file.
    $clear->execute([$visit['id']]);
}
echo 'Expired visit photos processed: ' . count($expired) . '; files deleted: ' . $deleted . PHP_EOL;
