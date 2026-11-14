<?php
/**
 * BOOKSTORY - ĐƠN HÀNG CỦA BẠN
 * Giao diện mới tách CSS theo từng trang: css/pages/orders.css.
 * Nghiệp vụ giữ nguyên: chỉ xem đơn của tài khoản, xóa đơn pending bằng POST + CSRF.
 */
@include '../config/config.php';
require_once '../config/order_items.php';

session_start();
$user_id = (int) ($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    header('Location: login.php');
    exit();
}

// 01. Token CSRF riêng cho thao tác xóa đơn hàng.
if (!isset($_SESSION['orders_csrf_token'])) {
    $_SESSION['orders_csrf_token'] = bin2hex(random_bytes(32));
}
$order_message = $_SESSION['orders_message'] ?? '';
unset($_SESSION['orders_message']);

// 02. Giữ nguyên quy tắc cũ: chỉ xóa đơn pending thuộc đúng người đăng nhập.
if (isset($_POST['delete_order'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $csrf_token = (string) ($_POST['csrf_token'] ?? '');

    if (!$order_id || !hash_equals($_SESSION['orders_csrf_token'], $csrf_token)) {
        $_SESSION['orders_message'] = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    } else {
        $delete_stmt = mysqli_prepare(
            $conn,
            "DELETE FROM `orders` WHERE id = ? AND user_id = ? AND payment_status = 'pending'"
        );
        mysqli_stmt_bind_param($delete_stmt, 'ii', $order_id, $user_id);
        mysqli_stmt_execute($delete_stmt);
        $deleted = mysqli_stmt_affected_rows($delete_stmt);
        mysqli_stmt_close($delete_stmt);

        $_SESSION['orders_message'] = $deleted > 0
            ? 'Đã xóa đơn hàng đang chờ thanh toán.'
            : 'Không thể xóa đơn hàng này vì đơn không tồn tại hoặc đã được thanh toán.';
    }

    header('Location: orders.php');
    exit();
}

// 03. Đọc dữ liệu đơn hàng bằng truy vấn tham số, đúng phạm vi tài khoản.
$order_stmt = mysqli_prepare($conn, 'SELECT * FROM `orders` WHERE user_id = ? ORDER BY id DESC');
mysqli_stmt_bind_param($order_stmt, 'i', $user_id);
mysqli_stmt_execute($order_stmt);
$orders = mysqli_fetch_all(mysqli_stmt_get_result($order_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($order_stmt);

// Giá sách hiện tại chỉ dùng để trình bày đơn giá/thành tiền từng dòng.
// Tổng thanh toán luôn lấy từ orders.total_price đã lưu tại thời điểm đặt hàng.
$book_prices = [];
$price_query = mysqli_query($conn, 'SELECT `name`, `price` FROM `products`');
if ($price_query) {
    while ($price_row = mysqli_fetch_assoc($price_query)) {
        $book_prices[$price_row['name']] = (float) $price_row['price'];
    }
}

// 04. Các helper giao diện: escape HTML, định dạng tiền và trạng thái.
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value): string => number_format((float) $value, 0, ',', '.') . ' VNĐ';
$status_info = static function ($value): array {
    switch (strtolower(trim((string) $value))) {
        case 'pending':
            return ['Chờ thanh toán', 'pending', 'fa-clock'];
        case 'paid':
        case 'completed':
        case 'success':
            return ['Đã thanh toán', 'paid', 'fa-check-circle'];
        case 'failed':
            return ['Thanh toán thất bại', 'failed', 'fa-exclamation-circle'];
        case 'cancelled':
        case 'canceled':
            return ['Đã hủy', 'cancelled', 'fa-times-circle'];
        default:
            return ['Đang xử lý', 'other', 'fa-circle-info'];
    }
};
$payment_name = static function ($value): string {
    $normalized = strtolower(trim((string) $value));
    if (in_array($normalized, ['cash on delivery', 'cod'], true)) {
        return 'Thanh toán khi nhận hàng (COD)';
    }
    if (strpos($normalized, 'sepay') !== false) {
        return 'Chuyển khoản qua SePay';
    }
    return (string) $value;
};

$order_count = count($orders);
$pending_count = 0;
$paid_count = 0;
foreach ($orders as $order) {
    $status = strtolower(trim((string) ($order['payment_status'] ?? '')));
    if ($status === 'pending') {
        $pending_count++;
    }
    if (in_array($status, ['paid', 'completed', 'success'], true)) {
        $paid_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của bạn | BookStory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <!-- CSS tách theo trang; mỗi mảng style được đặt trong file riêng để dễ bảo trì. -->
    <link rel="stylesheet" href="../css/pages/orders.css">
</head>
<body>
<?php include '../components/header.php'; ?>

<main class="bs-commerce bs-orders">
    <div class="bs-wrap">
        <!-- 05. Điều hướng và tiêu đề trang -->
        <nav class="bs-crumb" aria-label="Đường dẫn">
            <a href="../index.php">Trang chủ</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span>Đơn hàng của bạn</span>
        </nav>

        <div class="bs-page-intro">
            <div>
                <span class="bs-eyebrow"><i class="fas fa-book-open" aria-hidden="true"></i> BOOKSTORY · THEO DÕI ĐƠN</span>
                <h1>Đơn hàng của bạn</h1>
                <p>Xem lại sách đã mua, theo dõi thanh toán và quản lý đơn hàng tại một nơi.</p>
            </div>
            <div class="bs-hero-badge" aria-label="Tổng số đơn hàng">
                <i class="fas fa-box-open" aria-hidden="true"></i>
                <div><strong><?= $order_count ?> đơn hàng</strong><small>Lịch sử mua sách</small></div>
            </div>
        </div>

        <?php if ($order_message !== ''): ?>
            <div class="bs-alert" role="status"><?= $e($order_message) ?></div>
        <?php endif; ?>

        <!-- 06. Tóm tắt giúp xem nhanh tình trạng đơn hàng. -->
        <section class="bs-orders-overview" aria-label="Tổng quan đơn hàng">
            <div class="bs-orders-stat"><span class="bs-orders-stat-icon"><i class="fas fa-receipt" aria-hidden="true"></i></span><div><small>Tổng đơn đã đặt</small><strong><?= $order_count ?></strong></div></div>
            <div class="bs-orders-stat"><span class="bs-orders-stat-icon is-warning"><i class="far fa-clock" aria-hidden="true"></i></span><div><small>Chờ thanh toán</small><strong><?= $pending_count ?></strong></div></div>
            <div class="bs-orders-stat"><span class="bs-orders-stat-icon is-success"><i class="far fa-check-circle" aria-hidden="true"></i></span><div><small>Đã thanh toán</small><strong><?= $paid_count ?></strong></div></div>
        </section>

        <!-- 07. Danh sách từng đơn; trạng thái và nút thao tác lấy từ dữ liệu thật. -->
        <section class="bs-orders-list" aria-label="Danh sách đơn hàng">
            <div class="bs-orders-section-title">
                <div><span class="bs-eyebrow">LỊCH SỬ MUA SẮM</span><h2>Đơn hàng đã đặt</h2></div>
                <span class="bs-orders-count"><?= $order_count ?> đơn</span>
            </div>

            <?php if (!$orders): ?>
                <div class="bs-panel bs-empty bs-orders-empty">
                    <i class="fas fa-box-open" aria-hidden="true"></i>
                    <h2>Bạn chưa có đơn hàng nào</h2>
                    <p>Khám phá các đầu sách thú vị và bắt đầu đơn hàng đầu tiên của bạn.</p>
                    <a href="shop.php" class="bs-btn bs-btn-primary"><i class="fas fa-book" aria-hidden="true"></i> Khám phá cửa hàng</a>
                </div>
            <?php endif; ?>

            <?php foreach ($orders as $order): ?>
                <?php
                    $info = $status_info($order['payment_status'] ?? '');
                    $items = parse_order_items($order['total_products'] ?? '');
                    $is_pending = (string) ($order['payment_status'] ?? '') === 'pending';
                ?>
                <article class="bs-panel bs-order-card">
                    <div class="bs-order-head">
                        <div class="bs-order-identity">
                            <span class="bs-order-id"><i class="fas fa-receipt" aria-hidden="true"></i> Đơn hàng #<?= (int) $order['id'] ?></span>
                            <span class="bs-order-date"><i class="far fa-calendar-alt" aria-hidden="true"></i> <?= $e($order['placed_on'] ?? '') ?></span>
                        </div>
                        <span class="bs-order-status bs-order-status-<?= $e($info[1]) ?>">
                            <i class="fas <?= $e($info[2]) ?>" aria-hidden="true"></i> <?= $e($info[0]) ?>
                        </span>
                    </div>

                    <div class="bs-order-body">
                        <!-- Cột trái: thông tin người nhận / thanh toán. -->
                        <section class="bs-order-info" aria-label="Thông tin giao hàng">
                            <h3><i class="far fa-address-card" aria-hidden="true"></i> Thông tin nhận hàng</h3>
                            <dl class="bs-order-details">
                                <div><dt>Người nhận</dt><dd><?= $e($order['name'] ?? '') ?></dd></div>
                                <div><dt>Điện thoại</dt><dd><?= $e($order['number'] ?? '') ?></dd></div>
                                <div><dt>Email</dt><dd class="bs-order-break"><?= $e($order['email'] ?? '') ?></dd></div>
                                <div><dt>Địa chỉ</dt><dd class="bs-order-break"><?= $e($order['address'] ?? '') ?></dd></div>
                                <div><dt>Thanh toán</dt><dd><?= $e($payment_name($order['method'] ?? '')) ?></dd></div>
                            </dl>
                        </section>

                        <!-- Cột phải: từng đầu sách, số lượng và số tiền. -->
                        <section class="bs-order-products" aria-label="Danh sách sách đã đặt">
                            <h3><i class="fas fa-book" aria-hidden="true"></i> Sách trong đơn hàng</h3>
                            <?php if ($items): ?>
                                <div class="bs-order-table" role="table" aria-label="Chi tiết sách trong đơn">
                                    <div class="bs-order-table-row bs-order-table-head" role="row">
                                        <span role="columnheader">Tên sách</span><span role="columnheader">SL</span><span role="columnheader">Đơn giá</span><span role="columnheader">Thành tiền</span>
                                    </div>
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                            $unit_price = $book_prices[$item['name']] ?? null;
                                            $line_price = $unit_price !== null ? $unit_price * (int) $item['qty'] : null;
                                        ?>
                                        <div class="bs-order-table-row" role="row">
                                            <span class="bs-order-book-name" role="cell"><?= $e($item['name']) ?></span>
                                            <span class="bs-order-qty" role="cell"><?= (int) $item['qty'] ?></span>
                                            <span class="bs-order-unit" role="cell"><?= $unit_price === null ? '—' : $money($unit_price) ?></span>
                                            <strong class="bs-order-line" role="cell"><?= $line_price === null ? '—' : $money($line_price) ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="bs-order-fallback"><?= $e($order['total_products'] ?? '') ?></p>
                            <?php endif; ?>
                            <?php if ((float) ($order['discount_amount'] ?? 0) > 0): ?>
                                <div class="bs-order-discount"><i class="fas fa-tag" aria-hidden="true"></i> Mã <?= $e($order['coupon_code'] ?? '') ?>: giảm <?= $money($order['discount_amount']) ?></div>
                            <?php endif; ?>
                        </section>
                    </div>

                    <!-- 08. Tổng đơn lấy từ database; giữ hành động thanh toán và xóa đơn pending. -->
                    <div class="bs-order-bottom">
                        <div class="bs-order-total"><span>Tổng thanh toán</span><strong><?= $money($order['total_price'] ?? 0) ?></strong></div>
                        <?php if ($is_pending): ?>
                            <div class="bs-order-actions">
                                <a href="checkout.php?order_id=<?= (int) $order['id'] ?>" class="bs-btn bs-btn-primary"><i class="far fa-credit-card" aria-hidden="true"></i> Xem chi tiết / Thanh toán</a>
                                <form action="orders.php" method="post" onsubmit="return confirm('Bạn có chắc muốn xóa đơn hàng này?');">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $e($_SESSION['orders_csrf_token']) ?>">
                                    <button type="submit" name="delete_order" value="1" class="bs-btn bs-order-delete"><i class="far fa-trash-alt" aria-hidden="true"></i> Xóa đơn hàng</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span class="bs-order-final-note"><i class="fas fa-shield-alt" aria-hidden="true"></i> Đơn hàng đã được ghi nhận</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </div>
</main>

<?php include '../components/footer.php'; ?>
<script src="../js/script.js"></script>
</body>
</html>
