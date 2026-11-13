<?php
// BookStory / Wishlist: mọi thao tác thay đổi dữ liệu đều dùng POST + CSRF.
require_once '../config/config.php';
session_start();
$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: login.php');
    exit();
}
$_SESSION['wishlist_csrf'] ??= bin2hex(random_bytes(32));
$notice = $_SESSION['wishlist_notice'] ?? '';
unset($_SESSION['wishlist_notice']);

// Không nhận tên/giá/ảnh từ hidden input: đọc sách từ dữ liệu đã lưu trên server.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['wishlist_csrf'], $token)) {
        http_response_code(400);
        $notice = 'Phiên thao tác không hợp lệ. Hãy tải lại trang.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $wishlistId = (int) ($_POST['wishlist_id'] ?? 0);
        if ($action === 'clear') {
            $stmt = mysqli_prepare($conn, 'DELETE FROM wishlist WHERE user_id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['wishlist_notice'] = 'Đã xóa toàn bộ sách yêu thích.';
        } elseif ($wishlistId > 0 && $action === 'remove') {
            $stmt = mysqli_prepare($conn, 'DELETE FROM wishlist WHERE id = ? AND user_id = ?');
            mysqli_stmt_bind_param($stmt, 'ii', $wishlistId, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['wishlist_notice'] = 'Đã xóa sách khỏi danh sách yêu thích.';
        } elseif ($wishlistId > 0 && $action === 'add_to_cart') {
            $stmt = mysqli_prepare($conn, 'SELECT pid, name, price, image FROM wishlist WHERE id = ? AND user_id = ?');
            mysqli_stmt_bind_param($stmt, 'ii', $wishlistId, $user_id);
            mysqli_stmt_execute($stmt);
            $book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if ($book) {
                $pid = (int) $book['pid'];
                $stmt = mysqli_prepare($conn, 'SELECT id FROM cart WHERE user_id = ? AND pid = ? LIMIT 1');
                mysqli_stmt_bind_param($stmt, 'ii', $user_id, $pid);
                mysqli_stmt_execute($stmt);
                $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);
                if (!$exists) {
                    $qty = 1;
                    $price = (float) $book['price'];
                    $stmt = mysqli_prepare($conn, 'INSERT INTO cart (user_id, pid, name, price, quantity, image) VALUES (?, ?, ?, ?, ?, ?)');
                    mysqli_stmt_bind_param($stmt, 'iisdis', $user_id, $pid, $book['name'], $price, $qty, $book['image']);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
                $stmt = mysqli_prepare($conn, 'DELETE FROM wishlist WHERE id = ? AND user_id = ?');
                mysqli_stmt_bind_param($stmt, 'ii', $wishlistId, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $_SESSION['wishlist_notice'] = $exists ? 'Sách đã có trong giỏ hàng.' : 'Đã chuyển sách vào giỏ hàng.';
            }
        }
        header('Location: wishlist.php');
        exit();
    }
}

// Đọc danh sách một lần, dùng cho cả lưới sách và bộ đếm.
$stmt = mysqli_prepare($conn, 'SELECT id, pid, name, price, image FROM wishlist WHERE user_id = ? ORDER BY id DESC');
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$items = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value): string => number_format((float) $value, 0, ',', '.') . ' đ';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách yêu thích | BookStory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <!-- CSS dùng chung rồi đến CSS riêng: không trộn thêm vào style.css. -->
    <link rel="stylesheet" href="../css/pages/commerce.css">
    <link rel="stylesheet" href="../css/pages/wishlist.css">
</head>
<body>
<?php include '../components/header.php'; ?>
<main class="bs-commerce bs-wishlist">
    <div class="bs-wrap">
        <nav class="bs-crumb" aria-label="Đường dẫn"><a href="../index.php">Trang chủ</a><i class="fas fa-chevron-right"></i><span>Danh sách yêu thích</span></nav>
        <div class="bs-page-intro">
            <div><span class="bs-eyebrow"><i class="far fa-heart"></i> Góc sách của bạn</span><h1>Danh sách yêu thích</h1><p>Những cuốn sách bạn muốn dành thời gian khám phá.</p></div>
            <div class="bs-hero-badge"><i class="far fa-heart"></i><div><strong><?= count($items) ?> cuốn sách</strong><small>Đã lưu vào yêu thích</small></div></div>
        </div>
        <?php if ($notice !== ''): ?><div class="bs-alert" role="status"><?= $e($notice) ?></div><?php endif; ?>
        <div class="bs-section-head"><h2>Sách đã lưu <span>(<?= count($items) ?> sản phẩm)</span></h2><a href="shop.php" class="bs-text-link"><i class="fas fa-arrow-left"></i> Tiếp tục mua sắm</a></div>
        <?php if ($items): ?>
        <div class="bs-wishlist-grid">
            <?php foreach ($items as $book): ?>
            <article class="bs-book-card">
                <div class="bs-book-visual"><span class="bs-book-tag">ĐÃ YÊU THÍCH</span>
                    <form method="post" class="bs-book-heart" onsubmit="return confirm('Xóa sách khỏi yêu thích?')">
                        <input type="hidden" name="csrf_token" value="<?= $e($_SESSION['wishlist_csrf']) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="wishlist_id" value="<?= (int) $book['id'] ?>">
                        <button type="submit" aria-label="Xóa khỏi yêu thích"><i class="fas fa-heart"></i></button>
                    </form>
                    <a href="view_page.php?pid=<?= (int) $book['pid'] ?>"><img loading="lazy" src="../uploaded_img/<?= $e(basename((string) $book['image'])) ?>" alt="Bìa sách <?= $e($book['name']) ?>"></a>
                </div>
                <div class="bs-book-details"><span class="bs-card-kicker">BOOKSTORY COLLECTION</span><h3><a href="view_page.php?pid=<?= (int) $book['pid'] ?>"><?= $e($book['name']) ?></a></h3><strong class="bs-price"><?= $money($book['price']) ?></strong>
                    <div class="bs-book-actions"><form method="post"><input type="hidden" name="csrf_token" value="<?= $e($_SESSION['wishlist_csrf']) ?>"><input type="hidden" name="action" value="add_to_cart"><input type="hidden" name="wishlist_id" value="<?= (int) $book['id'] ?>"><button type="submit" class="bs-btn bs-btn-primary"><i class="fas fa-shopping-bag"></i> Thêm vào giỏ</button></form>
                        <a href="view_page.php?pid=<?= (int) $book['pid'] ?>" class="bs-btn bs-btn-soft" aria-label="Xem chi tiết <?= $e($book['name']) ?>"><i class="far fa-eye"></i></a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <form method="post" class="bs-wishlist-bottom" onsubmit="return confirm('Xóa tất cả sách yêu thích?')"><input type="hidden" name="csrf_token" value="<?= $e($_SESSION['wishlist_csrf']) ?>"><input type="hidden" name="action" value="clear"><span><i class="fas fa-shield-alt"></i> Sách bạn yêu thích được lưu trong tài khoản của bạn.</span><button class="bs-text-danger" type="submit"><i class="far fa-trash-alt"></i> Xóa tất cả</button></form>
        <?php else: ?>
        <div class="bs-empty"><i class="far fa-heart"></i><h2>Danh sách yêu thích đang trống</h2><p>Hãy khám phá cửa hàng và chọn những cuốn sách bạn thích.</p><a href="shop.php" class="bs-btn bs-btn-primary">Khám phá sách <i class="fas fa-arrow-right"></i></a></div>
        <?php endif; ?>
    </div>
</main>
<?php include '../components/footer.php'; ?>
<script src="../js/script.js"></script>
</body></html>
