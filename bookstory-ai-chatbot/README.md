# BookBot – chatbot tư vấn sách cho website PHP/MySQL

BookBot tìm sách thật trong bảng `products`, lọc đồng thời theo từ khóa và ngân sách, trả lời chính sách cửa hàng và gợi ý theo lịch sử tương tác. Không cần API key để dùng các chức năng này. OpenAI là phần tùy chọn để diễn đạt câu trả lời từ các sách đã tìm được.

## Cài đặt trên XAMPP

Cấu trúc đúng sau khi clone repository:

```text
C:\xampp\htdocs\web-kinh-doanh-s-ch\
├── config\local.php
├── database\shop_db_install.sql
├── components\footer.php
├── pages\
└── bookstory-ai-chatbot\
    ├── config.php
    ├── config.local.php.example
    ├── widget.php
    ├── api\
    ├── assets\
    └── database\install.sql
```

1. Bật Apache và MySQL. Dùng PHP **8.1 trở lên**, có `mysqli`, `pdo_mysql`, `mbstring`. Bật thêm `curl` nếu dùng OpenAI.
2. **Website mới:** import `database/shop_db_install.sql` trong phpMyAdmin. File này tạo cả bảng cửa hàng lẫn hai bảng chatbot.
3. **Website đã có dữ liệu:** chọn đúng database rồi chỉ chạy `bookstory-ai-chatbot/database/install.sql`. Script dùng `CREATE TABLE IF NOT EXISTS`, không cập nhật sản phẩm hay đơn hàng. Không cần chạy lại bộ SQL cài website.
4. Kiểm tra cấu hình database trong `config/local.php` của website. Chatbot đọc chung file này. Biến môi trường `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` được ưu tiên nếu có.
5. Mở `http://localhost/web-kinh-doanh-s-ch/`, đăng nhập tài khoản khách hàng và bấm **Hỏi BookBot** ở góc dưới bên phải. Trang đăng nhập/đăng ký hiện không có footer nên không hiển thị chatbot.

Giữ `bookstory-ai-chatbot` **bên trong** thư mục website. Không phải đổi tên thành `bookstore-ai-chatbot` hay chuyển ra cạnh website. Đường dẫn được tính từ trang PHP đang chạy, nên đổi tên thư mục website, dùng thư mục con hoặc phục vụ tại gốc tên miền đều được.

Nếu reverse proxy đổi URL công khai, đặt biến môi trường `BOOKBOT_SITE_PATH` (ví dụ `/cua-hang`, hoặc chuỗi rỗng ở gốc tên miền). Không thêm hostname vào giá trị này.

## Giao diện và trang hiển thị

Widget dùng tông tím pastel, font của cửa hàng, thẻ sách dạng hàng và các nút gợi ý tự xuống dòng. CSS nằm riêng trong `assets/chatbot.css`; các biến màu `--bb-*` ở đầu file là nơi đổi màu. Khoảng đệm của khung được đặt rõ để không bị CSS `section` chung làm lệch bố cục. Chỉ vùng tin nhắn/danh sách sách cuộn; ô nhập luôn ở dưới.

Mặc định **không xuất nút, khung chat, CSS hoặc JavaScript chatbot** trên `pages/cart.php`, `pages/checkout.php` và `pages/orders.php`. Trang checkout được ẩn trong mọi trạng thái: nhập thông tin, chờ QR, đặt hàng COD và thanh toán thành công. Khi quay về trang chủ/cửa hàng, nút xuất hiện lại và khung vẫn đóng cho đến khi khách bấm mở.

Danh sách này nằm trong `hidden_pages` của `config.php`. Có thể thêm trang bằng đường dẫn tương đối từ repository, hoặc cho hiện lại một trang bằng cấu hình riêng:

```php
'hidden_pages' => [
    'pages/orders.php' => false,
],
```

Không cần chỉnh các file xử lý giỏ hàng hay thanh toán để thay đổi quy tắc hiển thị.

## Đổi cấu hình mà không sửa logic

Mặc định nằm trong `bookstory-ai-chatbot/config.php`. Muốn giữ cấu hình riêng qua các lần cập nhật code, sao chép `config.local.php.example` thành **`config.local.php`** ngay cạnh nó. File riêng đã được bỏ qua bằng `.gitignore`; chỉ khai báo những mục muốn đổi:

```php
<?php
return [
    'store_faq' => [
        'shipping' => 'Nội dung giao hàng đã được cửa hàng xác nhận.',
        'payment' => 'Các phương thức thanh toán cửa hàng thực sự hỗ trợ.',
        'return' => 'Điều kiện đổi trả thực tế của cửa hàng.',
        'contact' => 'Thông tin liên hệ thực tế của cửa hàng.',
    ],
    'openai' => [
        'model' => 'gpt-4.1-mini',
    ],
];
```

Chỉ thay các câu mẫu bằng chính sách thật trước khi triển khai. Trong `config.php`:

| Mục | Khi nào cần sửa |
|---|---|
| `store_faq` | Nội dung trả lời giao hàng, thanh toán, đổi trả, liên hệ |
| `faq_keywords` | Từ/cụm từ nhận diện câu hỏi chính sách |
| `search.ignore_phrases`, `search.stop_words` | Bỏ từ dẫn nhập như “gợi ý”, “tôi muốn” khi tìm sách |
| `product_columns` | Website đổi tên cột sản phẩm |
| `product_url_template` | Trang chi tiết không còn là `pages/view_page.php?pid=...` |
| `image_base_url` | Ảnh không nằm trong `uploaded_img/` |
| `openai` | Model, thời gian chờ và giới hạn đầu ra |

Mapping hiện tại là `id`, `name`, `details`, `category`, `price`, `image`; `stock=null` vì website chưa có cột tồn kho. Không khẳng định còn hàng khi không có dữ liệu. Nếu sau này có cột tồn kho, ánh xạ `stock` để loại sách hết hàng khỏi kết quả.

## Cách trả lời

- Câu hỏi chính sách được trả lời bằng `store_faq`, ưu tiên trước tìm sản phẩm. Không gọi OpenAI cho những câu này.
- “Xin chào” nhận lời chào; không tìm sách có chữ “chào”.
- “Tìm sách Python dưới 200k” phải khớp **Python và giá tối đa 200.000đ**. Không có sách phù hợp thì báo chưa tìm thấy, không chèn sách khác.
- “Gợi ý sách hay” dùng danh sách gợi ý. Chỉ dùng gợi ý chung khi khách chưa nêu chủ đề cụ thể.
- Có thể hỏi nội dung/tóm tắt một cuốn sách, thể loại, hoặc nêu sở thích như “Tôi thích truyện tranh”. Câu hỏi nội dung cần có tên sách; câu hỏi thể loại chung liệt kê thể loại đang có trong danh mục.
- Câu hỏi ngoài sách và chính sách cửa hàng bị từ chối. Khi không có API key/OpenAI lỗi, câu trả lời về nội dung dùng mô tả trong danh mục; nếu mô tả không có thì bot nói rõ thay vì tự bịa.
- Hỗ trợ tìm không dấu và ngân sách như `200k`, `200.000 đồng`, `200,000 VND`, `1,5 triệu`. Bảng sản phẩm cần collation không phân biệt dấu, như `utf8mb4_unicode_ci` trong bộ SQL cài đặt.

Tìm sách vẫn dùng từ khóa SQL, chưa phải tìm ngữ nghĩa hay trợ lý nhớ hội thoại. Câu sau muốn đổi ngân sách cần nhắc lại chủ đề, ví dụ “Python dưới 300k”.

## Bật OpenAI (tùy chọn)

Đặt `OPENAI_API_KEY` trong môi trường của Apache/PHP và khởi động lại Apache nếu vừa đổi môi trường. Có thể đổi model bằng `OPENAI_MODEL`; tài khoản API cần có quyền và hạn mức tương ứng. Có thể đặt key trong `config.local.php` riêng trên máy chủ nếu XAMPP không tiện cấu hình môi trường. Không đưa key vào JavaScript hoặc commit lên GitHub.

Khi thiếu key, dịch vụ AI lỗi hoặc trả về dữ liệu không dùng được, BookBot vẫn trả lời từ dữ liệu sản phẩm. Trường `ai_enabled` trong phản hồi API cho biết lần trả lời đó có thực sự dùng AI hay không.

## Cá nhân hóa và API

Widget ghi nhận click vào thẻ sách. Những thao tác khác chỉ được ghi nhận nếu website gọi `BookBot.track('view'|'cart'|'favorite'|'purchase', productId)` sau khi thao tác thành công. Chỉ gọi `purchase` khi thanh toán thực sự được xác nhận. Danh sách gợi ý dùng thể loại quan tâm và độ phổ biến; chưa phải mô hình học máy đã huấn luyện.

Các endpoint nằm tại `<đường-dẫn-website>/bookstory-ai-chatbot/api/`:

| Endpoint | Phương thức | Chức năng |
|---|---|---|
| `chat.php` | POST | JSON `{"message":"Jujutsu Kaisen"}` |
| `recommend.php?limit=8` | GET | Danh sách gợi ý |
| `track.php` | POST | JSON `{"event":"view","product_id":5}` |

POST phải gửi cookie phiên và header `X-CSRF-Token` do widget cấp. Chat giới hạn 40 tin nhắn/giờ/phiên; câu hỏi tối đa 1.500 ký tự. Không gửi thông tin thanh toán bí mật vào hội thoại.

## Kiểm tra và xử lý lỗi

Thử “Jujutsu Kaisen”, “Gợi ý sách dưới 200k”, “Tìm sách Python dưới 200k”, “Phí giao hàng bao nhiêu?” và tab **Dành cho bạn**. Kiểm tra ảnh và liên kết chi tiết sách.

- Không có nút: đăng nhập, kiểm tra trang có dùng `components/footer.php` và chatbot vẫn nằm trong repository.
- JavaScript/CSS 404: kiểm tra `data-base` trên `#bookbot-root`, cấu trúc thư mục và `BOOKBOT_SITE_PATH` nếu có proxy.
- API báo tạm thời chưa sẵn sàng: xem PHP/Apache error log. Lỗi thiếu bảng thường do chưa chạy script chatbot trên database website đang dùng. Không tạo bảng tự động trong mỗi request.
- `403`: tải lại trang để lấy token phiên mới. `429`: đã đạt giới hạn tin nhắn trong giờ.

Kiểm tra hồi quy với database thử nghiệm đã tạo riêng (không dùng database cửa hàng):

```sh
TEST_DB_NAME=bookbot_test DB_HOST=127.0.0.1 DB_USER=root DB_PASSWORD='' php tests/chatbot.php
php tests/chatbot_visibility.php
node tests/navigation.cjs
```

`tests/chatbot.php` chỉ tạo bảng `TEMPORARY` trong connection của bài kiểm tra và không gọi OpenAI thật. Nếu XAMPP chưa thêm PHP vào PATH, dùng đường dẫn `C:\xampp\php\php.exe`; trên Windows đặt các biến môi trường trước khi chạy lệnh.
