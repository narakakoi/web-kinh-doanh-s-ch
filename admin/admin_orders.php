<?php
@include '../config/config.php'; 

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../pages/login.php');
    exit();
}

if (isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $update_payment = $_POST['update_payment'] ?? 'pending';
    mysqli_query($conn, "UPDATE `orders` SET payment_status = '$update_payment' WHERE id = '$order_id'") or die('Cập nhật trạng thái thanh toán thất bại');
    $message[] = 'Trạng thái thanh toán đã được cập nhật!';
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM `orders` WHERE id = '$delete_id'") or die('Xóa đơn hàng thất bại');
    header('location:admin_orders.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng - BookStory Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<section class="placed-orders">
    <h1 class="title">Quản Lý Đơn Hàng Đã Đặt</h1>
    <div class="box-container">
        <?php
        $select_orders = mysqli_query($conn, "SELECT * FROM `orders` ORDER BY id DESC") or die('Truy vấn thất bại');
        if (mysqli_num_rows($select_orders) > 0) {
            while ($fetch_orders = mysqli_fetch_assoc($select_orders)) {
                $status = $fetch_orders['payment_status'] ?? 'pending';
                $is_completed = ($status === 'completed');
        ?>
        <div class="box order-card <?php echo $is_completed ? 'order-completed' : 'order-pending'; ?>">
            <div class="order-header">
                <div class="order-id">
                    <i class="fas fa-receipt"></i> Đơn hàng #<?php echo htmlspecialchars($fetch_orders['id'] ?? '0'); ?>
                </div>
                <span class="status-badge <?php echo $is_completed ? 'badge-success' : 'badge-warning'; ?>">
                    <i class="fas <?php echo $is_completed ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                    <?php echo $is_completed ? 'Hoàn thành' : 'Đang chờ xử lý'; ?>
                </span>
            </div>

            <div class="order-info-list">
                <p><span class="label"><i class="fas fa-id-badge"></i> ID khách hàng:</span> <span>#<?php echo htmlspecialchars($fetch_orders['user_id'] ?: 'Khách'); ?></span></p>
                <p><span class="label"><i class="fas fa-calendar-alt"></i> Ngày đặt:</span> <span><?php echo htmlspecialchars($fetch_orders['placed_on'] ?: 'Chưa cập nhật'); ?></span></p>
                <p><span class="label"><i class="fas fa-user"></i> Người nhận:</span> <span><?php echo htmlspecialchars($fetch_orders['name'] ?: 'Chưa cập nhật'); ?></span></p>
                <p><span class="label"><i class="fas fa-phone-alt"></i> Điện thoại:</span> <span><?php echo htmlspecialchars($fetch_orders['number'] ?: 'Chưa cập nhật'); ?></span></p>
                <p><span class="label"><i class="fas fa-envelope"></i> Email:</span> <span><?php echo htmlspecialchars($fetch_orders['email'] ?: 'Chưa cập nhật'); ?></span></p>
                <p><span class="label"><i class="fas fa-map-marker-alt"></i> Địa chỉ:</span> <span><?php echo htmlspecialchars($fetch_orders['address'] ?: 'Chưa cập nhật'); ?></span></p>
                <p><span class="label"><i class="fas fa-box"></i> Sản phẩm:</span> <span><?php echo htmlspecialchars($fetch_orders['total_products'] ?: 'Không có mô tả'); ?></span></p>
                <p><span class="label"><i class="fas fa-credit-card"></i> Thanh toán:</span> <span><?php echo htmlspecialchars($fetch_orders['method'] ?: 'COD'); ?></span></p>
                <p class="total-row"><span class="label"><i class="fas fa-coins"></i> Tổng thanh toán:</span> <span class="total-price"><?php echo number_format((float)($fetch_orders['total_price'] ?? 0), 0, ',', '.'); ?> VNĐ</span></p>
            </div>

            <form action="" method="post" class="order-form">
                <input type="hidden" name="order_id" value="<?php echo $fetch_orders['id']; ?>">
                <label class="select-label">Cập nhật trạng thái thanh toán:</label>
                <select name="update_payment">
                    <option value="pending" <?php echo (!$is_completed) ? 'selected' : ''; ?>>Đang chờ (Pending)</option>
                    <option value="completed" <?php echo ($is_completed) ? 'selected' : ''; ?>>Hoàn thành (Completed)</option>
                </select>
                <div class="card-actions">
                    <button type="submit" name="update_order" class="option-btn">
                        <i class="fas fa-sync-alt"></i> Cập Nhật
                    </button>
                    <a href="admin_orders.php?delete=<?php echo $fetch_orders['id']; ?>" class="delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?');">
                        <i class="fas fa-trash-alt"></i> Xóa
                    </a>
                </div>
            </form>
        </div>
        <?php
            }
        } else {
            echo '<p class="empty"><i class="fas fa-clipboard-check"></i> Chưa có đơn hàng nào được đặt!</p>';
        }
        ?>
    </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>
