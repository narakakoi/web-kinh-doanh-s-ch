<?php
require_once '../config/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu.');
}

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
