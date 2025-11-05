<?php
namespace App\Controllers;


use App\Models\Booking;


/**
* PaymentController hiển thị màn hình thanh toán cho booking hiện tại và xử lý việc hoàn tất hoặc hủy thanh toán. 
* Một bộ đếm ngược đơn giản được triển khai phía máy khách để tự động hủy sau 5 phút.
*/


class PaymentController extends BaseController
{
    public function pay(): void
    {
        if (empty($_SESSION['current_booking'])) {
            $this->redirect('index.php');
            return;
        }
        // Hủy bất kỳ khoản thanh toán nào đang chờ xử lý trước thời gian cho phép (15 phút).
// Điều này đảm bảo các đặt chỗ và thanh toán được tự động hủy trước khi hiển thị trang thanh toán. 
// Phương thức này sẽ cập nhật cả trạng thái thanh toán và đặt chỗ khi cần thiết và giải phóng chỗ ngồi.


        $paymentExpiryModel = new \App\Models\Payment();
        $paymentExpiryModel->expirePendingPayments();
        $bookingModel = new Booking();
        $bookingId = $_SESSION['current_booking'];
        $booking = $bookingModel->find($bookingId);
        if (!$booking) {
            unset($_SESSION['current_booking']);
            $this->redirect('index.php');
            return;
        }
        // Xử lý hoàn tất thanh toán thủ công (dự phòng) nếu quản trị viên đánh dấu booking là đã thanh toán.
// Hệ thống cập nhật trạng thái đặt phòng và chuyển hướng đến trang chi tiết


        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete'])) {
            // Đánh dấu booking là đã hoàn tất và thanh toán thành công. Trạng thái thanh toán được lưu


            $bookingModel->updateStatus($bookingId, 'Đã hoàn thành', 'Thành công');
            // Sau khi thanh toán xóa biến phiên booking hiện tại
            unset($_SESSION['current_booking']);
            // Chuyển hướng đến trang booking_detail và chỉ ra thanh toán thành công.
            $this->redirect('index.php?pg=booking-detail&id=' . $bookingId . '&paid=1');
            return;
        }
        // Xử lý hủy thông qua tham số GET
        if (isset($_GET['cancel']) && $_GET['cancel'] == '1') {
            // Hủy đặt chỗ và đánh dấu thanh toán là không thành công. Trạng thái thanh toán là Thất bại.
            $bookingModel->updateStatus($bookingId, 'Đã hủy', 'Thất bại');
            unset($_SESSION['current_booking']);
            $_SESSION['error'] = 'Đơn hàng đã được hủy do hết thời gian giữ chỗ.';
            $this->redirect('index.php');
            return;
        }
        // Render payment view bao gồm nút VNPay
        $this->render('pay', [
            'booking' => $booking
        ]);
    }


    /* Khởi tạo thanh toán VNPay cho booking hiện tại. Phương thức này tạo một URL VNPay đã ký và chuyển hướng người dùng đến cổng thanh toán. */
    public function vnpayPayment(): void
    {
        // Yêu cầu booking phiên hiện tại
        if (empty($_SESSION['current_booking'])) {
            $this->redirect('index.php');
            return;
        }
        $bookingId = (int)$_SESSION['current_booking'];
        // Lấy thông tin booking để biết số tiền và đảm bảo số tiền đó tồn tại
        $bookingModel = new \App\Models\Booking();
        $booking = $bookingModel->find($bookingId);
        if (!$booking) {
            unset($_SESSION['current_booking']);
            $this->redirect('index.php');
            return;
        }
        // Tính tổng
        $total = 0;
        foreach ($booking['tickets'] as $t) {
            $total += $t['price'];
        }
        // Tạo một tham chiếu giao dịch duy nhất
        $txnRef = 'STG' . date('YmdHis') . rand(1000, 9999);
        // Tạo thanh toán record
        $paymentModel = new \App\Models\Payment();
        $paymentModel->create($bookingId, $total, $txnRef, 'VNPAY');
        // Tạo dữ liệu VNPay yêu cầu
        require_once __DIR__ . '/../../config/vnpay.php';
        $vnp_Version    = '2.1.0';
        $vnp_TmnCode    = VNPAY_TMN_CODE;
        $vnp_HashSecret = VNPAY_HASH_SECRET;
        $vnp_Url        = VNPAY_URL;
        $vnp_ReturnUrl  = VNPAY_RETURN_URL;
        $vnp_TxnRef     = $txnRef;
        $vnp_OrderInfo  = 'Thanh toan don hang ' . $bookingId;
        // Loại đơn hàng có thể được đặt thành "thanh toán hóa đơn" để tuân thủ tài liệu VNPay.
        $vnp_OrderType  = 'billpayment';
        // Nhân với 100 để quy đổi ra đơn vị nhỏ nhất của VNPay (VND * 100).
        $vnp_Amount     = (int)round($total * 100);
        $vnp_Locale     = 'vn';
        // BankCode có thể được cung cấp thông qua tham số truy vấn để cho phép người dùng chọn phương thức thanh toán
        $vnp_BankCode   = '';
        // Chấp nhận cả `bank`, `bank code` và `bank Code`
        if (!empty($_GET['bank'])) {
            $vnp_BankCode = preg_replace('/[^A-Za-z0-9]/', '', $_GET['bank']);
        } elseif (!empty($_GET['bank_code'])) {
            $vnp_BankCode = preg_replace('/[^A-Za-z0-9]/', '', $_GET['bank_code']);
        } elseif (!empty($_GET['bankCode'])) {
            $vnp_BankCode = preg_replace('/[^A-Za-z0-9]/', '', $_GET['bankCode']);
        }
        $vnp_IpAddr     = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        // Đặt thời gian hết hạn là 15 phút kể từ bây giờ
        $vnp_ExpireDate = date('YmdHis', strtotime('+15 minutes'));
        $inputData = [
            'vnp_Version'    => $vnp_Version,
            'vnp_TmnCode'    => $vnp_TmnCode,
            'vnp_Amount'     => $vnp_Amount,
            'vnp_Command'    => 'pay',
            'vnp_CreateDate' => date('YmdHis'),
            'vnp_CurrCode'   => 'VND',
            'vnp_ExpireDate' => $vnp_ExpireDate,
            'vnp_IpAddr'     => $vnp_IpAddr,
            'vnp_Locale'     => $vnp_Locale,
            'vnp_OrderInfo'  => $vnp_OrderInfo,
            'vnp_OrderType'  => $vnp_OrderType,
            'vnp_ReturnUrl'  => $vnp_ReturnUrl,
            'vnp_TxnRef'     => $vnp_TxnRef
        ];


        if (!empty($vnp_BankCode)) {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        // Sắp xếp dữ liệu và xây dựng chuỗi truy vấn và chuỗi băm theo mẫu VNPay.
        ksort($inputData);
        $queryString = '';
        $hashString  = '';
        $first       = true;
        foreach ($inputData as $key => $value) {
            $encodedKey   = urlencode($key);
            $encodedValue = urlencode((string)$value);
            // Xây dựng chuỗi truy vấn (luôn được phân tách bằng dấu '&')
            $queryString .= $encodedKey . '=' . $encodedValue . '&';
            // Xây dựng chuỗi dữ liệu băm
            if ($first) {
                $hashString .= $encodedKey . '=' . $encodedValue;
                $first = false;
            } else {
                $hashString .= '&' . $encodedKey . '=' . $encodedValue;
            }
        }
        $vnp_SecureHash = hash_hmac('sha512', $hashString, $vnp_HashSecret);
        $queryString   .= 'vnp_SecureHash=' . $vnp_SecureHash;
        $paymentUrl     = $vnp_Url . '?' . $queryString;
        // Chuyển hướng đến cổng VNPay
        header('Location: ' . $paymentUrl);
        exit;
    }


    /*Xử lý URL trả về từ VNPay sau khi người dùng hoàn tất hoặc hủy thanh toán.*/

    public function vnpayReturn(): void
    {
        // Ghi lại tất cả các tham số trả về của VNPay
        $vnpData = [];
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) === 'vnp_') {
                $vnpData[$key] = $value;
            }
        }
        $vnp_SecureHash = $vnpData['vnp_SecureHash'] ?? '';
        unset($vnpData['vnp_SecureHash']);
        // Load config
        require_once __DIR__ . '/../../config/vnpay.php';
        // Sắp xếp dữ liệu để tính toán hash
        ksort($vnpData);
        // Xây dựng chuỗi dữ liệu băm bằng các quy tắc mã hóa giống như mẫu VNPay. Khóa và giá trị được mã hóa URL và được nối bằng ký tự &.
        $hashdata = '';
        $i = 0;
        foreach ($vnpData as $key => $val) {
            $encodedKey = urlencode($key);
            $encodedVal = urlencode((string)$val);
            if ($i === 0) {
                $hashdata = $encodedKey . '=' . $encodedVal;
            } else {
                $hashdata .= '&' . $encodedKey . '=' . $encodedVal;
            }
            $i++;
        }
        $calculatedHash = hash_hmac('sha512', $hashdata, VNPAY_HASH_SECRET);
        $paymentModel = new \App\Models\Payment();
        $bookingModel = new \App\Models\Booking();
        // Verify hash
        if (hash_equals($calculatedHash, $vnp_SecureHash)) {
            $txnRef = $vnpData['vnp_TxnRef'] ?? '';
            $payment = $paymentModel->findByTxnRef($txnRef);
            if ($payment) {
                // Xác định kết quả thanh toán
                $responseCode = $vnpData['vnp_ResponseCode'] ?? '';
                $transStatus  = $vnpData['vnp_TransactionStatus'] ?? '';
                $bankCode     = $vnpData['vnp_BankCode'] ?? null;
                $payDate      = $vnpData['vnp_PayDate'] ?? null;
                if ($responseCode === '00' && $transStatus === '00') {
                    // Kiểm tra tính nhất quán của số tiền: VNPay trả về vnp_Amount theo đơn vị nhỏ nhất (VND * 100)
                    $paidAmount = isset($vnpData['vnp_Amount']) ? (int)$vnpData['vnp_Amount'] / 100 : 0;
                    if ((int)$paidAmount === (int)$payment['amount']) {
                        // Thanh toán thành công: cập nhật trạng thái booking và payment.
                        $bookingModel->updateStatus($payment['booking_id'], 'Đã hoàn thành', 'Thành công');
                        $redirectBooking = $payment['booking_id'];
                    } else {
                        // Số tiền không khớp: đánh dấu thanh toán là không thành công và hủy đặt chỗ
                        $bookingModel->updateStatus($payment['booking_id'], 'Đã hủy', 'Thất bại');
                        $_SESSION['error'] = 'Số tiền thanh toán không khớp. Giao dịch bị từ chối.';
                    }
                } else {
                    // Thanh toán không thành công hoặc đã hủy: đánh dấu đặt chỗ và thanh toán là không thành công
                    $bookingModel->updateStatus($payment['booking_id'], 'Đã hủy', 'Thất bại');
                    $_SESSION['error'] = 'Thanh toán thất bại hoặc bị hủy. Vui lòng thử lại.';
                }
            } else {
                // Tham chiếu thanh toán không xác định
                $_SESSION['error'] = 'Không tìm thấy giao dịch thanh toán.';
            }
        } else {
            // Hash không khớp
            $_SESSION['error'] = 'Xác minh chữ ký không thành công.';
        }
        // Luôn hủy đặt chỗ hiện tại sau khi return
        unset($_SESSION['current_booking']);
        // Nếu booking chuyển hướng được thiết lập, sẽ chuyển hướng đến trang chi tiết của booking đó
        if (isset($redirectBooking) && $redirectBooking > 0) {
            $this->redirect('index.php?pg=booking-detail&id=' . $redirectBooking . '&paid=1');
        } else {
            // Chuyển hướng về trang chủ trong các trường hợp khác
            $this->redirect('index.php');
        }
    }
}