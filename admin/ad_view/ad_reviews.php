<h2 class="h4 mb-3">Quản lý đánh giá</h2>
<!-- Form lọc đánh giá theo vở diễn và điểm ranking -->
<form method="get" class="row g-2 mb-3">
    <input type="hidden" name="pg" value="admin-reviews">
    <div class="col-auto">
        <select name="show_id" class="form-select">
            <option value="0">-- Tất cả vở diễn --</option>
            <?php if (isset($shows) && is_array($shows)): ?>
                <?php foreach ($shows as $s): ?>
                    <option value="<?= htmlspecialchars($s['show_id']) ?>" <?php if (!empty($showFilter) && (int)$showFilter === (int)$s['show_id']) echo 'selected'; ?>><?= htmlspecialchars($s['title']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="rating" class="form-select">
            <option value="0">Tất cả đánh giá</option>
            <option value="5" <?php if (!empty($ratingFilter) && (int)$ratingFilter === 5) echo 'selected'; ?>>Đánh giá ≤ 5</option>
            <option value="4" <?php if (!empty($ratingFilter) && (int)$ratingFilter === 4) echo 'selected'; ?>>Đánh giá ≤ 4</option>
            <option value="3" <?php if (!empty($ratingFilter) && (int)$ratingFilter === 3) echo 'selected'; ?>>Đánh giá ≤ 3</option>
            <option value="2" <?php if (!empty($ratingFilter) && (int)$ratingFilter === 2) echo 'selected'; ?>>Đánh giá ≤ 2</option>
            <option value="1" <?php if (!empty($ratingFilter) && (int)$ratingFilter === 1) echo 'selected'; ?>>Đánh giá ≤ 1</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="index.php?pg=admin-reviews" class="btn btn-secondary">Xóa lọc</a>
    </div>
</form>
<?php if (!empty($reviews)): ?>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Vở diễn</th>
                    <th scope="col">Khách hàng</th>
                    <th scope="col">Đánh giá</th>
                    <th scope="col">Nội dung</th>
                    <th scope="col">Ngày</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                    <tr>
                        <th scope="row"><?= htmlspecialchars($r['review_id']) ?></th>
                        <td><?= htmlspecialchars($r['title']) ?></td>
                        <td><?= htmlspecialchars($r['account_name'] ?? $r['name'] ?? '') ?></td>
                        <td>
                            <?php
                            $rating = (int)($r['rating'] ?? 0);
                            for ($k = 1; $k <= 5; $k++) {
                                if ($k <= $rating) {
                                    echo '<i class="bi bi-star-fill text-warning"></i>';
                                } else {
                                    echo '<i class="bi bi-star text-warning"></i>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            //Gói nội dung đánh giá dài với 15 ký tự mỗi dòng để dễ đọc.
                            $text = $r['content'];
                            $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
                            $chunks = array_chunk($words, 15);
                            $lines = array_map(function ($chunk) {
                                return implode(' ', $chunk);
                            }, $chunks);
                            $wrapped = implode("\n", $lines);
                            echo nl2br(htmlspecialchars($wrapped));
                            ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                        <td>
                            <form method="post" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa đánh giá này?');">
                                <input type="hidden" name="delete_id" value="<?= $r['review_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p>Không có đánh giá nào.</p>
<?php endif; ?>