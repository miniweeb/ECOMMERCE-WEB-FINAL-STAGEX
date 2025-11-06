<h2 class="h4 mb-4">Quản lý tài khoản nhân viên</h2>


<?php
$formValues = $formValues ?? [];
?>


<section class="mb-5">
    <h3 class="h5">Thêm tài khoản nhân viên</h3>
    <form method="post" class="row g-3">
        <input type="hidden" name="action" value="staff_add">
        <div class="col-md-4">
            <label class="form-label">Tên tài khoản</label>
            <input type="text" name="account_name" class="form-control" required value="<?= htmlspecialchars($formValues['account_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($formValues['email'] ?? '') ?>">
        </div>
        <div class="col-md-4 position-relative">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" id="staff_password" class="form-control" style="padding-right:2.5rem;" required value="<?= htmlspecialchars($formValues['password'] ?? '') ?>">
            <span style="position:absolute; top:50%; right:0.75rem; transform:translateY(-50%); cursor:pointer; color:#ffc107;" onclick="togglePw('staff_password', this)"><i class="bi bi-eye-slash"></i></span>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-success">Thêm nhân viên</button>
        </div>
    </form>
</section>


<section>
    <h3 class="h5">Danh sách nhân viên</h3>
    <?php if (!empty($staff)): ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Tên tài khoản</th>
                        <th scope="col">Email</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col" class="text-center" style="width: 200px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $s): ?>
                        <tr>
                            <form method="post">
                                <input type="hidden" name="action" value="staff_update">
                                <input type="hidden" name="user_id" value="<?= $s['user_id'] ?>">
                                <td><?= htmlspecialchars($s['user_id']) ?></td>
                                <td><input type="text" name="account_name" class="form-control form-control-sm" value="<?= htmlspecialchars($s['account_name'] ?? '') ?>" required></td>
                                <td><input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($s['email'] ?? '') ?>" required></td>
                                <td>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="hoạt động" <?= ($s['status'] === 'hoạt động') ? 'selected' : '' ?>>Hoạt động</option>
                                        <option value="khóa" <?= ($s['status'] === 'khóa') ? 'selected' : '' ?>>Bị khóa</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                    <button type="submit" class="btn btn-sm btn-primary">Cập nhật</button>
                            </form>
                            <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản nhân viên này?');">
                                <input type="hidden" name="action" value="staff_delete">
                                <input type="hidden" name="user_id" value="<?= $s['user_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Chưa có nhân viên nào.</p>
    <?php endif; ?>
</section>


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

