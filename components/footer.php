<?php
require_once '../config/config.php';
?>

<section class="footer">
    <div class="box-container">
        <!-- Phần liên kết nhanh -->
        <div class="box">
            <h3>Liên kết nhanh</h3>
            <a href="<?php echo getBaseUrl(); ?>index.php">Trang chủ</a>
            <a href="<?php echo getBaseUrl(); ?>pages/about.php">Giới thiệu</a>
            <a href="<?php echo getBaseUrl(); ?>pages/contact.php">Liên hệ</a>
            <a href="<?php echo getBaseUrl(); ?>pages/shop.php">Cửa hàng</a>
        </div>
        <!-- Phần liên kết bổ sung -->
        <div class="box">
            <h3>Liên kết bổ sung</h3>
            <a href="<?php echo getBaseUrl(); ?>pages/login.php">Đăng nhập</a>
            <a href="<?php echo getBaseUrl(); ?>pages/register.php">Đăng ký</a>
            <a href="<?php echo getBaseUrl(); ?>pages/orders.php">Đơn hàng của tôi</a>
            <a href="<?php echo getBaseUrl(); ?>pages/cart.php">Giỏ hàng của tôi</a>
        </div>
        <!-- Theo dõi chúng tôi -->
        <div class="box">
            <h3>Theo dõi chúng tôi</h3>
            <a href="#"><i class="fab fa-facebook-f"></i>Facebook</a>
            <a href="#"><i class="fab fa-twitter"></i>Twitter</a>
            <a href="#"><i class="fab fa-github"></i>Github</a>
            <a href="#"><i class="fab fa-instagram"></i>Instagram</a>
        </div>
    </div>
</section>
