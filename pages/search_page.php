<?php
require_once __DIR__ . '/../config/config.php';

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

require_once __DIR__ . '/../config/product_actions.php';
$rawSearch = $_GET['search_box'] ?? $_POST['search_box'] ?? null;
$hasSearch = is_string($rawSearch);
$searchTerm = $hasSearch ? trim($rawSearch) : '';
$returnUrl = 'search_page.php' . ($hasSearch ? '?' . http_build_query(['search_box' => $searchTerm]) : '');
$message = process_product_request($conn, (int) $user_id, $_POST, $returnUrl);
$wishlistProducts = product_wishlist_ids($conn, (int) $user_id);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Tìm kiếm</title>
   <!-- liên kết font awesome cdn -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <!-- liên kết file css tùy chỉnh -->
   <link rel="stylesheet" href="../css/style.css">
   <link rel="stylesheet" href="../css/components/header.css">
   <link rel="stylesheet" href="../css/components/footer.css">
   <link rel="stylesheet" href="../css/components/product-notice.css">
   <link rel="stylesheet" href="../css/components/product-card.css">
   <link rel="stylesheet" href="../css/components/search-form.css">

</head>
<body>
   
<?php @include '../components/header.php'; ?>
<?php include __DIR__ . '/../components/product_notice.php'; ?>

<section class="heading">
    <h3>Tìm kiếm</h3>
    <p> <a href="../index.php">Trang chủ</a> / Tìm kiếm </p>
</section>

<section class="search-form">
    <form action="search_page.php" method="GET">
        <input type="text" class="box" placeholder="Tìm kiếm sản phẩm..." name="search_box" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>">
        <input type="submit" class="btn" value="Tìm kiếm" name="search_btn">
    </form>
</section>
<section class="products" style="padding-top: 0;">
   <div class="box-container">
      <?php
        if ($hasSearch) {
         $search_box = mysqli_real_escape_string($conn, $searchTerm);
         $select_products = mysqli_query($conn, "SELECT * FROM `products` WHERE name LIKE '%{$search_box}%'") or die('Lỗi truy vấn');
         if(mysqli_num_rows($select_products) > 0){
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
            <input type="hidden" name="search_box" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">
            <button type="submit" name="add_to_wishlist" value="1" class="option-btn" formnovalidate data-wishlist-saved="<?= isset($wishlistProducts[(int) $fetch_products['id']]) ? 'true' : 'false' ?>" title="<?= isset($wishlistProducts[(int) $fetch_products['id']]) ? 'Đã lưu vào yêu thích' : 'Thêm vào yêu thích' ?>"><i class="far fa-heart" aria-hidden="true"></i> Yêu thích</button>
            <input type="submit" value="Thêm vào giỏ hàng" name="add_to_cart" class="btn">
         </div>
      </form>
      <?php
            }
            }else{
                echo '<p class="empty">Không tìm thấy kết quả!</p>';
            }
        }else{
            echo '<p class="empty">Tìm kiếm gì đó!</p>';
        }
      ?>
   </div>
</section>

<?php @include '../components/footer.php'; ?>
<script src="../js/script.js"></script>
<script src="../js/product-actions.js"></script>
</body>
</html>
