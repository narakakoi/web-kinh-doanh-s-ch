<?php

// Shared by all product forms. Never trust the price/name/image sent by a browser.
function requested_product_action(array $input): ?string
{
    $cart = array_key_exists('add_to_cart', $input);
    $wishlist = array_key_exists('add_to_wishlist', $input);
    if ($cart && $wishlist) return 'invalid';
    return $cart ? 'cart' : ($wishlist ? 'wishlist' : null);
}

function handle_product_action(mysqli $conn, int $userId, array $input): array
{
    $_SESSION['product_csrf'] ??= bin2hex(random_bytes(32));
    $action = requested_product_action($input);
    if ($action === null) return [];
    if ($action === 'invalid') {
        http_response_code(400);
        return ['Vui lòng chọn một thao tác: thêm giỏ hàng hoặc yêu thích.'];
    }
    if ($userId <= 0 || !is_string($input['product_csrf'] ?? null)
        || !hash_equals($_SESSION['product_csrf'], $input['product_csrf'])) {
        http_response_code(400);
        return ['Phiên thao tác không hợp lệ. Hãy tải lại trang.'];
    }

    $productId = filter_var($input['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $quantity = $action === 'cart'
        ? filter_var($input['product_quantity'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]])
        : 1;
    if ($productId === false || $productId === null || $quantity === false) {
        http_response_code(400);
        return ['Sản phẩm hoặc số lượng không hợp lệ (từ 1 đến 99 cuốn).'];
    }

    $stmt = mysqli_prepare($conn, 'SELECT id, name, price, image FROM products WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    $book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$book) {
        http_response_code(404);
        return ['Không tìm thấy sách. Sản phẩm có thể đã được gỡ khỏi cửa hàng.'];
    }

    if ($action === 'cart') {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM cart WHERE user_id = ? AND pid = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $productId);
        mysqli_stmt_execute($stmt);
        $inCart = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($inCart) return ['Sản phẩm đã có trong giỏ hàng.'];
        $stmt = mysqli_prepare($conn, 'INSERT INTO cart (user_id, pid, name, price, quantity, image) VALUES (?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iisdis', $userId, $productId, $book['name'], $book['price'], $quantity, $book['image']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        // Remove the saved item only after the cart insert succeeds.
        $stmt = mysqli_prepare($conn, 'DELETE FROM wishlist WHERE user_id = ? AND pid = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $productId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return ['Sản phẩm đã được thêm vào giỏ hàng.'];
    }

    $stmt = mysqli_prepare($conn, 'SELECT id FROM wishlist WHERE user_id = ? AND pid = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $productId);
    mysqli_stmt_execute($stmt);
    $inWishlist = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($inWishlist) {
        return ['Sản phẩm đã có trong danh sách yêu thích.'];
    }
    $stmt = mysqli_prepare($conn, 'INSERT INTO wishlist (user_id, pid, name, price, image) VALUES (?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iisds', $userId, $productId, $book['name'], $book['price'], $book['image']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return ['Sản phẩm đã được thêm vào danh sách yêu thích.'];
}

/** Product IDs are kept as keys for rendering every copy of a book consistently. */
function product_wishlist_ids(mysqli $conn, int $userId): array
{
    $stmt = mysqli_prepare($conn, 'SELECT pid FROM wishlist WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ids = [];
    while ($row = mysqli_fetch_assoc($result)) $ids[(int) $row['pid']] = true;
    mysqli_stmt_close($stmt);
    return $ids;
}

function product_header_counts(mysqli $conn, int $userId): array
{
    $stmt = mysqli_prepare($conn, 'SELECT
        (SELECT COUNT(*) FROM cart WHERE user_id = ?) AS cart_count,
        (SELECT COUNT(*) FROM wishlist WHERE user_id = ?) AS wishlist_count');
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $userId);
    mysqli_stmt_execute($stmt);
    $counts = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return ['cart_count' => (int) $counts['cart_count'], 'wishlist_count' => (int) $counts['wishlist_count']];
}

/** AJAX returns JSON; a regular successful POST redirects to a GET (no replay). */
function process_product_request(mysqli $conn, int $userId, array $input, string $returnUrl): array
{
    $messages = $_SESSION['product_notice'] ?? [];
    unset($_SESSION['product_notice']);
    $action = requested_product_action($input);
    $result = handle_product_action($conn, $userId, $input);
    if ($action === null) return $messages;

    $status = http_response_code() ?: 200;
    $ajax = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
        || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(array_merge([
            'success' => $status < 400,
            'message' => implode(' ', $result),
            'action' => $action,
            'wishlist_ids' => array_keys(product_wishlist_ids($conn, $userId)),
        ], product_header_counts($conn, $userId)), JSON_UNESCAPED_UNICODE);
        exit();
    }
    if ($status < 400) {
        $_SESSION['product_notice'] = $result;
        header('Location: ' . $returnUrl, true, 303);
        exit();
    }
    return array_merge($messages, $result);
}
