<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = '';
$reset = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $lookup = db()->prepare('SELECT pr.id, pr.user_id FROM password_resets pr WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at >= NOW() LIMIT 1');
    $lookup->execute([hash('sha256', $token)]);
    $reset = $lookup->fetch();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    if (!$reset) {
        $error = 'This reset link is invalid or has expired. Request a new one.';
    } elseif (strlen($password) < 8) {
        $error = 'Your new password must be at least 8 characters.';
    } else {
        db()->beginTransaction();
        try {
            $markUsed = db()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ? AND used_at IS NULL');
            $markUsed->execute([$reset['id']]);
            if ($markUsed->rowCount() !== 1) {
                throw new RuntimeException('This reset link has already been used.');
            }
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
            db()->commit();
            $success = 'Password updated. You can now log in.';
        } catch (Throwable $exception) {
            db()->rollBack();
            throw $exception;
        }
    }
}
page_start('Reset password', null);
?>
<section class="card">
<?php if ($success): ?><p class="success"><?= e($success) ?></p><a class="button" href="index.php">Go to login</a>
<?php elseif (!$reset): ?><p class="error">This reset link is invalid or has expired.</p><a class="button" href="forgot_password.php">Request a new link</a>
<?php else: ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="token" value="<?= e($token) ?>">
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?><label>New password<input name="password" type="password" minlength="8" required></label><button>Set new password</button></form><?php endif; ?>
</section>
<?php page_end(null); ?>
