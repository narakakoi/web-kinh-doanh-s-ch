<?php
require_once __DIR__ . '/../config/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}

session_start();

$auth_error = '';
$email = '';

if (isset($_POST['submit'])) {
   $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
   $password = is_string($_POST['pass'] ?? null) ? $_POST['pass'] : '';
   $user_captcha = trim(is_string($_POST['captcha'] ?? null) ? $_POST['captcha'] : '');
   $server_captcha = $_SESSION['captcha_code'] ?? '';
   unset($_SESSION['captcha_code']);

   if ($user_captcha === '' || $user_captcha !== $server_captcha) {
      $auth_error = 'Mã xác nhận CAPTCHA không chính xác (lưu ý phân biệt chữ hoa, chữ thường)!';
   } else {
      $stmt = mysqli_prepare($conn, 'SELECT * FROM users WHERE email = ? LIMIT 1');
      mysqli_stmt_bind_param($stmt, 's', $email);
      mysqli_stmt_execute($stmt);
      $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
      mysqli_stmt_close($stmt);
      require_once __DIR__ . '/../config/passwords.php';
      if ($row && verify_account_password($password, $row['password'])) {
         if (strlen($password) <= 72 && !str_contains($password, "\0") && password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ? AND password = ?');
            mysqli_stmt_bind_param($stmt, 'sis', $hash, $row['id'], $row['password']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
         }
         session_regenerate_id(true);
         $_SESSION = [];
         if ($row['user_type'] == 'admin') {
            $_SESSION['admin_name'] = $row['name'];
            $_SESSION['admin_email'] = $row['email'];
            $_SESSION['admin_id'] = $row['id'];
            header('location:../admin/admin_page.php');
            exit();
         } elseif ($row['user_type'] == 'user') {
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_id'] = $row['id'];
            header('location:../index.php');
            exit();
         } else {
            $auth_error = 'Tài khoản chưa được phân quyền hợp lệ!';
         }
      } else {
         $auth_error = 'Email hoặc mật khẩu không đúng! Vui lòng kiểm tra lại.';
      }
   }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/svg+xml" href="../images/favicon.svg">
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Đăng Nhập</title>
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
      <h3>ĐĂNG NHẬP</h3>

      <?php if ($auth_error !== ''): ?>
         <div style="background: #fdecea; color: #b02a37; border: 1px solid #f5c2c7; padding: 12px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 14px; display: flex; align-items: center; gap: 8px; text-align: left;">
            <i class="fas fa-exclamation-circle" style="font-size: 16px; flex-shrink: 0;"></i>
            <span><?php echo htmlspecialchars($auth_error); ?></span>
         </div>
      <?php endif; ?>

      <input type="email" name="email" class="box" placeholder="Nhập email của bạn" value="<?php echo htmlspecialchars($email); ?>" required>
      <input type="password" name="pass" class="box" placeholder="Nhập mật khẩu của bạn" required>
      <div style="display: flex; align-items: center; gap: 8px; margin: 1rem 0;">
         <input type="text" name="captcha" class="box" placeholder="Nhập mã (hoa, thường & số)" maxlength="5" autocomplete="off" required
                style="margin: 0; flex: 1; <?php echo str_contains($auth_error, 'CAPTCHA') ? 'border: 1.5px solid #dc3545;' : ''; ?>">
         <img src="../config/captcha.php" id="captchaImg" alt="CAPTCHA" title="Bấm để đổi mã khác" style="height: 46px; border-radius: 6px; cursor: pointer; border: 1px solid #d6c7b8;" onclick="this.src='../config/captcha.php?' + Date.now();">
         <button type="button" title="Đổi mã mới" onclick="document.getElementById('captchaImg').src='../config/captcha.php?' + Date.now();" style="height: 46px; padding: 0 12px; border: 1px solid #d6c7b8; background: #fff; border-radius: 6px; cursor: pointer; font-size: 16px;">
            <i class="fas fa-sync-alt"></i>
         </button>
      </div>
      <input type="submit" class="btn" name="submit" value="Đăng nhập">
      <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
   </form>
</section>
<script src="../js/script.js"></script>
</body>
</html>
