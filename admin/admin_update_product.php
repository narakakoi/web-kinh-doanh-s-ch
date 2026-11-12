<?php
@include '../config/config.php'; 
$book_categories = require '../config/book_categories.php';

session_start();

$admin_id =$_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../pages/login.php');
    exit();
}

if (isset($_POST['update_product'])) {
    $update_p_id =$_POST['update_p_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);$category = mysqli_real_escape_string($conn,$_POST['category']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);$details = mysqli_real_escape_string($conn,$_POST['details']);

    if (!in_array($_POST['category'], $book_categories, true)) {$message[] = 'Thể loại sách không hợp lệ!';
    } else {
        mysqli_query($conn, "UPDATE `products` SET name = '$name', category = '$category', details = '$details', price = '$price' WHERE id = '$update_p_id'") or die('Truy vấn thất bại');

        $image =$_FILES['image']['name'];
        $image_size =$_FILES['image']['size'];
        $image_tmp_name =$_FILES['image']['tmp_name'];
        $image_folder = __DIR__ . '/../uploaded_img/' .$image;
        $old_image =$_POST['update_p_image'];

        if (!empty($image)) {
            if ($image_size > 2000000) {$message[] = 'Kích thước tệp hình ảnh quá lớn!';
            } else {
                if (move_uploaded_file($image_tmp_name,$image_folder)) {
                    $old_image_path = __DIR__ . '/../uploaded_img/' .$old_image;
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
    <title>Cập Nhật Sản Phẩm - BookStory Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<section class="update-product">
<?php
   $update_id =$_GET['update'] ?? '';
   $select_products = mysqli_query($conn, "SELECT * FROM `products` WHERE id = '$update_id'") or die('Truy vấn thất bại');
   if (mysqli_num_rows($select_products) > 0) {
       while ($fetch_products = mysqli_fetch_assoc($select_products)) {
?>

<form action="" method="post" enctype="multipart/form-data">
   <div class="form-header">
      <h3><i class="fas fa-pen-square"></i> Chỉnh Sửa Thông Tin Sách</h3>
      <p>Cập nhật thông tin chi tiết cho sản phẩm #<?php echo $fetch_products['id']; ?></p>
   </div>

   <div class="preview-box">
      <img src="../uploaded_img/<?php echo htmlspecialchars($fetch_products['image']); ?>" class="image" alt="">
   </div>

   <input type="hidden" value="<?php echo $fetch_products['id']; ?>" name="update_p_id">
   <input type="hidden" value="<?php echo htmlspecialchars($fetch_products['image']); ?>" name="update_p_image">

   <div class="form-grid">
      <div class="form-group">
         <label>Tên sản phẩm</label>
         <input type="text" class="box" value="<?php echo htmlspecialchars($fetch_products['name']); ?>" required placeholder="Cập nhật tên sản phẩm" name="name">
      </div>

      <div class="form-group">
         <label>Thể loại sách</label>
         <select name="category" class="box" required>
            <?php foreach ($book_categories as$book_category): ?>
               <option value="<?php echo htmlspecialchars($book_category, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $fetch_products['category'] ===$book_category ? 'selected' : ''; ?>><?php echo htmlspecialchars($book_category, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
         </select>
      </div>

      <div class="form-group">
         <label>Giá bán (VNĐ)</label>
         <input type="number" min="0" class="box" value="<?php echo htmlspecialchars($fetch_products['price']); ?>" required placeholder="Cập nhật giá sản phẩm" name="price">
      </div>

      <div class="form-group">
         <label>Đổi ảnh bìa mới (bỏ trống nếu giữ nguyên)</label>
         <input type="file" accept="image/jpg, image/jpeg, image/png" class="box file-input" name="image">
      </div>

      <div class="form-group full-width">
         <label>Mô tả chi tiết</label>
         <textarea name="details" class="box" required placeholder="Cập nhật chi tiết sản phẩm" cols="30" rows="6"><?php echo htmlspecialchars($fetch_products['details']); ?></textarea>
      </div>
   </div>

   <div class="card-actions">
      <button type="submit" name="update_product" class="btn">
         <i class="fas fa-save"></i> Lưu Thay Đổi
      </button>
      <a href="admin_products.php" class="option-btn">
         <i class="fas fa-arrow-left"></i> Quay Lại Kho Sách
      </a>
   </div>
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
