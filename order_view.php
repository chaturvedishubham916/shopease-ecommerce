<?php
$page_title = 'Order Details';
require_once 'includes/header.php';
require_login();
$uid = (int)$_SESSION['user_id'];
$oid = (int)($_GET['id'] ?? $_POST['order_id'] ?? 0);

// Cancel a pending order and put the stock back
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if (!csrf_check()) { flash('danger', 'Invalid request.'); redirect(BASE_URL . 'orders.php'); }
    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ? AND user_id = ? AND order_status = 'Pending' FOR UPDATE");
        $stmt->bind_param('ii', $oid, $uid);
        $stmt->execute();
        $ok = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$ok) throw new Exception('This order can no longer be cancelled.');

        $stmt = $conn->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ?");
        $stmt->bind_param('i', $oid);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('UPDATE products p JOIN order_items oi ON oi.product_id = p.product_id
                                SET p.stock = p.stock + oi.quantity WHERE oi.order_id = ?');
        $stmt->bind_param('i', $oid);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        flash('success', 'Order #' . $oid . ' has been cancelled.');
    } catch (Exception $ex) {
        $conn->rollback();
        flash('danger', $ex->getMessage());
    }
    redirect(BASE_URL . 'order_view.php?id=' . $oid);
}

$stmt = $conn->prepare('SELECT o.*, pay.payment_method, pay.payment_status, pay.transaction_id
                        FROM orders o LEFT JOIN payments pay ON pay.order_id = o.order_id
                        WHERE o.order_id = ? AND o.user_id = ?');
$stmt->bind_param('ii', $oid, $uid);
$stmt->execute();
$o = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$o) { flash('danger', 'Order not found.'); redirect(BASE_URL . 'orders.php'); }

$stmt = $conn->prepare('SELECT oi.quantity, oi.price, p.product_id, p.product_name, p.image
                        FROM order_items oi JOIN products p ON p.product_id = oi.product_id WHERE oi.order_id = ?');
$stmt->bind_param('i', $oid);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$badge = ['Pending' => 'warning text-dark', 'Shipped' => 'info text-dark', 'Delivered' => 'success', 'Cancelled' => 'danger'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Order #<?= (int)$o['order_id'] ?></h3>
  <span class="badge fs-6 bg-<?= $badge[$o['order_status']] ?>"><?= e($o['order_status']) ?></span>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <?php foreach ($items as $it): ?>
      <div class="row align-items-center g-2 border-bottom py-2">
        <div class="col-2"><img src="<?= e(product_img($it['image'])) ?>" class="img-fluid rounded" alt=""></div>
        <div class="col-6"><a class="text-dark text-decoration-none" href="product.php?id=<?= (int)$it['product_id'] ?>"><?= e($it['product_name']) ?></a>
          <div class="small text-muted"><?= money($it['price']) ?> × <?= (int)$it['quantity'] ?></div></div>
        <div class="col-4 text-end fw-semibold"><?= money($it['price'] * $it['quantity']) ?></div>
      </div>
      <?php endforeach; ?>
      <div class="d-flex justify-content-between fs-5 fw-bold pt-3"><span>Total</span><span><?= money($o['total_amount']) ?></span></div>
    </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card mb-3"><div class="card-body">
      <h6>Delivery Address</h6>
      <div><?= e($o['shipping_name']) ?></div>
      <div class="text-muted small"><?= nl2br(e($o['shipping_address'])) ?></div>
      <div class="small mt-1"><i class="bi bi-telephone"></i> <?= e($o['shipping_phone']) ?></div>
    </div></div>
    <div class="card mb-3"><div class="card-body">
      <h6>Payment</h6>
      <div><?= $o['payment_method'] === 'COD' ? 'Cash on Delivery' : 'Online (Demo)' ?> - <?= e($o['payment_status']) ?></div>
      <?php if ($o['transaction_id']): ?><div class="small text-muted"><?= e($o['transaction_id']) ?></div><?php endif; ?>
      <div class="small text-muted mt-1">Placed on <?= date('d M Y, h:i A', strtotime($o['order_date'])) ?></div>
    </div></div>
    <?php if ($o['order_status'] === 'Pending'): ?>
    <form method="post" onsubmit="return confirm('Cancel this order?');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="cancel">
      <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
      <button class="btn btn-outline-danger w-100">Cancel Order</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<a href="orders.php" class="btn btn-link mt-3">&larr; Back to my orders</a>
<?php require_once 'includes/footer.php'; ?>
