<?php
ob_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Every admin page includes this file, so it also acts as the admin login guard
if (empty($_SESSION['admin_id'])) redirect(BASE_URL . 'admin/login.php');

$A   = BASE_URL . 'admin/';
$cur = basename($_SERVER['SCRIPT_NAME']);
$nav = [
    'index.php'      => ['Dashboard', 'bi-speedometer2'],
    'products.php'   => ['Products', 'bi-box-seam'],
    'categories.php' => ['Categories', 'bi-tags'],
    'orders.php'     => ['Orders', 'bi-receipt'],
    'customers.php'  => ['Customers', 'bi-people'],
    'reports.php'    => ['Reports', 'bi-graph-up'],
];
$parentOf = ['product_form.php' => 'products.php', 'order_view.php' => 'orders.php'];
$active = $parentOf[$cur] ?? $cur;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title ?? 'Admin') ?> | <?= SITE_NAME ?> Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
  <style>@media print { .no-print { display: none !important; } }</style>
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark no-print">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" href="<?= $A ?>index.php"><i class="bi bi-shield-lock-fill text-warning"></i> <?= SITE_NAME ?> Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#anav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="anav">
      <ul class="navbar-nav me-auto">
        <?php foreach ($nav as $file => [$label, $icon]): ?>
          <li class="nav-item"><a class="nav-link <?= $active === $file ? 'active fw-semibold text-warning' : '' ?>" href="<?= $A . $file ?>"><i class="bi <?= $icon ?>"></i> <?= $label ?></a></li>
        <?php endforeach; ?>
      </ul>
      <a class="btn btn-outline-light btn-sm me-2" href="<?= BASE_URL ?>index.php" target="_blank">View Site</a>
      <a class="btn btn-warning btn-sm" href="<?= $A ?>logout.php">Logout (<?= e($_SESSION['admin_name']) ?>)</a>
    </div>
  </div>
</nav>
<main class="container-fluid px-4 my-4 flex-grow-1">
<?php show_flash(); ?>
