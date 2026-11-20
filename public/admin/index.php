<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/helpers.php';
$user=require_admin(); $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action=$_POST['action']??''; $userId=(int)($_POST['user_id']??0);
    if ($action==='add') { $name=trim($_POST['name']??''); $mobile=trim($_POST['mobile']??''); $password=$_POST['password']??''; if ($name===''||!is_valid_mobile($mobile)||strlen($password)<8) { $error='Enter a name, 10-digit mobile number, and a password of at least 8 characters.'; } else { try { db()->prepare("INSERT INTO users (name,mobile,password_hash,role) VALUES (?,?,?,'employee')")->execute([$name,$mobile,password_hash($password,PASSWORD_DEFAULT)]); } catch (PDOException) { $error='That mobile number is already in use.'; } } }
    elseif ($action==='toggle' && $userId) { db()->prepare("UPDATE users SET active=1-active WHERE id=? AND role='employee'")->execute([$userId]); }
    elseif ($action==='reset' && $userId) { $password=$_POST['password']??''; if (strlen($password)<8) { $error='The new password must be at least 8 characters.'; } else { db()->prepare("UPDATE users SET password_hash=? WHERE id=? AND role='employee'")->execute([password_hash($password,PASSWORD_DEFAULT),$userId]); } }
    if ($error==='') { redirect('index.php'); }
}
$mrs=db()->query("SELECT id,name,mobile,active,created_at FROM users WHERE role='employee' ORDER BY name")->fetchAll(); page_start('MRs',$user);
?>
<form method="post" class="card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add"><h2>Add MR</h2><?php if($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?><label>Name<input name="name" required></label><label>Mobile number<input name="mobile" type="tel" inputmode="numeric" pattern="[0-9]{10}" required></label><label>Password<input name="password" type="password" minlength="8" required></label><button>Add MR</button></form>
<h2>MR accounts</h2><?php foreach($mrs as $mr): ?><article class="card"><strong><?= e($mr['name']) ?></strong><p><?= e($mr['mobile']) ?> · <?= $mr['active']?'Active':'Inactive' ?></p><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="user_id" value="<?= $mr['id'] ?>"><button class="secondary"><?= $mr['active']?'Deactivate':'Activate' ?></button></form><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="reset"><input type="hidden" name="user_id" value="<?= $mr['id'] ?>"><label>Reset password<input name="password" type="password" minlength="8" required></label><button>Save new password</button></form></article><?php endforeach; ?>
<?php page_end($user); ?>
