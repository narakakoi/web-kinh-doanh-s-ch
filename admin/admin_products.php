<?php
@include '../config/config.php'; 
$book_categories = require '../config/book_categories.php';

session_start();

$admin_id =$_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../pages/login.php');
    exit();
}

if (isset($_POST['add_product'])) {

    $name = mysqli_real_escape_string($conn, $_POST['name']);$category = mysqli_real_escape_string($conn,$_POST['category']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);$details = mysqli_real_escape_string($conn,$_POST['details']);
    $image =$_FILES['image']['name'];
    $image_size =$_FILES['image']['size'];
    $image_tmp_name =$_FILES['image']['tmp_name'];
    $image_folder = __DIR__ . '/../uploaded_img/' .$image;

    if (!in_array($_POST['category'], $book_categories, true)) {$message[] = 'Thể loại sách không hợp lệ!';
    } else {
        $select_product_name = mysqli_query($conn, "SELECT name FROM `products` WHERE name = '$name'") or die('Truy vấn thất bại');

        if (mysqli_num_rows($select_product_name) > 0) {$message[] = 'Tên sản phẩm đã tồn tại!';
        } else {
            $insert_product = mysqli_query($conn, "INSERT INTO `products`(name, category, details, price, image) VALUES('$name', '$category', '$details', '$price', '$image')") or die('Truy vấn thất bại');

            if ($insert_product) {
                if ($image_size > 2000000) {$message[] = 'Kích thước ảnh quá lớn!';
                } else {
                    if (move_uploaded_file($image_tmp_name, $image_folder)) {$message[] = 'Thêm sản phẩm thành công!';
                    } else {
                        $message[] = 'Không thể di chuyển tệp hình ảnh!';
                    }
                }
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $delete_id =$_GET['delete'];
    $select_delete_image = mysqli_query($conn, "SELECT image FROM `products` WHERE id = '$delete_id'") or die('Truy vấn thất bại');
    $fetch_delete_image = mysqli_fetch_assoc($select_delete_image);
    $old_image_path = __DIR__ . '/../uploaded_img/' .$fetch_delete_image['image'];
    if (file_exists($old_image_path)) {
        unlink($old_image_path);
    }
    mysqli_query($conn, "DELETE FROM `products` WHERE id = '$delete_id'") or die('Truy vấn thất bại');
    mysqli_query($conn, "DELETE FROM `wishlist` WHERE pid = '$delete_id'") or die('Truy vấn thất bại');
    mysqli_query($conn, "DELETE FROM `cart` WHERE pid = '$delete_id'") or die('Truy vấn thất bại');

    header('location:admin_products.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm - BookStory Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<section class="add-products">
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-header">
            <h3><i class="fas fa-plus-circle"></i> Thêm Sản Phẩm Mới</h3>
            <p>Điền đầy đủ thông tin bên dưới để đăng bán sách mới lên hệ thống</p>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>Tên đầu sách</label>
                <input type="text" class="box" required placeholder="Nhập tên sản phẩm..." name="name">
            </div>

            <div class="form-group">
                <label>Danh mục thể loại</label>
                <select name="category" class="box" required>
                    <option value="">Chọn thể loại sách</option>
                    <?php foreach ($book_categories as$book_category): ?>
                        <option value="<?php echo htmlspecialchars($book_category, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($book_category, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Giá bán niêm yết (VNĐ)</label>
                <input type="number" min="0" class="box" required placeholder="Ví dụ: 85000" name="price">
            </div>

            <div class="form-group">
                <label>Ảnh bìa sách (JPG, PNG)</label>
                <input type="file" accept="image/jpg, image/jpeg, image/png" required class="box file-input" name="image">
            </div>

            <div class="form-group full-width">
                <label>Mô tả nội dung sách</label>
                <textarea name="details" class="box" required placeholder="Nhập tóm tắt nội dung hoặc thông tin xuất bản..." cols="30" rows="6"></textarea>
            </div>
        </div>

        <button type="submit" name="add_product" class="btn submit-btn">
            <i class="fas fa-cloud-upload-alt"></i> Thêm Sản Phẩm Vào Kho
        </button>
    </form>
</section>

<section class="show-products">
    <div class="section-heading">
        <h2 class="title">Danh Mục Sách Đang Kinh Doanh</h2>
    </div>

    <div class="box-container">
        <?php
        $select_products = mysqli_query($conn, "SELECT * FROM `products` ORDER BY id DESC") or die('Truy vấn thất bại');
        if (mysqli_num_rows($select_products) > 0) {
            while ($fetch_products = mysqli_fetch_assoc($select_products)) {
        ?>
        <div class="box product-card">
            <div class="image-wrapper">
                <div class="price"><?php echo number_format((float)$fetch_products['price'], 0, ',', '.'); ?> đ</div>
                <img class="image" src="../uploaded_img/<?php echo htmlspecialchars($fetch_products['image']); ?>" alt="<?php echo htmlspecialchars($fetch_products['name']); ?>">
            </div>

            <div class="product-body">
                <div class="category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($fetch_products['category'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="name" title="<?php echo htmlspecialchars($fetch_products['name']); ?>"><?php echo htmlspecialchars($fetch_products['name']); ?></div>
                <div class="details"><?php echo htmlspecialchars($fetch_products['details']); ?></div>
            </div>

            <div class="card-actions">
                <a href="admin_update_product.php?update=<?php echo $fetch_products['id']; ?>" class="option-btn">
                    <i class="fas fa-edit"></i> Sửa
                </a>
                <a href="admin_products.php?delete=<?php echo $fetch_products['id']; ?>" class="delete-btn" onclick="return confirm('Xác nhận xóa sản phẩm này?');">
                    <i class="fas fa-trash-alt"></i> Xóa
                </a>
            </div>
        </div>
        <?php
            }
        } else {
            echo '<p class="empty"><i class="fas fa-box-open"></i> Chưa có sản phẩm nào trong kho!</p>';
        }
        ?>
    </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>
