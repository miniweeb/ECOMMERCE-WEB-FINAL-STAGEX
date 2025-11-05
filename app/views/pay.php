<h2 class="h4 mb-3">Thanh toán</h2>
<p>Mã đơn hàng: <strong>#<?= $booking['booking_id'] ?></strong></p>


<h5>Thông tin vé</h5>
<ul class="list-group mb-3">
    <?php $total = 0; ?>
    <?php foreach ($booking['tickets'] as $t): ?>
        <?php $total += $t['price']; ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <?= htmlspecialchars($t['row_char'] . $t['seat_number']) ?> (<?= htmlspecialchars($t['category_name']) ?>)
            <span><?= number_format($t['price'], 0, ',', '.') ?>₫</span>
        </li>
    <?php endforeach; ?>
</ul>
<p class="fs-5">Tổng cộng: <strong><?= number_format($total, 0, ',', '.') ?>₫</strong></p>


<div class="mb-4">
<p class="fw-bold">Vui lòng thanh toán trong vòng 15 phút. Sau thời gian này, đơn hàng sẽ bị hủy.</p>
    <div>
        <p class="fw-bold mb-2">Chọn phương thức thanh toán:</p>
        <div class="d-grid gap-2 mb-3">
            <!-- Chỉ hỗ trợ thanh toán qua cổng VNPay -->
            <a href="<?= BASE_URL ?>index.php?pg=vnpay_payment" class="btn btn-primary">Thanh toán tại cổng VNPay</a>
        </div>
        <p>Thời gian còn lại: <span id="countdown" data-booking-id="<?= $booking['booking_id'] ?>">15:00</span></p>
    </div>
</div>


<form method="post" class="d-none">
    <!-- Nút hoàn tất thủ công dự phòng; ẩn theo mặc định, chỉ hiển thị khi IT cho phép khách hàng nhấp vào -->
    <button type="submit" name="complete" class="btn btn-success">Tôi đã thanh toán</button>
</form>