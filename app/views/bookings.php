<h2 class="h4 mb-3">Vé của tôi</h2>
<a href="index.php" class="btn btn-sm btn-secondary mb-3">Trở về trang chủ</a>
<?php if ($bookings): ?>
    <div class="table-responsive">
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mã suất</th>
                    <th>Ghế</th>
                    <th>Tổng (₫)</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= $b['booking_id'] ?></td>
                        <td><?= $b['performance_id'] ?></td>
                        <td><?= htmlspecialchars($b['seats'] ?? '') ?></td>
                        <td><?= number_format($b['total_amount'], 0, ',', '.') ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($b['created_at']))) ?></td>
                        <td><a href="<?= BASE_URL ?>index.php?pg=booking-detail&id=<?= $b['booking_id'] ?>" class="btn btn-sm btn-warning">Chi tiết</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p>Bạn chưa có đơn hàng nào.</p>
<?php endif; ?>