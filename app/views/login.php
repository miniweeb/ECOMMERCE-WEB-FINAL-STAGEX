<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-2">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <h2 class="h4 mb-3">Đăng nhập</h2>
        <form method="post" action="<?= BASE_URL ?>index.php?pg=login">
            <?php $selectedRole = $_POST['role'] ?? 'customer'; ?>
            <div class="mb-3">
                <label class="form-label d-block mb-1">Vai trò</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="role" id="roleCustomer" value="customer" <?= ($selectedRole === 'customer' ? 'checked' : '') ?>>
                    <label class="form-check-label" for="roleCustomer">Khách hàng</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="role" id="roleAdmin" value="admin" <?= ($selectedRole === 'admin' ? 'checked' : '') ?>>
                    <label class="form-check-label" for="roleAdmin">Quản trị</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Email/Tên đăng nhập</label>
                <input type="text" name="identifier" class="form-control" required value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Mật khẩu</label>
                <div class="input-group">
                    <input type="password" name="password" id="login_password" class="form-control" required style="border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                    <span class="input-group-text bg-white" style="cursor: pointer; border-left: 0; border-top-left-radius: 0; border-bottom-left-radius: 0;" onclick="togglePw('login_password', this)">
                        <i class="bi bi-eye-slash"></i>
                    </span>
                </div>
            </div>
            <button type="submit" class="btn btn-warning w-100">Đăng nhập</button>
            <p class="mt-2 mb-0 text-end"><a href="<?= BASE_URL ?>index.php?pg=getpassword" class="text-warning">Quên mật khẩu?</a></p>
        </form>
        <p class="mt-3">Chưa có tài khoản? <a href="<?= BASE_URL ?>index.php?pg=register" class="text-warning">Đăng ký</a></p>
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