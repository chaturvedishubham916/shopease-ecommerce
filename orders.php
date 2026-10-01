<?php
$page_title = 'My Orders';
require_once 'includes/header.php';
require_login();

$stmt = $conn->prepare('SELECT o.order_id, o.total_amount, o.order_status, o.order_date,
                               (SELECT SUM(quantity) FROM order_items WHERE order_id = o.order_id) AS items
                        FROM orders o WHERE o.user_id = ? ORDER BY o.order_id DESC');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$badge = ['Pending' => 'warning text-dark', 'Shipped' => 'info text-dark', 'Delivered' => 'success', 'Cancelled' => 'danger'];
?>
<h3 class="mb-3">My Orders</h3>
<?php if (!$orders): ?>
  <div class="alert alert-secondary">You haven't placed any orders yet. <a href="index.php">Start shopping</a></div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-hover bg-white align-middle">
  <thead class="table-light"><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td>#<?= (int)$o['order_id'] ?></td>
      <td><?= date('d M Y', strtotime($o['order_date'])) ?></td>
      <td><?= (int)$o['items'] ?></td>
      <td><?= money($o['total_amount']) ?></td>
      <td><span class="badge bg-<?= $badge[$o['order_status']] ?>"><?= e($o['order_status']) ?></span></td>
      <td><a class="btn btn-sm btn-outline-dark" href="order_view.php?id=<?= (int)$o['order_id'] ?>">View</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
