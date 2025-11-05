<h2 class="h4 mb-4">Đặt lại mật khẩu</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="post" class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="password">Mật khẩu mới</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="confirm_password">Xác nhận mật khẩu</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-warning">Cập nhật mật khẩu</button>
    </div>
</form>