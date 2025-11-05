<h2 class="h4 mb-3">Xác nhận đơn hàng</h2>
<p><strong>Vở diễn:</strong> <?= htmlspecialchars($show['title']) ?> | <strong>Ngày:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($performance['performance_date']))) ?> | <strong>Giờ:</strong> <?= htmlspecialchars(substr($performance['start_time'],0,5)) ?> | <strong>Phòng:</strong> <?= htmlspecialchars($performance['theater_name']) ?></p>


<h5 class="mt-4">Ghế đã chọn</h5>
<ul class="list-group mb-3">
    <?php foreach ($seats as $seatId => $price): ?>
        <?php $label = $seatRows[$seatId] ?? ('#'.$seatId); ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            Ghế <?= htmlspecialchars($label) ?>
            <span><?= number_format($price, 0, ',', '.') ?>₫</span>
        </li>
    <?php endforeach; ?>
</ul>
<p class="fs-5">Tổng cộng: <strong><?= number_format($total, 0, ',', '.') ?>₫</strong></p>


<?php if (empty($_SESSION['user'])): ?>
    <div class="alert alert-warning">
        Bạn cần
        <a href="<?= BASE_URL ?>index.php?pg=login">đăng nhập</a>
        hoặc <a href="<?= BASE_URL ?>index.php?pg=register">đăng ký</a>
        để tiếp tục thanh toán.
    </div>
<?php else: ?>
    <form method="post">
        <button type="submit" class="btn btn-warning">Xác nhận và thanh toán</button>
    </form>
<?php endif; ?>