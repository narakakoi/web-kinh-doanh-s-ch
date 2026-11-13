<?php
require_once '../config/config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../pages/login.php');
    exit();
}

$message = [];

// Tự động tạo bảng coupons nếu chưa có
try {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `coupons` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `code` varchar(50) NOT NULL UNIQUE,
        `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
        `discount_value` decimal(10,2) NOT NULL,
        `min_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
        `usage_limit` int(11) DEFAULT NULL,
        `used_count` int(11) NOT NULL DEFAULT 0,
        `starts_at` datetime DEFAULT NULL,
        `ends_at` datetime DEFAULT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Throwable $e) {}

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
        $message[] = 'Thông tin mã giảm giá không hợp lệ (Phần trăm tối đa là 100%).';
    } else {
        try {
            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO coupons (code, discount_type, discount_value, min_order_value, usage_limit, starts_at, ends_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($stmt, 'ssddiss', $code, $discountType, $discountValue, $minOrderValue, $usageLimit, $startsAt, $endsAt);
            if (mysqli_stmt_execute($stmt)) {
                $message[] = 'Đã phát hành mã giảm giá ' . $code . ' thành công!';
            }
            mysqli_stmt_close($stmt);
        } catch (Throwable $e) {
            if (mysqli_errno($conn) === 1062) {
                $message[] = 'Mã giảm giá này đã tồn tại trên hệ thống!';
            } else {
                $message[] = 'Không thể tạo mã giảm giá.';
            }
        }
    }
}

if (isset($_GET['toggle'])) {
    $couponId = filter_input(INPUT_GET, 'toggle', FILTER_VALIDATE_INT);
    if ($couponId) {
        try {
            $stmt = mysqli_prepare($conn, 'UPDATE coupons SET is_active = 1 - is_active WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $couponId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } catch (Throwable $e) {}
    }
    header('Location: admin_coupons.php');
    exit();
}

$coupons = false;
try {
    $coupons = mysqli_query($conn, 'SELECT * FROM coupons ORDER BY id DESC');
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Mã Giảm Giá - BookStory Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
    <style>
        /* CSS đặc biệt riêng cho bộ tạo Voucher thông minh */
        .live-voucher-preview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #ac5743 0%, #8e4332 100%);
            color: #fff;
            border-radius: 1.2rem;
            padding: 1.8rem 2.4rem;
            margin-bottom: 2.4rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0.8rem 2rem rgba(172, 87, 67, 0.25);
        }
        .live-voucher-preview::before,
        .live-voucher-preview::after {
            content: '';
            position: absolute;
            width: 2.6rem;
            height: 2.6rem;
            background: #ffffff;
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
        }
        .live-voucher-preview::before { left: -1.3rem; }
        .live-voucher-preview::after { right: -1.3rem; }
        .preview-left {
            display: flex;
            align-items: center;
            gap: 1.6rem;
        }
        .preview-icon {
            width: 5.4rem;
            height: 5.4rem;
            border-radius: 1.2rem;
            background: rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4rem;
        }
        .preview-info .preview-tag {
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 0.08rem;
            opacity: 0.85;
            display: block;
        }
        .preview-info h4 {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0.2rem 0;
        }
        .preview-info p {
            font-size: 1.3rem;
            opacity: 0.9;
        }
        .preview-code-box {
            background: #ffffff;
            color: #ac5743;
            padding: 0.9rem 1.8rem;
            border-radius: 0.8rem;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 0.1rem;
            border: 0.2rem dashed #f59e0b;
        }

        /* Nút chọn hình thức giảm giá dạng Tab đôi */
        .discount-type-switcher {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
            background: #f1f5f9;
            padding: 0.5rem;
            border-radius: 0.9rem;
            border: 0.1rem solid #cbd5e1;
        }
        .type-btn {
            padding: 1rem 1.2rem;
            border-radius: 0.7rem;
            font-size: 1.4rem;
            font-weight: 700;
            color: #64748b;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            transition: all 0.2s ease;
        }
        .type-btn.active {
            background: #ffffff;
            color: #ac5743;
            box-shadow: 0 0.2rem 0.8rem rgba(15, 23, 42, 0.08);
        }

        /* Ô nhập có gắn đơn vị % hoặc VNĐ bên phải */
        .input-with-unit {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-with-unit .box {
            padding-right: 7.5rem !important;
        }
        .unit-badge {
            position: absolute;
            right: 0.8rem;
            padding: 0.6rem 1.2rem;
            border-radius: 0.6rem;
            font-size: 1.4rem;
            font-weight: 700;
            background: #fef3c7;
            color: #d97706;
            pointer-events: none;
        }
        .unit-badge.unit-vnd {
            background: #d1fae5;
            color: #059669;
        }

        /* Nút tạo mã ngẫu nhiên */
        .code-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .code-input-wrap .box {
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.08rem;
            padding-right: 11rem !important;
        }
        .random-code-btn {
            position: absolute;
            right: 0.6rem;
            background: #fdf2f0;
            color: #ac5743;
            border: 0.1rem solid #f5d0c8;
            padding: 0.6rem 1rem;
            border-radius: 0.6rem;
            font-size: 1.25rem;
            font-weight: 700;
            cursor: pointer;
        }
        .random-code-btn:hover {
            background: #ac5743;
            color: #fff;
        }

        /* Các nút gợi ý chọn nhanh mức giảm (Quick Chips) */
        .quick-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 0.6rem;
        }
        .chip-btn {
            background: #f8fafc;
            border: 0.1rem solid #cbd5e1;
            color: #475569;
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            font-size: 1.25rem;
            font-weight: 600;
            cursor: pointer;
        }
        .chip-btn:hover {
            background: #fdf2f0;
            color: #ac5743;
            border-color: #ac5743;
        }
    </style>
</head>
<body>
<?php @include 'admin_header.php'; ?>

<section class="add-products">
    <form method="post" id="couponForm">
        <div class="form-header">
            <h3><i class="fas fa-ticket-alt"></i> Tạo Mã Giảm Giá Mới</h3>
            <p>Thiết lập chương trình khuyến mãi và xem trước thẻ Voucher trực tiếp</p>
        </div>

        <!-- Khung Xem Trước Voucher Trực Tiếp (Live Preview) -->
        <div class="live-voucher-preview">
            <div class="preview-left">
                <div class="preview-icon"><i class="fas fa-gift"></i></div>
                <div class="preview-info">
                    <span class="preview-tag">Xem trước Voucher phát hành</span>
                    <h4 id="previewDiscountText">Giảm 15%</h4>
                    <p id="previewMinOrderText">Áp dụng cho đơn hàng từ 0 VNĐ</p>
                </div>
            </div>
            <div class="preview-code-box" id="previewCodeText">BOOK2026</div>
        </div>

        <div class="form-grid">
            <!-- Ô nhập mã khuyến mãi + Nút tạo ngẫu nhiên -->
            <div class="form-group">
                <label>Mã khuyến mãi (Chữ in hoa & số)</label>
                <div class="code-input-wrap">
                    <input class="box" id="codeInput" name="code" maxlength="50" pattern="[A-Za-z0-9_-]{3,50}" placeholder="VD: LEBEO2026" required>
                    <button type="button" class="random-code-btn" id="randomCodeBtn">
                        <i class="fas fa-magic"></i> Tạo ngẫu nhiên
                    </button>
                </div>
            </div>

            <!-- Bộ chuyển đổi Hình thức giảm giá (% vs VNĐ) -->
            <div class="form-group">
                <label>Hình thức giảm giá</label>
                <input type="hidden" name="discount_type" id="discountTypeInput" value="percent">
                <div class="discount-type-switcher">
                    <button type="button" class="type-btn active" data-type="percent">
                        <i class="fas fa-percentage"></i> Theo phần trăm (%)
                    </button>
                    <button type="button" class="type-btn" data-type="fixed">
                        <i class="fas fa-money-bill-wave"></i> Số tiền cố định (VNĐ)
                    </button>
                </div>
            </div>

            <!-- Ô nhập Giá trị giảm tự động biến đổi theo lựa chọn -->
            <div class="form-group">
                <label id="discountValueLabel">Mức phần trăm giảm (%)</label>
                <div class="input-with-unit">
                    <input class="box" type="number" id="discountValueInput" name="discount_value" min="1" max="100" step="1" placeholder="Nhập từ 1 đến 100 (VD: 15)" required>
                    <span class="unit-badge" id="unitBadge">%</span>
                </div>
                <!-- Dãy nút bấm chọn nhanh -->
                <div class="quick-chips" id="quickChipsContainer"></div>
            </div>

            <!-- Giá trị đơn tối thiểu -->
            <div class="form-group">
                <label>Giá trị đơn tối thiểu (VNĐ)</label>
                <div class="input-with-unit">
                    <input class="box" type="number" id="minOrderInput" name="min_order_value" min="0" step="1000" value="0" placeholder="0 là không giới hạn">
                    <span class="unit-badge unit-vnd">VNĐ</span>
                </div>
                <div class="quick-chips">
                    <button type="button" class="chip-btn min-chip" data-min="0">0đ (Mọi đơn)</button>
                    <button type="button" class="chip-btn min-chip" data-min="50000">Từ 50.000đ</button>
                    <button type="button" class="chip-btn min-chip" data-min="100000">Từ 100.000đ</button>
                    <button type="button" class="chip-btn min-chip" data-min="200000">Từ 200.000đ</button>
                </div>
            </div>

            <div class="form-group full-width">
                <label>Giới hạn lượt sử dụng (Để trống nếu không giới hạn)</label>
                <input class="box" type="number" name="usage_limit" min="1" placeholder="Nhập tổng số lượt khách được dùng mã này...">
            </div>

            <div class="form-group">
                <label>Thời gian bắt đầu</label>
                <input class="box" type="datetime-local" name="starts_at">
            </div>

            <div class="form-group">
                <label>Thời gian kết thúc</label>
                <input class="box" type="datetime-local" name="ends_at">
            </div>
        </div>

        <button class="btn submit-btn" type="submit" name="add_coupon">
            <i class="fas fa-plus-circle"></i> Phát Hành Mã Giảm Giá
        </button>
    </form>
</section>

<section class="coupons-section">
    <h2 class="title">Danh Sách Voucher Hiện Có</h2>
    <div class="box-container">
        <?php if ($coupons && mysqli_num_rows($coupons) > 0): ?>
            <?php while ($coupon = mysqli_fetch_assoc($coupons)): ?>
                <?php $isActive = (bool)$coupon['is_active']; ?>
                <div class="box coupon-card <?php echo $isActive ? 'coupon-active' : 'coupon-inactive'; ?>">
                    <div class="coupon-top">
                        <span class="coupon-code"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($coupon['code'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="status-badge <?php echo $isActive ? 'badge-success' : 'badge-muted'; ?>">
                            <i class="fas <?php echo $isActive ? 'fa-check-circle' : 'fa-ban'; ?>"></i>
                            <?php echo $isActive ? 'Đang bật' : 'Đã tắt'; ?>
                        </span>
                    </div>

                    <div class="coupon-value">
                        Giảm <strong><?php echo number_format((float) $coupon['discount_value'], 0, ',', '.'); ?><?php echo $coupon['discount_type'] === 'percent' ? '%' : ' VNĐ'; ?></strong>
                    </div>

                    <div class="coupon-details">
                        <p><span><i class="fas fa-shopping-cart"></i> Đơn tối thiểu:</span> <strong><?php echo number_format((float) $coupon['min_order_value'], 0, ',', '.'); ?> VNĐ</strong></p>
                        <p><span><i class="fas fa-users"></i> Lượt sử dụng:</span> <strong><?php echo (int) $coupon['used_count']; ?> / <?php echo $coupon['usage_limit'] === null ? '∞' : (int) $coupon['usage_limit']; ?></strong></p>
                        <?php if (!empty($coupon['ends_at'])): ?>
                            <p><span><i class="fas fa-hourglass-end"></i> Hết hạn:</span> <strong><?php echo htmlspecialchars($coupon['ends_at']); ?></strong></p>
                        <?php endif; ?>
                    </div>

                    <a class="<?php echo $isActive ? 'delete-btn' : 'option-btn'; ?> full-btn" href="admin_coupons.php?toggle=<?php echo (int) $coupon['id']; ?>">
                        <i class="fas <?php echo $isActive ? 'fa-power-off' : 'fa-play'; ?>"></i>
                        <?php echo $isActive ? 'Tạm Tắt Mã' : 'Kích Hoạt Mã'; ?>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty"><i class="fas fa-ticket-alt"></i> Chưa có mã giảm giá nào được tạo!</p>
        <?php endif; ?>
    </div>
</section>

<script src="../js/admin_script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const codeInput = document.getElementById('codeInput');
    const randomCodeBtn = document.getElementById('randomCodeBtn');
    const typeButtons = document.querySelectorAll('.type-btn');
    const discountTypeInput = document.getElementById('discountTypeInput');
    const discountValueLabel = document.getElementById('discountValueLabel');
    const discountValueInput = document.getElementById('discountValueInput');
    const unitBadge = document.getElementById('unitBadge');
    const quickChipsContainer = document.getElementById('quickChipsContainer');
    const minOrderInput = document.getElementById('minOrderInput');
    const minChips = document.querySelectorAll('.min-chip');

    // Các phần tử trên thẻ Xem Trước (Live Preview)
    const previewCodeText = document.getElementById('previewCodeText');
    const previewDiscountText = document.getElementById('previewDiscountText');
    const previewMinOrderText = document.getElementById('previewMinOrderText');

    const percentPresets = [
        { label: '5%', value: 5 },
        { label: '10%', value: 10 },
        { label: '15%', value: 15 },
        { label: '20%', value: 20 },
        { label: '50%', value: 50 }
    ];

    const fixedPresets = [
        { label: '10.000đ', value: 10000 },
        { label: '20.000đ', value: 20000 },
        { label: '30.000đ', value: 30000 },
        { label: '50.000đ', value: 50000 },
        { label: '100.000đ', value: 100000 }
    ];

    function formatVND(num) {
        return Number(num || 0).toLocaleString('vi-VN');
    }

    function updatePreview() {
        const code = codeInput.value.trim().toUpperCase() || 'BOOK2026';
        const type = discountTypeInput.value;
        const val = parseFloat(discountValueInput.value) || 0;
        const minOrder = parseFloat(minOrderInput.value) || 0;

        previewCodeText.textContent = code;
        if (type === 'percent') {
            previewDiscountText.textContent = 'Giảm ' + (val > 0 ? val : 15) + '%';
        } else {
            previewDiscountText.textContent = 'Giảm ' + (val > 0 ? formatVND(val) : '30.000') + ' VNĐ';
        }
        previewMinOrderText.textContent = 'Áp dụng cho đơn hàng từ ' + formatVND(minOrder) + ' VNĐ';
    }

    function renderQuickChips(type) {
        const presets = type === 'percent' ? percentPresets : fixedPresets;
        quickChipsContainer.innerHTML = '';
        presets.forEach(item => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chip-btn';
            btn.textContent = 'Chọn ' + item.label;
            btn.addEventListener('click', function () {
                discountValueInput.value = item.value;
                updatePreview();
            });
            quickChipsContainer.appendChild(btn);
        });
    }

    // Xử lý khi bấm chuyển đổi giữa Phần trăm (%) và Số tiền cố định (VNĐ)
    typeButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            typeButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const selectedType = this.getAttribute('data-type');
            discountTypeInput.value = selectedType;
            discountValueInput.value = '';

            if (selectedType === 'percent') {
                discountValueLabel.textContent = 'Mức phần trăm giảm (%)';
                discountValueInput.placeholder = 'Nhập từ 1% đến 100% (VD: 15)';
                discountValueInput.max = '100';
                discountValueInput.step = '1';
                unitBadge.textContent = '%';
                unitBadge.classList.remove('unit-vnd');
            } else {
                discountValueLabel.textContent = 'Số tiền giảm trực tiếp (VNĐ)';
                discountValueInput.placeholder = 'Nhập số tiền giảm (VD: 30000)';
                discountValueInput.removeAttribute('max');
                discountValueInput.step = '1000';
                unitBadge.textContent = 'VNĐ';
                unitBadge.classList.add('unit-vnd');
            }

            renderQuickChips(selectedType);
            updatePreview();
            discountValueInput.focus();
        });
    });

    // Tự động viết hoa mã giảm giá
    codeInput.addEventListener('input', function () {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '');
        updatePreview();
    });

    // Nút tạo mã ngẫu nhiên
    randomCodeBtn.addEventListener('click', function () {
        const prefixes = ['BOOK', 'SACH', 'LEBEO', 'VIP', 'KIMDONG'];
        const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
        const randomNum = Math.floor(100 + Math.random() * 900);
        codeInput.value = prefix + randomNum;
        updatePreview();
    });

    // Chọn nhanh đơn tối thiểu
    minChips.forEach(chip => {
        chip.addEventListener('click', function () {
            minOrderInput.value = this.getAttribute('data-min');
            updatePreview();
        });
    });

    discountValueInput.addEventListener('input', updatePreview);
    minOrderInput.addEventListener('input', updatePreview);

    // Khởi tạo ban đầu
    renderQuickChips('percent');
    updatePreview();
});
</script>
</body>
</html>
