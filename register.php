<?php
$page_title = 'Register';
require_once 'includes/header.php';

if (is_logged_in()) redirect(BASE_URL . 'index.php');

$errors = [];
$name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $name     = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if (strlen($name) < 3)                          $errors[] = 'Name must be at least 3 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (!preg_match('/^[6-9][0-9]{9}$/', $phone))   $errors[] = 'Enter a valid 10-digit mobile number.';
        if (strlen($password) < 6)                      $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm)                     $errors[] = 'Passwords do not match.';

        if (!$errors) {
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = 'This email is already registered.';
            $stmt->close();
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (full_name, email, password, phone) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $name, $email, $hash, $phone);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Registration successful! Please log in.');
            redirect(BASE_URL . 'login.php');
        }
    }
}
?>
<div class="card auth-card shadow-sm">
  <div class="card-body p-4">
    <h4 class="mb-3">Create your account</h4>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?= e($name) ?>" required></div>
      <div class="mb-3"><label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required></div>
      <div class="mb-3"><label class="form-label">Mobile Number</label>
        <input type="text" name="phone" maxlength="10" class="form-control" value="<?= e($phone) ?>" required></div>
      <div class="mb-3"><label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Confirm Password</label>
        <input type="password" name="confirm" class="form-control" required></div>
      <button class="btn btn-dark w-100">Register</button>
    </form>
    <p class="text-center mt-3 mb-0 small">Already have an account? <a href="login.php">Login</a></p>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
