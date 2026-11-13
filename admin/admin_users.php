<?php
@include '../config/config.php'; 

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../pages/login.php');
    exit();
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM `users` WHERE id = '$delete_id'") or die('Xóa người dùng thất bại');
    header('location:admin_users.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Người Dùng - BookStory Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<section class="users">
    <h1 class="title">Quản Lý Tài Khoản Hệ Thống</h1>
    <div class="box-container">
        <?php
        $select_users = mysqli_query($conn, "SELECT * FROM `users` ORDER BY id DESC") or die('Truy vấn thất bại');
        if (mysqli_num_rows($select_users) > 0) {
            while ($fetch_users = mysqli_fetch_assoc($select_users)) {
                $is_admin = ($fetch_users['user_type'] == 'admin');
        ?>
        <div class="box user-card <?php echo $is_admin ? 'user-admin' : ''; ?>">
            <div class="user-avatar">
                <i class="fas <?php echo $is_admin ? 'fa-user-shield' : 'fa-user'; ?>"></i>
            </div>

            <span class="role-badge <?php echo $is_admin ? 'role-admin' : 'role-user'; ?>">
                <?php echo $is_admin ? 'Quản Trị Viên' : 'Khách Hàng'; ?>
            </span>

            <h3 class="user-name"><?php echo htmlspecialchars($fetch_users['name']); ?></h3>
            <p class="user-id">ID tài khoản: <span>#<?php echo $fetch_users['id']; ?></span></p>
            <p class="user-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($fetch_users['email']); ?></p>

            <?php if ($fetch_users['id'] != $admin_id): ?>
                <a href="admin_users.php?delete=<?php echo $fetch_users['id']; ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');" class="delete-btn full-btn">
                    <i class="fas fa-user-times"></i> Xóa Tài Khoản
                </a>
            <?php else: ?>
                <div class="current-session-tag"><i class="fas fa-check-circle"></i> Đang đăng nhập</div>
            <?php endif; ?>
        </div>
        <?php
            }
        } else {
            echo '<p class="empty"><i class="fas fa-users"></i> Không có người dùng nào!</p>';
        }
        ?>
    </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>
