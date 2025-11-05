<?php
//Tạo base cho tất cả các ctrl của admin.

namespace App\Controllers;




abstract class AdBaseController extends BaseController //BaseController là lớp cha và AdBaseController là lớp kế thừa chỉ dùng cho các ctrl của admin 
{
       protected function ensureAdmin(): bool 
//dùng method quyền staff,admin 
// Kiểm tra session user tồn tại & hợp lệ 
    
{
        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) //Nếu $_SESSION['user'] tồn tại nhưng không phải kiểu mảng, nghĩa là session không đúng cấu trúc 
 {
            $_SESSION['error'] = 'Bạn không có quyền truy cập trang này.';
            $this->redirect('index.php'); //Gọi phương thức redirect() để chuyển hướng người dùng về trang index.php
            return false;
        }


        $type = $_SESSION['user']['user_type'] ?? '';
        if ($type !== 'admin' && $type !== 'staff') {
            $_SESSION['error'] = 'Bạn không có quyền truy cập trang này.';
            $this->redirect('index.php');
            return false;
        }
        return true;
    }




       protected function renderAdmin(string $view, array $data = []): void 
// hiển thị (render) một trang giao diện quản trị kèm dữ liệu cần thiết
    {
                extract($data); 
//chuyển các phần tử trong mảng $data thành biến riêng lẻ
               $viewFile = __DIR__ . '/../ad_view/' . $view . '.php';
//tìm file giao diện có tên trùng với $view trong thư mục ad_view
                include __DIR__ . '/../ad_view/layout.php';
// Gọi khung giao diện chính 
    }
}





