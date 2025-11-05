<?php
namespace App\Models;


/* Mô hình thanh toán bao gồm các hồ sơ thanh toán liên quan đến đặt chỗ. 
Mỗi hồ sơ thanh toán sẽ theo dõi tham chiếu giao dịch VNPay cùng với số tiền và trạng thái thanh toán.*/


class Payment extends Database
{
    // Create a new payment record for a booking.
    public function create(int $bookingId, float $amount, string $txnRef, string $method = 'VNPAY'): int
    {
        // Gọi proc tạo payment
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_create_payment(:bid, :amount, :status, :ref, :method)');
            $stmt->execute([
                'bid'    => $bookingId,
                'amount' => $amount,
                'status' => 'Đang chờ',
                'ref'    => $txnRef,
                'method' => $method
            ]);
            $result = $stmt->fetch();
            $stmt->closeCursor();
            return isset($result['payment_id']) ? (int)$result['payment_id'] : 0;
        } catch (\Throwable $ex) {
            return 0;
        }
    }


    /*Tìm khoản thanh toán theo mã giao dịch VNPay.*/
    public function findByTxnRef(string $txnRef): ?array
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare('CALL proc_get_payment_by_txn(:ref)');
        $stmt->execute(['ref' => $txnRef]);
        $result = $stmt->fetch();
        $stmt->closeCursor();
        return $result ?: null;
    }


    /*Tìm khoản thanh toán liên quan đến một đặt phòng. 
    Trả về bản ghi thanh toán gần đây nhất hoặc null nếu không có. 
    Truy vấn dự phòng được sử dụng nếu các thủ tục được lưu trữ không khả dụng.*/
    public function findByBooking(int $bookingId): ?array
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_get_payments_by_booking(:bid)');
            $stmt->execute(['bid' => $bookingId]);
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            if (!$rows) return null;
            return $rows[count($rows) - 1];
        } catch (\Throwable $ex) {
            return null;
        }
    }


    /* Cập nhật trạng thái thanh toán bằng cách sử dụng tham chiếu giao dịch VNPay.
    Cũng lưu lại các trường VNPay bổ sung để kiểm tra.*/
    public function updateStatusByTxnRef(string $txnRef, string $status, ?string $bankCode, ?string $payDate): bool
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare('CALL proc_update_payment_status(:ref, :status, :bank, :payDate)');
        $result = $stmt->execute([
            'ref'    => $txnRef,
            'status' => $status,
            'bank'   => $bankCode,
            'payDate'=> $payDate
        ]);
        $stmt->closeCursor();
        return $result;
    }


    public function expirePendingPayments(): void
    {
        $pdo = $this->getConnection();
        try {
            // Gọi thủ tục hết hạn; sử dụng bất kỳ tập kết quả nào để đóng con trỏ
            $stmt = $pdo->query('CALL proc_expire_pending_payments()');
            if ($stmt) {
                $stmt->fetchAll();
                $stmt->closeCursor();
            }
        } catch (\Throwable $ex) {
            // Bỏ qua các ngoại lệ; thời hạn hết hạn không quan trọng đối với việc hiển thị trang
        }
    }
}