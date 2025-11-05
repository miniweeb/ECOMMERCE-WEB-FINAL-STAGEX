<?php
/**
 * Controller for managing performances in the admin area.
 *
 * Administrators can create, delete and update performances.  New
 * performances require a show, theatre, date, start time and price.  End
 * time is optional; if omitted the duration of the selected show will
 * determine the end time.  Status updates allow administrators to
 * cancel or complete scheduled performances.
 */
namespace App\Controllers;




class PerformanceAdminController extends AdBaseController
{
    /**
     * Display and handle performance management actions.
     */
    public function index(): void
    {
        if (!$this->ensureAdmin()) return;
        $perfModel   = new \App\Models\PerformanceModel();
        $showModel   = new \App\Models\Show();
        $theaterModel= new \App\Models\Theater();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? '';
            if ($type === 'performance_add') {
                $showId    = (int)($_POST['show_id'] ?? 0);
                $theaterId = (int)($_POST['theater_id'] ?? 0);
                $date      = trim($_POST['performance_date'] ?? '');
                $start     = trim($_POST['start_time'] ?? '');
                // End time is optional; if omitted the model will compute it
                $end       = trim($_POST['end_time'] ?? '');
                $price     = (float)($_POST['price'] ?? 0);
                if ($showId > 0 && $theaterId > 0 && $date && $start && $price > 0) {
                    // Validate that the performance date is in the future (strictly greater than today).
                    // Convert both the provided date and today's date to timestamps at midnight to
                    // compare only the dates (ignoring time).  If the performance date is today
                    // or earlier, reject the creation.
                    $perfDate = strtotime($date);
                    $todayDate = strtotime(date('Y-m-d'));
                    if ($perfDate !== false && $perfDate > $todayDate) {
                        if ($perfModel->create($showId, $theaterId, $date, $start, $end, $price)) {
                            $_SESSION['success'] = 'Đã thêm suất diễn.';
                        } else {
                            $_SESSION['error'] = 'Không thể thêm suất diễn.';
                        }
                    } else {
                        $_SESSION['error'] = 'Ngày diễn phải lớn hơn ngày hiện tại.';
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin suất diễn.';
                }
                $this->redirect('index.php?pg=admin-performance');
                return;
            }
            if ($type === 'performance_delete') {
                $id = (int)($_POST['performance_id'] ?? 0);
                if ($id > 0) {
                    if ($perfModel->delete($id)) {
                        $_SESSION['success'] = 'Đã xóa suất diễn.';
                    } else {
                        $_SESSION['error'] = 'Không thể xóa suất diễn.';
                    }
                }
                $this->redirect('index.php?pg=admin-performance');
                return;
            }
            if ($type === 'performance_update') {
                $id     = (int)($_POST['performance_id'] ?? 0);
                $status = trim($_POST['status'] ?? '');
                if ($id > 0 && $status) {
                    if ($perfModel->updateStatus($id, $status)) {
                        $_SESSION['success'] = 'Đã cập nhật suất diễn.';
                    } else {
                        $_SESSION['error'] = 'Không thể cập nhật suất diễn.';
                    }
                }
                $this->redirect('index.php?pg=admin-performance');
                return;
            }
        }
        // Determine if editing an existing performance
        $editPerformance = null;
        if (isset($_GET['edit_id'])) {
            $editId = (int)$_GET['edit_id'];
            if ($editId > 0) {
                $editPerformance = $perfModel->find($editId);
            }
        }
        $performances = $perfModel->all();
        $shows        = $showModel->all();
        // Only display theatres that have been approved.  Pending
        // theatres (status "Chờ xử lý") are excluded from the drop‑down
        // when creating a new performance.  This prevents admins from
        // scheduling performances at incomplete venues.
        $theatersRaw = $theaterModel->all();
        $theaters    = [];
        foreach ($theatersRaw as $th) {
            if (($th['status'] ?? '') === 'Đã hoạt động') {
                $theaters[] = $th;
            }
        }
        usort($performances, function ($a, $b) {
            return ($a['performance_id'] ?? 0) <=> ($b['performance_id'] ?? 0);
        });
        usort($shows, function ($a, $b) {
            return ($a['show_id'] ?? 0) <=> ($b['show_id'] ?? 0);
        });
        usort($theaters, function ($a, $b) {
            return ($a['theater_id'] ?? 0) <=> ($b['theater_id'] ?? 0);
        });
        // Render the performance view outside of its folder.  The index file
        // has been renamed to ad_performance.php, so omit the trailing
        // "/index" when specifying the view path.
        $this->renderAdmin('ad_performance', [
            'performances'    => $performances,
            'shows'           => $shows,
            'theaters'        => $theaters,
            'editPerformance' => $editPerformance
        ]);
    }
}





