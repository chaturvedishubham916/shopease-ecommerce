<?php
$page_title = 'Sales Report';
require_once '../includes/admin_header.php';

$valid = fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d ?? '') && strtotime($d) !== false;
$from = $valid($_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
$to   = $valid($_GET['to'] ?? '')   ? $_GET['to']   : date('Y-m-d');
if ($from > $to) [$from, $to] = [$to, $from];

// Summary (cancelled orders are excluded from sales)
$stmt = $conn->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS revenue FROM orders
                        WHERE order_status <> 'Cancelled' AND DATE(order_date) BETWEEN ? AND ?");
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$sum = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE order_status = 'Cancelled' AND DATE(order_date) BETWEEN ? AND ?");
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$cancelled = $stmt->get_result()->fetch_row()[0];
$stmt->close();

$stmt = $conn->prepare("SELECT p.product_name, SUM(oi.quantity) AS qty, SUM(oi.quantity * oi.price) AS amount
                        FROM order_items oi JOIN orders o ON o.order_id = oi.order_id JOIN products p ON p.product_id = oi.product_id
                        WHERE o.order_status <> 'Cancelled' AND DATE(o.order_date) BETWEEN ? AND ?
                        GROUP BY p.product_id, p.product_name ORDER BY qty DESC LIMIT 5");
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$top = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT c.category_name, SUM(oi.quantity) AS qty, SUM(oi.quantity * oi.price) AS amount
                        FROM order_items oi JOIN orders o ON o.order_id = oi.order_id
                        JOIN products p ON p.product_id = oi.product_id JOIN categories c ON c.category_id = p.category_id
                        WHERE o.order_status <> 'Cancelled' AND DATE(o.order_date) BETWEEN ? AND ?
                        GROUP BY c.category_id, c.category_name ORDER BY amount DESC");
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$byCat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT DATE(order_date) AS d, COUNT(*) AS n, SUM(total_amount) AS amount FROM orders
                        WHERE order_status <> 'Cancelled' AND DATE(order_date) BETWEEN ? AND ?
                        GROUP BY DATE(order_date) ORDER BY d DESC");
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$daily = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$avg = $sum['n'] ? $sum['revenue'] / $sum['n'] : 0;
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h3 class="mb-0">Sales Report <small class="text-muted fs-6"><?= date('d M Y', strtotime($from)) ?> to <?= date('d M Y', strtotime($to)) ?></small></h3>
  <div class="d-flex gap-2 no-print">
    <form method="get" class="d-flex gap-2 align-items-center">
      <input type="date" name="from" value="<?= e($from) ?>" class="form-control form-control-sm">
      <span>to</span>
      <input type="date" name="to" value="<?= e($to) ?>" class="form-control form-control-sm">
      <button class="btn btn-dark btn-sm">Apply</button>
    </form>
    <button class="btn btn-outline-dark btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([['Orders', $sum['n']], ['Revenue', money($sum['revenue'])], ['Average Order', money($avg)], ['Cancelled Orders', $cancelled]] as [$l, $v]): ?>
  <div class="col-6 col-lg-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small"><?= $l ?></div><div class="fs-4 fw-bold"><?= e($v) ?></div></div></div></div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Top 5 Products</div>
    <table class="table mb-0"><thead class="table-light"><tr><th>Product</th><th>Units</th><th class="text-end">Amount</th></tr></thead><tbody>
    <?php foreach ($top as $t): ?><tr><td><?= e($t['product_name']) ?></td><td><?= (int)$t['qty'] ?></td><td class="text-end"><?= money($t['amount']) ?></td></tr>
    <?php endforeach; if (!$top): ?><tr><td colspan="3" class="text-muted text-center">No sales in this period.</td></tr><?php endif; ?></tbody></table></div></div>
  <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Sales by Category</div>
    <table class="table mb-0"><thead class="table-light"><tr><th>Category</th><th>Units</th><th class="text-end">Amount</th></tr></thead><tbody>
    <?php foreach ($byCat as $t): ?><tr><td><?= e($t['category_name']) ?></td><td><?= (int)$t['qty'] ?></td><td class="text-end"><?= money($t['amount']) ?></td></tr>
    <?php endforeach; if (!$byCat): ?><tr><td colspan="3" class="text-muted text-center">No sales in this period.</td></tr><?php endif; ?></tbody></table></div></div>
  <div class="col-12"><div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Daily Sales</div>
    <table class="table mb-0"><thead class="table-light"><tr><th>Date</th><th>Orders</th><th class="text-end">Revenue</th></tr></thead><tbody>
    <?php foreach ($daily as $d): ?><tr><td><?= date('d M Y', strtotime($d['d'])) ?></td><td><?= (int)$d['n'] ?></td><td class="text-end"><?= money($d['amount']) ?></td></tr>
    <?php endforeach; if (!$daily): ?><tr><td colspan="3" class="text-muted text-center">No sales in this period.</td></tr><?php endif; ?></tbody></table></div></div>
</div>
<?php require_once '../includes/admin_footer.php'; ?>
