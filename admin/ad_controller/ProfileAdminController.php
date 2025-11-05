<?php
namespace App\Controllers;

use App\Models\UserDetail;
class ProfileController extends BaseController
{
    public function index(): void
    {
        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            $_SESSION['error'] = 'Bạn cần đăng nhập để xem hồ sơ của mình.';
            $this->redirect('index.php?pg=login');
            return;
        }
        $user = $_SESSION['user'];
        $userId = (int)($user['user_id'] ?? 0);
        $detailModel = new UserDetail();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName = trim($_POST['full_name'] ?? '');
            $dob      = trim($_POST['date_of_birth'] ?? '');
            $address  = trim($_POST['address'] ?? '');
            $phone    = trim($_POST['phone'] ?? '');
            $dob     = $dob     !== '' ? $dob     : null;
            $fullName= $fullName!== '' ? $fullName: null;
            $address = $address !== '' ? $address : null;
            $phone   = $phone   !== '' ? $phone   : null;
            if ($detailModel->save($userId, $fullName, $dob, $address, $phone)) {
                $_SESSION['success'] = 'Cập nhật thành công';
            } else {
                $_SESSION['error'] = 'Không thể cập nhật thông tin hồ sơ.';
            }
            $this->redirect('index.php?pg=profile');
            return;
        }
        $details = $detailModel->find($userId);
        if (!empty($user['user_type']) && ($user['user_type'] === 'admin' || $user['user_type'] === 'staff')) {
            $this->redirect('../admin/index.php?pg=admin-profile');
            return;
        }
        $this->render('profile', [
            'details' => $details,
            'user'    => $user
        ]);
    }

    public function resetPassword(): void
    {
        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            $_SESSION['error'] = 'Bạn cần đăng nhập để thay đổi mật khẩu.';
            $this->redirect('index.php?pg=login');
            return;
        }
        $user = $_SESSION['user'];
        $userId = (int)($user['user_id'] ?? 0);
        $userModel = new \App\Models\User();
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pwd1 = trim($_POST['password'] ?? '');
            $pwd2 = trim($_POST['confirm_password'] ?? '');
            if (!$pwd1 || !$pwd2) {
                $error = 'Vui lòng nhập mật khẩu mới và xác nhận mật khẩu.';
            } elseif (strlen($pwd1) < 8) {
                $error = 'Mật khẩu phải có ít nhất 8 ký tự.';
            } elseif ($pwd1 !== $pwd2) {
                $error = 'Mật khẩu xác nhận không khớp.';
            } else {
                if ($userModel->updatePassword($userId, $pwd1)) {
                    $_SESSION['success'] = 'Đổi mật khẩu thành công.';
                    $this->redirect('index.php?pg=profile');
                    return;
                } else {
                    $error = 'Không thể cập nhật mật khẩu.';
                }
            }
        }
        $this->render('profile_reset_password', [
            'error' => $error
        ]);
    }
}