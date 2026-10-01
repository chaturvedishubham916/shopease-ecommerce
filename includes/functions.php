<?php
// Shared helper functions

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirect($url) { header('Location: ' . $url); exit; }

function money($n) { return '₹' . number_format((float)$n, 2); }

function is_logged_in() { return isset($_SESSION['user_id']); }

function flash($type, $msg) { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }

function show_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show" role="alert">'
           . e($f['msg'])
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        flash('warning', 'Please log in to continue.');
        redirect(BASE_URL . 'login.php');
    }
}

// CSRF protection for all POST forms
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check() {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function cart_count($conn) {
    if (!is_logged_in()) return 0;
    $stmt = $conn->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($n);
    $stmt->fetch();
    $stmt->close();
    return (int)$n;
}

function product_img($file) {
    $path = __DIR__ . '/../uploads/products/' . $file;
    if ($file && $file !== 'no-image.png' && file_exists($path)) {
        return BASE_URL . 'uploads/products/' . rawurlencode($file);
    }
    return BASE_URL . 'assets/images/no-image.png';
}

// Safe "go back" URL: only returns the referrer if it belongs to this site
function back_url($default) {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref && (parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')
        || parse_url($ref, PHP_URL_HOST) === explode(':', $_SERVER['HTTP_HOST'] ?? '')[0])) {
        return $ref;
    }
    return $default;
}

function stars($rating) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<i class="bi ' . ($rating >= $i ? 'bi-star-fill' : ($rating >= $i - 0.5 ? 'bi-star-half' : 'bi-star')) . ' text-warning"></i>';
    }
    return $out;
}
