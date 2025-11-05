<?php
/**
 * Base class for all admin controllers.
 *
 * The admin area operates separately from the public controllers.  Every
 * admin controller must ensure that the current session belongs to an
 * administrator or staff member before executing its actions.  This base
 * class provides a shared `ensureAdmin` method to perform the role
 * check as well as a `renderAdmin` helper that includes the unified
 * admin layout when rendering views.
 */
namespace App\Controllers;




abstract class AdBaseController extends BaseController
{
    /**
     * Check that the current user is an administrator or staff.  If the
     * session does not contain a user or the user is not authorised, an
     * error message is stored and the user is redirected back to the
     * public home page.  Returning false prevents the calling method
     * from executing further.
     *
     * @return bool true if the user is an admin/staff, false otherwise
     */
    protected function ensureAdmin(): bool
    {
        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            $_SESSION['error'] = 'Bạn không có quyền truy cập trang này.';
            $this->redirect('index.php');
            return false;
        }
        $type = $_SESSION['user']['user_type'] ?? '';
        if ($type !== 'admin' && $type !== 'staff') {
            $_SESSION['error'] = 'Bạn không có quyền truy cập trang này.';
            $this->redirect('index.php');
            return false;
        }
        return true;
    }




    /**
     * Render an admin view using the admin layout.  This method extracts
     * variables from the provided data array and includes the admin
     * layout file.  The admin layout will in turn include the header,
     * sidebar, footer and the requested view file.
     *
     * @param string $view relative path under admin/ad_view (e.g. 'ad_transaction/index')
     * @param array  $data associative array of variables to make available to the view
     */
    protected function renderAdmin(string $view, array $data = []): void
    {
        // Extract variables into local scope for use in the view
        extract($data);
        // Compute the absolute path to the view file.  The admin view
        // directory lives at admin/ad_view next to the ad_controller directory.
        $viewFile = __DIR__ . '/../ad_view/' . $view . '.php';
        // Include the admin layout which will reference $viewFile
        include __DIR__ . '/../ad_view/layout.php';
    }
}





