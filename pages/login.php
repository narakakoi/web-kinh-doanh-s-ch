<?php
require_once __DIR__ . '/../config/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}

session_start();

if (isset($_POST['submit'])) {
   $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
   $password = is_string($_POST['pass'] ?? null) ? $_POST['pass'] : '';
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
         $message[] = 'Không tìm thấy người dùng!';
      }
   } else {
      $message[] = 'Email hoặc mật khẩu không đúng!';
   }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Đăng Nhập</title>
   <!-- liên kết font awesome cdn -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <!-- liên kết file css tùy chỉnh -->
   <link rel="stylesheet" href="../css/style.css">
   <link rel="stylesheet" href="../css/components/header.css">
   <link rel="stylesheet" href="../css/pages/auth.css">
</head>
<body>
<?php @include '../components/header.php'; ?>

<?php
if (isset($message)) {
   foreach ($message as $msg) {
      echo '
      <div class="message">
         <span>' . $msg . '</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>
   
<section class="form-container">
   <form action="" method="post">
      <h3>ĐĂNG NHẬP</h3>
      <input type="email" name="email" class="box" placeholder="Nhập email của bạn" required>
      <input type="password" name="pass" class="box" placeholder="Nhập mật khẩu của bạn" required>
      <input type="submit" class="btn" name="submit" value="Đăng nhập">
      <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
   </form>
</section>
<script src="../js/script.js"></script>
</body>
</html>
