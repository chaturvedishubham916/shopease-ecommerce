<?php
ob_start();   // allows redirects after the header has been included
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
$cartCount = cart_count($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title ?? 'Home') ?> | <?= SITE_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>index.php"><i class="bi bi-bag-heart-fill text-warning"></i> <?= SITE_NAME ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <form class="d-flex mx-lg-4 flex-grow-1 my-2 my-lg-0" action="<?= BASE_URL ?>index.php" method="get">
        <input class="form-control me-2" type="search" name="q" placeholder="Search products..." value="<?= e($_GET['q'] ?? '') ?>">
        <button class="btn btn-warning" type="submit"><i class="bi bi-search"></i></button>
      </form>
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php">Home</a></li>
        <?php if (is_logged_in()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>wishlist.php"><i class="bi bi-heart"></i> Wishlist</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>orders.php">My Orders</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>cart.php"><i class="bi bi-cart3"></i> Cart
            <span class="badge bg-warning text-dark"><?= $cartCount ?></span></a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i> <?= e(explode(' ', $_SESSION['user_name'])[0]) ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php">My Profile</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>logout.php">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-warning btn-sm ms-lg-2" href="<?= BASE_URL ?>register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container my-4 flex-grow-1">
<?php show_flash(); ?>
