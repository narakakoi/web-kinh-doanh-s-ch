<?php
@include 'config/config.php';

session_start();

$user_id = $_SESSION['user_id'] ;

if (!isset($user_id)) {
    header('location:pages/login.php');
    exit();
}

$message = [];

// Xử lý thêm sản phẩm vào danh sách yêu thích
if (isset($_POST['add_to_wishlist'])) {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_price = mysqli_real_escape_string($conn, $_POST['product_price']);
    $product_image = mysqli_real_escape_string($conn, $_POST['product_image']);
    
    $check_wishlist_numbers = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE name = '$product_name' AND user_id = '$user_id'") or die('Lỗi truy vấn');
    $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$user_id'") or die('Lỗi truy vấn');

    if (mysqli_num_rows($check_wishlist_numbers) > 0) {
        $message[] = 'Sản phẩm đã được thêm vào danh sách yêu thích.';
    } elseif (mysqli_num_rows($check_cart_numbers) > 0) {
        $message[] = 'Sản phẩm đã được thêm vào giỏ hàng.';
    } else {
        mysqli_query($conn, "INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES('$user_id', '$product_id', '$product_name', '$product_price', '$product_image')") or die('Lỗi truy vấn');
        $message[] = 'Sản phẩm đã được thêm vào danh sách yêu thích.';
    }
}

// Xử lý thêm sản phẩm vào giỏ hàng
if (isset($_POST['add_to_cart'])) {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_price = mysqli_real_escape_string($conn, $_POST['product_price']);
    $product_image = mysqli_real_escape_string($conn, $_POST['product_image']);
    $product_quantity = mysqli_real_escape_string($conn, $_POST['product_quantity']);

    $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$user_id'") or die('Lỗi truy vấn');

    if (mysqli_num_rows($check_cart_numbers) > 0) {
        $message[] = 'Sản phẩm đã được thêm vào giỏ hàng.';
    } else {
        $check_wishlist_numbers = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE name = '$product_name' AND user_id = '$user_id'") or die('Lỗi truy vấn');

        if (mysqli_num_rows($check_wishlist_numbers) > 0) {
            mysqli_query($conn, "DELETE FROM `wishlist` WHERE name = '$product_name' AND user_id = '$user_id'") or die('Lỗi truy vấn');
        }
        mysqli_query($conn, "INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES('$user_id', '$product_id', '$product_name', '$product_price', '$product_quantity', '$product_image')") or die('Lỗi truy vấn');
        $message[] = 'Sản phẩm đã được thêm vào giỏ hàng.';
    }
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
</head>
<body>
   
<?php @include 'components/header.php'; ?>
<section class="home">

   <div class ="homeslide fade">
      <img src="images/slider_2.webp" alt="lỗi">
   </div>
   <div class ="homeslide fade">
      <img src="images/slider_3.webp" alt="lỗi">
   </div>
   <div class ="homeslide fade">
      <img src="images/slider_4.webp" alt="lỗi">
   </div>
   <button class="prev" onclick="plusSlides(-1)">&#10094;</button>
   <button class="next" onclick="plusSlides(1)">&#10095;</button>

   <div class="content">
      <!--<h3>Sách là tri thức của nhân loại</h3>
      <a href="pages/about.php" class="btn">Xem Thêm</a>-->
   </div>
</section>

<script>
   let plusIndex = 1;
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
<!-- section 1-->
<section class="products">
   <h1 class="title">Sản phẩm mới nhất</h1>
   <div class="box-container">
      <?php
         $select_products = mysqli_query($conn, "SELECT * FROM `products` LIMIT 8") or die('Lỗi truy vấn');
         if (mysqli_num_rows($select_products) > 0) {
            while ($fetch_products = mysqli_fetch_assoc($select_products)) {
      ?>
      <form action="" method="POST" class="box">
         <a href="pages/view_page.php?pid=<?php echo $fetch_products['id']; ?>" class="fas fa-eye"></a>
         <div class="price"><?php echo number_format($fetch_products['price'], 0, ',', '.'); ?> VNĐ</div>
         <img src="uploaded_img/<?php echo $fetch_products['image']; ?>" alt="" class="image">
         <div class="name"><?php echo $fetch_products['name']; ?></div>
         <div class="category"><?php echo htmlspecialchars($fetch_products['category'], ENT_QUOTES, 'UTF-8'); ?></div>
         <div class="box-actions">
            <input type="number" name="product_quantity" value="1" min="1" class="qty">
            <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">
            <input type="hidden" name="product_name" value="<?php echo $fetch_products['name']; ?>">
            <input type="hidden" name="product_price" value="<?php echo $fetch_products['price']; ?>">
            <input type="hidden" name="product_image" value="<?php echo $fetch_products['image']; ?>">
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
         $select_products = mysqli_query($conn, "SELECT * FROM `products` WHERE `category` = '$comic_category' LIMIT 8") or die('Lỗi truy vấn');
         if (mysqli_num_rows($select_products) > 0) {
            while ($fetch_products = mysqli_fetch_assoc($select_products)) {
      ?>
          <form action="" method="POST" class="box">
            <a href="pages/view_page.php?pid=<?php echo $fetch_products['id']; ?>" class="fas fa-eye"></a>
            <div class="price"><?php echo number_format($fetch_products['price'], 0, ',', '.'); ?> VNĐ</div>
            <img src="uploaded_img/<?php echo $fetch_products['image']; ?>" alt="" class="image">
            <div class="name"><?php echo $fetch_products['name']; ?></div>
            <div class="category"><?php echo htmlspecialchars($fetch_products['category'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="box-actions">
               <input type="number" name="product_quantity" value="1" min="1" class="qty">
               <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">
               <input type="hidden" name="product_name" value="<?php echo $fetch_products['name']; ?>">
               <input type="hidden" name="product_price" value="<?php echo $fetch_products['price']; ?>">
               <input type="hidden" name="product_image" value="<?php echo $fetch_products['image']; ?>">
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

<section class="home-contact">
   <div class="content">
      <h3>Bạn có câu hỏi nào không?</h3>
      <p>Chúng tôi cảm ơn bạn vì đã quan tâm đến các sản phẩm. Bạn có thể gửi ý kiến của mình cho chúng tôi nếu cần sự hỗ trợ.</p>
      <a href="pages/contact.php" class="btn">Liên hệ với chúng tôi</a>
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
