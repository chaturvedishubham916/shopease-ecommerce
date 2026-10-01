<?php
$page_title = 'Manage Order';
require_once '../includes/admin_header.php';
$oid = (int)($_GET['id'] ?? $_POST['order_id'] ?? 0);

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = $_POST['status'] ?? '';
    if (!csrf_check() || !in_array($new, ['Pending', 'Shipped', 'Delivered', 'Cancelled'], true)) {
        flash('danger', 'Invalid request.');
        redirect($A . 'order_view.php?id=' . $oid);
    }
    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare('SELECT order_status FROM orders WHERE order_id = ? FOR UPDATE');
        $stmt->bind_param('i', $oid);
        $stmt->execute();
        $cur = $stmt->get_result()->fetch_assoc()['order_status'] ?? null;
        $stmt->close();

        if ($cur === null)                                throw new Exception('Order not found.');
        if (in_array($cur, ['Delivered', 'Cancelled'], true)) throw new Exception('This order is already ' . $cur . ' and cannot be changed.');
        if ($new === $cur)                                throw new Exception('Status is already ' . $cur . '.');

        $stmt = $conn->prepare('UPDATE orders SET order_status = ? WHERE order_id = ?');
        $stmt->bind_param('si', $new, $oid);
        $stmt->execute();
        $stmt->close();

        if ($new === 'Cancelled') {   // put the stock back
            $stmt = $conn->prepare('UPDATE products p JOIN order_items oi ON oi.product_id = p.product_id
                                    SET p.stock = p.stock + oi.quantity WHERE oi.order_id = ?');
            $stmt->bind_param('i', $oid);
            $stmt->execute();
            $stmt->close();
        }
        if ($new === 'Delivered') {   // cash is collected on delivery
            $stmt = $conn->prepare("UPDATE payments SET payment_status = 'Paid', paid_at = COALESCE(paid_at, NOW()) WHERE order_id = ?");
            $stmt->bind_param('i', $oid);
            $stmt->execute();
            $stmt->close();
        }
        $conn->commit();
        flash('success', 'Order #' . $oid . ' marked as ' . $new . '.');
    } catch (Exception $ex) {
        $conn->rollback();
        flash('danger', $ex->getMessage());
    }
    redirect($A . 'order_view.php?id=' . $oid);
}

$stmt = $conn->prepare('SELECT o.*, u.full_name, u.email, pay.payment_method, pay.payment_status, pay.transaction_id
                        FROM orders o JOIN users u ON u.user_id = o.user_id
                        LEFT JOIN payments pay ON pay.order_id = o.order_id WHERE o.order_id = ?');
$stmt->bind_param('i', $oid);
$stmt->execute();
$o = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$o) { flash('danger', 'Order not found.'); redirect($A . 'orders.php'); }

$stmt = $conn->prepare('SELECT oi.quantity, oi.price, p.product_name, p.image
                        FROM order_items oi JOIN products p ON p.product_id = oi.product_id WHERE oi.order_id = ?');
$stmt->bind_param('i', $oid);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$badge = ['Pending' => 'warning text-dark', 'Shipped' => 'info text-dark', 'Delivered' => 'success', 'Cancelled' => 'danger'];
$final = in_array($o['order_status'], ['Delivered', 'Cancelled'], true);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Order #<?= (int)$o['order_id'] ?></h3>
  <span class="badge fs-6 bg-<?= $badge[$o['order_status']] ?>"><?= e($o['order_status']) ?></span>
</div>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Product</th><th>Price</th><th>Qty</th><th class="text-end">Subtotal</th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr><td><img src="<?= e(product_img($it['image'])) ?>" width="40" height="40" style="object-fit:contain" alt=""> <?= e($it['product_name']) ?></td>
          <td><?= money($it['price']) ?></td><td><?= (int)$it['quantity'] ?></td><td class="text-end"><?= money($it['price'] * $it['quantity']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end"><?= money($o['total_amount']) ?></td></tr></tfoot>
    </table></div></div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm mb-3"><div class="card-body">
      <h6>Customer</h6>
      <div><?= e($o['full_name']) ?></div><div class="small text-muted"><?= e($o['email']) ?></div>
      <hr><h6>Ship To</h6>
      <div><?= e($o['shipping_name']) ?></div>
      <div class="small text-muted"><?= nl2br(e($o['shipping_address'])) ?></div>
      <div class="small"><i class="bi bi-telephone"></i> <?= e($o['shipping_phone']) ?></div>
      <hr><h6>Payment</h6>
      <div><?= e($o['payment_method']) ?> - <?= e($o['payment_status']) ?></div>
      <?php if ($o['transaction_id']): ?><div class="small text-muted"><?= e($o['transaction_id']) ?></div><?php endif; ?>
      <div class="small text-muted mt-1">Placed <?= date('d M Y, h:i A', strtotime($o['order_date'])) ?></div>
    </div></div>

    <div class="card shadow-sm"><div class="card-body">
      <h6>Update Status</h6>
      <?php if ($final): ?>
        <div class="text-muted small">This order is <?= e($o['order_status']) ?>, so its status can no longer be changed.</div>
      <?php else: ?>
      <form method="post" class="d-flex gap-2">
        <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
        <select name="status" class="form-select">
          <?php foreach (['Pending', 'Shipped', 'Delivered', 'Cancelled'] as $s): ?>
            <option <?= $o['order_status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-dark">Update</button>
      </form>
      <div class="form-text">Cancelling puts the stock back. Delivered marks COD payment as Paid.</div>
      <?php endif; ?>
    </div></div>
  </div>
</div>
<a href="orders.php" class="btn btn-link mt-3">&larr; Back to orders</a>
<?php require_once '../includes/admin_footer.php'; ?>
