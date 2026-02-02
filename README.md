# StageX Web – Website Bán Vé Sân Khấu Kịch

## Thông tin học phần
- Học phần: Phát triển Ứng dụng Web  
- Đề tài: Thiết kế và triển khai Website bán vé sân khấu kịch – StageX

---

## Giới thiệu
Trong bối cảnh thương mại điện tử (TMĐT) phát triển mạnh mẽ, việc số hóa hoạt động mua bán vé biểu diễn trở nên cấp thiết. Đề tài này xây dựng một website TMĐT quản lý bán vé sân khấu kịch, giúp khách dễ dàng tra cứu vở diễn, chọn suất diễn và đặt vé trực tuyến, đồng thời hỗ trợ quản trị viên quản lý rạp, vở diễn, suất diễn, tài khoản và báo cáo doanh thu. Hệ thống được phát triển từ các kiến thức môn **Phát triển ứng dụng Web** và ứng dụng các công cụ **HTML, CSS, JavaScript, PHP và MySQL**.

---

## Giảng viên hướng dẫn
- TS. Đặng Ngọc Hoàng Thành

---

## Nhóm thực hiện gồm 4 thành viên:
- Nguyễn Hoài Thu – 31231026200 - Leader
- Dương Thanh Ngọc – 31231024139  
- Nguyễn Thị Thùy Trang – 31231026201  
- Lê Thị Mỹ Trang – 31231026559  


---

## Báo cáo và demo sản phẩm
- Báo cáo cuối kỳ: https://drive.google.com/drive/folders/12ojLczxvf3LVNVxHaSLblPVTW7YXP8mx?usp=sharing
- Web trải nghiệm: http://stagex.host20.uk/
- Demo ứng dụng Web: https://www.youtube.com/watch?v=v1QaV3_KpSE

---

## Mô tả và mục tiêu dự án

### Mô tả
StageX Web là website thương mại điện tử được xây dựng bằng **PHP** và **MySQL**, vận hành trên môi trường **XAMPP**. Hệ thống gồm 3 đối tượng người dùng chính:

- **Khách:** Người chưa đăng ký có thể tra cứu vở diễn, lịch diễn và xem đánh giá.  
- **Thành viên:** Người dùng đã đăng ký có thể đặt vé, thanh toán, quản lý vé của tôi và đánh giá vở diễn.  
- **Quản trị viên:** Quản lý nội dung hệ thống (rạp, vở diễn, lịch diễn, tài khoản), theo dõi doanh thu và xuất báo cáo.  

### Mục tiêu
- **Số hóa quy trình:** Thay thế việc bán vé thủ công bằng website trực tuyến để khách có thể tự đặt vé và thanh toán.  
- **Quản lý trực quan:** Xây dựng giao diện responsive, sơ đồ ghế động giúp khách chọn ghế dễ dàng; cung cấp bảng điều khiển cho quản trị viên.  
- **Tối ưu hóa đặt vé:** Hỗ trợ tạo đơn hàng, tính tổng tiền tự động, tích hợp thanh toán trực tuyến an toàn và xuất vé PDF có mã vạch.  
- **Hỗ trợ ra quyết định:** Cung cấp thống kê doanh thu và số lượng vé bán để quản trị viên đánh giá hiệu quả hoạt động và lập kế hoạch.  

---

## Kiến thức và công nghệ áp dụng
Dự án áp dụng kiến thức lập trình web **3 tầng (MVC)** và sử dụng các công cụ sau:

### Front-end
- **HTML & CSS:** Xây dựng cấu trúc và định dạng giao diện website.  
- **JavaScript:** Tạo tương tác động cho trang và xử lý logic phía trình duyệt.  
- **Bootstrap:** Framework CSS giúp giao diện responsive và nhất quán trên nhiều thiết bị.  

### Back-end
- **PHP:** Xử lý logic phía server, quản lý đăng nhập, thanh toán và kết nối cơ sở dữ liệu.  
- **MySQL:** Lưu trữ dữ liệu về vở diễn, suất diễn, vé, tài khoản và đơn hàng.  
- **XAMPP:** Môi trường máy chủ cục bộ tích hợp Apache, PHP và MySQL để phát triển và kiểm thử.  
- **phpMyAdmin:** Quản trị cơ sở dữ liệu.  

### Thư viện & công nghệ bổ trợ
- **PHPMailer:** Gửi email xác nhận tài khoản và đơn hàng.  
- **Bcrypt:** Mã hóa mật khẩu an toàn.  

---

## Các chức năng chính

### Dành cho Khách/Thành viên
- **Đăng ký & Đăng nhập:** Khách tạo tài khoản để trở thành Thành viên; hỗ trợ quên mật khẩu.  
- **Tra cứu vở diễn & Suất diễn:** Tìm kiếm và lọc vở diễn theo tên, thể loại và thời gian; xem lịch diễn và chi tiết vở diễn.  
- **Đặt vé & Thanh toán:** Chọn suất diễn, số lượng vé và ghế; tạo đơn hàng và thanh toán qua nhiều phương thức (tiền mặt, chuyển khoản hoặc cổng thanh toán trực tuyến); sau khi đặt vé thành công hệ thống gửi vé PDF.  
- **Quản lý vé của tôi:** Xem danh sách vé đã mua, tải xuống vé dưới dạng PDF và kiểm tra trạng thái.  
- **Đánh giá vở diễn:** Gửi nhận xét và chấm điểm chất lượng vở diễn hoặc dịch vụ.  
- **Quản lý hồ sơ cá nhân:** Cập nhật thông tin, đổi mật khẩu và xem lịch sử mua hàng.  

### Dành cho Quản trị viên
- **Quản lý tài khoản:** Thêm, chỉnh sửa và xóa tài khoản người dùng.  
- **Quản lý đánh giá:** Kiểm duyệt và duy trì chất lượng đánh giá của người dùng (xóa hoặc lọc đánh giá).  
- **Quản lý vở diễn:** Thêm, sửa, xóa vở diễn, quản lý nội dung vở diễn.  
- **Quản lý thể loại kịch:** Thêm, sửa, xóa các thể loại kịch phục vụ phân loại.  
- **Quản lý suất diễn:** Thêm mới và chỉnh sửa lịch diễn chi tiết của các vở diễn.  
- **Quản lý rạp:** Thêm, xóa và chỉnh sửa thông tin các rạp, bao gồm sức chứa và sơ đồ ghế.  
- **Quản lý hạng ghế:** Thiết lập và điều chỉnh các hạng ghế (mức giá khác nhau) cho mỗi rạp.  
- **Theo dõi đơn hàng và doanh thu:** Tra cứu đơn hàng và theo dõi doanh thu bán vé (nếu có).  

---

## Hướng dẫn cài đặt & triển khai

### Yêu cầu hệ thống
- **Hệ điều hành:** Windows 10/11 hoặc Linux  
- **XAMPP:** (Apache, PHP 8 và MySQL)  
- **Trình duyệt:** Chrome / Edge / Firefox  

### Các bước cài đặt
1. Cài đặt **XAMPP** và khởi động dịch vụ **Apache** & **MySQL**.  
2. Tải mã nguồn và giải nén vào thư mục `htdocs` của XAMPP.  
3. Tạo cơ sở dữ liệu qua **phpMyAdmin** và import file SQL: `database/stagex_web.sql`.  
4. Cấu hình kết nối trong `config/db.php` (host, database name, user, password).  
5. Chạy ứng dụng bằng cách truy cập: `http://localhost/stagex_web` trên trình duyệt.  

---


**Mô tả thư mục:**
- `assets/`: Chứa CSS, JavaScript, hình ảnh và fonts.  
- `templates/`: Giao diện dùng chung (header, footer, menu).  
- `modules/`: Mã nguồn các chức năng chính.  
- `admin/`: Trang quản trị hệ thống.  
- `database/`: Script SQL tạo cấu trúc CSDL và dữ liệu mẫu.  
- `config/`: Cấu hình kết nối và hằng số chung.  
- `uploads/`: Lưu trữ hình ảnh poster và tài liệu tải lên.  

---

## Hạn chế và hướng phát triển

### Hạn chế
- Chưa tối ưu cho mô hình nhiều chi nhánh rạp.  

### Hướng phát triển
- Phát triển ứng dụng di động trên nền tảng Android và iOS.  
- Nâng cấp mô-đun báo cáo với thuật toán dự báo doanh thu dựa trên Machine Learning.  
- Mở rộng hệ thống để quản lý chuỗi rạp và nhà hát.  

---

## Lời cảm ơn
Nhóm xin chân thành cảm ơn **TS. Đặng Ngọc Hoàng Thành** đã nhiệt tình hướng dẫn và hỗ trợ trong suốt quá trình thực hiện đề tài. Những kiến thức và kinh nghiệm từ học phần đã giúp nhóm hoàn thành sản phẩm. Đồng thời, nhóm cũng xin cảm ơn **UEH** đã tạo điều kiện về cơ sở vật chất và môi trường học tập thuận lợi.
