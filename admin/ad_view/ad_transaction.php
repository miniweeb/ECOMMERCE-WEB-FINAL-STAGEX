<h2 class="h4 mb-4">Quản lý đơn hàng</h2>


<!-- Khu vực tìm kiếm và danh sách đơn hàng -->
<!-- Tìm kiếm đơn hàng -->
<div class="mb-4">
    <h3 class="h5">Tìm kiếm đơn hàng</h3>
    <form method="get" class="row g-3" action="index.php">
        <!-- Preserve page identifier -->
        <input type="hidden" name="pg" value="admin-transactions">
        <div class="col-md-3">
            <label class="form-label">Email khách hàng</label>
            <input type="text" name="email" class="form-control" value="<?= htmlspecialchars($filterEmail ?? '') ?>" placeholder="Nhập email">
        </div>
        <div class="col-md-2">
            <label class="form-label">Mã đơn hàng</label>
            <input type="text" name="booking_id" class="form-control" value="<?= htmlspecialchars($filterBooking ?? '') ?>" placeholder="ID đơn hàng">
        </div>
        <div class="col-md-2">
            <label class="form-label">Ngày đặt</label>
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Vở diễn</label>
            <select name="show" class="form-select">
                <option value="">Tất cả</option>
                <?php foreach ($showsList as $s): ?>
                    <?php $sel = ((string)($filterShow ?? '') === (string)$s['show_id']) ? 'selected' : ''; ?>
                    <option value="<?= $s['show_id'] ?>" <?= $sel ?>><?= htmlspecialchars($s['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Suất diễn</label>
            <select name="performance" id="performance-select" class="form-select">
                <option value="">Tất cả</option>
                <?php foreach ($perfsList as $p): ?>
                    <?php $sel = ((string)($filterPerf ?? '') === (string)$p['performance_id']) ? 'selected' : ''; ?>
                    <option value="<?= $p['performance_id'] ?>" data-show-id="<?= $p['show_id'] ?>" <?= $sel ?>><?= htmlspecialchars($p['show_title']) ?> - <?= htmlspecialchars(date('d/m/Y', strtotime($p['performance_date']))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
        </div>
    </form>
</div>
<!-- Danh sách đơn hàng -->
<div class="mb-4">
    <?php if ($bookings): ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Mã suất</th>
                        <th>Tổng (₫)</th>
                        <th>Trạng thái đơn</th>
                        <th>Trạng thái thanh toán</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['booking_id']) ?></td>
                            <td><?= htmlspecialchars($b['email']) ?></td>
                            <td><?= htmlspecialchars($b['performance_id']) ?></td>
                            <td><?= number_format($b['total_amount'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($b['booking_status']) ?></td>
                            <td><?= htmlspecialchars($b['payment_status']) ?></td>
                            <td><?= htmlspecialchars($b['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Không có đơn hàng.</p>
    <?php endif; ?>
</div>


<script>
// Lọc các suất diễn khi 1 vở diễn được chọn
document.addEventListener('DOMContentLoaded', function () {
    var showSelect = document.querySelector('select[name="show"]');
    var perfSelect = document.getElementById('performance-select');
    if (!showSelect || !perfSelect) return;
    function filterPerformances() {
        var selectedShow = showSelect.value;
        var options = perfSelect.options;
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            var showId = opt.getAttribute('data-show-id');
            // Luôn giữ opt đầu tiên là hiển thị ("Tất cả")
            if (!showId) continue;
            if (!selectedShow || selectedShow === '') {
                opt.hidden = false;
            } else {
                opt.hidden = (showId !== selectedShow);
            }
        }
        // Nếu opt đang được chọn bị ẩn thì đặt lại về mặc định
        if (perfSelect.selectedIndex > 0 && perfSelect.options[perfSelect.selectedIndex].hidden) {
            perfSelect.selectedIndex = 0;
        }
    }
    showSelect.addEventListener('change', filterPerformances);
    // Áp dụng sẵn bộ lọc mặc định khi tải trang
    filterPerformances();
});
</script>