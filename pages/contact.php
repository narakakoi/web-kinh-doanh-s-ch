<?php
require_once '../config/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}

session_start();
$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('location:../pages/login.php');
    exit();
}

// Giữ nguyên các tên field và cách lưu bảng message của nhóm.
if (isset($_POST['send'])) {
    $name = mysqli_real_escape_string($conn, trim((string) ($_POST['name'] ?? '')));
    $email = mysqli_real_escape_string($conn, trim((string) ($_POST['email'] ?? '')));
    $number = mysqli_real_escape_string($conn, trim((string) ($_POST['number'] ?? '')));
    $msg = mysqli_real_escape_string($conn, trim((string) ($_POST['message'] ?? '')));
    $select_message = mysqli_query($conn, "SELECT * FROM `message` WHERE name = '$name' AND email = '$email' AND number = '$number' AND message = '$msg'") or die('query failed');

    if (mysqli_num_rows($select_message) > 0) {
        $message[] = 'Tin nhắn đã được gửi!';
    } else {
        mysqli_query($conn, "INSERT INTO `message`(user_id, name, email, number, message) VALUES('$user_id', '$name', '$email', '$number', '$msg')") or die('query failed');
        $message[] = 'Tin nhắn gửi thành công!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Liên hệ | BookStory</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="../css/style.css">
   <link rel="stylesheet" href="../css/components/header.css">
   <link rel="stylesheet" href="../css/components/footer.css">
   <!-- CSS trang liên hệ: tách riêng để giữ nguyên giao diện các trang khác. -->
   <link rel="stylesheet" href="../css/pages/contact.css">
</head>
<body>
<?php @include '../components/header.php'; ?>
<main class="bs-contact-page">
   <!-- 01. Phần giới thiệu: typography đơn giản, không dùng icon trang trí. -->
   <section class="bs-contact-hero" aria-labelledby="bs-contact-title">
      <div class="bs-contact-wrap">
         <nav class="bs-contact-crumb" aria-label="Đường dẫn"><a href="../index.php">Trang chủ</a><span>/</span><span>Liên hệ</span></nav>
         <span class="bs-contact-eyebrow">BOOKSTORY · GÓC TRÒ CHUYỆN</span>
         <h1 id="bs-contact-title">Có điều gì muốn <em>nhắn với tụi mình?</em></h1>
         <p>Bạn đang tìm một cuốn sách hay, cần hỏi về đơn hàng hay đơn giản muốn góp ý? BookStory luôn muốn nghe từ bạn.</p>
      </div>
   </section>

   <!-- 02. Hai cột vừa đủ: lời giới thiệu bên trái, form liên hệ bên phải. -->
   <section class="bs-contact-body" aria-label="Biểu mẫu liên hệ">
      <div class="bs-contact-wrap bs-contact-layout">
         <aside class="bs-contact-aside">
            <span class="bs-contact-kicker">Một chút về lời nhắn</span>
            <h2>Chuyện sách vở,<br>cứ kể tụi mình nghe.</h2>
            <p>Nếu có thắc mắc về sách hoặc đơn hàng, bạn để lại thông tin và nội dung cần hỗ trợ trong biểu mẫu bên cạnh nhé.</p>
            <div class="bs-contact-note">
               <strong>Gợi ý nhỏ</strong>
               <p>Nếu hỏi về đơn hàng, bạn có thể ghi kèm mã đơn để việc kiểm tra được thuận tiện hơn.</p>
            </div>
         </aside>
         <div class="bs-contact-form-card">
            <div class="bs-contact-form-heading">
               <h2>Gửi lời nhắn</h2>
               <p>Điền vài thông tin cơ bản để BookStory có thể liên hệ lại với bạn.</p>
            </div>
            <?php if (!empty($message)): ?>
               <?php foreach ($message as $feedback): ?><p class="bs-contact-notice" role="status"><?= htmlspecialchars((string) $feedback, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>
            <?php endif; ?>
            <!-- Giữ nguyên tên field name/email/number/message và name=send để PHP xử lý như cũ. -->
            <form action="" method="POST" class="bs-contact-form">
               <div class="bs-contact-field"><label for="bs-contact-name">Họ và tên <span>*</span></label><input id="bs-contact-name" type="text" name="name" placeholder="Tên của bạn" autocomplete="name" maxlength="100" required></div>
               <div class="bs-contact-row">
                  <div class="bs-contact-field"><label for="bs-contact-email">Email <span>*</span></label><input id="bs-contact-email" type="email" name="email" placeholder="email@example.com" autocomplete="email" maxlength="255" required></div>
                  <div class="bs-contact-field"><label for="bs-contact-phone">Số điện thoại <span>*</span></label><input id="bs-contact-phone" type="tel" name="number" placeholder="Số điện thoại liên hệ" autocomplete="tel" maxlength="30" required></div>
               </div>
               <div class="bs-contact-field"><label for="bs-contact-message">Nội dung tin nhắn <span>*</span></label><textarea id="bs-contact-message" name="message" placeholder="Bạn cần BookStory hỗ trợ điều gì?" rows="6" required></textarea></div>
               <button type="submit" name="send" value="1" class="bs-contact-submit">Gửi tin nhắn</button>
               <p class="bs-contact-privacy">Lưu ý: Không gửi mật khẩu hoặc thông tin thanh toán qua biểu mẫu.</p>
            </form>
         </div>
      </div>
   </section>
</main>
<?php @include '../components/footer.php'; ?>
<script src="../js/script.js"></script>
</body>
</html>
