<?php /** @var array $performances */ ?>
<?php /** @var array $shows */ ?>
<?php /** @var array $theaters */ ?>
<h2 class="h4 mb-4">Quản lý suất diễn</h2>


<!-- Form to add a new performance -->
<section class="mb-5">
    <h3 class="h5">Thêm suất diễn</h3>
    <form method="post" class="row g-3">
        <input type="hidden" name="type" value="performance_add">
        <div class="col-md-4">
            <label class="form-label">Vở diễn</label>
            <select name="show_id" class="form-select" required>
                <option value="">Chọn vở diễn</option>
                <?php foreach ($shows as $s): ?>
                    <option value="<?= $s['show_id'] ?>"><?= htmlspecialchars($s['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Rạp</label>
            <select name="theater_id" class="form-select" required>
                <option value="">Chọn rạp</option>
                <?php foreach ($theaters as $t): ?>
                    <option value="<?= $t['theater_id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Ngày diễn</label>
            <input type="date" name="performance_date" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Giờ bắt đầu</label>
            <input type="time" name="start_time" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Giờ kết thúc</label>
            <!-- End time is automatically computed based on the show's duration.  This field is read-only to prevent manual adjustment. -->
            <input type="time" name="end_time" class="form-control" placeholder="Tự động" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label">Giá vé</label>
            <!-- Use integer pricing (VND) without fractional part -->
            <input type="number" step="1" name="price" class="form-control" min="0" required>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-success">Thêm suất diễn</button>
        </div>
    </form>
</section>


<!-- Edit performance section (displayed between add and list) -->
<?php if (isset($editPerformance) && is_array($editPerformance)): ?>
<section class="mb-5">
    <h3 class="h5">Chỉnh sửa suất diễn</h3>
    <form method="post" class="row g-3">
        <input type="hidden" name="type" value="performance_update">
        <input type="hidden" name="performance_id" value="<?= htmlspecialchars($editPerformance['performance_id']) ?>">
        <div class="col-md-4">
            <label class="form-label">Vở diễn</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($editPerformance['title'] ?? $editPerformance['show_title'] ?? '') ?>" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label">Rạp</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($editPerformance['theater_name']) ?>" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label">Ngày diễn</label>
            <input type="date" class="form-control" value="<?= htmlspecialchars($editPerformance['performance_date']) ?>" disabled>
        </div>
        <div class="col-md-3">
            <label class="form-label">Giờ bắt đầu</label>
            <input type="time" class="form-control" value="<?= htmlspecialchars(substr($editPerformance['start_time'],0,5)) ?>" disabled>
        </div>
        <div class="col-md-3">
            <label class="form-label">Giờ kết thúc</label>
            <input type="time" class="form-control" value="<?= htmlspecialchars(substr($editPerformance['end_time'],0,5)) ?>" disabled>
        </div>
        <div class="col-md-3">
            <label class="form-label">Giá vé</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars(number_format((float)$editPerformance['price'],0,',','.')) ?>đ" disabled>
        </div>
        <div class="col-md-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select" required>
                <?php
                // Use Vietnamese status strings directly as both the value and label.  These
                // correspond to the enum values in the `performances` table.
                // Only allow editing to "Đang mở bán" or "Đã hủy".  The status
                // "Đã kết thúc" is set automatically when a performance ends.
                $statuses = [
                    'Đang mở bán' => 'Đang mở bán',
                    'Đã hủy'     => 'Đã hủy'
                ];
                $current = $editPerformance['status'] ?? '';
                foreach ($statuses as $value => $label):
                    $sel = ($current === $value) ? 'selected' : '';
                    ?>
                    <option value="<?= $value ?>" <?= $sel ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </div>
    </form>
</section>
<?php endif; ?>


<!-- List of performances -->
<section class="mb-5">
    <h3 class="h5">Danh sách suất diễn</h3>
    <?php if (!empty($performances)): ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Vở diễn</th>
                        <th scope="col">Rạp</th>
                        <th scope="col">Ngày</th>
                        <th scope="col">Bắt đầu</th>
                        <th scope="col">Kết thúc</th>
                        <th scope="col">Giá vé</th>
                        <th scope="col" class="text-center" style="width: 200px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($performances as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['performance_id']) ?></td>
                            <td><?= htmlspecialchars($p['title'] ?? $p['show_title'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['theater_name']) ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($p['performance_date']))) ?></td>
                            <td><?= htmlspecialchars(substr($p['start_time'],0,5)) ?></td>
                            <td><?= htmlspecialchars($p['end_time'] ? substr($p['end_time'],0,5) : '') ?></td>
                            <td><?= htmlspecialchars(number_format((float)$p['price'], 0, ',', '.')) ?>đ</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <!-- Edit performance button.  Clicking triggers loading of the edit form by adding edit_id to query string -->
                                    <a href="index.php?pg=admin-performance&edit_id=<?= $p['performance_id'] ?>" class="btn btn-sm btn-primary">Chỉnh sửa</a>
                                    <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa suất diễn này?');" class="d-inline">
                                        <input type="hidden" name="type" value="performance_delete">
                                        <input type="hidden" name="performance_id" value="<?= $p['performance_id'] ?>">
                                        <?php
                                        // Only allow deletion when performance status is "Đã kết thúc".
                                        $canDel = isset($p['status']) && $p['status'] === 'Đã kết thúc';
                                        ?>
                                        <button type="submit" class="btn btn-sm btn-danger" <?= $canDel ? '' : 'disabled' ?>>Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Chưa có suất diễn nào.</p>
    <?php endif; ?>
</section>




<script>
// Auto-calculate end time based on selected show duration and start time.
document.addEventListener('DOMContentLoaded', function () {
    var showSelect = document.querySelector('select[name="show_id"]');
    var startInput = document.querySelector('input[name="start_time"]');
    var endInput   = document.querySelector('input[name="end_time"]');
    // Map show IDs to their duration in minutes
    var showDurations = {};
    <?php foreach ($shows as $s): ?>
    showDurations['<?= $s['show_id'] ?>'] = <?= (int)$s['duration_minutes'] ?>;
    <?php endforeach; ?>
    function updateEnd() {
        var sid = showSelect.value;
        var duration = showDurations[sid] || 0;
        var startVal = startInput.value;
        if (duration > 0 && startVal) {
            var parts = startVal.split(':');
            if (parts.length >= 2) {
                var h = parseInt(parts[0], 10);
                var m = parseInt(parts[1], 10);
                var date = new Date(0, 0, 0, h, m);
                date.setMinutes(date.getMinutes() + duration);
                var hh = date.getHours().toString().padStart(2, '0');
                var mm = date.getMinutes().toString().padStart(2, '0');
                endInput.value = hh + ':' + mm;
            }
        } else {
            endInput.value = '';
        }
    }
    if (showSelect && startInput && endInput) {
        showSelect.addEventListener('change', updateEnd);
        startInput.addEventListener('change', updateEnd);
    }
});
</script>

