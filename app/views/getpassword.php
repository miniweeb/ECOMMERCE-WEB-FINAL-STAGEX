<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card bg-secondary text-light mt-4">
            <div class="card-body p-4">
                <?php if (empty($_SESSION['otp_sent'])): ?>
                    <h2 class="h5 mb-3 text-center">Quên mật khẩu</h2>
                    <p class="small text-muted">Nhập email của bạn để nhận mã xác thực.</p>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($info)): ?>
                        <div class="alert alert-success py-2">
                            <?= htmlspecialchars($info) ?>
                        </div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">Gửi mã xác thực</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>