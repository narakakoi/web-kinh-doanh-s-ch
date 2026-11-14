<?php
require_once '../config/config.php';
session_start();

/** @var mysqli $conn Connection initialized by config.php. */
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
<!doctype html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Thanh toán | BookStory</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../css/style.css"><!-- CSS riêng, phân tách theo trang -->
<link rel="stylesheet" href="../css/pages/checkout.css">
</head><body>
<?php include '../components/header.php'; ?>
<main class="bs-commerce bs-checkout"><div class="bs-wrap">
    <nav class="bs-crumb" aria-label="Đường dẫn"><a href="../index.php">Trang chủ</a><i class="fas fa-chevron-right"></i><a href="cart.php">Giỏ hàng</a><i class="fas fa-chevron-right"></i><span>Thanh toán</span></nav>
    <div class="bs-page-intro"><div><span class="bs-eyebrow"><i class="fas fa-lock"></i> Thanh toán an toàn</span><h1><?= $checkoutOrder ? 'Thông tin đơn hàng' : 'Hoàn tất đơn hàng' ?></h1><p><?= $checkoutOrder ? 'Theo dõi trạng thái thanh toán của bạn.' : 'Chỉ một bước nữa là những cuốn sách sẽ đến tay bạn.' ?></p></div></div>
    <?php if ($checkoutOrder): ?>
    <!-- Sau khi đặt hàng: giữ nguyên logic thanh toán COD/SePay hiện có. -->
    <section class="bs-payment-result bs-panel">
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

    </section>
    <a href="orders.php" class="bs-btn bs-btn-soft">Xem danh sách đơn hàng <i class="fas fa-arrow-right"></i></a>
    <?php else: ?>
    <?php
    // Tóm tắt giỏ hàng được tính từ MySQL, không tin dữ liệu giá gửi từ trình duyệt.
    $stmt = mysqli_prepare($conn, 'SELECT pid, name, price, quantity, image FROM cart WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $checkoutItems = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    $grand_total = 0.0;
    $quantityTotal = 0;
    foreach ($checkoutItems as $book) { $grand_total += (float) $book['price'] * (int) $book['quantity']; $quantityTotal += (int) $book['quantity']; }
    $activeCoupon = null;
    $discountAmount = 0.0;
    if (!empty($_SESSION['checkout_coupon'])) {
        [$activeCoupon, $discountAmount] = $findCoupon($conn, $_SESSION['checkout_coupon'], $grand_total);
        if ($activeCoupon === null) { unset($_SESSION['checkout_coupon']); }
    }
    $checkoutTotal = max(0, $grand_total - $discountAmount);
    $escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $formatPrice = static fn($value): string => number_format((float) $value, 0, ',', '.') . ' đ';
    ?>
    <?php foreach ($message as $item): ?><div class="bs-alert" role="status"><?= $escape($item) ?></div><?php endforeach; ?>
    <div class="bs-steps"><span><b>1</b> Giỏ hàng</span><i class="fas fa-minus"></i><span class="bs-step-active"><b>2</b> Thanh toán</span><i class="fas fa-minus"></i><span><b>3</b> Hoàn tất</span></div>
    <div class="bs-checkout-layout">
      <section class="bs-panel bs-checkout-form">
        <div class="bs-panel-heading"><h2>Thông tin thanh toán</h2><small>Các ô có dấu * là bắt buộc</small></div>
        <!-- FORM đặt hàng dùng đúng tên trường mà PHP phía trên đang xử lý. -->
        <form method="post" action="">
          <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['checkout_csrf_token']) ?>">
          <div class="bs-form-section"><div class="bs-form-title"><i class="far fa-user"></i><h3>Thông tin người nhận</h3></div><div class="bs-form-grid">
            <label><span>Họ và tên <em>*</em></span><input name="name" required autocomplete="name" value="<?= $escape($_POST['name'] ?? '') ?>" placeholder="Nguyễn Văn A"></label>
            <label><span>Số điện thoại <em>*</em></span><input type="tel" name="number" required autocomplete="tel" value="<?= $escape($_POST['number'] ?? '') ?>" placeholder="09xx xxx xxx"></label>
            <label class="bs-field-wide"><span>Email <em>*</em></span><input type="email" name="email" required autocomplete="email" value="<?= $escape($_POST['email'] ?? '') ?>" placeholder="email@example.com"></label>
          </div></div>
          <div class="bs-form-section"><div class="bs-form-title"><i class="fas fa-map-marker-alt"></i><h3>Địa chỉ nhận hàng</h3></div><div class="bs-form-grid">
            <label><span>Số nhà <em>*</em></span><input name="flat" required value="<?= $escape($_POST['flat'] ?? '') ?>" placeholder="Số 12/5"></label>
            <label><span>Tên đường <em>*</em></span><input name="street" required value="<?= $escape($_POST['street'] ?? '') ?>" placeholder="Đường Nguyễn Trãi"></label>
            <label><span>Thành phố <em>*</em></span><input name="city" required value="<?= $escape($_POST['city'] ?? '') ?>" placeholder="TP. Hồ Chí Minh"></label>
            <label><span>Quận / Huyện <em>*</em></span><input name="state" required value="<?= $escape($_POST['state'] ?? '') ?>" placeholder="Quận 5"></label>
            <label><span>Quốc gia <em>*</em></span><input name="country" required value="<?= $escape($_POST['country'] ?? 'Việt Nam') ?>" placeholder="Việt Nam"></label>
            <label><span>Mã bưu điện <em>*</em></span><input name="pin_code" required inputmode="numeric" value="<?= $escape($_POST['pin_code'] ?? '') ?>" placeholder="700000"></label>
          </div></div>
          <div class="bs-form-section"><div class="bs-form-title"><i class="far fa-credit-card"></i><h3>Phương thức thanh toán</h3></div><div class="bs-payment-options">
             <label class="bs-payment-choice"><input type="radio" name="method" value="cash on delivery" <?= ($_POST['method'] ?? 'cash on delivery') === 'cash on delivery' ? 'checked' : '' ?>><span><strong>Thanh toán khi nhận hàng</strong><small>Thanh toán tiền mặt khi nhận sách (COD)</small></span></label>
             <?php if ($sepayConfigured): ?><label class="bs-payment-choice"><input type="radio" name="method" value="sepay_qr" <?= ($_POST['method'] ?? '') === 'sepay_qr' ? 'checked' : '' ?>><span><strong>Chuyển khoản QR · SePay</strong><small>Quét QR qua ứng dụng ngân hàng</small></span></label><?php endif; ?>
          </div>
          <?php if (!$sepayConfigured): ?><p class="bs-payment-help"><i class="fas fa-info-circle"></i> QR SePay chưa khả dụng vì máy chủ chưa cấu hình đầy đủ.</p><?php endif; ?>
          </div>
          <?php if ($checkoutItems && $grand_total > 0): ?><button type="submit" name="order" value="1" class="bs-btn bs-btn-primary bs-btn-block bs-place-order">Đặt hàng ngay <i class="fas fa-arrow-right"></i></button><?php else: ?><p class="bs-payment-help">Vui lòng thêm sách vào giỏ trước khi đặt hàng.</p><?php endif; ?>
        </form>
      </section>
      <aside class="bs-panel bs-checkout-summary">
         <div class="bs-panel-heading"><h2>Đơn hàng của bạn</h2><small><?= $quantityTotal ?> cuốn sách</small></div>
         <div class="bs-order-items">
            <?php foreach ($checkoutItems as $book): ?>
            <div class="bs-order-item"><img loading="lazy" src="../uploaded_img/<?= $escape(basename((string) $book['image'])) ?>" alt="Bìa sách <?= $escape($book['name']) ?>"><div><strong><?= $escape($book['name']) ?></strong><small>Số lượng: <?= (int) $book['quantity'] ?></small></div><span><?= $formatPrice((float) $book['price'] * (int) $book['quantity']) ?></span></div>
            <?php endforeach; ?>
            <?php if (!$checkoutItems): ?><div class="bs-empty"><i class="fas fa-shopping-basket"></i><p>Chưa có sản phẩm nào.</p><a href="shop.php" class="bs-btn bs-btn-soft">Chọn sách</a></div><?php endif; ?>
         </div>
         <div class="bs-summary">
            <h3><i class="fas fa-tag"></i> Mã giảm giá</h3>
            <!-- Form mã giảm giá độc lập, không lồng trong form đặt hàng. -->
            <form method="post" class="bs-coupon-form"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['checkout_csrf_token']) ?>"><input name="coupon_code" value="<?= $escape($_SESSION['checkout_coupon'] ?? '') ?>" placeholder="Nhập mã giảm giá"><button type="submit" name="apply_coupon" class="bs-btn bs-btn-soft">Áp dụng</button></form>
            <?php if ($activeCoupon): ?><div class="bs-coupon-success">Mã <?= $escape($activeCoupon['code']) ?> giảm <?= $formatPrice($discountAmount) ?><form method="post"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['checkout_csrf_token']) ?>"><button type="submit" name="remove_coupon" class="bs-text-danger">Bỏ mã</button></form></div><?php endif; ?>
            <div class="bs-summary-row"><span>Tạm tính</span><strong><?= $formatPrice($grand_total) ?></strong></div><div class="bs-summary-row"><span>Giảm giá</span><strong class="bs-discount">− <?= $formatPrice($discountAmount) ?></strong></div><div class="bs-summary-row"><span>Phí vận chuyển</span><span>Chưa tính</span></div><hr><div class="bs-summary-total"><span>Thành tiền</span><strong><?= $formatPrice($checkoutTotal) ?></strong></div>
            <p class="bs-summary-note"><i class="fas fa-shield-alt"></i> Thông tin được xử lý qua hệ thống BookStory.</p>
         </div>
      </aside>
    </div>
    <a href="cart.php" class="bs-back"><i class="fas fa-arrow-left"></i> Quay lại giỏ hàng</a>
    <?php endif; ?>
</div></main>
<?php include '../components/footer.php'; ?><script src="../js/script.js"></script>
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

</body></html>
