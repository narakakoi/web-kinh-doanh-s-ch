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
