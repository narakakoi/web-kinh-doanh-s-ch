<?php
require_once '../config/config.php'; 
$book_categories = require '../config/book_categories.php';

session_start();

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
   header('location:../pages/login.php');
   exit();
}

require_once __DIR__ . '/../config/product_actions.php';
$returnUrl = 'shop.php';
if (is_string($_GET['category'] ?? null)) $returnUrl .= '?' . http_build_query(['category' => $_GET['category']]);
$message = process_product_request($conn, (int) $user_id, $_POST, $returnUrl);
$wishlistProducts = product_wishlist_ids($conn, (int) $user_id);

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Cửa hàng</title>
   <!-- liên kết font awesome cdn -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <!-- liên kết tệp css tùy chỉnh -->
   <link rel="stylesheet" href="../css/style.css">
   <link rel="stylesheet" href="../css/components/header.css">
   <link rel="stylesheet" href="../css/components/footer.css">
   <link rel="stylesheet" href="../css/components/product-notice.css">
   <link rel="stylesheet" href="../css/components/product-card.css">
   <link rel="stylesheet" href="../css/components/search-form.css">
   <!-- Chỉ đổi màu và chữ cho Shop, không sửa layout/form. -->
   <link rel="stylesheet" href="../css/pages/shop.css">  
</head>
<body>
   
<?php @include '../components/header.php'; ?>
<?php include __DIR__ . '/../components/product_notice.php'; ?>

<section class="heading bs-shop-heading">
    <h3>Cửa hàng của chúng tôi</h3>
    <p> <a href="../index.php">Trang chủ</a> / Cửa hàng </p>  
</section>
<?php
   $selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';
   $category_is_valid = $selected_category === '' || in_array($selected_category, $book_categories, true);
?>
<section class="search-form">
   <form action="shop.php" method="GET">
      <select name="category" class="box">
         <option value="">Tất cả thể loại</option>
         <?php foreach ($book_categories as $book_category): ?>
            <option value="<?php echo htmlspecialchars($book_category, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected_category === $book_category ? 'selected' : ''; ?>><?php echo htmlspecialchars($book_category, ENT_QUOTES, 'UTF-8'); ?></option>
         <?php endforeach; ?>
      </select>
      <input type="submit" class="btn" value="Lọc sách">
   </form>
</section>
<section class="products bs-shop-products">
   <h1 class="title"><?php echo $selected_category !== '' && $category_is_valid ? 'Sách thể loại ' . htmlspecialchars($selected_category, ENT_QUOTES, 'UTF-8') : 'Sản phẩm mới nhất'; ?></h1>
   <div class="box-container">
      <?php
         if (!$category_is_valid) {
            echo '<p class="empty">Thể loại sách không hợp lệ.</p>';
            $select_products = false;
         } elseif ($selected_category !== '') {
            $escaped_category = mysqli_real_escape_string($conn, $selected_category);
            $select_products = mysqli_query($conn, "SELECT * FROM `products` WHERE category = '$escaped_category'") or die('Truy vấn thất bại');
         } else {
            $select_products = mysqli_query($conn, "SELECT * FROM `products`") or die('Truy vấn thất bại');
         }
         if($select_products && mysqli_num_rows($select_products) > 0){
            while($fetch_products = mysqli_fetch_assoc($select_products)){
      ?>
      <form action="" method="POST" class="box" data-product-form>
         <a href="view_page.php?pid=<?php echo $fetch_products['id']; ?>" class="fas fa-eye"></a>
         <div class="price"><?php echo number_format($fetch_products['price'], 0, ',', '.'); ?> VNĐ</div>
         <img src="../uploaded_img/<?php echo htmlspecialchars(basename($fetch_products['image']), ENT_QUOTES, 'UTF-8'); ?>" alt="" class="image">
         <div class="name"><?php echo htmlspecialchars($fetch_products['name'], ENT_QUOTES, 'UTF-8'); ?></div>
         <div class="category"><?php echo htmlspecialchars($fetch_products['category'], ENT_QUOTES, 'UTF-8'); ?></div>
         <div class="box-actions">
            <input type="number" name="product_quantity" value="1" min="1" max="99" class="qty">
            <input type="hidden" name="product_csrf" value="<?= htmlspecialchars($_SESSION['product_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">
            <button type="submit" name="add_to_wishlist" value="1" class="option-btn" formnovalidate data-wishlist-saved="<?= isset($wishlistProducts[(int) $fetch_products['id']]) ? 'true' : 'false' ?>" title="<?= isset($wishlistProducts[(int) $fetch_products['id']]) ? 'Đã lưu vào yêu thích' : 'Thêm vào yêu thích' ?>"><i class="<?= isset($wishlistProducts[(int) $fetch_products['id']]) ? 'fas' : 'far' ?> fa-heart" aria-hidden="true"></i> Yêu thích</button>
            <input type="submit" value="Thêm vào giỏ hàng" name="add_to_cart" class="btn">
         </div>
      </form>
      <?php
         }
      }elseif ($category_is_valid) {
         echo '<p class="empty">Chưa có sản phẩm nào!</p>';
      }
      ?>
   </div>
</section>

<?php @include '../components/footer.php'; ?> 
<script src="../js/script.js"></script>
<script src="../js/product-actions.js"></script>

</body>
</html>
