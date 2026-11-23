<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';
$user = require_login();
if ($user['role'] !== 'employee') { http_response_code(403); exit('Only MRs can check in.'); }
$partyId = filter_input(INPUT_GET, 'party_id', FILTER_VALIDATE_INT) ?: (int) ($_POST['party_id'] ?? 0);
$party = party_for_user($partyId, $user);
if (!$party) { http_response_code(404); exit('Party not found.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT); $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $status = $_POST['status'] ?? ''; $remark = trim($_POST['remark'] ?? ''); $upload = $_FILES['photo'] ?? null;
    if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180 || !in_array($status, ['converted','not_converted'], true) || strlen($remark) > 500) { $error = 'GPS, status, and a short remark are required in the expected format.'; }
    elseif (!$upload || $upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024) { $error = 'Take a JPEG or PNG photo smaller than 5 MB.'; }
    else {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        $extension = $mime === 'image/jpeg' ? 'jpg' : ($mime === 'image/png' ? 'png' : '');
        if ($extension === '') { $error = 'The photo must be a JPEG or PNG image.'; }
        else {
            $name = bin2hex(random_bytes(20)) . '.' . $extension; $photoPath = dirname(__DIR__) . '/storage/photos/' . $name;
            if (!move_uploaded_file($upload['tmp_name'], $photoPath)) { $error = 'Photo upload failed. Please try again.'; }
            else {
                $distance = null; $tag = 'first';
                if ($party['latitude'] !== null && $party['longitude'] !== null) {
                    $earth = 6371000; $lat1 = deg2rad((float)$party['latitude']); $lat2 = deg2rad((float)$latitude); $dLat = $lat2-$lat1; $dLng = deg2rad((float)$longitude) - deg2rad((float)$party['longitude']);
                    $a = sin($dLat/2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng/2) ** 2; $a = min(1, max(0, $a)); $distance = $earth * 2 * atan2(sqrt($a), sqrt(1-$a)); $tag = $distance <= 200 ? 'near' : 'far';
                }
                $pdo = db(); $pdo->beginTransaction();
                try {
                    if ($party['latitude'] === null || $party['longitude'] === null) { $pdo->prepare('UPDATE parties SET latitude=?, longitude=? WHERE id=? AND latitude IS NULL AND longitude IS NULL')->execute([$latitude, $longitude, $partyId]); }
                    $stmt = $pdo->prepare('INSERT INTO visits (party_id,mr_id,latitude,longitude,distance_m,location_tag,status,remark,photo_path,photo_expires_at) VALUES (?,?,?,?,?,?,?,?,?,DATE_ADD(NOW(), INTERVAL 30 DAY))');
                    $stmt->execute([$partyId, $user['id'], $latitude, $longitude, $distance, $tag, $status, $remark, $name]); $pdo->commit(); redirect('party.php?id=' . $partyId);
                } catch (Throwable $exception) { $pdo->rollBack(); @unlink($photoPath); throw $exception; }
            }
        }
    }
}
page_start('Check-in: '.$party['name'], $user, 'parties');
?>
<p class="notice">Your location and photo are captured at check-in.</p><form id="checkin-form" method="post" enctype="multipart/form-data" class="card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="party_id" value="<?= $partyId ?>"><input id="latitude" name="latitude" type="hidden"><input id="longitude" name="longitude" type="hidden">
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?><label>Camera photo<input id="photo" name="photo" type="file" accept="image/*" capture="environment" required></label><p id="location-message" class="muted">Requesting GPS location…</p><label>Status<select name="status" required><option value="converted">Converted</option><option value="not_converted">Not converted</option></select></label><label>Remark (optional)<textarea name="remark" maxlength="500"></textarea></label><p id="upload-message" class="muted" hidden>Resizing and uploading photo…</p><button id="save-button" type="submit" disabled>Save check-in</button></form><script src="assets/app.js"></script>
<?php page_end($user,'parties'); ?>
