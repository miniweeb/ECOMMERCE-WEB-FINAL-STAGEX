<?php
namespace App\Controllers;




class PerformanceAdminController extends AdBaseController
{
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
                
                $end       = trim($_POST['end_time'] ?? '');
                $price     = (float)($_POST['price'] ?? 0);
                if ($showId > 0 && $theaterId > 0 && $date && $start && $price > 0) {
                    
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
        
        $editPerformance = null;
        if (isset($_GET['edit_id'])) {
            $editId = (int)$_GET['edit_id'];
            if ($editId > 0) {
                $editPerformance = $perfModel->find($editId);
            }
        }
        $performances = $perfModel->all();
        $shows        = $showModel->all();
        
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
        
        $this->renderAdmin('ad_performance', [
            'performances'    => $performances,
            'shows'           => $shows,
            'theaters'        => $theaters,
            'editPerformance' => $editPerformance
        ]);
    }
}





