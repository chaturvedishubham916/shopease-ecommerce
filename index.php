<?php
$page_title = 'Home';
require_once 'includes/header.php';

$q    = trim($_GET['q'] ?? '');
$cat  = (int)($_GET['cat'] ?? 0);
$sort = $_GET['sort'] ?? 'new';

$orderBy = [
    'new'        => 'p.product_id DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name'       => 'p.product_name ASC',
][$sort] ?? 'p.product_id DESC';

$sql = 'SELECT p.product_id, p.product_name, p.price, p.stock, p.image, c.category_name
        FROM products p JOIN categories c ON c.category_id = p.category_id WHERE 1=1';
$types = '';
$params = [];

if ($q !== '') {
    $sql .= ' AND (p.product_name LIKE ? OR p.description LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'ss';
    array_push($params, $like, $like);
}
if ($cat > 0) {
    $sql .= ' AND p.category_id = ?';
    $types .= 'i';
    $params[] = $cat;
}
$sql .= ' ORDER BY ' . $orderBy;   // $orderBy comes from a fixed whitelist above

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$categories = $conn->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetch_all(MYSQLI_ASSOC);

function qs($over = []) { return '?' . http_build_query(array_filter(array_merge($_GET, $over), fn($v) => $v !== '' && $v !== 0)); }
?>
<?php if ($q === '' && !$cat): ?>
<div class="hero mb-4">
  <h2 class="fw-bold">Shop smarter with <?= SITE_NAME ?></h2>
  <p class="mb-0">Electronics, fashion, books and more, delivered to your door.</p>
</div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mb-3">
  <a class="btn btn-sm <?= !$cat ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= qs(['cat' => 0]) ?>">All</a>
  <?php foreach ($categories as $c): ?>
    <a class="btn btn-sm <?= $cat == $c['category_id'] ? 'btn-dark' : 'btn-outline-dark' ?>"
       href="<?= qs(['cat' => $c['category_id']]) ?>"><?= e($c['category_name']) ?></a>
  <?php endforeach; ?>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="text-muted">
    <?= count($products) ?> product(s)<?= $q !== '' ? ' for “' . e($q) . '”' : '' ?>
  </div>
  <form method="get" class="d-flex align-items-center gap-2">
    <input type="hidden" name="q" value="<?= e($q) ?>">
    <input type="hidden" name="cat" value="<?= $cat ?>">
    <label class="small text-muted">Sort</label>
    <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option>
      <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
      <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
    </select>
  </form>
</div>

<?php if (!$products): ?>
  <div class="alert alert-secondary">No products found. Try a different search or category.</div>
<?php else: ?>
<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
  <?php foreach ($products as $p): ?>
  <div class="col">
    <div class="card h-100 product-card">
      <img src="<?= e(product_img($p['image'])) ?>" class="card-img-top" alt="<?= e($p['product_name']) ?>">
      <div class="card-body d-flex flex-column">
        <small class="text-muted"><?= e($p['category_name']) ?></small>
        <h6 class="card-title mt-1">
          <a href="product.php?id=<?= (int)$p['product_id'] ?>" class="text-dark text-decoration-none"><?= e($p['product_name']) ?></a>
        </h6>
        <div class="price mb-2"><?= money($p['price']) ?></div>
        <div class="mt-auto">
          <?php if ($p['stock'] > 0): ?>
            <form method="post" action="cart.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
              <button class="btn btn-dark btn-sm w-100"><i class="bi bi-cart-plus"></i> Add to Cart</button>
            </form>
          <?php else: ?>
            <button class="btn btn-secondary btn-sm w-100" disabled>Out of Stock</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
