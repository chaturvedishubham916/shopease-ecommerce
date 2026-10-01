<?php
require_once 'includes/header.php';

$pid = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare('SELECT p.*, c.category_name FROM products p
                        JOIN categories c ON c.category_id = p.category_id WHERE p.product_id = ?');
$stmt->bind_param('i', $pid);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) {
    flash('danger', 'Product not found.');
    redirect(BASE_URL . 'index.php');
}
$page_title = $p['product_name'];

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review') {
    require_login();
    $uid = (int)$_SESSION['user_id'];
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if (!csrf_check())                        flash('danger', 'Invalid request.');
    elseif ($rating < 1 || $rating > 5)       flash('danger', 'Please select a rating from 1 to 5.');
    elseif (strlen($comment) > 500)           flash('danger', 'Comment is too long (max 500 characters).');
    else {
        // One review per user per product: replace the old one if it exists
        $stmt = $conn->prepare('DELETE FROM reviews WHERE user_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $uid, $pid);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare('INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiis', $pid, $uid, $rating, $comment);
        $stmt->execute();
        $stmt->close();
        flash('success', 'Thanks for your review!');
    }
    redirect(BASE_URL . 'product.php?id=' . $pid);
}

// Reviews and average rating
$stmt = $conn->prepare('SELECT r.rating, r.comment, r.created_at, u.full_name
                        FROM reviews r JOIN users u ON u.user_id = r.user_id
                        WHERE r.product_id = ? ORDER BY r.created_at DESC');
$stmt->bind_param('i', $pid);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$avg = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

// In wishlist?
$inWish = false;
if (is_logged_in()) {
    $stmt = $conn->prepare('SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ?');
    $stmt->bind_param('ii', $_SESSION['user_id'], $pid);
    $stmt->execute();
    $inWish = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

// Related products
$stmt = $conn->prepare('SELECT product_id, product_name, price, image FROM products
                        WHERE category_id = ? AND product_id <> ? ORDER BY RAND() LIMIT 4');
$stmt->bind_param('ii', $p['category_id'], $pid);
$stmt->execute();
$related = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="index.php?cat=<?= (int)$p['category_id'] ?>"><?= e($p['category_name']) ?></a></li>
    <li class="breadcrumb-item active"><?= e($p['product_name']) ?></li>
  </ol>
</nav>

<div class="row g-4">
  <div class="col-md-5">
    <div class="card"><img src="<?= e(product_img($p['image'])) ?>" class="img-fluid p-3" alt="<?= e($p['product_name']) ?>"></div>
  </div>
  <div class="col-md-7">
    <h3><?= e($p['product_name']) ?></h3>
    <div class="mb-2">
      <?= stars($avg) ?>
      <span class="text-muted small ms-1"><?= $reviews ? number_format($avg, 1) . ' (' . count($reviews) . ' review' . (count($reviews) > 1 ? 's' : '') . ')' : 'No reviews yet' ?></span>
    </div>
    <div class="price fs-3 mb-2"><?= money($p['price']) ?></div>
    <p><?= nl2br(e($p['description'])) ?></p>
    <p>
      <?php if ($p['stock'] > 5): ?><span class="badge bg-success">In Stock</span>
      <?php elseif ($p['stock'] > 0): ?><span class="badge bg-warning text-dark">Only <?= (int)$p['stock'] ?> left</span>
      <?php else: ?><span class="badge bg-danger">Out of Stock</span><?php endif; ?>
    </p>

    <div class="d-flex flex-wrap gap-2">
      <?php if ($p['stock'] > 0): ?>
      <form method="post" action="cart.php" class="d-flex gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= $pid ?>">
        <input type="number" name="quantity" value="1" min="1" max="<?= (int)$p['stock'] ?>" class="form-control" style="width:80px">
        <button class="btn btn-dark"><i class="bi bi-cart-plus"></i> Add to Cart</button>
      </form>
      <?php endif; ?>
      <form method="post" action="wishlist.php">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= $pid ?>">
        <button class="btn <?= $inWish ? 'btn-danger' : 'btn-outline-danger' ?>">
          <i class="bi <?= $inWish ? 'bi-heart-fill' : 'bi-heart' ?>"></i> <?= $inWish ? 'In Wishlist' : 'Add to Wishlist' ?>
        </button>
      </form>
    </div>
  </div>
</div>

<hr class="my-4">
<div class="row g-4">
  <div class="col-md-7">
    <h5>Customer Reviews</h5>
    <?php if (!$reviews): ?><p class="text-muted">Be the first to review this product.</p><?php endif; ?>
    <?php foreach ($reviews as $r): ?>
      <div class="border-bottom py-2">
        <strong><?= e($r['full_name']) ?></strong> <?= stars($r['rating']) ?>
        <small class="text-muted ms-2"><?= date('d M Y', strtotime($r['created_at'])) ?></small>
        <?php if ($r['comment']): ?><div><?= e($r['comment']) ?></div><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="col-md-5">
    <h5>Write a Review</h5>
    <?php if (is_logged_in()): ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="review">
      <div class="mb-2"><label class="form-label">Rating</label>
        <select name="rating" class="form-select" required>
          <option value="">Select</option>
          <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?>
        </select></div>
      <div class="mb-2"><label class="form-label">Comment</label>
        <textarea name="comment" rows="3" maxlength="500" class="form-control"></textarea></div>
      <button class="btn btn-outline-dark btn-sm">Submit Review</button>
    </form>
    <?php else: ?>
      <p class="text-muted"><a href="login.php">Log in</a> to write a review.</p>
    <?php endif; ?>
  </div>
</div>

<?php if ($related): ?>
<h5 class="mt-5 mb-3">You may also like</h5>
<div class="row row-cols-2 row-cols-md-4 g-3">
  <?php foreach ($related as $r): ?>
  <div class="col">
    <a href="product.php?id=<?= (int)$r['product_id'] ?>" class="text-decoration-none text-dark">
      <div class="card h-100 product-card">
        <img src="<?= e(product_img($r['image'])) ?>" class="card-img-top" alt="">
        <div class="card-body"><h6 class="mb-1"><?= e($r['product_name']) ?></h6><div class="price"><?= money($r['price']) ?></div></div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
