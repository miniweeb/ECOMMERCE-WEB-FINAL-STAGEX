<?php

namespace App\Controllers;

class CategoryShowController extends AdBaseController
{
    /**
     * Hiển thị và xử lý các hd lquan đến thể loại và vở diễn.
     */

    public function index(): void
    {
        if (!$this->ensureAdmin()) return;
        // Khởi tạo biến
        $genreModel = new \App\Models\Genre();
        $showModel  = new \App\Models\Show();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? '';
            // Thêm thể loại mới
            if ($type === 'genre_add') {
                $name = trim($_POST['genre_name'] ?? '');
                if ($name) {
                    if ($genreModel->create($name)) {
                        $_SESSION['success'] = 'Thể loại được thêm thành công.';
                    } else {
                        $_SESSION['error'] = 'Không thể thêm thể loại.';
                    }
                } else {
                    $_SESSION['error'] = 'Tên thể loại không được bỏ trống.';
                }
                $this->redirect('index.php?pg=admin-category-show');
                return; //Hướng về trang chính thể loại và vở diễn
            }
            if ($type === 'genre_update') {
                $id   = (int)($_POST['genre_id'] ?? 0);
                $name = trim($_POST['genre_name'] ?? '');
                if ($id > 0 && $name) {
                    if ($genreModel->update($id, $name)) {
                        $_SESSION['success'] = 'Đã cập nhật thể loại.';
                    } else {
                        $_SESSION['error'] = 'Không thể cập nhật thể loại.';
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng nhập tên thể loại.';
                }
                $this->redirect('index.php?pg=admin-category-show');
                return;
            }
            if ($type === 'genre_delete') {
                $id = (int)($_POST['genre_id'] ?? 0);
                if ($id > 0) {
                    if ($genreModel->delete($id)) {
                        $_SESSION['success'] = 'Đã xóa thể loại.';
                    } else {
                        $_SESSION['error'] = 'Không thể xóa thể loại.';
                    }
                }
                $this->redirect('index.php?pg=admin-category-show');
                return;
            }
            if ($type === 'show_add') {
                $title    = trim($_POST['title'] ?? '');
                $desc     = trim($_POST['description'] ?? '');
                $duration = (int)($_POST['duration'] ?? 0);
                $director = trim($_POST['director'] ?? '');
                $poster   = trim($_POST['poster_url'] ?? '');
                // Luôn cài vở diễn mới ở trạng thái sắp chiếu
                // Trạng thái k dc chọn thủ công vì nó được xác định bởi tồn tại&trạng thái of vở diễn ở proc_update_show_statuses().
                $status   = 'Sắp chiếu';
                $genreIds = $_POST['genre_ids'] ?? [];
                if ($title && $duration > 0 && $director && $poster) {
                    $newId = $showModel->create($title, $desc, $duration, $director, $poster, $status);
                    if ($newId) {
                        // Sử dụng thủ tục lưu trữ để gắn thể loại cho vở diễn mới
                        // nhằm tránh truy vấn thủ công trực tiếp.  updateGenres() sẽ
                        // xóa toàn bộ liên kết cũ (không có trong trường hợp tạo mới)
                        // rồi thêm lại theo danh sách genreIds.
                        $showModel->updateGenres($newId, $genreIds);
                        $_SESSION['success'] = 'Đã thêm vở diễn mới.';
                    } else {
                        $_SESSION['error'] = 'Không thể thêm vở diễn.';
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin vở diễn.';
                }
                $this->redirect('index.php?pg=admin-category-show');
                return;
            }
            if ($type === 'show_delete') {
                $id = (int)($_POST['show_id'] ?? 0);
                if ($id > 0) {
                    if ($showModel->delete($id)) {
                        $_SESSION['success'] = 'Đã xóa vở diễn.';
                    } else {
                        $_SESSION['error'] = 'Không thể xóa vở diễn.';
                    }
                }
                $this->redirect('index.php?pg=admin-category-show');
                return;
            }

            if ($type === 'show_update') {
                $showId    = (int)($_POST['show_id'] ?? 0);
                $title     = trim($_POST['title'] ?? '');
                $desc      = trim($_POST['description'] ?? '');
                $duration  = (int)($_POST['duration'] ?? 0);
                $director  = trim($_POST['director'] ?? '');
                $poster    = trim($_POST['poster_url'] ?? '');
                // Không cho phép thay đổi trạng thái vở diễn theo cách thủ công. 
                //Trạng thái này được tính toán từ dữ liệu buổi diễn (performance data) thông qua thủ tục proc_update_show_statuses() và cập nhật dự phòng trong phương thức Show::all().
                // Hãy bỏ qua mọi giá trị trạng thái được gửi lên (qua form POST).
                $genreIds  = $_POST['genre_ids'] ?? [];
                if ($showId > 0 && $title && $duration > 0 && $director && $poster) {
                    // Cập nhật thông tin show bằng thủ tục lưu trữ để tránh truy vấn thủ công
                    $success = $showModel->updateDetails($showId, $title, $desc, $duration, $director, $poster);
                    // Cập nhật danh sách thể loại bằng thủ tục lưu trữ
                    $successGenres = $showModel->updateGenres($showId, $genreIds);
                    if ($success && $successGenres) {
                        $_SESSION['success'] = 'Đã cập nhật vở diễn.';
                    } else {
                        $_SESSION['error'] = 'Không thể cập nhật vở diễn.';
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin vở diễn.';
                }
                $this->redirect('index.php?pg=admin-category-show');
                return;
            }
        }
        // Lấy dữ liệu để hiển thị.
        $genres = $genreModel->all();
        $shows  = $showModel->all();
        usort($genres, function ($a, $b) {
            return ($a['genre_id'] ?? 0) <=> ($b['genre_id'] ?? 0);
        });
        usort($shows, function ($a, $b) {
            return ($a['show_id'] ?? 0) <=> ($b['show_id'] ?? 0);
        });
        // // Đối với mỗi vở diễn, xác định xem nó có thể bị xóa hay không. 
        //Một vở diễn chỉ có thể bị xóa khi nó không có các buổi diễn (performances) liên kết.
        // Thuộc tính này được view (giao diện) sử dụng để bật hoặc tắt nút xóa. Khi các thủ tục lưu trữ không khả dụng, phương thức dự phòng Show::performances() vẫn sẽ trả về các buổi diễn đã được lên lịch cho vở diễn đó. 
        //// Xác định liệu mỗi vở diễn có thể bị xóa hay không.
        // Một vở diễn chỉ có thể bị xóa nếu nó hoàn toàn không có buổi diễn (bất kể trạng thái buổi diễn). Truy vấn trực tiếp bảng performances vì phương thức Show::performances() chỉ trả về các buổi diễn đang mở (open performances).
        $pdo = \App\Models\Database::connect();
        foreach ($shows as &$sh) {
            $sid = (int)($sh['show_id'] ?? 0);
        // Sử dụng một thủ tục lưu trữ để đếm số buổi diễn cho một vở diễn.
            $stmt = $pdo->prepare('CALL proc_count_performances_by_show(:sid)');
            $stmt->execute(['sid' => $sid]);
            $count = 0;
            if ($row = $stmt->fetch()) {
                $count = (int)($row['performance_count'] ?? 0);
            }
            $stmt->closeCursor();
            $sh['can_delete'] = ($count === 0);
        }
        unset($sh);

       // Xác định xem có đang chỉnh sửa một vở diễn hiện có hay không.
        $editShow = null;
        $selectedGenres = [];
        if (isset($_GET['edit_id'])) {
            $editId = (int)$_GET['edit_id'];
            if ($editId > 0) {
                $editShow = $showModel->find($editId);
                if ($editShow) {
                    $pdo = \App\Models\Database::connect();
                    // Use a stored procedure to get genre IDs for a show
                    $stmt = $pdo->prepare('CALL proc_get_genre_ids_by_show(:sid)');
                    $stmt->execute(['sid' => $editId]);
                    $selectedGenres = [];
                    foreach ($stmt->fetchAll() as $row) {
                        $selectedGenres[] = (int)($row['genre_id'] ?? 0);
                    }
                    $stmt->closeCursor();
                }
            }
        }

        // Xác định xem có đang chỉnh sửa một vở diễn hiện có hay không.
        $editGenre = null;
        if (isset($_GET['edit_genre_id'])) {
            $gid = (int)$_GET['edit_genre_id'];
            if ($gid > 0) {
                // Tìm thể loại bằng cách lặp qua mảng thể loại đã được tải.
                foreach ($genres as $g) {
                    if ((int)($g['genre_id'] ?? 0) === $gid) {
                        $editGenre = $g;
                        break;
                    }
                }
            }
        }
        // Hiển thị view (giao diện) bên ngoài thư mục cũ của nó. 
        // Tệp index đã được di chuyển và đổi tên thành ad_category&show.php để dễ hiểu hơn.
        $this->renderAdmin('ad_category&show', [
            'genres'        => $genres,
            'shows'         => $shows,
            'editShow'      => $editShow,
            'selectedGenres'=> $selectedGenres
            ,'editGenre'     => $editGenre
        ]);
    }
}