-- One-time database setup for the bookstore application.
-- Run this file once in MySQL/phpMyAdmin. It creates the schema and loads the current catalog.

-- ===== shop_db.sql =====
CREATE DATABASE IF NOT EXISTS `shop_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `shop_db`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `user_type` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL DEFAULT 'Khác',
  `details` TEXT NOT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `image` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_products_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cart` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `pid` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `image` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cart_user_id` (`user_id`),
  KEY `idx_cart_pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `pid` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `image` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_wishlist_user_id` (`user_id`),
  KEY `idx_wishlist_pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `number` VARCHAR(30) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `method` VARCHAR(50) NOT NULL,
  `address` TEXT NOT NULL,
  `total_products` TEXT NOT NULL,
  `total_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `coupon_code` VARCHAR(50) DEFAULT NULL,
  `placed_on` VARCHAR(50) NOT NULL,
  `payment_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `payment_reference` VARCHAR(100) DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_orders_user_id` (`user_id`),
  KEY `idx_orders_payment_status` (`payment_status`),
  UNIQUE KEY `uq_orders_payment_reference` (`payment_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `discount_type` ENUM('percent', 'fixed') NOT NULL,
  `discount_value` DECIMAL(12,2) NOT NULL,
  `min_order_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `usage_limit` INT UNSIGNED DEFAULT NULL,
  `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coupons_code` (`code`),
  KEY `idx_coupons_active_dates` (`is_active`, `starts_at`, `ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `message` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `number` VARCHAR(30) NOT NULL,
  `message` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_message_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `users` (`name`, `email`, `password`, `user_type`)
VALUES ('Quản trị viên', '123@gmail.com', MD5('123'), 'admin');


-- ===== seed_uploaded_books.sql =====
USE `shop_db`;

-- Prices/descriptions are maintained in update_verified_book_content.sql.
-- Prices were checked against publisher/retailer listings on 2026-10-27/28.
INSERT INTO `products` (`name`, `category`, `details`, `price`, `image`)
SELECT seed.`name`, seed.`category`, seed.`details`, seed.`price`, seed.`image`
FROM (
  SELECT
    'Bạch Tuyết tốc độ - Tập 16' AS `name`,
    'Truyện tranh' AS `category`,
    'Tại Lilias, dịch cúm khiến công việc nghiên cứu của Shirayuki và Ryu thêm phần căng thẳng. Cả hai tiếp tục trưởng thành qua trải nghiệm ấy, trong khi Zen nhận một mệnh lệnh mới từ Izana.' AS `details`,
    27000.00 AS `price`,
    'bachtuyettocdo.png' AS `image`
  UNION ALL SELECT
    'Bản giao hưởng của Nước',
    'Khác',
    'Tác phẩm khám phá vai trò của nước đối với sự sống, văn hóa và lịch sử qua nhiều góc nhìn. Nội dung cũng gợi mở suy nghĩ về việc trân trọng và bảo vệ nguồn nước.',
    58500.00,
    'bangiaohuongcuanuoc.png'
  UNION ALL SELECT
    'Dược sư tự sự - Tập 6',
    'Truyện tranh',
    'Miêu Miêu từng hành nghề dược sư ở phố hoa trước khi bị đưa vào cung đình, nơi kiến thức về dược liệu giúp cô lần theo những bí ẩn trong cung. Đây là phần giới thiệu tiền đề chung của bộ manga, không phải tóm tắt riêng tập 6.',
    42300.00,
    'duocsutusu.png'
  UNION ALL SELECT
    'Hôn nhân hạnh phúc của tôi - Tập 1',
    'Truyện tranh',
    'Miyo lớn lên trong sự ghẻ lạnh của gia đình và bị sắp đặt kết hôn với Kiyoka, một quân nhân mang tiếng lạnh lùng. Cuộc gặp gỡ ban đầu đầy dè dặt mở ra cơ hội để cả hai dần hiểu nhau và tìm kiếm một mái ấm.',
    20000.00,
    'honnhanhahphuc.png'
  UNION ALL SELECT
    'Jujutsu Kaisen - Tập 23',
    'Truyện tranh',
    'Itadori Yuji là một học sinh trung học có thể lực phi thường; cuộc đời cậu rẽ sang hướng khác sau khi vướng vào một vật bị nguyền rủa. Đây là phần giới thiệu tiền đề chung của bộ manga Chú thuật hồi chiến, không phải tóm tắt riêng tập 23.',
    27000.00,
    'jujustukaisen23.png'
  UNION ALL SELECT
    'Lời chào từ Moskva',
    'Tiểu thuyết',
    'Điệp viên James Bond trở thành mục tiêu của SMERSH trong một kế hoạch gài bẫy được chuẩn bị kỹ lưỡng. Sự xuất hiện của nữ điệp viên Tatiana Romanova khiến nhiệm vụ và lòng tin của Bond thêm phức tạp.',
    10000.00,
    'moskva.jpg'
  UNION ALL SELECT
    'Son Goal! - Tập 3',
    'Truyện tranh',
    'Sơn và Phong được gọi lên trại huấn luyện để chuẩn bị cho giải U15 châu Á. Trong lúc Phong quyết tâm giành suất thi đấu, Sơn tìm hiểu thêm về mẹ mình qua những câu chuyện của người thân. Cả hai phải đối mặt với thử thách để có cơ hội góp mặt trong đội tuyển.',
    45000.00,
    'SonGoal.png'
  UNION ALL SELECT
    'Sứ giả bốn mùa: Xứ diệu mùa xuân - Tập 2',
    'Tiểu thuyết',
    'Trong thế giới nơi bốn mùa được hình thành từ mùa đông, các sứ giả có nhiệm vụ đưa mùa đi theo dòng thời gian. Quyển 2 giới thiệu Hinagiku, sứ giả mùa xuân, cùng Sakura, kiếm sĩ đã thề bảo vệ cô; câu chuyện đặt họ trong một hành trình gắn với sứ mệnh và lời hẹn.',
    126000.00,
    'sugia4mua.png'
  UNION ALL SELECT
    'Thiên sứ nhà bên - Tập 8',
    'Tiểu thuyết',
    'Amane Fujimiya sống cạnh Shiina Mahiru, nữ sinh nổi tiếng nhất trường; một lần gặp nhau trong ngày mưa mở đầu cho mối gắn kết giữa hai người. Đây là tiền đề chung của bộ tiểu thuyết Thiên sứ nhà bên, không phải tóm tắt riêng tập 8.',
    85500.00,
    'thien-su-nha-ben-tap-8.jpg'
  UNION ALL SELECT
    'Thiên sứ nhà bên - Tập 2',
    'Tiểu thuyết',
    'Amane Fujimiya sống cạnh Shiina Mahiru, nữ sinh nổi tiếng nhất trường; một lần gặp nhau trong ngày mưa mở đầu cho mối gắn kết giữa hai người. Đây là tiền đề chung của bộ tiểu thuyết Thiên sứ nhà bên, không phải tóm tắt riêng tập 2.',
    85500.00,
    'thiesunhaben.png'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM `products` AS existing
  WHERE existing.`image` = seed.`image`
);


-- ===== seed_kim_dong_books.sql =====
USE `shop_db`;

-- Prices and availability checked against NXB Kim Dong listings on 2026-10-27.
-- Current sale prices may change. Descriptions are original paraphrases of the
-- publisher's product information. Cover files are sourced from the linked
-- official listings; use them only under the store's confirmed authorization.
--
-- Sources:
-- https://nxbkimdong.com.vn/products/dao-lam-chong-dam-tap-15
-- https://nxbkimdong.com.vn/products/tokyo-aliens-tap-9-tang-kem-set-card
-- https://nxbkimdong.com.vn/products/45-chuong-mo-dau-bo-truyen-ngan-doraemon-tap-2-26
-- https://nxbkimdong.com.vn/products/ve-chuyen-toi-chuyen-sinh-thanh-slime-tap-3-tang-kem-qua-tang-doc-quyen
-- https://nxbkimdong.com.vn/products/the-gioi-xe-co-xe-buyt-truong-school-bus-26
-- https://nxbkimdong.com.vn/products/o-day-co-niem-vui-tiem-hoa-dieu-ki
-- https://nxbkimdong.com.vn/products/khac-biet-tuyet-voi-cu-nho-tim-ban
-- https://nxbkimdong.com.vn/products/thu-thi-cung-con-noi-trai-tim-thuoc-ve
-- https://nxbkimdong.com.vn/products/ha-noi-bam-sau-pho-phuong-26
-- https://nxbkimdong.com.vn/products/toi-da-tro-thanh-nha-van-nhu-the-nao
-- https://nxbkimdong.com.vn/products/co-luu-lac
-- https://nxbkimdong.com.vn/products/charlie-va-nha-may-socola-26
-- https://nxbkimdong.com.vn/products/hai-van-dam-duoi-bien-26
-- https://nxbkimdong.com.vn/products/ba-ngoai-tren-cay-tao-26
-- https://nxbkimdong.com.vn/products/ki-nang-vang-cho-hoc-sinh-trung-hoc-hoc-cach-truong-thanh-26
-- https://nxbkimdong.com.vn/products/kheo-khon-voi-tien-tranh-nhung-uu-phien-tap-1-hieu-ve-tien
-- https://nxbkimdong.com.vn/products/bi-an-the-gioi-loai-vat-ca-26
-- https://nxbkimdong.com.vn/products/cac-trieu-dai-viet-nam
-- https://nxbkimdong.com.vn/products/luoc-su-nuoc-viet-bang-tranh-26
-- https://nxbkimdong.com.vn/products/tranh-truyen-lich-su-viet-nam-le-quy-don

INSERT INTO `products` (`name`, `category`, `details`, `price`, `image`)
SELECT seed.`name`, seed.`category`, seed.`details`, seed.`price`, seed.`image`
FROM (
  SELECT
    'Đạo làm chồng đảm - Tập 15' AS `name`,
    'Truyện tranh' AS `category`,
    'Tatsu và những người thân cận đưa bé Anzu đi đào nghêu, nơi một cuộc gặp gỡ giữa hai đứa trẻ dẫn đến màn đối đầu bất ngờ giữa các gia đình yakuza. Bộ truyện khai thác sự tương phản hài hước giữa thế giới giang hồ và cuộc sống nội trợ.' AS `details`,
    31500.00 AS `price`,
    'kd-book-01.png' AS `image`
  UNION ALL SELECT
    'Tokyo Aliens - Tập 9 (Tặng kèm Set Card)',
    'Truyện tranh',
    'Trong tập này, Tenkubashi tìm cách giải cứu Gunji khỏi tổ chức buôn bán người ngoài hành tinh. Cuộc giải cứu buộc cậu phải dựa vào những đầu mối nguy hiểm và liều mình tiến sâu vào thế giới ngầm.',
    36000.00,
    'kd-book-02.jpg'
  UNION ALL SELECT
    '45 chương mở đầu bộ truyện ngắn Doraemon - Tập 2',
    'Truyện tranh',
    'Tập sách tuyển chọn những truyện ngắn Doraemon do chính Fujiko F Fujio lựa chọn từ kho truyện của mình. Các câu chuyện kết hợp phát minh kỳ thú, tình bạn và những tình huống dí dỏm quen thuộc.',
    49500.00,
    'kd-book-03.jpg'
  UNION ALL SELECT
    'Về chuyện tôi chuyển sinh thành Slime - Tập 3 (Tặng kèm Quà độc quyền)',
    'Truyện tranh',
    'Rimuru đang tập kiểm soát sức mạnh mới thì nhận được tín hiệu cầu cứu từ những người đồng đội ở làng Goblin. Cuộc đối đầu với nhóm đối thủ bí ẩn mở ra thử thách lớn cho cậu và cộng đồng.',
    40500.00,
    'kd-book-04.jpg'
  UNION ALL SELECT
    'Thế giới xe cộ - Xe buýt trường - School bus',
    'Sách tham khảo',
    'Cuốn sách tranh song ngữ Việt - Anh giới thiệu xe buýt trường học qua hình ảnh và từ vựng đơn giản. Nội dung phù hợp để trẻ nhỏ vừa làm quen với phương tiện giao thông vừa học thêm tiếng Anh.',
    22500.00,
    'kd-book-05.jpg'
  UNION ALL SELECT
    'Ở đây có niềm vui - Tiệm hoa diệu kì',
    'Khác',
    'Chú Sói mở một tiệm hoa trong rừng và có thể hiểu ngôn ngữ của các loài hoa. Một chuyến giao hoa nhỏ trở thành câu chuyện dịu dàng về sự quan tâm và niềm vui được sẻ chia.',
    40500.00,
    'kd-book-06.jpg'
  UNION ALL SELECT
    'Khác biệt tuyệt vời - Cú nhỏ tìm bạn',
    'Khác',
    'Cú Nhỏ muốn thức ban ngày, trái với nếp sinh hoạt của cả họ nhà Cú. Câu chuyện theo chân bạn nhỏ trong hành trình tìm bạn và khơi gợi cách nhìn tích cực về những nét riêng của mỗi người.',
    27000.00,
    'kd-book-07.jpg'
  UNION ALL SELECT
    'Thủ thỉ cùng con - Nơi trái tim thuộc về (2025)',
    'Khác',
    'Cuốn sách tranh dùng những vần thơ ngắn và hình ảnh ấm áp để cùng trẻ trò chuyện về cảm giác thân thuộc của mái ấm gia đình. Nội dung hướng tới những khoảnh khắc gần gũi giữa cha mẹ và con.',
    31500.00,
    'kd-book-08.png'
  UNION ALL SELECT
    'Hà Nội băm sáu phố phường',
    'Khác',
    'Qua những trang ký giàu quan sát, Thạch Lam gợi lại vẻ duyên dáng của Hà Nội trong nếp sống và những điều bình dị thường ngày. Tác phẩm cũng dành nhiều sự chú ý đến món ăn và nét văn hóa phố phường.',
    27000.00,
    'kd-book-09.jpg'
  UNION ALL SELECT
    'Tôi đã trở thành nhà văn như thế nào',
    'Khác',
    'Cuốn sách nhìn lại hành trình Phùng Quán đến với nghề viết, từ tuổi trẻ nhiều trải nghiệm đến những năm tháng gắn bó với văn chương. Giọng kể chân thành giúp người đọc hiểu thêm về con người và sức bền sáng tạo của ông.',
    49500.00,
    'kd-book-10.jpg'
  UNION ALL SELECT
    'Cỏ lưu lạc',
    'Tiểu thuyết',
    'Sau biến cố khiến mình lạc mất cha mẹ, cô vịt nhỏ Bông Cỏ tìm đến một làng biển và kết bạn với những cư dân mới. Những chuyến khám phá và thử thách ở vùng đất ấy giúp cô dần trưởng thành, đồng thời tìm hiểu văn hóa địa phương.',
    58500.00,
    'kd-book-11.jpg'
  UNION ALL SELECT
    'Charlie và nhà máy sôcôla',
    'Tiểu thuyết',
    'Charlie Bucket giành được tấm Vé Vàng để thăm nhà máy sôcôla kỳ lạ của Willy Wonka. Chuyến tham quan mở ra hàng loạt điều bất ngờ trong thế giới sáng tạo của nhà văn Roald Dahl.',
    54000.00,
    'kd-book-12.jpg'
  UNION ALL SELECT
    'Hai vạn dặm dưới biển',
    'Tiểu thuyết',
    'Một đoàn thám hiểm ra khơi truy tìm sinh vật bí ẩn đang khiến tàu thuyền gặp nguy hiểm. Cuộc hành trình đưa họ lên chiếc tàu ngầm của thuyền trưởng Nemo và khám phá những bí mật dưới đại dương.',
    89100.00,
    'kd-book-13.jpg'
  UNION ALL SELECT
    'Bà ngoại trên cây táo',
    'Tiểu thuyết',
    'Andi buồn vì nghĩ rằng mình là đứa trẻ duy nhất không có bà, cho đến khi một người bà bất ngờ xuất hiện trên cây táo. Những chuyến phiêu lưu cùng bà giúp cậu khám phá nhiều miền đất và mở lòng với những mối quan hệ mới.',
    45000.00,
    'kd-book-14.jpg'
  UNION ALL SELECT
    'Kĩ năng vàng cho học sinh trung học - Học cách trưởng thành',
    'Sách tham khảo',
    'Cuốn sách đồng hành cùng tuổi mới lớn khi các em đối diện thay đổi tâm lý, việc học và các mối quan hệ. Nội dung gợi ý cách hiểu bản thân và ứng phó với những vướng mắc thường gặp trên hành trình trưởng thành.',
    41400.00,
    'kd-book-15.jpg'
  UNION ALL SELECT
    'Khéo khôn với tiền - Tránh những ưu phiền - Tập 1 - Hiểu về Tiền',
    'Sách tham khảo',
    'Tập truyện tranh giới thiệu những khái niệm tài chính căn bản bằng tình huống gần gũi với gia đình Việt. Người đọc được làm quen với tiền và cách suy nghĩ có trách nhiệm khi sử dụng tiền.',
    106200.00,
    'kd-book-16.jpg'
  UNION ALL SELECT
    'Bí ẩn thế giới loài vật - Cá',
    'Sách tham khảo',
    'Cuốn sách tranh tập hợp thông tin thú vị về các loài cá và những đặc điểm ít người biết trong thế giới tự nhiên. Hình thức trình bày hướng tới bạn đọc nhỏ tuổi yêu thích khám phá động vật.',
    45000.00,
    'kd-book-17.jpg'
  UNION ALL SELECT
    'Các triều đại Việt Nam',
    'Sách tham khảo',
    'Tác phẩm lần theo sự tiếp nối của các triều đại và vua chúa Việt Nam từ thời dựng nước đến cuối chế độ quân chủ. Cách sắp xếp theo dòng triều đại giúp người đọc có cái nhìn khái quát về lịch sử chính trị của đất nước.',
    108000.00,
    'kd-book-18.jpg'
  UNION ALL SELECT
    'Lược sử nước Việt bằng tranh',
    'Sách tham khảo',
    'Cuốn sách kể lại những chặng đường lớn của lịch sử Việt Nam bằng hình ảnh và lời dẫn cô đọng. Tác phẩm kết nối các dấu mốc từ buổi đầu dựng nước với những biến chuyển về sau.',
    126000.00,
    'kd-book-19.jpg'
  UNION ALL SELECT
    'Tranh truyện lịch sử Việt Nam - Lê Quý Đôn',
    'Sách tham khảo',
    'Tập truyện tranh giới thiệu cuộc đời và dấu ấn học thuật của Lê Quý Đôn qua lối kể dành cho bạn đọc nhỏ tuổi. Đây là một đầu sách trong bộ tranh truyện về các nhân vật lịch sử Việt Nam.',
    18000.00,
    'kd-book-20.jpg'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM `products` AS existing
  WHERE existing.`image` = seed.`image`
     OR existing.`name` = seed.`name`
);


-- ===== update_verified_book_content.sql =====
USE `shop_db`;

-- Previously unpriced books checked on 2026-10-28; the other seeded books
-- were checked on 2026-10-27. Current sale prices may change.

-- Listing is currently out of stock; price is the displayed sale price.
-- https://nxbkimdong.com.vn/products/bach-tuyet-toc-do-tap-16
UPDATE `products`
SET
  `price` = 27000,
  `details` = 'Tại Lilias, dịch cúm khiến công việc nghiên cứu của Shirayuki và Ryu thêm phần căng thẳng. Cả hai tiếp tục trưởng thành qua trải nghiệm ấy, trong khi Zen nhận một mệnh lệnh mới từ Izana.'
WHERE `image` = 'bachtuyettocdo.png';

-- https://nxbkimdong.com.vn/products/ban-giao-huong-cua-nuoc
UPDATE `products`
SET
  `price` = 58500,
  `details` = 'Tác phẩm khám phá vai trò của nước đối với sự sống, văn hóa và lịch sử qua nhiều góc nhìn. Nội dung cũng gợi mở suy nghĩ về việc trân trọng và bảo vệ nguồn nước.'
WHERE `image` = 'bangiaohuongcuanuoc.png';

-- Variant "Tập 06 (2026)" is currently available.
-- https://nxbkimdong.com.vn/products/tap-le-duoc-su-tu-su-manga
UPDATE `products`
SET
  `price` = 42300,
  `details` = 'Miêu Miêu từng hành nghề dược sư ở phố hoa trước khi bị đưa vào cung đình, nơi kiến thức về dược liệu giúp cô lần theo những bí ẩn trong cung. Đây là phần giới thiệu tiền đề chung của bộ manga, không phải tóm tắt riêng tập 6.'
WHERE `image` = 'duocsutusu.png';

-- Variant "Tập 23 (2026)" is currently available.
-- https://nxbkimdong.com.vn/products/tap-le-chu-thuat-hoi-chien
UPDATE `products`
SET
  `price` = 27000,
  `details` = 'Itadori Yuji là một học sinh trung học có thể lực phi thường; cuộc đời cậu rẽ sang hướng khác sau khi vướng vào một vật bị nguyền rủa. Đây là phần giới thiệu tiền đề chung của bộ manga Chú thuật hồi chiến, không phải tóm tắt riêng tập 23.'
WHERE `image` = 'jujustukaisen23.png';

-- This cover is the light-novel edition, not the manga edition.
-- Listing is currently out of stock; price is the displayed sale price.
-- https://nxbkimdong.com.vn/products/hon-nhan-hanh-phuc-cua-toi-light-novel-tap-1-tang-02-clipsheet-01-card-nhua-trong
UPDATE `products`
SET
  `price` = 20000,
  `details` = 'Miyo lớn lên trong sự ghẻ lạnh của gia đình và bị sắp đặt kết hôn với Kiyoka, một quân nhân mang tiếng lạnh lùng. Cuộc gặp gỡ ban đầu đầy dè dặt mở ra cơ hội để cả hai dần hiểu nhau và tìm kiếm một mái ấm.'
WHERE `image` = 'honnhanhahphuc.png';

-- The standard-edition listing is out of stock; the limited-edition listing
-- with the same ISBN is available at the same displayed price.
-- https://nxbkimdong.com.vn/products/loi-chao-tu-moskva-james-bond-ban-gioi-han-tang-kem-bia-ao-postcard
UPDATE `products`
SET
  `price` = 10000,
  `details` = 'Điệp viên James Bond trở thành mục tiêu của SMERSH trong một kế hoạch gài bẫy được chuẩn bị kỹ lưỡng. Sự xuất hiện của nữ điệp viên Tatiana Romanova khiến nhiệm vụ và lòng tin của Bond thêm phức tạp.'
WHERE `image` = 'moskva.jpg';

-- Exact standard-edition listing from Nhà Sách Phương Nam on Tiki.
-- https://tiki.vn/son-goal-tap-3-p275838908.html?spid=275838909
UPDATE `products`
SET
  `price` = 45000,
  `details` = 'Sơn và Phong được gọi lên trại huấn luyện để chuẩn bị cho giải U15 châu Á. Trong lúc Phong quyết tâm giành suất thi đấu, Sơn tìm hiểu thêm về mẹ mình qua những câu chuyện của người thân. Cả hai phải đối mặt với thử thách để có cơ hội góp mặt trong đội tuyển.'
WHERE `image` = 'SonGoal.png';

-- Standard-edition listing is out of stock; the limited-edition listing with
-- the same ISBN is available at the same displayed price.
-- https://nxbkimdong.com.vn/products/su-gia-bon-mua-vu-dieu-mua-xuan-quyen-2-ban-gioi-han-tang-postcard-clear-bookmark
UPDATE `products`
SET
  `price` = 126000,
  `details` = 'Trong thế giới nơi bốn mùa được hình thành từ mùa đông, các sứ giả có nhiệm vụ đưa mùa đi theo dòng thời gian. Quyển 2 giới thiệu Hinagiku, sứ giả mùa xuân, cùng Sakura, kiếm sĩ đã thề bảo vệ cô; câu chuyện đặt họ trong một hành trình gắn với sứ mệnh và lời hẹn.'
WHERE `image` = 'sugia4mua.png';

-- Variant "Tập 08 (2025)" is currently available.
-- https://nxbkimdong.com.vn/products/tap-le-thien-su-nha-ben-light-novel
UPDATE `products`
SET
  `price` = 85500,
  `details` = 'Amane Fujimiya sống cạnh Shiina Mahiru, nữ sinh nổi tiếng nhất trường; một lần gặp nhau trong ngày mưa mở đầu cho mối gắn kết giữa hai người. Đây là tiền đề chung của bộ tiểu thuyết Thiên sứ nhà bên, không phải tóm tắt riêng tập 8.'
WHERE `image` = 'thien-su-nha-ben-tap-8.jpg';

-- Variant "Tập 02 (2025)" is currently available.
-- https://nxbkimdong.com.vn/products/tap-le-thien-su-nha-ben-light-novel
UPDATE `products`
SET
  `price` = 85500,
  `details` = 'Amane Fujimiya sống cạnh Shiina Mahiru, nữ sinh nổi tiếng nhất trường; một lần gặp nhau trong ngày mưa mở đầu cho mối gắn kết giữa hai người. Đây là tiền đề chung của bộ tiểu thuyết Thiên sứ nhà bên, không phải tóm tắt riêng tập 2.'
WHERE `image` = 'thiesunhaben.png';


-- ===== update_kim_dong_book_covers.sql =====
USE `shop_db`;

UPDATE `products` SET `image` = 'kd-book-01.png' WHERE `name` = 'Đạo làm chồng đảm - Tập 15' AND `image` = 'sample-book-01.svg';
UPDATE `products` SET `image` = 'kd-book-02.jpg' WHERE `name` = 'Tokyo Aliens - Tập 9 (Tặng kèm Set Card)' AND `image` = 'sample-book-02.svg';
UPDATE `products` SET `image` = 'kd-book-03.jpg' WHERE `name` = '45 chương mở đầu bộ truyện ngắn Doraemon - Tập 2' AND `image` = 'sample-book-03.svg';
UPDATE `products` SET `image` = 'kd-book-04.jpg' WHERE `name` = 'Về chuyện tôi chuyển sinh thành Slime - Tập 3 (Tặng kèm Quà độc quyền)' AND `image` = 'sample-book-04.svg';
UPDATE `products` SET `image` = 'kd-book-05.jpg' WHERE `name` = 'Thế giới xe cộ - Xe buýt trường - School bus' AND `image` = 'sample-book-05.svg';
UPDATE `products` SET `image` = 'kd-book-06.jpg' WHERE `name` = 'Ở đây có niềm vui - Tiệm hoa diệu kì' AND `image` = 'sample-book-06.svg';
UPDATE `products` SET `image` = 'kd-book-07.jpg' WHERE `name` = 'Khác biệt tuyệt vời - Cú nhỏ tìm bạn' AND `image` = 'sample-book-07.svg';
UPDATE `products` SET `image` = 'kd-book-08.png' WHERE `name` = 'Thủ thỉ cùng con - Nơi trái tim thuộc về (2025)' AND `image` = 'sample-book-08.svg';
UPDATE `products` SET `image` = 'kd-book-09.jpg' WHERE `name` = 'Hà Nội băm sáu phố phường' AND `image` = 'sample-book-09.svg';
UPDATE `products` SET `image` = 'kd-book-10.jpg' WHERE `name` = 'Tôi đã trở thành nhà văn như thế nào' AND `image` = 'sample-book-10.svg';
UPDATE `products` SET `image` = 'kd-book-11.jpg' WHERE `name` = 'Cỏ lưu lạc' AND `image` = 'sample-book-11.svg';
UPDATE `products` SET `image` = 'kd-book-12.jpg' WHERE `name` = 'Charlie và nhà máy sôcôla' AND `image` = 'sample-book-12.svg';
UPDATE `products` SET `image` = 'kd-book-13.jpg' WHERE `name` = 'Hai vạn dặm dưới biển' AND `image` = 'sample-book-13.svg';
UPDATE `products` SET `image` = 'kd-book-14.jpg' WHERE `name` = 'Bà ngoại trên cây táo' AND `image` = 'sample-book-14.svg';
UPDATE `products` SET `image` = 'kd-book-15.jpg' WHERE `name` = 'Kĩ năng vàng cho học sinh trung học - Học cách trưởng thành' AND `image` = 'sample-book-15.svg';
UPDATE `products` SET `image` = 'kd-book-16.jpg' WHERE `name` = 'Khéo khôn với tiền - Tránh những ưu phiền - Tập 1 - Hiểu về Tiền' AND `image` = 'sample-book-16.svg';
UPDATE `products` SET `image` = 'kd-book-17.jpg' WHERE `name` = 'Bí ẩn thế giới loài vật - Cá' AND `image` = 'sample-book-17.svg';
UPDATE `products` SET `image` = 'kd-book-18.jpg' WHERE `name` = 'Các triều đại Việt Nam' AND `image` = 'sample-book-18.svg';
UPDATE `products` SET `image` = 'kd-book-19.jpg' WHERE `name` = 'Lược sử nước Việt bằng tranh' AND `image` = 'sample-book-19.svg';
UPDATE `products` SET `image` = 'kd-book-20.jpg' WHERE `name` = 'Tranh truyện lịch sử Việt Nam - Lê Quý Đôn' AND `image` = 'sample-book-20.svg';

