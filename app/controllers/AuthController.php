<?php
namespace App\Controllers;

use App\Models\User;

class AuthController extends BaseController
{
    public function login(): void
    {
        // Nếu người dùng đã đăng nhập, thì quay về trang chính
        if (!empty($_SESSION['user'])) {
            $this->redirect('index.php');
            return;
        }
        $error = '';
        // Handle login submission on POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Đăng nhập bằng email hoặc tên đăng nhập (identifier)
            $identifier = trim($_POST['identifier'] ?? '');
            $password   = trim($_POST['password'] ?? '');
            // Default role to customer if not provided
            $role = $_POST['role'] ?? 'customer';
            $userModel = new User();
            $user = $userModel->findByEmailOrAccountName($identifier);
            if (!$user) {
                $error = 'Tài khoản không tồn tại.';
            } elseif (!password_verify($password, $user['password'])) {
                $error = 'Sai mật khẩu, hãy nhập lại';
            } else {
                // Promote legacy staff accounts to admin role up front.  This ensures
                // that staff users are always considered admins regardless of the
                // selected radio button.
                if ($user['user_type'] === 'staff') {
                    $user['user_type'] = 'admin';
                }
                // If the login form requested the admin role but the user is not an
                // administrator, deny access.  This prevents customers from using
                // the admin portal with their normal accounts.
                if ($role === 'admin' && $user['user_type'] !== 'admin') {
                    $error = 'Bạn không có quyền vào cổng này.';
                } elseif ($user['status'] !== 'hoạt động') {
                    // When the user status is not "hoạt động" (active), treat the account as locked
                    $error = 'Tài khoản của bạn đã bị khóa.';
                } elseif (!$user['is_verified'] && $user['user_type'] === 'customer') {
                    // Customers must be verified via OTP before completing login
                    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expires = date('Y-m-d H:i:s', time() + 10 * 60);
                    $userModel->setOtp((int)$user['user_id'], $otp, $expires);
                    $this->sendOtpEmail($user['email'], $otp);
                    $_SESSION['pending_user_id'] = $user['user_id'];
                    $_SESSION['pending_role']      = $role;
                    $_SESSION['info']             = 'Một mã xác thực đã được gửi tới email của bạn. Vui lòng nhập mã để xác minh.';
                    $this->redirect('index.php?pg=verify');
                    return;
                } elseif (empty($error)) {
                    // Successful login: set session and redirect based on user type
                    $_SESSION['user']    = $user;
                    $_SESSION['success'] = 'Đăng nhập thành công!';
                        if ($user['user_type'] === 'admin') {
                        // Redirect admins (including legacy staff) to the admin portal index.
                        $this->redirect('../admin/index.php?pg=admin-index');
                    } else {
                        // Customers return to the home page
                        $this->redirect('index.php');
                    }
                    return;
                }
            }
            // If an error occurred during POST, fall through to render the login page with error
        }
        // Render the login page (for both GET and POST with errors)
        $this->render('login', ['error' => $error]);
    }

    public function register(): void
    {
        // Clear any existing sessions so registration is possible
        if (!empty($_SESSION['user'])) {
            unset($_SESSION['user']);
            session_destroy();
        }
        $error = '';
        // Initialise form values so that they can be repopulated in case of error.
        $email       = trim($_POST['email'] ?? '');
        $password    = trim($_POST['password'] ?? '');
        $accountName = trim($_POST['account_name'] ?? '');
        // New user detail fields collected during registration
        $fullName    = trim($_POST['full_name'] ?? '');
        $dob         = trim($_POST['date_of_birth'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $userModel   = new User();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Check for duplicate email
            $existingByEmail = $userModel->findByEmail($email);
            // Check for duplicate account name
            $existingByName  = $userModel->findByAccountName($accountName);
            // Allow reuse of email or account_name if existing account is unverified
            $emailTaken = $existingByEmail && ($existingByEmail['is_verified'] ?? 0) == 1;
            $nameTaken  = $existingByName && ($existingByName['is_verified'] ?? 0) == 1;
            if ($nameTaken) {
                $error = 'Tên tài khoản đã được sử dụng vui lòng nhập tên khác';
            } elseif ($emailTaken) {
                $error = 'Đã đăng ký tài khoản, Quên mật khẩu?';
            } elseif (!$email || !$password || !$accountName || !$fullName || !$dob) {
                // Require basic account fields and the additional profile information
                $error = 'Vui lòng nhập đầy đủ thông tin.';
            } elseif (strlen($password) < 8) {
                // Enforce a minimum password length for security.  Users must
                // choose passwords that are at least 8 characters long when
                // registering a new account.  This prevents extremely weak
                // passwords from being accepted.
                $error = 'Mật khẩu phải có ít nhất 8 ký tự.';
            } else {
                // If there is an existing unverified user, update their record instead of creating new
                if ($existingByEmail && ($existingByEmail['is_verified'] ?? 0) == 0) {
                    // Update password and account name for unverified email user via stored procedure
                    $userId = (int)$existingByEmail['user_id'];
                    $hash   = password_hash($password, PASSWORD_DEFAULT);
                    $pdo    = $userModel->getPdo();
                    try {
                        $stmt = $pdo->prepare('CALL proc_update_unverified_user_password_name(:uid, :pwd, :acc)');
                        $stmt->execute([
                            'uid' => $userId,
                            'pwd' => $hash,
                            'acc' => $accountName
                        ]);
                        // Drain the result set to free the connection for further queries
                        $stmt->closeCursor();
                    } catch (\Throwable $e) {
                        // On failure silently ignore and allow registration to continue
                    }
                    $newUserId = $userId;
                } elseif ($existingByName && ($existingByName['is_verified'] ?? 0) == 0) {
                    // Update email and password for unverified account name user via stored procedure
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
                        // Suppress errors during the update; registration continues
                    }
                    $newUserId = $userId;
                } else {
                    // Create an unverified customer account
                    $userModel->create($email, $password, $accountName, 'customer', false);
                    $new = $userModel->findByEmail($email);
                    $newUserId = $new ? (int)$new['user_id'] : 0;
                }
                if ($newUserId) {
                    // Save the user detail information collected during registration.  Even if the
                    // account is unverified, we persist the profile details so they are not lost.
                    try {
                        $detailModel = new \App\Models\UserDetail();
                        // Convert empty optional fields to null to avoid storing empty strings
                        $addr = $address !== '' ? $address : null;
                        $ph   = $phone   !== '' ? $phone   : null;
                        $detailModel->save($newUserId, $fullName, $dob, $addr, $ph);
                    } catch (\Throwable $detailEx) {
                        // Ignore errors during saving user details; registration continues
                    }
                    // Generate OTP for verification
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
        // Pass the form values back to the view so that inputs are preserved on error
        $this->render('register', [
            'error'        => $error,
            'account_name' => $accountName,
            'email'        => $email,
            'full_name'    => $fullName,
            'date_of_birth'=> $dob,
            'address'      => $address,
            'phone'        => $phone
        ]);
    }

    /**
     * Display the OTP verification form and handle submissions.  When a user
     * registers or logs in with an unverified account, their ID is stored
     * in `$_SESSION['pending_user_id']`.  This method prompts for the
     * numeric code and verifies it via the User model.  On success, the
     * user is logged in and redirected to the appropriate page.
     */
    public function verify(): void
    {
        if (empty($_SESSION['pending_user_id'])) {
            // If no pending verification, redirect to home instead of login page
            $this->redirect('index.php');
            return;
        }
        $error = '';
        $userModel = new User();
        $userId = (int)$_SESSION['pending_user_id'];
        $role = $_SESSION['pending_role'] ?? 'customer';
        // Look up the pending user and compute time remaining on the OTP.  The
        // expiry is stored in the users table when the OTP was generated.
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
                    // Fetch user and complete login
                    $user = $userModel->findById($userId);
                    if ($user) {
                        unset($_SESSION['pending_user_id'], $_SESSION['pending_role']);
                        $_SESSION['user'] = $user;
                        $_SESSION['success'] = 'Xác minh thành công! Bạn đã đăng nhập.';
                        // Post-verification redirect: treat staff as admin
                        $userType = $user['user_type'] ?? 'customer';
                        if ($userType === 'staff') {
                            $userType = 'admin';
                            // update the session and user object to unified admin role
                            $user['user_type'] = 'admin';
                            $_SESSION['user'] = $user;
                        }
                        if ($userType === 'admin') {
                            // After verifying, redirect administrators to the admin portal
                            $this->redirect('../admin/index.php?pg=admin-index');
                        } else {
                            // Customers are redirected back to the home page
                            $this->redirect('index.php');
                        }
                        return;
                    }
                } else {
                    // Wrong or expired OTP: inform the user
                    $error = 'Mã OTP sai hoặc đã hết hạn.';
                }
            }
        }
        $this->render('verify', [
            'error' => $error,
            'remaining' => $remainingSeconds
        ]);
    }

    /**
     * Internal helper to send an OTP email to the provided address.  Uses
     * the PHPMailer stub in `vendor/PHPMailer`.  SMTP configuration is
     * loaded from `config/mail.php`.  If SMTP host is not specified the
     * mail() function is used as a fallback.
     *
     * @param string $recipient Recipient email address
     * @param string $otpCode   Generated OTP code
     */
    public function sendOtpEmail(string $recipient, string $otpCode): void
    {
        // Load PHPMailer classes from vendor
        require_once __DIR__ . '/../../vendor/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../vendor/PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/../../vendor/PHPMailer/src/Exception.php';

        $config = require __DIR__ . '/../../config/mail.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            // Determine whether to use SMTP
            if (!empty($config['host'])) {
                $mail->isSMTP();
                $mail->Host       = $config['host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['username'];
                $mail->Password   = $config['password'];
                $mail->SMTPSecure = $config['encryption'] ?? 'tls';
                $mail->Port       = $config['port'] ?? 587;
            }
            // Sender/recipient
            $fromEmail = $config['from_email'] ?? $config['username'] ?? 'no-reply@stagex.local';
            $fromName  = $config['from_name'] ?? 'StageX';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($recipient);
            // Compose the OTP email in Vietnamese.  Address the recipient
            // directly, explain the purpose of the code and include the code
            // itself.  This mirrors the example provided by the user.
            $mail->isHTML(false);
            $mail->Subject = 'Mã xác thực StageX';
            // Build a friendly salutation.  Use the recipient email as
            // part of the greeting since we do not store a full name.
            $body  = "Kính gửi Quý khách {$recipient}\n\n";
            $body .= "Chúng tôi đã nhận được yêu cầu xác thực trên hệ thống.\n\n";
            $body .= "Vui lòng nhập mã xác minh bên dưới để tiếp tục thực hiện thao tác trên hệ thống.\n\n";
            $body .= "Mã xác minh: {$otpCode}\n\n";
            $body .= "Xin chân thành cảm ơn Quý khách!";
            $mail->Body = $body;
            $mail->send();
        } catch (\Throwable $e) {
            // Log error; do not interrupt user flow
            error_log('Không thể gửi email OTP: ' . $e->getMessage());
        }
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        session_destroy();
        $this->redirect('index.php');
    }

    /**
     * Handle the "Quên mật khẩu" (forgot password) workflow.  This method
     * orchestrates three stages: requesting a reset (enter email or
     * account name), verifying an OTP code, and setting a new password.
     * The current stage is determined by session variables.
     */
    
    public function forgot(): void
{
    $userModel = new User();
    $error = '';
    $info  = '';

    // === QUAN TRỌNG: XÓA SESSION RESET KHI TRUY CẬP TRANG QUA GET ===
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Người dùng vừa vào trang "Quên mật khẩu" → reset mọi thứ
        unset(
            $_SESSION['reset_user_id'],
            $_SESSION['reset_verified'],
            $_SESSION['reset_user_email']
        );
        $stage = 'request';
        $remainingSeconds = 0;
    } else {
        // === XÁC ĐỊNH GIAI ĐOẠN DỰA TRÊN POST & SESSION ===
        $stage = 'request';
        if (!empty($_SESSION['reset_user_id']) && empty($_SESSION['reset_verified'])) {
            $stage = 'otp';
        } elseif (!empty($_SESSION['reset_user_id']) && !empty($_SESSION['reset_verified'])) {
            $stage = 'reset';
        }
    }

    // Tính thời gian còn lại của OTP (chỉ ở bước OTP)
    $remainingSeconds = 0;
    if ($stage === 'otp') {
        $uid = (int)($_SESSION['reset_user_id'] ?? 0);
        if ($uid) {
            $pending = $userModel->findById($uid);
            if ($pending && !empty($pending['otp_expires_at'])) {
                $expiresAt = strtotime($pending['otp_expires_at']);
                $remainingSeconds = max(0, $expiresAt - time());
            }
        }
    }

    // === XỬ LÝ POST ===
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Stage 1: Nhập email
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

                    $_SESSION['reset_user_id']    = $user['user_id'];
                    $_SESSION['reset_user_email'] = $user['email'];
                    $info = 'Một mã xác thực đã được gửi tới email của bạn.';
                    $stage = 'otp'; // Chuyển sang bước OTP
                }
            }
        }
        // Stage 2: Nhập OTP
        elseif ($stage === 'otp') {
            $otpInput = trim($_POST['otp'] ?? '');
            if (!$otpInput) {
                $error = 'Vui lòng nhập mã xác thực.';
            } else {
                $uid = (int)$_SESSION['reset_user_id'];
                if ($userModel->verifyOtp($uid, $otpInput)) {
                    $_SESSION['reset_verified'] = true;
                    $stage = 'reset';
                    $info = 'Mã xác thực chính xác. Vui lòng nhập mật khẩu mới.';
                } else {
                    $error = 'Mã OTP sai hoặc đã hết hạn.';
                }
            }
        }
        // Stage 3: Đặt lại mật khẩu
        elseif ($stage === 'reset') {
            $pwd  = trim($_POST['password'] ?? '');
            $pwd2 = trim($_POST['confirm_password'] ?? '');
            if (!$pwd || !$pwd2) {
                $error = 'Vui lòng nhập mật khẩu mới và xác nhận mật khẩu.';
            } elseif (strlen($pwd) < 8) {
                // Validate minimum length when resetting password.  Enforcing the
                // same rule as during registration improves overall account
                // security and prevents users from setting very short passwords.
                $error = 'Mật khẩu phải có ít nhất 8 ký tự.';
            } elseif ($pwd !== $pwd2) {
                $error = 'Mật khẩu xác nhận không khớp.';
            } else {
                $uid = (int)$_SESSION['reset_user_id'];
                if ($userModel->updatePassword($uid, $pwd)) {
                    unset($_SESSION['reset_user_id'], $_SESSION['reset_verified'], $_SESSION['reset_user_email']);
                    $_SESSION['success'] = 'Đổi mật khẩu thành công. Bạn có thể đăng nhập với mật khẩu mới.';
                    $this->redirect('index.php?pg=login');
                    return;
                } else {
                    $error = 'Không thể cập nhật mật khẩu.';
                }
            }
        }
    }

    // === RENDER VIEW ===
    $this->render('getpassword', [
        'stage'     => $stage,
        'error'     => $error,
        'info'      => $info,
        'remaining' => $remainingSeconds
    ]);
}
}