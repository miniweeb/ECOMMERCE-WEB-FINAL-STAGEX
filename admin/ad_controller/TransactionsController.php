<?php
/*
Controller để quản lý các booking trong admin area.
Cung cấp giao diện cho quản trị viên xem, lọc các bookings
*/

namespace App\Controllers;


use App\Models\Booking;


class TransactionsController extends AdBaseController
{
    //Hiển thị danh sách booking với bộ lọc tùy chọn.
    public function index(): void
    {
        if (!$this->ensureAdmin()) return;
        $bookingModel = new Booking();
        $bookings = $bookingModel->ad_getAllBookings();


        $paymentModel = new \App\Models\Payment();
        foreach ($bookings as &$bk) {
            $payment = $paymentModel->findByBooking((int)($bk['booking_id'] ?? 0));
            $bk['payment_status'] = $payment['status'] ?? 'Đang chờ';
        }
        unset($bk);
        // Truy xuất vở và suất diễn cho bộ lọc danh sách
        $showModel = new \App\Models\Show();
        $perfModel = new \App\Models\PerformanceModel();
        $showsList = $showModel->all();
        $perfsList = $perfModel->all();
        // Sắp xếp các đặt chỗ theo ID để hiển thị nhất quán
        usort($bookings, function ($a, $b) {
            return ($a['booking_id'] ?? 0) <=> ($b['booking_id'] ?? 0);
        });
        // Trích xuất bộ lọc từ các tham số truy vấn
        $filterEmail    = isset($_GET['email']) ? trim($_GET['email']) : '';
        $filterBooking  = isset($_GET['booking_id']) ? trim($_GET['booking_id']) : '';
        $filterDate     = isset($_GET['date']) ? trim($_GET['date']) : '';
        $filterShow     = isset($_GET['show']) ? trim($_GET['show']) : '';
        $filterPerf     = isset($_GET['performance']) ? trim($_GET['performance']) : '';
        if ($filterEmail || $filterBooking || $filterDate || $filterShow || $filterPerf) {
            $bookings = array_filter($bookings, function ($b) use ($filterEmail, $filterBooking, $filterDate, $filterShow, $filterPerf) {
                $match = true;
                if ($filterEmail) {
                    $match = $match && (stripos($b['email'], $filterEmail) !== false);
                }
                if ($filterBooking) {
                    $match = $match && ((string)$b['booking_id'] === (string)$filterBooking);
                }
                if ($filterDate) {
                    $match = $match && (strpos($b['created_at'], $filterDate) === 0);
                }
                if ($filterShow) {
                    $match = $match && ((string)($b['show_id'] ?? '') === (string)$filterShow);
                }
                if ($filterPerf) {
                    $match = $match && ((string)($b['performance_id'] ?? '') === (string)$filterPerf);
                }
                return $match;
            });
        }
        // Hiển thị chế độ xem giao dịch
        // Truyền tất cả các tham số bộ lọc và danh sách để hiển thị.


        $this->renderAdmin('ad_transaction', [
            'bookings'      => $bookings,
            'filterEmail'   => $filterEmail,
            'filterBooking' => $filterBooking,
            'filterDate'    => $filterDate,
            'filterShow'    => $filterShow,
            'filterPerf'    => $filterPerf,
            'showsList'     => $showsList,
            'perfsList'     => $perfsList
        ]);
    }
}
