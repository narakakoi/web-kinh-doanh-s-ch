<?php
require_once '../config/config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

if ($user_id === null) {
    header('location:../pages/login.php');
    exit();
}

$sepayBank = trim((string) getenv('SEPAY_BANK_CODE'));
$sepayAccount = trim((string) getenv('SEPAY_ACCOUNT_NUMBER'));
$sepayWebhookKey = trim((string) getenv('SEPAY_WEBHOOK_API_KEY'));
$sepayConfigured = $sepayBank !== '' && $sepayAccount !== '' && $sepayWebhookKey !== '';
$message = [];
$checkoutOrder = null;
$_SESSION['checkout_csrf_token'] ??= bin2hex(random_bytes(32));

if (isset($_POST['order'])) {
    $name = trim($_POST['name'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $method = $_POST['method'] ?? '';
    $flat = trim($_POST['flat'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $pinCode = trim($_POST['pin_code'] ?? '');

    $allowedMethods = ['cash on delivery'];
    if ($sepayConfigured) {
        $allowedMethods[] = 'sepay_qr';
    }

    if (!hash_equals($_SESSION['checkout_csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(400);
        $message[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } elseif (!in_array($method, $allowedMethods, true)) {
        $message[] = 'Phương thức thanh toán không hợp lệ hoặc chưa được cấu hình.';
    } elseif ($name === '' || $number === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $flat === '' || $street === '' || $city === '' || $country === '' || $pinCode === ''
        || trim($_POST['state'] ?? '') === '') {
        $message[] = 'Vui lòng nhập đầy đủ thông tin giao hàng hợp lệ.';
    } else {
        $state = trim($_POST['state']);
        $address = 'số nhà ' . $flat . ', ' . $street . ', ' . $city . ', ' . $state . ', ' . $country . ' - ' . $pinCode;
        $placed_on = date('d-M-Y');

        $cart_total = 0.0;
        $cart_products = [];

        $cart_stmt = mysqli_prepare($conn, 'SELECT name, price, quantity FROM `cart` WHERE user_id = ?');
        mysqli_stmt_bind_param($cart_stmt, 'i', $user_id);
        mysqli_stmt_execute($cart_stmt);
        $cart_query = mysqli_stmt_get_result($cart_stmt);
        while ($cart_item = mysqli_fetch_assoc($cart_query)) {
            $cart_products[] = $cart_item['name'] . ' (' . $cart_item['quantity'] . ') ';
            $sub_total = (float) $cart_item['price'] * (int) $cart_item['quantity'];
            $cart_total += $sub_total;
        }
        mysqli_stmt_close($cart_stmt);
        $cart_total = round($cart_total);

        if ($cart_total <= 0 || count($cart_products) === 0) {
            $message[] = 'Giỏ hàng đang trống hoặc tổng tiền không hợp lệ. Hãy cập nhật giá sách trước khi đặt hàng.';
        } else {
            $total_products = implode(', ', $cart_products);

            mysqli_begin_transaction($conn);
            try {
                $order_stmt = mysqli_prepare(
                    $conn,
                    'INSERT INTO `orders` (user_id, name, number, email, method, address, total_products, total_price, placed_on) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                mysqli_stmt_bind_param(
                    $order_stmt,
                    'issssssds',
                    $user_id,
                    $name,
                    $number,
                    $email,
                    $method,
                    $address,
                    $total_products,
                    $cart_total,
                    $placed_on
                );
                mysqli_stmt_execute($order_stmt);
                $order_id = mysqli_insert_id($conn);
                mysqli_stmt_close($order_stmt);

                $delete_cart_stmt = mysqli_prepare($conn, 'DELETE FROM `cart` WHERE user_id = ?');
                mysqli_stmt_bind_param($delete_cart_stmt, 'i', $user_id);
                mysqli_stmt_execute($delete_cart_stmt);
                mysqli_stmt_close($delete_cart_stmt);

                mysqli_commit($conn);
                header('Location: checkout.php?order_id=' . $order_id);
                exit();
            } catch (mysqli_sql_exception $exception) {
                mysqli_rollback($conn);
                error_log('Order creation failed: ' . $exception->getMessage());
                http_response_code(500);
                $message[] = 'Không thể tạo đơn hàng lúc này. Vui lòng thử lại.';
            }
        }
    }
}

$requestedOrderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
if ($requestedOrderId) {
    $order_stmt = mysqli_prepare(
        $conn,
        'SELECT id, total_price, method, payment_status FROM `orders` WHERE id = ? AND user_id = ?'
    );
    mysqli_stmt_bind_param($order_stmt, 'ii', $requestedOrderId, $user_id);
    mysqli_stmt_execute($order_stmt);
    $order_result = mysqli_stmt_get_result($order_stmt);
    $checkoutOrder = mysqli_fetch_assoc($order_result) ?: null;
    mysqli_stmt_close($order_stmt);
}

$qrUrl = '';
if ($checkoutOrder && $checkoutOrder['method'] === 'sepay_qr' && $sepayConfigured) {
    $qrParameters = http_build_query([
        'bank' => $sepayBank,
        'acc' => $sepayAccount,
        'template' => 'compact',
        'amount' => (int) round((float) $checkoutOrder['total_price']),
        'des' => 'DH' . (int) $checkoutOrder['id'],
    ]);
    $qrUrl = 'https://qr.sepay.vn/img?' . $qrParameters;
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Thanh toán</title>
   <!-- Font Awesome CDN Link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <!-- Custom CSS File Link -->
   <link rel="stylesheet" href="../css/style.css">
</head>
<body>
   
<?php @include '../components/header.php'; ?>

<section class="heading">
    <h3>Thanh toán đơn hàng</h3>
    <p> <a href="index.php">Trang chủ</a> / Thanh toán </p>
</section>

<section class="display-order">
    <?php if ($checkoutOrder): ?>
        <h3>Đơn hàng #<?php echo (int) $checkoutOrder['id']; ?></h3>
        <p>Trạng thái thanh toán:
            <strong id="payment-status"><?php echo htmlspecialchars($checkoutOrder['payment_status'], ENT_QUOTES, 'UTF-8'); ?></strong>
        </p>
        <?php if ($checkoutOrder['method'] === 'sepay_qr' && $qrUrl !== ''): ?>
            <p>Quét mã QR và giữ nguyên nội dung chuyển khoản <strong>DH<?php echo (int) $checkoutOrder['id']; ?></strong>.</p>
            <img src="<?php echo htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Mã QR thanh toán SePay" style="display:block;max-width:320px;width:100%;margin:1rem auto;">
            <p>Thanh toán: <strong><?php echo number_format((float) $checkoutOrder['total_price'], 0, ',', '.'); ?> VNĐ</strong></p>
            <p id="payment-confirmation" <?php echo $checkoutOrder['payment_status'] === 'completed' ? '' : 'hidden'; ?>>Đã xác nhận thanh toán. Cảm ơn bạn đã mua hàng!</p>
        <?php elseif ($checkoutOrder['method'] === 'sepay_qr'): ?>
            <p>QR chưa khả dụng vì cấu hình SePay trên máy chủ chưa đầy đủ. Đơn hàng vẫn đang chờ thanh toán.</p>
        <?php else: ?>
            <p>Đơn hàng đã được tạo. Chúng tôi sẽ xử lý theo phương thức thanh toán đã chọn.</p>
        <?php endif; ?>
    <?php else: ?>
    <?php
        $grand_total = 0;
        $cart_display_stmt = mysqli_prepare($conn, 'SELECT * FROM `cart` WHERE user_id = ?');
        mysqli_stmt_bind_param($cart_display_stmt, 'i', $user_id);
        mysqli_stmt_execute($cart_display_stmt);
        $select_cart = mysqli_stmt_get_result($cart_display_stmt);
        if(mysqli_num_rows($select_cart) > 0){
            while($fetch_cart = mysqli_fetch_assoc($select_cart)){
            $total_price = ($fetch_cart['price'] * $fetch_cart['quantity']);
            $grand_total += $total_price;
    ?>    
<p> <?php echo $fetch_cart['name']; ?> <span>[<?php echo number_format($fetch_cart['price'], 0, ',', '.'); ?> VNĐ x <?php echo $fetch_cart['quantity']; ?>]</span> </p>
<?php
        }
        }else{
            echo '<p class="empty">Giỏ hàng của bạn đang trống</p>';
        }
        mysqli_stmt_close($cart_display_stmt);
    ?>
    <div class="grand-total">Tổng cộng: <span><?php echo number_format($grand_total, 0, ',', '.'); ?> VNĐ</span></div>
    <?php endif; ?>
</section>
<?php if (!$checkoutOrder): ?>
<section class="checkout">
    <?php foreach ($message as $item): ?>
        <p class="message"><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endforeach; ?>
    <form action="" method="POST">
        <h3>Đặt hàng</h3>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['checkout_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="flex">
            <div class="inputBox">
                <span>Tên của bạn :</span>
                <input type="text" name="name" placeholder="Nhập tên của bạn" required>
            </div>
            <div class="inputBox">
                <span>Số điện thoại :</span>
                <input type="number" name="number" min="0" placeholder="Nhập số điện thoại của bạn" required>
            </div>
            <div class="inputBox">
                <span>Email của bạn :</span>
                <input type="email" name="email" placeholder="Nhập email của bạn" required>
            </div>
            <div class="inputBox">
                <span>Phương thức thanh toán :</span>
                <select name="method" required>
                    <option value="cash on delivery">Thanh toán khi nhận hàng</option>
                    <?php if ($sepayConfigured): ?>
                        <option value="sepay_qr">Chuyển khoản QR qua SePay</option>
                    <?php endif; ?>
                </select>
                <?php if (!$sepayConfigured): ?>
                    <small>Thanh toán QR SePay chưa khả dụng: máy chủ chưa được cấu hình đầy đủ.</small>
                <?php endif; ?>
            </div>
            <div class="inputBox">
                <span>Địa chỉ dòng 1 :</span>
                <input type="text" name="flat" placeholder="Ví dụ: số nhà" required>
            </div>
            <div class="inputBox">
                <span>Địa chỉ dòng 2 :</span>
                <input type="text" name="street" placeholder="Ví dụ: tên đường" required>
            </div>
            <div class="inputBox">
                <span>Thành phố :</span>
                <input type="text" name="city" placeholder="Ví dụ: TP. HCM" required>
            </div>
            <div class="inputBox">
                <span>Quận/Huyện :</span>
                <input type="text" name="state" placeholder="Ví dụ: Tân Phú" required>
            </div>
            <div class="inputBox">
                <span>Quốc gia :</span>
                <input type="text" name="country" placeholder="Ví dụ: Việt Nam" required>
            </div>
            <div class="inputBox">
                <span>Mã bưu điện :</span>
                <input type="number" min="0" name="pin_code" placeholder="Ví dụ: 100000" required>
            </div>
        </div>
        <input type="submit" name="order" value="Đặt hàng ngay" class="btn">
    </form>

</section>
<?php endif; ?>

<?php @include '../components/footer.php'; ?>
<script src="../js/script.js"></script>
<?php if ($checkoutOrder && $checkoutOrder['method'] === 'sepay_qr' && $checkoutOrder['payment_status'] !== 'completed'): ?>
<script>
   const paymentStatus = document.getElementById('payment-status');
   const paymentConfirmation = document.getElementById('payment-confirmation');
   const orderId = <?php echo (int) $checkoutOrder['id']; ?>;

   async function refreshPaymentStatus() {
      try {
         const response = await fetch('payment_status.php?order_id=' + orderId, {
            headers: { 'Accept': 'application/json' }
         });
         if (!response.ok) return;
         const result = await response.json();
         paymentStatus.textContent = result.payment_status;
         if (result.payment_status === 'completed') {
            paymentConfirmation.hidden = false;
            window.clearInterval(paymentPoll);
         }
      } catch (error) {
         console.error('Unable to refresh payment status.', error);
      }
   }

   const paymentPoll = window.setInterval(refreshPaymentStatus, 3000);
   refreshPaymentStatus();
</script>
<?php endif; ?>

</body>
</html>
