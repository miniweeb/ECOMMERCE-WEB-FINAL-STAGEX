<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card bg-secondary text-light mt-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3 text-center">Đặt lại mật khẩu</h2>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3 position-relative">
                        <label for="password" class="form-label">Mật khẩu mới</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" required style="border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                            <span class="input-group-text bg-white" style="cursor: pointer; border-left: 0; border-top-left-radius: 0; border-bottom-left-radius: 0;" onclick="togglePw('password', this)">
                                <i class="bi bi-eye-slash"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required style="border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                            <span class="input-group-text bg-white" style="cursor: pointer; border-left: 0; border-top-left-radius: 0; border-bottom-left-radius: 0;" onclick="togglePw('confirm_password', this)">
                                <i class="bi bi-eye-slash"></i>
                            </span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Cập nhật mật khẩu</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle visibility of password fields.  When the eye icon is clicked,
// it switches the input type between password and text and toggles
// the icon between eye and eye-slash.
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