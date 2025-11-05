<?php
namespace App\Controllers;

class AccountsController extends AdBaseController
{
    public function index(): void
    {
        if (!$this->ensureAdmin()) return;
        $userModel = new \App\Models\User();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'staff_add') {
                $accName = trim($_POST['account_name'] ?? '');
                $email   = trim($_POST['email'] ?? '');
                $pwd     = trim($_POST['password'] ?? '');
                $formValues = [
                    'account_name' => $accName,
                    'email'        => $email,
                    'password'     => $pwd
                ];
                $errorMsg = '';
                if ($accName && $email && $pwd) {
                    if (strlen($pwd) < 8) {
                        $errorMsg = 'Mật khẩu phải có ít nhất 8 ký tự.';
                    } elseif ($userModel->findByEmail($email)) {
                        $errorMsg = 'Email đã tồn tại.';
                    } else {
                        $userModel->create($email, $pwd, $accName, 'staff', true);
                        $_SESSION['success'] = 'Đã thêm tài khoản nhân viên.';
                        $this->redirect('index.php?pg=admin-accounts');
                        return;
                    }
                } else {
                    $errorMsg = 'Vui lòng nhập đầy đủ thông tin tài khoản.';
                }
                $_SESSION['error'] = $errorMsg;
                $staff = $userModel->getStaff();
                $this->renderAdmin('ad_account', [
                    'staff'      => $staff,
                    'formValues' => $formValues
                ]);
                return;
            }
            if ($action === 'staff_update') {
                $uid     = (int)($_POST['user_id'] ?? 0);
                $accName = trim($_POST['account_name'] ?? '');
                $email   = trim($_POST['email'] ?? '');
                $status  = trim($_POST['status'] ?? 'hoạt động');
                if ($uid > 0 && $accName && $email) {
                    if ($userModel->updateStaff($uid, $accName, $email, $status)) {
                        $_SESSION['success'] = 'Cập nhật thành công';
                    } else {
                        $_SESSION['error'] = 'Không thể cập nhật tài khoản.';
                    }
                } else {
                    $_SESSION['error'] = 'Thiếu thông tin cập nhật.';
                }
                $this->redirect('index.php?pg=admin-accounts');
                return;
            }
            if ($action === 'staff_delete') {
                $uid = (int)($_POST['user_id'] ?? 0);
                if ($uid > 0) {
                    if ($userModel->deleteStaff($uid)) {
                        $_SESSION['success'] = 'Đã xóa tài khoản nhân viên.';
                    } else {
                        $_SESSION['error'] = 'Không thể xóa tài khoản nhân viên.';
                    }
                } else {
                    $_SESSION['error'] = 'Thiếu thông tin xóa.';
                }
                $this->redirect('index.php?pg=admin-accounts');
                return;
            }
        }
        $staff = $userModel->getStaff();
        $this->renderAdmin('ad_account', ['staff' => $staff]);
    }
}