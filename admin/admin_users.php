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

// 1. Tạo tài khoản mới trực tiếp từ trang Admin
if (isset($_POST['add_user_account'])) {
    $new_name = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $new_email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $new_pass = md5(trim($_POST['password'] ?? ''));
    $new_type = in_array($_POST['user_type'] ?? '', ['user', 'admin']) ? $_POST['user_type'] : 'user';

    if ($new_name !== '' && $new_email !== '') {
        $check_u = mysqli_query($conn, "SELECT id FROM `users` WHERE email = '$new_email'");
        if ($check_u && mysqli_num_rows($check_u) > 0) {
            $message[] = 'Email này đã được đăng ký trên hệ thống!';
        } else {
            mysqli_query($conn, "INSERT INTO `users`(name, email, password, user_type) VALUES('$new_name', '$new_email', '$new_pass', '$new_type')");
            $message[] = 'Đã tạo tài khoản ' . htmlspecialchars($new_name) . ' thành công!';
        }
    }
}

// 2. Đổi quyền nhanh (Khách hàng <-> Quản trị viên)
if (isset($_GET['toggle_role'])) {
    $target_id = (int)$_GET['toggle_role'];
    if ($target_id > 0 && $target_id != (int)$admin_id) {
        $res = mysqli_query($conn, "SELECT user_type FROM `users` WHERE id = '$target_id'");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $next_role = ($row['user_type'] === 'admin') ? 'user' : 'admin';
            mysqli_query($conn, "UPDATE `users` SET user_type = '$next_role' WHERE id = '$target_id'");
        }
    }
    header('location:admin_users.php');
    exit();
}

// 3. Xóa tài khoản
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    if ($delete_id > 0 && $delete_id != (int)$admin_id) {
        mysqli_query($conn, "DELETE FROM `users` WHERE id = '$delete_id'") or die('Xóa người dùng thất bại');
    }
    header('location:admin_users.php');
    exit();
}

// 4. Lấy thống kê đơn hàng & chi tiêu của từng user
$user_order_stats = [];
try {
    $stat_q = mysqli_query($conn, "SELECT user_id, COUNT(*) AS total_orders, COALESCE(SUM(total_price), 0) AS total_spent FROM `orders` GROUP BY user_id");
    if ($stat_q) {
        while ($s = mysqli_fetch_assoc($stat_q)) {
            $user_order_stats[(int)$s['user_id']] = [
                'orders' => (int)$s['total_orders'],
                'spent'  => (float)$s['total_spent']
            ];
        }
    }
} catch (Throwable $e) {}

// 5. Lấy danh sách toàn bộ tài khoản
$users_list = [];
$total_admins = 0;
$total_customers = 0;

$select_users = mysqli_query($conn, "SELECT * FROM `users` ORDER BY id DESC") or die('Truy vấn thất bại');
while ($u = mysqli_fetch_assoc($select_users)) {
    $users_list[] = $u;
    if (($u['user_type'] ?? '') === 'admin') {
        $total_admins++;
    } else {
        $total_customers++;
    }
}
$total_all = count($users_list);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Tài Khoản Hệ Thống - BookStory Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<section class="users-management-section">
    <!-- 1. Banner Tiêu Đề & Nút Mở Form Tạo Tài Khoản -->
    <div class="users-hero-bar">
        <div class="users-hero-title">
            <h1><i class="fas fa-users-cog"></i> Quản Lý Tài Khoản & Phân Quyền Hệ Thống</h1>
            <p>Giám sát thông tin thành viên, lịch sử chi tiêu và cấp quyền vận hành cửa hàng BookStory</p>
        </div>
        <button type="button" class="open-create-btn" id="toggleCreateUserBtn">
            <i class="fas fa-user-plus"></i> Tạo Tài Khoản Mới
        </button>
    </div>

    <!-- 2. Khung Tạo Tài Khoản Nhanh -->
    <div class="quick-create-panel" id="quickCreatePanel">
        <form method="POST" action="">
            <div class="quick-create-header">
                <h3><i class="fas fa-user-shield" style="color:var(--brown);"></i> Thêm Tài Khoản Người Dùng / Quản Trị Viên Mới</h3>
            </div>
            <div class="quick-create-grid">
                <div class="qc-field">
                    <label>Họ và tên hiển thị</label>
                    <input type="text" name="name" placeholder="VD: Nguyễn Văn A" required>
                </div>
                <div class="qc-field">
                    <label>Địa chỉ Email đăng nhập</label>
                    <input type="email" name="email" placeholder="VD: khachhang@gmail.com" required>
                </div>
                <div class="qc-field">
                    <label>Mật khẩu khởi tạo</label>
                    <input type="password" name="password" placeholder="Nhập mật khẩu..." required>
                </div>
                <div class="qc-field">
                    <label>Vai trò hệ thống</label>
                    <select name="user_type">
                        <option value="user">Khách hàng thành viên (User)</option>
                        <option value="admin">Quản trị viên (Admin)</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_user_account" class="btn" style="margin-top:1.8rem; width:100%; padding:1.3rem; font-size:1.6rem;">
                <i class="fas fa-check-circle"></i> Lưu & Kích Hoạt Tài Khoản
            </button>
        </form>
    </div>

    <!-- 3. Ba Thẻ Thống Kê Nhanh (KPIs) -->
    <div class="users-kpi-grid">
        <div class="user-kpi-card kpi-total">
            <div class="kpi-info">
                <span>Tổng tài khoản hệ thống</span>
                <h3><?php echo $total_all; ?> <small>tài khoản</small></h3>
            </div>
            <div class="kpi-icon-box"><i class="fas fa-id-card-alt"></i></div>
        </div>

        <div class="user-kpi-card kpi-admin">
            <div class="kpi-info">
                <span>Ban quản trị (Admin)</span>
                <h3><?php echo $total_admins; ?> <small>quản trị viên</small></h3>
            </div>
            <div class="kpi-icon-box"><i class="fas fa-user-shield"></i></div>
        </div>

        <div class="user-kpi-card kpi-customer">
            <div class="kpi-info">
                <span>Khách hàng thành viên</span>
                <h3><?php echo $total_customers; ?> <small>khách hàng</small></h3>
            </div>
            <div class="kpi-icon-box"><i class="fas fa-user-check"></i></div>
        </div>
    </div>

    <!-- 4. Thanh Tìm Kiếm & Lọc Theo Vai Trò -->
    <div class="users-Filter-toolbar">
        <div class="user-search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="userSearchInput" placeholder="Tìm nhanh theo tên tài khoản, địa chỉ email hoặc mã ID...">
        </div>

        <div class="role-filter-tabs">
            <button type="button" class="role-tab-btn active" data-role="all">
                Tất cả (<?php echo $total_all; ?>)
            </button>
            <button type="button" class="role-tab-btn" data-role="admin">
                Quản trị viên (<?php echo $total_admins; ?>)
            </button>
            <button type="button" class="role-tab-btn" data-role="user">
                Khách hàng (<?php echo $total_customers; ?>)
            </button>
        </div>
    </div>

    <!-- 5. Danh Sách Thẻ Hồ Sơ Tài Khoản Cỡ Lớn -->
    <div class="enterprise-users-grid" id="usersGrid">
        <?php if ($total_all > 0): ?>
            <?php foreach ($users_list as $fetch_users): ?>
                <?php
                    $uid = (int)$fetch_users['id'];
                    $is_admin = (($fetch_users['user_type'] ?? '') === 'admin');
                    $u_orders = $user_order_stats[$uid]['orders'] ?? 0;
                    $u_spent = $user_order_stats[$uid]['spent'] ?? 0;
                    $search_blob = mb_strtolower($fetch_users['name'] . ' ' . $fetch_users['email'] . ' #' . $uid, 'UTF-8');
                ?>
                <div class="enterprise-user-card <?php echo $is_admin ? 'is-admin-card' : ''; ?>"
                     data-role="<?php echo $is_admin ? 'admin' : 'user'; ?>"
                     data-search="<?php echo htmlspecialchars($search_blob); ?>">
                    <div>
                        <div class="u-card-top">
                            <div class="u-identity">
                                <div class="u-avatar-circle">
                                    <i class="fas <?php echo $is_admin ? 'fa-user-shield' : 'fa-user'; ?>"></i>
                                </div>
                                <div class="u-title-block">
                                    <h3><?php echo htmlspecialchars($fetch_users['name']); ?></h3>
                                    <span class="u-id-pill">Mã tài khoản: #<?php echo $uid; ?></span>
                                </div>
                            </div>

                            <span class="u-role-badge <?php echo $is_admin ? 'badge-admin' : 'badge-user'; ?>">
                                <i class="fas <?php echo $is_admin ? 'fa-crown' : 'fa-user-tag'; ?>"></i>
                                <?php echo $is_admin ? 'Quản Trị Viên' : 'Khách Hàng'; ?>
                            </span>
                        </div>

                        <div class="u-info-list">
                            <div class="u-info-row">
                                <span class="u-info-label"><i class="fas fa-envelope"></i> Email đăng nhập:</span>
                                <span class="u-info-val"><?php echo htmlspecialchars($fetch_users['email']); ?></span>
                            </div>
                            <div class="u-info-row">
                                <span class="u-info-label"><i class="fas fa-shopping-bag"></i> Đơn hàng đã đặt:</span>
                                <span class="u-info-val"><?php echo $u_orders; ?> đơn hàng</span>
                            </div>
                            <div class="u-info-row">
                                <span class="u-info-label"><i class="fas fa-coins"></i> Tổng chi tiêu:</span>
                                <span class="u-info-val" style="color:var(--brown);"><?php echo number_format($u_spent, 0, ',', '.'); ?> VNĐ</span>
                            </div>
                        </div>
                    </div>

                    <?php if ($uid !== (int)$admin_id): ?>
                        <div class="u-card-actions">
                            <a href="admin_users.php?toggle_role=<?php echo $uid; ?>"
                               onclick="return confirm('Bạn muốn đổi vai trò của tài khoản này?');"
                               class="u-btn u-btn-role">
                                <i class="fas fa-exchange-alt"></i>
                                <?php echo $is_admin ? 'Hạ xuống Khách' : 'Cấp quyền Admin'; ?>
                            </a>
                            <a href="admin_users.php?delete=<?php echo $uid; ?>"
                               onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn tài khoản này?');"
                               class="u-btn u-btn-delete">
                                <i class="fas fa-trash-alt"></i> Xóa Tài Khoản
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="u-active-session-banner">
                            <i class="fas fa-shield-alt"></i> Tài khoản của bạn đang đăng nhập (Được bảo vệ)
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty"><i class="fas fa-users"></i> Chưa có tài khoản nào trên hệ thống!</p>
        <?php endif; ?>
    </div>
</section>

<script src="../js/admin_script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleCreateUserBtn');
    const createPanel = document.getElementById('quickCreatePanel');
    const searchInput = document.getElementById('userSearchInput');
    const roleButtons = document.querySelectorAll('.role-tab-btn');
    const userCards = document.querySelectorAll('.enterprise-user-card');

    let activeRole = 'all';

    if (toggleBtn && createPanel) {
        toggleBtn.addEventListener('click', function () {
            createPanel.classList.toggle('active');
        });
    }

    function filterUsers() {
        const kw = (searchInput.value || '').trim().toLowerCase();
        userCards.forEach(card => {
            const role = card.getAttribute('data-role');
            const text = card.getAttribute('data-search') || '';
            const matchRole = (activeRole === 'all') || (role === activeRole);
            const matchKw = text.includes(kw);
            card.style.display = (matchRole && matchKw) ? 'flex' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterUsers);

    roleButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            roleButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeRole = this.getAttribute('data-role');
            filterUsers();
        });
    });
});
</script>
</body>
</html>
