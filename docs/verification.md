# Kiểm tra bản sửa giỏ hàng, đăng nhập và điều hướng

## Thay đổi

- `config/config.php`: ưu tiên biến môi trường `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, sau đó mới đến `config/local.php` và mặc định; bật chế độ lỗi mysqli nhất quán.
- `config/product_actions.php`: dùng chung xử lý thêm sách cho trang chủ, cửa hàng, chi tiết và tìm kiếm. Đọc tên/giá/ảnh từ products, phân biệt sách bằng ID, kiểm tra CSRF và số lượng 1–99.
- `index.php`, `pages/shop.php`, `pages/view_page.php`, `pages/search_page.php`: thêm token và thông báo thao tác, bỏ trường giá/tên/ảnh gửi từ trình duyệt, escape nội dung sách. Sửa liên kết tìm kiếm và giữ kết quả sau thao tác thêm sách.
- `js/script.js`, `pages/login.php`, `pages/register.php`: menu hoạt động khi chưa đăng nhập; nạp script ở các trang tài khoản và khởi động session trên trang đăng ký.
- `config/passwords.php`, `pages/login.php`, `pages/register.php`: loại cảnh báo FILTER_SANITIZE_STRING làm hỏng redirect. Tài khoản đăng ký mới dùng password_hash; tài khoản MD5 cũ được xác thực tương thích và nâng cấp hash khi đăng nhập thành công. Đổi session ID khi đăng nhập.
- `tests/product_actions.php`, `tests/navigation.cjs`: kiểm tra hồi quy.

Không thay đổi file SQL hay thực thi migration trên database của nhóm. Việc nâng cấp hash diễn ra khi người dùng đăng nhập sau khi triển khai code. Trang Admin hiện vẫn tạo tài khoản MD5; tài khoản này được nâng cấp khi đăng nhập.

## Môi trường và kết quả

Đã chạy bằng PHP 8.4.27 (mysqli/mysqlnd/mbstring), MariaDB 11.8.6 trong môi trường kiểm thử riêng. Import `database/shop_db_install.sql` vào database riêng có tên `bookstory_test` bằng cách thay tên database trong luồng import; không chỉnh file SQL trong repository.

Đã kiểm tra:

- Cú pháp toàn bộ file PHP; `git diff --check`.
- Menu: khách, người đã đăng nhập, và trang không có header.
- Giỏ hàng/yêu thích: metadata giả, CSRF sai, số lượng sai, ID không tồn tại, thêm trùng, sách cùng tên khác ID, chuyển yêu thích vào giỏ.
- Mật khẩu MD5 cũ (cả tài khoản đăng ký và Admin tạo), mật khẩu mới chứa ký tự đặc biệt, mật khẩu sai, NUL và giới hạn bcrypt.
- HTTP: đăng ký, chuyển hướng đăng nhập, thêm giỏ từ bốn trang, giữ kết quả tìm kiếm, đặt hàng COD, xóa giỏ sau đặt hàng và xem lịch sử đơn.
- HTTP: hiển thị trang giới thiệu/liên hệ/yêu thích/tìm kiếm, đăng nhập Admin và hiển thị dashboard/sản phẩm/đơn hàng/mã giảm giá/người dùng/liên hệ/sửa sản phẩm.

## Chạy lại kiểm tra tự động

```sh
node tests/navigation.cjs
TEST_DB_NAME=bookstory_test DB_HOST=localhost DB_USER=root DB_PASSWORD='' php tests/product_actions.php
```

Bài kiểm tra PHP yêu cầu một database kiểm thử đã tồn tại. Chỉ tạo các bảng TEMPORARY trong connection của bài kiểm tra; không sửa các bảng ứng dụng. Có thể cấu hình socket bằng `php -d mysqli.default_socket=/path/to/mysql.sock ...`.

## Giới hạn

Chưa kiểm thử giao dịch SePay thật, Apache/XAMPP trên máy nhóm hoặc toàn bộ giao diện trực quan trên các kích thước màn hình. Đây là các bản sửa lỗi đã tái hiện và kiểm chứng, không phải xác nhận website không còn lỗi hoặc đã hoàn tất kiểm toán bảo mật.
