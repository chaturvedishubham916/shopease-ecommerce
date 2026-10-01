<?php
$page_title = 'Dashboard';
require_once '../includes/admin_header.php';

$one = fn($sql) => $conn->query($sql)->fetch_row()[0];
$products  = $one('SELECT COUNT(*) FROM products');
$customers = $one('SELECT COUNT(*) FROM users');
$orders    = $one('SELECT COUNT(*) FROM orders');
$pending   = $one("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'");
$revenue   = $one("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE order_status <> 'Cancelled'");

$recent = $conn->query('SELECT o.order_id, o.total_amount, o.order_status, o.order_date, u.full_name
                        FROM orders o JOIN users u ON u.user_id = o.user_id ORDER BY o.order_id DESC LIMIT 6')->fetch_all(MYSQLI_ASSOC);
$low = $conn->query('SELECT product_id, product_name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 6')->fetch_all(MYSQLI_ASSOC);

$badge = ['Pending' => 'warning text-dark', 'Shipped' => 'info text-dark', 'Delivered' => 'success', 'Cancelled' => 'danger'];
$cards = [
    ['Total Revenue', money($revenue), 'bi-currency-rupee', 'success'],
    ['Orders', $orders . ' (' . $pending . ' pending)', 'bi-receipt', 'primary'],
    ['Products', $products, 'bi-box-seam', 'warning'],
    ['Customers', $customers, 'bi-people', 'info'],
];
?>
<h3 class="mb-3">Dashboard</h3>
<div class="row g-3 mb-4">
  <?php foreach ($cards as [$label, $val, $icon, $color]): ?>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm"><div class="card-body d-flex align-items-center gap-3">
      <div class="fs-1 text-<?= $color ?>"><i class="bi <?= $icon ?>"></i></div>
      <div><div class="text-muted small"><?= $label ?></div><div class="fs-5 fw-bold"><?= e($val) ?></div></div>
    </div></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Recent Orders</div>
      <div class="table-responsive"><table class="table mb-0 align-middle">
        <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $o): ?>
          <tr><td>#<?= (int)$o['order_id'] ?></td><td><?= e($o['full_name']) ?></td>
            <td><?= date('d M Y', strtotime($o['order_date'])) ?></td><td><?= money($o['total_amount']) ?></td>
            <td><span class="badge bg-<?= $badge[$o['order_status']] ?>"><?= e($o['order_status']) ?></span></td>
            <td><a href="order_view.php?id=<?= (int)$o['order_id'] ?>" class="btn btn-sm btn-outline-dark">View</a></td></tr>
        <?php endforeach; if (!$recent): ?><tr><td colspan="6" class="text-muted text-center">No orders yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Low Stock (5 or fewer)</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($low as $p): ?>
          <li class="list-group-item d-flex justify-content-between">
            <a href="product_form.php?id=<?= (int)$p['product_id'] ?>" class="text-dark text-decoration-none"><?= e($p['product_name']) ?></a>
            <span class="badge bg-<?= $p['stock'] == 0 ? 'danger' : 'warning text-dark' ?>"><?= (int)$p['stock'] ?></span>
          </li>
        <?php endforeach; if (!$low): ?><li class="list-group-item text-muted">All products are well stocked.</li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
<?php require_once '../includes/admin_footer.php'; ?>
