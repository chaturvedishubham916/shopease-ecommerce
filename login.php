<?php
$page_title = 'Login';
require_once 'includes/header.php';

if (is_logged_in()) redirect(BASE_URL . 'index.php');

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare('SELECT user_id, full_name, password, status FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['status']) {
                $error = 'Your account has been blocked. Please contact support.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];

                // Only redirect to pages inside this site
                $to = $_SESSION['redirect_to'] ?? '';
                unset($_SESSION['redirect_to']);
                if (strpos($to, BASE_URL) !== 0) $to = BASE_URL . 'index.php';
                redirect($to);
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<div class="card auth-card shadow-sm">
  <div class="card-body p-4">
    <h4 class="mb-3">Welcome back</h4>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required></div>
      <div class="mb-3"><label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-dark w-100">Login</button>
    </form>
    <p class="text-center mt-3 mb-0 small">New here? <a href="register.php">Create an account</a></p>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
