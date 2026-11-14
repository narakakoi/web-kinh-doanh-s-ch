<?php
require_once '../config/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}

session_start();
$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
   header('location:../pages/login.php');
   exit();
}

if(isset($_POST['add_to_wishlist'])){
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $product_name = mysqli_real_escape_string($conn, trim((string) ($_POST['product_name'] ?? '')));
    $product_price = (float) ($_POST['product_price'] ?? 0);
    $product_image = mysqli_real_escape_string($conn, trim((string) ($_POST['product_image'] ?? '')));
    
    $check_wishlist_numbers = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE name = '$product_name' AND user_id = '$user_id'") or die('Truy vấn thất bại');

    $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$user_id'") or die('Truy vấn thất bại');

    if(mysqli_num_rows($check_wishlist_numbers) > 0){
        $message[] = 'Đã thêm vào danh sách yêu thích';
    }elseif(mysqli_num_rows($check_cart_numbers) > 0){
        $message[] = 'Đã thêm vào giỏ hàng';
    }else{
        mysqli_query($conn, "INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES('$user_id', '$product_id', '$product_name', '$product_price', '$product_image')") or die('Truy vấn thất bại');
        $message[] = 'Sản phẩm đã được thêm vào danh sách yêu thích';
    }
}

if(isset($_POST['add_to_cart'])){
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $product_name = mysqli_real_escape_string($conn, trim((string) ($_POST['product_name'] ?? '')));
    $product_price = (float) ($_POST['product_price'] ?? 0);
    $product_image = mysqli_real_escape_string($conn, trim((string) ($_POST['product_image'] ?? '')));
    $product_quantity = max(1, (int) ($_POST['product_quantity'] ?? 1));

    $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$user_id'") or die('Truy vấn thất bại');

    if(mysqli_num_rows($check_cart_numbers) > 0){
        $message[] = 'Đã thêm vào giỏ hàng';
    }else{
        $check_wishlist_numbers = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE name = '$product_name' AND user_id = '$user_id'") or die('Truy vấn thất bại');
        if(mysqli_num_rows($check_wishlist_numbers) > 0){
            mysqli_query($conn, "DELETE FROM `wishlist` WHERE name = '$product_name' AND user_id = '$user_id'") or die('Truy vấn thất bại');
        }
        mysqli_query($conn, "INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES('$user_id', '$product_id', '$product_name', '$product_price', '$product_quantity', '$product_image')") or die('Truy vấn thất bại');
        $message[] = 'Sản phẩm đã được thêm vào giỏ hàng';
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Chi tiết sách | BookStory</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="../css/style.css">
   <!-- CSS riêng trang chi tiết, không sửa cấu trúc header/footer. -->
   <link rel="stylesheet" href="../css/pages/view-page.css">
</head>
<body>
<?php @include '../components/header.php'; ?>
<main class="bs-view-page">
   <div class="bs-view-wrap">
      <nav class="bs-view-crumb" aria-label="Đường dẫn"><a href="../index.php">Trang chủ</a><i class="fas fa-chevron-right" aria-hidden="true"></i><a href="shop.php">Cửa hàng</a><i class="fas fa-chevron-right" aria-hidden="true"></i><span>Chi tiết sách</span></nav>
      <?php
         // Ép id về số nguyên để tránh chèn dữ liệu không hợp lệ vào truy vấn.
         $pid = filter_input(INPUT_GET, 'pid', FILTER_VALIDATE_INT);
         $select_products = $pid ? mysqli_query($conn, 'SELECT * FROM `products` WHERE id = ' . (int) $pid . ' LIMIT 1') : false;
         if ($select_products && mysqli_num_rows($select_products) > 0):
            $fetch_products = mysqli_fetch_assoc($select_products);
            $bookName = htmlspecialchars((string) $fetch_products['name'], ENT_QUOTES, 'UTF-8');
            $bookCategory = htmlspecialchars((string) $fetch_products['category'], ENT_QUOTES, 'UTF-8');
            $bookImage = htmlspecialchars(basename((string) $fetch_products['image']), ENT_QUOTES, 'UTF-8');
      ?>
      <form action="" method="POST" class="bs-view-book-form">
         <div class="bs-view-product">
            <div class="bs-view-visual"><div class="bs-view-image-frame"><img src="../uploaded_img/<?= $bookImage ?>" alt="Bìa sách <?= $bookName ?>"></div><div class="bs-view-image-note"><i class="far fa-image" aria-hidden="true"></i> Hình ảnh bìa sách</div></div>
            <div class="bs-view-information">
               <span class="bs-view-kicker"><i class="fas fa-book-open" aria-hidden="true"></i> GÓC SÁCH BOOKSTORY</span>
               <a href="shop.php?category=<?= urlencode((string) $fetch_products['category']) ?>" class="bs-view-category"><?= $bookCategory ?></a>
               <h1><?= $bookName ?></h1>
               <div class="bs-view-price"><span>Giá bán</span><strong><?= number_format((float) $fetch_products['price'], 0, ',', '.') ?> đ</strong><small>Giá hiển thị cho một cuốn sách</small></div>
               <div class="bs-view-action-area"><label for="bs-product-qty">Số lượng</label><div class="bs-view-purchase"><input type="number" id="bs-product-qty" name="product_quantity" value="1" min="1" max="99" required aria-label="Số lượng sách"><button type="submit" name="add_to_cart" value="1" class="bs-view-add-cart"><i class="fas fa-shopping-cart" aria-hidden="true"></i> Thêm vào giỏ hàng</button></div><button type="submit" name="add_to_wishlist" value="1" class="bs-view-like"><i class="far fa-heart" aria-hidden="true"></i> Thêm vào yêu thích</button></div>
               <div class="bs-view-advantages"><div><i class="fas fa-shield-alt" aria-hidden="true"></i><span>Kiểm tra đơn trước khi thanh toán</span></div><div><i class="fas fa-list-alt" aria-hidden="true"></i><span>Theo dõi đơn hàng của bạn</span></div></div>
               <!-- Giữ tên hidden field tương thích với PHP xử lý giỏ hàng hiện tại. -->
               <input type="hidden" name="product_id" value="<?= (int) $fetch_products['id'] ?>">
               <input type="hidden" name="product_name" value="<?= $bookName ?>">
               <input type="hidden" name="product_price" value="<?= htmlspecialchars((string) $fetch_products['price'], ENT_QUOTES, 'UTF-8') ?>">
               <input type="hidden" name="product_image" value="<?= $bookImage ?>">
            </div>
         </div>
      </form>
      <section class="bs-view-description" aria-labelledby="bs-book-desc-title"><div class="bs-view-description-header"><i class="fas fa-align-left" aria-hidden="true"></i><h2 id="bs-book-desc-title">Nội dung sách</h2></div><p><?= nl2br(htmlspecialchars((string) $fetch_products['details'], ENT_QUOTES, 'UTF-8')) ?></p></section>
      <?php else: ?>
         <div class="bs-view-missing"><i class="fas fa-book-open" aria-hidden="true"></i><h1>Không tìm thấy sách</h1><p>Cuốn sách này có thể đã được gỡ khỏi cửa hàng.</p><a href="shop.php">Quay lại cửa hàng</a></div>
      <?php endif; ?>
   </div>
</main>
<?php @include '../components/footer.php'; ?>
<script src="../js/script.js"></script>
</body>
</html>
