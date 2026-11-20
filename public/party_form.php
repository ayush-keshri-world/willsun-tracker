<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';
$user = require_login();
$partyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$party = $partyId ? party_for_user($partyId, $user) : null;
if ($partyId && !$party) { http_response_code(404); exit('Party not found.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = ['type' => $_POST['type'] ?? '', 'name' => trim($_POST['name'] ?? ''), 'designation' => trim($_POST['designation'] ?? ''), 'clinic_name' => trim($_POST['clinic_name'] ?? ''), 'area' => trim($_POST['area'] ?? ''), 'pincode' => trim($_POST['pincode'] ?? ''), 'mobile' => trim($_POST['mobile'] ?? ''), 'visit_day' => $_POST['visit_day'] ?? ''];
    $mrId = $user['role'] === 'admin' ? (int) ($_POST['mr_id'] ?? 0) : (int) $user['id'];
    if (!in_array($data['type'], ['doctor', 'retail'], true) || $data['name'] === '' || $data['area'] === '' || !is_valid_pincode($data['pincode']) || !is_valid_mobile($data['mobile']) || !in_array($data['visit_day'], visit_day_options(), true) || $mrId < 1) {
        $error = 'Complete required fields with a valid mobile number and six-digit pincode.'; $party = array_merge($party ?? [], $data, ['mr_id' => $mrId]);
    } else {
        if ($user['role'] === 'admin') { $check = db()->prepare("SELECT id FROM users WHERE id=? AND role='employee' AND active=1"); $check->execute([$mrId]); if (!$check->fetch()) { $error = 'Choose an active MR.'; } }
        if ($error === '') {
            if ($partyId) { $stmt = db()->prepare('UPDATE parties SET type=?,name=?,designation=?,clinic_name=?,area=?,pincode=?,mobile=?,visit_day=?,mr_id=? WHERE id=?'); $stmt->execute([...array_values($data), $mrId, $partyId]); }
            else { $stmt = db()->prepare('INSERT INTO parties (type,name,designation,clinic_name,area,pincode,mobile,visit_day,mr_id) VALUES (?,?,?,?,?,?,?,?,?)'); $stmt->execute([...array_values($data), $mrId]); $partyId = (int) db()->lastInsertId(); }
            redirect('party.php?id=' . $partyId);
        }
    }
}
$mrs = $user['role'] === 'admin' ? db()->query("SELECT id,name FROM users WHERE role='employee' AND active=1 ORDER BY name")->fetchAll() : [];
$party ??= ['type'=>'doctor','name'=>'','designation'=>'','clinic_name'=>'','area'=>'','pincode'=>'','mobile'=>'','visit_day'=>'Monday','mr_id'=>$user['id']];
page_start($partyId ? 'Edit party' : 'Add party', $user, 'parties');
?>
<form method="post" class="card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<label>Type<select name="type"><option value="doctor" <?= $party['type']==='doctor'?'selected':'' ?>>Doctor</option><option value="retail" <?= $party['type']==='retail'?'selected':'' ?>>Retail Party</option></select></label>
<label>Name<input name="name" required value="<?= e($party['name']) ?>"></label><label>Designation<input name="designation" value="<?= e($party['designation']) ?>" placeholder="MBBS, Owner, Pharmacist"></label><label>Clinic / Shop name<input name="clinic_name" value="<?= e($party['clinic_name']) ?>"></label><label>Area<input name="area" required value="<?= e($party['area']) ?>"></label><label>Pincode<input name="pincode" type="tel" inputmode="numeric" pattern="[0-9]{6}" required value="<?= e($party['pincode']) ?>"></label><label>Mobile number<input name="mobile" type="tel" inputmode="numeric" pattern="[0-9]{10}" required value="<?= e($party['mobile']) ?>"></label><label>Visit day<select name="visit_day"><?php foreach (visit_day_options() as $day): ?><option <?= $party['visit_day']===$day?'selected':'' ?>><?= e($day) ?></option><?php endforeach; ?></select></label>
<?php if ($user['role']==='admin'): ?><label>Assigned MR<select name="mr_id" required><option value="">Choose MR</option><?php foreach ($mrs as $mr): ?><option value="<?= $mr['id'] ?>" <?= (int)$party['mr_id']===(int)$mr['id']?'selected':'' ?>><?= e($mr['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?><button type="submit">Save party</button></form>
<?php page_end($user, 'parties'); ?>
