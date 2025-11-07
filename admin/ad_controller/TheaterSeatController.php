<?php
namespace App\Controllers;




class TheaterSeatController extends AdBaseController
{
    public function index(): void
    {
        if (!$this->ensureAdmin()) return;
        $theaterModel = new \App\Models\Theater();
        $seatCatModel = new \App\Models\SeatCategory();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? '';
            if ($type === 'theater_create') {
                $name = trim($_POST['theater_name'] ?? '');
                $rows = (int)($_POST['rows'] ?? 0);
                $cols = (int)($_POST['cols'] ?? 0);
                if ($name && $rows > 0 && $cols > 0) {
                    if ($theaterModel->create($name, $rows, $cols)) {
                        $_SESSION['success'] = 'Đã tạo rạp và sơ đồ ghế.';
                    } else {
                        $_SESSION['error'] = 'Không thể tạo rạp.';
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng nhập tên rạp, số hàng và số cột.';
                }
                $this->redirect('index.php?pg=admin-theater-seat');
                return;
            }
            if ($type === 'theater_add') {
                $name     = trim($_POST['theater_name'] ?? '');
                $capacity = (int)($_POST['theater_capacity'] ?? 0);
                if ($name && $capacity > 0) {
                    $rows = (int)floor(sqrt($capacity));
                    $cols = (int)ceil($capacity / max(1, $rows));
                    if ($theaterModel->create($name, $rows, $cols)) {
                        $_SESSION['success'] = 'Đã thêm rạp mới.';
                    } else {
                        $_SESSION['error'] = 'Không thể thêm rạp.';
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng nhập tên rạp và tổng ghế.';
                }
                $this->redirect('index.php?pg=admin-theater-seat');
                return;
            }
            if ($type === 'theater_delete') {
                $id = (int)($_POST['theater_id'] ?? 0);
                if ($id > 0) {
                    if ($theaterModel->delete($id)) {
                        $_SESSION['success'] = 'Đã xóa rạp.';
                    } else {
                        $_SESSION['error'] = 'Không thể xóa rạp.';
                    }
                }
                $this->redirect('index.php?pg=admin-theater-seat');
                return;
            }
            if ($type === 'category_add') {
                $name  = trim($_POST['category_name'] ?? '');
                
                $price = isset($_POST['category_price']) ? (float)$_POST['category_price'] : 0;
                if ($name && $price >= 0) {
                    
                    if ($seatCatModel->findByPrice($price)) {
                        $_SESSION['error'] = 'Hạng giá ghế này đã tồn tại, Hãy tạo một hạng ghế với giá mới';
                    } else {
                        
                        try {
                            $randomInt = random_int(0, 0xFFFFFF);
                        } catch (\Exception $ex) {
                            $randomInt = mt_rand(0, 0xFFFFFF);
                        }
                        $color = strtolower(sprintf('%06X', $randomInt));
                        if ($seatCatModel->create($name, $price, $color)) {
                            $_SESSION['success'] = 'Đã thêm hạng ghế.';
                        } else {
                            $_SESSION['error'] = 'Không thể thêm hạng ghế.';
                        }
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin hạng ghế.';
                }
                $this->redirect('index.php?pg=admin-theater-seat');
                return;
            }
            if ($type === 'category_update') {
                
                $cid   = (int)($_POST['category_id'] ?? 0);
                $cname = trim($_POST['category_name'] ?? '');
                $cprice= isset($_POST['category_price']) ? (float)$_POST['category_price'] : 0;
                if ($cid > 0 && $cname !== '' && $cprice >= 0) {
                    
                    $catRow = $seatCatModel->find($cid);
                    if ($catRow) {
                        $color = $catRow['color_class'] ?? '';
                        if ($seatCatModel->update($cid, $cname, $cprice, $color)) {
                            $_SESSION['success'] = 'Đã cập nhật hạng ghế.';
                        } else {
                            $_SESSION['error'] = 'Không thể cập nhật hạng ghế.';
                        }
                    } else {
                        $_SESSION['error'] = 'Hạng ghế không tồn tại.';
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng nhập tên và giá hạng ghế.';
                }
                $this->redirect('index.php?pg=admin-theater-seat');
                return;
            }




            if ($type === 'theater_update') {
                
                $tid  = (int)($_POST['theater_id'] ?? 0);
                $tname= trim($_POST['theater_name'] ?? '');
                if ($tid > 0 && $tname) {
                    if ($theaterModel->update($tid, $tname)) {
                        $_SESSION['success'] = 'Đã cập nhật tên rạp.';
                    } else {
                        $_SESSION['error'] = 'Không thể cập nhật tên rạp.';
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng nhập tên rạp.';
                }
                $this->redirect('index.php?pg=admin-theater-seat');
                return;
            }




            if ($type === 'theater_modify') {
                
                $tid      = (int)($_POST['theater_id'] ?? 0);
                $tname    = trim($_POST['theater_name'] ?? '');
                $addRows  = (int)($_POST['add_rows'] ?? 0);
                $addCols  = (int)($_POST['add_cols'] ?? 0);
                if ($tid > 0) {
                    if ($theaterModel->modify($tid, $tname, $addRows, $addCols)) {
                        $_SESSION['success'] = 'Đã cập nhật rạp.';
                    } else {
                        $_SESSION['error'] = 'Không thể cập nhật rạp.';
                    }
                } else {
                    $_SESSION['error'] = 'Rạp không hợp lệ.';
                }
                
                $this->redirect('index.php?pg=admin-theater-seat' . ($tid ? '&tid=' . $tid . '&edit_tid=' . $tid : ''));
                return;
            }




            if ($type === 'theater_approve') {
                
                $tid = (int)($_POST['theater_id'] ?? 0);
                if ($tid > 0) {
                    $seatModel = new \App\Models\Seat();
                    $allSeats = $seatModel->seatsForTheater($tid);
                    $hasEmptyRow = false;
                    $hasEmptyCol = false;
                    $rows = [];
                    $cols = [];
                    
                    foreach ($allSeats as $s) {
                        $row = $s['row_char'];
                        $col = (int)$s['seat_number'];
                        
                        $seatHasCat = isset($s['category_id']) && $s['category_id'] !== null;
                        if (!isset($rows[$row])) {
                            $rows[$row] = ['hasCat' => false];
                        }
                        if (!isset($cols[$col])) {
                            $cols[$col] = ['hasCat' => false];
                        }
                        if ($seatHasCat) {
                            $rows[$row]['hasCat'] = true;
                            $cols[$col]['hasCat'] = true;
                        }
                    }
                   
                    foreach ($rows as $rInfo) {
                        if (!$rInfo['hasCat']) {
                            $hasEmptyRow = true;
                            break;
                        }
                    }
                   
                    if (!$hasEmptyRow) {
                        foreach ($cols as $cInfo) {
                            if (!$cInfo['hasCat']) {
                                $hasEmptyCol = true;
                                break;
                            }
                        }
                    }
                    if ($hasEmptyRow || $hasEmptyCol) {
                        $_SESSION['error'] = 'Không thể phê duyệt rạp có hàng/cột dư thừa.';
                    } else {
                        if ($theaterModel->approve($tid)) {
                            $_SESSION['success'] = 'Phê duyệt rạp thành công.';
                        } else {
                            $_SESSION['error'] = 'Không thể phê duyệt rạp.';
                        }
                    }
                }
                
                $this->redirect('index.php?pg=admin-theater-seat' . ($tid ? '&tid=' . $tid . '&edit_tid=' . $tid : ''));
                return;
            }




            if ($type === 'category_delete') {
                $id = (int)($_POST['category_id'] ?? 0);
                if ($id > 0) {
                    if ($seatCatModel->delete($id)) {
                        $_SESSION['success'] = 'Đã xóa hạng ghế.';
                    } else {
                        $_SESSION['error'] = 'Không thể xóa hạng ghế.';
                    }
                }
                $this->redirect('index.php?pg=admin-theater-seat');
                return;
            }
            if ($type === 'seat_update') {
                $theaterId  = (int)($_POST['theater_id'] ?? 0);
                $rowChar    = trim($_POST['row_char'] ?? '');
                $startSeat  = (int)($_POST['start_seat'] ?? 0);
                $endSeat    = (int)($_POST['end_seat'] ?? 0);
                $categoryId = (int)($_POST['category_id'] ?? 0);
                if ($theaterId > 0 && $rowChar && $startSeat > 0 && $endSeat > 0) {
                    $seatModel = new \App\Models\Seat();
                    if ($seatModel->updateCategoryRange($theaterId, $rowChar, $startSeat, $endSeat, $categoryId)) {
                        $_SESSION['success'] = 'Đã cập nhật hạng ghế cho các ghế đã chọn.';
                    } else {
                        $_SESSION['error'] = 'Không thể cập nhật ghế.';
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng chọn rạp, hàng và khoảng ghế.';
                }
                $this->redirect('index.php?pg=admin-theater-seat' . ($theaterId ? '&tid=' . $theaterId : ''));
                return;
            }
        }
        $theaters   = $theaterModel->all();
        $categories = $seatCatModel->all();
        
        foreach ($theaters as &$th) {
            $tid = (int)($th['theater_id'] ?? ($th['id'] ?? 0));
            $th['can_delete'] = $theaterModel->canDelete($tid);
        }
        foreach ($categories as &$cat) {
            $cid = (int)($cat['category_id'] ?? 0);
            $cat['can_delete'] = $seatCatModel->canDelete($cid);
        }
        unset($th, $cat);
        usort($theaters, function ($a, $b) {
            return ($a['theater_id'] ?? 0) <=> ($b['theater_id'] ?? 0);
        });
        usort($categories, function ($a, $b) {
            return ($a['category_id'] ?? 0) <=> ($b['category_id'] ?? 0);
        });
        
        $selectedId  = isset($_GET['tid']) ? (int)$_GET['tid'] : 0;
        $seatsForMap = [];
        if ($selectedId > 0) {
            $seatModel = new \App\Models\Seat();
            $seatsForMap = $seatModel->seatsForTheater($selectedId);
        }


        $editCategory = null;
        if (isset($_GET['edit_cid'])) {
            $cidParam = (int)$_GET['edit_cid'];
            if ($cidParam > 0) {
                foreach ($categories as $c) {
                    if ((int)($c['category_id'] ?? 0) === $cidParam) {
                        $editCategory = $c;
                        break;
                    }
                }
            }
        }
        $editTheater = null;
        if (isset($_GET['edit_tid'])) {
            $tidParam = (int)$_GET['edit_tid'];
            if ($tidParam > 0) {
                foreach ($theaters as $t) {
                    if ((int)($t['theater_id'] ?? ($t['id'] ?? 0)) === $tidParam) {
                        $editTheater = $t;
                        break;
                    }
                }
            }
        }
       
        $this->renderAdmin('ad_theater&seat', [
            'theaters'        => $theaters,
            'categories'      => $categories,
            'selectedTheater' => $selectedId,
            'seatsForMap'     => $seatsForMap,
            'editCategory'    => $editCategory,
            'editTheater'     => $editTheater
        ]);
    }
}