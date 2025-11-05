<?php /** @var array $genres */ ?>
<?php /** @var array $shows */ ?>
<h2 class="h4 mb-4">Quản lý thể loại &amp; vở diễn</h2>


<!-- Edit show section: displayed when an existing show is being edited -->
<?php if (isset($editShow) && is_array($editShow)): ?>
<section class="mb-5">
    <h3 class="h5">Chỉnh sửa vở diễn</h3>
    <form method="post">
        <input type="hidden" name="type" value="show_update">
        <input type="hidden" name="show_id" value="<?= htmlspecialchars($editShow['show_id']) ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Tiêu đề</label>
                <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($editShow['title'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Thời lượng (phút)</label>
                <input type="number" name="duration" class="form-control" min="1" required value="<?= htmlspecialchars($editShow['duration_minutes'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Đạo diễn</label>
                <input type="text" name="director" class="form-control" required value="<?= htmlspecialchars($editShow['director'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Poster URL</label>
                <input type="text" name="poster_url" class="form-control" required value="<?= htmlspecialchars($editShow['poster_image_url'] ?? '') ?>">
            </div>
            <div class="col-md-12">
                <label class="form-label">Mô tả</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editShow['description'] ?? '') ?></textarea>
            </div>
            <!-- Status field removed: show status is determined automatically from performances -->
            <div class="col-md-8">
                <label class="form-label">Thể loại</label>
                <div class="border rounded p-2" style="max-height: 180px; overflow-y: auto;">
                    <?php foreach ($genres as $genre): ?>
                        <?php $checked = in_array((int)$genre['genre_id'], $selectedGenres ?? []) ? 'checked' : ''; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="genre_ids[]" id="editgenre<?= $genre['genre_id'] ?>" value="<?= $genre['genre_id'] ?>" <?= $checked ?> >
                            <label class="form-check-label" for="editgenre<?= $genre['genre_id'] ?>">
                                <?= htmlspecialchars($genre['genre_name']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text text-muted genre-note">Chọn một hoặc nhiều thể loại</div>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Cập nhật vở diễn</button>
                <a href="index.php?pg=admin-category-show" class="btn btn-secondary">Hủy</a>
            </div>
        </div>
    </form>
</section>
<?php endif; ?>


<!-- Form to add a new genre -->
<section class="mb-5">
    <h3 class="h5">Thêm thể loại mới</h3>
    <form method="post" class="row g-3">
        <input type="hidden" name="type" value="genre_add">
        <div class="col-auto flex-grow-1">
            <input type="text" name="genre_name" class="form-control" placeholder="Tên thể loại" required>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-success">Thêm</button>
        </div>
    </form>
</section>


<!-- Edit genre section -->
<?php if (isset($editGenre) && is_array($editGenre)): ?>
<section class="mb-5">
    <h3 class="h5">Chỉnh sửa thể loại</h3>
    <form method="post" class="row g-3">
        <input type="hidden" name="type" value="genre_update">
        <input type="hidden" name="genre_id" value="<?= htmlspecialchars($editGenre['genre_id']) ?>">
        <div class="col-12 col-md-8">
            <input type="text" name="genre_name" class="form-control" value="<?= htmlspecialchars($editGenre['genre_name']) ?>" required>
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary">Lưu</button>
            <a href="index.php?pg=admin-category-show" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</section>
<?php endif; ?>


<!-- List of genres -->
<section class="mb-5">
    <h3 class="h5">Danh sách thể loại</h3>
    <?php if (!empty($genres)): ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Tên thể loại</th>
                        <th scope="col" class="text-center" style="width: 200px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($genres as $genre): ?>
                                <tr>
                            <td><?= htmlspecialchars($genre['genre_id']) ?></td>
                            <td><?= htmlspecialchars($genre['genre_name']) ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <!-- Edit genre button triggers edit form via query parameter -->
                                    <a href="index.php?pg=admin-category-show&edit_genre_id=<?= $genre['genre_id'] ?>" class="btn btn-sm btn-primary">Chỉnh sửa</a>
                                    <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa thể loại này?');" class="d-inline">
                                        <input type="hidden" name="type" value="genre_delete">
                                        <input type="hidden" name="genre_id" value="<?= $genre['genre_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Chưa có thể loại nào.</p>
    <?php endif; ?>
</section>


<!-- Form to add a new show -->
<section class="mb-5">
    <h3 class="h5">Thêm vở diễn mới</h3>
    <form method="post">
        <input type="hidden" name="type" value="show_add">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Tiêu đề</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Thời lượng (phút)</label>
                <input type="number" name="duration" class="form-control" min="1" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Đạo diễn</label>
                <input type="text" name="director" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Poster URL</label>
                <input type="text" name="poster_url" class="form-control" required>
            </div>
            <div class="col-md-12">
                <label class="form-label">Mô tả</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <!-- Status field removed: new shows always start as "Sắp chiếu" -->
            <div class="col-md-8">
                <label class="form-label">Thể loại</label>
                <!-- Custom genre selector: a scrollable list of checkboxes to allow multiple selections -->
                <div class="border rounded p-2" style="max-height: 180px; overflow-y: auto;">
                    <?php foreach ($genres as $genre): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="genre_ids[]" id="genre<?= $genre['genre_id'] ?>" value="<?= $genre['genre_id'] ?>">
                            <label class="form-check-label" for="genre<?= $genre['genre_id'] ?>">
                                <?= htmlspecialchars($genre['genre_name']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text text-muted genre-note">Chọn một hoặc nhiều thể loại</div>


            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success">Thêm vở diễn</button>
            </div>
        </div>
    </form>
</section>


<!-- List of shows -->
<section class="mb-5">
    <h3 class="h5">Danh sách vở diễn</h3>
    <?php if (!empty($shows)): ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Tiêu đề</th>
                        <th scope="col">Thể loại</th>
                        <th scope="col">Thời lượng</th>
                        <th scope="col">Đạo diễn</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col" class="text-center" style="width: 200px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shows as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['show_id']) ?></td>
                            <td><?= htmlspecialchars($s['title']) ?></td>
                            <td><?= htmlspecialchars($s['genres']) ?></td>
                            <td><?= htmlspecialchars($s['duration_minutes']) ?> phút</td>
                            <td><?= htmlspecialchars($s['director']) ?></td>
                            <td><?= htmlspecialchars($s['status']) ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <!-- Edit show button: adds edit_id to the query string to display edit form -->
                                    <a href="index.php?pg=admin-category-show&edit_id=<?= $s['show_id'] ?>" class="btn btn-sm btn-primary">Chỉnh sửa</a>
                                    <?php
                                    // Disable delete button when the show has performances.  The
                                    // controller sets `can_delete` to false when performances exist.
                                    $deleteDisabled = empty($s['can_delete']) || !$s['can_delete'];
                                    ?>
                                    <form method="post"<?= $deleteDisabled ? ' class="d-inline"' : ' class="d-inline"' ?> onsubmit="return <?= $deleteDisabled ? 'false' : 'confirm(\'Bạn có chắc muốn xóa vở diễn này?\')' ?>;">
                                        <input type="hidden" name="type" value="show_delete">
                                        <input type="hidden" name="show_id" value="<?= $s['show_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" <?= $deleteDisabled ? 'disabled' : '' ?>>Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Chưa có vở diễn nào.</p>
    <?php endif; ?>
</section>

