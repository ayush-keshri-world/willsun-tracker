<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/helpers.php';
$user = require_login();
if ($user['role'] !== 'employee') { http_response_code(403); exit('Only MRs can add orders.'); }
$partyId = filter_input(INPUT_GET, 'party_id', FILTER_VALIDATE_INT) ?: (int) ($_POST['party_id'] ?? 0); $party = party_for_user($partyId, $user);
if (!$party) { http_response_code(404); exit('Party not found.'); }
$values = ['order_date'=>date('Y-m-d'),'product'=>'','quantity'=>'','amount'=>'']; $error='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $values = ['order_date'=>$_POST['order_date'] ?? '', 'product'=>trim($_POST['product'] ?? ''), 'quantity'=>trim($_POST['quantity'] ?? ''), 'amount'=>trim($_POST['amount'] ?? '')];
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $values['order_date']); $quantity = filter_var($values['quantity'], FILTER_VALIDATE_INT); $amount = filter_var($values['amount'], FILTER_VALIDATE_FLOAT);
    if (!$date || $date->format('Y-m-d') !== $values['order_date'] || $values['product']==='' || strlen($values['product'])>150 || $quantity===false || $quantity<1 || $amount===false || $amount<0) { $error='Enter a date, product, a quantity of at least 1, and a non-negative amount.'; }
    else { $stmt=db()->prepare('INSERT INTO orders (party_id,mr_id,order_date,product,quantity,amount) VALUES (?,?,?,?,?,?)'); $stmt->execute([$partyId,$user['id'],$values['order_date'],$values['product'],$quantity,$amount]); redirect('party.php?id='.$partyId); }
}
page_start('Add order: '.$party['name'], $user);
?>
<form method="post" class="card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="party_id" value="<?= $partyId ?>"><?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?><label>Date<input type="date" name="order_date" required value="<?= e($values['order_date']) ?>"></label><label>Product name<input name="product" maxlength="150" required value="<?= e($values['product']) ?>"></label><label>Quantity<input type="number" name="quantity" min="1" step="1" required value="<?= e($values['quantity']) ?>"></label><label>Amount<input type="number" name="amount" min="0" step="0.01" required value="<?= e($values['amount']) ?>"></label><button type="submit">Save order</button></form>
<?php page_end($user,'parties'); ?>
