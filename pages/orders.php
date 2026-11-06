<?php
@include '../config/config.php';
require_once '../config/order_items.php';

session_start();

$user_id = $_SESSION['user_id'] ?? null;

if($user_id === null){
   header('location:../pages/login.php');
   exit();
}

if (!isset($_SESSION['orders_csrf_token'])) {
    $_SESSION['orders_csrf_token'] = bin2hex(random_bytes(32));
}

$order_message = $_SESSION['orders_message'] ?? '';
unset($_SESSION['orders_message']);

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

// Bảng giá hiện tại của sách, dùng để tính đơn giá và thành tiền từng cuốn
// trong đơn hàng đã đặt (đơn hàng chỉ lưu tên sách kèm số lượng).
$book_prices = [];
$price_query = mysqli_query($conn, "SELECT `name`, `price` FROM `products`");
if($price_query){
    while($price_row = mysqli_fetch_assoc($price_query)){
        $book_prices[$price_row['name']] = (float) $price_row['price'];
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Đơn Hàng</title>
   <!-- liên kết font awesome cdn -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <!-- liên kết file css tùy chỉnh -->
   <link rel="stylesheet" href="../css/style.css">
</head>
<body>
   
<?php @include '../components/header.php'; ?>

<section class="heading">
    <h3>đơn hàng của bạn</h3>
    <p> <a href="index.php">Trang chủ</a> / Đơn hàng </p>
</section>

<section class="placed-orders">
    <h1 class="title">Đơn hàng đã đặt</h1>
    <?php if ($order_message !== ''): ?>
        <p class="message"><?php echo htmlspecialchars($order_message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <div class="box-container">
    <?php
        $select_orders = mysqli_query($conn, "SELECT * FROM `orders` WHERE user_id = '$user_id'") or die('Lỗi truy vấn');
        if(mysqli_num_rows($select_orders) > 0){
            while($fetch_orders = mysqli_fetch_assoc($select_orders)){
    ?>
    <div class="box">
        <p> Ngày đặt hàng : <span><?php echo $fetch_orders['placed_on']; ?></span> </p>
        <p> Tên : <span><?php echo $fetch_orders['name']; ?></span> </p>
        <p> Số điện thoại : <span><?php echo $fetch_orders['number']; ?></span> </p>
        <p> Email : <span><?php echo $fetch_orders['email']; ?></span> </p>
        <p> Địa chỉ : <span><?php echo $fetch_orders['address']; ?></span> </p>
        <p> Phương thức thanh toán : <span><?php echo $fetch_orders['method']; ?></span> </p>
        <?php $order_items = parse_order_items($fetch_orders['total_products']); ?>
        <?php if(!empty($order_items)){ ?>
        <div class="order-items">
            <p class="order-items-title">Sách đã đặt</p>
            <div class="order-item order-item-head">
                <span>Tên sách</span>
                <span>SL</span>
                <span>Đơn giá</span>
                <span>Thành tiền</span>
            </div>
            <?php
                foreach($order_items as $item){
                    $unit_price = isset($book_prices[$item['name']]) ? $book_prices[$item['name']] : null;
                    $line_price = $unit_price !== null ? $unit_price * $item['qty'] : null;
            ?>
            <div class="order-item">
                <span class="item-name"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="item-qty"><?php echo $item['qty']; ?></span>
                <span class="item-unit"><?php echo $unit_price === null ? '—' : number_format($unit_price, 0, ',', '.') . ' VNĐ'; ?></span>
                <span class="item-line"><?php echo $line_price === null ? '—' : number_format($line_price, 0, ',', '.') . ' VNĐ'; ?></span>
            </div>
            <?php } ?>
            <div class="order-item-total">
                <span>Tổng giá</span>
                <span><?php echo number_format($fetch_orders['total_price'], 0, ',', '.'); ?> VNĐ</span>
            </div>
        </div>
        <?php }else{ ?>
        <p> Sản phẩm của bạn : <span><?php echo htmlspecialchars($fetch_orders['total_products'], ENT_QUOTES, 'UTF-8'); ?></span> </p>
        <p> Tổng giá: <span><?php echo number_format($fetch_orders['total_price'], 0, ',', '.'); ?> VNĐ</span></p>
        <?php } ?>
        <p> Tình trạng thanh toán : <span style="color:<?php if($fetch_orders['payment_status'] == 'pending'){echo 'tomato'; }else{echo 'green';} ?>"><?php echo $fetch_orders['payment_status']; ?></span> </p>
        <?php if ($fetch_orders['payment_status'] === 'pending'): ?>
            <div class="order-actions">
                <a href="checkout.php?order_id=<?php echo (int) $fetch_orders['id']; ?>" class="btn">
                    <i class="fas fa-credit-card"></i> Xem chi tiết / Thanh toán
                </a>
                <form action="orders.php" method="post" onsubmit="return confirm('Bạn có chắc muốn xóa đơn hàng này?');">
                    <input type="hidden" name="order_id" value="<?php echo (int) $fetch_orders['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['orders_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" name="delete_order" class="delete-btn">
                        <i class="fas fa-trash"></i> Xóa đơn hàng
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <?php
        }
    }else{
        echo '<p class="empty">Bạn chưa có đơn hàng nào!</p>';
    }
    
    ?>
    </div>

</section>

<?php @include '../components/footer.php'; ?>
<script src="../js/script.js"></script>

</body>
</html>
