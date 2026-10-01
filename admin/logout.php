<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
unset($_SESSION['admin_id'], $_SESSION['admin_name']);
session_regenerate_id(true);
flash('info', 'Admin logged out.');
redirect(BASE_URL . 'admin/login.php');
