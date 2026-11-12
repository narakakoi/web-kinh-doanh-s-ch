<?php
if (isset($message)) {
    foreach ($message as $msg) {
        echo '
        <div class="message">
            <span><i class="fas fa-bell" style="color: var(--brown); margin-right: 1rem; font-size: 2rem;"></i>' . htmlspecialchars($msg) . '</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
        </div>
        ';
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="header">
    <div class="flex">
        <a href="admin_page.php" class="logo">
            <i class="fas fa-book-open"></i> BookStory<span>Admin</span>
        </a>
        <nav class="navbar">
            <a href="admin_page.php" class="<?php echo ($current_page == 'admin_page.php') ? 'active-link' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Trang Chủ
            </a>
            <a href="admin_products.php" class="<?php echo ($current_page == 'admin_products.php' || $current_page == 'admin_update_product.php') ? 'active-link' : ''; ?>">
                <i class="fas fa-book"></i> Sản Phẩm
            </a>
            <a href="admin_orders.php" class="<?php echo ($current_page == 'admin_orders.php') ? 'active-link' : ''; ?>">
                <i class="fas fa-shopping-bag"></i> Đơn Hàng
            </a>
            <a href="admin_coupons.php" class="<?php echo ($current_page == 'admin_coupons.php') ? 'active-link' : ''; ?>">
                <i class="fas fa-ticket-alt"></i> Mã giảm giá
            </a>
            <a href="admin_users.php" class="<?php echo ($current_page == 'admin_users.php') ? 'active-link' : ''; ?>">
                <i class="fas fa-users"></i> Người Dùng
            </a>
            <a href="admin_contacts.php" class="<?php echo ($current_page == 'admin_contacts.php') ? 'active-link' : ''; ?>">
                <i class="fas fa-envelope"></i> Tin Nhắn
            </a>
        </nav>
        <div class="icons">
            <div id="menu-btn" class="fas fa-bars"></div>
            <div id="user-btn" class="fas fa-user-shield" title="Tài khoản Quản trị"></div>
        </div>
        <div class="account-box">
            <div class="admin-avatar">
                <i class="fas fa-user-tie"></i>
            </div>
            <p>Quản trị viên: <span><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span></p>
            <p>Email: <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span></p>
            <a href="../pages/logout.php" class="delete-btn"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a>
            <div class="account-links">
                <a href="../pages/login.php">Đăng Nhập</a> | <a href="../pages/register.php">Đăng Ký</a>
            </div>
        </div>
    </div>
</header>
