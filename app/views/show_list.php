<?php

?>


<div class="row g-4">

    <div class="col-12 col-md-3">
        <div class="card bg-dark border-secondary text-light">
            <div class="card-header border-secondary">
                <strong>Lọc kết quả</strong>
            </div>
            <div class="card-body">
                <form method="get" action="<?= BASE_URL ?>index.php">
                    <input type="hidden" name="pg" value="shows">
   
                    <div class="mb-3">
                        <label for="genre" class="form-label">Thể loại</label>
                        <select class="form-select" id="genre" name="genre">
                            <option value="all" <?= ($selectedGenre === '' || strtolower($selectedGenre) === 'all') ? 'selected' : '' ?>>Tất cả</option>
                            <?php foreach ($genres as $g): ?>
                                <?php $value = htmlspecialchars($g['genre_name']); ?>
                                <option value="<?= $value ?>" <?= (strtolower($selectedGenre) === strtolower($g['genre_name'])) ? 'selected' : '' ?>><?= $g['genre_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
      
                    <div class="mb-3">
                        <label class="form-label">Khoảng thời gian</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($startDate) ?>" placeholder="Từ">
                            <span class="input-group-text">–</span>
                            <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($endDate) ?>" placeholder="Đến">
                        </div>
                    </div>
             
                    <div class="mb-3">
                        <label class="form-label">Khoảng giá (VND)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="price_min" value="<?= htmlspecialchars($priceMin) ?>" min="0" placeholder="Từ">
                            <span class="input-group-text">–</span>
                            <input type="number" class="form-control" name="price_max" value="<?= htmlspecialchars($priceMax) ?>" min="0" placeholder="Đến">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Áp dụng</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-9">
        <?php if (empty($shows)): ?>
            <p>Không tìm thấy vở diễn phù hợp.</p>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
                <?php foreach ($shows as $show): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?= htmlspecialchars($show['poster_image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($show['title']) ?>" style="height:220px; object-fit:cover;">
                            <div class="card-body d-flex flex-column">
                               
                                <div class="mb-1">
                                    <?php if (isset($show['avg_rating']) && $show['avg_rating'] !== null): ?>
                                        <?php
                                        $ratingVal = (float)$show['avg_rating'];
                                        $fullStars = floor($ratingVal);
                                        $halfStar  = ($ratingVal - $fullStars) >= 0.5;
                                        
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $fullStars) {
                                                echo '<i class="bi bi-star-fill text-warning"></i>';
                                            } elseif ($halfStar && $i === $fullStars + 1) {
                                                echo '<i class="bi bi-star-half text-warning"></i>';
                                            } else {
                                                echo '<i class="bi bi-star text-warning"></i>';
                                            }
                                        }
                                        ?>
                                        <span class="text-light ms-1">
                                            <?= number_format($show['avg_rating'], 1) ?>/5
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa có đánh giá</span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="card-title mb-2"><?= htmlspecialchars($show['title']) ?></h5>
                                <?php if ($show['price_from'] !== null && $show['price_to'] !== null): ?>
                                    <p class="small mb-1 text-warning">Giá: <?= number_format($show['price_from'], 0, ',', '.') ?> – <?= number_format($show['price_to'], 0, ',', '.') ?> đ</p>
                                <?php endif; ?>
                                <p class="small flex-fill">
                                    <?php
                                    $desc = strip_tags($show['description'] ?? '');
                                    if (mb_strlen($desc) > 100) {
                                        $descShort = mb_substr($desc, 0, 100) . '...';
                                    } else {
                                        $descShort = $desc;
                                    }
                                    echo htmlspecialchars($descShort);
                                    ?>
                                    <a href="<?= BASE_URL ?>index.php?pg=show&id=<?= $show['show_id'] ?>" class="link-warning ms-1">Chi tiết</a>
                                </p>
                                <?php if (!empty($show['nearest_date'])): ?>
                                    <p class="small">Suất gần nhất: <?= date('d/m/Y', strtotime($show['nearest_date'])) ?></p>
                                <?php else: ?>
                                    <p class="small">Chưa có suất diễn</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

