<?php
$page_title = 'Orders';
require_once '../includes/admin_header.php';

$status = $_GET['status'] ?? '';
$valid = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];
if (!in_array($status, $valid, true)) $status = '';

$sql = 'SELECT o.order_id, o.total_amount, o.order_status, o.order_date, u.full_name,
               pay.payment_method, pay.payment_status
        FROM orders o JOIN users u ON u.user_id = o.user_id
        LEFT JOIN payments pay ON pay.order_id = o.order_id';
if ($status !== '') {
    $stmt = $conn->prepare($sql . ' WHERE o.order_status = ? ORDER BY o.order_id DESC');
    $stmt->bind_param('s', $status);
} else {
    $stmt = $conn->prepare($sql . ' ORDER BY o.order_id DESC');
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$badge = ['Pending' => 'warning text-dark', 'Shipped' => 'info text-dark', 'Delivered' => 'success', 'Cancelled' => 'danger'];
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h3 class="mb-0">Orders</h3>
  <div class="btn-group">
    <a href="orders.php" class="btn btn-sm <?= $status === '' ? 'btn-dark' : 'btn-outline-dark' ?>">All</a>
    <?php foreach ($valid as $s): ?>
      <a href="orders.php?status=<?= $s ?>" class="btn btn-sm <?= $status === $s ? 'btn-dark' : 'btn-outline-dark' ?>"><?= $s ?></a>
    <?php endforeach; ?>
  </div>
</div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td>#<?= (int)$o['order_id'] ?></td>
      <td><?= e($o['full_name']) ?></td>
      <td><?= date('d M Y, h:i A', strtotime($o['order_date'])) ?></td>
      <td><?= money($o['total_amount']) ?></td>
      <td><?= e($o['payment_method']) ?> <span class="text-muted small">(<?= e($o['payment_status']) ?>)</span></td>
      <td><span class="badge bg-<?= $badge[$o['order_status']] ?>"><?= e($o['order_status']) ?></span></td>
      <td><a href="order_view.php?id=<?= (int)$o['order_id'] ?>" class="btn btn-sm btn-outline-dark">Manage</a></td>
    </tr>
  <?php endforeach; if (!$orders): ?><tr><td colspan="7" class="text-center text-muted py-4">No orders found.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<?php require_once '../includes/admin_footer.php'; ?>
