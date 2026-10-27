<?php
@include '../config/config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:../pages/login.php');
};

if(isset($_POST['add_to_wishlist'])){
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    
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
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $product_quantity = $_POST['product_quantity'];

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
   <title>Xem nhanh</title>
   <!-- liên kết font awesome cdn -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <!-- liên kết tệp css tùy chỉnh -->
   <link rel="stylesheet" href="../css/style.css">
</head>
<body>
   
<?php @include '../components/header.php'; ?>

<section class="product-detail">
    <a href="shop.php" class="back-link">
        <i class="fas fa-arrow-left" aria-hidden="true"></i> Về cửa hàng
    </a>
    <?php  
        if(isset($_GET['pid'])){
            $pid = $_GET['pid'];
            $select_products = mysqli_query($conn, "SELECT * FROM `products` WHERE id = '$pid'") or die('Truy vấn thất bại');
         if(mysqli_num_rows($select_products) > 0){
            while($fetch_products = mysqli_fetch_assoc($select_products)){
    ?>
    <form action="" method="POST">
        <div class="product-top">
            <figure class="product-media">
                <img src="../uploaded_img/<?php echo $fetch_products['image']; ?>" alt="<?php echo htmlspecialchars($fetch_products['name'], ENT_QUOTES, 'UTF-8'); ?>" class="image">
            </figure>
            <div class="product-info">
                <a class="category" href="shop.php?category=<?php echo urlencode($fetch_products['category']); ?>"><?php echo htmlspecialchars($fetch_products['category'], ENT_QUOTES, 'UTF-8'); ?></a>
                <h1 class="name"><?php echo $fetch_products['name']; ?></h1>
                <div class="price-block">
                    <span class="price-label">Giá mỗi cuốn</span>
                    <div class="price"><?php echo number_format($fetch_products['price'], 0, ',', '.'); ?> VNĐ</div>
                </div>
                <div class="purchase">
                    <div class="qty-field">
                        <label class="qty-label" for="product-qty">Số lượng</label>
                        <input type="number" id="product-qty" name="product_quantity" value="1" min="0" class="qty">
                    </div>
                    <input type="submit" value="Thêm vào giỏ hàng" name="add_to_cart" class="btn">
                    <button type="submit" name="add_to_wishlist" value="1" class="option-btn"><i class="fas fa-heart" aria-hidden="true"></i> Yêu thích</button>
                </div>
                <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">
                <input type="hidden" name="product_name" value="<?php echo $fetch_products['name']; ?>">
                <input type="hidden" name="product_price" value="<?php echo $fetch_products['price']; ?>">
                <input type="hidden" name="product_image" value="<?php echo $fetch_products['image']; ?>">
            </div>
        </div>
        <div class="product-description">
            <h2>Nội dung sách</h2>
            <p class="details"><?php echo $fetch_products['details']; ?></p>
        </div>
    </form>
    <?php
            }
        }else{
        echo '<p class="empty">Không có chi tiết sản phẩm!</p>';
        }
    }
    ?>
</section>

<?php @include '../components/footer.php'; ?>
<script src="../js/script.js"></script>
</body>
</html>
