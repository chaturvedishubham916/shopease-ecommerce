<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$_SESSION = [];
session_destroy();
session_start();
flash('info', 'You have been logged out.');
redirect(BASE_URL . 'index.php');
