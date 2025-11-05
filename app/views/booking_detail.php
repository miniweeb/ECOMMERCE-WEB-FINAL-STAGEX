<h2 class="h4 mb-3">Chi tiết vé</h2>


<?php
// Hiển thị biểu ngữ thanh toán thành công khi chi tiết đặt phòng được truy cập sau khi hoàn tất thanh toán.
$paymentSuccess = false;
if (isset($_GET['paid']) && $_GET['paid'] == '1') {
    $paymentSuccess = true;
}
// Dự phòng cho các thông báo thành công cũ
if (!empty($_SESSION['success'])) {
    $paymentSuccess = true;
}
?>
<?php if ($paymentSuccess): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        Thanh toán thành công! Cảm ơn bạn đã đặt vé.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>


<div class="mb-4">
    <?php if (!empty($userDetail) && isset($userDetail['full_name'])): ?>
        <p class="mb-1"><strong>Họ tên:</strong> <?= htmlspecialchars($userDetail['full_name']) ?></p>
    <?php endif; ?>
    <h3 class="h5"><?= htmlspecialchars($show['title'] ?? '') ?></h3>
    <p class="mb-1"><strong>Ngày:</strong> <?= isset($performance['performance_date']) ? date('d/m/Y', strtotime($performance['performance_date'])) : '' ?></p>
    <p class="mb-1"><strong>Giờ:</strong> <?= isset($performance['start_time']) ? substr($performance['start_time'], 0, 5) : '' ?></p>
    <p class="mb-1"><strong>Phòng:</strong> <?= htmlspecialchars($performance['theater_name'] ?? '') ?></p>
</div>


<div class="table-responsive mb-4">
    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>Ghế</th>
                <th>Mã vé</th>
                <th>Giá (₫)</th>
            </tr>
        </thead>
        <tbody>
            <?php $total = 0; ?>
            <?php foreach ($booking['tickets'] as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['row_char'] . $t['seat_number']) ?></td>
                    <td><?= htmlspecialchars($t['ticket_code']) ?></td>
                    <td><?= number_format($t['price'], 0, ',', '.') ?></td>
                </tr>
                <?php $total += $t['price']; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


<div class="mb-3">
    <p><strong>Tổng tiền:</strong> <?= number_format($total, 0, ',', '.') ?> ₫</p>
    <p><strong>Phương thức thanh toán:</strong> <?= htmlspecialchars($payment['payment_method'] ?? 'VNPAY') ?></p>
    <p><strong>Ngày giao dịch:</strong>
        <?php
        // Sử dụng ngày thanh toán VNPay nếu có (vnp_pay_date là yyyymmddHHMMSS)
        // nếu không sử dụng payment updated_at
        if (!empty($payment['vnp_pay_date'])) {
            $pd = $payment['vnp_pay_date'];
            $dt = \DateTime::createFromFormat('YmdHis', $pd);
            echo $dt ? $dt->format('d/m/Y H:i:s') : '';
        } elseif (!empty($payment['updated_at'])) {
            echo date('d/m/Y H:i:s', strtotime($payment['updated_at']));
        } else {
            echo date('d/m/Y H:i:s', strtotime($booking['created_at']));
        }
        ?>
    </p>
</div>

<a href="<?= BASE_URL ?>index.php?pg=bookings" class="btn btn-outline-light">Quay về danh sách vé</a>