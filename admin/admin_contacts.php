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
    mysqli_query($conn, "DELETE FROM `message` WHERE id = '$delete_id'") or die('Lỗi truy vấn');
    header('location:admin_contacts.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Tin Nhắn - BookStory Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<section class="messages">
    <h1 class="title">Hộp Thư Phản Hồi Khách Hàng</h1>
    <div class="box-container">
        <?php
        $select_message = mysqli_query($conn, "SELECT * FROM `message` ORDER BY id DESC") or die('Lỗi truy vấn');
        if (mysqli_num_rows($select_message) > 0) {
            while ($fetch_message = mysqli_fetch_assoc($select_message)) {
        ?>
        <div class="box message-card">
            <div class="msg-header">
                <div class="sender-info">
                    <div class="sender-avatar"><i class="fas fa-comment-dots"></i></div>
                    <div>
                        <h4><?php echo htmlspecialchars($fetch_message['name']); ?></h4>
                        <span class="sender-id">ID khách: #<?php echo htmlspecialchars($fetch_message['user_id'] ?: 'Khách vãng lai'); ?></span>
                    </div>
                </div>
            </div>

            <div class="msg-contact">
                <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($fetch_message['number']); ?></p>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($fetch_message['email']); ?></p>
            </div>

            <div class="msg-content">
                <span class="msg-label">Nội dung lời nhắn:</span>
                <p><?php echo nl2br(htmlspecialchars($fetch_message['message'])); ?></p>
            </div>

            <a href="admin_contacts.php?delete=<?php echo $fetch_message['id']; ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa tin nhắn này?');" class="delete-btn full-btn">
                <i class="fas fa-trash-alt"></i> Xóa Tin Nhắn
            </a>
        </div>
        <?php
            }
        } else {
            echo '<p class="empty"><i class="fas fa-inbox"></i> Hộp thư hiện đang trống!</p>';
        }
        ?>
    </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>
