<?php
$page_title = 'Checkout';
require_once 'includes/header.php';
require_login();
$uid = (int)$_SESSION['user_id'];

// Cart items
$stmt = $conn->prepare('SELECT c.quantity, p.product_id, p.product_name, p.price, p.stock
                        FROM cart c JOIN products p ON p.product_id = c.product_id WHERE c.user_id = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$items) { flash('warning', 'Your cart is empty.'); redirect(BASE_URL . 'cart.php'); }

$total = 0;
foreach ($items as $it) $total += $it['price'] * $it['quantity'];

// Prefill shipping details from profile
$stmt = $conn->prepare('SELECT full_name, phone, address, city, pincode FROM users WHERE user_id = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

$errors = [];
$name    = $u['full_name'];
$phone   = $u['phone'];
$address = trim(($u['address'] ?? '') . ' ' . ($u['city'] ?? '') . ' ' . ($u['pincode'] ?? ''));
$method  = 'COD';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $method  = ($_POST['method'] ?? '') === 'Online' ? 'Online' : 'COD';

        if (strlen($name) < 3)                        $errors[] = 'Enter the receiver name.';
        if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) $errors[] = 'Enter a valid 10-digit mobile number.';
        if (strlen($address) < 10)                    $errors[] = 'Enter a complete delivery address.';

        if (!$errors) {
            try {
                $conn->begin_transaction();

                // Re-check stock inside the transaction (locks the rows)
                $orderTotal = 0;
                $lines = [];
                foreach ($items as $it) {
                    $stmt = $conn->prepare('SELECT product_name, price, stock FROM products WHERE product_id = ? FOR UPDATE');
                    $stmt->bind_param('i', $it['product_id']);
                    $stmt->execute();
                    $p = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if (!$p || $p['stock'] < $it['quantity']) {
                        throw new Exception('Sorry, “' . ($p['product_name'] ?? 'a product') . '” does not have enough stock.');
                    }
                    $orderTotal += $p['price'] * $it['quantity'];
                    $lines[] = [$it['product_id'], $it['quantity'], $p['price']];
                }

                $stmt = $conn->prepare('INSERT INTO orders (user_id, total_amount, shipping_name, shipping_phone, shipping_address) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('idsss', $uid, $orderTotal, $name, $phone, $address);
                $stmt->execute();
                $orderId = $conn->insert_id;
                $stmt->close();

                $ins = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
                $upd = $conn->prepare('UPDATE products SET stock = stock - ? WHERE product_id = ?');
                foreach ($lines as [$pid, $qty, $price]) {
                    $ins->bind_param('iiid', $orderId, $pid, $qty, $price);
                    $ins->execute();
                    $upd->bind_param('ii', $qty, $pid);
                    $upd->execute();
                }
                $ins->close();
                $upd->close();

                // Payment record (online payment is simulated for this project)
                if ($method === 'Online') {
                    $txn = 'TXN' . strtoupper(bin2hex(random_bytes(5)));
                    $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, payment_status, transaction_id, paid_at) VALUES (?, 'Online', 'Paid', ?, NOW())");
                    $stmt->bind_param('is', $orderId, $txn);
                } else {
                    $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, payment_status) VALUES (?, 'COD', 'Pending')");
                    $stmt->bind_param('i', $orderId);
                }
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
                $stmt->bind_param('i', $uid);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                redirect(BASE_URL . 'order_success.php?id=' . $orderId);
            } catch (Exception $ex) {
                $conn->rollback();
                $errors[] = $ex->getMessage();
            }
        }
    }
}
?>
<h3 class="mb-3">Checkout</h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" class="row g-4">
  <?= csrf_field() ?>
  <div class="col-lg-7">
    <div class="card mb-3"><div class="card-body">
      <h5>Delivery Details</h5>
      <div class="mb-3"><label class="form-label">Receiver Name</label>
        <input type="text" name="name" class="form-control" value="<?= e($name) ?>" required></div>
      <div class="mb-3"><label class="form-label">Mobile Number</label>
        <input type="text" name="phone" maxlength="10" class="form-control" value="<?= e($phone) ?>" required></div>
      <div class="mb-0"><label class="form-label">Delivery Address</label>
        <textarea name="address" rows="3" class="form-control" required><?= e($address) ?></textarea></div>
    </div></div>

    <div class="card"><div class="card-body">
      <h5>Payment Method</h5>
      <div class="form-check mb-2">
        <input class="form-check-input" type="radio" name="method" id="cod" value="COD" <?= $method === 'COD' ? 'checked' : '' ?>>
        <label class="form-check-label" for="cod">Cash on Delivery</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="method" id="online" value="Online" <?= $method === 'Online' ? 'checked' : '' ?>>
        <label class="form-check-label" for="online">Online Payment <span class="badge bg-secondary">Demo</span></label>
        <div class="form-text">Simulated payment for demonstration. No real money is charged.</div>
      </div>
    </div></div>
  </div>

  <div class="col-lg-5">
    <div class="card shadow-sm"><div class="card-body">
      <h5>Order Summary</h5>
      <?php foreach ($items as $it): ?>
        <div class="d-flex justify-content-between small mb-1">
          <span><?= e($it['product_name']) ?> × <?= (int)$it['quantity'] ?></span>
          <span><?= money($it['price'] * $it['quantity']) ?></span>
        </div>
      <?php endforeach; ?>
      <hr>
      <div class="d-flex justify-content-between"><span>Delivery</span><span class="text-success">Free</span></div>
      <div class="d-flex justify-content-between fs-5 fw-bold mt-1"><span>Total</span><span><?= money($total) ?></span></div>
      <button class="btn btn-warning w-100 mt-3">Place Order</button>
      <a href="cart.php" class="btn btn-link w-100">Back to cart</a>
    </div></div>
  </div>
</form>
<?php require_once 'includes/footer.php'; ?>
