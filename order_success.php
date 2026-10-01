<?php
$page_title = 'Order Placed';
require_once 'includes/header.php';
require_login();

$oid = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT o.order_id, o.total_amount, o.order_date, pay.payment_method, pay.payment_status, pay.transaction_id
                        FROM orders o JOIN payments pay ON pay.order_id = o.order_id
                        WHERE o.order_id = ? AND o.user_id = ?');
$stmt->bind_param('ii', $oid, $_SESSION['user_id']);
$stmt->execute();
$o = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$o) redirect(BASE_URL . 'orders.php');
?>
<div class="card auth-card shadow-sm text-center" style="max-width:560px">
  <div class="card-body p-4">
    <i class="bi bi-check-circle-fill text-success" style="font-size:3.5rem"></i>
    <h4 class="mt-2">Thank you! Your order is placed.</h4>
    <p class="text-muted">Order #<?= (int)$o['order_id'] ?> &middot; <?= date('d M Y, h:i A', strtotime($o['order_date'])) ?></p>
    <table class="table table-sm text-start">
      <tr><th>Amount</th><td><?= money($o['total_amount']) ?></td></tr>
      <tr><th>Payment</th><td><?= e($o['payment_method'] === 'COD' ? 'Cash on Delivery' : 'Online (Demo)') ?> - <?= e($o['payment_status']) ?></td></tr>
      <?php if ($o['transaction_id']): ?><tr><th>Transaction ID</th><td><?= e($o['transaction_id']) ?></td></tr><?php endif; ?>
    </table>
    <a href="order_view.php?id=<?= (int)$o['order_id'] ?>" class="btn btn-dark">View Order</a>
    <a href="index.php" class="btn btn-outline-dark">Continue Shopping</a>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
