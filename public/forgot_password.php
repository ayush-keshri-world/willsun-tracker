<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $userQuery = db()->prepare('SELECT id, name, email FROM users WHERE email = ? AND active = 1 LIMIT 1');
        $userQuery->execute([$email]);
        $account = $userQuery->fetch();
        if ($account) {
            $recent = db()->prepare('SELECT id FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 10 MINUTE) LIMIT 1');
            $recent->execute([$account['id']]);
            if (!$recent->fetch()) {
                $token = bin2hex(random_bytes(32));
                db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')
                    ->execute([$account['id'], hash('sha256', $token)]);
                send_password_reset_email($account['email'], $account['name'], $token);
            }
        }
    }
    $notice = 'If that email address is registered, a password-reset link has been sent. Check spam as well.';
}
page_start('Forgot password', null);
?>
<form method="post" class="card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<p>Enter the email address registered to your account. We will send a one-time reset link.</p>
<?php if ($notice): ?><p class="success"><?= e($notice) ?></p><?php endif; ?>
<label>Email address<input name="email" type="email" maxlength="255" required></label>
<button>Send reset link</button>
<p class="center-links"><a href="index.php">Back to login</a></p></form>
<?php page_end(null); ?>
