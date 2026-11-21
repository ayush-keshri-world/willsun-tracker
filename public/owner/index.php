<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/helpers.php';
$user = require_owner();
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$fromDate = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
$toDate = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
if (!$fromDate || !$toDate || $from > $to) { $from = date('Y-m-01'); $to = date('Y-m-d'); }
$stats = db()->prepare("SELECT
    (SELECT COUNT(*) FROM users WHERE role='employee' AND active=1) AS active_mrs,
    (SELECT COUNT(*) FROM visits WHERE DATE(visited_at) BETWEEN ? AND ?) AS visits,
    (SELECT COUNT(*) FROM visits WHERE status='converted' AND DATE(visited_at) BETWEEN ? AND ?) AS converted,
    (SELECT COALESCE(SUM(amount), 0) FROM orders WHERE order_date BETWEEN ? AND ?) AS sales");
$stats->execute([$from, $to, $from, $to, $from, $to]);
$totals = $stats->fetch();
$mrs = db()->prepare("SELECT u.id,u.name,u.mobile,u.active,
    (SELECT COUNT(*) FROM visits v WHERE v.mr_id=u.id AND DATE(v.visited_at) BETWEEN ? AND ?) visits,
    (SELECT COUNT(*) FROM visits v WHERE v.mr_id=u.id AND v.status='converted' AND DATE(v.visited_at) BETWEEN ? AND ?) converted,
    (SELECT COALESCE(SUM(o.amount),0) FROM orders o WHERE o.mr_id=u.id AND o.order_date BETWEEN ? AND ?) sales
    FROM users u WHERE u.role='employee' ORDER BY u.name");
$mrs->execute([$from, $to, $from, $to, $from, $to]);
page_start('Owner dashboard', $user);
?>
<form method="get" class="card"><label>From<input type="date" name="from" value="<?= e($from) ?>"></label><label>To<input type="date" name="to" value="<?= e($to) ?>"></label><button>Update dashboard</button></form>
<section class="card"><strong><?= e((string)$totals['active_mrs']) ?> active MRs</strong><p><?= e((string)$totals['visits']) ?> visits · <?= e((string)$totals['converted']) ?> converted · ₹<?= number_format((float)$totals['sales'], 2) ?> sales</p></section>
<h2>MR performance</h2>
<?php foreach ($mrs as $mr): ?><a class="card party-card" href="mrs.php?id=<?= $mr['id'] ?>&from=<?= e($from) ?>&to=<?= e($to) ?>"><div class="row"><strong><?= e($mr['name']) ?></strong><span class="badge <?= $mr['active'] ? 'green' : 'grey' ?>"><?= $mr['active'] ? 'Active' : 'Inactive' ?></span></div><span><?= e((string)$mr['visits']) ?> visits · <?= e((string)$mr['converted']) ?> converted · ₹<?= number_format((float)$mr['sales'], 2) ?></span></a><?php endforeach; ?>
<?php if (!$mrs): ?><p class="card muted">No MR accounts yet.</p><?php endif; ?>
<?php page_end($user); ?>
