<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';

if (current_user()) {
    redirect(current_user()['role'] === 'admin' ? 'admin/index.php' : 'today.php');
}
$error = '';
$name = '';
$mobile = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    if ($name === '' || strlen($name) > 100 || !is_valid_mobile($mobile) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        $error = 'Enter your name, email address, 10-digit mobile number, and a password of at least 8 characters.';
    }
    else {
        try {
            $statement = db()->prepare("INSERT INTO users (name, mobile, email, password_hash, role) VALUES (?, ?, ?, ?, 'employee')");
            $statement->execute([$name, $mobile, $email, password_hash($password, PASSWORD_DEFAULT)]);
            login(['id' => (int) db()->lastInsertId(), 'name' => $name, 'role' => 'employee']);
            redirect('today.php');
        }
        catch (PDOException) {
            $error = 'That mobile number is already registered. Please log in or ask an admin to reset your password.';
        }
    }
}
page_start('New registration', null);
?>
<form method="post" class="card login-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<label>Your name<input name="name" maxlength="100" required value="<?= e($name) ?>"></label>
<label>Email address<input name="email" type="email" maxlength="255" required value="<?= e($email) ?>"></label>
<label>Mobile number<input name="mobile" type="tel" inputmode="numeric" pattern="[0-9]{10}" required value="<?= e($mobile) ?>"></label>
<label>Create password<input name="password" type="password" minlength="8" required></label>
<button type="submit">Create account</button>
<p class="center-links"><a href="index.php">Back to login</a></p>
</form>
<?php page_end(null); ?>
