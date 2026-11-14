<?php
// Run against a disposable database: TEST_DB_NAME=... php tests/product_actions.php
// Temporary tables shadow application tables and disappear when this connection closes.
require_once __DIR__ . '/../config/product_actions.php';
require_once __DIR__ . '/../config/passwords.php';
function check(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
check(verify_account_password('old<pass>"', md5((string) filter_var('old<pass>"', filter_id('string')))), 'Legacy filtered password must still work');
check(verify_account_password('admin<pass>"', md5('admin<pass>"')), 'Legacy admin-created password must still work');
$hash = password_hash('new<pass>"', PASSWORD_DEFAULT);
check(verify_account_password('new<pass>"', $hash), 'Modern password must preserve special characters');
check(!verify_account_password('wrong', $hash), 'Wrong password accepted');
check(!verify_account_password(str_repeat('a', 73), password_hash(str_repeat('a', 72), PASSWORD_DEFAULT)), 'Bcrypt truncation accepted');
check(!verify_account_password("bad\0password", $hash), 'NUL password accepted');
$db = getenv('TEST_DB_NAME');
if (!$db) { throw new RuntimeException('Set TEST_DB_NAME to a disposable database'); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_connect(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root', getenv('DB_PASSWORD') ?: '', $db);
mysqli_set_charset($conn, 'utf8mb4');
mysqli_query($conn, 'CREATE TEMPORARY TABLE products (id INT PRIMARY KEY, name VARCHAR(100), price DECIMAL(10,2), image VARCHAR(100))');
mysqli_query($conn, 'CREATE TEMPORARY TABLE cart (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, pid INT, name VARCHAR(100), price DECIMAL(10,2), quantity INT, image VARCHAR(100))');
mysqli_query($conn, 'CREATE TEMPORARY TABLE wishlist (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, pid INT, name VARCHAR(100), price DECIMAL(10,2), image VARCHAR(100))');
mysqli_query($conn, "INSERT INTO products VALUES (1, 'Same title', 120000, 'book.jpg'), (2, 'Same title', 90000, 'other.jpg')");
$_SESSION = [];
check(handle_product_action($conn, 7, []) === [], 'Rendering must not mutate');
$input = ['product_csrf' => $_SESSION['product_csrf'], 'product_id' => 1, 'product_quantity' => 2, 'product_name' => 'forged', 'product_price' => 1, 'product_image' => 'forged.jpg', 'add_to_wishlist' => 1];
handle_product_action($conn, 7, $input);
check(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT * FROM wishlist'))['name'] === 'Same title', 'Forged wishlist metadata accepted');
unset($input['add_to_wishlist']); $input['add_to_cart'] = 1;
handle_product_action($conn, 7, $input);
$row = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT * FROM cart'));
check((float) $row['price'] === 120000.0 && $row['name'] === 'Same title' && $row['image'] === 'book.jpg' && (int) $row['quantity'] === 2, 'Cart must use catalog metadata and submitted quantity');
check((int) mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM wishlist'))[0] === 0, 'Wishlist item was not moved');
handle_product_action($conn, 7, $input);
check((int) mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM cart'))[0] === 1, 'Duplicate cart item');
$input['product_id'] = 2; handle_product_action($conn, 7, $input);
check((int) mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM cart'))[0] === 2, 'Same-title books must be distinct by ID');
foreach ([0, -1, 100, 'abc', []] as $quantity) {
    $input['product_quantity'] = $quantity;
    handle_product_action($conn, 8, $input);
}
$input['product_quantity'] = 1; $input['product_id'] = 999;
handle_product_action($conn, 8, $input);
$input['product_id'] = 1; $input['product_csrf'] = 'invalid';
handle_product_action($conn, 8, $input);
$input['product_csrf'] = [];
handle_product_action($conn, 8, $input);
check((int) mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM cart WHERE user_id = 8'))[0] === 0, 'Invalid requests changed cart');
echo "Product actions and password compatibility checks passed\n";
