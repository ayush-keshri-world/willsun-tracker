<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/src/helpers.php'; $user=require_admin();
$from=$_GET['from']??date('Y-m-01');$to=$_GET['to']??date('Y-m-d');
$validFrom=DateTimeImmutable::createFromFormat('!Y-m-d',$from);$validTo=DateTimeImmutable::createFromFormat('!Y-m-d',$to);
if(!$validFrom||!$validTo||$from>$to){$from=date('Y-m-01');$to=date('Y-m-d');}
$sql="SELECT u.name, (SELECT COUNT(*) FROM visits v WHERE v.mr_id=u.id AND DATE(v.visited_at) BETWEEN ? AND ?) AS total_visits, (SELECT COUNT(*) FROM visits v WHERE v.mr_id=u.id AND v.status='converted' AND DATE(v.visited_at) BETWEEN ? AND ?) AS converted_count, (SELECT COALESCE(SUM(o.amount),0) FROM orders o WHERE o.mr_id=u.id AND o.order_date BETWEEN ? AND ?) AS total_sales FROM users u WHERE u.role='employee' ORDER BY u.name";
$stmt=db()->prepare($sql);$stmt->execute([$from,$to,$from,$to,$from,$to]);$rows=$stmt->fetchAll();page_start('Summary',$user);
?>
<form method="get" class="card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>From<input name="from" type="date" value="<?= e($from) ?>"></label><label>To<input name="to" type="date" value="<?= e($to) ?>"></label><button>Show summary</button></form><div class="card"><table><thead><tr><th>MR</th><th>Visits</th><th>Converted</th><th>Sales</th></tr></thead><tbody><?php foreach($rows as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e((string)$row['total_visits']) ?></td><td><?= e((string)$row['converted_count']) ?></td><td>₹<?= number_format((float)$row['total_sales'],2) ?></td></tr><?php endforeach;?></tbody></table></div>
<?php page_end($user); ?>
