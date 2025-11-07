<?php
require_once __DIR__ . '/../config/config.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});


require_once __DIR__ . '/ad_controller/AdBaseController.php';
require_once __DIR__ . '/ad_controller/DashboardController.php';
require_once __DIR__ . '/ad_controller/TransactionsController.php';
require_once __DIR__ . '/ad_controller/CategoryShowController.php';
require_once __DIR__ . '/ad_controller/TheaterSeatController.php';
require_once __DIR__ . '/ad_controller/PerformanceAdminController.php';
require_once __DIR__ . '/ad_controller/ReviewsController.php';
require_once __DIR__ . '/ad_controller/AccountsController.php';
require_once __DIR__ . '/ad_controller/ProfileAdminController.php';


use App\Controllers\DashboardController;
use App\Controllers\TransactionsController;
use App\Controllers\CategoryShowController;
use App\Controllers\TheaterSeatController;
use App\Controllers\PerformanceAdminController;
use App\Controllers\ReviewsController;
use App\Controllers\AccountsController;
use App\Controllers\ProfileAdminController;


$pg = $_GET['pg'] ?? 'admin-index';


switch ($pg) {
    case 'admin-index':
    case 'admin-dashboard':
        (new DashboardController())->index();
        break;
    case 'admin-transactions':
        (new TransactionsController())->index();
        break;
    case 'admin-category-show':
        (new CategoryShowController())->index();
        break;
    case 'admin-theater-seat':
        (new TheaterSeatController())->index();
        break;
    case 'admin-performance':
        (new PerformanceAdminController())->index();
        break;
    case 'admin-reviews':
        (new ReviewsController())->index();
        break;
    case 'admin-accounts':
        (new AccountsController())->index();
        break;
    case 'admin-profile':
        (new ProfileAdminController())->index();
        break;
    case 'admin-profile-reset-password':
        (new ProfileAdminController())->resetPassword();
        break;
    default:
        (new DashboardController())->index();
        break;
}



