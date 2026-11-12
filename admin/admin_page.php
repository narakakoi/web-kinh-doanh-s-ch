<?php
@include '../config/config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:../pages/login.php');
   exit();
};

// Tính toán an toàn các chỉ số thống kê
$total_pendings = 0;
try {
   $select_pendings = mysqli_query($conn, "SELECT * FROM `orders` WHERE payment_status = 'pending'");
   if ($select_pendings) {
      while($fetch_pendings = mysqli_fetch_assoc($select_pendings)){
         $total_pendings += (float)($fetch_pendings['total_price'] ?? 0);
      }
   }
} catch (Throwable $e) {}

$total_completes = 0;
try {
   $select_completes = mysqli_query($conn, "SELECT * FROM `orders` WHERE payment_status = 'completed'");
   if ($select_completes) {
      while($fetch_completes = mysqli_fetch_assoc($select_completes)){
         $total_completes += (float)($fetch_completes['total_price'] ?? 0);
      }
   }
} catch (Throwable $e) {}

$number_of_orders = 0;
try {
   $select_orders = mysqli_query($conn, "SELECT * FROM `orders`");
   if ($select_orders) $number_of_orders = mysqli_num_rows($select_orders);
} catch (Throwable $e) {}

$number_of_products = 0;
try {
   $select_products = mysqli_query($conn, "SELECT * FROM `products`");
   if ($select_products) $number_of_products = mysqli_num_rows($select_products);
} catch (Throwable $e) {}

$number_of_users = 0;
try {
   $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE user_type = 'user'");
   if ($select_users) $number_of_users = mysqli_num_rows($select_users);
} catch (Throwable $e) {}

$number_of_admin = 0;
try {
   $select_admin = mysqli_query($conn, "SELECT * FROM `users` WHERE user_type = 'admin'");
   if ($select_admin) $number_of_admin = mysqli_num_rows($select_admin);
} catch (Throwable $e) {}

$number_of_account = 0;
try {
   $select_account = mysqli_query($conn, "SELECT * FROM `users`");
   if ($select_account) $number_of_account = mysqli_num_rows($select_account);
} catch (Throwable $e) {}

$number_of_messages = 0;
try {
   $select_messages = mysqli_query($conn, "SELECT * FROM `message`");
   if ($select_messages) $number_of_messages = mysqli_num_rows($select_messages);
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Bảng Điều Khiển - Quản Trị BookStory</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<section class="dashboard">
   <div class="dashboard-banner">
      <div class="banner-text">
         <h1 class="title">Tổng Quan Kinh Doanh</h1>
         <p class="subtitle">Xin chào <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></strong>, dưới đây là số liệu vận hành hệ thống hôm nay.</p>
      </div>
      <div class="banner-actions">
         <a href="admin_products.php" class="btn"><i class="fas fa-plus-circle"></i> Thêm sách mới</a>
         <a href="admin_orders.php" class="option-btn"><i class="fas fa-clipboard-list"></i> Duyệt đơn hàng</a>
      </div>
   </div>

   <div class="box-container">
      <div class="box stat-card card-warning">
         <div class="stat-header">
            <span class="stat-label">Doanh thu chờ duyệt</span>
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
         </div>
         <h3><?php echo number_format($total_pendings, 0, ',', '.'); ?> <small>VNĐ</small></h3>
         <p><a href="admin_orders.php">Tổng thanh toán đang chờ <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-success">
         <div class="stat-header">
            <span class="stat-label">Doanh thu thực nhận</span>
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
         </div>
         <h3><?php echo number_format($total_completes, 0, ',', '.'); ?> <small>VNĐ</small></h3>
         <p><a href="admin_orders.php">Thanh toán hoàn thành <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-primary">
         <div class="stat-header">
            <span class="stat-label">Tổng số đơn hàng</span>
            <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
         </div>
         <h3><?php echo $number_of_orders; ?> <small>đơn</small></h3>
         <p><a href="admin_orders.php">Đơn hàng đã đặt <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-brown">
         <div class="stat-header">
            <span class="stat-label">Kho sách hiện có</span>
            <div class="stat-icon"><i class="fas fa-book"></i></div>
         </div>
         <h3><?php echo $number_of_products; ?> <small>sản phẩm</small></h3>
         <p><a href="admin_products.php">Sản phẩm đã thêm <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-info">
         <div class="stat-header">
            <span class="stat-label">Khách hàng thành viên</span>
            <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
         </div>
         <h3><?php echo $number_of_users; ?> <small>khách</small></h3>
         <p><a href="admin_users.php">Người dùng <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-purple">
         <div class="stat-header">
            <span class="stat-label">Nhân sự vận hành</span>
            <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
         </div>
         <h3><?php echo $number_of_admin; ?> <small>admin</small></h3>
         <p><a href="admin_users.php">Người dùng quản trị <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-teal">
         <div class="stat-header">
            <span class="stat-label">Toàn bộ hệ thống</span>
            <div class="stat-icon"><i class="fas fa-id-card"></i></div>
         </div>
         <h3><?php echo $number_of_account; ?> <small>tài khoản</small></h3>
         <p><a href="admin_users.php">Tổng tài khoản <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-danger">
         <div class="stat-header">
            <span class="stat-label">Phản hồi khách hàng</span>
            <div class="stat-icon"><i class="fas fa-comments"></i></div>
         </div>
         <h3><?php echo $number_of_messages; ?> <small>tin nhắn</small></h3>
         <p><a href="admin_contacts.php">Tin nhắn mới <i class="fas fa-arrow-right"></i></a></p>
      </div>
   </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>
