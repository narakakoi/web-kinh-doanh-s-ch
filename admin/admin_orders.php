<?php
require_once '../config/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}

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
   <style>
      /* =========================================================
         CSS TRUNG TÂM ĐIỀU PHỐI & XỬ LÝ ĐƠN HÀNG CHUYÊN NGHIỆP
         ========================================================= */
      .orders-hub-section {
         max-width: 1300px;
         margin: 0 auto;
         padding: 2.8rem 2rem 5rem;
      }

      .orders-hero-bar {
         background: var(--white);
         border: var(--border);
         border-radius: 1.8rem;
         padding: 2.6rem 3rem;
         box-shadow: 0 1rem 2.6rem rgba(15, 23, 42, 0.05);
         display: flex;
         align-items: center;
         justify-content: space-between;
         flex-wrap: wrap;
         gap: 2rem;
         margin-bottom: 2.4rem;
      }

      .orders-hero-title h1 {
         font-size: 2.8rem;
         font-weight: 700;
         color: var(--black);
         margin-bottom: 0.5rem;
         display: flex;
         align-items: center;
         gap: 1.2rem;
      }

      .orders-hero-title h1 i {
         color: var(--brown);
      }

      .orders-hero-title p {
         font-size: 1.55rem;
         color: var(--light-color);
      }

      .hero-action-link {
         background: #fdf2f0;
         color: var(--brown);
         border: 0.15rem solid #f5d0c8;
         padding: 1.2rem 2rem;
         border-radius: 1.1rem;
         font-size: 1.5rem;
         font-weight: 700;
         display: inline-flex;
         align-items: center;
         gap: 0.8rem;
      }

      .hero-action-link:hover {
         background: var(--brown);
         color: #fff;
      }

      /* 4 Thẻ KPI Đơn Hàng */
      .orders-kpi-grid {
         display: grid;
         grid-template-columns: repeat(4, 1fr);
         gap: 2rem;
         margin-bottom: 2.4rem;
      }

      .order-kpi-card {
         background: var(--white);
         border: var(--border);
         border-radius: 1.6rem;
         padding: 2.2rem;
         box-shadow: var(--box-shadow);
         display: flex;
         align-items: center;
         justify-content: space-between;
      }

      .order-kpi-card.kpi-all { border-top: 0.45rem solid #3b82f6; }
      .order-kpi-card.kpi-pending { border-top: 0.45rem solid #f59e0b; }
      .order-kpi-card.kpi-completed { border-top: 0.45rem solid #10b981; }
      .order-kpi-card.kpi-revenue { border-top: 0.45rem solid #ac5743; }

      .o-kpi-text span {
         display: block;
         font-size: 1.35rem;
         font-weight: 700;
         color: var(--light-color);
         text-transform: uppercase;
         margin-bottom: 0.6rem;
      }

      .o-kpi-text h3 {
         font-size: 2.7rem;
         font-weight: 700;
         color: var(--black);
      }

      .o-kpi-text h3 small {
         font-size: 1.4rem;
         color: var(--light-color);
      }

      .o-kpi-icon {
         width: 5.2rem;
         height: 5.2rem;
         border-radius: 1.3rem;
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 2.2rem;
         flex-shrink: 0;
      }

      .kpi-all .o-kpi-icon { background: #dbeafe; color: #2563eb; }
      .kpi-pending .o-kpi-icon { background: #fef3c7; color: #d97706; }
      .kpi-completed .o-kpi-icon { background: #d1fae5; color: #059669; }
      .kpi-revenue .o-kpi-icon { background: #fdf2f0; color: #ac5743; }

      /* Thanh Công Cụ Tìm Kiếm & Lọc Trạng Thái */
      .orders-toolbar {
         background: var(--white);
         border: var(--border);
         border-radius: 1.5rem;
         padding: 1.8rem 2.2rem;
         box-shadow: var(--box-shadow);
         display: flex;
         align-items: center;
         justify-content: space-between;
         flex-wrap: wrap;
         gap: 1.6rem;
         margin-bottom: 2.6rem;
      }

      .order-search-box {
         position: relative;
         flex: 1;
         min-width: 28rem;
      }

      .order-search-box i {
         position: absolute;
         left: 1.6rem;
         top: 50%;
         transform: translateY(-50%);
         font-size: 1.6rem;
         color: var(--light-color);
      }

      .order-search-box input {
         width: 100%;
         background: #f8fafc;
         border: 0.15rem solid #cbd5e1;
         border-radius: 1rem;
         padding: 1.2rem 1.6rem 1.2rem 4.4rem;
         font-size: 1.5rem;
         color: var(--black);
      }

      .order-search-box input:focus {
         background: #fff;
         border-color: var(--brown);
      }

      .order-filter-tabs {
         display: flex;
         align-items: center;
         gap: 0.8rem;
         flex-wrap: wrap;
      }

      .o-tab-btn {
         background: #f1f5f9;
         color: #475569;
         padding: 1rem 1.8rem;
         border-radius: 1rem;
         font-size: 1.45rem;
         font-weight: 700;
         cursor: pointer;
         transition: all 0.2s ease;
      }

      .o-tab-btn.active {
         background: var(--brown);
         color: #fff;
      }

      /* Lưới Thẻ Đơn Hàng Cỡ Lớn */
      .enterprise-orders-grid {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(40rem, 1fr));
         gap: 2.4rem;
      }

      .enterprise-order-card {
         background: var(--white);
         border: var(--border);
         border-top: 0.5rem solid #f59e0b;
         border-radius: 1.8rem;
         padding: 2.6rem;
         box-shadow: 0 1rem 2.4rem rgba(15, 23, 42, 0.06);
         display: flex;
         flex-direction: column;
         justify-content: space-between;
         transition: transform 0.2s ease;
      }

      .enterprise-order-card:hover {
         transform: translateY(-4px);
      }

      .enterprise-order-card.status-completed {
         border-top-color: #10b981;
      }

      .o-card-header {
         display: flex;
         align-items: center;
         justify-content: space-between;
         padding-bottom: 1.5rem;
         margin-bottom: 1.6rem;
         border-bottom: var(--border);
      }

      .o-code-group h3 {
         font-size: 2.1rem;
         font-weight: 700;
         color: var(--black);
         display: flex;
         align-items: center;
         gap: 0.8rem;
      }

      .o-code-group h3 i {
         color: var(--brown);
      }

      .o-date-sub {
         font-size: 1.35rem;
         color: var(--light-color);
         font-weight: 600;
         margin-top: 0.3rem;
         display: block;
      }

      .o-status-pill {
         padding: 0.7rem 1.4rem;
         border-radius: 2rem;
         font-size: 1.35rem;
         font-weight: 700;
         display: inline-flex;
         align-items: center;
         gap: 0.6rem;
      }

      .o-status-pill.pill-pending {
         background: #fef3c7;
         color: #b45309;
      }

      .o-status-pill.pill-completed {
         background: #d1fae5;
         color: #047857;
      }

      .o-customer-box {
         background: #f8fafc;
         border: 0.1rem solid #e2e8f0;
         border-radius: 1.2rem;
         padding: 1.6rem 1.8rem;
         margin-bottom: 1.6rem;
      }

      .o-row {
         display: flex;
         justify-content: space-between;
         align-items: flex-start;
         gap: 1.2rem;
         font-size: 1.5rem;
         padding: 0.65rem 0;
         border-bottom: 0.1rem dashed #e2e8f0;
      }

      .o-row:last-child {
         border-bottom: none;
      }

      .o-row-label {
         color: var(--light-color);
         font-weight: 600;
         white-space: nowrap;
         display: flex;
         align-items: center;
         gap: 0.7rem;
      }

      .o-row-label i {
         color: var(--brown);
         width: 1.8rem;
         text-align: center;
      }

      .o-row-val {
         color: var(--black);
         font-weight: 700;
         text-align: right;
         word-break: break-word;
      }

      .o-products-box {
         background: #fffdf9;
         border: 0.15rem solid #f5d0c8;
         border-radius: 1.2rem;
         padding: 1.4rem 1.6rem;
         margin-bottom: 1.6rem;
      }

      .o-products-label {
         font-size: 1.3rem;
         font-weight: 700;
         text-transform: uppercase;
         color: var(--brown);
         display: block;
         margin-bottom: 0.5rem;
      }

      .o-products-list {
         font-size: 1.55rem;
         font-weight: 700;
         color: var(--black);
         line-height: 1.55;
      }

      .o-total-banner {
         background: linear-gradient(135deg, #fdf2f0 0%, #fce8e4 100%);
         border-radius: 1.1rem;
         padding: 1.3rem 1.8rem;
         display: flex;
         align-items: center;
         justify-content: space-between;
         margin-bottom: 1.8rem;
      }

      .o-total-banner span {
         font-size: 1.5rem;
         font-weight: 700;
         color: var(--black);
      }

      .o-total-banner strong {
         font-size: 2.3rem;
         font-weight: 700;
         color: var(--red);
      }

      .o-update-form {
         border-top: var(--border);
         padding-top: 1.6rem;
      }

      .o-select-wrap {
         position: relative;
         margin-bottom: 1.2rem;
      }

      .o-select-wrap select {
         width: 100%;
         background: #f8fafc;
         border: 0.15rem solid #cbd5e1;
         border-radius: 1rem;
         padding: 1.2rem 1.4rem;
         font-size: 1.5rem;
         font-weight: 700;
         color: var(--black);
         cursor: pointer;
      }

      .o-action-grid {
         display: grid;
         grid-template-columns: 1.3fr 1fr 0.9fr;
         gap: 0.9rem;
      }

      .o-btn {
         padding: 1.15rem 1.2rem;
         border-radius: 0.9rem;
         font-size: 1.4rem;
         font-weight: 700;
         cursor: pointer;
         display: inline-flex;
         align-items: center;
         justify-content: center;
         gap: 0.6rem;
         text-align: center;
      }

      .o-btn-save {
         background: var(--brown);
         color: #fff;
      }

      .o-btn-print {
         background: #f1f5f9;
         color: #334155;
         border: 0.1rem solid #cbd5e1;
      }

      .o-btn-print:hover {
         background: var(--black);
         color: #fff;
      }

      .o-btn-del {
         background: #fee2e2;
         color: #dc2626;
      }

      .o-btn-del:hover {
         background: #dc2626;
         color: #fff;
      }

      .pro-empty-state {
         background: var(--white);
         border: 0.2rem dashed #cbd5e1;
         border-radius: 1.8rem;
         padding: 4.5rem 2.5rem;
         text-align: center;
         max-width: 68rem;
         margin: 1rem auto;
         box-shadow: var(--box-shadow);
      }

      .pro-empty-icon {
         width: 8rem;
         height: 8rem;
         border-radius: 50%;
         background: #fdf2f0;
         color: var(--brown);
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 3.4rem;
         margin: 0 auto 1.8rem;
      }

      .pro-empty-state h3 {
         font-size: 2.3rem;
         font-weight: 700;
         color: var(--black);
         margin-bottom: 0.8rem;
      }

      .pro-empty-state p {
         font-size: 1.55rem;
         color: var(--light-color);
         line-height: 1.6;
      }

      @media (max-width: 1024px) {
         .orders-kpi-grid { grid-template-columns: 1fr 1fr; }
      }
      @media (max-width: 600px) {
         .orders-kpi-grid { grid-template-columns: 1fr; }
         .enterprise-orders-grid { grid-template-columns: 1fr; }
         .o-action-grid { grid-template-columns: 1fr; }
      }
   </style>
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
         <style>
            body { font-family: Arial, sans-serif; padding: 30px; color: #1e293b; }
            .box { border: 2px solid #ac5743; border-radius: 12px; padding: 25px; }
            h2 { color: #ac5743; margin-top: 0; }
            .row { margin: 10px 0; font-size: 15px; }
            .items { background: #f8fafc; padding: 15px; border-radius: 8px; margin: 15px 0; font-weight: bold; }
            .total { font-size: 20px; color: #e74c3c; font-weight: bold; text-align: right; margin-top: 15px; }
         </style>
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
