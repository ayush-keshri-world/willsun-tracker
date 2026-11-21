<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/helpers.php';
$user = require_owner(); $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); $name=trim($_POST['name']??'');$mobile=trim($_POST['mobile']??'');$password=$_POST['password']??'';
    if($name===''||strlen($name)>100||!is_valid_mobile($mobile)||strlen($password)<8){$error='Enter a name, 10-digit mobile number, and password of at least 8 characters.';}
    else {try {db()->prepare("INSERT INTO users (name,mobile,password_hash,role) VALUES (?,?,?,'admin')")->execute([$name,$mobile,password_hash($password,PASSWORD_DEFAULT)]);redirect('admins.php');} catch(PDOException){$error='That mobile number is already registered.';}}
}
$admins=db()->query("SELECT name,mobile,active,created_at FROM users WHERE role IN ('owner','admin') ORDER BY role DESC,name")->fetchAll(); page_start('Admin accounts',$user);
?>
<form method="post" class="card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><h2>Add admin</h2><?php if($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?><label>Name<input name="name" maxlength="100" required></label><label>Mobile number<input name="mobile" type="tel" inputmode="numeric" pattern="[0-9]{10}" required></label><label>Temporary password<input name="password" type="password" minlength="8" required></label><button>Add admin</button></form>
<h2>Access list</h2><?php foreach($admins as $account): ?><article class="card"><strong><?= e($account['name']) ?></strong><p><?= e($account['mobile']) ?> · <?= e($account['active']?'Active':'Inactive') ?></p></article><?php endforeach; ?>
<?php page_end($user); ?>
