<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';
$user = require_login();
if ($user['role'] === 'admin') { redirect('admin/index.php'); }
$stmt = db()->prepare('SELECT p.*, EXISTS(SELECT 1 FROM visits v WHERE v.party_id=p.id AND DATE(v.visited_at)=CURDATE()) AS visited_today FROM parties p WHERE p.active=1 AND p.mr_id=? AND p.visit_day=DAYNAME(CURDATE()) ORDER BY p.name');
$stmt->execute([$user['id']]); $parties = $stmt->fetchAll(); page_start("Today's visits", $user, 'today');
?>
<p class="muted">Your parties scheduled for <?= e(date('l')) ?>.</p>
<?php foreach ($parties as $party): ?><a class="card party-card" href="party.php?id=<?= $party['id'] ?>"><div class="row"><strong><?= e($party['name']) ?></strong><span class="badge <?= $party['visited_today']?'green':'orange' ?>"><?= $party['visited_today']?'Visited today':'Pending' ?></span></div><span><?= e(ucfirst($party['type'])) ?> · <?= e($party['area']) ?></span></a><?php endforeach; ?>
<?php if (!$parties): ?><p class="card muted">No visits are due today.</p><?php endif; page_end($user,'today'); ?>
