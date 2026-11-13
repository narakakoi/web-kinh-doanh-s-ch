<?php
@include '../config/config.php';

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
   <style>
      /* =========================================================
         CSS TRUNG TÂM CHĂM SÓC KHÁCH HÀNG & HELPDESK CHUYÊN NGHIỆP
         ========================================================= */
      .helpdesk-section {
         max-width: 1300px;
         margin: 0 auto;
         padding: 2.8rem 2rem 5rem;
      }

      .helpdesk-hero-bar {
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

      .helpdesk-hero-title h1 {
         font-size: 2.8rem;
         font-weight: 700;
         color: var(--black);
         margin-bottom: 0.5rem;
         display: flex;
         align-items: center;
         gap: 1.2rem;
      }

      .helpdesk-hero-title h1 i {
         color: var(--brown);
      }

      .helpdesk-hero-title p {
         font-size: 1.55rem;
         color: var(--light-color);
      }

      .hd-online-pill {
         background: #d1fae5;
         color: #047857;
         border: 0.15rem solid #a7f3d0;
         padding: 1.1rem 1.8rem;
         border-radius: 2rem;
         font-size: 1.45rem;
         font-weight: 700;
         display: inline-flex;
         align-items: center;
         gap: 0.8rem;
      }

      /* 3 Thẻ KPI Hộp Thư */
      .helpdesk-kpi-grid {
         display: grid;
         grid-template-columns: repeat(3, 1fr);
         gap: 2.2rem;
         margin-bottom: 2.4rem;
      }

      .hd-kpi-card {
         background: var(--white);
         border: var(--border);
         border-radius: 1.6rem;
         padding: 2.2rem 2.6rem;
         box-shadow: var(--box-shadow);
         display: flex;
         align-items: center;
         justify-content: space-between;
      }

      .hd-kpi-card.kpi-inbox { border-left: 0.5rem solid #ac5743; }
      .hd-kpi-card.kpi-senders { border-left: 0.5rem solid #3b82f6; }
      .hd-kpi-card.kpi-sla { border-left: 0.5rem solid #10b981; }

      .hd-kpi-info span {
         display: block;
         font-size: 1.4rem;
         font-weight: 700;
         color: var(--light-color);
         text-transform: uppercase;
         margin-bottom: 0.6rem;
      }

      .hd-kpi-info h3 {
         font-size: 2.9rem;
         font-weight: 700;
         color: var(--black);
      }

      .hd-kpi-info h3 small {
         font-size: 1.45rem;
         color: var(--light-color);
      }

      .hd-kpi-icon {
         width: 5.6rem;
         height: 5.6rem;
         border-radius: 1.4rem;
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 2.4rem;
      }

      .kpi-inbox .hd-kpi-icon { background: #fdf2f0; color: #ac5743; }
      .kpi-senders .hd-kpi-icon { background: #dbeafe; color: #2563eb; }
      .kpi-sla .hd-kpi-icon { background: #d1fae5; color: #059669; }

      /* Thanh tìm kiếm tin nhắn */
      .helpdesk-toolbar {
         background: var(--white);
         border: var(--border);
         border-radius: 1.5rem;
         padding: 1.6rem 2.2rem;
         box-shadow: var(--box-shadow);
         margin-bottom: 2.6rem;
         display: flex;
         align-items: center;
         justify-content: space-between;
         gap: 1.6rem;
         flex-wrap: wrap;
      }

      .hd-search-wrap {
         position: relative;
         flex: 1;
         min-width: 28rem;
      }

      .hd-search-wrap i {
         position: absolute;
         left: 1.6rem;
         top: 50%;
         transform: translateY(-50%);
         font-size: 1.6rem;
         color: var(--light-color);
      }

      .hd-search-wrap input {
         width: 100%;
         background: #f8fafc;
         border: 0.15rem solid #cbd5e1;
         border-radius: 1rem;
         padding: 1.25rem 1.6rem 1.25rem 4.4rem;
         font-size: 1.5rem;
         color: var(--black);
      }

      .hd-search-wrap input:focus {
         background: #fff;
         border-color: var(--brown);
      }

      .hd-badge-pill {
         background: #fdf2f0;
         color: var(--brown);
         padding: 1rem 1.6rem;
         border-radius: 1rem;
         font-size: 1.4rem;
         font-weight: 700;
      }

      /* Lưới Thẻ Tin Nhắn Cỡ Lớn */
      .enterprise-tickets-grid {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(38rem, 1fr));
         gap: 2.4rem;
      }

      .enterprise-ticket-card {
         background: var(--white);
         border: var(--border);
         border-top: 0.5rem solid var(--brown);
         border-radius: 1.8rem;
         padding: 2.6rem;
         box-shadow: 0 1rem 2.4rem rgba(15, 23, 42, 0.06);
         display: flex;
         flex-direction: column;
         justify-content: space-between;
         transition: transform 0.2s ease;
      }

      .enterprise-ticket-card:hover {
         transform: translateY(-4px);
      }

      .t-card-header {
         display: flex;
         align-items: center;
         justify-content: space-between;
         gap: 1.2rem;
         padding-bottom: 1.6rem;
         margin-bottom: 1.6rem;
         border-bottom: var(--border);
      }

      .t-sender-box {
         display: flex;
         align-items: center;
         gap: 1.4rem;
      }

      .t-avatar {
         width: 5.6rem;
         height: 5.6rem;
         border-radius: 1.4rem;
         background: #fdf2f0;
         color: var(--brown);
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 2.4rem;
         flex-shrink: 0;
      }

      .t-sender-meta h3 {
         font-size: 2rem;
         font-weight: 700;
         color: var(--black);
         margin-bottom: 0.3rem;
      }

      .t-sender-meta span {
         font-size: 1.35rem;
         color: var(--light-color);
         font-weight: 600;
      }

      .t-ticket-badge {
         background: #d1fae5;
         color: #047857;
         padding: 0.6rem 1.2rem;
         border-radius: 2rem;
         font-size: 1.3rem;
         font-weight: 700;
         white-space: nowrap;
      }

      .t-contact-info {
         background: #f8fafc;
         border: 0.1rem solid #e2e8f0;
         border-radius: 1.2rem;
         padding: 1.4rem 1.6rem;
         margin-bottom: 1.6rem;
      }

      .t-contact-row {
         display: flex;
         align-items: center;
         justify-content: space-between;
         font-size: 1.5rem;
         padding: 0.5rem 0;
      }

      .t-contact-row span {
         color: var(--light-color);
         font-weight: 600;
         display: flex;
         align-items: center;
         gap: 0.7rem;
      }

      .t-contact-row span i {
         color: var(--brown);
         width: 1.8rem;
      }

      .t-contact-row strong {
         color: var(--black);
         font-weight: 700;
      }

      .t-message-bubble {
         background: #fffdf9;
         border: 0.15rem solid #f5d0c8;
         border-left: 0.45rem solid var(--brown);
         border-radius: 1.2rem;
         padding: 1.6rem;
         margin-bottom: 2rem;
         flex: 1;
      }

      .t-message-label {
         display: block;
         font-size: 1.3rem;
         font-weight: 700;
         text-transform: uppercase;
         color: var(--brown);
         margin-bottom: 0.6rem;
      }

      .t-message-bubble p {
         font-size: 1.6rem;
         color: var(--black);
         line-height: 1.65;
         font-weight: 500;
      }

      .t-actions-grid {
         display: grid;
         grid-template-columns: 1.2fr 1fr 1fr;
         gap: 0.9rem;
      }

      .t-btn {
         padding: 1.15rem 1rem;
         border-radius: 0.9rem;
         font-size: 1.4rem;
         font-weight: 700;
         display: inline-flex;
         align-items: center;
         justify-content: center;
         gap: 0.6rem;
         cursor: pointer;
         text-align: center;
      }

      .t-btn-reply {
         background: var(--brown);
         color: #fff;
      }

      .t-btn-call {
         background: #f1f5f9;
         color: #334155;
         border: 0.1rem solid #cbd5e1;
      }

      .t-btn-call:hover {
         background: var(--black);
         color: #fff;
      }

      .t-btn-del {
         background: #fee2e2;
         color: #dc2626;
      }

      .t-btn-del:hover {
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

      @media (max-width: 991px) {
         .helpdesk-kpi-grid { grid-template-columns: 1fr; }
      }
      @media (max-width: 600px) {
         .enterprise-tickets-grid { grid-template-columns: 1fr; }
         .t-actions-grid { grid-template-columns: 1fr; }
      }
   </style>
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
