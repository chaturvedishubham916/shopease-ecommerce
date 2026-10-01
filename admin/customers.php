<?php
$page_title = 'Customers';
require_once '../includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['user_id'] ?? 0);
    if (!csrf_check()) {
        flash('danger', 'Invalid request.');
    } else {
        $stmt = $conn->prepare('UPDATE users SET status = 1 - status WHERE user_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        flash('success', 'Customer status updated.');
    }
    redirect($A . 'customers.php');
}

$users = $conn->query('SELECT u.user_id, u.full_name, u.email, u.phone, u.status, u.created_at,
                       (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id) AS orders
                       FROM users u ORDER BY u.user_id DESC')->fetch_all(MYSQLI_ASSOC);
?>
<h3 class="mb-3">Customers</h3>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Joined</th><th>Orders</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= (int)$u['user_id'] ?></td><td><?= e($u['full_name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['phone']) ?></td>
      <td><?= date('d M Y', strtotime($u['created_at'])) ?></td><td><?= (int)$u['orders'] ?></td>
      <td><span class="badge bg-<?= $u['status'] ? 'success' : 'danger' ?>"><?= $u['status'] ? 'Active' : 'Blocked' ?></span></td>
      <td>
        <form method="post" onsubmit="return confirm('Change this customer\'s status?');">
          <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
          <button class="btn btn-sm <?= $u['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= $u['status'] ? 'Block' : 'Unblock' ?></button>
        </form>
      </td>
    </tr>
  <?php endforeach; if (!$users): ?><tr><td colspan="8" class="text-center text-muted py-4">No customers yet.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<?php require_once '../includes/admin_footer.php'; ?>
