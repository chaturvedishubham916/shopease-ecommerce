<?php
ob_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!empty($_SESSION['admin_id'])) redirect(BASE_URL . 'admin/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $stmt = $conn->prepare('SELECT admin_id, username, password FROM admin WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $a = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($a && password_verify($_POST['password'] ?? '', $a['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $a['admin_id'];
            $_SESSION['admin_name'] = $a['username'];
            redirect(BASE_URL . 'admin/index.php');
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login | <?= SITE_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center min-vh-100">
<div class="container" style="max-width:420px">
  <div class="card shadow">
    <div class="card-body p-4">
      <h4 class="text-center mb-3"><i class="bi bi-shield-lock-fill text-warning"></i> Admin Login</h4>
      <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required autofocus></div>
        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <button class="btn btn-dark w-100">Login</button>
      </form>
      <div class="text-center mt-3"><a href="<?= BASE_URL ?>index.php" class="small">&larr; Back to shop</a></div>
    </div>
  </div>
</div>
</body>
</html>
