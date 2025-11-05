<?php
// Front controller for StageX admin portal.  This file routes requests
// to the appropriate administrative actions.  It operates separately
// from the public front controller to keep admin and customer code
// paths distinct.


// Load configuration and initialise the environment (database, session,
// timezone).  This file lives one directory above the public folder,
// so reference config via the parent path.
require_once __DIR__ . '/../config/config.php';


// Autoload models and base controllers from the app folder.  The admin
// controller itself is located in admin/ad_controller and therefore
// is included manually below.
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


// Manually include admin controllers.  The admin controllers live
// inside admin/ad_controller and are not loaded by the default
// autoloader because they sit outside of the app/ namespace.  Each
// controller extends the AdBaseController defined in ad_controller.
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


// Determine which admin page to show based on the `pg` query parameter.  If
// none is provided default to the dashboard (admin-index).  The naming
// convention matches the routes that previously existed in the public
// index.  New pages should be added here and implemented in
// AdminController.
$pg = $_GET['pg'] ?? 'admin-index';


switch ($pg) {
    case 'admin-index':
    case 'admin-dashboard':
        // Both admin-index and admin-dashboard resolve to the dashboard
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
        // Unknown admin route: fall back to dashboard
        (new DashboardController())->index();
        break;
}



