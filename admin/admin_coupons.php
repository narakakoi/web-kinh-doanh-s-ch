<?php
require_once '../config/config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../pages/login.php');
    exit();
}

$message = [];

if (isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discountType = $_POST['discount_type'] ?? '';
    $discountValue = filter_input(INPUT_POST, 'discount_value', FILTER_VALIDATE_FLOAT);
    $minOrderValue = filter_input(INPUT_POST, 'min_order_value', FILTER_VALIDATE_FLOAT);
    $usageLimitRaw = trim($_POST['usage_limit'] ?? '');
    $usageLimit = $usageLimitRaw === '' ? null : filter_var($usageLimitRaw, FILTER_VALIDATE_INT);
    $startsAt = trim($_POST['starts_at'] ?? '') ?: null;
    $endsAt = trim($_POST['ends_at'] ?? '') ?: null;

    $valid = $code !== '' && preg_match('/^[A-Z0-9_-]{3,50}$/', $code)
        && in_array($discountType, ['percent', 'fixed'], true)
        && $discountValue !== false && $discountValue > 0
        && $minOrderValue !== false && $minOrderValue >= 0
        && ($usageLimit === null || ($usageLimit !== false && $usageLimit > 0));
    if ($discountType === 'percent' && $discountValue > 100) {
        $valid = false;
    }

    if (!$valid) {
        $message[] = 'Thông tin mã giảm giá không hợp lệ.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO coupons (code, discount_type, discount_value, min_order_value, usage_limit, starts_at, ends_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'ssddiss', $code, $discountType, $discountValue, $minOrderValue, $usageLimit, $startsAt, $endsAt);
        if (mysqli_stmt_execute($stmt)) {
            $message[] = 'Đã tạo mã giảm giá.';
        } elseif (mysqli_errno($conn) === 1062) {
            $message[] = 'Mã giảm giá đã tồn tại.';
        } else {
            error_log('Coupon creation failed: ' . mysqli_error($conn));
            $message[] = 'Không thể tạo mã giảm giá.';
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_GET['toggle'])) {
    $couponId = filter_input(INPUT_GET, 'toggle', FILTER_VALIDATE_INT);
    if ($couponId) {
        $stmt = mysqli_prepare($conn, 'UPDATE coupons SET is_active = 1 - is_active WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $couponId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: admin_coupons.php');
    exit();
}

$coupons = mysqli_query($conn, 'SELECT * FROM coupons ORDER BY id DESC');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã giảm giá</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
<?php @include 'admin_header.php'; ?>

<?php foreach ($message as $item): ?>
    <div class="message"><span><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></span></div>
<?php endforeach; ?>

<section class="add-products">
    <form method="post">
        <h3>Tạo mã giảm giá</h3>
        <input class="box" name="code" maxlength="50" pattern="[A-Za-z0-9_-]{3,50}" placeholder="Mã, ví dụ: SACH2026" required>
        <select class="box" name="discount_type" required>
            <option value="percent">Giảm theo phần trăm</option>
            <option value="fixed">Giảm số tiền cố định</option>
        </select>
        <input class="box" type="number" name="discount_value" min="1" step="0.01" placeholder="Giá trị giảm" required>
        <input class="box" type="number" name="min_order_value" min="0" step="0.01" value="0" placeholder="Đơn tối thiểu">
        <input class="box" type="number" name="usage_limit" min="1" placeholder="Số lượt dùng, để trống là không giới hạn">
        <label>Thời gian bắt đầu</label>
        <input class="box" type="datetime-local" name="starts_at">
        <label>Thời gian kết thúc</label>
        <input class="box" type="datetime-local" name="ends_at">
        <input class="btn" type="submit" name="add_coupon" value="Tạo mã">
    </form>
</section>

<section class="show-products">
    <div class="box-container">
        <?php while ($coupon = mysqli_fetch_assoc($coupons)): ?>
            <div class="box">
                <h3><?php echo htmlspecialchars($coupon['code'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p>Giảm: <?php echo number_format((float) $coupon['discount_value'], 0, ',', '.'); ?><?php echo $coupon['discount_type'] === 'percent' ? '%' : ' VNĐ'; ?></p>
                <p>Đơn tối thiểu: <?php echo number_format((float) $coupon['min_order_value'], 0, ',', '.'); ?> VNĐ</p>
                <p>Đã dùng: <?php echo (int) $coupon['used_count']; ?> / <?php echo $coupon['usage_limit'] === null ? 'Không giới hạn' : (int) $coupon['usage_limit']; ?></p>
                <p>Trạng thái: <?php echo $coupon['is_active'] ? 'Đang bật' : 'Đã tắt'; ?></p>
                <a class="<?php echo $coupon['is_active'] ? 'delete-btn' : 'option-btn'; ?>" href="admin_coupons.php?toggle=<?php echo (int) $coupon['id']; ?>">
                    <?php echo $coupon['is_active'] ? 'Tắt mã' : 'Bật mã'; ?>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
</section>
<script src="../js/admin_script.js"></script>
</body>
</html>
