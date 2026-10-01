<?php
$page_title = 'Categories';
require_once '../includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['category_id'] ?? 0);
    $name   = trim($_POST['category_name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');

    if (!csrf_check()) {
        flash('danger', 'Invalid request.');
    } elseif ($action === 'add' || $action === 'edit') {
        if (strlen($name) < 2) {
            flash('danger', 'Category name is too short.');
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $conn->prepare('INSERT INTO categories (category_name, description) VALUES (?, ?)');
                    $stmt->bind_param('ss', $name, $desc);
                } else {
                    $stmt = $conn->prepare('UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?');
                    $stmt->bind_param('ssi', $name, $desc, $id);
                }
                $stmt->execute();
                $stmt->close();
                flash('success', $action === 'add' ? 'Category added.' : 'Category updated.');
            } catch (mysqli_sql_exception $ex) {
                flash('danger', 'A category with that name already exists.');
            }
        }
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $n = $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        if ($n > 0) {
            flash('warning', 'This category still has ' . $n . ' product(s). Move or delete them first.');
        } else {
            $stmt = $conn->prepare('DELETE FROM categories WHERE category_id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Category deleted.');
        }
    }
    redirect($A . 'categories.php');
}

$cats = $conn->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) AS pcount
                      FROM categories c ORDER BY c.category_name')->fetch_all(MYSQLI_ASSOC);
?>
<h3 class="mb-3">Categories</h3>
<div class="card shadow-sm mb-4"><div class="card-body">
  <h6>Add New Category</h6>
  <form method="post" class="row g-2">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="col-md-4"><input type="text" name="category_name" class="form-control" placeholder="Category name" required></div>
    <div class="col-md-6"><input type="text" name="description" class="form-control" placeholder="Short description (optional)"></div>
    <div class="col-md-2"><button class="btn btn-dark w-100">Add</button></div>
  </form>
</div></div>

<div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
  <thead class="table-light"><tr><th>Name</th><th>Description</th><th>Products</th><th class="text-end">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($cats as $c): $fid = 'f' . (int)$c['category_id']; ?>
    <tr>
      <td><input form="<?= $fid ?>" type="text" name="category_name" class="form-control form-control-sm" value="<?= e($c['category_name']) ?>" required></td>
      <td><input form="<?= $fid ?>" type="text" name="description" class="form-control form-control-sm" value="<?= e($c['description']) ?>"></td>
      <td><?= (int)$c['pcount'] ?></td>
      <td class="text-end">
        <form id="<?= $fid ?>" method="post" class="d-inline">
          <?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
          <button class="btn btn-sm btn-outline-primary">Save</button>
        </form>
        <form method="post" class="d-inline" onsubmit="return confirm('Delete this category?');">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="category_id" value="<?= (int)$c['category_id'] ?>">
          <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div></div>
<?php require_once '../includes/admin_footer.php'; ?>
