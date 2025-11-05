<?php
// Database configuration for StageX demo site
// Adjust these values according to your local environment.  The SQL dump provided
// with this exercise defines a database named `stagex_db` with tables for
// shows, performances, seats, bookings, tickets and users.  Create this
// database in phpMyAdmin or via the command line and import the SQL file
// before running the site.


define('DB_HOST', 'localhost');
define('DB_NAME', 'stagex_db');
define('DB_USER', 'root');
define('DB_PASS', '');


// Base URL for generating links.  Use an empty string to build
// relative paths such as "index.php?pg=register".  This avoids
// environment-specific issues when the application is served from a
// subfolder like /public.  If you need to force a specific base
// prefix, set BASE_URL here (e.g. '/public/').
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}


// Start the session early so controllers and views can access
// user information.  Without this call PHP will not send the
// session cookie and any login/logout logic will fail.
// Set default timezone for all date/time operations.  Without this,
// calls to date() may use the server's UTC timezone which can
// cause VNPay to reject requests due to time drift.  VNPay's
// sandbox expects times in Asia/Ho_Chi_Minh.
date_default_timezone_set('Asia/Ho_Chi_Minh');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// On the first page load of a new session, clear any persisted user data.  Without this
// reset the application may appear to "auto login" using stale session files from
// previous runs.  The 'initialized' flag ensures this cleanup happens only once per
// new session and does not log out users repeatedly during normal navigation.
if (!isset($_SESSION['initialized'])) {
    unset($_SESSION['user']);
    unset($_SESSION['success'], $_SESSION['error'], $_SESSION['info']);
    $_SESSION['initialized'] = true;
}

