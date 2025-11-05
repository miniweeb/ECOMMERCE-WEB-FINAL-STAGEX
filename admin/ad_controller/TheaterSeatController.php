<<?php
/**
 * Controller for managing theatres and seats in the admin area.
 *
 * Administrators can create new theatres with a specified number of rows
 * and columns, add seat categories, delete theatres or categories and
 * assign categories to ranges of seats.  The resulting seat map is
 * displayed for the selected theatre.
 */
namespace App\Controllers;




class TheaterSeatController extends AdBaseController
{
    /**
     * Display and handle actions for theatre and seat management.
     */
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
                // Accept a numeric base price.  Treat empty or non‑positive as invalid.
                $price = isset($_POST['category_price']) ? (float)$_POST['category_price'] : 0;
                if ($name && $price >= 0) {
                    // Prevent duplicate seat categories with the same base price.  If such
                    // a category already exists, set an error and do not create a new one.
                    if ($seatCatModel->findByPrice($price)) {
                        $_SESSION['error'] = 'Hạng giá ghế này đã tồn tại, Hãy tạo một hạng ghế với giá mới';
                    } else {
                        // Generate a random six‑digit hexadecimal colour code for the new seat category.
                        // We avoid using predefined Bootstrap classes here to ensure that each
                        // newly created seat category has a unique colour.  The colour is stored
                        // without a leading '#' to remain a valid CSS class name if needed.
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
                // Update an existing seat category
                $cid   = (int)($_POST['category_id'] ?? 0);
                $cname = trim($_POST['category_name'] ?? '');
                $cprice= isset($_POST['category_price']) ? (float)$_POST['category_price'] : 0;
                if ($cid > 0 && $cname !== '' && $cprice >= 0) {
                    // Fetch current category to preserve colour
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
                // Preserve backward compatibility: update only the theatre name.
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
                // Update theatre name and adjust rows/columns in a single action
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
                // Redirect back to the same theatre page with both tid and edit_tid
                $this->redirect('index.php?pg=admin-theater-seat' . ($tid ? '&tid=' . $tid . '&edit_tid=' . $tid : ''));
                return;
            }




            if ($type === 'theater_approve') {
                // Approve a theatre after validating that no entire rows or columns are empty.
                $tid = (int)($_POST['theater_id'] ?? 0);
                if ($tid > 0) {
                    $seatModel = new \App\Models\Seat();
                    $allSeats = $seatModel->seatsForTheater($tid);
                    $hasEmptyRow = false;
                    $hasEmptyCol = false;
                    $rows = [];
                    $cols = [];
                    // Organise seats by row and col to detect empties
                    foreach ($allSeats as $s) {
                        $row = $s['row_char'];
                        $col = (int)$s['seat_number'];
                        // Track if this seat has a category
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
                    // Check for any row without a seat category
                    foreach ($rows as $rInfo) {
                        if (!$rInfo['hasCat']) {
                            $hasEmptyRow = true;
                            break;
                        }
                    }
                    // Check for any column without a seat category
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
                // After approval, stay on the same theatre page to view the updated status and seat map
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
        // Determine if theatres and seat categories can be deleted based on
        // whether they are currently referenced by open performances.  The
        // canDelete() methods return true when deletion is safe.  Annotate
        // each theatre and category with a boolean flag to inform the view.
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
        // Determine selected theatre for seat map
        $selectedId  = isset($_GET['tid']) ? (int)$_GET['tid'] : 0;
        $seatsForMap = [];
        if ($selectedId > 0) {
            $seatModel = new \App\Models\Seat();
            $seatsForMap = $seatModel->seatsForTheater($selectedId);
        }




        // Determine edit category and theatre from query parameters
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
        // Render the theatre & seat view outside of its folder.  The index
        // file has been renamed to ad_theater&seat.php, so remove the
        // "/index" suffix when specifying the view.  This passes along
        // theatres, categories, the selected theatre ID and seat map.
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




?php
/**
 * Controller for managing theatres and seats in the admin area.
 *
 * Administrators can create new theatres with a specified number of rows
 * and columns, add seat categories, delete theatres or categories and
 * assign categories to ranges of seats.  The resulting seat map is
 * displayed for the selected theatre.
 */
namespace App\Controllers;




class TheaterSeatController extends AdBaseController
{
    /**
     * Display and handle actions for theatre and seat management.
     */
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
                // Accept a numeric base price.  Treat empty or non‑positive as invalid.
                $price = isset($_POST['category_price']) ? (float)$_POST['category_price'] : 0;
                if ($name && $price >= 0) {
                    // Prevent duplicate seat categories with the same base price.  If such
                    // a category already exists, set an error and do not create a new one.
                    if ($seatCatModel->findByPrice($price)) {
                        $_SESSION['error'] = 'Hạng giá ghế này đã tồn tại, Hãy tạo một hạng ghế với giá mới';
                    } else {
                        // Generate a random six‑digit hexadecimal colour code for the new seat category.
                        // We avoid using predefined Bootstrap classes here to ensure that each
                        // newly created seat category has a unique colour.  The colour is stored
                        // without a leading '#' to remain a valid CSS class name if needed.
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
                // Update an existing seat category
                $cid   = (int)($_POST['category_id'] ?? 0);
                $cname = trim($_POST['category_name'] ?? '');
                $cprice= isset($_POST['category_price']) ? (float)$_POST['category_price'] : 0;
                if ($cid > 0 && $cname !== '' && $cprice >= 0) {
                    // Fetch current category to preserve colour
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
                // Preserve backward compatibility: update only the theatre name.
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
                // Update theatre name and adjust rows/columns in a single action
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
                // Redirect back to the same theatre page with both tid and edit_tid
                $this->redirect('index.php?pg=admin-theater-seat' . ($tid ? '&tid=' . $tid . '&edit_tid=' . $tid : ''));
                return;
            }




            if ($type === 'theater_approve') {
                // Approve a theatre after validating that no entire rows or columns are empty.
                $tid = (int)($_POST['theater_id'] ?? 0);
                if ($tid > 0) {
                    $seatModel = new \App\Models\Seat();
                    $allSeats = $seatModel->seatsForTheater($tid);
                    $hasEmptyRow = false;
                    $hasEmptyCol = false;
                    $rows = [];
                    $cols = [];
                    // Organise seats by row and col to detect empties
                    foreach ($allSeats as $s) {
                        $row = $s['row_char'];
                        $col = (int)$s['seat_number'];
                        // Track if this seat has a category
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
                    // Check for any row without a seat category
                    foreach ($rows as $rInfo) {
                        if (!$rInfo['hasCat']) {
                            $hasEmptyRow = true;
                            break;
                        }
                    }
                    // Check for any column without a seat category
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
                // After approval, stay on the same theatre page to view the updated status and seat map
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
        // Determine if theatres and seat categories can be deleted based on
        // whether they are currently referenced by open performances.  The
        // canDelete() methods return true when deletion is safe.  Annotate
        // each theatre and category with a boolean flag to inform the view.
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
        // Determine selected theatre for seat map
        $selectedId  = isset($_GET['tid']) ? (int)$_GET['tid'] : 0;
        $seatsForMap = [];
        if ($selectedId > 0) {
            $seatModel = new \App\Models\Seat();
            $seatsForMap = $seatModel->seatsForTheater($selectedId);
        }




        // Determine edit category and theatre from query parameters
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
        // Render the theatre & seat view outside of its folder.  The index
        // file has been renamed to ad_theater&seat.php, so remove the
        // "/index" suffix when specifying the view.  This passes along
        // theatres, categories, the selected theatre ID and seat map.
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





