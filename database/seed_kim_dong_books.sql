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
