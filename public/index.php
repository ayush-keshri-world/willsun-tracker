<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';

start_session();
if (current_user()) {
    redirect(current_user()['role'] === 'admin' ? 'admin/index.php' : 'today.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM users WHERE mobile = ? AND active = 1 LIMIT 1');
    $stmt->execute([$mobile]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        login($user);
        redirect($user['role'] === 'admin' ? 'admin/index.php' : 'today.php');
    }
    $error = 'Invalid mobile number, password, or inactive account.';
}
page_start('MR Visit Tracker', null);
?>
<form method="post" class="card login-form">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <label>Mobile number<input name="mobile" type="tel" inputmode="numeric" pattern="[0-9]{10}" required></label>
  <label>Password<input name="password" type="password" required></label>
  <button type="submit">Log in</button>
  <p class="center-links"><a href="register.php">New registration</a> · <a href="forgot_password.php">Forgot password?</a></p>
</form>
<?php page_end(null); ?>
