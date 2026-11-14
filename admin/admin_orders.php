<?php
@include '../config/config.php';

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;

if (!isset($admin_id)) {
    header('location:../pages/login.php');
    exit();
}

$message = [];

// 1. Cập nhật trạng thái thanh toán của đơn hàng
if (isset($_POST['update_order'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $update_payment = mysqli_real_escape_string($conn,$_POST['update_payment'] ?? 'pending');
    if ($order_id > 0) {
        mysqli_query($conn, "UPDATE `orders` SET payment_status = '$update_payment' WHERE id = '$order_id'") or die('Truy vấn thất bại');
        $message[] = 'Đã cập nhật trạng thái đơn hàng #' . $order_id . ' thành công!';
    }
}

// 2. Xóa đơn hàng
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM `orders` WHERE id = '$delete_id'") or die('Truy vấn thất bại');
    header('location:admin_orders.php');
    exit();
}

// 3. Lấy danh sách toàn bộ đơn hàng và tính chỉ số KPI
$orders_list = [];$count_all = 0;
$count_pending = 0;
$count_completed = 0;
$sum_completed_revenue = 0;

$select_orders = mysqli_query($conn, "SELECT * FROM `orders` ORDER BY id DESC") or die('Truy vấn thất bại');
while ($row = mysqli_fetch_assoc($select_orders)) {
    $orders_list[] =$row;
    $count_all++;
    $price = (float)($row['total_price'] ?? 0);
    if (($row['payment_status'] ?? '') === 'completed') {$count_completed++;
        $sum_completed_revenue +=$price;
    } else {
        $count_pending++;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Quản Lý Đơn Hàng - BookStory Admin</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<section class="orders-hub-section">
   <!-- 1. Banner Tiêu Đề -->
   <div class="orders-hero-bar">
      <div class="orders-hero-title">
         <h1><i class="fas fa-shipping-fast"></i> Trung Tâm Điều Phối & Xử Lý Đơn Hàng</h1>
         <p>Theo dõi tiến độ thanh toán, thông tin giao nhận và in phiếu xuất kho BookStory</p>
      </div>
      <a href="admin_page.php" class="hero-action-link">
         <i class="fas fa-chart-line"></i> Xem Báo Cáo Doanh Thu
      </a>
   </div>

   <!-- 2. 4 Thẻ KPI Đơn Hàng -->
   <div class="orders-kpi-grid">
      <div class="order-kpi-card kpi-all">
         <div class="o-kpi-text">
            <span>Tổng số đơn đặt</span>
            <h3><?php echo $count_all; ?> <small>đơn hàng</small></h3>
         </div>
         <div class="o-kpi-icon"><i class="fas fa-shopping-bag"></i></div>
      </div>

      <div class="order-kpi-card kpi-pending">
         <div class="o-kpi-text">
            <span>Đơn chờ xác nhận</span>
            <h3><?php echo $count_pending; ?> <small>chờ duyệt</small></h3>
         </div>
         <div class="o-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
      </div>

      <div class="order-kpi-card kpi-completed">
         <div class="o-kpi-text">
            <span>Đơn đã hoàn tất</span>
            <h3><?php echo $count_completed; ?> <small>hoàn thành</small></h3>
         </div>
         <div class="o-kpi-icon"><i class="fas fa-check-double"></i></div>
      </div>

      <div class="order-kpi-card kpi-revenue">
         <div class="o-kpi-text">
            <span>Doanh thu hoàn tất</span>
            <h3><?php echo number_format($sum_completed_revenue, 0, ',', '.'); ?> <small>đ</small></h3>
         </div>
         <div class="o-kpi-icon"><i class="fas fa-coins"></i></div>
      </div>
   </div>

   <!-- 3. Thanh Tìm Kiếm & Bộ Lọc Trạng Thái -->
   <div class="orders-toolbar">
      <div class="order-search-box">
         <i class="fas fa-search"></i>
         <input type="text" id="orderSearchInput" placeholder="Tìm nhanh theo mã đơn, tên khách hàng, số điện thoại, địa chỉ hoặc tên sách...">
      </div>

      <div class="order-filter-tabs">
         <button type="button" class="o-tab-btn active" data-status="all">
            Tất cả (<?php echo $count_all; ?>)
         </button>
         <button type="button" class="o-tab-btn" data-status="pending">
            Đang chờ duyệt (<?php echo $count_pending; ?>)
         </button>
         <button type="button" class="o-tab-btn" data-status="completed">
            Đã hoàn thành (<?php echo $count_completed; ?>)
         </button>
      </div>
   </div>

   <!-- 4. Danh Sách Phiếu Đơn Hàng -->
   <?php if ($count_all > 0): ?>
      <div class="enterprise-orders-grid" id="ordersGrid">
         <?php foreach ($orders_list as$fetch_orders): ?>
            <?php
               $oid = (int)$fetch_orders['id'];$is_done = (($fetch_orders['payment_status'] ?? '') === 'completed');$search_str = mb_strtolower(
                  '#' . $oid . ' ' .
                  ($fetch_orders['name'] ?? '') . ' ' .
                  ($fetch_orders['number'] ?? '') . ' ' .
                  ($fetch_orders['email'] ?? '') . ' ' .
                  ($fetch_orders['address'] ?? '') . ' ' .
                  ($fetch_orders['total_products'] ?? ''),
                  'UTF-8'
               );
            ?>
            <div class="enterprise-order-card <?php echo $is_done ? 'status-completed' : 'status-pending'; ?>"
                 data-status="<?php echo $is_done ? 'completed' : 'pending'; ?>"
                 data-search="<?php echo htmlspecialchars($search_str); ?>">
               <div>
                  <div class="o-card-header">
                     <div class="o-code-group">
                        <h3><i class="fas fa-receipt"></i> Đơn Hàng #<?php echo $oid; ?></h3>
                        <span class="o-date-sub"><i class="far fa-clock"></i> Ngày đặt: <?php echo htmlspecialchars($fetch_orders['placed_on']); ?></span>
                     </div>
                     <span class="o-status-pill <?php echo $is_done ? 'pill-completed' : 'pill-pending'; ?>">
                        <i class="fas <?php echo $is_done ? 'fa-check-circle' : 'fa-spinner'; ?>"></i>
                        <?php echo $is_done ? 'Đã hoàn thành' : 'Chờ xử lý'; ?>
                     </span>
                  </div>

                  <div class="o-customer-box">
                     <div class="o-row">
                        <span class="o-row-label"><i class="fas fa-user"></i> Khách hàng:</span>
                        <span class="o-row-val"><?php echo htmlspecialchars($fetch_orders['name']); ?> (ID: #<?php echo (int)$fetch_orders['user_id']; ?>)</span>
                     </div>
                     <div class="o-row">
                        <span class="o-row-label"><i class="fas fa-phone-alt"></i> Điện thoại:</span>
                        <span class="o-row-val"><?php echo htmlspecialchars($fetch_orders['number']); ?></span>
                     </div>
                     <div class="o-row">
                        <span class="o-row-label"><i class="fas fa-envelope"></i> Email:</span>
                        <span class="o-row-val"><?php echo htmlspecialchars($fetch_orders['email']); ?></span>
                     </div>
                     <div class="o-row">
                        <span class="o-row-label"><i class="fas fa-credit-card"></i> Thanh toán:</span>
                        <span class="o-row-val"><?php echo htmlspecialchars($fetch_orders['method']); ?></span>
                     </div>
                     <div class="o-row">
                        <span class="o-row-label"><i class="fas fa-map-marker-alt"></i> Giao đến:</span>
                        <span class="o-row-val"><?php echo htmlspecialchars($fetch_orders['address']); ?></span>
                     </div>
                  </div>

                  <div class="o-products-box">
                     <span class="o-products-label"><i class="fas fa-book-open"></i> Chi tiết đầu sách đặt mua</span>
                     <div class="o-products-list"><?php echo htmlspecialchars($fetch_orders['total_products']); ?></div>
                  </div>

                  <div class="o-total-banner">
                     <span>Tổng thanh toán:</span>
                     <strong><?php echo number_format((float)$fetch_orders['total_price'], 0, ',', '.'); ?> VNĐ</strong>
                  </div>
               </div>

               <form action="" method="post" class="o-update-form">
                  <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                  <div class="o-select-wrap">
                     <select name="update_payment">
                        <option value="pending" <?php echo !$is_done ? 'selected' : ''; ?>>Trạng thái: Đang chờ xử lý (Pending)</option>
                        <option value="completed" <?php echo $is_done ? 'selected' : ''; ?>>Trạng thái: Đã thanh toán & Giao thành công (Completed)</option>
                     </select>
                  </div>
                  <div class="o-action-grid">
                     <button type="submit" name="update_order" class="o-btn o-btn-save">
                        <i class="fas fa-save"></i> Cập Nhật
                     </button>
                     <button type="button" class="o-btn o-btn-print"
                             onclick="printInvoice('<?php echo $oid; ?>', '<?php echo htmlspecialchars(addslashes($fetch_orders['name'])); ?>', '<?php echo htmlspecialchars(addslashes($fetch_orders['number'])); ?>', '<?php echo htmlspecialchars(addslashes($fetch_orders['address'])); ?>', '<?php echo htmlspecialchars(addslashes($fetch_orders['total_products'])); ?>', '<?php echo number_format((float)$fetch_orders['total_price'], 0, ',', '.'); ?>')">
                        <i class="fas fa-print"></i> In Phiếu
                     </button>
                     <a href="admin_orders.php?delete=<?php echo $oid; ?>"
                        class="o-btn o-btn-del"
                        onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng #<?php echo $oid; ?>?');">
                        <i class="fas fa-trash-alt"></i> Xóa
                     </a>
                  </div>
               </form>
            </div>
         <?php endforeach; ?>
      </div>
   <?php else: ?>
      <div class="pro-empty-state">
         <div class="pro-empty-icon"><i class="fas fa-box-open"></i></div>
         <h3>Chưa Có Đơn Đặt Hàng Nào Trên Hệ Thống</h3>
         <p>Khi khách hàng đặt mua sách trên cửa hàng, thông tin đơn hàng và trạng thái thanh toán sẽ tự động hiển thị tại đây.</p>
      </div>
   <?php endif; ?>
</section>

<script src="../js/admin_script.js"></script>
<script>
function printInvoice(id, name, phone, address, products, total) {
   const win = window.open('', '_blank', 'width=720,height=650');
   win.document.write(`
      <html>
      <head>
         <title>Phiếu Giao Hàng #${id} - BookStory</title>
      </head>
      <body>
         <div class="box">
            <h2>PHIẾU XUẤT KHO & GIAO HÀNG - BOOKSTORY (#${id})</h2>
            <div class="row"><strong>Người nhận:</strong> ${name}</div>
            <div class="row"><strong>Số điện thoại:</strong> ${phone}</div>
            <div class="row"><strong>Địa chỉ giao:</strong> ${address}</div>
            <div class="items"><strong>Sách đặt mua:</strong><br>${products}</div>
            <div class="total">Tổng thu: ${total} VNĐ</div>
         </div>
         <script>window.print();<\/script>
      </body>
      </html>
   `);
   win.document.close();
}

document.addEventListener('DOMContentLoaded', function () {
   const searchInput = document.getElementById('orderSearchInput');
   const tabButtons = document.querySelectorAll('.o-tab-btn');
   const orderCards = document.querySelectorAll('.enterprise-order-card');
   let currentStatus = 'all';

   function filterOrders() {
      const kw = (searchInput ? searchInput.value : '').trim().toLowerCase();
      orderCards.forEach(card => {
         const st = card.getAttribute('data-status');
         const text = card.getAttribute('data-search') || '';
         const matchStatus = (currentStatus === 'all') || (st === currentStatus);
         const matchKw = text.includes(kw);
         card.style.display = (matchStatus && matchKw) ? 'flex' : 'none';
      });
   }

   if (searchInput) searchInput.addEventListener('input', filterOrders);

   tabButtons.forEach(btn => {
      btn.addEventListener('click', function () {
         tabButtons.forEach(b => b.classList.remove('active'));
         this.classList.add('active');
         currentStatus = this.getAttribute('data-status');
         filterOrders();
      });
   });
});
</script>
</body>
</html>
