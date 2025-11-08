<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="h4 mb-3">Đăng ký</h2>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Tên tài khoản</label>
                <input type="text" name="account_name" class="form-control" required
                    value="<?= htmlspecialchars($account_name ?? ($_POST['account_name'] ?? '')) ?>">
                <small class="text-muted">Tên này sẽ được dùng để đăng nhập và hiển thị công khai. Bạn có thể cập nhật họ tên thật trong hồ sơ.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required
                    value="<?= htmlspecialchars($email ?? ($_POST['email'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Họ tên</label>
                <input type="text" name="full_name" class="form-control" required
                    value="<?= htmlspecialchars($full_name ?? ($_POST['full_name'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Ngày sinh</label>
                <input type="date" name="date_of_birth" class="form-control" required
                    value="<?= htmlspecialchars($date_of_birth ?? ($_POST['date_of_birth'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Địa chỉ <small class="text-muted">(tùy chọn)</small></label>
                <input type="text" name="address" class="form-control"
                    value="<?= htmlspecialchars($address ?? ($_POST['address'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Số điện thoại <small class="text-muted">(tùy chọn)</small></label>
                <input type="text" name="phone" class="form-control"
                    value="<?= htmlspecialchars($phone ?? ($_POST['phone'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Mật khẩu</label>
                <div class="input-group">
                    <input type="password" name="password" id="register_password" class="form-control" required style="border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                    <span class="input-group-text bg-white" style="cursor: pointer; border-left: 0; border-top-left-radius: 0; border-bottom-left-radius: 0;" onclick="togglePw('register_password', this)">
                        <i class="bi bi-eye-slash"></i>
                    </span>
                </div>
            </div>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-warning w-100">Đăng ký</button>
        </form>
    </div>
</div>

<script>
    function togglePw(inputId, iconEl) {
        var input = document.getElementById(inputId);
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            iconEl.querySelector('i').classList.remove('bi-eye-slash');
            iconEl.querySelector('i').classList.add('bi-eye');
        } else {
            input.type = 'password';
            iconEl.querySelector('i').classList.remove('bi-eye');
            iconEl.querySelector('i').classList.add('bi-eye-slash');
        }
    }
</script>