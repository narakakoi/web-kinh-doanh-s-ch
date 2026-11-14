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

// 1. Xóa tin nhắn
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM `message` WHERE id = '$delete_id'") or die('Truy vấn thất bại');
    header('location:admin_contacts.php');
    exit();
}

// 2. Lấy danh sách tin nhắn
$messages_list = [];
$unique_senders = [];
$select_message = mysqli_query($conn, "SELECT * FROM `message` ORDER BY id DESC") or die('Truy vấn thất bại');
while ($row = mysqli_fetch_assoc($select_message)) {
    $messages_list[] = $row;
    if (!empty($row['email'])) {
        $unique_senders[$row['email']] = true;
    }
}
$total_messages = count($messages_list);
$total_senders = count($unique_senders);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Trung Tâm CSKH & Hộp Thư - BookStory Admin</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<section class="helpdesk-section">
   <!-- 1. Banner Tiêu Đề -->
   <div class="helpdesk-hero-bar">
      <div class="helpdesk-hero-title">
         <h1><i class="fas fa-headset"></i> Trung Tâm Chăm Sóc Khách Hàng & Hộp Thư</h1>
         <p>Tiếp nhận yêu cầu tư vấn sách, hỗ trợ mã giảm giá và giải đáp phản hồi người mua</p>
      </div>
      <span class="hd-online-pill">
         <i class="fas fa-check-circle"></i> Hệ thống tiếp nhận trực tuyến
      </span>
   </div>

   <!-- 2. 3 Thẻ KPI Hộp Thư -->
   <div class="helpdesk-kpi-grid">
      <div class="hd-kpi-card kpi-inbox">
         <div class="hd-kpi-info">
            <span>Tổng phiếu phản hồi</span>
            <h3><?php echo $total_messages; ?> <small>tin nhắn</small></h3>
         </div>
         <div class="hd-kpi-icon"><i class="fas fa-envelope-open-text"></i></div>
      </div>

      <div class="hd-kpi-card kpi-senders">
         <div class="hd-kpi-info">
            <span>Khách hàng liên hệ</span>
            <h3><?php echo $total_senders; ?> <small>khách hàng</small></h3>
         </div>
         <div class="hd-kpi-icon"><i class="fas fa-user-friends"></i></div>
      </div>

      <div class="hd-kpi-card kpi-sla">
         <div class="hd-kpi-info">
            <span>Trạng thái kênh CSKH</span>
            <h3>24/7 <small>hoạt động</small></h3>
         </div>
         <div class="hd-kpi-icon"><i class="fas fa-bolt"></i></div>
      </div>
   </div>

   <!-- 3. Thanh Tìm Kiếm Tin Nhắn -->
   <div class="helpdesk-toolbar">
      <div class="hd-search-wrap">
         <i class="fas fa-search"></i>
         <input type="text" id="msgSearchInput" placeholder="Tìm nhanh theo tên khách hàng, số điện thoại, email hoặc nội dung câu hỏi...">
      </div>
      <div class="hd-badge-pill">
         <i class="fas fa-inbox"></i> Đang hiển thị: <strong><?php echo $total_messages; ?></strong> phiếu hỗ trợ
      </div>
   </div>

   <!-- 4. Danh Sách Thẻ Tin Nhắn -->
   <?php if ($total_messages > 0): ?>
      <div class="enterprise-tickets-grid" id="ticketsGrid">
         <?php foreach ($messages_list as $fetch_message): ?>
            <?php
               $mid = (int)$fetch_message['id'];
               $search_blob = mb_strtolower(
                  ($fetch_message['name'] ?? '') . ' ' .
                  ($fetch_message['number'] ?? '') . ' ' .
                  ($fetch_message['email'] ?? '') . ' ' .
                  ($fetch_message['message'] ?? ''),
                  'UTF-8'
               );
            ?>
            <div class="enterprise-ticket-card" data-search="<?php echo htmlspecialchars($search_blob); ?>">
               <div>
                  <div class="t-card-header">
                     <div class="t-sender-box">
                        <div class="t-avatar"><i class="fas fa-user-circle"></i></div>
                        <div class="t-sender-meta">
                           <h3><?php echo htmlspecialchars($fetch_message['name']); ?></h3>
                           <span>Mã khách hàng: #<?php echo (int)$fetch_message['user_id']; ?></span>
                        </div>
                     </div>
                     <span class="t-ticket-badge"><i class="fas fa-ticket-alt"></i> Phiếu #<?php echo $mid; ?></span>
                  </div>

                  <div class="t-contact-info">
                     <div class="t-contact-row">
                        <span><i class="fas fa-phone-alt"></i> Điện thoại:</span>
                        <strong><?php echo htmlspecialchars($fetch_message['number']); ?></strong>
                     </div>
                     <div class="t-contact-row">
                        <span><i class="fas fa-envelope"></i> Email:</span>
                        <strong><?php echo htmlspecialchars($fetch_message['email']); ?></strong>
                     </div>
                  </div>

                  <div class="t-message-bubble">
                     <span class="t-message-label"><i class="fas fa-comment-dots"></i> Nội dung khách hàng gửi:</span>
                     <p><?php echo nl2br(htmlspecialchars($fetch_message['message'])); ?></p>
                  </div>
               </div>

               <div class="t-actions-grid">
                  <a href="mailto:<?php echo htmlspecialchars($fetch_message['email']); ?>?subject=BookStory%20CSKH%20-%20Phản%20hồi%20phiếu%20hỗ%20trợ%20%23<?php echo $mid; ?>"
                     class="t-btn t-btn-reply">
                     <i class="fas fa-reply"></i> Gửi Email
                  </a>
                  <a href="tel:<?php echo htmlspecialchars($fetch_message['number']); ?>"
                     class="t-btn t-btn-call">
                     <i class="fas fa-phone"></i> Gọi Điện
                  </a>
                  <a href="admin_contacts.php?delete=<?php echo $mid; ?>"
                     onclick="return confirm('Xác nhận đã xử lý xong và xóa tin nhắn này?');"
                     class="t-btn t-btn-del">
                     <i class="fas fa-trash-alt"></i> Xóa
                  </a>
               </div>
            </div>
         <?php endforeach; ?>
      </div>
   <?php else: ?>
      <div class="pro-empty-state">
         <div class="pro-empty-icon"><i class="fas fa-comments"></i></div>
         <h3>Hộp Thư Phản Hồi Hiện Đang Trống</h3>
         <p>Mọi tin nhắn hoặc câu hỏi do khách hàng gửi từ trang Liên Hệ sẽ tự động được ghi nhận và hiển thị tại đây.</p>
      </div>
   <?php endif; ?>
</section>

<script src="../js/admin_script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
   const searchInput = document.getElementById('msgSearchInput');
   const cards = document.querySelectorAll('.enterprise-ticket-card');

   if (searchInput) {
      searchInput.addEventListener('input', function () {
         const kw = this.value.trim().toLowerCase();
         cards.forEach(card => {
            const text = card.getAttribute('data-search') || '';
            card.style.display = text.includes(kw) ? 'flex' : 'none';
         });
      });
   }
});
</script>
</body>
</html>
