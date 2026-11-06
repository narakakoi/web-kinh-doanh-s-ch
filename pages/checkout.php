<?php
require_once '../config/config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

if ($user_id === null) {
    header('location:../pages/login.php');
    exit();
}

$sepayBank = trim((string) ($localConfig['sepay_bank_code'] ?? getenv('SEPAY_BANK_CODE')));
$sepayAccount = trim((string) ($localConfig['sepay_account_number'] ?? getenv('SEPAY_ACCOUNT_NUMBER')));
$sepayWebhookKey = trim((string) ($localConfig['sepay_webhook_api_key'] ?? getenv('SEPAY_WEBHOOK_API_KEY')));
$sepayConfigured = $sepayBank !== '' && $sepayAccount !== '' && $sepayWebhookKey !== '';
$message = [];
$checkoutOrder = null;
$_SESSION['checkout_csrf_token'] ??= bin2hex(random_bytes(32));

$getCartTotal = static function (mysqli $conn, int $userId): array {
    $total = 0.0;
    $products = [];
    $stmt = mysqli_prepare($conn, 'SELECT name, price, quantity FROM `cart` WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($item = mysqli_fetch_assoc($result)) {
        $products[] = $item['name'] . ' (' . $item['quantity'] . ') ';
        $total += (float) $item['price'] * (int) $item['quantity'];
    }
    mysqli_stmt_close($stmt);
    return [round($total), $products];
};

$findCoupon = static function (mysqli $conn, string $code, float $cartTotal, bool $lock = false): array {
    $sql = 'SELECT * FROM coupons WHERE code = ? AND is_active = 1
        AND (starts_at IS NULL OR starts_at <= NOW())
        AND (ends_at IS NULL OR ends_at >= NOW())
        AND (usage_limit IS NULL OR used_count < usage_limit)';
    if ($lock) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $code);
    mysqli_stmt_execute($stmt);
    $coupon = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    mysqli_stmt_close($stmt);

    if ($coupon === null) {
        return [null, 0.0, 'Mã giảm giá không tồn tại, đã hết hạn hoặc đã hết lượt sử dụng.'];
    }
    if ($cartTotal < (float) $coupon['min_order_value']) {
        return [null, 0.0, 'Đơn hàng chưa đạt giá trị tối thiểu để dùng mã này.'];
    }
    $discount = $coupon['discount_type'] === 'percent'
        ? $cartTotal * (float) $coupon['discount_value'] / 100
        : (float) $coupon['discount_value'];
    $discount = min($cartTotal, round($discount));
    return [$coupon, $discount, ''];
};

if (isset($_POST['apply_coupon']) && hash_equals($_SESSION['checkout_csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
    $couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));
    [$cartTotal] = $getCartTotal($conn, (int) $user_id);
    [$coupon, $discount, $couponError] = $findCoupon($conn, $couponCode, $cartTotal);
    if ($coupon === null) {
        unset($_SESSION['checkout_coupon']);
        $message[] = $couponError;
    } else {
        $_SESSION['checkout_coupon'] = $coupon['code'];
        $message[] = 'Đã áp dụng mã giảm giá. Bạn được giảm ' . number_format($discount, 0, ',', '.') . ' VNĐ.';
    }
}

if (isset($_POST['remove_coupon']) && hash_equals($_SESSION['checkout_csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
    unset($_SESSION['checkout_coupon']);
    $message[] = 'Đã bỏ mã giảm giá.';
}

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

        [$cart_total, $cart_products] = $getCartTotal($conn, (int) $user_id);

        if ($cart_total <= 0 || count($cart_products) === 0) {
            $message[] = 'Giỏ hàng đang trống hoặc tổng tiền không hợp lệ. Hãy cập nhật giá sách trước khi đặt hàng.';
        } else {
            $total_products = implode(', ', $cart_products);

            mysqli_begin_transaction($conn);
            try {
                $coupon = null;
                $discount = 0.0;
                $couponCode = $_SESSION['checkout_coupon'] ?? '';
                if ($couponCode !== '') {
                    [$coupon, $discount, $couponError] = $findCoupon($conn, $couponCode, $cart_total, true);
                    if ($coupon === null) {
                        throw new RuntimeException($couponError);
                    }
                }
                $orderTotal = max(0, $cart_total - $discount);
                $order_stmt = mysqli_prepare(
                    $conn,
                    'INSERT INTO `orders` (user_id, name, number, email, method, address, total_products, total_price, subtotal, discount_amount, coupon_code, placed_on) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                mysqli_stmt_bind_param(
                    $order_stmt,
                    'issssssdddss',
                    $user_id,
                    $name,
                    $number,
                    $email,
                    $method,
                    $address,
                    $total_products,
                    $orderTotal,
                    $cart_total,
                    $discount,
                    $couponCode,
                    $placed_on
                );
                mysqli_stmt_execute($order_stmt);
                $order_id = mysqli_insert_id($conn);
                mysqli_stmt_close($order_stmt);

                if ($coupon !== null) {
                    $coupon_update = mysqli_prepare($conn, 'UPDATE coupons SET used_count = used_count + 1 WHERE id = ?');
                    mysqli_stmt_bind_param($coupon_update, 'i', $coupon['id']);
                    mysqli_stmt_execute($coupon_update);
                    mysqli_stmt_close($coupon_update);
                }
                $delete_cart_stmt = mysqli_prepare($conn, 'DELETE FROM `cart` WHERE user_id = ?');
                mysqli_stmt_bind_param($delete_cart_stmt, 'i', $user_id);
                mysqli_stmt_execute($delete_cart_stmt);
                mysqli_stmt_close($delete_cart_stmt);

                mysqli_commit($conn);
                unset($_SESSION['checkout_coupon']);
                header('Location: checkout.php?order_id=' . $order_id);
                exit();
            } catch (Throwable $exception) {
                mysqli_rollback($conn);
                if ($exception instanceof mysqli_sql_exception) {
                    error_log('Order creation failed: ' . $exception->getMessage());
                    http_response_code(500);
                    $message[] = 'Không thể tạo đơn hàng lúc này. Vui lòng thử lại.';
                } else {
                    $message[] = $exception->getMessage();
                }
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

$isQrPayment = $checkoutOrder && $checkoutOrder['method'] === 'sepay_qr' && $qrUrl !== '';
$isPaymentCompleted = $checkoutOrder && $checkoutOrder['payment_status'] === 'completed';

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
        <?php if ($isQrPayment): ?>
            <div class="sepay-payment-card <?php echo $isPaymentCompleted ? 'is-completed' : ''; ?>" id="sepay-payment-card">
                <div class="sepay-payment-header">
                    <span class="sepay-payment-icon"><i class="fa-solid fa-building-columns"></i></span>
                    <div>
                        <h3>Thanh toán đơn hàng #<?php echo (int) $checkoutOrder['id']; ?></h3>
                        <p id="payment-status" class="payment-status <?php echo $isPaymentCompleted ? 'payment-status-success' : 'payment-status-pending'; ?>">
                            <?php echo $isPaymentCompleted ? 'Đã thanh toán thành công' : 'Đang chờ thanh toán'; ?>
                        </p>
                    </div>
                </div>

                <div class="sepay-pending-content" <?php echo $isPaymentCompleted ? 'hidden' : ''; ?>>
                    <p class="sepay-instruction">Mở ứng dụng ngân hàng và quét mã QR bên dưới để thanh toán.</p>
                    <div class="sepay-qr-wrapper">
                        <img src="<?php echo htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Mã QR thanh toán SePay">
                    </div>
                    <div class="sepay-payment-details">
                        <div><span>Số tiền</span><strong><?php echo number_format((float) $checkoutOrder['total_price'], 0, ',', '.'); ?> VNĐ</strong></div>
                        <div><span>Nội dung chuyển khoản</span><strong>DH<?php echo (int) $checkoutOrder['id']; ?></strong></div>
                    </div>
                    <p class="sepay-note"><i class="fa-solid fa-circle-info"></i> Giữ nguyên nội dung chuyển khoản để hệ thống tự động xác nhận.</p>
                    <div class="sepay-waiting"><span class="sepay-spinner"></span> Đang kiểm tra thanh toán...</div>
                </div>

                <div id="payment-confirmation" class="sepay-success-content" <?php echo $isPaymentCompleted ? '' : 'hidden'; ?>>
                    <span class="sepay-success-icon"><i class="fa-solid fa-check"></i></span>
                    <h4>Thanh toán thành công!</h4>
                    <p>Đơn hàng của bạn đã được xác nhận. Cảm ơn bạn đã mua hàng.</p>
                    <a href="orders.php" class="btn">Xem đơn hàng</a>
                </div>
            </div>
        <?php elseif ($checkoutOrder['method'] === 'sepay_qr'): ?>
            <div class="payment-unavailable">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p>QR chưa khả dụng vì cấu hình SePay trên máy chủ chưa đầy đủ. Đơn hàng vẫn đang chờ thanh toán.</p>
            </div>
        <?php else: ?>
            <div class="payment-unavailable">
                <i class="fa-solid fa-circle-check"></i>
                <p>Đơn hàng đã được tạo. Chúng tôi sẽ xử lý theo phương thức thanh toán đã chọn.</p>
            </div>
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
    <?php
        $activeCoupon = null;
        $discountAmount = 0.0;
        if (!empty($_SESSION['checkout_coupon'])) {
            [$activeCoupon, $discountAmount, $activeCouponError] = $findCoupon($conn, $_SESSION['checkout_coupon'], $grand_total);
            if ($activeCoupon === null) {
                unset($_SESSION['checkout_coupon']);
            }
        }
        $checkoutTotal = max(0, $grand_total - $discountAmount);
    ?>
    <div class="coupon-box">
        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['checkout_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="text" name="coupon_code" value="<?php echo htmlspecialchars($_SESSION['checkout_coupon'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nhập mã giảm giá">
            <button type="submit" name="apply_coupon" class="option-btn">Áp dụng</button>
            <?php if ($activeCoupon): ?>
                <button type="submit" name="remove_coupon" class="delete-btn">Bỏ mã</button>
            <?php endif; ?>
        </form>
        <?php if ($activeCoupon): ?>
            <p>Mã <?php echo htmlspecialchars($activeCoupon['code'], ENT_QUOTES, 'UTF-8'); ?>: giảm <?php echo number_format($discountAmount, 0, ',', '.'); ?> VNĐ</p>
        <?php endif; ?>
    </div>
    <div class="grand-total">Tạm tính: <span><?php echo number_format($grand_total, 0, ',', '.'); ?> VNĐ</span></div>
    <div class="grand-total">Thành tiền: <span><?php echo number_format($checkoutTotal, 0, ',', '.'); ?> VNĐ</span></div>
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
<?php if ($isQrPayment && !$isPaymentCompleted): ?>
<script>
   const paymentStatus = document.getElementById('payment-status');
   const paymentConfirmation = document.getElementById('payment-confirmation');
   const paymentCard = document.getElementById('sepay-payment-card');
   const pendingContent = paymentCard.querySelector('.sepay-pending-content');
   const orderId = <?php echo (int) $checkoutOrder['id']; ?>;

   async function refreshPaymentStatus() {
      try {
         const response = await fetch('payment_status.php?order_id=' + orderId, {
            headers: { 'Accept': 'application/json' }
         });
         if (!response.ok) return;
         const result = await response.json();
         if (result.payment_status === 'completed') {
            paymentStatus.textContent = 'Đã thanh toán thành công';
            paymentStatus.classList.remove('payment-status-pending');
            paymentStatus.classList.add('payment-status-success');
            paymentCard.classList.add('is-completed');
            pendingContent.hidden = true;
            paymentConfirmation.hidden = false;
         }
         if (result.payment_status === 'completed') {
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
