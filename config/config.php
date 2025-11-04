<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'stagex_db');
define('DB_USER', 'root');
define('DB_PASS', '');

if (!defined('BASE_URL')) 
    {
    define('BASE_URL', '');}

date_default_timezone_set('Asia/Ho_Chi_Minh');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['initialized'])) {
    unset($_SESSION['user']);
    unset($_SESSION['success'], $_SESSION['error'], $_SESSION['info']);
    $_SESSION['initialized'] = true;
}