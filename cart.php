<?php
$page_title = 'My Cart';
require_once 'includes/header.php';
require_login();
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('danger', 'Invalid request.'); redirect(BASE_URL . 'cart.php'); }

    $action = $_POST['action'] ?? '';
    $pid    = (int)($_POST['product_id'] ?? 0);
    $qty    = max(1, (int)($_POST['quantity'] ?? 1));

    $stmt = $conn->prepare('SELECT product_name, stock FROM products WHERE product_id = ?');
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prod) {
        flash('danger', 'Product not found.');
        redirect(BASE_URL . 'index.php');
    }

    if ($action === 'add') {
        $stmt = $conn->prepare('SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $uid, $pid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $newQty = ($row['quantity'] ?? 0) + $qty;
        if ($prod['stock'] < 1) {
            flash('danger', 'Sorry, this product is out of stock.');
        } else {
            if ($newQty > $prod['stock']) {
                $newQty = $prod['stock'];
                flash('warning', 'Only ' . $prod['stock'] . ' unit(s) available. Quantity adjusted.');
            } else {
                flash('success', '“' . $prod['product_name'] . '” added to your cart.');
            }
            $stmt = $conn->prepare('INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)
                                    ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)');
            $stmt->bind_param('iii', $uid, $pid, $newQty);
            $stmt->execute();
            $stmt->close();
        }
        redirect(back_url(BASE_URL . 'cart.php'));
    }

    if ($action === 'update') {
        $qty = min($qty, max(1, (int)$prod['stock']));
        $stmt = $conn->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?');
        $stmt->bind_param('iii', $qty, $uid, $pid);
        $stmt->execute();
        $stmt->close();
        flash('success', 'Cart updated.');
    }

    if ($action === 'remove') {
        $stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $uid, $pid);
        $stmt->execute();
        $stmt->close();
        flash('info', 'Item removed from cart.');
    }
    redirect(BASE_URL . 'cart.php');
}

$stmt = $conn->prepare('SELECT c.quantity, p.product_id, p.product_name, p.price, p.stock, p.image
                        FROM cart c JOIN products p ON p.product_id = c.product_id
                        WHERE c.user_id = ? ORDER BY c.added_at DESC');
$stmt->bind_param('i', $uid);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = 0;
foreach ($items as $it) $total += $it['price'] * $it['quantity'];
?>
<h3 class="mb-3"><i class="bi bi-cart3"></i> Shopping Cart</h3>

<?php if (!$items): ?>
  <div class="alert alert-secondary">Your cart is empty. <a href="index.php">Continue shopping</a></div>
<?php else: ?>
<div class="row g-4">
  <div class="col-lg-8">
    <?php foreach ($items as $it): ?>
    <div class="card mb-3">
      <div class="card-body">
        <div class="row align-items-center g-3">
          <div class="col-3 col-md-2"><img src="<?= e(product_img($it['image'])) ?>" class="img-fluid rounded" alt=""></div>
          <div class="col-9 col-md-4">
            <a class="fw-semibold text-dark text-decoration-none" href="product.php?id=<?= (int)$it['product_id'] ?>"><?= e($it['product_name']) ?></a>
            <div class="text-muted small"><?= money($it['price']) ?> each</div>
            <?php if ($it['stock'] < $it['quantity']): ?><div class="text-danger small">Only <?= (int)$it['stock'] ?> left in stock</div><?php endif; ?>
          </div>
          <div class="col-6 col-md-3">
            <form method="post" class="d-flex gap-1">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
              <input type="number" name="quantity" min="1" max="<?= max(1, (int)$it['stock']) ?>" value="<?= (int)$it['quantity'] ?>" class="form-control form-control-sm" style="width:70px">
              <button class="btn btn-outline-dark btn-sm">Update</button>
            </form>
          </div>
          <div class="col-4 col-md-2 fw-bold"><?= money($it['price'] * $it['quantity']) ?></div>
          <div class="col-2 col-md-1 text-end">
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
              <button class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5>Order Summary</h5>
        <div class="d-flex justify-content-between"><span>Items</span><span><?= array_sum(array_column($items, 'quantity')) ?></span></div>
        <div class="d-flex justify-content-between"><span>Delivery</span><span class="text-success">Free</span></div>
        <hr>
        <div class="d-flex justify-content-between fs-5 fw-bold"><span>Total</span><span><?= money($total) ?></span></div>
        <a href="checkout.php" class="btn btn-warning w-100 mt-3">Proceed to Checkout</a>
        <a href="index.php" class="btn btn-link w-100">Continue shopping</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
