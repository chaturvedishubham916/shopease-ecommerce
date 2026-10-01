<?php
// Database connection (MySQLi) - included by every page
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';          // XAMPP default is empty
$db_name = 'shopease';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Database connection failed. Please check config/db.php and make sure MySQL is running.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SITE_NAME', 'ShopEase');
define('BASE_URL', '/shopease/');   // change if you place the folder elsewhere
