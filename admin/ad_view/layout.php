<?php
// Unified admin layout.  Provides the full HTML structure for all
// administrative pages, including a sidebar for navigation and a
// content area for the selected view.  The `$viewFile` variable
// (absolute path to the view) and any extracted data must be defined
// by the controller prior to including this layout.
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StageX Quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Use a relative path to load the shared stylesheet from the public assets.  BASE_URL is not
         applicable inside the admin folder because it resolves relative to /public. -->
    <link rel="stylesheet" href="../public/assets/css/style.css">
    <style>
        /* Basic styles for admin layout */
        body {
            background-color: #111;
            color: #f8f9fa;
        }
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 240px;
            background-color: #212529;
            border-right: 1px solid #343a40;
            padding-top: 1rem;
        }
        .admin-sidebar a {
            color: #f8f9fa;
            text-decoration: none;
            display: block;
            padding: 0.5rem 1rem;
        }
        .admin-sidebar a:hover,
        .admin-sidebar .active {
            background-color: #343a40;
            color: #ffc107;
        }
        .admin-content {
            flex: 1;
            padding: 1rem;
        }
    </style>
</head>
<body>
<div class="admin-container">
    <nav class="admin-sidebar">
        <?php
        // Determine the current user for greeting
        $currentUser = (isset($_SESSION['user']) && is_array($_SESSION['user'])) ? $_SESSION['user'] : null;
        $userName    = $currentUser['account_name'] ?? ($currentUser['email'] ?? 'Admin');
        ?>
        <div class="px-3 mb-4">
            <h5 class="text-warning mb-0">StageX Admin</h5>
            <small class="text-muted">Xin chào, <?= htmlspecialchars($userName) ?></small>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="index.php?pg=admin-index" class="nav-link">Bảng điều khiển</a>
            </li>
            <li><a href="index.php?pg=admin-transactions" class="nav-link">Quản lý đơn hàng</a></li>
            <li><a href="index.php?pg=admin-category-show" class="nav-link">Quản lý thể loại &amp; vở diễn</a></li>
            <li><a href="index.php?pg=admin-theater-seat" class="nav-link">Quản lý rạp &amp; ghế</a></li>
            <li><a href="index.php?pg=admin-performance" class="nav-link">Quản lý suất diễn</a></li>
            <li><a href="index.php?pg=admin-reviews" class="nav-link">Quản lý đánh giá</a></li>
            <li><a href="index.php?pg=admin-accounts" class="nav-link">Quản lý tài khoản</a></li>
            <li><a href="index.php?pg=admin-profile" class="nav-link">Hồ sơ quản trị viên</a></li>
            <li><a href="../public/index.php?pg=logout" class="nav-link text-danger">Đăng xuất</a></li>
        </ul>
    </nav>
    <div class="admin-content">
        <?php
        // Display flash messages for admin pages.  Pull success/error from session
        // and then remove them so they do not persist.  Use the same mechanism
        // as the public header for consistency.
        $adminSuccess = $_SESSION['success'] ?? '';
        $adminError   = $_SESSION['error'] ?? '';
        unset($_SESSION['success'], $_SESSION['error']);
        ?>
        <?php if ($adminSuccess): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <?= htmlspecialchars($adminSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($adminError): ?>
            <div class="alert alert-danger alert-dismissible" role="alert">
                <?= htmlspecialchars($adminError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php
        // Include the actual view file.  The variable $viewFile is set by the controller.
        if (isset($viewFile) && file_exists($viewFile)) {
            include $viewFile;
        }
        ?>
    </div><!-- /.admin-content -->
</div><!-- /.admin-container -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

