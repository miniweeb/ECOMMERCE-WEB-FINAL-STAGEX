<?php
namespace App\Models;


/**
* Mô hình đặt chỗ bao gồm việc đặt chỗ và tạo vé. Một booking
* thuộc về một người dùng và một suất diễn cụ thể, có một hoặc nhiều
* vé liên quan. Trạng thái thanh toán và đặt chỗ được theo dõi.
*/


class Booking extends Database
{
/**
* Tạo một booking mới với vé. Trả về ID đặt chỗ mới hoặc false nếu 
* không thành công. Thao tác này được gói gọn trong một giao dịch
* để đảm bảo tính nhất quán.
*
* @return int|false
*/
 public function create(int $userId, int $performanceId, array $seatIds, float $total)
{
    $pdo = $this->getConnection();
    try {
        // Kiểm tra xem lỗi gì làm không tạo đơn được
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);


        $pdo->beginTransaction();


        // Gọi proc tạo booking
        $stmt = $pdo->prepare('CALL proc_create_booking(:uid, :pid, :total)');
        $stmt->execute([
            'uid'   => $userId,
            'pid'   => $performanceId,
            'total' => $total
        ]);


        // Lấy kết quả (booking_id)
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();


        // Trích xuất ID
        $bookingId = isset($row['booking_id']) ? (int)$row['booking_id'] : 0;


        // Debug: hiển thị những gì proc được lưu trữ trả về
        if (!$bookingId) {
            throw new \Exception('Stored procedure did not return a valid booking_id');
        }


        // Chuẩn bị proc tạo ticket
        $ticketStmt = $pdo->prepare('CALL proc_create_ticket(:bid, :seat, :code)');


        foreach ($seatIds as $sid => $price) {
            // Tạo mã vé ngẫu nhiên: 3 chữ cái + 6 chữ số
            $letters = '';
            for ($i = 0; $i < 3; $i++) {
                $letters .= chr(random_int(65, 90));
            }
            $numbers = '';
            for ($i = 0; $i < 6; $i++) {
                $numbers .= (string)random_int(0, 9);
            }
            $code = $letters . $numbers;


            // Thực thi proc_create_ticket cho mỗi seat
            $ticketStmt->execute([
                'bid'  => $bookingId,
                'seat' => $sid,
                'code' => $code
            ]);


            $ticketStmt->closeCursor();
        }


        $pdo->commit();


        return $bookingId;


    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }


        // Hiển thị xem lỗi gì làm không tạo đơn được
        echo '<pre style="color:red;">';
        echo "⚠️ Booking creation failed:\n";
        echo "Error message: " . $e->getMessage() . "\n";
        if (isset($e->errorInfo)) {
            echo "SQLSTATE: " . ($e->errorInfo[0] ?? 'N/A') . "\n";
            echo "Driver code: " . ($e->errorInfo[1] ?? 'N/A') . "\n";
            echo "SQL message: " . ($e->errorInfo[2] ?? 'N/A') . "\n";
        }
        echo "</pre>";


        return false;
    }
}


    /**
     * Lấy thông tin booking cùng ticket liên quan
     * @param int $id
     * @return array|null
     */
    public function find(int $id)
    {
        $pdo = $this->getConnection();


        try {
            $stmt = $pdo->prepare('CALL proc_get_booking_with_tickets(:bid)');
            $stmt->execute(['bid' => $id]);
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
        } catch (\Throwable $ex) {
            $rows = [];
        }
        if (!$rows) {
            return null;
        }
        // Khởi tạo dữ liệu đặt chỗ từ hàng đầu tiên
        $first = $rows[0];
        // Xác định trạng thái thanh toán bằng cách tra cứu hồ sơ thanh toán mới nhất
        $paymentStatus = 'Đang chờ';
        try {
            $paymentModel = new \App\Models\Payment();
            $payment      = $paymentModel->findByBooking((int)$first['booking_id']);
            if ($payment && isset($payment['status'])) {
                $paymentStatus = $payment['status'];
            }
        } catch (\Throwable $e) {
            // Nếu tra cứu thanh toán không thành công, mặc định là đang chờ xử lý
            $paymentStatus = 'Đang chờ';
        }
        $bookingData = [
            'booking_id'     => $first['booking_id'],
            'user_id'        => $first['user_id'],
            'performance_id' => $first['performance_id'],
            'total_amount'   => $first['total_amount'],
            'booking_status' => $first['booking_status'],
            // Trạng thái thanh toán được lấy từ bảng thanh toán (Thành công/Thất bại/Đang chờ)
            'payment_status' => $paymentStatus,
            'created_at'     => $first['created_at'],
        ];
        // Tạo tickets array
        $tickets = [];
        foreach ($rows as $row) {
            if (!empty($row['ticket_id'])) {
                $tickets[] = [
                    'ticket_id'    => $row['ticket_id'],
                    'ticket_code'  => $row['ticket_code'] ?? '',
                    'row_char'     => $row['row_char'],
                    'seat_number'  => $row['seat_number'],
                    'category_name'=> $row['category_name'],
                    'color_class'  => $row['color_class'],
                    'price'        => $row['ticket_price'],
                ];
            }
        }
        $bookingData['tickets'] = $tickets;
        return $bookingData;
    }


    /**
     * Cập nhật trạng thái booking và tùy chọn trạng thái thanh toán.
     * Khi paymentStatus được cung cấp, khoản thanh toán gần nhất cho booking sẽ được cập nhật thông qua Payment model. The booking_status
lưu trữ thông qua thủ tục lưu trữ `proc_update_booking_status`.
     * @return bool                      True on success
     */
    public function updateStatus(int $bookingId, string $bookingStatus, ?string $paymentStatus = null): bool
    {
        $pdo = $this->getConnection();
        // Gọi thủ tục chỉ cập nhật booking_status.        
$stmt = $pdo->prepare('CALL proc_update_booking_status(:id, :b)');
        $result = $stmt->execute([
            'id' => $bookingId,
            'b'  => $bookingStatus
        ]);
        $stmt->closeCursor();
        // Nếu trạng thái thanh toán được cung cấp, cập nhật bản ghi gần nhất cho booking đó.
        if ($paymentStatus !== null) {
            try {
                $paymentModel = new \App\Models\Payment();
                $payment      = $paymentModel->findByBooking($bookingId);
                if ($payment && isset($payment['vnp_txn_ref'])) {
                    $paymentModel->updateStatusByTxnRef($payment['vnp_txn_ref'], $paymentStatus, null, null);
                }
            } catch (\Throwable $e) {
                // Bỏ qua lỗi cập nhật thanh toán
            }
        }
        return (bool)$result;
    }

    public function forUser(int $userId): array
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare('CALL proc_get_bookings_by_user(:uid)');
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();
        $stmt->closeCursor();
        return $rows;
    }


    {
        // Sử dụng proc lấy danh sách booking của admin
        $pdo = $this->getConnection();
        $stmt = $pdo->query('CALL proc_get_all_bookings()');
        $rows = $stmt->fetchAll();
        $stmt->closeCursor();
        return $rows;
    }


    public function forUserDetailed(int $userId): array
    {
        $pdo = $this->getConnection();
        // Sử dụng proc lấy bd. Trả về mảng trống nếu k có bd nào
        try {
            $stmt = $pdo->prepare('CALL proc_get_user_bookings_detailed(:uid)');
            $stmt->execute(['uid' => $userId]);
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }


    public function ad_getAllBookings(): array
    {
        return $this->all();
    }
}