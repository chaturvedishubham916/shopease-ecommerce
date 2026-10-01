<?php
$page_title = 'Products';
require_once '../includes/admin_header.php';

// Delete a product (blocked if it appears in past orders, to keep order history intact)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['product_id'] ?? 0);
    if (!csrf_check()) {
        flash('danger', 'Invalid request.');
    } else {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $used = $stmt->get_result()->fetch_row()[0];
        $stmt->close();

        if ($used > 0) {
            flash('warning', 'This product is part of existing orders and cannot be deleted. Set its stock to 0 instead.');
        } else {
            $stmt = $conn->prepare('SELECT image FROM products WHERE product_id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $img = $stmt->get_result()->fetch_assoc()['image'] ?? null;
            $stmt->close();

            $stmt = $conn->prepare('DELETE FROM products WHERE product_id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            if ($img && $img !== 'no-image.png') @unlink(__DIR__ . '/../uploads/products/' . basename($img));
            flash('success', 'Product deleted.');
        }
    }
    redirect($A . 'products.php');
}

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';
$stmt = $conn->prepare('SELECT p.product_id, p.product_name, p.price, p.stock, p.image, c.category_name
                        FROM products p JOIN categories c ON c.category_id = p.category_id
                        WHERE p.product_name LIKE ? ORDER BY p.product_id DESC');
$stmt->bind_param('s', $like);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h3 class="mb-0">Products</h3>
  <div class="d-flex gap-2">
    <form class="d-flex gap-2" method="get"><input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search products"><button class="btn btn-outline-dark">Search</button></form>
    <a href="product_form.php" class="btn btn-dark"><i class="bi bi-plus-lg"></i> Add Product</a>
  </div>
</div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead class="table-light"><tr><th>ID</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th class="text-end">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $p): ?>
    <tr>
      <td><?= (int)$p['product_id'] ?></td>
      <td><img src="<?= e(product_img($p['image'])) ?>" width="48" height="48" style="object-fit:contain" alt=""></td>
      <td><?= e($p['product_name']) ?></td>
      <td><?= e($p['category_name']) ?></td>
      <td><?= money($p['price']) ?></td>
      <td><span class="badge bg-<?= $p['stock'] == 0 ? 'danger' : ($p['stock'] <= 5 ? 'warning text-dark' : 'success') ?>"><?= (int)$p['stock'] ?></span></td>
      <td class="text-end">
        <a href="product_form.php?id=<?= (int)$p['product_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
        <form method="post" class="d-inline" onsubmit="return confirm('Delete this product?');">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
          <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
        </form>
      </td>
    </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No products found.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<?php require_once '../includes/admin_footer.php'; ?>
