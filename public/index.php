<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';

start_session();
if (current_user()) {
    redirect(in_array(current_user()['role'], ['owner', 'admin'], true) ? 'admin/index.php' : 'today.php');
}
$error = '';
$portalRole = $_GET['role'] ?? ($_POST['portal_role'] ?? '');
if (!in_array($portalRole, ['employee', 'admin'], true)) {
    $portalRole = '';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM users WHERE mobile = ? AND active = 1 LIMIT 1');
    $stmt->execute([$mobile]);
    $user = $stmt->fetch();
    $hasCorrectRole = $user && ($portalRole === 'employee'
        ? $user['role'] === 'employee'
        : in_array($user['role'], ['owner', 'admin'], true));
    if ($hasCorrectRole && password_verify($password, $user['password_hash'])) {
        login($user);
        redirect(in_array($user['role'], ['owner', 'admin'], true) ? 'admin/index.php' : 'today.php');
    }
    $error = $portalRole === 'admin'
        ? 'This account does not have admin access, or the login details are incorrect.'
        : 'Invalid mobile number, password, or inactive account.';
}
page_start($portalRole === '' ? 'Willsun ERP' : ($portalRole === 'admin' ? 'Admin login' : 'Employee login'), null);
?>
<?php if ($portalRole === ''): ?>
<section class="card role-choice">
  <p class="muted">Select how you want to sign in.</p>
  <a class="button" href="index.php?role=employee">Login as Employee</a>
  <a class="button secondary" href="index.php?role=admin">Login as Admin</a>
  <p class="center-links"><a href="register.php">New employee registration</a></p>
</section>
<?php else: ?>
<form method="post" class="card login-form">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="portal_role" value="<?= e($portalRole) ?>">
  <p class="login-role-label"><?= $portalRole === 'admin' ? 'Administrator / Owner login' : 'Employee login' ?></p>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <label>Mobile number<input name="mobile" type="tel" inputmode="numeric" pattern="[0-9]{10}" required></label>
  <label>Password<input name="password" type="password" required></label>
  <button type="submit">Log in</button>
  <p class="center-links">
    <?php if ($portalRole === 'employee'): ?><a href="register.php">New registration</a> · <?php endif; ?><a href="forgot_password.php">Forgot password?</a><br>
    <a href="index.php">← Change login type</a>
  </p>
</form>
<?php endif; ?>
<?php page_end(null); ?>
