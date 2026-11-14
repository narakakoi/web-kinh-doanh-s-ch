<?php
require_once '../config/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:../pages/login.php');
   exit();
};

// 1. Tính toán các chỉ số thống kê tổng quan
$total_pendings = 0;
try {
   $select_pendings = mysqli_query($conn, "SELECT * FROM `orders` WHERE payment_status = 'pending'");
   if ($select_pendings) {
      while($fetch_pendings = mysqli_fetch_assoc($select_pendings)){
         $total_pendings += (float)($fetch_pendings['total_price'] ?? 0);
      }
   }
} catch (Throwable $e) {}

$total_completes = 0;
try {
   $select_completes = mysqli_query($conn, "SELECT * FROM `orders` WHERE payment_status = 'completed'");
   if ($select_completes) {
      while($fetch_completes = mysqli_fetch_assoc($select_completes)){
         $total_completes += (float)($fetch_completes['total_price'] ?? 0);
      }
   }
} catch (Throwable $e) {}

$number_of_orders = 0;
try {
   $select_orders = mysqli_query($conn, "SELECT * FROM `orders`");
   if ($select_orders) $number_of_orders = mysqli_num_rows($select_orders);
} catch (Throwable $e) {}

$number_of_products = 0;
try {
   $select_products = mysqli_query($conn, "SELECT * FROM `products`");
   if ($select_products) $number_of_products = mysqli_num_rows($select_products);
} catch (Throwable $e) {}

$number_of_users = 0;
try {
   $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE user_type = 'user'");
   if ($select_users) $number_of_users = mysqli_num_rows($select_users);
} catch (Throwable $e) {}

$number_of_admin = 0;
try {
   $select_admin = mysqli_query($conn, "SELECT * FROM `users` WHERE user_type = 'admin'");
   if ($select_admin) $number_of_admin = mysqli_num_rows($select_admin);
} catch (Throwable $e) {}

$number_of_account = 0;
try {
   $select_account = mysqli_query($conn, "SELECT * FROM `users`");
   if ($select_account) $number_of_account = mysqli_num_rows($select_account);
} catch (Throwable $e) {}

$number_of_messages = 0;
try {
   $select_messages = mysqli_query($conn, "SELECT * FROM `message`");
   if ($select_messages) $number_of_messages = mysqli_num_rows($select_messages);
} catch (Throwable $e) {}

// 2. Lấy dữ liệu thật từ Database cho Biểu đồ cơ cấu thể loại sách
$category_labels = [];
$category_counts = [];
try {
   $cat_query = mysqli_query($conn, "SELECT category, COUNT(*) as total FROM `products` GROUP BY category ORDER BY total DESC");
   if ($cat_query && mysqli_num_rows($cat_query) > 0) {
      while ($row = mysqli_fetch_assoc($cat_query)) {
         $category_labels[] = $row['category'] ?: 'Chưa phân loại';
         $category_counts[] = (int)$row['total'];
      }
   }
} catch (Throwable $e) {}

if (empty($category_labels)) {
   $category_labels = ['Sách tham khảo', 'Văn học', 'Kỹ năng sống'];
   $category_counts = [10, 10, 10];
}

// 3. Lấy dữ liệu doanh thu theo ngày đặt hàng (nếu có đơn) hoặc hiển thị biểu đồ so sánh dòng tiền
$revenue_labels = [];
$revenue_values = [];
$has_real_orders = false;
try {
   $rev_query = mysqli_query($conn, "SELECT placed_on, SUM(total_price) as daily_rev FROM `orders` GROUP BY placed_on ORDER BY id ASC LIMIT 7");
   if ($rev_query && mysqli_num_rows($rev_query) > 0) {
      $has_real_orders = true;
      while ($row = mysqli_fetch_assoc($rev_query)) {
         $revenue_labels[] = $row['placed_on'] ?: 'Hôm nay';
         $revenue_values[] = (float)$row['daily_rev'];
      }
   }
} catch (Throwable $e) {}

if (!$has_real_orders) {
   $total_inventory_value = 0;
   try {
      $inv_q = mysqli_query($conn, "SELECT SUM(price) as total_val FROM `products`");
      if ($inv_q && $r = mysqli_fetch_assoc($inv_q)) {
         $total_inventory_value = (float)($r['total_val'] ?? 0);
      }
   } catch (Throwable $e) {}
   $revenue_labels = ['Doanh thu thực nhận', 'Doanh thu chờ duyệt', 'Tổng giá trị đầu sách kho'];
   $revenue_values = [$total_completes, $total_pendings, $total_inventory_value];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Bảng Điều Khiển - Quản Trị BookStory</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
   <!-- Thư viện vẽ biểu đồ chuyên nghiệp Chart.js -->
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<section class="dashboard">
   <div class="dashboard-banner">
      <div class="banner-text">
         <h1 class="title">Tổng Quan Kinh Doanh</h1>
         <p class="subtitle">Xin chào <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></strong>, dưới đây là số liệu vận hành hệ thống hôm nay.</p>
      </div>
      <div class="banner-actions">
         <a href="admin_products.php" class="btn"><i class="fas fa-plus-circle"></i> Thêm sách mới</a>
         <a href="admin_orders.php" class="option-btn"><i class="fas fa-clipboard-list"></i> Duyệt đơn hàng</a>
      </div>
   </div>

   <!-- 8 Thẻ chỉ số KPI -->
   <div class="box-container">
      <div class="box stat-card card-warning">
         <div class="stat-header">
            <span class="stat-label">Doanh thu chờ duyệt</span>
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
         </div>
         <h3><?php echo number_format($total_pendings, 0, ',', '.'); ?> <small>VNĐ</small></h3>
         <p><a href="admin_orders.php">Tổng thanh toán đang chờ <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-success">
         <div class="stat-header">
            <span class="stat-label">Doanh thu thực nhận</span>
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
         </div>
         <h3><?php echo number_format($total_completes, 0, ',', '.'); ?> <small>VNĐ</small></h3>
         <p><a href="admin_orders.php">Thanh toán hoàn thành <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-primary">
         <div class="stat-header">
            <span class="stat-label">Tổng số đơn hàng</span>
            <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
         </div>
         <h3><?php echo $number_of_orders; ?> <small>đơn</small></h3>
         <p><a href="admin_orders.php">Đơn hàng đã đặt <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-brown">
         <div class="stat-header">
            <span class="stat-label">Kho sách hiện có</span>
            <div class="stat-icon"><i class="fas fa-book"></i></div>
         </div>
         <h3><?php echo $number_of_products; ?> <small>sản phẩm</small></h3>
         <p><a href="admin_products.php">Sản phẩm đã thêm <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-info">
         <div class="stat-header">
            <span class="stat-label">Khách hàng thành viên</span>
            <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
         </div>
         <h3><?php echo $number_of_users; ?> <small>khách</small></h3>
         <p><a href="admin_users.php">Người dùng <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-purple">
         <div class="stat-header">
            <span class="stat-label">Nhân sự vận hành</span>
            <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
         </div>
         <h3><?php echo $number_of_admin; ?> <small>admin</small></h3>
         <p><a href="admin_users.php">Người dùng quản trị <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-teal">
         <div class="stat-header">
            <span class="stat-label">Toàn bộ hệ thống</span>
            <div class="stat-icon"><i class="fas fa-id-card"></i></div>
         </div>
         <h3><?php echo $number_of_account; ?> <small>tài khoản</small></h3>
         <p><a href="admin_users.php">Tổng tài khoản <i class="fas fa-arrow-right"></i></a></p>
      </div>

      <div class="box stat-card card-danger">
         <div class="stat-header">
            <span class="stat-label">Phản hồi khách hàng</span>
            <div class="stat-icon"><i class="fas fa-comments"></i></div>
         </div>
         <h3><?php echo $number_of_messages; ?> <small>tin nhắn</small></h3>
         <p><a href="admin_contacts.php">Tin nhắn mới <i class="fas fa-arrow-right"></i></a></p>
      </div>
   </div>

   <!-- Khu vực Biểu đồ trực quan (Analytics Charts) -->
   <div class="analytics-grid">
      <!-- Biểu đồ 1: Sơ đồ doanh thu & dòng tiền -->
      <div class="chart-card">
         <div class="chart-header">
            <h3><i class="fas fa-chart-bar"></i> <?php echo $has_real_orders ? 'Biểu Đồ Doanh Thu Theo Ngày Đặt Hàng' : 'Sơ Đồ Đối Chiếu Doanh Thu & Giá Trị Kho Sách'; ?></h3>
            <span class="chart-badge">Đơn vị: VNĐ</span>
         </div>
         <div class="chart-canvas-wrap">
            <canvas id="revenueChart"></canvas>
         </div>
      </div>

      <!-- Biểu đồ 2: Cơ cấu đầu sách theo thể loại -->
      <div class="chart-card">
         <div class="chart-header">
            <h3><i class="fas fa-chart-pie"></i> Cơ Cấu Kho Sách Theo Thể Loại</h3>
            <span class="chart-badge"><?php echo $number_of_products; ?> đầu sách</span>
         </div>
         <div class="chart-canvas-wrap">
            <canvas id="categoryChart"></canvas>
         </div>
      </div>
   </div>
</section>

<script src="../js/admin_script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
   Chart.defaults.font.family = "'Quicksand', sans-serif";
   Chart.defaults.font.size = 13;
   Chart.defaults.color = '#64748b';

   // 1. Vẽ biểu đồ cột Doanh thu
   const revCtx = document.getElementById('revenueChart').getContext('2d');
   new Chart(revCtx, {
      type: 'bar',
      data: {
         labels: <?php echo json_encode($revenue_labels, JSON_UNESCAPED_UNICODE); ?>,
         datasets: [{
            label: 'Giá trị (VNĐ)',
            data: <?php echo json_encode($revenue_values); ?>,
            backgroundColor: [
               'rgba(16, 185, 129, 0.85)',
               'rgba(245, 158, 11, 0.85)',
               'rgba(172, 87, 67, 0.88)',
               'rgba(59, 130, 246, 0.85)',
               'rgba(139, 92, 246, 0.85)'
            ],
            borderRadius: 8,
            barThickness: 46
         }]
      },
      options: {
         responsive: true,
         maintainAspectRatio: false,
         plugins: {
            legend: { display: false },
            tooltip: {
               callbacks: {
                  label: function(context) {
                     return ' ' + Number(context.raw).toLocaleString('vi-VN') + ' VNĐ';
                  }
               }
            }
         },
         scales: {
            y: {
               beginAtZero: true,
               grid: { color: '#f1f5f9' },
               ticks: {
                  callback: function(value) {
                     return Number(value).toLocaleString('vi-VN') + ' đ';
                  }
               }
            },
            x: {
               grid: { display: false }
            }
         }
      }
   });

   // 2. Vẽ biểu đồ tròn Cơ cấu thể loại sách kèm %
   const rawLabels = <?php echo json_encode($category_labels, JSON_UNESCAPED_UNICODE); ?>;
   const rawCounts = <?php echo json_encode($category_counts); ?>;
   const totalBooks = rawCounts.reduce((acc, val) => acc + Number(val), 0);

   // Tạo nhãn chú thích có kèm sẵn % ở bên dưới
   const labelsWithPercent = rawLabels.map((label, idx) => {
      const pct = totalBooks > 0 ? ((rawCounts[idx] / totalBooks) * 100).toFixed(1).replace('.', ',') : 0;
      return `${label} (${pct}%)`;
   });

   // Plugin vẽ trực tiếp số % lên trên từng múi của biểu đồ tròn
   const doughnutPercentPlugin = {
      id: 'doughnutPercentPlugin',
      afterDatasetsDraw(chart) {
         const { ctx, data } = chart;
         const dataset = data.datasets[0];
         const meta = chart.getDatasetMeta(0);
         const total = dataset.data.reduce((a, b) => a + Number(b), 0);

         ctx.save();
         meta.data.forEach((arc, index) => {
            const val = Number(dataset.data[index]);
            if (!val || total === 0) return;
            const pct = (val / total) * 100;
            if (pct < 5) return; // Ẩn nếu múi quá nhỏ để không đè chữ

            const pos = arc.tooltipPosition();
            ctx.fillStyle = '#ffffff';
            ctx.font = "bold 13px 'Quicksand', sans-serif";
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(pct.toFixed(1).replace('.', ',') + '%', pos.x, pos.y);
         });
         ctx.restore();
      }
   };

   const catCtx = document.getElementById('categoryChart').getContext('2d');
   new Chart(catCtx, {
      type: 'doughnut',
      data: {
         labels: labelsWithPercent,
         datasets: [{
            data: rawCounts,
            backgroundColor: [
               '#ac5743',
               '#f59e0b',
               '#10b981',
               '#3b82f6',
               '#8b5cf6',
               '#06b6d4',
               '#ec4899'
            ],
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: 6
         }]
      },
      options: {
         responsive: true,
         maintainAspectRatio: false,
         cutout: '58%',
         plugins: {
            legend: {
               position: 'bottom',
               labels: {
                  padding: 15,
                  usePointStyle: true,
                  pointStyle: 'circle',
                  font: {
                     size: 13,
                     weight: '600'
                  }
               }
            },
            tooltip: {
               callbacks: {
                  label: function(context) {
                     const val = Number(context.raw);
                     const pct = totalBooks > 0 ? ((val / totalBooks) * 100).toFixed(1).replace('.', ',') : 0;
                     const cleanName = rawLabels[context.dataIndex] || '';
                     return ` ${cleanName}: ${val} cuốn sách (${pct}%)`;
                  }
               }
            }
         }
      },
      plugins: [doughnutPercentPlugin]
   });
});
</script>
</body>
</html>
