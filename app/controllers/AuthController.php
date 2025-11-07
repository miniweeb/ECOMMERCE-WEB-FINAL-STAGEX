<?php

namespace App\Controllers;

use App\Models\User;

class AuthController extends BaseController
{
    public function login(): void
    {
        if (!empty($_SESSION['user'])) {
            $this->redirect('index.php');
            return;
        }
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Người dùng đã nhấn nút đăng nhập
            $identifier = trim($_POST['identifier'] ?? '');
            $password   = trim($_POST['password'] ?? '');
            $role = $_POST['role'] ?? 'customer';
            //Kiểm tra thông tin đăng nhập
            $userModel = new User();
            $user = $userModel->findByEmailOrAccountName($identifier);
            if (!$user) {
                $error = 'Tài khoản không tồn tại.';
            } elseif (!password_verify($password, $user['password'])) {
                $error = 'Sai mật khẩu, hãy nhập lại';
            } else {
                if ($user['user_type'] === 'staff') {
                    $user['user_type'] = 'admin';
                }
                if ($role === 'admin' && $user['user_type'] !== 'admin') {
                    $error = 'Bạn không có quyền vào cổng này.';
                } elseif ($user['status'] !== 'hoạt động') {
                    $error = 'Tài khoản của bạn đã bị khóa.';
                } elseif (!$user['is_verified'] && $user['user_type'] === 'customer') {
                    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expires = date('Y-m-d H:i:s', time() + 10 * 60);
                    $userModel->setOtp((int)$user['user_id'], $otp, $expires);
                    //gửi mail nếu khách hàng chưa xác minh
                    $this->sendOtpEmail($user['email'], $otp);
                    $_SESSION['pending_user_id'] = $user['user_id'];
                    $_SESSION['pending_role']      = $role;
                    $_SESSION['info']             = 'Một mã xác thực đã được gửi tới email của bạn. Vui lòng nhập mã để xác minh.';
                    $this->redirect('index.php?pg=verify');
                    return;
                } elseif (empty($error)) {
                    //Nếu không có lỗi, lưu thông tin người dùng vào phiên đăng nhập
                    $_SESSION['user']    = $user;
                    $_SESSION['success'] = 'Đăng nhập thành công!';
                    if ($user['user_type'] === 'admin') {
                        $this->redirect('../admin/index.php?pg=admin-index');
                    } else {
                        $this->redirect('index.php');
                    }
                    return;
                }
            }
        }
        $this->render('login', ['error' => $error]);
    }

    public function register(): void
    {
        if (!empty($_SESSION['user'])) {
            unset($_SESSION['user']); //xóa biến user khỏi session
            session_destroy(); // xóa toàn bộ session
        }
        $error = '';
        $email       = trim($_POST['email'] ?? '');
        $password    = trim($_POST['password'] ?? '');
        $accountName = trim($_POST['account_name'] ?? '');
        $fullName    = trim($_POST['full_name'] ?? '');
        $dob         = trim($_POST['date_of_birth'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $userModel   = new User();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Kiểm tra trùng lặp email/tên tài khoản (email đã tồn tại và đã xác thực)
            $existingByEmail = $userModel->findByEmail($email);
            $existingByName  = $userModel->findByAccountName($accountName);
            $emailTaken = $existingByEmail && ($existingByEmail['is_verified'] ?? 0) == 1;
            $nameTaken  = $existingByName && ($existingByName['is_verified'] ?? 0) == 1;
            if ($nameTaken || $emailTaken) {
                $error = 'Tài khoản đã được sử dụng vui lòng nhập tên hoặc email khác';
            } elseif (!$email || !$password || !$accountName || !$fullName || !$dob) {
                $error = 'Vui lòng nhập đầy đủ thông tin.';
            } elseif (strlen($password) < 8) {
                $error = 'Mật khẩu phải có ít nhất 8 ký tự.';
            } else {
                //Nếu email đã tồn tại nhưng chưa xác minh thì lấy thông tin của account cũ chứ không tạo mới
                if ($existingByEmail && ($existingByEmail['is_verified'] ?? 0) == 0) {
                    $userId = (int)$existingByEmail['user_id'];
                    $hash   = password_hash($password, PASSWORD_DEFAULT);
                    $pdo    = $userModel->getPdo();
                    try {
                        //chuẩn bị một câu lệnh SQL có chứa tham số)
                        $stmt = $pdo->prepare('CALL proc_update_unverified_user_password_name(:uid, :pwd, :acc)');
                        //Gửi dữ liệu và chạy lệnh trên db
                        $stmt->execute([
                            'uid' => $userId,
                            'pwd' => $hash,
                            'acc' => $accountName
                        ]);
                        $stmt->closeCursor(); //đóng con trỏ sau khi chạy proce, giải phóng tài nguyên
                    } catch (\Throwable $e) {
                    }

                    $newUserId = $userId; //Gán lại id của người dùng để tiếp tục xử lý

                } elseif ($existingByName && ($existingByName['is_verified'] ?? 0) == 0) {
                    $userId = (int)$existingByName['user_id'];
                    $hash   = password_hash($password, PASSWORD_DEFAULT);
                    $pdo    = $userModel->getPdo();
                    try {
                        $stmt = $pdo->prepare('CALL proc_update_unverified_user_password_email(:uid, :pwd, :email)');
                        $stmt->execute([
                            'uid'   => $userId,
                            'pwd'   => $hash,
                            'email' => $email
                        ]);
                        $stmt->closeCursor();
                    } catch (\Throwable $e) {
                    }

                    $newUserId = $userId;
                } else {
                    $userModel->create($email, $password, $accountName, 'customer', false);
                    $new = $userModel->findByEmail($email);
                    $newUserId = $new ? (int)$new['user_id'] : 0;
                }

                if ($newUserId) {
                    try {
                        $detailModel = new \App\Models\UserDetail();
                        $addr = $address !== '' ? $address : null;
                        $ph   = $phone   !== '' ? $phone   : null;
                        $detailModel->save($newUserId, $fullName, $dob, $addr, $ph);
                    } catch (\Throwable $detailEx) {
                    }

                    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expires = date('Y-m-d H:i:s', time() + 10 * 60);
                    $userModel->setOtp($newUserId, $otp, $expires);
                    $this->sendOtpEmail($email, $otp);

                    $_SESSION['pending_user_id'] = $newUserId;
                    $_SESSION['pending_role'] = 'customer';
                    $_SESSION['info'] = 'Một mã xác thực đã được gửi tới email của bạn. Vui lòng nhập mã để xác minh.';
                    $this->redirect('index.php?pg=verify');
                    return;
                }
            }
        }
        $this->render('register', [
            'error'        => $error,
            'account_name' => $accountName,
            'email'        => $email,
            'full_name'    => $fullName,
            'date_of_birth' => $dob,
            'address'      => $address,
            'phone'        => $phone
        ]);
    }

    public function verify(): void
    {
        if (empty($_SESSION['pending_user_id'])) {
            $this->redirect('index.php');
            return;
        }

        $error = '';
        $userModel = new User();
        $userId = (int)$_SESSION['pending_user_id'];
        $role = $_SESSION['pending_role'] ?? 'customer';
        $remainingSeconds = 0;

        if ($userId) {
            $pendingUser = $userModel->findById($userId);
            if ($pendingUser && !empty($pendingUser['otp_expires_at'])) {
                $expiresAt = strtotime($pendingUser['otp_expires_at']);
                $remainingSeconds = max(0, $expiresAt - time());
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $otpInput = trim($_POST['otp'] ?? '');
            if (!$otpInput) {
                $error = 'Vui lòng nhập mã xác thực.';
            } else {
                if ($userModel->verifyOtp($userId, $otpInput)) {
                    $user = $userModel->findById($userId);
                    if ($user) {
                        if ($user['status'] !== 'hoạt động') {
                            $error = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
                        } else {
                            unset($_SESSION['pending_user_id'], $_SESSION['pending_role']);
                            $_SESSION['user'] = $user;
                            $_SESSION['success'] = 'Xác minh thành công! Bạn đã đăng nhập.';
                            $userType = $user['user_type'] ?? 'customer';
                            if ($userType === 'staff') {
                                $userType = 'admin';
                                $user['user_type'] = 'admin';
                                $_SESSION['user'] = $user;
                            }
                            if ($userType === 'admin') {
                                $this->redirect('../admin/index.php?pg=admin-index');
                            } else {
                                $this->redirect('index.php');
                            }
                            return;
                        }
                    } else {
                        $error = 'Không tìm thấy người dùng.';
                    }
                } else {
                    $error = 'Mã OTP không đúng hoặc đã hết hạn. Vui lòng kiểm tra lại.';
                }
            }
        }
        $this->render('verify', [
            'error' => $error,
            'remaining' => $remainingSeconds
        ]);
    }

    public function sendOtpEmail(string $recipient, string $otpCode): void
    {
        require_once __DIR__ . '/../../vendor/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../vendor/PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/../../vendor/PHPMailer/src/Exception.php';

        $config = require __DIR__ . '/../../config/mail.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            if (!empty($config['host'])) {
                $mail->isSMTP();
                $mail->Host       = $config['host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['username'];
                $mail->Password   = $config['password'];
                $mail->SMTPSecure = $config['encryption'] ?? 'tls';
                $mail->Port       = $config['port'] ?? 587;
            }
            $fromEmail = $config['from_email'] ?? $config['username'] ?? 'no-reply@stagex.local';
            $fromName  = $config['from_name'] ?? 'StageX';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($recipient);
            $mail->isHTML(false);
            $mail->Subject = 'Mã xác thực StageX';
            $body  = "Kính gửi Quý khách {$recipient}\n\n";
            $body .= "Chúng tôi đã nhận được yêu cầu xác thực trên hệ thống.\n\n";
            $body .= "Vui lòng nhập mã xác minh bên dưới để tiếp tục thực hiện thao tác trên hệ thống.\n\n";
            $body .= "Mã xác minh: {$otpCode}\n\n";
            $body .= "Xin chân thành cảm ơn Quý khách!";
            $mail->Body = $body;
            $mail->send();
        } catch (\Throwable $e) {
            error_log('Không thể gửi email OTP: ' . $e->getMessage());
        }
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        session_destroy();
        $this->redirect('index.php');
    }

    public function forgot(): void
    {
        $userModel = new User();
        $error = '';
        $info  = '';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            unset($_SESSION['reset_user_id'], $_SESSION['reset_verified'], $_SESSION['reset_user_email']);
            $stage = 'request';
        } else {
            $stage = 'request';
            if (!empty($_SESSION['reset_user_id']) && empty($_SESSION['reset_verified'])) {
                $stage = 'otp';
            } elseif (!empty($_SESSION['reset_user_id']) && !empty($_SESSION['reset_verified'])) {
                $stage = 'reset';
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($stage === 'request') {
                $emailInput = trim($_POST['email'] ?? '');
                if (!$emailInput) {
                    $error = 'Vui lòng nhập email.';
                } elseif (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Vui lòng nhập email hợp lệ.';
                } else {
                    $user = $userModel->findByEmail($emailInput);
                    if (!$user) {
                        $error = 'Không tìm thấy tài khoản.';
                    } else {
                        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                        $expires = date('Y-m-d H:i:s', time() + 10 * 60);
                        $userModel->setOtp((int)$user['user_id'], $otp, $expires);
                        $this->sendOtpEmail($user['email'], $otp);

                        $_SESSION['pending_user_id'] = $user['user_id'];
                        $_SESSION['pending_role'] = 'forgot';
                        $_SESSION['info'] = 'Mã xác thực đã được gửi. Vui lòng kiểm tra email.';

                        $this->redirect('index.php?pg=verify');
                        return;
                    }
                }
            } elseif ($stage === 'reset') {
                $pwd  = trim($_POST['password'] ?? '');
                $pwd2 = trim($_POST['confirm_password'] ?? '');

                if (!$pwd || !$pwd2) {
                    $error = 'Vui lòng nhập mật khẩu mới và xác nhận.';
                } elseif (strlen($pwd) < 8) {
                    $error = 'Mật khẩu phải có ít nhất 8 ký tự.';
                } elseif ($pwd !== $pwd2) {
                    $error = 'Mật khẩu xác nhận không khớp.';
                } else {
                    $uid = (int)$_SESSION['reset_user_id'];
                    if ($userModel->updatePassword($uid, $pwd)) {
                        unset(
                            $_SESSION['reset_user_id'],
                            $_SESSION['reset_verified'],
                            $_SESSION['reset_user_email'],
                            $_SESSION['pending_user_id'],
                            $_SESSION['pending_role']
                        );
                        $_SESSION['success'] = 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại.';
                        $this->redirect('index.php?pg=getpassword'); // Quay về form nhập email
                        return;
                    } else {
                        $error = 'Không thể cập nhật mật khẩu. Vui lòng thử lại.';
                    }
                }
            }
        }
        $this->render('getpassword', [
            'stage' => $stage,
            'error' => $error,
            'info'  => $info
        ]);
    }
}
