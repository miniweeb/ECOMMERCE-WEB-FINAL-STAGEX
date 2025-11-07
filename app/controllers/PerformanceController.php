<?php
namespace App\Controllers;


use App\Models\Show;
use App\Models\Seat;
use App\Models\SeatCategory;

class PerformanceController extends BaseController
{
    public function select(int $performanceId): void
    {
        $paymentExpireModel = new \App\Models\Payment();
        $paymentExpireModel->expirePendingPayments();


        // Nếu người dùng gửi biểu mẫu chọn ghế
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $raw = $_POST['seats'] ?? [];
            // $raw có thể là một mảng với một chuỗi JSON chứa danh sách ghế|giá
            $selectedList = [];
            if (is_array($raw) && count($raw) === 1 && strpos($raw[0], '[') === 0) {
                $selectedList = json_decode($raw[0], true) ?: [];
            } elseif (is_array($raw)) {
                $selectedList = $raw;
            }
            if (empty($selectedList)) {
                // Không có ghế nào được chọn
                $_SESSION['error'] = 'Vui lòng chọn ít nhất một ghế.';
            } else {
                // Kiểm tra đăng nhập: yêu cầu người dùng đăng nhập trước khi đặt chỗ
                if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
                    $_SESSION['error'] = 'Bạn cần đăng nhập để đặt vé.';
                    // Chuyển hướng đến trang đăng nhập
                    $this->redirect('index.php?pg=login');
                    return;
                }
                // Xây dựng mảng seat_id => price
                $seats = [];
                foreach ($selectedList as $pair) {
                    if (strpos($pair, '|') !== false) {
                        [$seatId, $price] = explode('|', $pair);
                        $seats[(int)$seatId] = (float)$price;
                    }
                }
                if (!empty($seats)) {
                    // Tính tổng số tiền
                    $total = 0;
                    foreach ($seats as $p) {
                        $total += (float)$p;
                    }
                    // Tạo booking mới và các vé liên quan thông qua thủ tục lưu trữ
                    $bookingModel = new \App\Models\Booking();
                    $userId = (int)($_SESSION['user']['user_id'] ?? 0);
                    $bookingId = $bookingModel->create($userId, $performanceId, $seats, $total);
                    if ($bookingId) {
                        // Lưu booking hiện tại vào phiên để PaymentController sử dụng
                        unset($_SESSION['selected_seats'], $_SESSION['selected_performance']);
                        $_SESSION['current_booking'] = $bookingId;
                        // Chuyển hướng trực tiếp tới trang tạo thanh toán VNPay
                        $this->redirect('index.php?pg=vnpay_payment');
                        return;
                    } else {
                        $_SESSION['error'] = 'Không thể tạo đơn hàng. Vui lòng thử lại.';
                    }
                } else {
                    $_SESSION['error'] = 'Lựa chọn ghế không hợp lệ.';
                }
            }
        }


        $showModel = new Show();
        $seatModel = new Seat();
        $categoriesModel = new SeatCategory();
        $performance = $this->findPerformanceById($performanceId);
        if (!$performance) {
            $this->redirect('index.php');
            return;
        }
        $show = $showModel->find($performance['show_id']);
        $categories = $categoriesModel->all();
        $seats   = $seatModel->seatsForTheater((int)$performance['theater_id']);
        $booked  = $seatModel->bookedForPerformance($performanceId);
        $this->render('performance', [
            'performance' => $performance,
            'show'  => $show,
            'categories' => $categories,
            'seats' => $seats,
            'booked' => $booked
        ]);
    }


    /**
     * Helper to fetch a single performance record by ID including theater
     * name.  We replicate the query used in Show::performances but for
     * an individual performance.
     *
     * @param int $id
     * @return array|null
     */
    private function findPerformanceById(int $id)
    {
        $db = \App\Models\Database::connect();
        try {
            $stmt = $db->prepare('CALL proc_get_performance_by_id(:id)');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            return $row ?: null;
        } catch (\Throwable $ex) {
            return null;
        }
    }
}