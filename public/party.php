<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';
$user = require_login(); $partyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0; $party = party_for_user($partyId, $user);
if (!$party) { http_response_code(404); exit('Party not found.'); }
$visitStmt = db()->prepare('SELECT * FROM visits WHERE party_id=? ORDER BY visited_at DESC'); $visitStmt->execute([$partyId]); $visits=$visitStmt->fetchAll();
$orderStmt = db()->prepare('SELECT * FROM orders WHERE party_id=? ORDER BY order_date DESC, id DESC'); $orderStmt->execute([$partyId]); $orders=$orderStmt->fetchAll();
$totalSales = array_sum(array_map(static fn(array $order): float => (float) $order['amount'], $orders));
page_start($party['name'], $user, 'parties');
?>
<section class="card"><p><strong><?= e(ucfirst($party['type'])) ?></strong> · <?= e($party['area']) ?></p><p><?= e($party['designation']) ?><?= $party['clinic_name'] ? ' · '.e($party['clinic_name']) : '' ?></p><p><?= e($party['mobile']) ?> · <?= e($party['pincode']) ?></p><p>Total visits: <strong><?= count($visits) ?></strong><?= $visits ? ' · Last visit: '.e(date('d M Y', strtotime($visits[0]['visited_at']))) : '' ?></p></section>
<?php if ($user['role']==='employee'): ?><a class="button" href="checkin.php?party_id=<?= $partyId ?>">Check-in</a><a class="button secondary" href="order_form.php?party_id=<?= $partyId ?>">Add order</a><?php endif; ?><a class="edit-link" href="party_form.php?id=<?= $partyId ?>">Edit party</a>
<h2>Visit history</h2><?php foreach ($visits as $visit): ?><article class="card small"><div class="row"><strong><?= e(date('d M Y, g:i a', strtotime($visit['visited_at']))) ?></strong><span class="badge <?= $visit['status']==='converted'?'green':'grey' ?>"><?= $visit['status']==='converted'?'Converted':'Not converted' ?></span></div><p><?= e($visit['remark']) ?: 'No remark' ?></p><span class="badge <?= $visit['location_tag']==='far'?'red':($visit['location_tag']==='near'?'blue':'orange') ?>"><?= e(ucfirst($visit['location_tag'])) ?></span><?php if ($visit['photo_path'] && strtotime($visit['photo_expires_at']) > time()): ?><p><a href="photo.php?id=<?= $visit['id'] ?>">View photo</a></p><?php else: ?><p class="muted">Photo expired</p><?php endif; ?></article><?php endforeach; ?><?php if (!$visits): ?><p class="muted">No visits recorded.</p><?php endif; ?>
<h2>Orders · ₹<?= number_format($totalSales, 2) ?></h2><?php foreach ($orders as $order): ?><article class="card small"><strong><?= e($order['product']) ?></strong><br><?= e($order['order_date']) ?> · Qty <?= e((string)$order['quantity']) ?> · ₹<?= number_format((float)$order['amount'],2) ?></article><?php endforeach; ?><?php if (!$orders): ?><p class="muted">No orders recorded.</p><?php endif; ?>
<?php page_end($user,'parties'); ?>
