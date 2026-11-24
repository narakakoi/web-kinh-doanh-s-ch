<?php
declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$localConfigPath = $projectRoot . '/config/local.php';
$localConfig = [];
if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (is_array($loadedConfig)) {
        $localConfig = $loadedConfig;
    }
}

$dbSetting = static function (string $environmentVariable, string $key, string $default) use ($localConfig): string {
    $value = getenv($environmentVariable);
    return $value !== false ? $value : (string)($localConfig[$key] ?? $default);
};

$dbPort = getenv('DB_PORT');

// Xác định tiền tố URL từ tệp PHP hiện tại, bao gồm các thư mục pages/ và api/.
// BOOKBOT_SITE_PATH ghi đè giá trị này khi máy chủ trung gian viết lại URL công khai.
$siteBasePath = '';
$scriptFile = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
$projectPrefix = str_replace('\\', '/', $projectRoot) . '/';
$scriptUrl = $_SERVER['SCRIPT_NAME'] ?? '';
if (str_starts_with($scriptFile, $projectPrefix)) {
    $relativeScript = substr($scriptFile, strlen($projectPrefix));
    if ($relativeScript !== '' && str_ends_with($scriptUrl, $relativeScript)) {
        $siteBasePath = substr($scriptUrl, 0, -strlen($relativeScript));
    }
}
$pathOverride = getenv('BOOKBOT_SITE_PATH');

$settings = [
    'db' => [
        'host' => $dbSetting('DB_HOST', 'db_host', 'localhost'),
        'port' => $dbPort !== false ? (int)$dbPort : 3306,
        'name' => $dbSetting('DB_NAME', 'db_name', 'shop_db'),
        'user' => $dbSetting('DB_USER', 'db_user', 'root'),
        'pass' => $dbSetting('DB_PASSWORD', 'db_password', ''),
        'charset' => 'utf8mb4',
    ],
    'site_base_path' => $pathOverride !== false ? $pathOverride : $siteBasePath,
    // Đường dẫn PHP tương đối. Đặt trang thành false để hiện lại BookBot tại đó.
    'hidden_pages' => [
        'pages/cart.php' => true,
        'pages/checkout.php' => true, // Bao gồm trạng thái chờ QR và thanh toán thành công.
        'pages/orders.php' => true,
    ],
    'product_table' => 'products',
    'product_columns' => [
        'id' => 'id',
        'name' => 'name',
        'description' => 'details',
        'category' => 'category',
        'price' => 'price',
        'image' => 'image',
        'stock' => null,
    ],
    'product_url_template' => '/pages/view_page.php?pid={id}',
    'currency_symbol' => '₫',
    'openai' => [
        'api_key' => getenv('OPENAI_API_KEY') ?: '',
        'model' => getenv('OPENAI_MODEL') ?: 'gpt-4.1-mini',
        'endpoint' => 'https://api.openai.com/v1/responses',
        'timeout_seconds' => 35,
        'max_output_tokens' => 500,
    ],
    'store_faq' => [
        'shipping' => 'Vui lòng xem mục Chính sách giao hàng trên website hoặc liên hệ cửa hàng để biết phí và thời gian giao hàng chính xác.',
        'payment' => 'Website hiển thị các phương thức thanh toán được hỗ trợ tại bước thanh toán. Vui lòng kiểm tra thông tin tại trang thanh toán.',
        'return' => 'Vui lòng xem mục Chính sách đổi trả trên website hoặc liên hệ bộ phận chăm sóc khách hàng để được xác nhận điều kiện áp dụng.',
        'contact' => 'Bạn có thể gửi yêu cầu qua trang Liên hệ của cửa hàng để được nhân viên hỗ trợ.',
    ],
    // Thêm từ nhận diện tại đây thay vì sửa phần xử lý yêu cầu.
    'faq_keywords' => [
        'shipping' => ['giao hàng', 'vận chuyển', 'ship', 'bao lâu nhận'],
        'payment' => ['thanh toán', 'trả tiền', 'chuyển khoản', 'COD'],
        'return' => ['đổi trả', 'hoàn tiền', 'trả hàng', 'đổi sách'],
        'contact' => ['liên hệ', 'hotline', 'nhân viên', 'chăm sóc khách hàng'],
    ],
    'book_topic_keywords' => [
        'sách', 'cuốn sách', 'đọc sách', 'truyện', 'tiểu thuyết', 'tác giả',
        'nội dung', 'tóm tắt', 'cốt truyện', 'nhân vật', 'thể loại',
        'sở thích đọc', 'sở thích sách', 'phù hợp với tôi', 'hợp với tôi',
        'gợi ý sách', 'đề xuất sách',
        'truyện tranh', 'manga', 'trinh thám', 'tình cảm', 'kinh dị',
        'khoa học', 'lịch sử', 'phiêu lưu', 'tâm lý', 'kinh tế', 'thiếu nhi',
        'fantasy', 'science fiction', 'mystery', 'romance', 'horror',
        'history', 'adventure', 'psychology', 'children books',
        'review', 'đánh giá', 'cảm nhận', 'book', 'novel', 'author',
        'genre', 'plot', 'summary', 'story',
    ],
    'book_content_keywords' => [
        'nội dung', 'tóm tắt', 'cốt truyện', 'câu chuyện', 'nhân vật',
        'kết thúc', 'ý nghĩa', 'nói về gì', 'nói gì', 'review', 'đánh giá',
        'cảm nhận', 'plot', 'summary', 'characters', 'ending', 'content of',
        'story',
    ],
    'book_genre_keywords' => ['thể loại', 'dòng sách', 'genre', 'category'],
    'search' => [
        'ignore_phrases' => [
            'gợi ý', 'đề xuất', 'giới thiệu', 'phù hợp với tôi',
            'cho người mới bắt đầu', 'cho người mới học', 'cho người mới',
            'nội dung', 'tóm tắt', 'cốt truyện', 'câu chuyện', 'nhân vật',
            'kết thúc', 'ý nghĩa', 'nói về gì', 'nói gì', 'review', 'đánh giá',
            'cảm nhận', 'thể loại', 'dòng sách',
            'tôi thích', 'mình thích', 'tôi muốn đọc', 'mình muốn đọc',
            'sở thích', 'hợp với tôi',
            'gợi ý sách', 'đề xuất sách',
            'content of', 'characters', 'ending', 'story',
            'book recommendation', 'i like', 'i prefer', 'my favorite',
        ],
        'stop_words' => [
            'tôi', 'mình', 'muốn', 'cần', 'tìm', 'mua', 'cho', 'một', 'cuốn',
            'quyển', 'sách', 'nào', 'với', 'về', 'là', 'có', 'không', 'shop',
            'bạn', 'hãy', 'giúp', 'vui', 'lòng', 'hay', 'đọc', 'nên', 'và',
            'ơi', 'nhé', 'được', 'thích', 'yêu', 'đang', 'hiện',
            'của', 'này', 'đó', 'gì', 'thường',
            'the', 'a', 'an', 'book', 'novel', 'content', 'plot', 'summary',
            'genre', 'category', 'like', 'love', 'prefer', 'what', 'is', 'of',
            'about', 'story', 'recommend', 'recommendation',
        ],
    ],
    'user_session_key' => 'user_id',
];

// Cấu hình ghi đè riêng là tùy chọn; các giá trị mặc định vẫn được Git quản lý và dễ cập nhật.
$overrideFile = __DIR__ . '/config.local.php';
if (is_file($overrideFile)) {
    $overrides = require $overrideFile;
    if (!is_array($overrides)) {
        throw new RuntimeException('bookstory-ai-chatbot/config.local.php phải trả về một array.');
    }
    $settings = array_replace_recursive($settings, $overrides);
}
$settings['site_base_path'] = rtrim((string)$settings['site_base_path'], '/');
$settings['chatbot_url_path'] ??= $settings['site_base_path'] . '/bookstory-ai-chatbot';
$settings['image_base_url'] ??= $settings['site_base_path'] . '/uploaded_img/';
return $settings;
