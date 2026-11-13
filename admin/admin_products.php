<?php
@include '../config/config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../pages/login.php');
    exit();
}

if (isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $price = (float)$_POST['price'];
    $details = mysqli_real_escape_string($conn, trim($_POST['details']));

    $image = $_FILES['image']['name'];
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = '../uploaded_img/' . $image;

    $select_product_name = mysqli_query($conn, "SELECT name FROM `products` WHERE name = '$name'") or die('Truy vấn thất bại');

    if (mysqli_num_rows($select_product_name) > 0) {
        $message[] = 'Tên đầu sách này đã tồn tại trong kho!';
    } else {
        if ($image_size > 5000000) {
            $message[] = 'Kích thước ảnh bìa quá lớn (tối đa 5MB)!';
        } else {
            $insert_product = mysqli_query($conn, "INSERT INTO `products`(name, category, details, price, image) VALUES('$name', '$category', '$details', '$price', '$image')") or die('Truy vấn thất bại');
            if ($insert_product) {
                if (!is_dir('../uploaded_img')) {
                    @mkdir('../uploaded_img', 0777, true);
                }
                move_uploaded_file($image_tmp_name, $image_folder);
                $message[] = 'Đã đăng bán đầu sách mới thành công!';
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $select_delete_image = mysqli_query($conn, "SELECT image FROM `products` WHERE id = '$delete_id'") or die('Truy vấn thất bại');
    if ($fetch_delete_image = mysqli_fetch_assoc($select_delete_image)) {
        if (!empty($fetch_delete_image['image']) && file_exists('../uploaded_img/' . $fetch_delete_image['image'])) {
            @unlink('../uploaded_img/' . $fetch_delete_image['image']);
        }
    }
    mysqli_query($conn, "DELETE FROM `products` WHERE id = '$delete_id'") or die('Truy vấn thất bại');
    mysqli_query($conn, "DELETE FROM `wishlist` WHERE pid = '$delete_id'");
    mysqli_query($conn, "DELETE FROM `cart` WHERE pid = '$delete_id'");
    header('location:admin_products.php');
    exit();
}

// Lấy toàn bộ sản phẩm để thống kê & hiển thị
$all_products = [];
$categories_count = ['all' => 0];
$total_catalog_value = 0;

$select_products = mysqli_query($conn, "SELECT * FROM `products` ORDER BY id DESC") or die('Truy vấn thất bại');
while ($row = mysqli_fetch_assoc($select_products)) {
    $all_products[] = $row;
    $categories_count['all']++;
    $cat = !empty($row['category']) ? $row['category'] : 'Khác';
    if (!isset($categories_count[$cat])) {
        $categories_count[$cat] = 0;
    }
    $categories_count[$cat]++;
    $total_catalog_value += (float)$row['price'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Quản Lý Sản Phẩm - BookStory Admin</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css?v=<?php echo time(); ?>">
   <style>
      /* =========================================================
         CSS NÂNG CẤP STUDIO THÊM SẢN PHẨM & QUẢN LÝ KHO SÁCH
         ========================================================= */
      .product-studio-section {
         max-width: 1300px;
         margin: 0 auto;
         padding: 2.5rem 2rem 1rem;
      }

      .studio-layout {
         display: grid;
         grid-template-columns: 1.65fr 1fr;
         gap: 2.6rem;
         align-items: start;
      }

      .studio-form-card {
         background: var(--white);
         border: var(--border);
         border-radius: 1.8rem;
         padding: 3rem;
         box-shadow: 0 1.2rem 3rem rgba(15, 23, 42, 0.06);
      }

      .studio-header {
         display: flex;
         align-items: center;
         justify-content: space-between;
         padding-bottom: 1.8rem;
         margin-bottom: 2.2rem;
         border-bottom: var(--border);
      }

      .studio-title-group {
         display: flex;
         align-items: center;
         gap: 1.4rem;
      }

      .studio-icon-badge {
         width: 4.8rem;
         height: 4.8rem;
         border-radius: 1.2rem;
         background: linear-gradient(135deg, #ac5743 0%, #8e4332 100%);
         color: #fff;
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 2.1rem;
         box-shadow: 0 0.6rem 1.4rem rgba(172, 87, 67, 0.28);
      }

      .studio-title-group h3 {
         font-size: 2.2rem;
         font-weight: 700;
         color: var(--black);
         margin-bottom: 0.3rem;
      }

      .studio-title-group p {
         font-size: 1.4rem;
         color: var(--light-color);
      }

      .studio-step-badge {
         background: #fdf2f0;
         color: var(--brown);
         font-size: 1.25rem;
         font-weight: 700;
         padding: 0.6rem 1.2rem;
         border-radius: 2rem;
         border: 0.1rem solid #f5d0c8;
      }

      .studio-grid {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 1.8rem;
      }

      .studio-field {
         display: flex;
         flex-direction: column;
      }

      .studio-field.full-span {
         grid-column: 1 / -1;
      }

      .studio-label {
         display: flex;
         align-items: center;
         justify-content: space-between;
         font-size: 1.4rem;
         font-weight: 700;
         color: var(--black);
         margin-bottom: 0.7rem;
      }

      .studio-label span i {
         color: var(--brown);
         margin-right: 0.5rem;
      }

      .studio-input,
      .studio-select,
      .studio-textarea {
         width: 100%;
         background-color: #f8fafc;
         border: 0.15rem solid #cbd5e1;
         border-radius: 1rem;
         padding: 1.25rem 1.4rem;
         font-size: 1.5rem;
         color: var(--black);
         transition: all 0.2s ease;
         -webkit-appearance: none;
         appearance: none;
      }

      .studio-input:focus,
      .studio-select:focus,
      .studio-textarea:focus {
         background-color: #ffffff;
         border-color: var(--brown);
         box-shadow: 0 0 0 0.35rem rgba(172, 87, 67, 0.14);
      }

      .select-wrapper {
         position: relative;
      }

      .select-wrapper::after {
         content: '\f078';
         font-family: 'Font Awesome 6 Free';
         font-weight: 900;
         position: absolute;
         right: 1.4rem;
         top: 50%;
         transform: translateY(-50%);
         color: var(--brown);
         font-size: 1.3rem;
         pointer-events: none;
      }

      .price-input-wrapper {
         position: relative;
         display: flex;
         align-items: center;
      }

      .price-input-wrapper .studio-input {
         padding-right: 7rem;
         font-weight: 700;
         color: var(--red);
      }

      .currency-tag {
         position: absolute;
         right: 0.8rem;
         background: #fee2e2;
         color: #dc2626;
         font-size: 1.3rem;
         font-weight: 700;
         padding: 0.5rem 1rem;
         border-radius: 0.6rem;
         pointer-events: none;
      }

      .quick-action-chips {
         display: flex;
         flex-wrap: wrap;
         gap: 0.6rem;
         margin-top: 0.7rem;
      }

      .q-chip {
         background: #f1f5f9;
         color: #475569;
         border: 0.1rem solid #e2e8f0;
         padding: 0.45rem 1rem;
         border-radius: 2rem;
         font-size: 1.2rem;
         font-weight: 600;
         cursor: pointer;
         transition: all 0.15s ease;
      }

      .q-chip:hover {
         background: #fdf2f0;
         color: var(--brown);
         border-color: var(--brown);
      }

      .upload-dropzone {
         position: relative;
         border: 0.2rem dashed #cbd5e1;
         border-radius: 1.2rem;
         background: #f8fafc;
         padding: 1.4rem 1.6rem;
         text-align: center;
         cursor: pointer;
         transition: all 0.2s ease;
         display: flex;
         align-items: center;
         justify-content: space-between;
         gap: 1.2rem;
      }

      .upload-dropzone:hover {
         border-color: var(--brown);
         background: #fffaf9;
      }

      .upload-dropzone input[type="file"] {
         position: absolute;
         inset: 0;
         opacity: 0;
         cursor: pointer;
         width: 100%;
         height: 100%;
      }

      .dropzone-info {
         display: flex;
         align-items: center;
         gap: 1.2rem;
         text-align: left;
      }

      .dropzone-icon {
         width: 4.4rem;
         height: 4.4rem;
         border-radius: 1rem;
         background: #fdf2f0;
         color: var(--brown);
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 2rem;
         flex-shrink: 0;
      }

      .dropzone-text h4 {
         font-size: 1.4rem;
         font-weight: 700;
         color: var(--black);
         margin-bottom: 0.2rem;
      }

      .dropzone-text p {
         font-size: 1.2rem;
         color: var(--light-color);
      }

      .dropzone-btn-fake {
         background: var(--white);
         color: var(--brown);
         border: 0.15rem solid #f5d0c8;
         padding: 0.6rem 1.2rem;
         border-radius: 0.8rem;
         font-size: 1.25rem;
         font-weight: 700;
         white-space: nowrap;
      }

      .char-counter {
         font-size: 1.25rem;
         font-weight: 600;
         color: var(--light-color);
      }

      .studio-submit-btn {
         width: 100%;
         margin-top: 2rem;
         padding: 1.4rem;
         font-size: 1.65rem;
         border-radius: 1rem;
         background: linear-gradient(135deg, #ac5743 0%, #8e4332 100%);
         color: #fff;
         font-weight: 700;
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 0.8rem;
         box-shadow: 0 0.8rem 2rem rgba(172, 87, 67, 0.28);
         cursor: pointer;
      }

      .studio-submit-btn:hover {
         transform: translateY(-2px);
         box-shadow: 0 1rem 2.4rem rgba(172, 87, 67, 0.36);
      }

      /* Cột phải: Thẻ Xem Trước Sản Phẩm Trực Tiếp */
      .studio-preview-panel {
         background: var(--white);
         border: var(--border);
         border-radius: 1.8rem;
         padding: 2.4rem;
         box-shadow: 0 1.2rem 3rem rgba(15, 23, 42, 0.06);
         position: sticky;
         top: 9.5rem;
      }

      .preview-panel-header {
         display: flex;
         align-items: center;
         justify-content: space-between;
         margin-bottom: 1.6rem;
         padding-bottom: 1.2rem;
         border-bottom: var(--border);
      }

      .preview-panel-header h4 {
         font-size: 1.6rem;
         font-weight: 700;
         color: var(--black);
         display: flex;
         align-items: center;
         gap: 0.6rem;
      }

      .preview-panel-header h4 i {
         color: var(--brown);
      }

      .live-dot {
         display: inline-flex;
         align-items: center;
         gap: 0.5rem;
         font-size: 1.2rem;
         font-weight: 700;
         color: #059669;
         background: #d1fae5;
         padding: 0.35rem 0.9rem;
         border-radius: 2rem;
      }

      .live-dot::before {
         content: '';
         width: 0.7rem;
         height: 0.7rem;
         background: #10b981;
         border-radius: 50%;
      }

      .mockup-card {
         background: #f8fafc;
         border: 0.15rem solid #e2e8f0;
         border-radius: 1.4rem;
         padding: 1.8rem;
         text-align: center;
      }

      .mockup-cover-box {
         position: relative;
         height: 22rem;
         background: #ffffff;
         border-radius: 1rem;
         border: 0.1rem solid #e2e8f0;
         display: flex;
         align-items: center;
         justify-content: center;
         overflow: hidden;
         margin-bottom: 1.4rem;
      }

      .mockup-cover-box img {
         max-height: 90% !important;
         max-width: 90% !important;
         width: auto !important;
         height: auto !important;
         object-fit: contain !important;
         border-radius: 0.4rem;
         display: none;
      }

      .mockup-placeholder {
         display: flex;
         flex-direction: column;
         align-items: center;
         color: #94a3b8;
         gap: 0.8rem;
      }

      .mockup-placeholder i {
         font-size: 4.2rem;
         color: #cbd5e1;
      }

      .mockup-placeholder span {
         font-size: 1.3rem;
         font-weight: 600;
      }

      .mockup-cat-badge {
         position: absolute;
         top: 1rem;
         left: 1rem;
         background: rgba(15, 23, 42, 0.78);
         color: #fff;
         padding: 0.4rem 1rem;
         border-radius: 2rem;
         font-size: 1.2rem;
         font-weight: 700;
      }

      .mockup-title {
         font-size: 1.75rem;
         font-weight: 700;
         color: var(--black);
         margin-bottom: 0.6rem;
         line-height: 1.4;
         word-break: break-word;
      }

      .mockup-price {
         font-size: 2.1rem;
         font-weight: 700;
         color: var(--red);
         margin-bottom: 0.8rem;
      }

      .mockup-desc {
         font-size: 1.35rem;
         color: var(--light-color);
         line-height: 1.5;
         background: #ffffff;
         padding: 1rem 1.2rem;
         border-radius: 0.8rem;
         border: 0.1rem solid #e2e8f0;
         text-align: left;
         max-height: 9.5rem;
         overflow-y: auto;
      }

      /* =========================================================
         THANH CÔNG CỤ & LƯỚI THẺ SÁCH (ĐÃ KHÓA KÍCH THƯỚC ẢNH)
         ========================================================= */
      .inventory-toolbar {
         max-width: 1300px;
         margin: 0 auto 2.4rem;
         background: var(--white);
         border: var(--border);
         border-radius: 1.4rem;
         padding: 1.8rem 2.2rem;
         box-shadow: var(--box-shadow);
         display: flex;
         flex-direction: column;
         gap: 1.6rem;
      }

      .toolbar-top {
         display: flex;
         align-items: center;
         justify-content: space-between;
         flex-wrap: wrap;
         gap: 1.4rem;
      }

      .search-box-wrap {
         position: relative;
         flex: 1;
         min-width: 26rem;
      }

      .search-box-wrap i {
         position: absolute;
         left: 1.5rem;
         top: 50%;
         transform: translateY(-50%);
         color: var(--light-color);
         font-size: 1.5rem;
      }

      .search-box-wrap input {
         width: 100%;
         background: #f8fafc;
         border: 0.15rem solid #cbd5e1;
         border-radius: 1rem;
         padding: 1.1rem 1.4rem 1.1rem 4.2rem;
         font-size: 1.45rem;
         color: var(--black);
      }

      .search-box-wrap input:focus {
         background: #fff;
         border-color: var(--brown);
      }

      .toolbar-Controls {
         display: flex;
         align-items: center;
         gap: 1.2rem;
         flex-wrap: wrap;
      }

      .sort-select {
         background: #f8fafc;
         border: 0.15rem solid #cbd5e1;
         border-radius: 1rem;
         padding: 1.05rem 1.4rem;
         font-size: 1.4rem;
         font-weight: 600;
         color: var(--black);
         cursor: pointer;
      }

      .inventory-summary-pill {
         background: #fdf2f0;
         color: var(--brown);
         padding: 1rem 1.4rem;
         border-radius: 1rem;
         font-size: 1.35rem;
         font-weight: 700;
      }

      .category-filter-pills {
         display: flex;
         align-items: center;
         gap: 0.8rem;
         flex-wrap: wrap;
         padding-top: 1.2rem;
         border-top: var(--border);
      }

      .cat-filter-btn {
         background: #f1f5f9;
         color: #475569;
         padding: 0.7rem 1.4rem;
         border-radius: 2rem;
         font-size: 1.35rem;
         font-weight: 700;
         cursor: pointer;
         display: inline-flex;
         align-items: center;
         gap: 0.6rem;
         transition: all 0.2s ease;
      }

      .cat-filter-btn .count-bubble {
         background: rgba(15, 23, 42, 0.1);
         padding: 0.15rem 0.7rem;
         border-radius: 1rem;
         font-size: 1.2rem;
      }

      .cat-filter-btn.active {
         background: var(--brown);
         color: #fff;
      }

      .cat-filter-btn.active .count-bubble {
         background: rgba(255, 255, 255, 0.25);
         color: #fff;
      }

      /* Khóa kích thước chuẩn cho từng thẻ sách trong danh mục */
      .show-products .box-container {
         display: grid !important;
         grid-template-columns: repeat(auto-fill, minmax(27rem, 1fr)) !important;
         gap: 2.4rem !important;
         max-width: 1300px !important;
         margin: 0 auto !important;
         align-items: stretch !important;
      }

      .show-products .box-container .box.product-card {
         background: var(--white) !important;
         border: var(--border) !important;
         border-radius: 1.6rem !important;
         padding: 1.8rem !important;
         box-shadow: var(--box-shadow) !important;
         display: flex !important;
         flex-direction: column !important;
         justify-content: space-between !important;
         overflow: hidden !important;
         position: relative !important;
         text-align: left !important;
      }

      .show-products .box-container .box.product-card .image-wrapper {
         position: relative !important;
         width: 100% !important;
         height: 25rem !important;
         background: #f8fafc !important;
         border-radius: 1.2rem !important;
         border: 0.1rem solid #e2e8f0 !important;
         display: flex !important;
         align-items: center !important;
         justify-content: center !important;
         overflow: hidden !important;
         padding: 1.2rem !important;
         margin-bottom: 1.4rem !important;
      }

      .show-products .box-container .box.product-card .image-wrapper img {
         width: 100% !important;
         height: 100% !important;
         max-width: 100% !important;
         max-height: 22.5rem !important;
         object-fit: contain !important;
         border-radius: 0.6rem !important;
         margin: 0 auto !important;
         display: block !important;
         transition: transform 0.3s ease !important;
      }

      .show-products .box-container .box.product-card:hover .image-wrapper img {
         transform: scale(1.04) !important;
      }

      .show-products .box-container .box.product-card .category-tag {
         position: absolute !important;
         top: 1rem !important;
         right: 1rem !important;
         background: rgba(15, 23, 42, 0.78) !important;
         color: #ffffff !important;
         padding: 0.4rem 1rem !important;
         border-radius: 2rem !important;
         font-size: 1.15rem !important;
         font-weight: 700 !important;
         z-index: 2 !important;
      }

      .show-products .box-container .box.product-card .price {
         position: absolute !important;
         top: 1rem !important;
         left: 1rem !important;
         background: var(--brown) !important;
         color: #ffffff !important;
         padding: 0.5rem 1.1rem !important;
         border-radius: 0.8rem !important;
         font-size: 1.35rem !important;
         font-weight: 700 !important;
         z-index: 2 !important;
         box-shadow: 0 0.4rem 1rem rgba(172, 87, 67, 0.28) !important;
      }

      .show-products .box-container .box.product-card .content-wrapper {
         flex: 1 !important;
         display: flex !important;
         flex-direction: column !important;
         margin-bottom: 1.4rem !important;
      }

      .show-products .box-container .box.product-card .name {
         font-size: 1.7rem !important;
         font-weight: 700 !important;
         color: var(--black) !important;
         margin-bottom: 0.6rem !important;
         line-height: 1.4 !important;
         display: -webkit-box !important;
         -webkit-line-clamp: 2 !important;
         line-clamp: 2 !important;
         -webkit-box-orient: vertical !important;
         overflow: hidden !important;
         min-height: 4.7rem !important;
      }

      .show-products .box-container .box.product-card .details {
         font-size: 1.35rem !important;
         color: var(--light-color) !important;
         line-height: 1.55 !important;
         display: -webkit-box !important;
         -webkit-line-clamp: 3 !important;
         line-clamp: 3 !important;
         -webkit-box-orient: vertical !important;
         overflow: hidden !important;
      }

      .show-products .box-container .box.product-card .action-buttons {
         display: grid !important;
         grid-template-columns: 1fr 1fr !important;
         gap: 1rem !important;
         width: 100% !important;
         margin-top: auto !important;
      }

      .show-products .box-container .box.product-card .action-buttons a {
         margin: 0 !important;
         width: 100% !important;
         display: inline-flex !important;
         align-items: center !important;
         justify-content: center !important;
         gap: 0.6rem !important;
         padding: 1rem !important;
      }

      @media (max-width: 991px) {
         .studio-layout {
            grid-template-columns: 1fr;
         }
         .studio-preview-panel {
            position: static;
         }
      }

      @media (max-width: 600px) {
         .studio-grid {
            grid-template-columns: 1fr;
         }
      }
   </style>
</head>
<body>
   
<?php @include 'admin_header.php'; ?>

<!-- PHẦN 1: STUDIO THÊM SẢN PHẨM MỚI + LIVE PREVIEW -->
<section class="product-studio-section">
   <div class="studio-layout">
      <form class="studio-form-card" action="" method="POST" enctype="multipart/form-data">
         <div class="studio-header">
            <div class="studio-title-group">
               <div class="studio-icon-badge"><i class="fas fa-book-medical"></i></div>
               <div>
                  <h3>Thêm Sản Phẩm Mới</h3>
                  <p>Điền thông tin để đăng bán đầu sách mới lên hệ thống BookStory</p>
               </div>
            </div>
            <span class="studio-step-badge"><i class="fas fa-bolt"></i> Chuẩn TMĐT</span>
         </div>

         <div class="studio-grid">
            <div class="studio-field">
               <label class="studio-label">
                  <span><i class="fas fa-heading"></i> Tên đầu sách</span>
               </label>
               <input type="text" class="studio-input" id="bookNameInput" name="name" placeholder="Ví dụ: Đắc Nhân Tâm - Bản Đặc Biệt" required>
            </div>

            <div class="studio-field">
               <label class="studio-label">
                  <span><i class="fas fa-layer-group"></i> Danh mục thể loại</span>
               </label>
               <div class="select-wrapper">
                  <select name="category" id="bookCategoryInput" class="studio-select" required>
                     <option value="" disabled selected>-- Chọn thể loại sách --</option>
                     <option value="Truyện tranh">Truyện tranh (Manga / Comic)</option>
                     <option value="Tiểu thuyết">Tiểu thuyết & Văn học</option>
                     <option value="Sách tham khảo">Sách tham khảo & Học thuật</option>
                     <option value="Kỹ năng sống">Kỹ năng sống & Phát triển bản thân</option>
                     <option value="Kinh tế">Kinh tế & Quản trị kinh doanh</option>
                     <option value="Thiếu nhi">Sách Thiếu nhi</option>
                     <option value="Khác">Thể loại khác</option>
                  </select>
               </div>
            </div>

            <div class="studio-field">
               <label class="studio-label">
                  <span><i class="fas fa-tag"></i> Giá bán niêm yết</span>
                  <small id="formattedPriceHint" style="color: var(--brown); font-weight:700;">0 VNĐ</small>
               </label>
               <div class="price-input-wrapper">
                  <input type="number" min="1000" step="1000" class="studio-input" id="bookPriceInput" name="price" placeholder="Ví dụ: 85000" required>
                  <span class="currency-tag">VNĐ</span>
               </div>
               <div class="quick-action-chips">
                  <button type="button" class="q-chip price-chip" data-price="45000">45.000đ</button>
                  <button type="button" class="q-chip price-chip" data-price="68000">68.000đ</button>
                  <button type="button" class="q-chip price-chip" data-price="85000">85.000đ</button>
                  <button type="button" class="q-chip price-chip" data-price="120000">120.000đ</button>
                  <button type="button" class="q-chip price-chip" data-price="150000">150.000đ</button>
               </div>
            </div>

            <div class="studio-field">
               <label class="studio-label">
                  <span><i class="fas fa-image"></i> Ảnh bìa sách (JPG, PNG)</span>
               </label>
               <div class="upload-dropzone" id="uploadDropzone">
                  <input type="file" id="bookImageInput" accept="image/jpg, image/jpeg, image/png, image/webp" name="image" required>
                  <div class="dropzone-info">
                     <div class="dropzone-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                     <div class="dropzone-text">
                        <h4 id="fileNameDisplay">Tải ảnh bìa từ máy Mac</h4>
                        <p id="fileSizeDisplay">Kéo thả hoặc bấm để chọn (Tối đa 5MB)</p>
                     </div>
                  </div>
                  <span class="dropzone-btn-fake">Chọn ảnh</span>
               </div>
            </div>

            <div class="studio-field full-span">
               <label class="studio-label">
                  <span><i class="fas fa-align-left"></i> Mô tả nội dung & Thông tin xuất bản</span>
                  <span class="char-counter" id="descCharCounter">0 / 500 ký tự</span>
               </label>
               <textarea name="details" id="bookDescInput" class="studio-textarea" placeholder="Nhập tóm tắt nội dung cuốn sách, tác giả, nhà xuất bản, số trang..." rows="4" required></textarea>
               <div class="quick-action-chips">
                  <button type="button" class="q-chip tpl-chip" data-tpl="• Nhà xuất bản: NXB Kim Đồng&#10;• Hình thức: Bìa mềm, in màu sắc nét.&#10;">+ Mẫu thông tin NXB</button>
                  <button type="button" class="q-chip tpl-chip" data-tpl="• Sách mới 100% nguyên seal, đóng gói chống sốc kỹ lưỡng.&#10;">+ Cam kết sách chính hãng</button>
                  <button type="button" class="q-chip tpl-chip" data-tpl="• Nội dung cuốn hút, phù hợp cho độc giả yêu thích khám phá và học tập.&#10;">+ Gợi ý lời giới thiệu</button>
               </div>
            </div>
         </div>

         <button type="submit" class="studio-submit-btn" name="add_product">
            <i class="fas fa-cloud-upload-alt"></i> Đăng Bán Sản Phẩm Vào Kho
         </button>
      </form>

      <aside class="studio-preview-panel">
         <div class="preview-panel-header">
            <h4><i class="fas fa-eye"></i> Xem Trước Hiển Thị</h4>
            <span class="live-dot">Trực tiếp</span>
         </div>

         <div class="mockup-card">
            <div class="mockup-cover-box">
               <span class="mockup-cat-badge" id="previewCatBadge">Thể loại sách</span>
               <img id="previewCoverImg" src="" alt="Preview">
               <div class="mockup-placeholder" id="previewPlaceholder">
                  <i class="fas fa-book-open"></i>
                  <span>Ảnh bìa sẽ hiện tại đây</span>
               </div>
            </div>
            <h3 class="mockup-title" id="previewBookTitle">Tên đầu sách mới</h3>
            <div class="mockup-price" id="previewBookPrice">0 VNĐ</div>
            <div class="mockup-desc" id="previewBookDesc">
               Tóm tắt nội dung cuốn sách và thông tin xuất bản sẽ hiển thị trực tiếp tại đây khi bạn gõ...
            </div>
         </div>
      </aside>
   </div>
</section>

<!-- PHẦN 2: DANH MỤC SÁCH ĐANG KINH DOANH -->
<section class="show-products">
   <h2 class="title">Danh Mục Sách Đang Kinh Doanh</h2>

   <div class="inventory-toolbar">
      <div class="toolbar-top">
         <div class="search-box-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="inventorySearchInput" placeholder="Tìm nhanh theo tên đầu sách hoặc mô tả...">
         </div>

         <div class="toolbar-Controls">
            <select id="inventorySortSelect" class="sort-select">
               <option value="newest">Sắp xếp: Mới thêm gần đây</option>
               <option value="price-asc">Giá bán: Thấp đến Cao</option>
               <option value="price-desc">Giá bán: Cao đến Thấp</option>
               <option value="name-asc">Tên sách: A → Z</option>
            </select>

            <div class="inventory-summary-pill">
               <i class="fas fa-boxes"></i> Kho: <strong><?php echo count($all_products); ?></strong> đầu sách | Tổng trị giá: <strong><?php echo number_format($total_catalog_value, 0, ',', '.'); ?>đ</strong>
            </div>
         </div>
      </div>

      <div class="category-filter-pills">
         <button type="button" class="cat-filter-btn active" data-cat="all">
            Tất cả thể loại <span class="count-bubble"><?php echo $categories_count['all']; ?></span>
         </button>
         <?php foreach ($categories_count as $cat_name => $count): ?>
            <?php if ($cat_name !== 'all'): ?>
               <button type="button" class="cat-filter-btn" data-cat="<?php echo htmlspecialchars($cat_name); ?>">
                  <?php echo htmlspecialchars($cat_name); ?> <span class="count-bubble"><?php echo $count; ?></span>
               </button>
            <?php endif; ?>
         <?php endforeach; ?>
      </div>
   </div>

   <div class="box-container" id="productsGridContainer">
      <?php if (count($all_products) > 0): ?>
         <?php foreach ($all_products as $fetch_products): ?>
            <?php
               $img_name = $fetch_products['image'];
               $img_src = '../uploaded_img/' . $img_name;
               if (!file_exists($img_src) && file_exists('../images/' . $img_name)) {
                  $img_src = '../images/' . $img_name;
               }
            ?>
            <div class="box product-card"
                 data-id="<?php echo (int)$fetch_products['id']; ?>"
                 data-name="<?php echo htmlspecialchars(mb_strtolower($fetch_products['name'], 'UTF-8')); ?>"
                 data-category="<?php echo htmlspecialchars($fetch_products['category'] ?: 'Khác'); ?>"
                 data-price="<?php echo (float)$fetch_products['price']; ?>">
               <div class="image-wrapper">
                  <span class="category-tag"><?php echo htmlspecialchars($fetch_products['category'] ?: 'Sách'); ?></span>
                  <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($fetch_products['name']); ?>">
                  <div class="price"><?php echo number_format((float)$fetch_products['price'], 0, ',', '.'); ?> VNĐ</div>
               </div>
               <div class="content-wrapper">
                  <div class="name"><?php echo htmlspecialchars($fetch_products['name']); ?></div>
                  <div class="details"><?php echo htmlspecialchars($fetch_products['details']); ?></div>
               </div>
               <div class="action-buttons">
                  <a href="admin_update_product.php?update=<?php echo $fetch_products['id']; ?>" class="option-btn">
                     <i class="fas fa-edit"></i> Sửa
                  </a>
                  <a href="admin_products.php?delete=<?php echo $fetch_products['id']; ?>" class="delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa cuốn sách này khỏi kho?');">
                     <i class="fas fa-trash-alt"></i> Xóa
                  </a>
               </div>
            </div>
         <?php endforeach; ?>
      <?php else: ?>
         <p class="empty"><i class="fas fa-box-open"></i> Hiện chưa có cuốn sách nào được đăng bán!</p>
      <?php endif; ?>
   </div>
</section>

<script src="../js/admin_script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
   const nameInput = document.getElementById('bookNameInput');
   const catInput = document.getElementById('bookCategoryInput');
   const priceInput = document.getElementById('bookPriceInput');
   const descInput = document.getElementById('bookDescInput');
   const imgInput = document.getElementById('bookImageInput');
   const formattedPriceHint = document.getElementById('formattedPriceHint');
   const descCharCounter = document.getElementById('descCharCounter');
   const fileNameDisplay = document.getElementById('fileNameDisplay');
   const fileSizeDisplay = document.getElementById('fileSizeDisplay');

   const previewBookTitle = document.getElementById('previewBookTitle');
   const previewCatBadge = document.getElementById('previewCatBadge');
   const previewBookPrice = document.getElementById('previewBookPrice');
   const previewBookDesc = document.getElementById('previewBookDesc');
   const previewCoverImg = document.getElementById('previewCoverImg');
   const previewPlaceholder = document.getElementById('previewPlaceholder');

   function formatVND(val) {
      return Number(val || 0).toLocaleString('vi-VN') + ' VNĐ';
   }

   nameInput.addEventListener('input', function () {
      previewBookTitle.textContent = this.value.trim() || 'Tên đầu sách mới';
   });

   catInput.addEventListener('change', function () {
      previewCatBadge.textContent = this.value || 'Thể loại sách';
   });

   priceInput.addEventListener('input', function () {
      const formatted = formatVND(this.value);
      formattedPriceHint.textContent = formatted;
      previewBookPrice.textContent = formatted;
   });

   document.querySelectorAll('.price-chip').forEach(chip => {
      chip.addEventListener('click', function () {
         priceInput.value = this.getAttribute('data-price');
         priceInput.dispatchEvent(new Event('input'));
      });
   });

   descInput.addEventListener('input', function () {
      const len = this.value.length;
      descCharCounter.textContent = len + ' / 500 ký tự';
      previewBookDesc.textContent = this.value.trim() || 'Tóm tắt nội dung cuốn sách và thông tin xuất bản sẽ hiển thị trực tiếp tại đây khi bạn gõ...';
   });

   document.querySelectorAll('.tpl-chip').forEach(chip => {
      chip.addEventListener('click', function () {
         const tpl = this.getAttribute('data-tpl');
         descInput.value = (descInput.value ? descInput.value + '\n' : '') + tpl;
         descInput.dispatchEvent(new Event('input'));
         descInput.focus();
      });
   });

   imgInput.addEventListener('change', function () {
      const file = this.files[0];
      if (file) {
         fileNameDisplay.textContent = file.name;
         const sizeKB = (file.size / 1024).toFixed(1);
         fileSizeDisplay.textContent = 'Dung lượng: ' + sizeKB + ' KB (Hợp lệ)';

         const reader = new FileReader();
         reader.onload = function (e) {
            previewCoverImg.src = e.target.result;
            previewCoverImg.style.display = 'block';
            previewPlaceholder.style.display = 'none';
         };
         reader.readAsDataURL(file);
      }
   });

   const searchInput = document.getElementById('inventorySearchInput');
   const sortSelect = document.getElementById('inventorySortSelect');
   const catButtons = document.querySelectorAll('.cat-filter-btn');
   const gridContainer = document.getElementById('productsGridContainer');
   const productCards = Array.from(document.querySelectorAll('.product-card'));

   let currentCategory = 'all';

   function filterAndSortProducts() {
      const keyword = (searchInput.value || '').trim().toLowerCase();
      const sortBy = sortSelect.value;

      productCards.forEach(card => {
         const name = card.getAttribute('data-name') || '';
         const cat = card.getAttribute('data-category') || '';
         const matchKeyword = name.includes(keyword);
         const matchCat = (currentCategory === 'all') || (cat === currentCategory);

         card.style.setProperty('display', (matchKeyword && matchCat) ? 'flex' : 'none', 'important');
      });

      const sortedCards = productCards.slice().sort((a, b) => {
         if (sortBy === 'price-asc') {
            return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
         } else if (sortBy === 'price-desc') {
            return parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price'));
         } else if (sortBy === 'name-asc') {
            return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '', 'vi');
         } else {
            return parseInt(b.getAttribute('data-id')) - parseInt(a.getAttribute('data-id'));
         }
      });

      sortedCards.forEach(card => gridContainer.appendChild(card));
   }

   if (searchInput) searchInput.addEventListener('input', filterAndSortProducts);
   if (sortSelect) sortSelect.addEventListener('change', filterAndSortProducts);

   catButtons.forEach(btn => {
      btn.addEventListener('click', function () {
         catButtons.forEach(b => b.classList.remove('active'));
         this.classList.add('active');
         currentCategory = this.getAttribute('data-cat');
         filterAndSortProducts();
      });
   });
});
</script>
</body>
</html>
