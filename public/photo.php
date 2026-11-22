<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';
$user = require_login(); $visitId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$sql = 'SELECT v.photo_path FROM visits v JOIN parties p ON p.id=v.party_id WHERE v.id=? AND v.photo_path IS NOT NULL AND (v.photo_expires_at IS NULL OR v.photo_expires_at > NOW())'; $values = [$visitId];
if (!in_array($user['role'], ['owner', 'admin'], true)) { $sql .= ' AND p.mr_id=?'; $values[] = $user['id']; }
$stmt=db()->prepare($sql); $stmt->execute($values); $visit=$stmt->fetch();
if (!$visit) { http_response_code(404); exit('Photo expired or unavailable.'); }
$file = dirname(__DIR__) . '/storage/photos/' . basename($visit['photo_path']);
if (!is_file($file)) { http_response_code(404); exit('Photo expired or unavailable.'); }
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file); if (!in_array($mime,['image/jpeg','image/png'],true)) { http_response_code(404); exit('Photo unavailable.'); }
header('Content-Type: '.$mime); header('Content-Length: '.filesize($file)); header('X-Content-Type-Options: nosniff'); readfile($file);
