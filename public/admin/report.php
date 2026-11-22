<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/helpers.php';

$user = require_admin();
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$mrId = (int) ($_GET['mr_id'] ?? 0);
$fromDate = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
$toDate = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
if (!$fromDate || !$toDate || $from > $to) {
    $from = date('Y-m-01');
    $to = date('Y-m-d');
}

$mrs = db()->query("SELECT id,name FROM users WHERE role='employee' ORDER BY name")->fetchAll();
$filter = $mrId ? ' AND u.id = ?' : '';
$filterValues = $mrId ? [$mrId] : [];

$mrSql = "SELECT u.id,u.name,u.mobile,u.active,
    (SELECT COUNT(*) FROM visits v WHERE v.mr_id=u.id AND DATE(v.visited_at) BETWEEN ? AND ?) visit_count,
    (SELECT COUNT(*) FROM visits v WHERE v.mr_id=u.id AND v.status='converted' AND DATE(v.visited_at) BETWEEN ? AND ?) converted_count,
    (SELECT COALESCE(SUM(o.amount),0) FROM orders o WHERE o.mr_id=u.id AND o.order_date BETWEEN ? AND ?) gross_sales
    FROM users u WHERE u.role='employee'" . $filter . ' ORDER BY u.name';
$mrStmt = db()->prepare($mrSql);
$mrStmt->execute(array_merge([$from, $to, $from, $to, $from, $to], $filterValues));
$mrRows = $mrStmt->fetchAll();

$partySql = "SELECT p.id,p.name,p.type,p.area,u.name mr_name,
    COUNT(o.id) order_count, COALESCE(SUM(o.amount),0) gross_sales,
    (SELECT COUNT(*) FROM visits v WHERE v.party_id=p.id AND DATE(v.visited_at) BETWEEN ? AND ?) visit_count
    FROM parties p JOIN users u ON u.id=p.mr_id
    LEFT JOIN orders o ON o.party_id=p.id AND o.order_date BETWEEN ? AND ?
    WHERE u.role='employee'" . ($mrId ? ' AND p.mr_id=?' : '') . "
    GROUP BY p.id,p.name,p.type,p.area,u.name
    ORDER BY gross_sales DESC,p.name";
$partyStmt = db()->prepare($partySql);
$partyStmt->execute($mrId ? [$from, $to, $from, $to, $mrId] : [$from, $to, $from, $to]);
$parties = $partyStmt->fetchAll();

$visitSql = "SELECT v.*,p.name party_name,p.area,u.name mr_name
    FROM visits v JOIN parties p ON p.id=v.party_id JOIN users u ON u.id=v.mr_id
    WHERE DATE(v.visited_at) BETWEEN ? AND ?" . ($mrId ? ' AND v.mr_id=?' : '') . ' ORDER BY v.visited_at DESC LIMIT 100';
$visitStmt = db()->prepare($visitSql);
$visitStmt->execute($mrId ? [$from, $to, $mrId] : [$from, $to]);
$visits = $visitStmt->fetchAll();
$grossSales = array_sum(array_map(static fn(array $mr): float => (float) $mr['gross_sales'], $mrRows));
$visitCount = array_sum(array_map(static fn(array $mr): int => (int) $mr['visit_count'], $mrRows));

function map_url(float $latitude, float $longitude): string {
    return 'https://www.google.com/maps?q=' . rawurlencode($latitude . ',' . $longitude);
}

page_start('Reports', $user);
?>
<form method="get" class="card"><label>From<input type="date" name="from" value="<?= e($from) ?>"></label><label>To<input type="date" name="to" value="<?= e($to) ?>"></label><label>MR<select name="mr_id"><option value="">All MRs</option><?php foreach ($mrs as $mr): ?><option value="<?= $mr['id'] ?>" <?= $mrId === (int) $mr['id'] ? 'selected' : '' ?>><?= e($mr['name']) ?></option><?php endforeach; ?></select></label><button>Show report</button></form>

<section class="card"><strong>₹<?= number_format($grossSales, 2) ?> gross sales</strong><p><?= $visitCount ?> visits · <?= count($parties) ?> parties · <?= count($mrRows) ?> MRs</p></section>

<h2>MR sales and visits</h2>
<div class="card"><table><thead><tr><th>MR</th><th>Visits</th><th>Converted</th><th>Gross sales</th></tr></thead><tbody><?php foreach ($mrRows as $mr): ?><tr><td><?= e($mr['name']) ?><br><span class="small muted"><?= e($mr['mobile']) ?></span></td><td><?= e((string) $mr['visit_count']) ?></td><td><?= e((string) $mr['converted_count']) ?></td><td>₹<?= number_format((float) $mr['gross_sales'], 2) ?></td></tr><?php endforeach; ?></tbody></table></div>

<h2>Party recurring sales</h2>
<?php foreach ($parties as $party): ?><article class="card small"><div class="row"><strong><?= e($party['name']) ?></strong><strong>₹<?= number_format((float) $party['gross_sales'], 2) ?></strong></div><p><?= e($party['mr_name']) ?> · <?= e(ucfirst($party['type'])) ?> · <?= e($party['area']) ?></p><span><?= e((string) $party['order_count']) ?> orders · <?= e((string) $party['visit_count']) ?> visits</span></article><?php endforeach; ?><?php if (!$parties): ?><p class="muted">No parties found for this period.</p><?php endif; ?>

<h2>Visit proof and locations</h2>
<?php foreach ($visits as $visit): ?><article class="card small"><div class="row"><strong><?= e($visit['party_name']) ?></strong><span class="badge <?= $visit['status'] === 'converted' ? 'green' : 'grey' ?>"><?= e(str_replace('_', ' ', $visit['status'])) ?></span></div><p><?= e($visit['mr_name']) ?> · <?= e(date('d M Y, g:i a', strtotime($visit['visited_at']))) ?></p><p><?= e($visit['remark']) ?: 'No remark' ?></p><p><a href="<?= e(map_url((float) $visit['latitude'], (float) $visit['longitude'])) ?>" target="_blank" rel="noopener">View captured location</a> · <?= e($visit['location_tag']) ?><?= $visit['distance_m'] !== null ? ' · ' . e(number_format((float) $visit['distance_m'])) . ' m from party' : '' ?></p><?php if ($visit['photo_path'] && strtotime($visit['photo_expires_at']) > time()): ?><a href="../photo.php?id=<?= $visit['id'] ?>">View visit photo</a><?php else: ?><span class="muted">Photo expired</span><?php endif; ?></article><?php endforeach; ?><?php if (!$visits): ?><p class="muted">No visits found for this period.</p><?php endif; ?>
<?php page_end($user); ?>
