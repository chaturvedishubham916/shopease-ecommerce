<?php
$page_title = 'My Profile';
require_once 'includes/header.php';
require_login();
$uid = (int)$_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = 'Invalid request. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'profile') {
        $name    = trim($_POST['full_name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city    = trim($_POST['city'] ?? '');
        $pin     = trim($_POST['pincode'] ?? '');

        if (strlen($name) < 3)                        $errors[] = 'Name must be at least 3 characters.';
        if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) $errors[] = 'Enter a valid 10-digit mobile number.';
        if ($pin !== '' && !preg_match('/^[0-9]{6}$/', $pin)) $errors[] = 'Pincode must be 6 digits.';

        if (!$errors) {
            $stmt = $conn->prepare('UPDATE users SET full_name = ?, phone = ?, address = ?, city = ?, pincode = ? WHERE user_id = ?');
            $stmt->bind_param('sssssi', $name, $phone, $address, $city, $pin, $uid);
            $stmt->execute();
            $stmt->close();
            $_SESSION['user_name'] = $name;
            flash('success', 'Profile updated.');
            redirect(BASE_URL . 'profile.php');
        }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $old = $_POST['old'] ?? '';
        $new = $_POST['new'] ?? '';
        $cnf = $_POST['confirm'] ?? '';

        $stmt = $conn->prepare('SELECT password FROM users WHERE user_id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $hash = $stmt->get_result()->fetch_assoc()['password'];
        $stmt->close();

        if (!password_verify($old, $hash)) $errors[] = 'Current password is incorrect.';
        if (strlen($new) < 6)              $errors[] = 'New password must be at least 6 characters.';
        if ($new !== $cnf)                 $errors[] = 'New passwords do not match.';

        if (!$errors) {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
            $stmt->bind_param('si', $newHash, $uid);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Password changed.');
            redirect(BASE_URL . 'profile.php');
        }
    }
}

$stmt = $conn->prepare('SELECT full_name, email, phone, address, city, pincode FROM users WHERE user_id = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<h3 class="mb-3">My Profile</h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card"><div class="card-body">
      <h5>Personal Details</h5>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="profile">
        <div class="mb-3"><label class="form-label">Email</label>
          <input type="text" class="form-control" value="<?= e($u['email']) ?>" disabled></div>
        <div class="mb-3"><label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" value="<?= e($u['full_name']) ?>" required></div>
        <div class="mb-3"><label class="form-label">Mobile</label>
          <input type="text" name="phone" maxlength="10" class="form-control" value="<?= e($u['phone']) ?>" required></div>
        <div class="mb-3"><label class="form-label">Address</label>
          <textarea name="address" rows="2" class="form-control"><?= e($u['address']) ?></textarea></div>
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label">City</label>
            <input type="text" name="city" class="form-control" value="<?= e($u['city']) ?>"></div>
          <div class="col-md-6 mb-3"><label class="form-label">Pincode</label>
            <input type="text" name="pincode" maxlength="6" class="form-control" value="<?= e($u['pincode']) ?>"></div>
        </div>
        <button class="btn btn-dark">Save Changes</button>
      </form>
    </div></div>
  </div>
  <div class="col-lg-5">
    <div class="card"><div class="card-body">
      <h5>Change Password</h5>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="password">
        <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="old" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Confirm New Password</label><input type="password" name="confirm" class="form-control" required></div>
        <button class="btn btn-outline-dark">Update Password</button>
      </form>
    </div></div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
