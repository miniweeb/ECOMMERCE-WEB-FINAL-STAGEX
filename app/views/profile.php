<div class="container-fluid px-0">
    <h2 class="h4 mb-4">Hồ sơ cá nhân</h2>
    <div class="card bg-secondary text-light mb-5">
        <div class="card-body d-flex flex-column flex-md-row align-items-start">
            <!-- Avatar placeholder -->
            <div class="me-md-4 mb-3 mb-md-0">
                <div class="bg-secondary rounded-circle" style="width:100px;height:100px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;">
                    <i class="bi bi-person-circle"></i>
                </div>
            </div>
            <!-- User info and form -->
            <div class="flex-grow-1">
                <h5 class="mb-1"><?= htmlspecialchars($user['account_name'] ?? $user['email']) ?></h5>
                <p class="text-muted mb-4"><?= htmlspecialchars($user['email']) ?></p>
                <form method="post" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="full_name">Họ tên</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($details['full_name'] ?? '') ?>" placeholder="Nhập họ tên">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="date_of_birth">Ngày sinh</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($details['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="address">Địa chỉ</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($details['address'] ?? '') ?>" placeholder="Nhập địa chỉ (tùy chọn)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Số điện thoại</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($details['phone'] ?? '') ?>" placeholder="Nhập số điện thoại (tùy chọn)">
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-warning">Cập nhật hồ sơ</button>
                    </div>
                <div class="col-12 mt-3">
                    <!-- Link to reset password page for logged in user -->
                    <a href="index.php?pg=profile-reset-password" class="btn btn-outline-light">Đặt lại mật khẩu</a>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Footer is included by BaseController::render() -->