<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/helpers.php';
$user = require_owner(); $mrId = (int) ($_GET['id'] ?? 0);
$from = $_GET['from'] ?? date('Y-m-01'); $to = $_GET['to'] ?? date('Y-m-d');
if (!$mrId) { redirect('index.php'); }
$mrStmt = db()->prepare("SELECT id,name,mobile,active,created_at FROM users WHERE id=? AND role='employee'"); $mrStmt->execute([$mrId]); $mr = $mrStmt->fetch();
if (!$mr) { http_response_code(404); exit('MR not found.'); }
$visitStmt = db()->prepare("SELECT v.*, p.name party_name, p.area FROM visits v JOIN parties p ON p.id=v.party_id WHERE v.mr_id=? AND DATE(v.visited_at) BETWEEN ? AND ? ORDER BY v.visited_at DESC"); $visitStmt->execute([$mrId,$from,$to]); $visits=$visitStmt->fetchAll();
$orderStmt = db()->prepare("SELECT o.*,p.name party_name FROM orders o JOIN parties p ON p.id=o.party_id WHERE o.mr_id=? AND o.order_date BETWEEN ? AND ? ORDER BY o.order_date DESC,o.id DESC"); $orderStmt->execute([$mrId,$from,$to]); $orders=$orderStmt->fetchAll();
$partyStmt=db()->prepare('SELECT id,name,type,area,active FROM parties WHERE mr_id=? ORDER BY active DESC,name'); $partyStmt->execute([$mrId]); $parties=$partyStmt->fetchAll();
page_start($mr['name'], $user);
?>
<a class="edit-link" href="index.php?from=<?= e($from) ?>&to=<?= e($to) ?>">← Dashboard</a><section class="card"><strong><?= e($mr['name']) ?></strong><p><?= e($mr['mobile']) ?> · <?= $mr['active'] ? 'Active' : 'Inactive' ?></p><p><?= count($parties) ?> assigned parties · <?= count($visits) ?> visits in selected period · ₹<?= number_format(array_sum(array_map(fn($order)=>(float)$order['amount'],$orders)),2) ?> sales</p></section>
<h2>Visits</h2><?php foreach($visits as $visit): ?><article class="card small"><strong><?= e($visit['party_name']) ?></strong> · <?= e($visit['area']) ?><br><?= e(date('d M Y, g:i a',strtotime($visit['visited_at']))) ?> · <span class="badge <?= $visit['status']==='converted'?'green':'grey' ?>"><?= e(str_replace('_',' ',$visit['status'])) ?></span><p><?= e($visit['remark']) ?: 'No remark' ?></p></article><?php endforeach; ?><?php if(!$visits): ?><p class="muted">No visits in this period.</p><?php endif; ?>
<h2>Orders</h2><?php foreach($orders as $order): ?><article class="card small"><strong><?= e($order['party_name']) ?></strong><br><?= e($order['product']) ?> · Qty <?= e((string)$order['quantity']) ?> · ₹<?= number_format((float)$order['amount'],2) ?></article><?php endforeach; ?><?php if(!$orders): ?><p class="muted">No orders in this period.</p><?php endif; ?>
<h2>Assigned parties</h2><?php foreach($parties as $party): ?><article class="card small"><strong><?= e($party['name']) ?></strong> · <?= e(ucfirst($party['type'])) ?><br><?= e($party['area']) ?> · <?= $party['active'] ? 'Active' : 'Inactive' ?></article><?php endforeach; ?>
<?php page_end($user); ?>
