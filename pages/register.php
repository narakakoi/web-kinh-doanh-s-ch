<?php
require_once '../config/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}

session_start();

if(isset($_POST['submit'])){
   $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
   $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
   $pass = is_string($_POST['pass'] ?? null) ? $_POST['pass'] : '';
   $cpass = is_string($_POST['cpass'] ?? null) ? $_POST['cpass'] : '';
   $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
   mysqli_stmt_bind_param($stmt, 's', $email);
   mysqli_stmt_execute($stmt);
   $existingUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
   mysqli_stmt_close($stmt);
   if ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || $pass === '' || strlen($pass) > 72 || str_contains($pass, "\0")) {
      $message[] = 'Vui lòng nhập tên, email hợp lệ và mật khẩu từ 1 đến 72 byte.';
   } elseif ($existingUser) {
      $message[] = 'Người dùng đã tồn tại!';
   } elseif ($pass !== $cpass) {
      $message[] = 'Mật khẩu xác nhận không khớp!';
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
   <!-- liên kết file css tùy chỉnh -->
   <link rel="stylesheet" href="../css/style.css">
   <link rel="stylesheet" href="../css/components/header.css">
   <link rel="stylesheet" href="../css/pages/auth.css">
</head>
<body>
<?php @include '../components/header.php'; ?>

<?php
if(isset($message)){
   foreach($message as $message){
      echo '
      <div class="message">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>
   
<section class="form-container">
   <form action="" method="post">
      <h3>Đăng ký</h3>
      <input type="text" name="name" class="box" placeholder="Nhập tên người dùng" required>
      <input type="email" name="email" class="box" placeholder="Nhập email của bạn" required>
      <input type="password" name="pass" class="box" placeholder="Nhập mật khẩu" required>
      <input type="password" name="cpass" class="box" placeholder="Xác nhận mật khẩu" required>
      <input type="submit" class="btn" name="submit" value="Đăng ký ngay">
      <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
   </form>
</section>
<script src="../js/script.js"></script>
</body>
</html>
