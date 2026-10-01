<?php
$page_title = 'My Wishlist';
require_once 'includes/header.php';
require_login();
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('danger', 'Invalid request.'); redirect(BASE_URL . 'wishlist.php'); }
    $pid = (int)($_POST['product_id'] ?? 0);

    $stmt = $conn->prepare('SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ?');
    $stmt->bind_param('ii', $uid, $pid);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        $stmt = $conn->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?');
        flash('info', 'Removed from wishlist.');
    } else {
        $stmt = $conn->prepare('INSERT INTO wishlist (user_id, product_id) SELECT ?, product_id FROM products WHERE product_id = ?');
        flash('success', 'Added to wishlist.');
    }
    $stmt->bind_param('ii', $uid, $pid);
    $stmt->execute();
    $stmt->close();
    redirect(back_url(BASE_URL . 'wishlist.php'));
}

$stmt = $conn->prepare('SELECT p.product_id, p.product_name, p.price, p.stock, p.image
                        FROM wishlist w JOIN products p ON p.product_id = w.product_id
                        WHERE w.user_id = ? ORDER BY w.added_at DESC');
$stmt->bind_param('i', $uid);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<h3 class="mb-3"><i class="bi bi-heart"></i> My Wishlist</h3>
<?php if (!$items): ?>
  <div class="alert alert-secondary">Your wishlist is empty. <a href="index.php">Browse products</a></div>
<?php else: ?>
<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
  <?php foreach ($items as $p): ?>
  <div class="col">
    <div class="card h-100 product-card">
      <img src="<?= e(product_img($p['image'])) ?>" class="card-img-top" alt="">
      <div class="card-body d-flex flex-column">
        <h6><a href="product.php?id=<?= (int)$p['product_id'] ?>" class="text-dark text-decoration-none"><?= e($p['product_name']) ?></a></h6>
        <div class="price mb-2"><?= money($p['price']) ?></div>
        <div class="mt-auto d-grid gap-2">
          <?php if ($p['stock'] > 0): ?>
          <form method="post" action="cart.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
            <button class="btn btn-dark btn-sm w-100">Add to Cart</button>
          </form>
          <?php else: ?><button class="btn btn-secondary btn-sm" disabled>Out of Stock</button><?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
            <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-heartbreak"></i> Remove</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
