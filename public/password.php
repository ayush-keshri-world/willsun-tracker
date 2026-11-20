<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';
$user = require_login();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $statement = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $statement->execute([$user['id']]);
    $account = $statement->fetch();
    if (!$account || !password_verify($currentPassword, $account['password_hash'])) {
        $error = 'Your current password is incorrect.';
    }
    elseif (strlen($newPassword) < 8) {
        $error = 'Your new password must be at least 8 characters.';
    }
    else {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
        $success = 'Password updated.';
    }
}
page_start('Change password', $user, 'password');
?>
<form method="post" class="card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= e($success) ?></p><?php endif; ?>
<label>Current password<input name="current_password" type="password" required></label>
<label>New password<input name="new_password" type="password" minlength="8" required></label>
<button type="submit">Update password</button>
</form>
<?php page_end($user, 'password'); ?>
