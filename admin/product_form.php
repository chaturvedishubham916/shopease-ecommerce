<?php
require_once '../includes/admin_header.php';

$id = (int)($_GET['id'] ?? $_POST['product_id'] ?? 0);
$p = ['category_id' => 0, 'product_name' => '', 'description' => '', 'price' => '', 'stock' => '', 'image' => 'no-image.png'];

if ($id) {
    $stmt = $conn->prepare('SELECT * FROM products WHERE product_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) { flash('danger', 'Product not found.'); redirect($A . 'products.php'); }
    $p = $found;
}
$page_title = $id ? 'Edit Product' : 'Add Product';
$cats = $conn->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetch_all(MYSQLI_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $p['category_id']  = (int)($_POST['category_id'] ?? 0);
        $p['product_name'] = trim($_POST['product_name'] ?? '');
        $p['description']  = trim($_POST['description'] ?? '');
        $p['price']        = $_POST['price'] ?? '';
        $p['stock']        = $_POST['stock'] ?? '';

        if (!$p['category_id'])                                  $errors[] = 'Select a category.';
        if (strlen($p['product_name']) < 3)                      $errors[] = 'Product name must be at least 3 characters.';
        if (!is_numeric($p['price']) || $p['price'] <= 0)        $errors[] = 'Enter a valid price.';
        if (!ctype_digit((string)$p['stock']))                   $errors[] = 'Stock must be a whole number (0 or more).';

        // Image upload (optional): JPG/PNG/WEBP, max 2 MB
        $newImage = null;
        if (!$errors && !empty($_FILES['image']['name'])) {
            $f = $_FILES['image'];
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Image upload failed. Please try again.';
            } elseif ($f['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Image must be smaller than 2 MB.';
            } else {
                $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']);
                if (!isset($allowed[$mime])) {
                    $errors[] = 'Only JPG, PNG or WEBP images are allowed.';
                } else {
                    $newImage = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
                    $dir = __DIR__ . '/../uploads/products/';
                    if (!is_dir($dir)) mkdir($dir, 0775, true);
                    if (!move_uploaded_file($f['tmp_name'], $dir . $newImage)) {
                        $errors[] = 'Could not save the image. Check folder permissions.';
                        $newImage = null;
                    }
                }
            }
        }

        if (!$errors) {
            $price = (float)$p['price'];
            $stock = (int)$p['stock'];
            if ($id) {
                $img = $newImage ?? $p['image'];
                $stmt = $conn->prepare('UPDATE products SET category_id=?, product_name=?, description=?, price=?, stock=?, image=? WHERE product_id=?');
                $stmt->bind_param('issdisi', $p['category_id'], $p['product_name'], $p['description'], $price, $stock, $img, $id);
                $stmt->execute();
                $stmt->close();
                if ($newImage && $p['image'] !== 'no-image.png') @unlink(__DIR__ . '/../uploads/products/' . basename($p['image']));
                flash('success', 'Product updated.');
            } else {
                $img = $newImage ?? 'no-image.png';
                $stmt = $conn->prepare('INSERT INTO products (category_id, product_name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('issdis', $p['category_id'], $p['product_name'], $p['description'], $price, $stock, $img);
                $stmt->execute();
                $stmt->close();
                flash('success', 'Product added.');
            }
            redirect($A . 'products.php');
        }
    }
}
?>
<h3 class="mb-3"><?= $id ? 'Edit Product' : 'Add Product' ?></h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
<div class="card shadow-sm" style="max-width:820px"><div class="card-body">
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="product_id" value="<?= $id ?>">
  <div class="row g-3">
    <div class="col-md-8"><label class="form-label">Product Name</label>
      <input type="text" name="product_name" class="form-control" value="<?= e($p['product_name']) ?>" required></div>
    <div class="col-md-4"><label class="form-label">Category</label>
      <select name="category_id" class="form-select" required>
        <option value="">Select</option>
        <?php foreach ($cats as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= $p['category_id'] == $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-12"><label class="form-label">Description</label>
      <textarea name="description" rows="4" class="form-control"><?= e($p['description']) ?></textarea></div>
    <div class="col-md-4"><label class="form-label">Price (₹)</label>
      <input type="number" step="0.01" min="0.01" name="price" class="form-control" value="<?= e($p['price']) ?>" required></div>
    <div class="col-md-4"><label class="form-label">Stock</label>
      <input type="number" min="0" name="stock" class="form-control" value="<?= e($p['stock']) ?>" required></div>
    <div class="col-md-4"><label class="form-label">Image (JPG/PNG/WEBP, max 2 MB)</label>
      <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="form-control"></div>
    <?php if ($id): ?><div class="col-12"><span class="small text-muted d-block mb-1">Current image</span>
      <img src="<?= e(product_img($p['image'])) ?>" height="90" class="border rounded p-1 bg-white" alt=""></div><?php endif; ?>
  </div>
  <div class="mt-4"><button class="btn btn-dark">Save Product</button> <a href="products.php" class="btn btn-outline-secondary">Cancel</a></div>
</form>
</div></div>
<?php require_once '../includes/admin_footer.php'; ?>
