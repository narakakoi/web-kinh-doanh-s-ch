<?php
require_once 'config/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}

session_start();

$user_id = $_SESSION['user_id'] ?? null;

if (!isset($user_id)) {
    header('location:pages/login.php');
    exit();
}

$message = [];

require_once __DIR__ . '/config/product_actions.php';
$message = array_merge($message ?? [], handle_product_action($conn, (int) $user_id, $_POST));

// BOOKSTORY / TRANG CHỦ: chỉ hiển thị voucher còn hiệu lực.
// Cùng điều kiện lọc với checkout.php; nếu bảng chưa được cài, trang vẫn hoạt động.
$homeCoupons = [];
try {
    $couponResult = mysqli_query($conn, "SELECT code, discount_type, discount_value, min_order_value, ends_at
        FROM coupons
        WHERE is_active = 1
          AND (starts_at IS NULL OR starts_at <= NOW())
          AND (ends_at IS NULL OR ends_at >= NOW())
          AND (usage_limit IS NULL OR used_count < usage_limit)
        ORDER BY discount_value DESC, id DESC LIMIT 4");
    if ($couponResult) {
        $homeCoupons = mysqli_fetch_all($couponResult, MYSQLI_ASSOC);
        mysqli_free_result($couponResult);
    }
} catch (mysqli_sql_exception $exception) {
    // Chưa cài bảng coupons thì hiển thị lời nhắc, không dừng cả trang chủ.
    error_log('BookStory coupons: ' . $exception->getMessage());
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Trang Chủ</title>
   <!-- liên kết font awesome cdn  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <!-- liên kết file css tùy chỉnh  -->
   <link rel="stylesheet" href="css/style.css">
   <!-- CSS riêng trang chủ: khu ưu đãi và liên hệ, không đè các màn hình khác. -->
   <link rel="stylesheet" href="css/pages/home.css">
</head>
<body class="bs-home-page">
   
<?php @include 'components/header.php'; ?>
<?php foreach ($message as $notice): ?>
<div class="message" role="status"><span><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>
<?php endforeach; ?>
<section class="home">

   <div class ="homeslide fade">
      <img src="images/slider_2.png" alt="lỗi">
   </div>
   <div class ="homeslide fade">
      <img src="images/slider_3.png" alt="lỗi">
   </div>
   <div class ="homeslide fade">
      <img src="images/slider_4.png" alt="lỗi">
   </div>
   <button class="prev" onclick="plusSlides(-1)">&#10094;</button>
   <button class="next" onclick="plusSlides(1)">&#10095;</button>

   <div class="content">
      <!--<h3>Sách là tri thức của nhân loại</h3>
      <a href="pages/about.php" class="btn">Xem Thêm</a>-->
   </div>
</section>

<script>
   function plusSlides(n) {
    homeIndex += n;

    const slides = document.getElementsByClassName("homeslide");

    if (homeIndex > slides.length) {
        homeIndex = 1;
    }

    if (homeIndex < 1) {
        homeIndex = slides.length;
    }

    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }

    slides[homeIndex - 1].style.display = "block";
   }

    let homeIndex = 0;
    function showHomeSlides() {
        const slides = document.getElementsByClassName("homeslide");

        for (let i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }

        homeIndex++;

        if (homeIndex > slides.length) {
            homeIndex = 1;
        }

        slides[homeIndex - 1].style.display = "block";

        setTimeout(showHomeSlides, 6000);
    }
    
    showHomeSlides();
    
</script>

<!-- BOOKSTORY / ƯU ĐÃI: bố cục lấy cảm hứng từ dải Flash Sale.
     Giá trị, ngày hết hạn và mã đều đọc từ bảng coupons thật. -->
<section class="bs-home-offers" aria-labelledby="bs-offers-title">
   <div class="bs-offers-inner">
      <div class="bs-offers-heading">
         <div>
            <span class="bs-offers-tag"><i class="fas fa-bolt" aria-hidden="true"></i> ƯU ĐÃI BOOKSTORY</span>
            <h2 id="bs-offers-title">Săn mã hay, đọc sách mê say</h2>
            <p>Chọn ưu đãi phù hợp và dùng mã khi thanh toán đơn sách.</p>
         </div>
         <a href="pages/shop.php" class="bs-offers-shop-link">Khám phá cửa hàng <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
      </div>
      <?php if (!empty($homeCoupons)): ?>
         <div class="bs-offers-grid">
            <?php foreach ($homeCoupons as $coupon): ?>
               <?php
                  $isPercent = $coupon['discount_type'] === 'percent';
                  $couponValue = $isPercent
                      ? number_format((float) $coupon['discount_value'], 0, ',', '.') . '%'
                      : number_format((float) $coupon['discount_value'], 0, ',', '.') . 'đ';
                  $safeCode = htmlspecialchars((string) $coupon['code'], ENT_QUOTES, 'UTF-8');
                  $minValue = (float) $coupon['min_order_value'];
               ?>
               <article class="bs-offer-card">
                  <div class="bs-offer-card-top">
                     <span class="bs-offer-icon"><i class="fas fa-ticket-alt" aria-hidden="true"></i></span>
                     <span class="bs-offer-type">MÃ GIẢM GIÁ</span>
                  </div>
                  <p class="bs-offer-value">Giảm <strong><?= htmlspecialchars($couponValue, ENT_QUOTES, 'UTF-8') ?></strong></p>
                  <p class="bs-offer-minimum">
                     <?= $minValue > 0 ? 'Đơn từ ' . number_format($minValue, 0, ',', '.') . 'đ' : 'Áp dụng cho mọi giá trị đơn' ?>
                  </p>
                  <?php if (!empty($coupon['ends_at'])): ?>
                     <p class="bs-offer-expiry"><i class="far fa-clock" aria-hidden="true"></i> Đến <?= htmlspecialchars(date('d/m/Y', strtotime($coupon['ends_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                  <?php else: ?>
                     <p class="bs-offer-expiry"><i class="fas fa-check-circle" aria-hidden="true"></i> Đang áp dụng</p>
                  <?php endif; ?>
                  <div class="bs-offer-code-row">
                     <code><?= $safeCode ?></code>
                     <button type="button" class="bs-copy-coupon" data-code="<?= $safeCode ?>" aria-label="Sao chép mã <?= $safeCode ?>">Sao chép</button>
                  </div>
               </article>
            <?php endforeach; ?>
         </div>
      <?php else: ?>
         <div class="bs-offers-empty">
            <div><strong>Chưa có mã giảm giá đang áp dụng</strong><span>Khi cửa hàng phát hành voucher mới, mã sẽ xuất hiện tại đây.</span></div>
         </div>
      <?php endif; ?>
   </div>
</section>
<script>
// Chỉ sao chép mã cho người dùng, áp dụng/kiểm tra giá vẫn do checkout.php xử lý.
document.querySelectorAll('.bs-copy-coupon').forEach(function (button) {
   button.addEventListener('click', async function () {
      var code = button.getAttribute('data-code') || '';
      try {
         if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(code);
         } else {
            var field = document.createElement('textarea');
            field.value = code; field.style.position = 'fixed'; field.style.opacity = '0';
            document.body.appendChild(field); field.select();
            if (!document.execCommand('copy')) throw new Error('copy not supported');
            field.remove();
         }
         button.textContent = 'Đã chép!';
         button.classList.add('bs-copied');
         window.setTimeout(function () {button.textContent = 'Sao chép'; button.classList.remove('bs-copied');}, 2200);
      } catch (error) {
         button.textContent = 'Chọn mã để chép';
         window.setTimeout(function () {button.textContent = 'Sao chép';}, 2200);
      }
   });
});
</script>

<!-- section 1-->
<section class="products">
   <h1 class="title">Sản phẩm mới nhất</h1>
   <div class="box-container">
      <?php
         $select_products = mysqli_query($conn, "SELECT * FROM `products` LIMIT 10") or die('Lỗi truy vấn');
         if (mysqli_num_rows($select_products) > 0) {
            while ($fetch_products = mysqli_fetch_assoc($select_products)) {
      ?>
      <form action="" method="POST" class="box">
         <a href="pages/view_page.php?pid=<?php echo $fetch_products['id']; ?>" class="fas fa-eye"></a>
         <div class="price"><?php echo number_format($fetch_products['price'], 0, ',', '.'); ?> VNĐ</div>
         <img src="uploaded_img/<?php echo htmlspecialchars(basename($fetch_products['image']), ENT_QUOTES, 'UTF-8'); ?>" alt="" class="image">
         <div class="name"><?php echo htmlspecialchars($fetch_products['name'], ENT_QUOTES, 'UTF-8'); ?></div>
         <div class="category"><?php echo htmlspecialchars($fetch_products['category'], ENT_QUOTES, 'UTF-8'); ?></div>
         <div class="box-actions">
            <input type="number" name="product_quantity" value="1" min="1" max="99" class="qty">
            <input type="hidden" name="product_csrf" value="<?= htmlspecialchars($_SESSION['product_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">
            <button type="submit" name="add_to_wishlist" value="1" class="option-btn"><i class="fas fa-heart" aria-hidden="true"></i> Yêu thích</button>
            <input type="submit" value="Thêm vào giỏ hàng" name="add_to_cart" class="btn">
         </div>
      </form>
      <?php
         }
      } else {
         echo '<p class="empty">Chưa có sản phẩm nào được thêm!</p>';
      }
      ?>
   </div>
   <div class="more-btn">
      <a href="pages/shop.php" class="option-btn">Toàn bộ</a>
   </div>
</section>

<!-- section 2-->
<section class="products">
   <h1 class="title">Truyện tranh</h1>
   <div class="box-container">
      <?php
         $comic_category = mysqli_real_escape_string($conn, 'Truyện tranh');
         $select_products = mysqli_query($conn, "SELECT * FROM `products` WHERE `category` = '$comic_category' LIMIT 10") or die('Lỗi truy vấn');
         if (mysqli_num_rows($select_products) > 0) {
            while ($fetch_products = mysqli_fetch_assoc($select_products)) {
      ?>
          <form action="" method="POST" class="box">
            <a href="pages/view_page.php?pid=<?php echo $fetch_products['id']; ?>" class="fas fa-eye"></a>
            <div class="price"><?php echo number_format($fetch_products['price'], 0, ',', '.'); ?> VNĐ</div>
            <img src="uploaded_img/<?php echo htmlspecialchars(basename($fetch_products['image']), ENT_QUOTES, 'UTF-8'); ?>" alt="" class="image">
            <div class="name"><?php echo htmlspecialchars($fetch_products['name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="category"><?php echo htmlspecialchars($fetch_products['category'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="box-actions">
               <input type="number" name="product_quantity" value="1" min="1" max="99" class="qty">
               <input type="hidden" name="product_csrf" value="<?= htmlspecialchars($_SESSION['product_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">
               <button type="submit" name="add_to_wishlist" value="1" class="option-btn"><i class="fas fa-heart" aria-hidden="true"></i> Yêu thích</button>
               <input type="submit" value="Thêm vào giỏ hàng" name="add_to_cart" class="btn">
            </div>
         </form>
      <?php
         }
      } else {
         echo '<p class="empty">Chưa có sản phẩm nào được thêm!</p>';
      }
      ?>
   </div>
   <div class="more-btn">
      <a href="pages/shop.php?category=<?php echo urlencode('Truyện tranh'); ?>" class="option-btn">Toàn bộ</a>
   </div>

</section>

<section class="slideshow">
   <div class="slideshow-container">
   
      <div class="mySlides fade">
         <img src="images/bn1.jpg" style="width:100%">
      </div>
      <div class="mySlides fade">
         <img src="images/bn2.jpg" style="width:100%">
      </div>
      <div class="mySlides fade">
         <img src="images/bn3.jpg" style="width:100%">
      </div>
   
   </div> 

   
</section>

<!-- BOOKSTORY / LIÊN HỆ NHANH: lời mời ngắn gọn, không dùng icon trang trí. -->
<section class="home-contact" aria-labelledby="home-contact-title">
   <div class="content">
      <div class="bs-home-contact-copy">
         <h3 id="home-contact-title">Bạn có câu hỏi nào không?</h3>
         <p>Nếu cần tư vấn chọn sách hoặc hỗ trợ đơn hàng, bạn có thể nhắn cho BookStory bất cứ lúc nào.</p>
      </div>
      <a href="pages/contact.php" class="btn">Gửi lời nhắn</a>
   </div>
</section>

<?php @include 'components/footer.php'; ?>
<script src="js/script.js"></script>
<!--
<script>
   let slideIndex = 0;
   const slides = document.getElementsByClassName("mySlides");
   const dots = document.getElementsByClassName("dot");
   showSlides();

   function showSlides() {
      if (slides.length === 0) return;
      slideIndex = (slideIndex % slides.length) + 1;
      displaySlide(slideIndex);
      setTimeout(showSlides, 5000);
   }

   function currentSlide(index) {
      slideIndex = index;
      displaySlide(slideIndex);
   }

   function displaySlide(index) {
      for (let i = 0; i < slides.length; i++) {
         slides[i].style.display = "none";
         dots[i].classList.remove("active");
      }
      slides[index - 1].style.display = "block";
      dots[index - 1].classList.add("active");
   }
</script>-->
  <script>
   let slideIndex = 0;
   showSlides();
   function showSlides() {
   let i;
   let slides = document.getElementsByClassName("mySlides");
   for (i = 0; i < slides.length; i++) {
      slides[i].style.display = "none";
   }
   slideIndex++;
   if (slideIndex > slides.length) {slideIndex = 1}
   slides[slideIndex-1].style.display = "block";
   setTimeout(showSlides, 3000);
   }
   </script>

</body>
</html>
