# Tích hợp SePay

## 1. Cấu hình môi trường

Đặt các biến sau trong môi trường Apache/PHP, không ghi thông tin bí mật vào mã nguồn:

- `SEPAY_BANK_CODE`: mã ngân hàng được QR SePay hỗ trợ, ví dụ mã ngân hàng trong cấu hình tài khoản của bạn.
- `SEPAY_ACCOUNT_NUMBER`: số tài khoản nhận tiền của bạn.
- `SEPAY_WEBHOOK_API_KEY`: API key bí mật dùng để xác thực webhook.

Với XAMPP, có thể khai báo `SetEnv` trong cấu hình VirtualHost của Apache, sau đó khởi động lại Apache:

```apache
SetEnv SEPAY_BANK_CODE YOUR_BANK_CODE
SetEnv SEPAY_ACCOUNT_NUMBER YOUR_ACCOUNT_NUMBER
SetEnv SEPAY_WEBHOOK_API_KEY YOUR_SECRET_API_KEY
```

Chỉ cấu hình tài khoản ngân hàng do bạn sở hữu. Không dùng thông tin ví dụ từ mã demo.

## 2. Cập nhật database

Database mới tạo từ `database/shop_db.sql` đã có các cột thanh toán. Với database hiện có, chạy một lần nội dung `database/add_sepay_payment_columns.sql` trong phpMyAdmin hoặc MySQL.

## 3. Cấu hình webhook SePay

Tạo webhook trong SePay trỏ tới URL HTTPS công khai:

```text
https://your-domain.example/sepay_webhook.php
```

Cấu hình header Authorization với định dạng `Apikey YOUR_SECRET_API_KEY`, dùng cùng giá trị với `SEPAY_WEBHOOK_API_KEY`. Website chỉ chấp nhận POST hợp lệ, giao dịch tiền vào, mã nội dung `DH<order_id>`, đúng số tiền và đơn hàng SePay đang ở trạng thái `pending`.

Để thử nghiệm từ máy local, dùng tunnel HTTPS có kiểm soát hoặc triển khai staging; SePay không thể gọi `localhost` trực tiếp. Không để API key trong URL, HTML hay JavaScript.

## 4. Luồng thanh toán

Khách chọn QR SePay ở checkout. Website tính tổng từ giỏ hàng phía máy chủ, tạo đơn `pending`, rồi hiển thị QR với nội dung `DH<order_id>`. Webhook đã xác thực sẽ đối chiếu số tiền và cập nhật trạng thái thành `completed`; trang checkout hỏi trạng thái đơn hàng định kỳ để cập nhật giao diện.

QR hiện dùng dịch vụ tạo ảnh QR `qr.sepay.vn` theo định dạng demo. Trước khi nhận thanh toán thật, xác minh định dạng QR, mã ngân hàng và cấu hình webhook với tài liệu/tài khoản SePay của bạn.
