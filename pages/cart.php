<?php
// BookStory / Cart: nút +/- cập nhật số lượng bằng POST và kiểm tra đúng chủ giỏ.
require_once '../config/config.php';
session_start();
$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) { header('Location: login.php'); exit(); }
$_SESSION['cart_csrf'] ??= bin2hex(random_bytes(32));
$notice = $_SESSION['cart_notice'] ?? '';
unset($_SESSION['cart_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['cart_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(400);
        $notice = 'Phiên thao tác không hợp lệ. Hãy tải lại trang.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $cartId = (int) ($_POST['cart_id'] ?? 0);
        if ($action === 'clear') {
            $stmt = mysqli_prepare($conn, 'DELETE FROM cart WHERE user_id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['cart_notice'] = 'Đã làm trống giỏ hàng.';
        } elseif ($cartId > 0 && $action === 'remove') {
            $stmt = mysqli_prepare($conn, 'DELETE FROM cart WHERE id = ? AND user_id = ?');
            mysqli_stmt_bind_param($stmt, 'ii', $cartId, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['cart_notice'] = 'Đã xóa sách khỏi giỏ hàng.';
        } elseif ($cartId > 0 && $action === 'change_quantity') {
            $qty = max(1, min(99, (int) ($_POST['cart_quantity'] ?? 1)));
            $stmt = mysqli_prepare($conn, 'UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?');
            mysqli_stmt_bind_param($stmt, 'iii', $qty, $cartId, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['cart_notice'] = 'Đã cập nhật số lượng sách.';
        }
        header('Location: cart.php');
        exit();
    }
}

$stmt = mysqli_prepare($conn, 'SELECT id, pid, name, price, quantity, image FROM cart WHERE user_id = ? ORDER BY id DESC');
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$items = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
$total = 0.0;
$quantityTotal = 0;
foreach ($items as $item) { $total += (float) $item['price'] * (int) $item['quantity']; $quantityTotal += (int) $item['quantity']; }
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value): string => number_format((float) $value, 0, ',', '.') . ' đ';
?>
<!doctype html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Giỏ hàng | BookStory</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<!-- Tách CSS theo màn hình; style.css vẫn phục vụ header/footer và các trang cũ. -->
<link rel="stylesheet" href="../css/pages/commerce.css"><link rel="stylesheet" href="../css/pages/cart.css">
</head><body>
<?php include '../components/header.php'; ?>
<main class="bs-commerce bs-cart"><div class="bs-wrap">
    <nav class="bs-crumb" aria-label="Đường dẫn"><a href="../index.php">Trang chủ</a><i class="fas fa-chevron-right"></i><span>Giỏ hàng</span></nav>
    <div class="bs-page-intro"><div><span class="bs-eyebrow"><i class="fas fa-shopping-bag"></i> Sắp sở hữu sách hay</span><h1>Giỏ hàng của bạn</h1><p>Kiểm tra những cuốn sách trước khi thanh toán.</p></div><div class="bs-hero-badge"><i class="fas fa-shopping-basket"></i><div><strong><?= $quantityTotal ?> cuốn sách</strong><small>Trong giỏ của bạn</small></div></div></div>
    <?php if ($notice !== ''): ?><div class="bs-alert" role="status"><?= $e($notice) ?></div><?php endif; ?>
    <div class="bs-steps"><span class="bs-step-active"><b>1</b> Giỏ hàng</span><i class="fas fa-minus"></i><span><b>2</b> Thanh toán</span><i class="fas fa-minus"></i><span><b>3</b> Hoàn tất</span></div>
    <div class="bs-cart-layout">
        <section class="bs-panel bs-cart-list"><div class="bs-panel-heading"><h2>Sách trong giỏ hàng <small>(<?= $quantityTotal ?> cuốn)</small></h2><a href="shop.php" class="bs-text-link">Mua thêm sách</a></div>
            <?php if (!$items): ?><div class="bs-empty"><i class="fas fa-shopping-basket"></i><h2>Giỏ hàng đang trống</h2><p>Hãy chọn vài cuốn sách hay để bắt đầu.</p><a class="bs-btn bs-btn-primary" href="shop.php">Khám phá cửa hàng</a></div><?php endif; ?>
            <?php foreach ($items as $item): ?>
            <?php $lineTotal = (float) $item['price'] * (int) $item['quantity']; ?>
            <article class="bs-cart-item"><a href="view_page.php?pid=<?= (int) $item['pid'] ?>" class="bs-cart-cover"><img loading="lazy" src="../uploaded_img/<?= $e(basename((string) $item['image'])) ?>" alt="Bìa sách <?= $e($item['name']) ?>"></a>
                <div class="bs-cart-details"><span class="bs-card-kicker">SÁCH BOOKSTORY</span><h3><a href="view_page.php?pid=<?= (int) $item['pid'] ?>"><?= $e($item['name']) ?></a></h3><strong><?= $money($item['price']) ?></strong>
                    <form method="post" onsubmit="return confirm('Xóa sách này khỏi giỏ hàng?')"><input type="hidden" name="csrf_token" value="<?= $e($_SESSION['cart_csrf']) ?>"><input type="hidden" name="cart_id" value="<?= (int) $item['id'] ?>"><button class="bs-text-danger" type="submit" name="action" value="remove"><i class="far fa-trash-alt"></i> Xóa sản phẩm</button></form>
                </div>
                <div class="bs-cart-amount"><form method="post" class="bs-quantity-form"><input type="hidden" name="csrf_token" value="<?= $e($_SESSION['cart_csrf']) ?>"><input type="hidden" name="cart_id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="action" value="change_quantity"><button type="button" class="bs-qty-minus" aria-label="Giảm số lượng" onclick="this.form.cart_quantity.stepDown();this.form.requestSubmit()" <?= (int) $item['quantity'] <= 1 ? 'disabled' : '' ?>>−</button><input type="number" id="qty-<?= (int) $item['id'] ?>" name="cart_quantity" min="1" max="99" value="<?= (int) $item['quantity'] ?>" aria-label="Số lượng sách" required><button type="button" aria-label="Tăng số lượng" onclick="this.form.cart_quantity.stepUp();this.form.requestSubmit()" <?= (int) $item['quantity'] >= 99 ? 'disabled' : '' ?>>+</button><button type="submit" class="bs-qty-save" aria-label="Lưu số lượng"><i class="fas fa-check"></i></button></form><strong class="bs-item-total"><?= $money($lineTotal) ?></strong></div>
            </article>
            <?php endforeach; ?>
            <?php if ($items): ?><div class="bs-panel-foot"><a class="bs-btn bs-btn-soft" href="shop.php"><i class="fas fa-arrow-left"></i> Tiếp tục mua sắm</a><form method="post" onsubmit="return confirm('Xóa toàn bộ giỏ hàng?')"><input type="hidden" name="csrf_token" value="<?= $e($_SESSION['cart_csrf']) ?>"><button type="submit" name="action" value="clear" class="bs-text-danger"><i class="far fa-trash-alt"></i> Xóa tất cả</button></form></div><?php endif; ?>
        </section>
        <aside class="bs-panel bs-summary"><h2>Tổng đơn hàng</h2><div class="bs-summary-row"><span>Sản phẩm (<?= $quantityTotal ?>)</span><strong><?= $money($total) ?></strong></div><div class="bs-summary-row"><span>Giảm giá</span><span>Áp dụng tại thanh toán</span></div><div class="bs-summary-row"><span>Phí vận chuyển</span><span>Chưa tính</span></div><hr><div class="bs-summary-total"><span>Tạm tính</span><strong><?= $money($total) ?></strong></div>
            <?php if ($items && $total > 0): ?><a href="checkout.php" class="bs-btn bs-btn-primary bs-btn-block">Tiến hành thanh toán <i class="fas fa-arrow-right"></i></a><?php else: ?><span class="bs-btn bs-btn-primary bs-btn-block bs-disabled">Tiến hành thanh toán</span><?php endif; ?>
            <p class="bs-summary-note"><i class="fas fa-lock"></i> Thanh toán được bảo vệ bằng phiên đăng nhập.</p>
        </aside>
    </div>
</div></main>
<?php include '../components/footer.php'; ?><script src="../js/script.js"></script></body></html>
