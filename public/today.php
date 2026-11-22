<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';
$user = require_login();
if ($user['role'] === 'owner') { redirect('owner/index.php'); }
if ($user['role'] === 'admin') { redirect('admin/index.php'); }
$stmt = db()->prepare('SELECT p.*, EXISTS(SELECT 1 FROM visits v WHERE v.party_id=p.id AND DATE(v.visited_at)=CURDATE()) AS visited_today FROM parties p WHERE p.active=1 AND p.mr_id=? AND p.visit_day=DAYNAME(CURDATE()) ORDER BY p.name');
$stmt->execute([$user['id']]); $parties = $stmt->fetchAll(); $performance=db()->prepare("SELECT (SELECT COUNT(*) FROM visits WHERE mr_id=? AND DATE(visited_at) BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND CURDATE()) visits,(SELECT COUNT(*) FROM visits WHERE mr_id=? AND status='converted' AND DATE(visited_at) BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND CURDATE()) converted,(SELECT COALESCE(SUM(amount),0) FROM orders WHERE mr_id=? AND order_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND CURDATE()) sales,(SELECT COUNT(*) FROM orders WHERE mr_id=? AND order_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND CURDATE()) orders");$performance->execute([$user['id'],$user['id'],$user['id'],$user['id']]);$stats=$performance->fetch(); page_start("Today's visits", $user, 'today');
?>
<p class="muted">Your parties scheduled for <?= e(date('l')) ?>.</p>
<section class="card"><strong>My performance — this month</strong><p>₹<?=number_format((float)$stats['sales'],2)?> sales · <?=e((string)$stats['orders'])?> orders · <?=e((string)$stats['visits'])?> visits · <?=e((string)$stats['converted'])?> converted</p></section>
<?php foreach ($parties as $party): ?><a class="card party-card" href="party.php?id=<?= $party['id'] ?>"><div class="row"><strong><?= e($party['name']) ?></strong><span class="badge <?= $party['visited_today']?'green':'orange' ?>"><?= $party['visited_today']?'Visited today':'Pending' ?></span></div><span><?= e(ucfirst($party['type'])) ?> · <?= e($party['area']) ?></span></a><?php endforeach; ?>
<?php if (!$parties): ?><p class="card muted">No visits are due today.</p><?php endif; page_end($user,'today'); ?>
