<?php
/*
Controller để quản lý đánh giá của khách hàng trong admin area.
Quản trị viên có thể xem tất cả đánh giá và xóa những đánh giá không phù hợp.
Danh sách đánh giá được sắp xếp theo ID
*/


namespace App\Controllers;


class ReviewsController extends AdBaseController
{
    public function index(): void
    {
        if (!$this->ensureAdmin()) return;
        $reviewModel = new \App\Models\Review();
        $showModel   = new \App\Models\Show();
        // Xử lý việc xóa đánh giá
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
            $rid = (int)$_POST['delete_id'];
            if ($rid > 0) {
                if ($reviewModel->delete($rid)) {
                    $_SESSION['success'] = 'Đã xóa đánh giá.';
                } else {
                    $_SESSION['error'] = 'Không thể xóa đánh giá.';
                }
            }
            $this->redirect('index.php?pg=admin-reviews');
            return;
        }
        // Lấy tất cả các đánh giá theo vở diễn và điểm xếp hạng dựa trên tham số truy vấn
        $reviews = $reviewModel->getAll();
        // Lấy tất cả vở diễn cho dropbox của bộ lọc
        $shows   = $showModel->all();
        $showFilter   = isset($_GET['show_id']) ? (int)$_GET['show_id'] : 0;
        $ratingFilter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
        if ($showFilter > 0) {
            $reviews = array_filter($reviews, function ($r) use ($showFilter) {
                return isset($r['show_id']) && (int)$r['show_id'] === $showFilter;
            });
        }
        if ($ratingFilter > 0) {
            $reviews = array_filter($reviews, function ($r) use ($ratingFilter) {
                return isset($r['rating']) && (int)$r['rating'] <= $ratingFilter;
            });
        }
        // Sắp xếp các bài đánh giá theo ID
        usort($reviews, function ($a, $b) {
            return ($a['review_id'] ?? 0) <=> ($b['review_id'] ?? 0);
        });
        // Render đánh giá
        $this->renderAdmin('ad_reviews', [
            'reviews'      => $reviews,
            'shows'        => $shows,
            'showFilter'   => $showFilter,
            'ratingFilter' => $ratingFilter
        ]);
    }
}
