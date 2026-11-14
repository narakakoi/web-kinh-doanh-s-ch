# CSS dùng ở đâu?

`css/style.css` chỉ giữ phần dùng chung: font Quicksand, biến màu, reset, nút, thông báo, tiêu đề, ảnh và breakpoint cỡ chữ. CSS từng thành phần/trang được khai báo bằng `<link>` trong `<head>` của PHP, để nhìn ngay được các phụ thuộc.

## Thứ tự nạp

1. Font Awesome từ CDN.
2. `css/style.css`.
3. Các file trong `css/components/` cần cho trang.
4. CSS riêng của trang trong `css/pages/` (palette và các ghi đè được nạp sau bố cục gốc).

Đường dẫn từ `index.php` bắt đầu bằng `css/`; từ thư mục `pages/` bắt đầu bằng `../css/`. Khi thêm trang mới, nạp đúng các thành phần cần dùng thay vì chép toàn bộ CSS của trang khác.

## Thành phần dùng chung

| File CSS | Mục đích | File PHP nạp CSS |
| --- | --- | --- |
| `css/style.css` | Biến màu, reset, font chung, nút, thông báo và tiêu đề | `index.php` và tất cả 11 trang giao diện trong `pages/` liệt kê bên dưới |
| `css/components/header.css` | Logo (kèm font tự lưu), menu, tài khoản và responsive | Các trang có `components/header.php`: toàn bộ 12 trang giao diện |
| `css/components/footer.css` | Các cột và liên kết chân trang | `index.php`, `about.php`, `cart.php`, `checkout.php`, `contact.php`, `orders.php`, `search_page.php`, `shop.php`, `view_page.php`, `wishlist.php` (các file sau index nằm trong `pages/`) |
| `css/components/product-card.css` | Lưới sách, bìa, giá, tên, thể loại, số lượng, yêu thích và nút giỏ hàng | `index.php`, `pages/shop.php`, `pages/search_page.php` |
| `css/components/search-form.css` | Form tìm kiếm/lọc thể loại | `pages/shop.php`, `pages/search_page.php` |
| `css/components/sepay.css` | QR, trạng thái chờ/thành công, spinner và thông báo thanh toán chưa khả dụng | `pages/checkout.php` |

## CSS riêng theo trang

| File CSS | File PHP nạp CSS | Mục đích |
| --- | --- | --- |
| `css/pages/home.css` | `index.php` | Hai slideshow, voucher, liên hệ nhanh và palette trang chủ |
| `css/pages/auth.css` | `pages/login.php`, `pages/register.php` | Form đăng nhập và đăng ký |
| `css/pages/about.css` | `pages/about.php` | Bố cục giới thiệu, đánh giá và palette |
| `css/pages/shop.css` | `pages/shop.php` | Palette cửa hàng và form lọc; bố cục thẻ sách từ `product-card.css` |
| `css/pages/contact.css` | `pages/contact.php` | Trang và form liên hệ hiện tại |
| `css/pages/cart.css` | `pages/cart.php` | Giỏ hàng, thay đổi số lượng và tóm tắt tiền |
| `css/pages/checkout.css` | `pages/checkout.php` | Form giao hàng, mã giảm giá, tóm tắt và bố cục thanh toán |
| `css/pages/orders.css` | `pages/orders.php` | Lịch sử, chi tiết và thao tác đơn hàng |
| `css/pages/wishlist.css` | `pages/wishlist.php` | Danh sách yêu thích và chuyển vào giỏ |
| `css/pages/view-page.css` | `pages/view_page.php` | Trang chi tiết sách hiện tại |

`pages/search_page.php` dùng CSS chung, header/footer, thẻ sách và form tìm kiếm; không cần một file CSS riêng chỉ để import lại các phần này. Trang đăng nhập/đăng ký không có footer, nên không nạp `footer.css`.

`css/admin_style.css` tiếp tục phục vụ riêng `admin/admin_page.php`, `admin/admin_products.php`, `admin/admin_update_product.php`, `admin/admin_orders.php`, `admin/admin_coupons.php`, `admin/admin_users.php`, `admin/admin_contacts.php`. File này không nằm trong đợt tách `style.css`.

`pages/logout.php`, `pages/payment_status.php`, `sepay_webhook.php` và các file cấu hình không trả giao diện HTML nên không nạp CSS. Các file component không tự thêm `<link>` vào body; trang PHP chứa chúng khai báo CSS trong head.

## Phần đã loại khỏi style.css

Đã kiểm tra class/selector trong các template PHP và JavaScript trước khi bỏ:

- Giao diện chi tiết cũ `.product-detail`; trang hiện tại dùng `.bs-view-page` và `view-page.css`.
- Giỏ/yêu thích/checkout/đơn hàng cũ: `.shopping-cart`, `.wishlist`, `.wishlist-total`, `.display-order`, `.checkout`, `.placed-orders` và các selector con. Các trang hiện tại dùng `.bs-*` trong CSS riêng.
- Form liên hệ cũ `.contact form`; form mới nằm trong `.bs-contact-page`.
- Khung voucher cũ `.coupon-box` và lớp `.disabled` không còn xuất hiện trong giao diện. Không nhầm `.disabled` với thuộc tính `disabled` hoặc `.bs-disabled` đang dùng.
- Tiêu đề banner cũ `.home .content h3`, animation `shadow3D`, caption `.text`, dấu chấm `.dot` và các đoạn CSS đã comment. Phần HTML/JavaScript của ví dụ slideshow có dots trong index đang được comment, không thực thi.
- Footer cũ `.credit`, selector gõ nhầm `.box-brownbrown`, nhánh selector cho đoạn `p` không có trong footer hiện tại.
- Khai báo `box-sizing` lặp và dấu đóng comment `*/` thừa trong phần slideshow. Các khai báo thiết lập body và ảnh đã gom về phần dùng chung, giữ nguyên giá trị.

Không xóa selector chỉ vì nó chưa khớp DOM lúc trang vừa mở. `.active` của menu/tài khoản được giữ trong `header.css`; hover, focus, menu mobile, `.bs-copied`, pending/success và spinner của thanh toán vẫn được giữ. Khi chuyển font logo sang `components/header.css`, đường dẫn font đã đổi thành `../../fonts/...`.

## Kiểm tra sau khi tách

- `style.css`: từ 1.969 dòng xuống 183 dòng; phần còn dùng đã chuyển sang file đúng mục đích.
- 57 cấu hình trang/trạng thái ở viewport rộng 1440, 768 và 390 px: đối chiếu vị trí, kích thước và 67 thuộc tính computed style trước–sau. Có trang chủ, tìm kiếm rỗng/có kết quả, cửa hàng, giới thiệu, liên hệ, giỏ và yêu thích có dữ liệu, checkout, xác nhận COD, lịch sử đơn, chi tiết có/không có sách, đăng nhập/đăng ký cho khách, menu và tài khoản mở.
- Không có khác biệt trong các phép đối chiếu, lỗi JavaScript hoặc lỗi tải CSS được ghi nhận. Kiểm tra các link CSS và đường dẫn tài nguyên cục bộ không có đường dẫn thiếu.
- Cú pháp toàn bộ PHP hợp lệ. Thay đổi PHP chỉ khai báo stylesheet trong head và cập nhật chú thích, không thay đổi logic, dữ liệu, kích thước bảng/khung hoặc field của form.

Đây là kiểm tra giao diện trong môi trường riêng; không phát sinh giao dịch SePay thật và không sửa database của nhóm.
