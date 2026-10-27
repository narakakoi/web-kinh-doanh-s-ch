<?php
@include '../config/config.php'; 
$book_categories = require '../config/book_categories.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../pages/login.php');
    exit();
}

if (isset($_POST['update_product'])) {
    $update_p_id = $_POST['update_p_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $details = mysqli_real_escape_string($conn, $_POST['details']);

    if (!in_array($_POST['category'], $book_categories, true)) {
        $message[] = 'Thể loại sách không hợp lệ!';
    } else {
        mysqli_query($conn, "UPDATE `products` SET name = '$name', category = '$category', details = '$details', price = '$price' WHERE id = '$update_p_id'") or die('Truy vấn thất bại');

        $image = $_FILES['image']['name'];
        $image_size = $_FILES['image']['size'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = __DIR__ . '/../uploaded_img/' . $image;
        $old_image = $_POST['update_p_image'];

        if (!empty($image)) {
            if ($image_size > 2000000) {
                $message[] = 'Kích thước tệp hình ảnh quá lớn!';
            } else {
                if (move_uploaded_file($image_tmp_name, $image_folder)) {
                    // Xóa ảnh cũ nếu tồn tại
                    $old_image_path = __DIR__ . '/../uploaded_img/' . $old_image;
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                    mysqli_query($conn, "UPDATE `products` SET image = '$image' WHERE id = '$update_p_id'") or die('Cập nhật hình ảnh thất bại');
                    $message[] = 'Cập nhật hình ảnh thành công!';
                } else {
                    $message[] = 'Không thể di chuyển tệp hình ảnh!';
                }
            }
        }
        $message[] = 'Cập nhật sản phẩm thành công!';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật sản phẩm</title>
    <!-- link cdn font awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- link file css admin custom -->
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<?php if (!empty($message)): ?>
    <div class="message">
        <?php foreach ($message as $item): ?>
            <span><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<section class="update-product">
<?php
   $update_id = $_GET['update'];
   $select_products = mysqli_query($conn, "SELECT * FROM `products` WHERE id = '$update_id'") or die('Truy vấn thất bại');
   if (mysqli_num_rows($select_products) > 0) {
       while ($fetch_products = mysqli_fetch_assoc($select_products)) {
?>

<form action="" method="post" enctype="multipart/form-data">
   <img src="../uploaded_img/<?php echo $fetch_products['image']; ?>" class="image" alt="">
   <input type="hidden" value="<?php echo $fetch_products['id']; ?>" name="update_p_id">
   <input type="hidden" value="<?php echo $fetch_products['image']; ?>" name="update_p_image">
   <input type="text" class="box" value="<?php echo $fetch_products['name']; ?>" required placeholder="Cập nhật tên sản phẩm" name="name">
   <select name="category" class="box" required>
      <?php foreach ($book_categories as $book_category): ?>
         <option value="<?php echo htmlspecialchars($book_category, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $fetch_products['category'] === $book_category ? 'selected' : ''; ?>><?php echo htmlspecialchars($book_category, ENT_QUOTES, 'UTF-8'); ?></option>
      <?php endforeach; ?>
   </select>
   <input type="number" min="0" class="box" value="<?php echo $fetch_products['price']; ?>" required placeholder="Cập nhật giá sản phẩm" name="price">
   <textarea name="details" class="box" required placeholder="Cập nhật chi tiết sản phẩm" cols="30" rows="10"><?php echo $fetch_products['details']; ?></textarea>
   <input type="file" accept="image/jpg, image/jpeg, image/png" class="box" name="image">
   <input type="submit" value="Cập nhật sản phẩm" name="update_product" class="btn">
   <a href="admin_products.php" class="option-btn">Quay lại</a>
</form>

<?php
       }
   } else {
       echo '<p class="empty">Không có sản phẩm nào được chọn để cập nhật</p>';
   }
?>

</section>

<script src="../js/admin_script.js"></script>

</body>
</html>
