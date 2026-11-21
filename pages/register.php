<?php
require_once '../config/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}

session_start();

$auth_error = '';
$name = '';
$email = '';

if(isset($_POST['submit'])){
   $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
   $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
   $pass = is_string($_POST['pass'] ?? null) ? $_POST['pass'] : '';
   $cpass = is_string($_POST['cpass'] ?? null) ? $_POST['cpass'] : '';
   $user_captcha = trim(is_string($_POST['captcha'] ?? null) ? $_POST['captcha'] : '');
   $server_captcha = $_SESSION['captcha_code'] ?? '';
   unset($_SESSION['captcha_code']);

   if ($user_captcha === '' || $user_captcha !== $server_captcha) {
      $auth_error = 'Mã xác nhận CAPTCHA không chính xác (lưu ý phân biệt chữ hoa, chữ thường)!';
   } else {
      $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
      mysqli_stmt_bind_param($stmt, 's', $email);
      mysqli_stmt_execute($stmt);
      $existingUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
      mysqli_stmt_close($stmt);
      if ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || $pass === '' || strlen($pass) > 72 || str_contains($pass, "\0")) {
         $auth_error = 'Vui lòng nhập đầy đủ tên, email hợp lệ và mật khẩu từ 1 đến 72 ký tự.';
      } elseif ($existingUser) {
         $auth_error = 'Email này đã được đăng ký! Vui lòng dùng email khác.';
      } elseif ($pass !== $cpass) {
         $auth_error = 'Mật khẩu xác nhận không khớp! Vui lòng nhập lại.';
      } else {
         $hash = password_hash($pass, PASSWORD_DEFAULT);
         $stmt = mysqli_prepare($conn, 'INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
         mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $hash);
         mysqli_stmt_execute($stmt);
         mysqli_stmt_close($stmt);
         header('location:login.php');
         exit();
      }
   }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Đăng ký</title>
   <!-- liên kết font awesome cdn -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <!-- liên kết tệp css tùy chỉnh -->
   <link rel="stylesheet" href="../css/style.css">
   <link rel="stylesheet" href="../css/components/header.css">
   <link rel="stylesheet" href="../css/pages/auth.css">
</head>
<body>
<?php @include '../components/header.php'; ?>
   
<section class="form-container">
   <form action="" method="post">
      <h3>Đăng ký</h3>

      <?php if ($auth_error !== ''): ?>
         <div style="background: #fdecea; color: #b02a37; border: 1px solid #f5c2c7; padding: 12px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 14px; display: flex; align-items: center; gap: 8px; text-align: left;">
            <i class="fas fa-exclamation-circle" style="font-size: 16px; flex-shrink: 0;"></i>
            <span><?php echo htmlspecialchars($auth_error); ?></span>
         </div>
      <?php endif; ?>

      <input type="text" name="name" class="box" placeholder="Nhập tên người dùng" value="<?php echo htmlspecialchars($name); ?>" required>
      <input type="email" name="email" class="box" placeholder="Nhập email của bạn" value="<?php echo htmlspecialchars($email); ?>" required>
      <input type="password" name="pass" class="box" placeholder="Nhập mật khẩu" required>
      <input type="password" name="cpass" class="box" placeholder="Xác nhận mật khẩu" required>
      <div style="display: flex; align-items: center; gap: 8px; margin: 1rem 0;">
         <input type="text" name="captcha" class="box" placeholder="Nhập mã (hoa, thường & số)" maxlength="5" autocomplete="off" required
                style="margin: 0; flex: 1; <?php echo str_contains($auth_error, 'CAPTCHA') ? 'border: 1.5px solid #dc3545;' : ''; ?>">
         <img src="../config/captcha.php" id="captchaImg" alt="CAPTCHA" title="Bấm để đổi mã khác" style="height: 46px; border-radius: 6px; cursor: pointer; border: 1px solid #d6c7b8;" onclick="this.src='../config/captcha.php?' + Date.now();">
         <button type="button" title="Đổi mã mới" onclick="document.getElementById('captchaImg').src='../config/captcha.php?' + Date.now();" style="height: 46px; padding: 0 12px; border: 1px solid #d6c7b8; background: #fff; border-radius: 6px; cursor: pointer; font-size: 16px;">
            <i class="fas fa-sync-alt"></i>
         </button>
      </div>
      <input type="submit" class="btn" name="submit" value="Đăng ký ngay">
      <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
   </form>
</section>
<script src="../js/script.js"></script>
</body>
</html>
