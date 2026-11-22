<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';
$user = require_login(); $search = trim($_GET['search'] ?? '');
$sql = 'SELECT p.*, EXISTS(SELECT 1 FROM visits v WHERE v.party_id=p.id AND DATE(v.visited_at)=CURDATE()) AS visited_today FROM parties p WHERE p.active=1'; $values=[];
if ($user['role'] === 'employee') { $sql.=' AND p.mr_id=?'; $values[]=$user['id']; }
if ($search !== '') { $compact = '%' . str_replace([' ', '-'], '', strtolower($search)) . '%'; $mobile = preg_replace('/\D+/', '', $search); $sql.=" AND (REPLACE(REPLACE(LOWER(p.name),' ',''),'-','') LIKE ? OR REPLACE(REPLACE(LOWER(p.area),' ',''),'-','') LIKE ? OR p.mobile LIKE ?)"; $values[]=$compact; $values[]=$compact; $values[]=$mobile === '' ? '%__no_mobile_match__%' : '%' . $mobile . '%'; }
$stmt=db()->prepare($sql.' ORDER BY p.name'); $stmt->execute($values); $parties=$stmt->fetchAll(); page_start('My parties',$user,'parties');
?>
<a class="button" href="party_form.php">Add party</a><form method="get" class="card"><label>Search parties<input name="search" value="<?= e($search) ?>" placeholder="Any part of name, area, or mobile"></label><button type="submit">Search</button></form>
<?php foreach ($parties as $party): ?><a class="card party-card" href="party.php?id=<?= $party['id'] ?>"><div class="row"><strong><?= e($party['name']) ?></strong><span class="badge <?= $party['visited_today']?'green':'orange' ?>"><?= $party['visited_today']?'Visited today':'Pending' ?></span></div><span><?= e(ucfirst($party['type'])) ?> · <?= e($party['area']) ?></span></a><?php endforeach; ?>
<?php if (!$parties): ?><p class="muted">No parties found.</p><?php endif; page_end($user,'parties'); ?>
